<?php
session_start();
include('../config/dbcon.php');
include('../includes/store_filter_helper.php');
include('../includes/permission_helper.php'); // Store filtering helper

// TEMPORARY FIX: Reconcile Payments & Dues
if(isset($_GET['fix_balance']) && $_GET['fix_balance'] == 1) {
    if(isset($_SESSION['auth_user'])) { 
        $fix_cust = mysqli_query($conn, "SELECT id FROM customers");
        $reconciled_count = 0;
        
        while($fc = mysqli_fetch_assoc($fix_cust)) {
            $fid = $fc['id'];
            
            // 1. Identify Orphaned/Unallocated Logs
            $orphan_query = "SELECT * FROM sell_logs 
                             WHERE customer_id = $fid 
                             AND type IN ('payment', 'Full Payment', 'Partial Payment', 'Due Paid')
                             AND (ref_invoice_id IS NULL OR ref_invoice_id = '' OR ref_invoice_id NOT IN (SELECT invoice_id FROM selling_info))";
            $orphan_res = mysqli_query($conn, $orphan_query);
            
            $distributable_amount = 0;
            $orphans_to_delete = [];
            
            while($log = mysqli_fetch_assoc($orphan_res)) {
                $distributable_amount += floatval($log['amount']);
                $orphans_to_delete[] = $log['id'];
            }
            
            // 2. Distribute to Open Invoices (Oldest First)
            if($distributable_amount > 0) {
                // Get invoices with positive computed due
                $inv_q = mysqli_query($conn, "SELECT * FROM selling_info WHERE customer_id = $fid AND inv_type = 'sale' ORDER BY created_at ASC");
                
                while($inv = mysqli_fetch_assoc($inv_q)) {
                    if($distributable_amount <= 0.01) break;
                    
                    $iid = $inv['invoice_id'];
                    $gtotal = floatval($inv['grand_total']);
                    
                    // Check already paid for this invoice
                    $paid_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as val FROM sell_logs WHERE ref_invoice_id = '$iid' AND type IN ('payment', 'Full Payment', 'Partial Payment', 'Due Paid')"));
                    $already_paid = floatval($paid_res['val'] ?? 0);
                    
                    $curr_due = $gtotal - $already_paid;
                    
                    if($curr_due > 0.01) {
                        $pay_amount = min($curr_due, $distributable_amount);
                        
                        // Create New Linked Log
                        $ref_no = 'REC' . date('YmdHis') . rand(100,999);
                        $uid = $_SESSION['auth_user']['user_id'] ?? 1;
                        $store_id = $inv['store_id'];
                        
                        $ins_sql = "INSERT INTO sell_logs (customer_id, reference_no, ref_invoice_id, type, amount, store_id, created_by, description, pmethod_id) 
                                    VALUES ($fid, '$ref_no', '$iid', 'Due Paid', $pay_amount, $store_id, $uid, 'Reconciled Payment', 1)";
                        mysqli_query($conn, $ins_sql);
                        
                        $distributable_amount -= $pay_amount;
                    }
                }
                
                // Delete consumed orphans
                if(!empty($orphans_to_delete)) {
                    $ids = implode(',', $orphans_to_delete);
                    mysqli_query($conn, "DELETE FROM sell_logs WHERE id IN ($ids)");
                }
                
                // If remainder exists, re-insert
                if($distributable_amount > 0.01) {
                     $ref_no = 'REM' . date('YmdHis');
                     mysqli_query($conn, "INSERT INTO sell_logs (customer_id, reference_no, ref_invoice_id, type, amount, store_id, created_by, description) 
                                          VALUES ($fid, '$ref_no', 'ADVANCE', 'payment', $distributable_amount, 0, 1, 'Unallocated Excess')");
                }
                
                $reconciled_count++;
            }
            
            // 3. Final Recalculation
            $fsales = floatval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as val FROM selling_info WHERE customer_id = $fid AND inv_type = 'sale'"))['val'] ?? 0);
            $freturns = floatval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(grand_total) as val FROM selling_info WHERE customer_id = $fid AND inv_type = 'return'"))['val'] ?? 0);
            $fpay = floatval(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as val FROM sell_logs WHERE customer_id = $fid AND type IN ('payment', 'Full Payment', 'Partial Payment', 'Due Paid')"))['val'] ?? 0);
            
            $fdue = round($fsales - $freturns - $fpay, 2);
            if($fdue < 0) $fdue = 0;
            mysqli_query($conn, "UPDATE customers SET current_due = $fdue WHERE id = $fid");
        }
        
        echo "<script>alert('Data Reconciled & Fixed! ($reconciled_count customers affected)'); window.location.href='/pos/customers/list';</script>";
        exit;
    }
}

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch customers with store count (Active relationship count)
// We use a subquery to count how many stores each customer is assigned to

// Get store filter (JOIN + WHERE)
$store_filter = getStoreFilterWithJoin('customers', 'c');
$store_join = $store_filter['join'];
$store_where = $store_filter['where'];

$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM customer_stores_map WHERE customer_id = c.id) as store_count
          FROM customers c
          {$store_join}
          WHERE 1=1 {$store_where}
          ORDER BY c.sort_order ASC, c.id DESC";
$query_run = mysqli_query($conn, $query);

$customers = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $customers[] = $row;
}

// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_customer_customer') ? '/pos/customers/add' : '#';
$edit_url = check_user_permission('update_customer_customer') ? '/pos/customers/edit' : '#';
$delete_url = check_user_permission('delete_customer_customer') ? '/pos/customers/delete' : '#';

// Prepare data for reusable list component
$list_config = [
    'title' => 'Customer List',
    'add_url' => $add_url,
    'table_id' => 'customerTable',
    'columns' => [
        ['key' => 'image', 'label' => 'Photo', 'type' => 'image', 'path' => '/pos/uploads/customers/'],
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Full Name', 'sortable' => true],
        ['key' => 'code_name', 'label' => 'Code', 'sortable' => true],
        ['key' => 'customer_group', 'label' => 'Group', 'type' => 'badge', 'badge_class' => 'bg-indigo-500/20 text-indigo-400'], 
        ['key' => 'membership_level', 'label' => 'Level', 'type' => 'badge', 'badge_class' => 'bg-pink-500/20 text-pink-400'], 
        ['key' => 'mobile', 'label' => 'Phone', 'sortable' => false],
        ['key' => 'current_due', 'label' => 'Current Due', 'sortable' => true, 'badge_class' => 'text-red-500 font-bold'],
        ['key' => 'reward_points', 'label' => 'Points', 'sortable' => true],
        // Changed badge color to Green/Emerald for Customers to distinguish from Suppliers
        ['key' => 'store_count', 'label' => 'Stores', 'type' => 'badge', 'badge_class' => 'bg-emerald-500/20 text-emerald-400'], 
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $customers,
    'edit_url' => $edit_url,
    'delete_url' => $delete_url,
    'status_url' => '/pos/customers/status',
    'primary_key' => 'id',
    'name_field' => 'name' // Used for delete confirmation message (e.g. "Delete John Doe?")
];

$page_title = "Customer List - Velocity POS";
include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    

    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php 
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
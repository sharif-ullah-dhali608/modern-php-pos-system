<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Quotations with Supplier Relation
$query = "SELECT q.*, s.name as supplier_name 
          FROM quotations q
          LEFT JOIN suppliers s ON q.supplier_id = s.id
          ORDER BY q.id DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        if($row['customer_id'] == 1) {
            $row['customer_display'] = 'Walk-in Customer';
        } elseif($row['customer_id'] == 2) {
            $row['customer_display'] = 'Regular Customer';
        } else {
            $row['customer_display'] = 'Unknown';
        }

        $row['supplier_display'] = $row['supplier_name'] ?? 'N/A';
        $row['formatted_date'] = date('d M, Y', strtotime($row['date']));
        $row['grand_total_display'] = 'TK ' . number_format($row['grand_total'], 2);
        
        $items[] = $row;
    }
}

// Configuration for reusable_list.php
$list_config = [
    'title' => 'Quotation List',
    'add_url' => '/pos/quotations/add',
    'table_id' => 'quotationTable',
    'columns' => [
        ['key' => 'formatted_date', 'label' => 'Date', 'sortable' => true],
        ['key' => 'ref_no', 'label' => 'Reference No', 'sortable' => true],
        ['key' => 'customer_display', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'supplier_display', 'label' => 'Supplier', 'sortable' => true],
        ['key' => 'order_tax_rate', 'label' => 'Tax (%)', 'sortable' => true],
        ['key' => 'grand_total_display', 'label' => 'Grand Total', 'sortable' => true, 'type' => 'badge', 'badge_class' => 'bg-teal-500/10 text-teal-600 font-bold'],
        
        // ✅ [UPDATED] Status with Custom Labels (Sent / Pending)
        [
            'key' => 'status', 
            'label' => 'Status', 
            'type' => 'status', 
            'active_label' => 'Sent',    // 1 হলে Sent দেখাবে
            'inactive_label' => 'Pending' // 0 হলে Pending দেখাবে
        ],
        
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/quotations/edit',
    'delete_url' => '/pos/quotations/save_quotation.php',
    'status_url' => '/pos/quotations/save_quotation.php',
    'primary_key' => 'id',
    'name_field' => 'ref_no'
];

$page_title = "Quotation List - Velocity POS";
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
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
<?php
session_start();
include('../config/dbcon.php');
include('../includes/store_filter_helper.php');
include('../includes/permission_helper.php'); // Store filtering helper

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Fetch suppliers with store count (Active relationship count)
// We use a subquery to count how many stores each supplier is assigned to

// Get store filter (JOIN + WHERE)
$store_filter = getStoreFilterWithJoin('suppliers', 's');
$store_join = $store_filter['join'];
$store_where = $store_filter['where'];

$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM supplier_stores_map WHERE supplier_id = s.id) as store_count
          FROM suppliers s
          {$store_join}
          WHERE 1=1 {$store_where}
          ORDER BY s.sort_order ASC, s.id DESC";
$query_run = mysqli_query($conn, $query);

$suppliers = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $suppliers[] = $row;
}

// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_supplier_supplier') ? '/pos/suppliers/add' : '#';
$edit_url = check_user_permission('update_supplier_supplier') ? '/pos/suppliers/edit' : '#';
$delete_url = check_user_permission('delete_supplier_supplier') ? '/pos/suppliers/delete' : '#';

// Prepare data for reusable list component
$list_config = [
    'title' => 'Supplier List',
    'add_url' => $add_url,
    'table_id' => 'supplierTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Supplier Name', 'sortable' => true],
        ['key' => 'code_name', 'label' => 'Code', 'sortable' => true],
        ['key' => 'mobile', 'label' => 'Mobile', 'sortable' => false],
        ['key' => 'email', 'label' => 'Email', 'sortable' => false],
        ['key' => 'city', 'label' => 'City', 'sortable' => true],
        ['key' => 'store_count', 'label' => 'Stores', 'type' => 'badge', 'badge_class' => 'bg-purple-500/20 text-purple-400'], // Different color for distinction
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $suppliers,
    'edit_url' => $edit_url,
    'delete_url' => $delete_url,
    'status_url' => '/pos/suppliers/save',
    'primary_key' => 'id',
    'name_field' => 'name' // Used for delete confirmation message (e.g. "Delete Global Traders?")
];

$page_title = "Supplier List - Velocity POS";
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
                // This assumes reusable_list.php handles the table generation, 
                // search, status toggles, and delete modals dynamically.
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
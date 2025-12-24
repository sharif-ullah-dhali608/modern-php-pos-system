<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Fetch suppliers with store count (Active relationship count)
// We use a subquery to count how many stores each supplier is assigned to
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM supplier_stores_map WHERE supplier_id = s.id) as store_count
          FROM suppliers s 
          ORDER BY s.sort_order ASC, s.id DESC";
$query_run = mysqli_query($conn, $query);

$suppliers = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $suppliers[] = $row;
}

// Prepare data for reusable list component
$list_config = [
    'title' => 'Supplier List',
    'add_url' => '/pos/suppliers/add', // Points to the create/edit file
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
    'edit_url' => '/pos/suppliers/edit', // Points to the create/edit file
    'delete_url' => '/pos/suppliers/save_supplier.php',
    'status_url' => '/pos/suppliers/save_supplier.php',
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
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
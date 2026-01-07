<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Fetch customers with store count (Active relationship count)
// We use a subquery to count how many stores each customer is assigned to
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM customer_stores_map WHERE customer_id = c.id) as store_count
          FROM customers c 
          ORDER BY c.sort_order ASC, c.id DESC";
$query_run = mysqli_query($conn, $query);

$customers = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $customers[] = $row;
}

// Prepare data for reusable list component
$list_config = [
    'title' => 'Customer List',
    'add_url' => '/pos/customers/add', // Points to the create/edit file
    'table_id' => 'customerTable',
    'columns' => [
        ['key' => 'image', 'label' => 'Photo', 'type' => 'image', 'path' => '/pos/uploads/customers/'],
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Full Name', 'sortable' => true],
        ['key' => 'code_name', 'label' => 'Code', 'sortable' => true],
        ['key' => 'customer_group', 'label' => 'Group', 'type' => 'badge', 'badge_class' => 'bg-indigo-500/20 text-indigo-400'], 
        ['key' => 'membership_level', 'label' => 'Level', 'type' => 'badge', 'badge_class' => 'bg-pink-500/20 text-pink-400'], 
        ['key' => 'mobile', 'label' => 'Phone', 'sortable' => false],
        ['key' => 'reward_points', 'label' => 'Points', 'sortable' => true],
        // Changed badge color to Green/Emerald for Customers to distinguish from Suppliers
        ['key' => 'store_count', 'label' => 'Stores', 'type' => 'badge', 'badge_class' => 'bg-emerald-500/20 text-emerald-400'], 
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $customers,
    'edit_url' => '/pos/customers/edit', // Points to the edit file (often same as add.php?id=x)
    'delete_url' => '/pos/customers/save_customer.php',
    'status_url' => '/pos/customers/save_customer.php',
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
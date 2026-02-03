<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin"); // Clean URL for signin
    exit(0);
}

$query = "SELECT * FROM payment_methods ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

$list_config = [
    'title' => 'Payment Method List',
    'add_url' => '/pos/payment-methods/add', // Clean URL
    'table_id' => 'paymentTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code Name', 'sortable' => true],
        ['key' => 'details', 'label' => 'Details', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/payment-methods/edit', // Clean URL base for edit
    'delete_url' => '/pos/payment_methods/save_payment_method.php', // Backend action paths usually keep .php or use a specific rewrite
    'status_url' => '/pos/payment_methods/save_payment_method.php', 
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Payment Method List - POS";
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
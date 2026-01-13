<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Log Details";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Fetch Data
$query = "SELECT sl.*, c.name as customer_name, pm.name as pmethod_name, u.name as created_by_name 
          FROM sell_logs sl 
          LEFT JOIN customers c ON sl.customer_id = c.id 
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id 
          LEFT JOIN users u ON sl.created_by = u.id 
          ORDER BY sl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Prepare Config
$config = [
    'title' => 'Sell Log Details',
    'table_id' => 'sell_log_table',
    'add_url' => '#', 
    'edit_url' => '#',
    'delete_url' => '#',
    'view_url' => '#', // No view page specified
    'primary_key' => 'id',
    'name_field' => 'reference_no',
    'data' => $data,
    'columns' => [
        ['label' => 'Created At', 'key' => 'created_at'],
        ['label' => 'Type', 'key' => 'type', 'type' => 'badge', 'badge_class' => 'bg-amber-100 text-amber-700'],
        ['label' => 'Customer', 'key' => 'customer_name'],
        ['label' => 'Payment Method', 'key' => 'pmethod_name'],
        ['label' => 'Created By', 'key' => 'created_by_name'],
        ['label' => 'Amount', 'key' => 'amount'],
        ['label' => 'View', 'key' => 'actions', 'type' => 'actions'] 
    ]
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <?php renderReusableList($config); ?>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

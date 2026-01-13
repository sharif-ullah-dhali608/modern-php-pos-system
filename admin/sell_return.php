<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Return List";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Fetch Data
// Assuming inv_type 'return' indicates a return
$query = "SELECT si.*, c.name as customer_name, u.name as return_by 
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          LEFT JOIN users u ON si.created_by = u.id 
          WHERE si.inv_type = 'return' 
          ORDER BY si.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Prepare Config
$config = [
    'title' => 'Sell Return List',
    'table_id' => 'sell_return_table',
    'add_url' => '#', // No add button requested for return list usually
    'edit_url' => '#',
    'delete_url' => '#', // Implement delete logic if needed
    'view_url' => '/pos/invoice/view', 
    'primary_key' => 'info_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'columns' => [
        ['label' => 'Date Time', 'key' => 'created_at'],
        ['label' => 'Customer Name', 'key' => 'customer_name'],
        // ['label' => 'Ref No.', 'key' => 'ref_invoice_id'], // Adjust based on requirement
        ['label' => 'Old Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Purchase Invoice Id', 'key' => 'purchased_id'], // Placeholder column
        ['label' => 'Return Note', 'key' => 'invoice_note'],
        ['label' => 'Amount', 'key' => 'grand_total', 'type' => 'text'],
        ['label' => 'Returned By', 'key' => 'return_by'],
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

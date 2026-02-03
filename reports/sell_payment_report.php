<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Payment Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT sl.*, u.name as user_name, pm.name as payment_method_name, c.name as customer_name
          FROM sell_logs sl
          JOIN users u ON sl.created_by = u.id
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
          LEFT JOIN selling_info si ON sl.ref_invoice_id = si.invoice_id
          LEFT JOIN customers c ON si.customer_id = c.id
          WHERE 1=1 ";

applyDateFilter($query, 'sl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY sl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_collected = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['customer_display'] = $row['customer_name'] ?: 'Walking Customer';
    $row['amount_formatted'] = number_format($row['amount'], 2);
    $total_collected += $row['amount'];
    $data[] = $row;
}

$config = [
    'title' => 'Sell Payment Report',
    'table_id' => 'sell_payment_table',
    'primary_key' => 'id',
    'name_field' => 'ref_invoice_id',
    'data' => $data,
    'date_column' => 'sl.created_at',
    'summary_cards' => [
        ['label' => 'Total Payments Received', 'value' => number_format($total_collected, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Transactions', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Customer', 'key' => 'customer_display'],
        ['label' => 'Payment Method', 'key' => 'payment_method_name'],
        ['label' => 'Collector', 'key' => 'user_name'],
        ['label' => 'Amount', 'key' => 'amount_formatted'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/invoice/view',
    'primary_key' => 'ref_invoice_id'
];
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
                renderReusableList($config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

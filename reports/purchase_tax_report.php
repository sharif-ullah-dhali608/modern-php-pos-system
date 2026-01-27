<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Purchase Tax Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT pin.invoice_id, pin.created_at, pin.order_tax as order_tax_rate,
          (SELECT SUM(item_tax) FROM purchase_item WHERE invoice_id = pin.invoice_id) as item_tax,
          (SELECT SUM(item_total) FROM purchase_item WHERE invoice_id = pin.invoice_id) as subtotal
          FROM purchase_info pin
          WHERE 1=1 ";

applyDateFilter($query, 'pin.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY pin.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_tax = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $order_tax_amount = ($row['subtotal'] ?? 0) * (($row['order_tax_rate'] ?? 0) / 100);
    $row['order_tax_formatted'] = number_format($order_tax_amount, 2);
    $row['item_tax_formatted'] = number_format($row['item_tax'] ?? 0, 2);
    $row['total_tax_per_inv'] = $order_tax_amount + ($row['item_tax'] ?? 0);
    $row['total_tax_formatted'] = number_format($row['total_tax_per_inv'], 2);
    
    $total_tax += $row['total_tax_per_inv'];
    $data[] = $row;
}

$config = [
    'title' => 'Purchase Tax Report',
    'table_id' => 'purchase_tax_table',
    'primary_key' => 'invoice_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'date_column' => 'pin.created_at',
    'summary_cards' => [
        ['label' => 'Total Tax Paid', 'value' => number_format($total_tax, 2), 'border_color' => 'border-purple-500'],
        ['label' => 'Invoices', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Invoice Id', 'key' => 'invoice_id'],
        ['label' => 'Order Tax', 'key' => 'order_tax_formatted'],
        ['label' => 'Item Tax', 'key' => 'item_tax_formatted'],
        ['label' => 'Total Tax', 'key' => 'total_tax_formatted'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/purchases/view'
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 overflow-hidden">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        <div class="content-scroll-area h-full overflow-y-auto p-6 bg-slate-50">
            <?php renderReusableList($config); ?>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

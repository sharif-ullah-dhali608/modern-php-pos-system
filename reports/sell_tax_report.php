<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Tax Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT si.invoice_id, si.created_at, si.tax_amount as order_tax,
          (SELECT SUM(CASE 
                WHEN tax_type = 'inclusive' THEN (price_sold * qty_sold) - ((price_sold * qty_sold) / (1 + (tax_rate / 100)))
                ELSE (price_sold * qty_sold) * (tax_rate / 100)
              END) FROM selling_item WHERE invoice_id = si.invoice_id) as item_tax
          FROM selling_info si
          WHERE si.inv_type = 'sale' ";

applyDateFilter($query, 'si.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY si.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_tax = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['order_tax_formatted'] = number_format($row['order_tax'] ?? 0, 2);
    $row['item_tax_formatted'] = number_format($row['item_tax'] ?? 0, 2);
    $row['total_tax_per_inv'] = ($row['order_tax'] ?? 0) + ($row['item_tax'] ?? 0);
    $row['total_tax_formatted'] = number_format($row['total_tax_per_inv'], 2);
    
    $total_tax += $row['total_tax_per_inv'];
    $data[] = $row;
}

$config = [
    'title' => 'Sell Tax Report',
    'table_id' => 'sell_tax_table',
    'primary_key' => 'invoice_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'date_column' => 'si.created_at',
    'summary_cards' => [
        ['label' => 'Total Tax Collected', 'value' => number_format($total_tax, 2), 'border_color' => 'border-purple-500'],
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
    'view_url' => '/pos/invoice/view'
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

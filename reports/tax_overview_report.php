<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Tax Overview - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// 1. Sell Tax Calculation
// 1.1 Total Order Tax
$order_tax_q = "SELECT SUM(tax_amount) as total FROM selling_info WHERE inv_type = 'sale' ";
applyDateFilter($order_tax_q, 'created_at', $date_filter, $start_date, $end_date);
$res_o = mysqli_query($conn, $order_tax_q);
$total_order_tax = mysqli_fetch_assoc($res_o)['total'] ?? 0;

// 1.2 Total Item Tax (Need join with selling_info for date filter)
$item_tax_q = "SELECT SUM(CASE 
                WHEN tax_type = 'inclusive' THEN (price_sold * qty_sold) - ((price_sold * qty_sold) / (1 + (tax_rate / 100)))
                ELSE (price_sold * qty_sold) * (tax_rate / 100)
              END) as total 
              FROM selling_item si_item
              JOIN selling_info si ON si_item.invoice_id = si.invoice_id
              WHERE si.inv_type = 'sale' ";
applyDateFilter($item_tax_q, 'si.created_at', $date_filter, $start_date, $end_date);
$res_i = mysqli_query($conn, $item_tax_q);
$total_item_tax = mysqli_fetch_assoc($res_i)['total'] ?? 0;

$total_sell_tax = $total_order_tax + $total_item_tax;

// 2. Purchase Tax Calculation
// 2.1 Total Order Tax
$pur_order_tax_q = "SELECT SUM((SELECT SUM(item_total) FROM purchase_item WHERE invoice_id = pin.invoice_id) * pin.order_tax / 100) as total FROM purchase_info pin ";
applyDateFilter($pur_order_tax_q, 'created_at', $date_filter, $start_date, $end_date);
$res_po = mysqli_query($conn, $pur_order_tax_q);
$total_pur_order_tax = mysqli_fetch_assoc($res_po)['total'] ?? 0;

// 2.2 Total Item Tax (Summing item_tax from purchase_item joined with purchase_info for date)
$pur_item_tax_q = "SELECT SUM(pi_item.item_tax) as total 
                  FROM purchase_item pi_item
                  JOIN purchase_info pin ON pi_item.invoice_id = pin.invoice_id ";
applyDateFilter($pur_item_tax_q, 'pin.created_at', $date_filter, $start_date, $end_date);
$res_pi = mysqli_query($conn, $pur_item_tax_q);
$total_pur_item_tax = mysqli_fetch_assoc($res_pi)['total'] ?? 0;

$total_purchase_tax = $total_pur_order_tax + $total_pur_item_tax;

$data = [
    [
        'id' => 1,
        'type' => 'Output Tax (Sales)',
        'amount' => $total_sell_tax,
        'formatted' => number_format($total_sell_tax, 2)
    ],
    [
        'id' => 2,
        'type' => 'Input Tax (Purchases)',
        'amount' => $total_purchase_tax,
        'formatted' => number_format($total_purchase_tax, 2)
    ],
    [
        'id' => 3,
        'type' => 'Net Tax Liability',
        'amount' => $total_sell_tax - $total_purchase_tax,
        'formatted' => number_format($total_sell_tax - $total_purchase_tax, 2)
    ]
];

$config = [
    'title' => 'Tax Overview Report',
    'table_id' => 'tax_overview_table',
    'primary_key' => 'id',
    'name_field' => 'type',
    'data' => $data,
    'date_column' => 'created_at', // Placeholder for date filter helper UI
    'summary_cards' => [
        ['label' => 'Net Tax Liability', 'value' => number_format($total_sell_tax - $total_purchase_tax, 2), 'border_color' => 'border-purple-500'],
    ],
    'columns' => [
        ['label' => 'Tax Type', 'key' => 'type'],
        ['label' => 'Total Amount', 'key' => 'formatted'],
    ],
    'action_buttons' => []
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

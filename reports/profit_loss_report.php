<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Profit and Loss - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// 1. Total Sales
$sale_q = "SELECT SUM(grand_total) as total FROM selling_info WHERE inv_type = 'sale' ";
applyDateFilter($sale_q, 'created_at', $date_filter, $start_date, $end_date);
$sale_res = mysqli_query($conn, $sale_q);
$total_sales = mysqli_fetch_assoc($sale_res)['total'] ?? 0;

// 2. Total Purchases
$pur_q = "SELECT SUM(total_sell) as total FROM purchase_info ";
applyDateFilter($pur_q, 'created_at', $date_filter, $start_date, $end_date);
$pur_res = mysqli_query($conn, $pur_q);
$total_pur = mysqli_fetch_assoc($pur_res)['total'] ?? 0;

$data = [
    ['id' => 1, 'label' => 'Total Sales Revenue', 'amount' => $total_sales, 'color' => 'text-emerald-600'],
    ['id' => 2, 'label' => 'Total Purchase Cost', 'amount' => $total_pur, 'color' => 'text-rose-600'],
    ['id' => 3, 'label' => 'Gross Profit', 'amount' => $total_sales - $total_pur, 'color' => 'text-blue-600 font-bold']
];

foreach ($data as &$row) {
    $row['amount_formatted'] = number_format($row['amount'], 2);
}

$config = [
    'title' => 'Profit and Loss Report',
    'table_id' => 'profit_loss_table',
    'primary_key' => 'id',
    'name_field' => 'label',
    'data' => $data,
    'date_column' => 'created_at',
    'summary_cards' => [
        ['label' => 'Net Profit', 'value' => number_format($total_sales - $total_pur, 2), 'border_color' => 'border-blue-500'],
    ],
    'columns' => [
        ['label' => 'Description', 'key' => 'label'],
        ['label' => 'Amount', 'key' => 'amount_formatted'],
    ],
    'action_buttons' => []
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

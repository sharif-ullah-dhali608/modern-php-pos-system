<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Income vs Expense - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// 1. Total Income (Actual Cash/Payment Received in period)
$inc_q = "SELECT SUM(amount) as total FROM sell_logs ";
applyDateFilter($inc_q, 'created_at', $date_filter, $start_date, $end_date);
$inc_res = mysqli_query($conn, $inc_q);
$total_income = mysqli_fetch_assoc($inc_res)['total'] ?? 0;

// 2. Total Expense (Actual Payments made in period)
$exp_q = "SELECT SUM(amount) as total FROM purchase_logs ";
applyDateFilter($exp_q, 'created_at', $date_filter, $start_date, $end_date);
$exp_res = mysqli_query($conn, $exp_q);
$total_expense = mysqli_fetch_assoc($exp_res)['total'] ?? 0;

$data = [
    ['id' => 1, 'type' => 'Income (Collections)', 'amount' => $total_income],
    ['id' => 2, 'type' => 'Expense (Payments)', 'amount' => $total_expense],
    ['id' => 3, 'type' => 'Net Cash Flow', 'amount' => $total_income - $total_expense],
];

foreach ($data as &$row) {
    $row['amount_formatted'] = number_format($row['amount'], 2);
}

$config = [
    'title' => 'Income vs Expense Report',
    'table_id' => 'inc_exp_table',
    'primary_key' => 'id',
    'name_field' => 'type',
    'data' => $data,
    'date_column' => 'created_at',
    'summary_cards' => [
        ['label' => 'Net Cash Flow', 'value' => number_format($total_income - $total_expense, 2), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Description', 'key' => 'type'],
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

<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Balance Sheet - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Fetch Current Assets
$cash_q = mysqli_query($conn, "SELECT (SUM(CASE WHEN pm.code = 'cash' THEN sl.amount ELSE 0 END) - SUM(IFNULL((SELECT SUM(amount) FROM purchase_logs WHERE pmethod_id = pm.id), 0))) as balance 
                                FROM sell_logs sl JOIN payment_methods pm ON sl.pmethod_id = pm.id");
$cash_balance = mysqli_fetch_assoc($cash_q)['balance'] ?? 0;

$data = [
    ['id' => 1, 'category' => 'Assets', 'item' => 'Cash in Hand', 'amount' => $cash_balance],
    ['id' => 2, 'category' => 'Assets', 'item' => 'Store Inventory Value', 'amount' => 0], // Placeholder
    ['id' => 3, 'category' => 'Liabilities', 'item' => 'Supplier Dues', 'amount' => 0], // Placeholder
];

foreach ($data as &$row) {
    $row['amount_formatted'] = number_format($row['amount'], 2);
}

$config = [
    'title' => 'Balance Sheet Report',
    'table_id' => 'balance_sheet_table',
    'primary_key' => 'id',
    'name_field' => 'item',
    'data' => $data,
    'summary_cards' => [
        ['label' => 'Total Cash Assets', 'value' => number_format($cash_balance, 2), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Category', 'key' => 'category'],
        ['label' => 'Item Name', 'key' => 'item'],
        ['label' => 'Balance', 'key' => 'amount_formatted'],
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
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

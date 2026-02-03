<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Bank Transactions - Velocity POS";
include('../includes/header.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data - Union of non-cash sell and purchase logs
$query = "(SELECT sl.created_at, sl.ref_invoice_id as invoice_id, 'Credit (Sale)' as type, pm.name as method, sl.transaction_id, sl.amount, u.name as user_name
           FROM sell_logs sl
           JOIN payment_methods pm ON sl.pmethod_id = pm.id
           JOIN users u ON sl.created_by = u.id
           WHERE pm.code != 'cash' AND sl.transaction_id IS NOT NULL AND sl.transaction_id != '')
          UNION ALL
          (SELECT pl.created_at, pl.ref_invoice_id as invoice_id, 'Debit (Purchase)' as type, pm.name as method, '' as transaction_id, pl.amount, u.name as user_name
           FROM purchase_logs pl
           JOIN payment_methods pm ON pl.pmethod_id = pm.id
           JOIN users u ON pl.created_by = u.id
           WHERE pm.code != 'cash')
          ORDER BY created_at DESC";

// Note: applyDateFilter might need complex handling for UNION, so I'll wrap it
$wrapped_query = "SELECT * FROM ($query) as combined WHERE 1=1 ";
if ($date_filter) {
    // Basic date filter handling for Union
    switch ($date_filter) {
        case 'today': $wrapped_query .= " AND DATE(created_at) = CURDATE() "; break;
        case 'yesterday': $wrapped_query .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) "; break;
    }
} elseif ($start_date && $end_date) {
    $wrapped_query .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date' ";
}

$result = mysqli_query($conn, $wrapped_query);
$data = [];
$total_in = 0;
$total_out = 0;

while ($row = mysqli_fetch_assoc($result)) {
    if(strpos($row['type'], 'Credit') !== false) $total_in += $row['amount'];
    else $total_out += $row['amount'];
    
    $row['amount_formatted'] = number_format($row['amount'], 2);
    $data[] = $row;
}

$config = [
    'title' => 'Bank Transactions',
    'table_id' => 'bank_transaction_table',
    'primary_key' => 'invoice_id',
    'name_field' => 'transaction_id',
    'data' => $data,
    'date_column' => 'created_at',
    'summary_cards' => [
        ['label' => 'Total Bank In', 'value' => number_format($total_in, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Bank Out', 'value' => number_format($total_out, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Net Bank Position', 'value' => number_format($total_in - $total_out, 2), 'border_color' => 'border-blue-500'],
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Type', 'key' => 'type'],
        ['label' => 'Invoice Id', 'key' => 'invoice_id'],
        ['label' => 'Method', 'key' => 'method'],
        ['label' => 'Transaction Id', 'key' => 'transaction_id'],
        ['label' => 'Amount', 'key' => 'amount_formatted'],
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
                include_once('../includes/reusable_list.php'); 
                renderReusableList($config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

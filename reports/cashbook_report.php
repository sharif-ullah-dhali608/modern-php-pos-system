<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Cashbook - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data - Union of cash sell and purchase logs
$query = "(SELECT sl.created_at, sl.ref_invoice_id as invoice_id, 'Cash In (Sale/Collection)' as type, sl.amount as credit, 0 as debit, u.name as user_name
           FROM sell_logs sl
           JOIN payment_methods pm ON sl.pmethod_id = pm.id
           JOIN users u ON sl.created_by = u.id
           WHERE pm.code = 'cash')
          UNION ALL
          (SELECT pl.created_at, pl.ref_invoice_id as invoice_id, 'Cash Out (Purchase/Payment)' as type, 0 as credit, pl.amount as debit, u.name as user_name
           FROM purchase_logs pl
           JOIN payment_methods pm ON pl.pmethod_id = pm.id
           JOIN users u ON pl.created_by = u.id
           WHERE pm.code = 'cash')
          ORDER BY created_at DESC";

$wrapped_query = "SELECT * FROM ($query) as combined WHERE 1=1 ";
if ($date_filter) {
    switch ($date_filter) {
        case 'today': $wrapped_query .= " AND DATE(created_at) = CURDATE() "; break;
        case 'yesterday': $wrapped_query .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) "; break;
    }
} elseif ($start_date && $end_date) {
    $wrapped_query .= " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date' ";
}

$result = mysqli_query($conn, $wrapped_query);
$data = [];
$total_credit = 0;
$total_debit = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_credit += $row['credit'];
    $total_debit += $row['debit'];
    
    $row['credit_formatted'] = number_format($row['credit'], 2);
    $row['debit_formatted'] = number_format($row['debit'], 2);
    $data[] = $row;
}

$config = [
    'title' => 'Cashbook Report',
    'table_id' => 'cashbook_table',
    'primary_key' => 'invoice_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'date_column' => 'created_at',
    'summary_cards' => [
        ['label' => 'Total Cash In', 'value' => number_format($total_credit, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Cash Out', 'value' => number_format($total_debit, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Net Cash in Hand', 'value' => number_format($total_credit - $total_debit, 2), 'border_color' => 'border-blue-500'],
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Type', 'key' => 'type'],
        ['label' => 'Invoice Id', 'key' => 'invoice_id'],
        ['label' => 'Credit (In)', 'key' => 'credit_formatted'],
        ['label' => 'Debit (Out)', 'key' => 'debit_formatted'],
        ['label' => 'Created By', 'key' => 'user_name'],
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

<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Purchase Payment Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT pl.*, u.name as user_name, pm.name as payment_method_name, s.name as supplier_name
          FROM purchase_logs pl
          JOIN users u ON pl.created_by = u.id
          LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id
          LEFT JOIN purchase_info pin ON pl.ref_invoice_id = pin.invoice_id
          LEFT JOIN suppliers s ON pin.sup_id = s.id
          WHERE 1=1 ";

applyDateFilter($query, 'pl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY pl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_paid = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['supplier_display'] = $row['supplier_name'] ?: 'N/A';
    $row['amount_formatted'] = number_format($row['amount'], 2);
    $total_paid += $row['amount'];
    $data[] = $row;
}

$config = [
    'title' => 'Purchase Payment Report',
    'table_id' => 'purchase_payment_table',
    'primary_key' => 'id',
    'name_field' => 'ref_invoice_id',
    'data' => $data,
    'date_column' => 'pl.created_at',
    'summary_cards' => [
        ['label' => 'Total Payments Made', 'value' => number_format($total_paid, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Transactions', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Supplier', 'key' => 'supplier_display'],
        ['label' => 'Payment Method', 'key' => 'payment_method_name'],
        ['label' => 'Created By', 'key' => 'user_name'],
        ['label' => 'Amount', 'key' => 'amount_formatted'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/purchases/view',
    'primary_key' => 'ref_invoice_id'
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

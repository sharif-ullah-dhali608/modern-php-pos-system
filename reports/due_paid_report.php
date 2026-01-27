<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Supplier Due Paid - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
// Note: In some systems, purchase logs have a specific type for payments.
// Assuming 'purchase' logs represent payments made to suppliers.
$query = "SELECT pl.*, u.name as user_name, pm.name as payment_method_name
          FROM purchase_logs pl
          JOIN users u ON pl.created_by = u.id
          LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id
          WHERE pl.type = 'purchase' ";

applyDateFilter($query, 'pl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY pl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_paid_to_suppliers = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['paid_formatted'] = number_format($row['amount'], 2);
    $total_paid_to_suppliers += $row['amount'];
    $data[] = $row;
}

$config = [
    'title' => 'Supplier Due Paid',
    'table_id' => 'supplier_due_paid_table',
    'primary_key' => 'id',
    'name_field' => 'ref_invoice_id',
    'data' => $data,
    'date_column' => 'pl.created_at',
    'summary_cards' => [
        ['label' => 'Total Paid to Suppliers', 'value' => number_format($total_paid_to_suppliers, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Total Transactions', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Created At', 'key' => 'created_at'],
        ['label' => 'Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Payment Method', 'key' => 'payment_method_name'],
        ['label' => 'Created By', 'key' => 'user_name'],
        ['label' => 'Paid Amount', 'key' => 'paid_formatted'],
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

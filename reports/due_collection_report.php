<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Due Collection List - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT sl.*, u.name as user_name, pm.name as payment_method_name
          FROM sell_logs sl
          JOIN users u ON sl.created_by = u.id
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
          WHERE sl.type = 'due_paid' ";

applyDateFilter($query, 'sl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY sl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_due_collected = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['paid_formatted'] = number_format($row['amount'], 2);
    $total_due_collected += $row['amount'];
    $data[] = $row;
}

$config = [
    'title' => 'Due Collection List',
    'table_id' => 'due_collection_table',
    'primary_key' => 'id',
    'name_field' => 'ref_invoice_id',
    'data' => $data,
    'date_column' => 'sl.created_at',
    'summary_cards' => [
        ['label' => 'Total Due Collected', 'value' => number_format($total_due_collected, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Entries', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'Created At', 'key' => 'created_at'],
        ['label' => 'Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Payment Method', 'key' => 'payment_method_name'],
        ['label' => 'Created By', 'key' => 'user_name'],
        ['label' => 'Paid Amount', 'key' => 'paid_formatted'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/invoice/view',
    'primary_key' => 'ref_invoice_id' // Overriding to use invoice id for viewing
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
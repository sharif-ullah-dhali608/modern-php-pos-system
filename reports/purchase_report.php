<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Purchase Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$filter_supplier = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : 0;
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT pin.*, COALESCE(s.name, 'N/A') as supplier_name,
          IFNULL((SELECT SUM(amount) FROM purchase_logs WHERE ref_invoice_id = pin.invoice_id AND type = 'purchase'), 0) as paid_amount
          FROM purchase_info pin 
          LEFT JOIN suppliers s ON pin.sup_id = s.id 
          WHERE 1=1 ";

if($filter_supplier > 0) {
    $query .= " AND pin.sup_id = $filter_supplier ";
}

applyDateFilter($query, 'pin.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY pin.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_purchase = 0;
$total_paid = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['current_due'] = max(0, $row['total_sell'] - $row['paid_amount']);
    $row['total_formatted'] = number_format($row['total_sell'], 2);
    $row['paid_formatted'] = number_format($row['paid_amount'], 2);
    $row['due_formatted'] = number_format($row['current_due'], 2);
    
    if($row['current_due'] <= 0.01) {
        $row['payment_status'] = '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">PAID</span>';
    } else {
        $row['payment_status'] = '<span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">DUE</span>';
    }
    
    $total_purchase += $row['total_sell'];
    $total_paid += $row['paid_amount'];
    $data[] = $row;
}

// Filter Options
$sup_res = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status = 1 ORDER BY name ASC");
$supplier_options = [['label' => 'All Suppliers', 'url' => '?', 'active' => $filter_supplier == 0]];
while($s = mysqli_fetch_assoc($sup_res)) {
    $supplier_options[] = ['label' => $s['name'], 'url' => '?supplier_id='.$s['id'], 'active' => $filter_supplier == $s['id']];
}

$config = [
    'title' => 'Purchase Report',
    'table_id' => 'purchase_report_table',
    'primary_key' => 'id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'date_column' => 'pin.created_at',
    'filters' => [
        ['id' => 'filter_supplier', 'label' => 'Supplier', 'searchable' => true, 'options' => $supplier_options]
    ],
    'summary_cards' => [
        ['label' => 'Total Purchase', 'value' => number_format($total_purchase, 2), 'border_color' => 'border-teal-500'],
        ['label' => 'Total Paid', 'value' => number_format($total_paid, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Due', 'value' => number_format($total_purchase - $total_paid, 2), 'border_color' => 'border-rose-500'],
    ],
    'columns' => [
        ['label' => 'Invoice ID', 'key' => 'invoice_id'],
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Supplier', 'key' => 'supplier_name'],
        ['label' => 'Total Amount', 'key' => 'total_formatted'],
        ['label' => 'Paid Amount', 'key' => 'paid_formatted'],
        ['label' => 'Due', 'key' => 'due_formatted'],
        ['label' => 'Status', 'key' => 'payment_status', 'type' => 'html'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/purchases/view'
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

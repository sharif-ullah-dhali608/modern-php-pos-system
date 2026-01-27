<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data
$query = "SELECT si.*, COALESCE(c.name, 'Walking Customer') as customer_name,
          (SELECT SUM(qty_sold) FROM selling_item WHERE invoice_id = si.invoice_id) as total_qty,
          (SELECT SUM(price_sold) FROM selling_item WHERE invoice_id = si.invoice_id) as total_selling_price,
          (si.grand_total - IFNULL((SELECT SUM(grand_total) FROM selling_info WHERE ref_invoice_id = si.invoice_id AND inv_type = 'return'), 0)) as net_amount,
          IFNULL((SELECT SUM(amount) FROM sell_logs WHERE ref_invoice_id = si.invoice_id AND type IN ('payment', 'full_payment', 'partial_payment', 'due_paid')), 0) as paid_amount
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          WHERE si.inv_type = 'sale' ";

if($filter_customer > 0) {
    $query .= " AND si.customer_id = $filter_customer ";
}

applyDateFilter($query, 'si.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY si.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
$total_net = 0;
$total_paid = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $row['current_due'] = max(0, $row['net_amount'] - $row['paid_amount']);
    $row['net_formatted'] = number_format($row['net_amount'], 2);
    $row['paid_formatted'] = number_format($row['paid_amount'], 2);
    $row['due_formatted'] = number_format($row['current_due'], 2);
    
    if($row['current_due'] <= 0.01) {
        $row['payment_status'] = '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">PAID</span>';
    } else {
        $row['payment_status'] = '<span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">DUE</span>';
    }
    
    $total_net += $row['net_amount'];
    $total_paid += $row['paid_amount'];
    $data[] = $row;
}

// Filter Options
$cust_res = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
$customer_options = [['label' => 'All Customers', 'url' => '?', 'active' => $filter_customer == 0]];
while($c = mysqli_fetch_assoc($cust_res)) {
    $customer_options[] = ['label' => $c['name'], 'url' => '?customer_id='.$c['id'], 'active' => $filter_customer == $c['id']];
}

$config = [
    'title' => 'Sell Report',
    'table_id' => 'sell_report_table',
    'primary_key' => 'info_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'date_column' => 'si.created_at',
    'filters' => [
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_options]
    ],
    'summary_cards' => [
        ['label' => 'Net Amount', 'value' => number_format($total_net, 2), 'border_color' => 'border-teal-500'],
        ['label' => 'Paid Amount', 'value' => number_format($total_paid, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Due Amount', 'value' => number_format($total_net - $total_paid, 2), 'border_color' => 'border-rose-500'],
    ],
    'columns' => [
        ['label' => 'Invoice ID', 'key' => 'invoice_id'],
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Customer', 'key' => 'customer_name'],
        ['label' => 'Quantity', 'key' => 'total_qty'],
        ['label' => 'Selling Price', 'key' => 'total_selling_price', 'type' => 'number'],
        ['label' => 'Net Amount', 'key' => 'net_formatted'],
        ['label' => 'Paid Amount', 'key' => 'paid_formatted'],
        ['label' => 'Due', 'key' => 'due_formatted'],
        ['label' => 'Status', 'key' => 'payment_status', 'type' => 'html'],
    ],
    'action_buttons' => ['view'],
    'view_url' => '/pos/invoice/view'
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

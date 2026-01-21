<?php
session_start();
// Setup base path for includes
$base_path = '../'; 
include($base_path . 'config/dbcon.php');
include($base_path . 'includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter parameters
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Customers for filter dropdown
$customers_list = [];
$cust_result = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
while($c = mysqli_fetch_assoc($cust_result)) $customers_list[] = $c;

// Fetch Installment Orders
$query = "SELECT io.*, si.grand_total as sale_total, c.name as customer_name, c.mobile as customer_mobile
          FROM installment_orders io
          LEFT JOIN selling_info si ON io.invoice_id = si.invoice_id
          LEFT JOIN customers c ON si.customer_id = c.id
          WHERE 1=1 ";

// Apply Customer Filter
if($filter_customer > 0) {
    $query .= " AND si.customer_id = $filter_customer ";
}

// Apply date filter
applyDateFilter($query, 'io.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY io.id DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['formatted_total'] = number_format($row['sale_total'], 2);
        $row['formatted_initial'] = number_format($row['initial_amount'], 2);
        $row['interest_display'] = $row['interest_percentage'] . '% (' . number_format($row['interest_amount'], 2) . ')';
        
        
        // Sum total paid from scheduled payments
        $invoice_id = $row['invoice_id'];
        $paid_query = "SELECT SUM(paid) as total_paid, SUM(due) as total_due FROM installment_payments WHERE invoice_id = '$invoice_id'";
        $paid_res = mysqli_query($conn, $paid_query);
        $paid_data = mysqli_fetch_assoc($paid_res);
        $total_paid_installments = $paid_data['total_paid'] ?? 0;
        $total_due_installments = $paid_data['total_due'] ?? 0;
        
        // Skip if all installments are fully paid (due <= 0.01 to handle rounding errors)
        if($total_due_installments <= 0.01) {
            continue;
        }
        
        $total_receivable = $row['sale_total'] + $row['interest_amount'];
        $remaining = $total_receivable - $row['initial_amount'] - $total_paid_installments;
        if($remaining <= 0.01) $remaining = 0;
        $row['remaining_due'] = number_format($remaining, 2);
        
        $items[] = $row;
    }
}

// Build customer filter options
$customer_filter_options = [['label' => 'All Customers', 'url' => '?customer_id=0', 'active' => $filter_customer == 0]];
foreach($customers_list as $cust) {
    $customer_filter_options[] = [
        'label' => $cust['name'],
        'url' => '?customer_id='.$cust['id'],
        'active' => $filter_customer == $cust['id']
    ];
}

$list_config = [
    'title' => 'Installment Orders',
    'add_url' => '#',
    'view_url' => '/pos/installment/view',
    'delete_url' => '/pos/installment/delete_installment.php',
    'action_buttons' => ['view', 'delete'],
    'table_id' => 'installmentTable',
    'date_column' => 'io.created_at',
    'filters' => [
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_filter_options]
    ],
    'columns' => [
        ['key' => 'invoice_id', 'label' => 'Invoice ID', 'sortable' => true],
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'duration', 'label' => 'Duration', 'sortable' => true],
        ['key' => 'installment_count', 'label' => 'Inst.', 'sortable' => true],
        ['key' => 'formatted_total', 'label' => 'Sale Amt', 'sortable' => true],
        ['key' => 'formatted_initial', 'label' => 'Down Payment', 'sortable' => true],
        ['key' => 'interest_display', 'label' => 'Interest', 'sortable' => true],
        ['key' => 'remaining_due', 'label' => 'Due', 'badge_class' => 'bg-red-500/20 text-red-400', 'type' => 'badge'],
        ['key' => 'payment_status', 'label' => 'Status', 'type' => 'badge', 'badge_class' => 'bg-blue-500/20 text-blue-400'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'primary_key' => 'id',
    'name_field' => 'invoice_id'
];

$page_title = "Installment Orders - Velocity POS";
include($base_path . 'includes/header.php');
?>

<div class="app-wrapper">
    <?php include($base_path . 'includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include($base_path . 'includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php 
                include($base_path . 'includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            <?php include($base_path . 'includes/footer.php'); ?>
        </div>
    </main>
</div>

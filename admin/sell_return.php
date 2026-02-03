<?php
session_start();
include('../config/dbcon.php');

include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
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

$page_title = "Sell Return List";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Fetch Data
// Assuming inv_type 'return' indicates a return
$query = "SELECT si.*, c.name as customer_name, u.name as return_by 
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          LEFT JOIN users u ON si.created_by = u.id 
          WHERE si.inv_type = 'return' ";

// Apply Customer Filter
if($filter_customer > 0) {
    $query .= " AND si.customer_id = $filter_customer ";
}

// Apply date filter
applyDateFilter($query, 'si.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY si.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Build customer filter options with parameter preservation
$current_params = $_GET;
unset($current_params['customer_id']);
$base_query = http_build_query($current_params);
$base_url_prefix = $base_query ? "?$base_query&" : "?";

$customer_filter_options = [['label' => 'All Customers', 'url' => $base_url_prefix . 'customer_id=0', 'active' => $filter_customer == 0]];
foreach($customers_list as $cust) {
    $customer_filter_options[] = [
        'label' => $cust['name'],
        'url' => $base_url_prefix . 'customer_id='.$cust['id'],
        'active' => $filter_customer == $cust['id']
    ];
}

// Prepare Config
$config = [
    'title' => 'Sell Return List',
    'table_id' => 'sell_return_table',
    'add_url' => '#', // No add button for return list
    'edit_url' => '#',
    'delete_url' => '/pos/admin/sell_return_delete.php',
    'view_url' => '/pos/admin/sell_return_view.php', 
    'primary_key' => 'info_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    'action_buttons' => ['view', 'delete'], // Only view and delete
    'date_column' => 'si.created_at',
    'filters' => [
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_filter_options]
    ],
    'columns' => [
        ['label' => 'Date Time', 'key' => 'created_at'],
        ['label' => 'Customer Name', 'key' => 'customer_name'],
        ['label' => 'Old Invoice Id', 'key' => 'ref_invoice_id'],
        ['label' => 'Return Note', 'key' => 'invoice_note'],
        ['label' => 'Amount', 'key' => 'grand_total', 'type' => 'text'],
        ['label' => 'Returned By', 'key' => 'return_by'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions']
    ]
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">

                <?php renderReusableList($config); ?>
            </div>
        </div>


        <?php include('../includes/footer.php'); ?>
    </main>
</div>

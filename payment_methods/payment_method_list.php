<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin"); // Clean URL for signin
    exit(0);
}

// Filter Inputs
$status = $_GET['status'] ?? '';
$code = $_GET['code'] ?? '';
$usage = $_GET['usage'] ?? '';

// Build Query
$where_clause = "WHERE 1=1";
if($status !== '') {
    $status = mysqli_real_escape_string($conn, $status);
    $where_clause .= " AND status = '$status'";
}
if($code !== '') {
    $code = mysqli_real_escape_string($conn, $code);
    $where_clause .= " AND code = '$code'";
}
if($usage === 'used') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM sell_logs WHERE pmethod_id = payment_methods.id)";
} elseif($usage === 'unused') {
    $where_clause .= " AND NOT EXISTS (SELECT 1 FROM sell_logs WHERE pmethod_id = payment_methods.id)";
}

$query = "SELECT * FROM payment_methods $where_clause ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

// Fetch Distinct Codes
$code_query = mysqli_query($conn, "SELECT DISTINCT code FROM payment_methods ORDER BY code ASC");
$code_options = [['label' => 'All Codes', 'url' => '?code=', 'active' => ($code === '')]];
while($c = mysqli_fetch_assoc($code_query)) {
    $code_options[] = [
        'label' => ucfirst($c['code']),
        'url' => "?code={$c['code']}",
        'active' => ($code == $c['code'])
    ];
}

// Filters Config
$filters = [];
// 1. Status
$filters[] = [
    'id' => 'filter_status',
    'label' => 'Status',
    'options' => [
        ['label' => 'All Status', 'url' => '?status=', 'active' => ($status === '')],
        ['label' => 'Active', 'url' => '?status=1', 'active' => ($status === '1')],
        ['label' => 'Inactive', 'url' => '?status=0', 'active' => ($status === '0')],
    ]
];
// 2. Code
$filters[] = [
    'id' => 'filter_code',
    'label' => 'Code',
    'options' => $code_options
];
// 3. Usage
$filters[] = [
    'id' => 'filter_usage',
    'label' => 'Usage',
    'options' => [
        ['label' => 'All Usage', 'url' => '?usage=', 'active' => ($usage === '')],
        ['label' => 'Used in Sales', 'url' => '?usage=used', 'active' => ($usage === 'used')],
        ['label' => 'Not Used', 'url' => '?usage=unused', 'active' => ($usage === 'unused')],
    ]
];

$list_config = [
    'title' => 'Payment Method List',
    'add_url' => '/pos/payment-methods/add', // Clean URL
    'table_id' => 'paymentTable',
    'filters' => $filters,
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code Name', 'sortable' => true],
        ['key' => 'details', 'label' => 'Details', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/payment-methods/edit', // Clean URL base for edit
    'delete_url' => '/pos/payment_methods/save_payment_method.php', // Backend action paths usually keep .php or use a specific rewrite
    'status_url' => '/pos/payment_methods/save_payment_method.php', 
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Payment Method List - POS";
include('../includes/header.php');
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
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
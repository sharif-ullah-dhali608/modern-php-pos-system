<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Filter Inputs
$status = $_GET['status'] ?? '';
$decimal = $_GET['decimal'] ?? '';
$usage = $_GET['usage'] ?? '';

// Build Query
$where_clause = "WHERE 1=1";
if($status !== '') {
    $status = mysqli_real_escape_string($conn, $status);
    $where_clause .= " AND c.status = '$status'";
}
if($decimal !== '') {
    $decimal = mysqli_real_escape_string($conn, $decimal);
    $where_clause .= " AND c.decimal_place = '$decimal'";
}
if($usage === 'used') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM store_currency WHERE currency_id = c.id)";
} elseif($usage === 'unused') {
    $where_clause .= " AND NOT EXISTS (SELECT 1 FROM store_currency WHERE currency_id = c.id)";
}

// Fetch currencies with store count
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM store_currency WHERE currency_id = c.id) as store_count
          FROM currencies c 
          $where_clause
          ORDER BY c.sort_order ASC, c.id DESC";
$query_run = mysqli_query($conn, $query);

$currencies = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $currencies[] = $row;
}

// Fetch Unique Decimal Places for Filter
$dec_query = mysqli_query($conn, "SELECT DISTINCT decimal_place FROM currencies ORDER BY decimal_place");
$dec_options = [['label' => 'All Decimals', 'url' => '?decimal=', 'active' => ($decimal === '')]];
while($d = mysqli_fetch_assoc($dec_query)) {
    $dec_options[] = [
        'label' => $d['decimal_place'] . ' Places',
        'url' => "?decimal={$d['decimal_place']}",
        'active' => ($decimal == $d['decimal_place'])
    ];
}

// Filters Configuration
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
// 2. Decimal Place
$filters[] = [
    'id' => 'filter_decimal',
    'label' => 'Decimals',
    'options' => $dec_options
];
// 3. Usage (Stores)
$filters[] = [
    'id' => 'filter_usage',
    'label' => 'Usage',
    'options' => [
        ['label' => 'All Usage', 'url' => '?usage=', 'active' => ($usage === '')],
        ['label' => 'Used in Stores', 'url' => '?usage=used', 'active' => ($usage === 'used')],
        ['label' => 'Not Used', 'url' => '?usage=unused', 'active' => ($usage === 'unused')],
    ]
];

// Prepare data for reusable list component
$list_config = [
    'title' => 'Currency List',
    'add_url' => '/pos/currency/add',
    'table_id' => 'currencyTable',
    'filters' => $filters,
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'currency_name', 'label' => 'Currency Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code', 'sortable' => true],
        ['key' => 'symbol_left', 'label' => 'Symbol Left', 'sortable' => false],
        ['key' => 'symbol_right', 'label' => 'Symbol Right', 'sortable' => false],
        ['key' => 'decimal_place', 'label' => 'Decimal Place', 'sortable' => true],
        ['key' => 'store_count', 'label' => 'Stores', 'type' => 'badge', 'badge_class' => 'bg-blue-500/20 text-blue-400'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $currencies,
    'edit_url' => '/pos/currency/edit',
    'delete_url' => '/pos/currency/save_currency.php',
    'status_url' => '/pos/currency/save_currency.php',
    'primary_key' => 'id',
    'name_field' => 'currency_name'
];

$page_title = "Currency List - Velocity POS";
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


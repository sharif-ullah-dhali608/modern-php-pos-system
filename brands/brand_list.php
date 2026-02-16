<?php
session_start();
include('../config/dbcon.php');
include('../includes/store_filter_helper.php'); // Store filtering helper
include('../includes/permission_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter Inputs
$status = $_GET['status'] ?? '';
$usage = $_GET['usage'] ?? '';

// Get store filter (JOIN + WHERE)
$store_filter = getStoreFilterWithJoin('brands', 'b');
$store_join = $store_filter['join'];
$store_where = $store_filter['where'];

// Build Query
$where_clause = "WHERE 1=1";
$where_clause .= $store_where; // Add store filtering
if($status !== '') {
    $status = mysqli_real_escape_string($conn, $status);
    $where_clause .= " AND b.status = '$status'";
}
if($usage === 'used') {
    $where_clause .= " AND EXISTS (SELECT 1 FROM products WHERE brand_id = b.id)";
} elseif($usage === 'unused') {
    $where_clause .= " AND NOT EXISTS (SELECT 1 FROM products WHERE brand_id = b.id)";
}

$query = "SELECT b.* FROM brands b {$store_join} $where_clause ORDER BY b.sort_order ASC, b.id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

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
// 2. Usage
$filters[] = [
    'id' => 'filter_usage',
    'label' => 'Usage',
    'options' => [
        ['label' => 'All Usage', 'url' => '?usage=', 'active' => ($usage === '')],
        ['label' => 'Used in Products', 'url' => '?usage=used', 'active' => ($usage === 'used')],
        ['label' => 'Not Used', 'url' => '?usage=unused', 'active' => ($usage === 'unused')],
    ]
];

// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_brand_brand') ? '/pos/brands/add' : '#';
$edit_url = check_user_permission('update_brand_brand') ? '/pos/brands/edit' : '#';
$delete_url = check_user_permission('delete_brand_brand') ? '/pos/brands/save_brand.php' : '#';

$list_config = [
    'title' => 'Brand List',
    'add_url' => $add_url,
    'table_id' => 'brandTable',
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
    'edit_url' => $edit_url,
    'delete_url' => $delete_url,
    'status_url' => '/pos/brands/save_brand.php',
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Brand List - POS";
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


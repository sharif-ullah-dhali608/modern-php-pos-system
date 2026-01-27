<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Stock Report - Velocity POS";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$filter_store = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$filter_category = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Fetch Data
$query = "SELECT p.id, p.product_name, p.product_code, c.name as category_name, 
          COALESCE(SUM(psm.stock), 0) as total_stock,
          p.alert_quantity, p.thumbnail, p.selling_price, p.purchase_price
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN product_store_map psm ON p.id = psm.product_id 
          WHERE p.status = 1 ";

if($filter_store > 0) {
    $query = "SELECT p.id, p.product_name, p.product_code, c.name as category_name, 
              COALESCE(psm.stock, 0) as total_stock,
              p.alert_quantity, p.thumbnail, p.selling_price, p.purchase_price
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN product_store_map psm ON p.id = psm.product_id AND psm.store_id = $filter_store
              WHERE p.status = 1 ";
}

if($filter_category > 0) {
    $query .= " AND p.category_id = $filter_category ";
}

$query .= " GROUP BY p.id ORDER BY p.product_name ASC";

$result = mysqli_query($conn, $query);
$data = [];
$total_stock_value = 0;
$low_stock_count = 0;
$sl = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $stock = floatval($row['total_stock']);
    $alert = floatval($row['alert_quantity']);
    
    if($stock <= $alert) {
        $row['stock_status'] = '<span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">LOW STOCK</span>';
        $low_stock_count++;
    } else {
        $row['stock_status'] = '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">IN STOCK</span>';
    }
    
    $row['sl'] = $sl++;
    $row['stock_formatted'] = number_format($stock, 0);
    $row['sell_price_formatted'] = number_format($row['selling_price'], 2);
    $row['purchase_price_formatted'] = number_format($row['purchase_price'], 2);
    $data[] = $row;
}

// Filter Options
$store_options = [['label' => 'All Stores', 'url' => '?category_id='.$filter_category, 'active' => $filter_store == 0]];
$s_res = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status = 1");
while($s = mysqli_fetch_assoc($s_res)) {
    $store_options[] = ['label' => $s['store_name'], 'url' => '?store_id='.$s['id'].'&category_id='.$filter_category, 'active' => $filter_store == $s['id']];
}

$cat_options = [['label' => 'All Categories', 'url' => '?store_id='.$filter_store, 'active' => $filter_category == 0]];
$c_res = mysqli_query($conn, "SELECT id, name FROM categories WHERE status = 1");
while($c = mysqli_fetch_assoc($c_res)) {
    $cat_options[] = ['label' => $c['name'], 'url' => '?store_id='.$filter_store.'&category_id='.$c['id'], 'active' => $filter_category == $c['id']];
}

$config = [
    'title' => 'Stock Report',
    'table_id' => 'stock_report_table',
    'primary_key' => 'id',
    'name_field' => 'product_name',
    'data' => $data,
    'filters' => [
        ['id' => 'filter_store', 'label' => 'Store', 'options' => $store_options],
        ['id' => 'filter_category', 'label' => 'Category', 'options' => $cat_options]
    ],
    'summary_cards' => [
        ['label' => 'Total Products', 'value' => count($data), 'border_color' => 'border-teal-500'],
        ['label' => 'Low Stock Items', 'value' => $low_stock_count, 'border_color' => 'border-rose-500'],
    ],
    'columns' => [
        ['label' => 'SL', 'key' => 'sl'],
        ['label' => 'Image', 'key' => 'thumbnail', 'type' => 'image', 'path' => ''],
        ['label' => 'Product Name', 'key' => 'product_name'],
        ['label' => 'Code', 'key' => 'product_code'],
        ['label' => 'Category', 'key' => 'category_name'],
        ['label' => 'Stock Qty', 'key' => 'stock_formatted'],
        ['label' => 'Sell Price', 'key' => 'sell_price_formatted'],
        ['label' => 'Purchase Price', 'key' => 'purchase_price_formatted'],
        ['label' => 'Status', 'key' => 'stock_status', 'type' => 'html'],
    ],
    'action_buttons' => []
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

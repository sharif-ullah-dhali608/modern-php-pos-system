<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter Inputs
$category_id = $_GET['category_id'] ?? '';
$brand_id    = $_GET['brand_id'] ?? '';
$supplier_id = $_GET['supplier_id'] ?? '';
$status      = $_GET['status'] ?? '';

// Build Query
$where_clause = "WHERE 1=1";
if(!empty($category_id)) { $category_id = mysqli_real_escape_string($conn, $category_id); $where_clause .= " AND p.category_id = '$category_id'"; }
if(!empty($brand_id))    { $brand_id = mysqli_real_escape_string($conn, $brand_id);       $where_clause .= " AND p.brand_id = '$brand_id'"; }
if(!empty($supplier_id)) { $supplier_id = mysqli_real_escape_string($conn, $supplier_id); $where_clause .= " AND p.supplier_id = '$supplier_id'"; }
if($status !== '')       { $status = mysqli_real_escape_string($conn, $status);           $where_clause .= " AND p.status = '$status'"; }

// Fetch Products with Relations
$query = "SELECT p.*, 
                 c.name as category_name, 
                 b.name as brand_name_display, 
                 u.unit_name,
                 bx.box_name,
                 cr.currency_name,
                 s.name as supplier_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          LEFT JOIN units u ON p.unit_id = u.id
          LEFT JOIN boxes bx ON p.box_id = bx.id
          LEFT JOIN currencies cr ON p.currency_id = cr.id
          LEFT JOIN suppliers s ON p.supplier_id = s.id
          $where_clause
          ORDER BY p.id DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Formats
        $row['formatted_price'] = number_format($row['selling_price'], 2);
        $row['formatted_purchase_price'] = number_format($row['purchase_price'], 2);
        $row['formatted_stock'] = $row['opening_stock']; // Or number_format if decimal needed
        
        // Handle Null values for display
        $row['brand_name'] = $row['brand_name_display'] ?? 'N/A';
        $row['box_display'] = $row['box_name'] ?? 'N/A';
        $row['currency_display'] = $row['currency_name'] ?? 'Default';
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'N/A';
        
        $items[] = $row;
    }
}

// --- Fetch Filter Options ---
$filters = [];

// 1. Category Filter
$cat_opts = [['label' => 'All Categories', 'url' => '?category_id=', 'active' => empty($category_id)]];
$cat_q = mysqli_query($conn, "SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC");
while($c = mysqli_fetch_assoc($cat_q)) {
    $cat_opts[] = [
        'label' => $c['name'],
        'url' => "?category_id={$c['id']}",
        'active' => ($category_id == $c['id'])
    ];
}
$filters[] = [
    'id' => 'filter_cat',
    'label' => 'Category',
    'searchable' => true,
    'options' => $cat_opts
];

// 2. Brand Filter
$brand_opts = [['label' => 'All Brands', 'url' => '?brand_id=', 'active' => empty($brand_id)]];
$brand_q = mysqli_query($conn, "SELECT id, name FROM brands WHERE status=1 ORDER BY name ASC");
while($b = mysqli_fetch_assoc($brand_q)) {
    $brand_opts[] = [
        'label' => $b['name'],
        'url' => "?brand_id={$b['id']}",
        'active' => ($brand_id == $b['id'])
    ];
}
$filters[] = [
    'id' => 'filter_brand',
    'label' => 'Brand',
    'searchable' => true,
    'options' => $brand_opts
];

// 3. Supplier Filter
$supp_opts = [['label' => 'All Suppliers', 'url' => '?supplier_id=', 'active' => empty($supplier_id)]];
$supp_q = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status=1 ORDER BY name ASC");
while($s = mysqli_fetch_assoc($supp_q)) {
    $supp_opts[] = [
        'label' => $s['name'],
        'url' => "?supplier_id={$s['id']}",
        'active' => ($supplier_id == $s['id'])
    ];
}
$filters[] = [
    'id' => 'filter_supp',
    'label' => 'Supplier',
    'searchable' => true,
    'options' => $supp_opts
];

// 4. Status Filter
$filters[] = [
    'id' => 'filter_status',
    'label' => 'Status',
    'options' => [
        ['label' => 'All Status', 'url' => '?status=', 'active' => ($status === '')],
        ['label' => 'Active', 'url' => '?status=1', 'active' => ($status === '1')],
        ['label' => 'Inactive', 'url' => '?status=0', 'active' => ($status === '0')],
    ]
];

$list_config = [
    'title' => 'Product List',
    'add_url' => '/pos/products/add',
    'table_id' => 'productTable',
    'filters' => $filters,
    'columns' => [
        ['key' => 'thumbnail', 'label' => 'Image', 'type' => 'image', 'sortable' => false],
        ['key' => 'product_code', 'label' => 'Code', 'sortable' => true],
        ['key' => 'product_name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'category_name', 'label' => 'Category', 'sortable' => true],
        ['key' => 'supplier_display', 'label' => 'Supplier', 'sortable' => true],
        
        ['key' => 'box_display', 'label' => 'Box/Shelf', 'sortable' => true],
        ['key' => 'formatted_stock', 'label' => 'Stock', 'sortable' => true],
        
        ['key' => 'formatted_purchase_price', 'label' => 'Purchase', 'sortable' => true],
        ['key' => 'formatted_price', 'label' => 'Selling', 'sortable' => true],
        
        ['key' => 'alert_quantity', 'label' => 'Alert Qty', 'type' => 'badge', 'badge_class' => 'bg-purple-500/20 text-purple-400'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/products/edit',
    'delete_url' => '/pos/products/save', 
    'status_url' => '/pos/products/save', 
    'primary_key' => 'id',
    'name_field' => 'product_name'
];

$page_title = "Product List - Velocity POS";
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
                // Alerts are handled by footer.php (Toastr/SweetAlert) based on SESSION data
                
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
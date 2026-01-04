<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

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

$list_config = [
    'title' => 'Product List',
    'add_url' => '/pos/products/add',
    'table_id' => 'productTable',
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
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
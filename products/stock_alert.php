<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Filters
$filter_store = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$filter_product = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Base path for filters to preserve other params
function get_filter_url($params) {
    $current_params = $_GET;
    foreach($params as $k => $v) {
        if($v === 0 || $v === '') unset($current_params[$k]);
        else $current_params[$k] = $v;
    }
    return '?' . http_build_query($current_params);
}

// Fetch Stores for filter
$stores_res = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status = 1 ORDER BY store_name ASC");
$store_options = [['label' => 'All Stores', 'url' => get_filter_url(['store_id' => 0]), 'active' => ($filter_store === 0)]];
while($st = mysqli_fetch_assoc($stores_res)) {
    $store_options[] = [
        'label' => $st['store_name'],
        'url' => get_filter_url(['store_id' => $st['id']]),
        'active' => ($filter_store === $st['id'])
    ];
}

// Fetch Products (that have alerts) for filter
$prod_filter_query = "SELECT DISTINCT p.id, p.product_name 
                      FROM products p
                      JOIN product_store_map psm ON p.id = psm.product_id
                      WHERE p.status = 1 AND psm.stock <= p.alert_quantity";
if($filter_store > 0) $prod_filter_query .= " AND psm.store_id = $filter_store";
$prod_filter_query .= " ORDER BY p.product_name ASC";
$prod_res = mysqli_query($conn, $prod_filter_query);

$product_options = [['label' => 'All Products', 'url' => get_filter_url(['product_id' => 0]), 'active' => ($filter_product === 0)]];
while($pr = mysqli_fetch_assoc($prod_res)) {
    $product_options[] = [
        'label' => $pr['product_name'],
        'url' => get_filter_url(['product_id' => $pr['id']]),
        'active' => ($filter_product === $pr['id'])
    ];
}
// Find active labels for buttons
$selected_store_name = 'Filter by Store';
foreach($store_options as $opt) {
    if($opt['active'] && $filter_store > 0) {
        $selected_store_name = $opt['label'];
        break;
    }
}

$selected_product_name = 'Filter by Product';
foreach($product_options as $opt) {
    if($opt['active'] && $filter_product > 0) {
        $selected_product_name = $opt['label'];
        break;
    }
}

// Fetch Data
$query = "SELECT p.*, psm.stock, psm.store_id, st.store_name, s.name as supplier_name, s.mobile as supplier_mobile
          FROM products p
          JOIN product_store_map psm ON p.id = psm.product_id
          JOIN stores st ON psm.store_id = st.id
          LEFT JOIN suppliers s ON p.supplier_id = s.id
          WHERE p.status = 1 AND psm.stock <= p.alert_quantity";

if($filter_store > 0) {
    $query .= " AND psm.store_id = $filter_store";
}
if($filter_product > 0) {
    $query .= " AND p.id = $filter_product";
}

// Apply date filter
applyDateFilter($query, 'p.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY psm.stock ASC";
$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['formatted_purchase_price'] = '৳' . number_format($row['purchase_price'], 2);
        $row['supplier_display'] = $row['supplier_name'] ?? 'No Supplier';
        $row['mobile_display'] = $row['supplier_mobile'] ?? 'N/A';
        
        // Stock Badge
        $stock = floatval($row['stock']);
        if($stock <= 0) {
            $row['stock_display'] = '<span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap">Out of Stock ('.$stock.')</span>';
        } else {
            $row['stock_display'] = '<span class="bg-orange-500 text-white px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap">Low ('.$stock.')</span>';
        }

        // Action Column - custom HTML
        $row['custom_actions'] = '
            <div class="flex items-center gap-2 whitespace-nowrap">
                <a href="/pos/purchases/add?product_id='.$row['id'].'&store_id='.$row['store_id'].'" class="w-8 h-8 flex items-center justify-center bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition" title="Add Purchase">
                    <i class="fas fa-shopping-cart"></i>
                </a>
                <button onclick="openQuickAddModal('.$row['id'].', \''.addslashes($row['product_name']).'\', '.$row['store_id'].', \''.addslashes($row['store_name']).'\')" class="w-8 h-8 flex items-center justify-center bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition" title="Quick Stock Update">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        ';
        
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Stock Alert List',
    'add_url' => '#', // No add new button for this list
    'table_id' => 'stockAlertTable',
    'columns' => [
        ['key' => 'id', 'label' => 'Id'],
        ['key' => 'product_name', 'label' => 'Name'],
        ['key' => 'store_name', 'label' => 'Store'],
        ['key' => 'supplier_display', 'label' => 'Supplier'],
        ['key' => 'mobile_display', 'label' => 'Mobile'],
        ['key' => 'formatted_purchase_price', 'label' => 'Purchase Price'],
        ['key' => 'stock_display', 'label' => 'Stock', 'type' => 'html'],
        ['key' => 'custom_actions', 'label' => 'Action', 'type' => 'html']
    ],
    'data' => $items,
    'extra_buttons' => [
        [
            'label' => 'Point of Sale',
            'icon' => 'fas fa-cash-register',
            'onclick' => "window.location.href='/pos/pos/'",
            'class' => 'inline-flex items-center gap-2 px-5 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg shadow transition-all'
        ]
    ],
    'date_column' => 'p.created_at',
    'filters' => [
        [
            'id' => 'filter_store',
            'label' => $selected_store_name,
            'options' => $store_options,
            'searchable' => true
        ],
        [
            'id' => 'filter_product',
            'label' => $selected_product_name,
            'options' => $product_options,
            'searchable' => true
        ]
    ],
    'primary_key' => 'id',
    'name_field' => 'product_name'
];

$page_title = "Stock Alert - Velocity POS";
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
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<!-- Quick Add QTY Modal -->
<div id="quickAddModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm" onclick="closeQuickAddModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all border border-slate-200">
            <div class="flex items-center justify-between bg-orange-500 px-6 py-4">
                <div class="flex items-center gap-3">
                    <i class="fas fa-boxes text-white"></i>
                    <h3 class="text-lg font-bold text-white">Quick Stock Update</h3>
                </div>
                <button onclick="closeQuickAddModal()" class="text-white hover:opacity-75"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Product</label>
                    <p id="modal-product-name" class="text-sm font-bold text-slate-800"></p>
                </div>
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Store</label>
                    <p id="modal-store-name" class="text-sm font-bold text-slate-800"></p>
                </div>
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Add Quantity (+)</label>
                    <input type="number" id="quick-add-qty" value="1" min="1" class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all font-bold text-lg">
                </div>
                
                <input type="hidden" id="modal-product-id">
                <input type="hidden" id="modal-store-id">
                
                <div class="flex gap-3">
                    <button onclick="closeQuickAddModal()" class="flex-1 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all">Cancel</button>
                    <button onclick="submitQuickAdd()" class="flex-1 py-3 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 shadow-lg shadow-orange-200 transition-all">Update Stock</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openQuickAddModal(pid, pname, sid, sname) {
    document.getElementById('modal-product-name').innerText = pname;
    document.getElementById('modal-store-name').innerText = sname;
    document.getElementById('modal-product-id').value = pid;
    document.getElementById('modal-store-id').value = sid;
    document.getElementById('quick-add-qty').value = 1;
    document.getElementById('quickAddModal').classList.remove('hidden');
}

function closeQuickAddModal() {
    document.getElementById('quickAddModal').classList.add('hidden');
}

function submitQuickAdd() {
    const pid = document.getElementById('modal-product-id').value;
    const sid = document.getElementById('modal-store-id').value;
    const qty = document.getElementById('quick-add-qty').value;
    
    if(qty <= 0) {
        Swal.fire('Invalid Qty', 'Please enter a quantity greater than zero.', 'error');
        return;
    }

    const btn = event.target;
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    $.ajax({
        type: "POST",
        url: "/pos/products/update_stock_ajax.php",
        data: {
            action: 'quick_add_qty',
            product_id: pid,
            store_id: sid,
            qty: qty
        },
        success: function(response) {
            const res = JSON.parse(response);
            if(res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: res.message,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        },
        error: function() {
            Swal.fire('Error', 'Something went wrong', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}
</script>

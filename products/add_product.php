<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// ==========================================
// URL ID PARSING
// ==========================================
if(!isset($_GET['id'])){
    $uri_segments = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $last_segment = end($uri_segments);
    if(is_numeric($last_segment)){
        $_GET['id'] = $last_segment;
    }
}

// Default Mode Settings
$mode = "create";
$btn_name = "save_product_btn";
$btn_text = "Save Product";
$page_title = "Add Product";

// Initialize default values
$d = [
    'id' => '', 
    'product_name' => '', 
    'product_code' => '', 
    'category_id' => '', 
    'brand_id' => '', 
    'barcode_symbology' => 'code128', 
    'unit_id' => '', 
    'purchase_price' => '', 
    'selling_price' => '', 
    'tax_rate_id' => '', 
    'tax_method' => 'exclusive', 
    'opening_stock' => '0', 
    'alert_quantity' => '5', 
    'box_id' => '', // Added box ID
    'currency_id' => '', // Added currency ID
    'expire_date' => '', 
    'description' => '', 
    'status' => '1', 
    'thumbnail' => ''
];

$selected_stores = []; 
$all_stores = [];

// Fetch Dropdown Data 
$categories = mysqli_query($conn, "SELECT id, name FROM categories WHERE status='1'");
$brands     = mysqli_query($conn, "SELECT id, name FROM brands WHERE status='1'");
$units      = mysqli_query($conn, "SELECT id, unit_name as name, code as short_name FROM units WHERE status='1'");
$taxes      = mysqli_query($conn, "SELECT id, name, taxrate as rate FROM taxrates WHERE status='1'");
$boxes      = mysqli_query($conn, "SELECT id, box_name as name FROM boxes WHERE status='1'"); // Added
$currencies = mysqli_query($conn, "SELECT id, currency_name as name, code FROM currencies WHERE status='1'"); // Added

// Fetch Stores
$stores_query = "SELECT * FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
if($stores_result){
    while($store = mysqli_fetch_assoc($stores_result)) {
        $all_stores[] = $store;
    }
}

// Edit Mode Logic
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_product_btn";
    $btn_text = "Update Product";
    $page_title = "Edit Product";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $query = "SELECT * FROM products WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        
        // Fetch Mapped Stores
        $map_query = "SELECT store_id FROM product_store_map WHERE product_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        
        if(mysqli_num_rows($map_result) > 0){
            while($map_row = mysqli_fetch_assoc($map_result)) { 
                $selected_stores[] = $map_row['store_id']; 
            }
        }
    } else {
        $_SESSION['message'] = "Product not found";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/list");
        exit(0);
    }
}

include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-7xl mx-auto">
                
                <?php if(isset($_SESSION['message'])): ?>
                    <div class="p-4 mb-4 text-sm rounded-lg flex items-center gap-2 <?php echo ($_SESSION['msg_type'] ?? 'success') == 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>" role="alert">
                        <i class="fas <?php echo ($_SESSION['msg_type'] ?? 'success') == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <span class="font-medium"><?= $_SESSION['message']; ?></span>
                    </div>
                    <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
                <?php endif; ?>

                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/products/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Fill in the details to <?= $mode == 'create' ? 'add a new' : 'update'; ?> inventory item.</p>
                        </div>
                    </div>
                </div>

                <form action="/pos/products/save" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="product_id" value="<?= $d['id']; ?>">
                        <input type="hidden" name="old_thumbnail" value="<?= htmlspecialchars($d['thumbnail']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="lg:col-span-2 space-y-6 slide-in delay-100">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-box-open text-teal-600"></i> Basic Information
                                </h3>
                                
                                <div class="grid grid-cols-1 gap-5">
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Product Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="product_name" id="product_name" value="<?= htmlspecialchars($d['product_name']); ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all outline-none" placeholder="e.g. Wireless Mouse">
                                        <span class="error-msg text-xs text-red-500 mt-1 hidden">Product Name is required.</span>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Product Code (Pcode) <span class="text-red-500">*</span></label>
                                            <div class="flex">
                                                <div class="relative w-full">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-barcode text-slate-400"></i>
                                                    </div>
                                                    <input type="text" name="product_code" id="product_code" value="<?= htmlspecialchars($d['product_code']); ?>" class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-l-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-mono uppercase" placeholder="Scan or Enter Code">
                                                </div>
                                                <button type="button" onclick="generateCode()" class="bg-teal-800 hover:bg-teal-600 text-white px-4 py-3 rounded-r-lg font-medium transition-colors" title="Generate Random Code">
                                                    <i class="fas fa-random"></i>
                                                </button>
                                            </div>
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Product Code is required.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Barcode Symbology <span class="text-red-500">*</span></label>
                                            <select name="barcode_symbology" id="barcode_symbology" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none cursor-pointer">
                                                <option value="code128" <?= $d['barcode_symbology'] == 'code128' ? 'selected' : ''; ?>>Code 128 (Standard)</option>
                                                <option value="code39" <?= $d['barcode_symbology'] == 'code39' ? 'selected' : ''; ?>>Code 39</option>
                                                <option value="ean13" <?= $d['barcode_symbology'] == 'ean13' ? 'selected' : ''; ?>>EAN-13</option>
                                                <option value="upc" <?= $d['barcode_symbology'] == 'upc' ? 'selected' : ''; ?>>UPC</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Category <span class="text-red-500">*</span></label>
                                            <select name="category_id" id="category_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                                <option value="">Select Category</option>
                                                <?php mysqli_data_seek($categories, 0); while($cat = mysqli_fetch_assoc($categories)): ?>
                                                    <option value="<?= $cat['id']; ?>" <?= $d['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?= $cat['name']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Category is required.</span>
                                        </div>
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Brand</label>
                                            <select name="brand_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                                <option value="">Select Brand</option>
                                                <?php mysqli_data_seek($brands, 0); while($brand = mysqli_fetch_assoc($brands)): ?>
                                                    <option value="<?= $brand['id']; ?>" <?= $d['brand_id'] == $brand['id'] ? 'selected' : ''; ?>><?= $brand['name']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Unit <span class="text-red-500">*</span></label>
                                            <select name="unit_id" id="unit_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                                <option value="">Select Unit</option>
                                                <?php mysqli_data_seek($units, 0); while($unit = mysqli_fetch_assoc($units)): ?>
                                                    <option value="<?= $unit['id']; ?>" <?= $d['unit_id'] == $unit['id'] ? 'selected' : ''; ?>><?= $unit['name']; ?> (<?= $unit['short_name']; ?>)</option>
                                                <?php endwhile; ?>
                                            </select>
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Unit is required.</span>
                                        </div>
                                    </div>
                                    
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-dashed border-slate-200 pt-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Box / Shelf Placement</label>
                                            <select name="box_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 outline-none">
                                                <option value="">Select Box</option>
                                                <?php mysqli_data_seek($boxes, 0); while($box = mysqli_fetch_assoc($boxes)): ?>
                                                    <option value="<?= $box['id']; ?>" <?= ($d['box_id'] ?? '') == $box['id'] ? 'selected' : ''; ?>><?= $box['name']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Currency</label>
                                            <select name="currency_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 outline-none">
                                                <?php mysqli_data_seek($currencies, 0); while($curr = mysqli_fetch_assoc($currencies)): ?>
                                                    <option value="<?= $curr['id']; ?>" <?= ($d['currency_id'] ?? '') == $curr['id'] ? 'selected' : ''; ?>><?= $curr['name']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-1 h-full bg-emerald-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                <i class="fas fa-coins text-emerald-600"></i> Pricing & Costing
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-5 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Purchase Price *</label>
                                    <input type="number" step="0.01" name="purchase_price" id="purchase_price" value="<?= $d['purchase_price']; ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none font-bold" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Profit (%)</label>
                                    <input type="number" step="0.01" name="profit_margin" id="profit_margin" value="<?= $d['profit_margin'] ?? '0'; ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none font-bold" placeholder="0">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Selling Price *</label>
                                    <input type="number" step="0.01" name="selling_price" id="selling_price" value="<?= $d['selling_price']; ?>" class="w-full bg-emerald-50 border border-emerald-400 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none font-bold text-emerald-900" placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Wholesale Price</label>
                                    <input type="number" step="0.01" name="wholesale_price" id="wholesale_price" value="<?= $d['wholesale_price'] ?? '0'; ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none font-bold" placeholder="0.00">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-dashed border-slate-200 pt-5">
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tax Rate</label>
                                    <select name="tax_rate_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none">
                                        <option value="">No Tax</option>
                                        <?php mysqli_data_seek($taxes, 0); while($tax = mysqli_fetch_assoc($taxes)): ?>
                                            <option value="<?= $tax['id']; ?>" <?= $d['tax_rate_id'] == $tax['id'] ? 'selected' : ''; ?>><?= $tax['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Tax Method</label>
                                    <select name="tax_method" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 outline-none">
                                        <option value="inclusive" <?= $d['tax_method'] == 'inclusive' ? 'selected' : ''; ?>>Inclusive (Price includes Tax)</option>
                                        <option value="exclusive" <?= $d['tax_method'] == 'exclusive' ? 'selected' : ''; ?>>Exclusive (Tax added to Price)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-warehouse text-teal-600"></i> Inventory Setup
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Opening Stock</label>
                                        <input type="number" name="opening_stock" id="opening_stock" value="<?= $d['opening_stock']; ?>" class="w-full bg-blue-50/30 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alert Quantity <span class="text-red-500">*</span></label>
                                        <input type="number" name="alert_quantity" id="alert_quantity" value="<?= $d['alert_quantity']; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                        <span class="error-msg text-xs text-red-500 mt-1 hidden">Alert Quantity is required.</span>
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Expire Date</label>
                                        <input type="date" name="expire_date" value="<?= $d['expire_date']; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-dashed border-slate-200 pt-5">
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alert Qty</label>
                                            <input type="number" name="alert_quantity" value="<?= $d['alert_quantity']; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 outline-none">
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Sort Order</label>
                                        <input type="number" name="sort_order" id="sort_order" value="<?= $d['sort_order'] ?? '0'; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-6 slide-in delay-200">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <label class="block text-sm font-semibold text-slate-700 mb-4">Product Thumbnail</label>
                                <div class="flex flex-col items-center justify-center h-48">

                                    <label for="thumbnail-upload" class="group relative w-full aspect-square max-w-[250px] rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden cursor-pointer hover:border-teal-500 hover:bg-teal-50 transition-all" id="thumbnail-preview-container">
                                        
                                        <?php 
                                        $img_src = "";
                                        $show_image = false;
                                        $db_thumb_path = $d['thumbnail'] ?? '';

                                        if(!empty($db_thumb_path)) {
                                            $clean_path = str_replace('/pos', '', $db_thumb_path);
                                            $server_path = ".." . $clean_path;

                                            if(file_exists($server_path)) {
                                                $img_src = $db_thumb_path;
                                                $show_image = true;
                                            }
                                        }
                                        ?>

                                        <?php if($show_image): ?>
                                            <img src="<?= $img_src; ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="text-center p-4">
                                                <i class="fas fa-cloud-upload-alt text-4xl text-slate-300 group-hover:text-teal-500 transition-colors mb-2"></i>
                                                <p class="text-sm text-slate-500 group-hover:text-teal-600">Click to Upload</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white font-medium text-sm">Change Image</span>
                                        </div>
                                    </label>

                                    <input type="file" name="thumbnail" id="thumbnail-upload" class="hidden" onchange="readURL(this);" accept="image/*">
                                    <p class="text-[11px] text-teal-800 mt-2 text-center">Size: 600x600px | Max: 2MB</p>
                                </div>
                            </div>

                            <?php 
                                    $current_status = $d['status']; 
                                    $status_title = "Product"; 
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";
                                    include('../includes/status_card.php'); 
                            ?>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
                                <textarea name="description" rows="4" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none text-sm resize-none" placeholder="Enter product details..."><?= htmlspecialchars($d['description']); ?></textarea>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Available Stores</label>
                                <input type="text" id="storeSearch" onkeyup="filterStores()" placeholder="Search stores..." class="w-full mb-3 px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:border-teal-500">
                                
                                <div id="storeList" class="space-y-2 max-h-48 overflow-y-auto custom-scroll pr-1">
                                    <?php foreach($all_stores as $store): ?>
                                        <label class="flex items-center p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors border border-transparent hover:border-slate-100">
                                            <input type="checkbox" name="store_ids[]" value="<?= $store['id']; ?>" 
                                                class="store-checkbox w-4 h-4 text-teal-600 bg-gray-100 border-gray-300 rounded focus:ring-teal-500 focus:ring-2"
                                                <?= in_array($store['id'], $selected_stores) ? 'checked' : ''; ?> 
                                            >
                                            <span class="ml-2.5 text-sm text-slate-600 font-medium select-none"><?= $store['store_name']; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p id="store-error" class="text-xs text-red-500 mt-2 hidden"><i class="fas fa-exclamation-circle"></i> Please select at least one store.</p>
                            </div>

                            <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-4 sticky top-24 z-10">
                                <button type="submit" name="<?= $btn_name; ?>" class="w-full bg-gradient-to-r from-teal-800 to-emerald-800 hover:from-teal-900 hover:to-emerald-900 text-white font-bold py-3.5 rounded-xl shadow-md transition-all transform hover:scale-[1.02] flex justify-center items-center gap-2 mb-3"> 
                                    <i class="fas fa-save"></i> <?= $btn_text; ?> 
                                </button>
                                <a href="/pos/products/list" class="block w-full bg-slate-100 text-slate-600 font-semibold py-3 rounded-xl text-center hover:bg-slate-200 transition-all">Cancel</a>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const productForm = document.getElementById('productForm');

    // Validation Logic
    productForm.addEventListener('submit', function(e) {
        let isValid = true;
        let firstErrorField = null;

        // List of Must Required Fields
        const requiredFields = [
            'product_name',
            'product_code',
            'barcode_symbology',
            'category_id',
            'unit_id',
            'purchase_price',
            'selling_price',
            'alert_quantity',
        ];

        requiredFields.forEach(function(fieldName) {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if(field) {
                const parent = field.closest('.form-group') || field.parentElement;
                const errorMsg = parent.querySelector('.error-msg');

                if(field.value.trim() === "") {
                    isValid = false;
                    field.classList.remove('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50', 'bg-emerald-50/50', 'bg-blue-50/30');
                    field.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                    if(errorMsg) errorMsg.classList.remove('hidden');
                    if(!firstErrorField) firstErrorField = field;
                } else {
                    field.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                    field.classList.add('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50');
                    if(errorMsg) errorMsg.classList.add('hidden');
                }
            }
        });

        // Store Checkbox Validation
        const storeCheckboxes = document.querySelectorAll('.store-checkbox');
        const storeError = document.getElementById('store-error'); 
        
        if(storeCheckboxes.length > 0) {
            let isOneStoreChecked = false;
            storeCheckboxes.forEach(cb => { if(cb.checked) isOneStoreChecked = true; });
            
            if(!isOneStoreChecked) {
                isValid = false;
                if(storeError) {
                    storeError.classList.remove('hidden');
                }
            } else {
                if(storeError) storeError.classList.add('hidden');
            }
        }

        if(!isValid) {
            e.preventDefault();
            if(firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
        }
    });

    // Real-time validation removal
    const allInputs = productForm.querySelectorAll('input, select, textarea');
    allInputs.forEach(input => {
        ['input', 'change'].forEach(evt => {
            input.addEventListener(evt, function() {
                if(this.value.trim() !== "") {
                    this.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                    this.classList.add('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50');
                    
                    const parent = this.closest('.form-group') || this.parentElement;
                    const errorMsg = parent.querySelector('.error-msg');
                    if(errorMsg) errorMsg.classList.add('hidden');
                }
            });
        });
    });
});

// Image Preview Function
function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            const container = document.getElementById('thumbnail-preview-container');
            container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">
                                   <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-white font-medium text-sm">Change Image</span>
                                   </div>`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Random Code Generator
function generateCode() {
    const min = 10000000;
    const max = 99999999;
    const randomCode = Math.floor(Math.random() * (max - min + 1)) + min;
    const input = document.getElementById('product_code');
    if(input) {
        input.value = randomCode;
        input.dispatchEvent(new Event('input'));
    }
}

// Store Filter Function
function filterStores() {
    const input = document.getElementById('storeSearch');
    const filter = input.value.toLowerCase();
    const list = document.getElementById('storeList');
    const labels = list.getElementsByTagName('label');

    for (let i = 0; i < labels.length; i++) {
        const span = labels[i].querySelector('span');
        const txtValue = span.textContent || span.innerText;
        labels[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
    }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pInput = document.getElementById('purchase_price');
    const mInput = document.getElementById('profit_margin');
    const sInput = document.getElementById('selling_price');

    // Automatic Profit/Selling Price Calculation
    function calculatePrices() {
        let purchase = parseFloat(pInput.value) || 0;
        let margin = parseFloat(mInput.value) || 0;
        let selling = purchase + (purchase * (margin / 100));
        sInput.value = selling.toFixed(2);
    }

    pInput.addEventListener('input', calculatePrices);
    mInput.addEventListener('input', calculatePrices);
});
</script>
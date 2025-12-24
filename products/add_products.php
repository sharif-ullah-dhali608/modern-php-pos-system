<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$mode = "create";
$btn_name = "save_product_btn";
$btn_text = "Save Product";
$page_title = "Add Product";

$d = [
    'id' => '', 'name' => '', 'code' => '', 'slug' => '', 'product_type' => 'standard',
    'barcode_symbology' => 'code128', 'category_id' => '', 'unit_id' => '', 
    'brand_id' => '', 'supplier_id' => '', 'box_id' => '', 'purchase_price' => '0',
    'price' => '0', 'tax_rate_id' => '', 'tax_method' => 'exclusive',
    'alert_quantity' => '10', 'details' => '', 'status' => '1', 
    'visibility_pos' => '1', 'thumbnail' => ''
];

$selected_stores = []; 

// Fetch Data for Edit Mode
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
        // Fetch mapped stores from pivot table
        $map_query = "SELECT store_id FROM product_store WHERE product_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        while($map_row = mysqli_fetch_assoc($map_result)) { $selected_stores[] = $map_row['store_id']; }
    }
}

include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <div class="mb-2 slide-in">
                    <div class="flex items-center gap-4 mb-2">
                        <a href="/pos/products/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Add new inventory item' : 'Update existing product'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/products/save_product.php" method="POST" enctype="multipart/form-data" id="productForm">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="product_id" value="<?= $d['id']; ?>">
                            <input type="hidden" name="old_thumbnail" value="<?= htmlspecialchars($d['thumbnail']); ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 glass-card rounded-xl p-6 shadow-lg border border-slate-200 bg-white">
                            <div class="lg:col-span-2 space-y-6">
                                
                                <div class="flex items-center gap-4">
                                    <label for="thumbnail-upload" class="w-20 h-20 rounded-xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0 cursor-pointer hover:border-teal-500 transition-all" id="thumbnail-preview-container">
                                        <?php if(!empty($d['thumbnail'])): ?>
                                            <img src="<?= htmlspecialchars($d['thumbnail']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-camera text-slate-400 text-2xl"></i>
                                        <?php endif; ?>
                                    </label>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-slate-700 mb-1">Product Image</label>
                                        <input type="file" name="thumbnail" id="thumbnail-upload" class="hidden" onchange="readURL(this);" accept="image/*">
                                        <button type="button" onclick="document.getElementById('thumbnail-upload').click()" class="text-xs font-bold text-teal-600 hover:text-teal-700 uppercase">Upload New Image</button>
                                        <p class="text-[10px] text-slate-400 mt-1">Recommended size: 500x500px (Max 2MB)</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Product Name <span class="text-red-600">*</span></label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($d['name']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Product Code (SKU) <span class="text-red-600">*</span></label>
                                        <input type="text" name="code" value="<?= htmlspecialchars($d['code']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none uppercase transition-all" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category <span class="text-red-600">*</span></label>
                                        <select name="category_id" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600" required>
                                            <option value="">Select Category</option>
                                            </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Brand</label>
                                        <select name="brand_id" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none">
                                            <option value="">Select Brand</option>
                                            </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Unit <span class="text-red-600">*</span></label>
                                        <select name="unit_id" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none" required>
                                            <option value="">Select Unit</option>
                                            </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Purchase Price <span class="text-red-600">*</span></label>
                                        <input type="number" step="0.01" name="purchase_price" value="<?= htmlspecialchars($d['purchase_price']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Selling Price <span class="text-red-600">*</span></label>
                                        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($d['price']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alert Quantity</label>
                                        <input type="number" name="alert_quantity" value="<?= htmlspecialchars($d['alert_quantity']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600">
                                    </div>
                                </div>

                                <?php 
                                    $store_label = "Product Available In"; 
                                    $search_placeholder = "Search branches for product...";
                                    include('../includes/store_select_component.php'); 
                                ?>
                            </div>

                            <div class="space-y-6">
                                
                                <div id="status-card" class="rounded-2xl p-7 border transition-all duration-500 shadow-md <?= $d['status'] == '1' ? 'bg-[#064e3b] border-[#064e3b]' : 'bg-[#f1f5f9] border-[#e2e8f0]'; ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-1">
                                            <h3 class="text-sm font-extrabold <?= $d['status'] == '1' ? 'text-white' : 'text-slate-800'; ?>">Product Status</h3>
                                            <p class="text-[10px] font-medium uppercase tracking-widest <?= $d['status'] == '1' ? 'text-teal-100' : 'text-slate-500'; ?>" id="status-label">
                                                <?= $d['status'] == '1' ? 'Active Operations' : 'Operations Disabled'; ?>
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="status" id="status_input" value="<?= $d['status']; ?>">
                                            <input type="checkbox" id="status_toggle" class="sr-only peer" <?= $d['status'] == '1' ? 'checked' : ''; ?>>
                                            <div class="w-12 h-7 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/30 border border-white/10 shadow-inner"></div>
                                        </label>
                                    </div>
                                </div>

                                <div id="pos-card" class="rounded-2xl p-7 border transition-all duration-500 shadow-md <?= $d['visibility_pos'] == '1' ? 'bg-[#064e3b] border-[#064e3b]' : 'bg-[#f1f5f9] border-[#e2e8f0]'; ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-1">
                                            <h3 class="text-sm font-extrabold <?= $d['visibility_pos'] == '1' ? 'text-white' : 'text-slate-800'; ?>">POS Visibility</h3>
                                            <p class="text-[10px] font-medium uppercase tracking-widest <?= $d['visibility_pos'] == '1' ? 'text-teal-100' : 'text-slate-500'; ?>" id="pos-label">
                                                <?= $d['visibility_pos'] == '1' ? 'Visible on POS' : 'Hidden from POS'; ?>
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="visibility_pos" id="pos_input" value="<?= $d['visibility_pos']; ?>">
                                            <input type="checkbox" id="pos_toggle" class="sr-only peer" <?= $d['visibility_pos'] == '1' ? 'checked' : ''; ?>>
                                            <div class="w-12 h-7 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/30 border border-white/10 shadow-inner"></div>
                                        </label>
                                    </div>
                                </div>

                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm">
                                    <label class="block text-sm font-semibold text-slate-700 mb-3">Sort Order</label>
                                    <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order'] ?? 0); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600 transition-all">
                                </div>

                                <div class="space-y-3 mt-8">
                                    <button type="submit" name="<?= $btn_name; ?>" id="submitBtn" class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold py-4 rounded-xl shadow-lg transition-all transform hover:scale-[1.01]"> <?= $btn_text; ?> </button>
                                    <a href="/pos/products/list" class="block w-full bg-slate-100 text-slate-700 font-bold py-4 rounded-xl text-center hover:bg-slate-200 transition-all">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic Toggles
    initStatusToggle('status-card', 'status_toggle', 'status_input', 'status-label', "Active Operations", "Operations Disabled");
    initStatusToggle('pos-card', 'pos_toggle', 'pos_input', 'pos-label', "Visible on POS", "Hidden from POS");

    // Form & Store Validation
    const productForm = document.getElementById('productForm');
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    const storeError = document.getElementById('store-error');

    productForm.addEventListener('submit', function(e) {
        const isOneChecked = Array.from(storeCheckboxes).some(cb => cb.checked);
        if(!isOneChecked) {
            e.preventDefault();
            storeError.classList.remove('hidden');
            storeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

function initStatusToggle(cardId, toggleId, inputId, labelId, activeTxt, inactiveTxt) {
    const toggle = document.getElementById(toggleId);
    const input = document.getElementById(inputId);
    const label = document.getElementById(labelId);
    const card = document.getElementById(cardId);
    const header = card.querySelector('h3');

    if(toggle) {
        toggle.addEventListener('change', function() {
            if(this.checked) {
                input.value = "1";
                label.innerText = activeTxt;
                label.className = "text-[10px] font-medium uppercase tracking-widest text-teal-100";
                header.className = "text-sm font-extrabold text-white";
                card.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#064e3b] border-[#064e3b]";
            } else {
                input.value = "0";
                label.innerText = inactiveTxt;
                label.className = "text-[10px] font-medium uppercase tracking-widest text-slate-500";
                header.className = "text-sm font-extrabold text-slate-800";
                card.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#f1f5f9] border-[#e2e8f0]";
            }
        });
    }
}

function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('thumbnail-preview-container').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
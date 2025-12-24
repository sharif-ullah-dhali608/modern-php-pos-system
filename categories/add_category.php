<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Default Data Structure
$mode = "create";
$btn_name = "save_category_btn";
$btn_text = "Save Category";
$page_title = "Add Category";

$d = [
    'id' => '',
    'name' => '',
    'category_code' => '',
    'slug' => '',
    'parent_id' => '0',
    'thumbnail' => '',
    'details' => '',
    'status' => '1',
    'sort_order' => '0',
    'visibility_pos' => '1'
];

$selected_stores = []; 

// 2. FETCH DATA FOR EDIT MODE
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_category_btn";
    $btn_text = "Update Category";
    $page_title = "Edit Category";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM categories WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        
        // Fetch mapped stores for the reusable component
        $map_query = "SELECT store_id FROM category_store_map WHERE category_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        while($map_row = mysqli_fetch_assoc($map_result)) {
            $selected_stores[] = $map_row['store_id'];
        }
    } else {
        $_SESSION['message'] = "Category not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/list");
        exit(0);
    }
}

// Fetch active stores for the search component
$stores_query = "SELECT id, store_name FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) { $all_stores[] = $store; }

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
                        <a href="/pos/categories/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Create new category' : 'Update category'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/categories/save_category.php" method="POST" enctype="multipart/form-data" id="categoryForm">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="category_id" value="<?= $d['id']; ?>">
                            <input type="hidden" name="old_thumbnail" value="<?= htmlspecialchars($d['thumbnail']); ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 glass-card rounded-xl p-6 shadow-lg border border-slate-200 bg-white">
                            <div class="lg:col-span-2 space-y-6">
                                
                                <div class="flex items-center gap-4">
                                    <label for="thumbnail-upload" class="w-16 h-16 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden shrink-0 cursor-pointer" id="thumbnail-preview-container">
                                        <?php if(!empty($d['thumbnail'])): ?>
                                            <img src="<?= htmlspecialchars($d['thumbnail']); ?>" alt="Thumb" class="w-full h-full object-cover" id="thumbnail-preview">
                                        <?php else: ?>
                                            <i class="fas fa-image text-slate-400 text-2xl" id="default-icon"></i>
                                        <?php endif; ?>
                                    </label>
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Thumbnail (Image Upload)</label>
                                        <input type="file" name="thumbnail" id="thumbnail-upload" class="w-full text-slate-700 border border-slate-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-800 file:text-white hover:file:bg-teal-700 transition-all" onchange="readURL(this);" accept="image/*">
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category Name <span class="text-red-600">*</span></label>
                                        <input type="text" name="name" id="category_name" value="<?= htmlspecialchars($d['name']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category Code <span class="text-red-600">*</span></label>
                                        <input type="text" name="category_code" value="<?= htmlspecialchars($d['category_code']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none uppercase transition-all" required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Category Slug <span class="text-red-600">*</span></label>
                                        <input type="text" name="slug" id="category_slug" value="<?= htmlspecialchars($d['slug']); ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 text-slate-500 outline-none transition-all" required readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Parent Category</label>
                                        <select name="parent_id" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all">
                                            <option value="0">None (Root Category)</option>
                                            <?php
                                            $cat_q = mysqli_query($conn, "SELECT id, name FROM categories WHERE status='1' AND id!='".$d['id']."'");
                                            while($cat = mysqli_fetch_assoc($cat_q)) {
                                                echo "<option value='".$cat['id']."' ".($d['parent_id'] == $cat['id'] ? 'selected' : '').">".$cat['name']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Category Details</label>
                                    <textarea name="details" rows="3" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all"><?= htmlspecialchars($d['details']); ?></textarea>
                                </div>

                                <?php 
                                    $store_label = "Category Available In"; 
                                    $search_placeholder = "Search branches for category...";
                                    include('../includes/store_select_component.php'); 
                                ?>
                            </div>

                            <div class="space-y-6">
                                <?php 
                                    $current_status = $d['status']; 
                                    $status_title = "Category"; // Dynamic title set korlam
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";

                                    include('../includes/status_card.php'); 
                                ?>

                                <div id="pos-card" class="rounded-2xl p-7 border transition-all duration-500 shadow-md <?= $d['visibility_pos'] == '1' ? 'bg-[#064e3b] border-[#064e3b]' : 'bg-[#f1f5f9] border-[#e2e8f0]'; ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-1">
                                            <h3 class="text-sm font-extrabold <?= $d['visibility_pos'] == '1' ? 'text-white' : 'text-slate-800'; ?>">Visibility on POS</h3>
                                            <p id="pos-desc" class="text-[10px] font-medium uppercase tracking-widest <?= $d['visibility_pos'] == '1' ? 'text-teal-100' : 'text-slate-500'; ?>">
                                                <?= $d['visibility_pos'] == '1' ? 'Visible on Sales Screen' : 'Hidden from Sales Screen'; ?>
                                            </p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="visibility_pos" id="pos_input" value="<?= $d['visibility_pos']; ?>">
                                            <input type="checkbox" id="pos_toggle" class="sr-only peer" <?= $d['visibility_pos'] == '1' ? 'checked' : ''; ?>>
                                            <div class="w-12 h-7 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/30 border border-white/10 shadow-inner"></div>
                                        </label>
                                    </div>
                                </div>

                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">
                                    <label class="block text-sm font-semibold text-slate-700 mb-3">Sort Order <span class="text-red-600">*</span></label>
                                    <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600 transition-all" required>
                                </div>

                                <div class="space-y-3 mt-8">
                                    <button type="submit" name="<?= $btn_name; ?>" id="submitBtn" class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold py-3 rounded-lg shadow-lg transition-all transform hover:scale-[1.01]"><?= $btn_text; ?></button>
                                    <a href="/pos/categories/list" class="block w-full bg-slate-100 text-slate-700 font-semibold py-3 rounded-lg text-center hover:bg-slate-200 transition-all">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
<script>
document.addEventListener('DOMContentLoaded', function() {
    initDynamicToggle('pos-card', 'pos_toggle', 'pos_input', 'pos-desc', "Visible on Sales Screen", "Hidden from Sales Screen");

    /**
     * 2. AUTO SLUG GENERATOR
     * Automatically generates a URL-friendly slug from the category name
     */
    const catNameInput = document.getElementById('category_name');
    const slugInput = document.getElementById('category_slug');
    if(catNameInput && slugInput) {
        catNameInput.addEventListener('input', function() {
            let text = this.value.toLowerCase().replace(/[^\w ]+/g, '').replace(/ +/g, '-');
            slugInput.value = text;
        });
    }

    /**
     * 3. MULTI-STORE SELECTION VALIDATION
     * Ensures at least one store is selected before form submission
     */
    const categoryForm = document.getElementById('categoryForm');
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    const storeError = document.getElementById('store-error');

    if(categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            const isOneChecked = Array.from(storeCheckboxes).some(cb => cb.checked);
            if(!isOneChecked) {
                e.preventDefault();
                if(storeError) {
                    storeError.classList.remove('hidden');
                    storeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});

/**
 * REUSABLE TOGGLE LOGIC
 * Used specifically for POS Visibility card updates
 */
function initDynamicToggle(cardId, toggleId, inputId, labelId, activeText, inactiveText) {
    const toggle = document.getElementById(toggleId);
    const input = document.getElementById(inputId);
    const label = document.getElementById(labelId);
    const card = document.getElementById(cardId);
    const header = card.querySelector('h3');

    if(toggle && card) {
        toggle.addEventListener('change', function() {
            if(this.checked) {
                input.value = "1";
                label.innerText = activeText;
                label.className = "text-[10px] font-medium uppercase tracking-widest text-teal-100";
                header.className = "text-sm font-extrabold text-white";
                card.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#064e3b] border-[#064e3b]";
            } else {
                input.value = "0";
                label.innerText = inactiveText;
                label.className = "text-[10px] font-medium uppercase tracking-widest text-slate-500";
                header.className = "text-sm font-extrabold text-slate-800";
                card.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#f1f5f9] border-[#e2e8f0]";
            }
        });
    }
}

/**
 * IMAGE PREVIEW HANDLER
 * Provides instant visual feedback when a thumbnail is selected
 */
function readURL(input) {
    const previewContainer = document.getElementById('thumbnail-preview-container');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            previewContainer.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover" id="thumbnail-preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK: Ensure user is authenticated
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Default Data Structure for Create Mode
$mode = "create";
$btn_name = "save_taxrate_btn";
$btn_text = "Save Taxrate";
$page_title = "Add Taxrate";

$d = [
    'id' => '',
    'name' => '',
    'code' => '',
    'taxrate' => '0',
    'status' => '1',
    'sort_order' => '0'
];

$selected_stores = []; // Array to store IDs of mapped stores

// 2. FETCH DATA FOR EDIT MODE
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_taxrate_btn";
    $btn_text = "Update Taxrate";
    $page_title = "Edit Taxrate";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM taxrates WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        
        // Fetch mapped stores from pivot table to ensure they are selected in edit mode
        // Database Table Reference: taxrate_store_map
        $map_query = "SELECT store_id FROM taxrate_store_map WHERE taxrate_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        
        $selected_stores = []; 
        while($map_row = mysqli_fetch_assoc($map_result)) {
            $selected_stores[] = (int)$map_row['store_id']; // Ensure ID is stored as integer
        }
    } else {
        $_SESSION['message'] = "Taxrate not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/list");
        exit(0);
    }
}

// 3. FETCH ACTIVE STORES: For display in the search component
$stores_query = "SELECT id, store_name FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) { 
    $all_stores[] = $store; 
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
            <div class="p-12">
                <div class="mb-2 slide-in">
                    <div class="flex items-center gap-4 mb-2">
                        <a href="/pos/taxrates/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Create new taxrate' : 'Update taxrate information'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/taxrates/save_taxrate.php" method="POST" id="taxrateForm">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="taxrate_id" value="<?= $d['id']; ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 glass-card rounded-xl p-6 shadow-lg border border-slate-200 bg-white">
                            <div class="lg:col-span-2 space-y-6">
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Taxrate Name <span class="text-red-600">*</span></label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                        placeholder="e.g. Standard VAT 15%"
                                        class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Code Name <span class="text-red-600">*</span></label>
                                    <input type="text" name="code" value="<?= htmlspecialchars($d['code']); ?>" 
                                        placeholder="e.g. VAT15"
                                        class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none uppercase transition-all" required>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Taxrate (%) <span class="text-red-600">*</span></label>
                                    <input type="number" step="0.01" name="taxrate" value="<?= htmlspecialchars($d['taxrate']); ?>" 
                                        class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:ring-2 focus:ring-teal-600 outline-none transition-all" required>
                                </div>

                                <?php 
                                    $store_label = "Taxrate Available In"; 
                                    $search_placeholder = "Search branches...";
                                    include('../includes/store_select_component.php'); 
                                ?>
                            </div>

                            <div class="space-y-6">
                                <?php 
                                    $current_status = $d['status'];  
                                    $status_title = "Taxrate";      
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";

                                    include('../includes/status_card.php'); 
                                ?>

                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">
                                    <label class="block text-sm font-semibold text-slate-700 mb-3">Sort Order</label>
                                    <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                        class="w-full border border-slate-300 rounded-lg px-4 py-3 outline-none focus:ring-2 focus:ring-teal-600 transition-all">
                                </div>

                                <div class="space-y-3 mt-8">
                                    <button type="submit" name="<?= $btn_name; ?>" id="submitBtn"
                                        class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold py-4 rounded-xl shadow-lg transition-all transform hover:scale-[1.01]"> <?= $btn_text; ?> </button>
                                    
                                    <a href="/pos/taxrates/list" 
                                        class="block w-full bg-slate-100 text-slate-700 font-bold py-4 rounded-xl text-center hover:bg-slate-200 transition-all">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
/**
 * UI Logic for Store Selection and Form Validation
 */
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllStores');
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    const taxrateForm = document.getElementById('taxrateForm');
    const storeError = document.getElementById('store-error');

    // Handle "Select All" stores checkbox
    if(selectAll) {
        selectAll.addEventListener('change', function() {
            storeCheckboxes.forEach(cb => { cb.checked = this.checked; });
            validateCheckboxes();
        });
    }

    // Sync "Select All" state when individual stores are toggled
    storeCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(storeCheckboxes).every(c => c.checked);
            if(selectAll) selectAll.checked = allChecked;
            validateCheckboxes();
        });
    });

    /**
     * Toggles browser validation (required) based on selection
     */
    function validateCheckboxes() {
        const isOneChecked = Array.from(storeCheckboxes).some(cb => cb.checked);
        if(isOneChecked) {
            if(storeError) storeError.classList.add('hidden');
            storeCheckboxes.forEach(cb => cb.removeAttribute('required'));
        } else {
            if(storeCheckboxes.length > 0) {
                storeCheckboxes[0].setAttribute('required', 'required');
            }
        }
    }

    // Ensure at least one store is selected before form submission
    taxrateForm.addEventListener('submit', function(e) {
        const isOneChecked = Array.from(storeCheckboxes).some(cb => cb.checked);
        if(!isOneChecked) {
            e.preventDefault();
            if(storeError) {
                storeError.classList.remove('hidden');
                storeError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Run initial validation for Edit mode
    validateCheckboxes();
});
</script>
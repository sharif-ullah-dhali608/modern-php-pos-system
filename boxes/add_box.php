<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php"); 
    exit(0);
}

// Initialize variables
$mode = "create";
$btn_name = "save_box_btn";
$btn_text = "Save Box";
$page_title = "Add New Box";

// Default Data Array
$d = [
    'id' => '',
    'box_name' => '',
    'code_name' => '',
    'barcode_id' => '',
    'shelf_number' => '',
    'storage_type' => '', // Dropdown value
    'max_capacity' => '',
    'box_details' => '',
    'status' => '1',
    'sort_order' => '0'
];

// Check if edit mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_box_btn";
    $btn_text = "Update Box";
    $page_title = "Edit Box Information";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM boxes WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Box not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: box_list.php");
        exit(0);
    }
}

// Fetch stores for multi-select
$stores_query = "SELECT * FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) {
    $all_stores[] = $store;
}

// Get selected stores if editing
$selected_stores = [];
if($mode == 'edit' && isset($d['id'])) {
    // Assuming pivot table is box_stores or similar
    $store_box_query = "SELECT store_id FROM box_stores WHERE box_id='{$d['id']}'";
    $store_box_result = mysqli_query($conn, $store_box_query);
    while($sc = mysqli_fetch_assoc($store_box_result)) {
        $selected_stores[] = $sc['store_id'];
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
                        <a href="/pos/boxes/box_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Create New Storage Box' : 'Update Box Configuration'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/boxes/save_box.php" method="POST" id="boxForm">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="box_id" value="<?= $d['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 glass-card rounded-xl p-6 shadow-lg border border-slate-200 bg-white">
                            
                            <div class="lg:col-span-2 space-y-6">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Box Name <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                name="box_name" 
                                                value="<?= htmlspecialchars($d['box_name']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all"
                                                placeholder="e.g., Red Bin A1"
                                                required
                                            >
                                            <i class="fas fa-box-open absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Code Name <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                name="code_name" 
                                                value="<?= htmlspecialchars($d['code_name']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all uppercase"
                                                placeholder="e.g., BX-001"
                                                required
                                            >
                                            <i class="fas fa-tag absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Barcode ID <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                name="barcode_id" 
                                                value="<?= htmlspecialchars($d['barcode_id']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all"
                                                placeholder="Scan or type barcode"
                                                required
                                            >
                                            <i class="fas fa-barcode absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Shelf / Rack Number <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                name="shelf_number" 
                                                value="<?= htmlspecialchars($d['shelf_number']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all"
                                                placeholder="e.g., Rack-05, Shelf-B"
                                                required
                                            >
                                            <i class="fas fa-layer-group absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Storage Type <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <select 
                                                name="storage_type" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all appearance-none"
                                                required
                                            >
                                                <option value="" disabled <?= empty($d['storage_type']) ? 'selected' : ''; ?>>Select Type</option>
                                                <option value="Dry" <?= $d['storage_type'] == 'Dry' ? 'selected' : ''; ?>>Dry Storage</option>
                                                <option value="Cold" <?= $d['storage_type'] == 'Cold' ? 'selected' : ''; ?>>Cold Storage</option>
                                                <option value="Fragile" <?= $d['storage_type'] == 'Fragile' ? 'selected' : ''; ?>>Fragile</option>
                                                <option value="Hazardous" <?= $d['storage_type'] == 'Hazardous' ? 'selected' : ''; ?>>Hazardous</option>
                                            </select>
                                            <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                                            Max Capacity (Qty) <span class="text-red-600">*</span>
                                        </label>
                                        <div class="relative">
                                            <input 
                                                type="number" 
                                                name="max_capacity" 
                                                value="<?= htmlspecialchars($d['max_capacity']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all"
                                                placeholder="e.g., 50"
                                                min="1"
                                                required
                                            >
                                            <i class="fas fa-weight-hanging absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Box Details
                                    </label>
                                    <textarea 
                                        name="box_details" 
                                        rows="3" 
                                        class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all resize-none"
                                        placeholder="Additional information about this box..."
                                    ><?= htmlspecialchars($d['box_details']); ?></textarea>
                                </div>
                                
                                <?php 
                                    $store_label = "Boxes Active Branches <span class='text-red-600'>*</span>"; 
                                    $search_placeholder = "Search branches for this box...";
                                    // Re-using your existing component
                                    include('../includes/store_select_component.php'); 
                                ?>
                            </div>
                            
                            <div class="space-y-6">
                                
                                <?php 
                                    $current_status = $d['status'];  
                                    $status_title = "Box";      
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";

                                    include('../includes/status_card.php'); 
                                ?>
                                
                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm">
                                    <label class="block text-sm font-semibold text-slate-700 mb-4">
                                        Sort Order <span class="text-red-600">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        name="sort_order" 
                                        value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                        class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all"
                                        min="0"
                                        required
                                    >
                                </div>
                                
                                <div class="space-y-3">
                                    <button 
                                        type="submit" 
                                        name="<?= $btn_name; ?>" 
                                        class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold py-3 rounded-lg shadow-lg transition-all transform hover:scale-[1.01]"
                                    >
                                        <i class="fas fa-save mr-2"></i>
                                        <?= $btn_text; ?>
                                    </button>
                                    
                                    <a 
                                        href="/pos/boxes/box_list.php" 
                                        class="block w-full bg-slate-100 text-slate-700 font-semibold py-3 rounded-lg text-center hover:bg-slate-200 transition-all"
                                    >
                                        <i class="fas fa-times mr-2"></i>
                                        Cancel
                                    </a>
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
// Filter Stores Script (Same as Currency)
function filterStores() {
    const input = document.getElementById('storeSearch');
    const filter = input.value.toLowerCase();
    const storeList = document.getElementById('storeList');
    const labels = storeList.querySelectorAll('label'); 
    
    for (let i = 0; i < labels.length; i++) {
        const span = labels[i].querySelector('span');
        const text = span ? span.textContent || span.innerText : labels[i].textContent || labels[i].innerText;
        
        if (text.toLowerCase().indexOf(filter) > -1) {
            labels[i].style.display = 'flex'; 
        } else {
            labels[i].style.display = 'none';
        }
    }
}
</script>
<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Initialize variables
$mode = "create";
$btn_name = "save_currency_btn";
$btn_text = "Save Currency";
$page_title = "Add Currency";

$d = [
    'id' => '',
    'currency_name' => '',
    'code' => '',
    'symbol_left' => '',
    'symbol_right' => '',
    'decimal_place' => '2',
    'status' => '1',
    'sort_order' => '0'
];

// Check if edit mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_currency_btn";
    $btn_text = "Update Currency";
    $page_title = "Edit Currency";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM currencies WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Currency not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: currency_list.php");
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
    $store_currency_query = "SELECT store_id FROM store_currency WHERE currency_id='{$d['id']}'";
    $store_currency_result = mysqli_query($conn, $store_currency_query);
    while($sc = mysqli_fetch_assoc($store_currency_result)) {
        $selected_stores[] = $sc['store_id'];
    }
}

include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <!-- Page Header -->
            <div class="mb-6 slide-in">
                <div class="flex items-center gap-4 mb-4">
                    <a href="/pos/index.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white transition-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2"><?= $page_title; ?></h1>
                        <div class="flex items-center gap-2 text-sm text-white/60">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span><?= $mode == 'create' ? 'Create New Currency' : 'Update Currency Information'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Card -->
            <div class="glass-card rounded-xl p-8 slide-in">
                <form action="save_currency.php" method="POST" id="currencyForm">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="currency_id" value="<?= $d['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Currency Name -->
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    Currency Name <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        name="currency_name" 
                                        value="<?= htmlspecialchars($d['currency_name']); ?>" 
                                        class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                        placeholder="e.g., United States Dollar"
                                        required
                                    >
                                    <i class="fas fa-money-bill-wave absolute right-3 top-1/2 transform -translate-y-1/2 text-white/40"></i>
                                </div>
                            </div>
                            
                            <!-- Code -->
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    Code <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        name="code" 
                                        value="<?= htmlspecialchars($d['code']); ?>" 
                                        class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all uppercase"
                                        placeholder="e.g., USD"
                                        maxlength="3"
                                        required
                                    >
                                    <i class="fas fa-barcode absolute right-3 top-1/2 transform -translate-y-1/2 text-white/40"></i>
                                </div>
                            </div>
                            
                            <!-- Symbols -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-white mb-2">
                                        Symbol Left
                                    </label>
                                    <input 
                                        type="text" 
                                        name="symbol_left" 
                                        value="<?= htmlspecialchars($d['symbol_left']); ?>" 
                                        class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                        placeholder="e.g., $"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-white mb-2">
                                        Symbol Right
                                    </label>
                                    <input 
                                        type="text" 
                                        name="symbol_right" 
                                        value="<?= htmlspecialchars($d['symbol_right']); ?>" 
                                        class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                        placeholder="e.g., €"
                                    >
                                </div>
                            </div>
                            
                            <!-- Decimal Place -->
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    Decimal Place <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="decimal_place" 
                                    value="<?= htmlspecialchars($d['decimal_place']); ?>" 
                                    class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                    min="0"
                                    max="4"
                                    required
                                >
                            </div>
                            
                            <!-- Stores Multi-Select -->
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">
                                    Store <span class="text-red-500">*</span>
                                </label>
                                <div class="bg-white/10 border border-white/20 rounded-lg p-4 max-h-48 overflow-y-auto">
                                    <div class="mb-3">
                                        <input 
                                            type="text" 
                                            id="storeSearch" 
                                            placeholder="Search stores..." 
                                            class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                            onkeyup="filterStores()"
                                        >
                                    </div>
                                    <div class="space-y-2" id="storeList">
                                        <?php foreach($all_stores as $store): ?>
                                            <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    name="stores[]" 
                                                    value="<?= $store['id']; ?>"
                                                    <?= in_array($store['id'], $selected_stores) ? 'checked' : ''; ?>
                                                    class="w-4 h-4 rounded accent-purple-600"
                                                >
                                                <span class="text-white/80"><?= htmlspecialchars($store['store_name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-6">
                            <!-- Status -->
                            <div class="glass-card rounded-lg p-6">
                                <label class="block text-sm font-semibold text-white mb-4">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="status" 
                                    class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                    required
                                >
                                    <option value="1" <?= $d['status'] == '1' ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?= $d['status'] == '0' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <!-- Sort Order -->
                            <div class="glass-card rounded-lg p-6">
                                <label class="block text-sm font-semibold text-white mb-4">
                                    Sort Order <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="sort_order" 
                                    value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                    class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                    min="0"
                                    required
                                >
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <button 
                                    type="submit" 
                                    name="<?= $btn_name; ?>" 
                                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-3 rounded-lg shadow-lg hover:from-purple-700 hover:to-indigo-700 transition-all transform hover:scale-105"
                                >
                                    <i class="fas fa-save mr-2"></i>
                                    <?= $btn_text; ?>
                                </button>
                                
                                <a 
                                    href="currency_list.php" 
                                    class="block w-full bg-white/10 text-white font-semibold py-3 rounded-lg text-center hover:bg-white/20 transition-all"
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
    </main>
</div>

<script>
function filterStores() {
    const input = document.getElementById('storeSearch');
    const filter = input.value.toLowerCase();
    const storeList = document.getElementById('storeList');
    const labels = storeList.getElementsByTagName('label');
    
    for (let i = 0; i < labels.length; i++) {
        const text = labels[i].textContent || labels[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            labels[i].style.display = '';
        } else {
            labels[i].style.display = 'none';
        }
    }
}
</script>


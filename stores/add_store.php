<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php"); // Updated path to standard signin
    exit(0);
}

// 2. INITIALIZE VARIABLES (DEFAULT VALUES)
$mode = "create";
$btn_name = "save_store_btn";
$btn_text = "Create Store";
$page_title = "Create New Store";

// Initialize empty array
$d = [
    'id' => '', 'store_name' => '', 'store_code' => '', 'business_type' => '',
    'email' => '', 'phone' => '', 'address' => '', 'city_zip' => '',
    'vat_number' => '', 'timezone' => 'Asia/Dhaka', 
    'max_line_disc' => '0', 'max_inv_disc' => '0', 'approval_disc' => '0',
    'overselling' => 'deny', 'low_stock' => '5', 
    'status' => '1', 'daily_target' => '', 'open_time' => '09:00', 'close_time' => '21:00',
    'allow_manual_price' => '0', 'allow_backdate' => '0'
];

// 3. CHECK IF EDIT MODE
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_store_btn";
    $btn_text = "Update Store";
    $page_title = "Edit Store Config";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM stores WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Store not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: store_list.php");
        exit(0);
    }
}

// 4. DEFINE LIGHT MODE INPUT CLASSES
// Note: We remove PHP variables for fields and use direct Tailwind classes for simplicity and consistency.
$error_msg_style = "text-rose-600 text-xs font-bold mt-1 ml-1 hidden flex items-center gap-1 slide-down"; 

// 5. INCLUDE HEADER 
include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen transition-all duration-300">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <div class="mb-8 slide-in">
                <div class="flex items-center gap-4 mb-4">
                    <a href="/pos/stores/store_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span><?= $mode == 'create' ? 'System Configuration' : 'Update Configuration'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="save_store.php" method="POST" id="storeForm" novalidate>
                
                <?php if($mode == 'edit'): ?>
                    <input type="hidden" name="store_id" value="<?= $d['id']; ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                    <div class="lg:col-span-8 space-y-6">
                        
                        <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-1 bg-white">
                            <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white flex items-center justify-center text-lg shadow-md shadow-emerald-900/40"><i class="fas fa-store"></i></span>
                                Store Identity
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                
                                <div class="relative w-full mb-1 group md:col-span-2">
                                    <input type="text" name="store_name" id="store_name" value="<?= htmlspecialchars($d['store_name']); ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 font-medium" placeholder="Store Name *">
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-pen-nib"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_store_name"><i class="fas fa-exclamation-circle"></i> <span>Store name is required</span></p>
                                </div>

                                <div class="relative w-full mb-1 group">
                                    <input type="text" name="store_code" id="store_code" value="<?= htmlspecialchars($d['store_code']); ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 uppercase tracking-wider font-mono" placeholder="Branch Code *">
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-barcode"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_store_code"><i class="fas fa-exclamation-circle"></i> <span>Code required (A-Z, 0-9 only)</span></p>
                                </div>

                                <div class="relative w-full mb-1 group">
                                    <select name="business_type" id="business_type" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all cursor-pointer">
                                        <option value="" disabled <?= $mode == 'create' ? 'selected' : ''; ?>>Select Business Type *</option>
                                        <option value="Retail" <?= $d['business_type'] == 'Retail' ? 'selected' : ''; ?>>Retail Store</option>
                                        <option value="Grocery" <?= $d['business_type'] == 'Grocery' ? 'selected' : ''; ?>>Grocery / Super Shop</option>
                                        <option value="Restaurant" <?= $d['business_type'] == 'Restaurant' ? 'selected' : ''; ?>>Restaurant / Cafe</option>
                                        <option value="Fashion" <?= $d['business_type'] == 'Fashion' ? 'selected' : ''; ?>>Fashion & Apparel</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_business_type"><i class="fas fa-exclamation-circle"></i> <span>Please select a type</span></p>
                                </div>

                                <div class="relative w-full mb-1 group">
                                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400" placeholder="Official Email">
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-envelope"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_email"><i class="fas fa-exclamation-circle"></i> <span>Invalid email format</span></p>
                                </div>

                                <div class="relative w-full mb-1 group">
                                    <div class="flex gap-2">
                                        <div class="relative w-1/3">
                                            <select name="country_code" class="block w-full py-3.5 px-2 text-sm text-slate-800 bg-white rounded-xl border border-slate-300 focus:outline-none focus:border-purple-600 cursor-pointer appearance-none text-center font-bold">
                                                <option value="+880">🇧🇩 +880</option>
                                                <option value="+1">🇺🇸 +1</option>
                                                <option value="+44">🇬🇧 +44</option>
                                                <option value="+91">🇮🇳 +91</option>
                                                <option value="+971">🇦🇪 +971</option>
                                            </select>
                                        </div>
                                        
                                        <div class="relative w-2/3">
                                            <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($d['phone']); ?>" 
                                                class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400" placeholder="Phone Number *" 
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)">
                                            <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-phone"></i>
                                        </div>
                                    </div>
                                    <p class="<?= $error_msg_style; ?>" id="err_phone"><i class="fas fa-exclamation-circle"></i> <span>Valid phone number required</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-2 bg-white">
                            <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-lg shadow-md shadow-indigo-600/40"><i class="fas fa-map-marked-alt"></i></span>
                                Location & Tax
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                                <div class="relative w-full mb-1 group md:col-span-2">
                                    <textarea name="address" id="address" rows="2" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 resize-none" placeholder="Full Address *"><?= htmlspecialchars($d['address']); ?></textarea>
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-map-pin"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_address"><i class="fas fa-exclamation-circle"></i> <span>Address is required</span></p>
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <input type="text" name="city_zip" id="city_zip" value="<?= htmlspecialchars($d['city_zip']); ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400" placeholder="City & Zip Code *">
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-city"></i>
                                    <p class="<?= $error_msg_style; ?>" id="err_city_zip"><i class="fas fa-exclamation-circle"></i> <span>Required</span></p>
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <input type="text" name="vat_number" id="vat_number" value="<?= htmlspecialchars($d['vat_number']); ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400" placeholder="VAT / BIN Number">
                                    <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-purple-600 transition-colors text-lg fas fa-file-invoice-dollar"></i>
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <select name="timezone" id="timezone" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all cursor-pointer">
                                        <option value="Asia/Dhaka" <?= $d['timezone'] == 'Asia/Dhaka' ? 'selected' : ''; ?>>Asia/Dhaka (GMT+6)</option>
                                        <option value="UTC" <?= $d['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                            <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg shadow-md shadow-rose-600/40"><i class="fas fa-shield-alt"></i></span>
                                Operational Controls
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="relative w-full mb-1 group">
                                    <input type="number" name="max_line_disc" id="max_line_disc" value="<?= $d['max_line_disc']; ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center font-bold text-purple-600" placeholder="Max Line Disc %" min="0" max="100">
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <input type="number" name="max_inv_disc" id="max_inv_disc" value="<?= $d['max_inv_disc']; ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center font-bold text-purple-600" placeholder="Max Inv Disc %" min="0" max="100">
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <input type="number" name="approval_disc" id="approval_disc" value="<?= $d['approval_disc']; ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center font-bold text-purple-600" placeholder="Manager Appr %" min="0" max="100">
                                </div>
                                <div class="relative w-full mb-1 group md:col-span-2">
                                    <select name="overselling" id="overselling" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all cursor-pointer">
                                        <option value="deny" <?= $d['overselling'] == 'deny' ? 'selected' : ''; ?>>🚫 Do Not Allow (Strict)</option>
                                        <option value="warning" <?= $d['overselling'] == 'warning' ? 'selected' : ''; ?>>⚠️ Allow with Warning</option>
                                        <option value="allow" <?= $d['overselling'] == 'allow' ? 'selected' : ''; ?>>✅ Allow Unlimited</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                                </div>
                                <div class="relative w-full mb-1 group">
                                    <input type="number" name="low_stock" id="low_stock" value="<?= $d['low_stock']; ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center font-bold text-purple-600" placeholder="Low Stock Alert" min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4 space-y-6 sticky top-28">
                        
                        <div class="glass-card rounded-xl p-8 slide-in delay-1 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white shadow-xl shadow-indigo-600/30">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-xl">Store Status</h3>
                                    <p class="text-white/80 text-sm mt-1 opacity-90 font-medium">Branch operations</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="status" class="sr-only peer" <?= $d['status'] == 1 ? 'checked' : ''; ?>>
                                    <div class="w-16 h-9 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-white/50 shadow-inner"></div>
                                </label>
                            </div>
                        </div>

                        <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-2 bg-white">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6 flex items-center gap-2">
                                <i class="fas fa-clock"></i> Daily Operations
                            </h3>
                            <div class="space-y-5">
                                <div class="relative w-full mb-1 group">
                                    <input type="number" name="daily_target" id="daily_target" value="<?= $d['daily_target']; ?>" 
                                        class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 pl-8 font-mono font-bold text-purple-600 text-right text-lg" placeholder="Daily Sales Target" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    <i class="absolute left-4 top-4 text-slate-400 text-lg fas fa-dollar-sign"></i>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="relative w-full mb-1 group">
                                        <input type="time" name="open_time" id="open_time" value="<?= $d['open_time']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center px-2 font-bold">
                                        <label for="open_time" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-6 scale-75 top-4 left-4 origin-[0] peer-focus:left-4 peer-focus:text-purple-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 pointer-events-none bg-white px-2 rounded-md z-10 font-semibold">Open</label>
                                    </div>
                                    <div class="relative w-full mb-1 group">
                                        <input type="time" name="close_time" id="close_time" value="<?= $d['close_time']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all placeholder-slate-400 text-center px-2 font-bold">
                                        <label for="close_time" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-6 scale-75 top-4 left-4 origin-[0] peer-focus:left-4 peer-focus:text-purple-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 pointer-events-none bg-white px-2 rounded-md z-10 font-semibold">Close</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-6 flex items-center gap-2">
                                <i class="fas fa-toggle-on"></i> Quick Rules
                            </h3>
                            <div class="space-y-4">
                                <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-100 transition cursor-pointer group">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-600 group-hover:text-purple-600 transition shadow-sm"><i class="fas fa-hand-holding-usd"></i></span>
                                        <span class="text-sm font-bold text-slate-800 group-hover:text-purple-600 transition">Manual Price</span>
                                    </div>
                                    <div class="relative inline-flex items-center">
                                        <input type="checkbox" name="allow_manual_price" class="sr-only peer" <?= $d['allow_manual_price'] == 1 ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-slate-400 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600 shadow-inner"></div>
                                    </div>
                                </label>
                                <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-slate-100 border border-slate-100 transition cursor-pointer group">
                                    <div class="flex items-center gap-3">
                                        <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-600 group-hover:text-rose-600 transition shadow-sm"><i class="fas fa-calendar-times"></i></span>
                                        <span class="text-sm font-bold text-slate-800 group-hover:text-rose-600 transition">Backdated Sales</span>
                                    </div>
                                    <div class="relative inline-flex items-center">
                                        <input type="checkbox" name="allow_backdate" class="sr-only peer" <?= $d['allow_backdate'] == 1 ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-slate-400 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600 shadow-inner"></div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="slide-in delay-3 pt-4">
                            <button type="submit" name="<?= $btn_name; ?>" class="w-full py-4 rounded-2xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold text-lg shadow-2xl shadow-emerald-900/30 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 flex items-center justify-center gap-3 group">
                                <span><?= $btn_text; ?></span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>
                    </div> 
                </div>
            </form>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
    // --- JavaScript Validation Logic (Retained and adjusted for light mode CSS) ---

    // Setup real-time validation removal
    document.addEventListener('DOMContentLoaded', function() {
        const fields = ['store_name', 'store_code', 'business_type', 'phone', 'email', 'address', 'city_zip'];
        
        fields.forEach(id => {
            const el = document.getElementById(id);
            if(el) {
                const clearError = () => {
                    const errEl = document.getElementById('err_' + id);
                    if(errEl && !errEl.classList.contains('hidden')) {
                        errEl.classList.add('hidden');
                        
                        let parent = el.closest('.relative.w-full.mb-1.group');
                        // Special handling for nested 'phone' field
                        if(id === 'phone') {
                            // Find the correct parent wrapper that holds the error message
                            parent = el.closest('.relative.w-full.mb-1.group'); 
                        }
                        if(parent) parent.classList.remove('has-error');
                    }
                };

                el.addEventListener('input', clearError);
                el.addEventListener('focus', clearError);
                el.addEventListener('blur', clearError); 
                el.addEventListener('change', clearError); 
            }
        });
    });

    document.getElementById('storeForm').addEventListener('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // Helper to find the correct error wrapper (closest relative parent)
        function getErrorParent(id) {
            const el = document.getElementById(id);
            if (id === 'phone') {
                return el.closest('.relative.w-full.mb-1.group');
            }
            // For other fields, assume the direct parent wrapper is correct
            return el.closest('.relative.w-full.mb-1.group');
        }

        // Helper to show/hide error
        function toggleError(id, show) {
            const el = document.getElementById('err_' + id);
            const parentDiv = getErrorParent(id);
            
            if(el && parentDiv) {
                if(show) {
                    el.classList.remove('hidden');
                    parentDiv.classList.add('has-error');
                    if(!firstError) firstError = document.getElementById(id);
                } else {
                    el.classList.add('hidden');
                    parentDiv.classList.remove('has-error');
                }
            }
        }

        // 1. Validate Store Name
        const name = document.getElementById('store_name');
        if(name.value.trim() === '') {
            isValid = false;
            toggleError('store_name', true);
        } else {
            toggleError('store_name', false);
        }

        // 2. Validate Code (Alphanumeric)
        const code = document.getElementById('store_code');
        const codeRegex = /^[A-Z0-9]+$/i;
        if(code.value.trim() === '' || !codeRegex.test(code.value)) {
            isValid = false;
            toggleError('store_code', true);
        } else {
            toggleError('store_code', false);
        }

        // 3. Validate Business Type
        const type = document.getElementById('business_type');
        if(type.value === '') {
            isValid = false;
            toggleError('business_type', true);
        } else {
            toggleError('business_type', false);
        }

        // 4. Validate Phone (Digits only, length 10-15)
        const phone = document.getElementById('phone');
        if(phone.value.trim() === '' || phone.value.length < 10 || phone.value.length > 15) {
            isValid = false;
            toggleError('phone', true); 
        } else {
            toggleError('phone', false);
        }

        // 5. Validate Email (Optional but if exists check format)
        const email = document.getElementById('email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(email.value.trim() !== '' && !emailRegex.test(email.value)) {
            isValid = false;
            toggleError('email', true);
        } else {
            toggleError('email', false);
        }

        // 6. Validate Address
        const address = document.getElementById('address');
        if(address.value.trim() === '') {
            isValid = false;
            toggleError('address', true);
        } else {
            toggleError('address', false);
        }

        // 7. Validate City/Zip
        const city = document.getElementById('city_zip');
        if(city.value.trim() === '') {
            isValid = false;
            toggleError('city_zip', true);
        } else {
            toggleError('city_zip', false);
        }

        if(!isValid) {
            e.preventDefault();
            if(firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            // Optional: Shake animation feedback
            Swal.fire({
                icon: 'error',
                title: 'Check fields',
                text: 'Please fill up the required fields correctly.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
</script>
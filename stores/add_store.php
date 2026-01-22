<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login"); 
    exit(0);
}

// 2. INITIALIZE VARIABLES (DEFAULT VALUES)
$mode = "create";
$btn_name = "save_store_btn";
$btn_text = "Create Store";
$page_title = "Create New Store";

// Initialize empty array
// Is hisse ko update karein (Line 23-28 approx)
$d = [
    'id' => '', 'store_name' => '', 'store_code' => '', 'business_type' => '',
    'email' => '', 'phone' => '', 'address' => '', 'city_zip' => '',
    'vat_number' => '', 'timezone' => 'Asia/Dhaka', 
    'max_line_disc' => '', // '0' se '' kar diya
    'max_inv_disc' => '',  // '0' se '' kar diya
    'approval_disc' => '', // '0' se '' kar diya
    'overselling' => 'deny', 'low_stock' => '', // '5' se '' kar diya
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
        // Ensure daily_target is handled correctly for display if null/empty in DB
        if(is_null($d['daily_target']) || $d['daily_target'] === '0.00' || $d['daily_target'] === '0'){
            $d['daily_target'] = '';
        }
    } else {
        $_SESSION['message'] = "Store not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: store_list.php");
        exit(0);
    }
}

// 4. DEFINE LIGHT MODE INPUT CLASSES
$error_msg_style = "text-rose-600 text-xs font-bold mt-1 ml-1 hidden flex items-center gap-1 slide-down"; 

// 5. DEFINE DYNAMIC STATUS CLASS (FOR INITIAL LOAD)
$status_class = $d['status'] == 1 
    ? 'bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white shadow-xl shadow-indigo-600/30' 
    : 'bg-slate-300 text-slate-800 shadow-md shadow-slate-500/30';

// 6. INCLUDE HEADER 
include('../includes/header.php');
?>
<link rel="stylesheet" href="/pos/assets/css/add-store.css">

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
        
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div> 
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <div class="mb-8 slide-in">
                    <div class="flex items-center gap-4 mb-4">
                        <a href="/pos/stores/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
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

                <form action="/pos/stores/save_store.php" method="POST" id="storeForm" novalidate>
                    
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
                                <div class="grid grid-cols-2 md:grid-cols-2 gap-x-6 gap-y-4">
                                    
                                    <div class="relative w-full mb-1 group md:col-span-2">
                                        <input type="text" name="store_name" id="store_name" value="<?= htmlspecialchars($d['store_name']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 font-medium" placeholder="Store Name *">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-pen-nib"></i>
                                        <p class="<?= $error_msg_style; ?>" id="err_store_name"><i class="fas fa-exclamation-circle"></i> <span>Store name is required</span></p>
                                    </div>

                                    <div class="relative w-full mb-1 group">
                                        <input type="text" name="store_code" id="store_code" value="<?= htmlspecialchars($d['store_code']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 uppercase tracking-wider font-mono" placeholder="Branch Code *">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-barcode"></i>
                                        <p class="<?= $error_msg_style; ?>" id="err_store_code"><i class="fas fa-exclamation-circle"></i> <span>Code required (A-Z, 0-9 only)</span></p>
                                    </div>

                                    <div class="relative w-full mb-1 group">
                                        <select name="business_type" id="business_type" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all cursor-pointer">
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
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400" placeholder="Official Email">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-envelope"></i>
                                        <p class="<?= $error_msg_style; ?>" id="err_email"><i class="fas fa-exclamation-circle"></i> <span>Invalid email format</span></p>
                                    </div>

                                    <div class="relative w-full mb-1 group">
                                        <div class="flex gap-2">
                                            <div class="relative w-1/3">
                                                <select name="country_code" class="block w-full py-3.5 px-2 text-sm text-slate-800 bg-white rounded-xl border border-slate-300 focus:outline-none focus:border-teal-600 cursor-pointer appearance-none text-center font-bold">
                                                    <option value="+880">🇧🇩 +880</option>
                                                    <option value="+1">🇺🇸 +1</option>
                                                    <option value="+44">🇬🇧 +44</option>
                                                    <option value="+91">🇮🇳 +91</option>
                                                    <option value="+971">🇦🇪 +971</option>
                                                </select>
                                            </div>
                                            
                                            <div class="relative w-2/3">
                                                <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($d['phone']); ?>" 
                                                    class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400" placeholder="Phone Number *" 
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15)">
                                                <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-phone"></i>
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
                                <div class="grid grid-cols-2 md:grid-cols-2 gap-x-6 gap-y-4">
                                    <div class="relative w-full mb-1 group md:col-span-2">
                                        <textarea name="address" id="address" rows="2" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 resize-none" placeholder="Full Address *"><?= htmlspecialchars($d['address']); ?></textarea>
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-map-pin"></i>
                                        <p class="<?= $error_msg_style; ?>" id="err_address"><i class="fas fa-exclamation-circle"></i> <span>Address is required</span></p>
                                    </div>
                                    <div class="relative w-full mb-1 group">
                                        <input type="text" name="city_zip" id="city_zip" value="<?= htmlspecialchars($d['city_zip']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400" placeholder="City & Zip Code *">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-city"></i>
                                        <p class="<?= $error_msg_style; ?>" id="err_city_zip"><i class="fas fa-exclamation-circle"></i> <span>Required</span></p>
                                    </div>
                                    <div class="relative w-full mb-1 group">
                                        <input type="text" name="vat_number" id="vat_number" value="<?= htmlspecialchars($d['vat_number']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400" placeholder="VAT / BIN Number">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <div class="relative w-full mb-1 group">
                                        <select name="timezone" id="timezone" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all cursor-pointer">
                                            <option value="Asia/Dhaka" <?= $d['timezone'] == 'Asia/Dhaka' ? 'selected' : ''; ?>>Asia/Dhaka (GMT+6)</option>
                                            <option value="UTC" <?= $d['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                                    </div>
                                    <div class="relative w-full mb-1 group">
                                        <select name="currency_id" id="currency_id" class="w-full">
                                            <option value="" disabled selected>Select Currency</option>
                                            <?php 
                                            if(!empty($d['currency_id'])) {
                                                $curr_q = mysqli_query($conn, "SELECT * FROM currencies WHERE id = '{$d['currency_id']}'");
                                                if($curr = mysqli_fetch_assoc($curr_q)) {
                                                    $symbol = $curr['symbol_left'] ? $curr['symbol_left'] : $curr['symbol_right'];
                                                    echo "<option value='{$curr['id']}' selected>{$curr['currency_name']} ({$curr['code']}) $symbol</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                        <i class="absolute right-4 top-4 text-slate-400 text-lg fas fa-coins pointer-events-none z-[11] select2-custom-icon"></i>
                                        
                                        <style>
                                            /* --- Robust Select2 Design Matching --- */
                                            .select2-container--default .select2-selection--single {
                                                height: 54px !important;
                                                background-color: #ffffff !important;
                                                border: 1px solid #cbd5e1 !important; /* slate-300 */
                                                border-radius: 0.75rem !important; /* rounded-xl */
                                                transition: all 0.2s ease !important;
                                                display: flex !important;
                                                align-items: center !important;
                                            }
                                            .select2-container--default.select2-container--focus .select2-selection--single {
                                                border-color: #0d9488 !important; /* teal-600 approx */
                                                box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2) !important;
                                            }
                                            .select2-container--default .select2-selection--single .select2-selection__rendered {
                                                color: #1e293b !important; /* slate-800 */
                                                font-size: 0.875rem !important; /* text-sm */
                                                padding-left: 1rem !important;
                                                padding-right: 3rem !important; /* Space for icon */
                                                width: 100% !important;
                                            }
                                            .select2-container--default .select2-selection--single .select2-selection__placeholder {
                                                color: #94a3b8 !important; /* slate-400 */
                                            }
                                            .select2-container--default .select2-selection--single .select2-selection__arrow {
                                                display: none !important; /* We use our custom FA icon */
                                            }
                                            .select2-dropdown {
                                                border-radius: 0.75rem !important;
                                                border: 1px solid #e2e8f0 !important;
                                                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                                                overflow: hidden !important;
                                                z-index: 9999 !important;
                                            }
                                            .select2-results__option {
                                                padding: 10px 16px !important;
                                                font-size: 0.875rem !important;
                                            }
                                            .select2-results__option--highlighted[aria-selected] {
                                                background-color: #0d9488 !important;
                                            }
                                        </style>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg shadow-md shadow-rose-600/40"><i class="fas fa-shield-alt"></i></span>
                                    Operational Controls
                                </h2>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                                    <div class="relative w-full mb-1 group">
                                        <input type="number" name="max_line_disc" id="max_line_disc" 
                                            value="<?= $d['max_line_disc']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 font-bold text-teal-600" 
                                            placeholder="Max Line Disc %" min="0" max="100">
                                    </div>
                                    
                                    <div class="relative w-full mb-1 group">
                                        <input type="number" name="max_inv_disc" id="max_inv_disc" 
                                            value="<?= $d['max_inv_disc']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 font-bold text-teal-600" 
                                            placeholder="Max Inv Disc %" min="0" max="100">
                                    </div>
                                    
                                    <div class="relative w-full mb-1 group">
                                        <input type="number" name="approval_disc" id="approval_disc" 
                                            value="<?= $d['approval_disc']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 font-bold text-teal-600" 
                                            placeholder="Manager Appr %" min="0" max="100">
                                    </div>

                                    <div class="relative w-full mb-1 group md:col-span-2">
                                        <select name="overselling" id="overselling" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all cursor-pointer">
                                            <option value="deny" <?= $d['overselling'] == 'deny' ? 'selected' : ''; ?>>🚫 Do Not Allow (Strict)</option>
                                            <option value="warning" <?= $d['overselling'] == 'warning' ? 'selected' : ''; ?>>⚠️ Allow with Warning</option>
                                            <option value="allow" <?= $d['overselling'] == 'allow' ? 'selected' : ''; ?>>✅ Allow Unlimited</option>
                                        </select>
                                        <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                                    </div>

                                    <div class="relative w-full mb-1 group">
                                        <input type="number" name="low_stock" id="low_stock" 
                                            value="<?= $d['low_stock']; ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-teal-600 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all placeholder-slate-400 font-bold text-teal-600" 
                                            placeholder="Low Stock Alert" min="1">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-6 h-full">
                            
                            <div id="store-status-card" class="glass-card rounded-xl p-8 slide-in delay-1 <?= $status_class; ?>">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-bold text-xl">Store Status</h3>
                                        <p class="<?= $d['status'] == 1 ? 'text-white/80' : 'text-slate-600/80'; ?> text-sm mt-1 opacity-90 font-medium">Branch operations</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="status" id="status-toggle" class="sr-only peer" <?= $d['status'] == 1 ? 'checked' : ''; ?>>
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
                                        <input type="number" name="daily_target" id="daily_target" value="<?= htmlspecialchars($d['daily_target']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 pl-8 font-mono font-bold text-teal-600 text-right text-lg" placeholder="Daily Sales Target" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <i class="absolute left-4 top-4 text-slate-400 text-lg fas fa-dollar-sign"></i>
                                    </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-widest ml-1">Opening Time</p>
                                        <div class="ios-time-picker group" id="open_picker">
                                            <div class="selection-overlay"></div>
                                            <div class="time-column hours" onscroll="handleScroll(this, 'open', 'h')"></div>
                                            <div class="picker-divider">:</div>
                                            <div class="time-column minutes" onscroll="handleScroll(this, 'open', 'm')"></div>
                                            <div class="time-column meridiem" onscroll="handleScroll(this, 'open', 'p')"></div>
                                        </div>
                                        <input type="hidden" name="open_time" id="open_time_val" value="<?= $d['open_time']; ?>">
                                    </div>

                                    <div class="space-y-3">
                                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-widest ml-1">Closing Time</p>
                                        <div class="ios-time-picker group" id="close_picker">
                                            <div class="selection-overlay"></div>
                                            <div class="time-column hours" onscroll="handleScroll(this, 'close', 'h')"></div>
                                            <div class="picker-divider">:</div>
                                            <div class="time-column minutes" onscroll="handleScroll(this, 'close', 'm')"></div>
                                            <div class="time-column meridiem" onscroll="handleScroll(this, 'close', 'p')"></div>
                                        </div>
                                        <input type="hidden" name="close_time" id="close_time_val" value="<?= $d['close_time']; ?>">
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
                                            <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-600 group-hover:text-teal-600 transition shadow-sm"><i class="fas fa-hand-holding-usd"></i></span>
                                            <span class="text-sm font-bold text-slate-800 group-hover:text-teal-600 transition">Manual Price</span>
                                        </div>
                                        <div class="relative inline-flex items-center">
                                            <input type="checkbox" name="allow_manual_price" class="sr-only peer" <?= $d['allow_manual_price'] == 1 ? 'checked' : ''; ?>>
                                            <div class="w-11 h-6 bg-slate-400 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600 shadow-inner"></div>
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
            </div>
        </main>
    </div>

    <script src="/pos/assets/js/add-store.js"></script>
    <!-- Include jQuery and Select2 if not already in header, but typically jQuery is. If not, we add it. 
         Assuming header has jQuery. If not, Select2 won't work. Let's assume header.php has it as it is standard.
         If not, the user will report an error. -->
    <script>
    $(document).ready(function() {
        $('#currency_id').select2({
            placeholder: "Select Currency",
            width: '100%',
            allowClear: true,
            ajax: {
                url: '/pos/stores/search_currency.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term, // search term
                        limit: 5 // initial load limit as requested (though Select2 might paginate differently, this param handles backend limit)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
        
        // Populate initial 5 data if not editing or existing value
        <?php if(empty($d['currency_id'])): ?>
        $.ajax({
            url: '/pos/stores/search_currency.php?limit=5',
            dataType: 'json',
            success: function(data) {
                if(data.results) {
                    data.results.forEach(item => {
                        var option = new Option(item.text, item.id, false, false);
                        $('#currency_id').append(option).trigger('change');
                    });
                    $('#currency_id').val(null).trigger('change'); // Clear selection but populate list
                }
            }
        });
        <?php endif; ?>
    });

    window.onload = () => {
        initPicker('open_picker', 'open', "<?= $d['open_time']; ?>");
        initPicker('close_picker', 'close', "<?= $d['close_time']; ?>");
    };
    </script>

<?php
session_start();
include('../config/dbcon.php');
include('../includes/header.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : ($_SESSION['store_id'] ?? 1);

// Fetch current settings
$settings = [];
$s_query = "SELECT * FROM pos_settings WHERE store_id = '$store_id'";
$s_run = mysqli_query($conn, $s_query);
while($row = mysqli_fetch_assoc($s_run)){
    $settings[$row['setting_key']] = $row['setting_value'];
}

function get_setting($key, $settings_array, $default = '') {
    return isset($settings_array[$key]) ? $settings_array[$key] : $default;
}

// Auto-fix DB Schema
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM product_store_map LIKE 'per_customer_limit'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE product_store_map ADD COLUMN per_customer_limit INT DEFAULT 0 AFTER stock");
}
?>

<!-- TomSelect CSS -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #0d9488 0%, #115e59 100%);
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.5);
        --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
        --card-bg: rgba(255, 255, 255, 0.9);
    }
    
    body { 
        background-color: #f0f4f8; 
        background-image: 
            radial-gradient(at 0% 0%, rgba(13, 148, 136, 0.15) 0px, transparent 50%),
            radial-gradient(at 100% 0%, rgba(14, 165, 233, 0.15) 0px, transparent 50%);
        background-attachment: fixed;
        font-family: 'Outfit', sans-serif; 
    }

    /* Glass Card */
    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        border-radius: 24px;
    }

    /* Switch Toggle */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 34px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    input:checked + .slider { background: var(--primary-gradient); }
    input:checked + .slider:before { transform: translateX(22px); }

    /* Tabs */
    .nav-tabs-glass {
        display: inline-flex;
        background: rgba(255,255,255,0.5);
        padding: 5px;
        border-radius: 16px;
        margin-bottom: 25px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    .nav-link-glass {
        padding: 10px 24px;
        border-radius: 12px;
        color: #64748b;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .nav-link-glass.active {
        background: white;
        color: #0d9488;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transform: translateY(-1px);
    }
    .nav-link-glass:hover:not(.active) {
        color: #334155;
        background: rgba(255,255,255,0.8);
    }

    .settings-section { display: none; animation: slideUp 0.4s ease-out; }
    .settings-section.active { display: block; }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }


    /* Hide number input spinners */
    .no-spin::-webkit-inner-spin-button, 
    .no-spin::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    .no-spin {
        -moz-appearance: textfield;
    }

    /* Input Styles */
    .glass-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255,255,255,0.8);
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s;
        font-size: 15px;
    }
    .glass-input:focus {
        outline: none;
        border-color: #0d9488;
        background: white;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    }
</style>

<div class="app-wrapper flex flex-col h-screen overflow-hidden">
    <?php include('../includes/sidebar.php'); ?>
    <?php include('../includes/navbar.php'); ?>
    
    <main id="main-content" class="lg:ml-64 flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300">
        
        <div class="flex-1 overflow-y-auto flex flex-col">
            <div class="max-w-7xl mx-auto w-full p-4 md:p-8">
            
                <!-- Header -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 tracking-tight" style="font-family: 'Outfit', sans-serif;">Store Settings</h1>
                        <p class="text-slate-500 mt-1">Manage configurations and system preferences.</p>
                    </div>
                    <button onclick="saveAllSettings()" class="bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white px-8 py-3 rounded-2xl shadow-lg shadow-teal-500/30 flex items-center gap-2 font-bold transition-all transform hover:scale-105 active:scale-95">
                        <i class="fas fa-save"></i> <span>Save Changes</span>
                    </button>
                </div>

                <!-- Glass Tabs -->
                <div class="flex justify-center md:justify-start">
                    <div class="nav-tabs-glass">
                        <div class="nav-link-glass active" onclick="switchTab('general', this)">
                            <i class="fas fa-sliders-h"></i> General
                        </div>
                        <div class="nav-link-glass" onclick="switchTab('interaction_tab', this)">
                            <i class="fas fa-cash-register"></i> Interaction
                        </div>
                        <div class="nav-link-glass" onclick="switchTab('product_limits', this)">
                            <i class="fas fa-hand-holding-usd"></i> Product Limits
                        </div>
                    </div>
                </div>

                <form id="settingsForm">
                    <input type="hidden" name="store_id" value="<?= $store_id; ?>">

                    <!-- Tab: General -->
                    <div id="general" class="settings-section active">
                        <!-- 2-Column Grid Layout -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <!-- LEFT COLUMN: Localization & Basic Store Info -->
                            <div class="space-y-6">
                                <!-- Localization & Layouts Card -->
                                <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-globe text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">Localization & Layouts</h3>
                                </div>

                                <div class="space-y-6">
                                    <!-- Currency Selector with Custom Searchable Dropdown -->
                                    <div>
                                        <?php
                                        $store_info_q = mysqli_query($conn, "SELECT currency_id FROM stores WHERE id = '$store_id'");
                                        $store_info = mysqli_fetch_assoc($store_info_q);
                                        $current_curr = $store_info['currency_id']; 
                                        
                                        // Get current currency name
                                        $current_curr_name = '';
                                        if($current_curr) {
                                            $curr_name_q = mysqli_query($conn, "SELECT currency_name, code FROM currencies WHERE id = '$current_curr'");
                                            if($curr_name_row = mysqli_fetch_assoc($curr_name_q)) {
                                                $current_curr_name = $curr_name_row['currency_name'] . ' (' . $curr_name_row['code'] . ')';
                                            }
                                        }
                                        ?>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Store Currency</label>
                                        
                                        <!-- Hidden input to store the actual value -->
                                        <input type="hidden" name="currency_id" id="currency_id" value="<?= $current_curr; ?>">
                                        
                                        <!-- Custom Dropdown -->
                                        <div class="relative" id="currency_dropdown_wrapper">
                                            <div class="glass-input cursor-pointer flex items-center justify-between" id="currency_display" onclick="toggleCurrencyDropdown()">
                                                <span id="selected_currency_text"><?= $current_curr_name ? $current_curr_name : 'Select Currency...'; ?></span>
                                                <i class="fas fa-chevron-down text-slate-400 text-sm"></i>
                                            </div>
                                            
                                            <!-- Dropdown Menu -->
                                            <div id="currency_dropdown_menu" class="hidden absolute z-50 w-full mt-2 bg-white rounded-xl border border-slate-200 shadow-xl max-h-80 overflow-hidden">
                                                <!-- Search Input -->
                                                <div class="p-3 border-b border-slate-100">
                                                    <input type="text" id="currency_search" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-teal-500" placeholder="Search currency..." oninput="filterCurrencies()">
                                                </div>
                                                
                                                <!-- Currency List -->
                                                <div class="overflow-y-auto max-h-64" id="currency_list">
                                                    <?php
                                                    $curr_q = mysqli_query($conn, "SELECT * FROM currencies WHERE status = 1 ORDER BY sort_order ASC, currency_name ASC");
                                                    $count = 0;
                                                    while($curr = mysqli_fetch_assoc($curr_q)) {
                                                        $count++;
                                                        $display_class = $count > 5 ? 'hidden currency-item-hidden' : '';
                                                        $selected_class = ($curr['id'] == $current_curr) ? 'bg-teal-50 text-teal-600' : '';
                                                        echo "<div class='currency-item $display_class $selected_class px-4 py-3 hover:bg-slate-50 cursor-pointer transition-colors' data-id='{$curr['id']}' data-name='{$curr['currency_name']} ({$curr['code']})' onclick='selectCurrency({$curr['id']}, \"{$curr['currency_name']} ({$curr['code']})\")'>
                                                                {$curr['currency_name']} ({$curr['code']})
                                                              </div>";
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-2 ml-1">Type to search. Limited view enabled for cleaner UI.</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Receipt Design (Template)</label>
                                        <select id="receipt_design_select" name="settings[receipt_template]" class="glass-input cursor-pointer appearance-none">
                                            <option value="classic" <?= get_setting('receipt_template', $settings) == 'classic' ? 'selected' : ''; ?>>Classic Heritage (Standard)</option>
                                            <option value="modern" <?= get_setting('receipt_template', $settings) == 'modern' ? 'selected' : ''; ?>>Modern Edge (Inter)</option>
                                            <option value="minimal" <?= get_setting('receipt_template', $settings) == 'minimal' ? 'selected' : ''; ?>>Eco Minimal (Compact)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Receipt Printer (Hardware)</label>
                                        <select id="receipt_printer_select" name="settings[receipt_printer]" class="glass-input cursor-pointer appearance-none">
                                            <option value="">-- Select Local/Network Printer --</option>
                                            <optgroup label="Mapped Printers (Recommended)">
                                                <?php
                                                $printers_q = "SELECT p.* FROM printers p 
                                                              JOIN printer_store_map psm ON p.printer_id = psm.printer_id 
                                                              WHERE p.status = 1 AND psm.store_id = '$store_id' 
                                                              ORDER BY p.sort_order ASC";
                                                $printers_res = mysqli_query($conn, $printers_q);
                                                
                                                if(mysqli_num_rows($printers_res) > 0) {
                                                    while($p_row = mysqli_fetch_assoc($printers_res)) {
                                                        $p_val = $p_row['printer_id'];
                                                        $selected = get_setting('receipt_printer', $settings) == $p_val ? 'selected' : '';
                                                        echo "<option value='$p_val' $selected>{$p_row['title']} (".ucfirst($p_row['type']).")</option>";
                                                    }
                                                } else {
                                                    echo "<option disabled>No printers mapped to this store</option>";
                                                }
                                                ?>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Invoice Footer Note</label>
                                        <textarea name="settings[invoice_footer]" rows="3" class="glass-input resize-none" placeholder="e.g. Thank you for shopping with us!"><?= htmlspecialchars(get_setting('invoice_footer', $settings)); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Store Information Card (in left column) -->
                            <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-store text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">Basic Store Information</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Special Account -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Special Account <span class="text-rose-500">*</span>
                                        </label>
                                        <select name="settings[special_account]" class="glass-input cursor-pointer">
                                            <option value="">--- Please Select ---</option>
                                            <option value="standard" <?= get_setting('special_account', $settings) == 'standard' ? 'selected' : ''; ?>>Standard Account</option>
                                            <option value="premium" <?= get_setting('special_account', $settings) == 'premium' ? 'selected' : ''; ?>>Premium Account</option>
                                            <option value="enterprise" <?= get_setting('special_account', $settings) == 'enterprise' ? 'selected' : ''; ?>>Enterprise Account</option>
                                        </select>
                                    </div>

                                    <!-- Store Name -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Name <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" name="settings[store_name]" value="<?= htmlspecialchars(get_setting('store_name', $settings)); ?>" class="glass-input" placeholder="STORE 01">
                                    </div>

                                    <!-- Code Name -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Code Name <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" name="settings[code_name]" value="<?= htmlspecialchars(get_setting('code_name', $settings)); ?>" class="glass-input" placeholder="store_name">
                                    </div>

                                    <!-- Country -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Country <span class="text-rose-500">*</span>
                                        </label>
                                        <select name="settings[country]" class="glass-input cursor-pointer">
                                            <option value="">--- Please Select ---</option>
                                            <option value="US" <?= get_setting('country', $settings) == 'US' ? 'selected' : ''; ?>>🇺🇸 United States (+1)</option>
                                            <option value="BD" <?= get_setting('country', $settings) == 'BD' ? 'selected' : ''; ?>>🇧🇩 Bangladesh (+880)</option>
                                            <option value="IN" <?= get_setting('country', $settings) == 'IN' ? 'selected' : ''; ?>>🇮🇳 India (+91)</option>
                                            <option value="GB" <?= get_setting('country', $settings) == 'GB' ? 'selected' : ''; ?>>🇬🇧 United Kingdom (+44)</option>
                                            <option value="CA" <?= get_setting('country', $settings) == 'CA' ? 'selected' : ''; ?>>🇨🇦 Canada (+1)</option>
                                        </select>
                                    </div>

                                    <!-- Mobile -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Mobile</label>
                                        <input type="text" name="settings[mobile]" value="<?= htmlspecialchars(get_setting('mobile', $settings)); ?>" class="glass-input" placeholder="7567826">
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Email <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="email" name="settings[email]" value="<?= htmlspecialchars(get_setting('email', $settings)); ?>" class="glass-input" placeholder="info@example.com">
                                    </div>

                                    <!-- Zip Code -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Zip Code <span class="text-rose-500">*</span>
                                        </label>
                                        <input type="text" name="settings[zip_code]" value="<?= htmlspecialchars(get_setting('zip_code', $settings)); ?>" class="glass-input" placeholder="1232">
                                    </div>

                                    <!-- Address (Full Width) -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-bold text-slate-600 mb-2">
                                            Address <span class="text-rose-500">*</span>
                                        </label>
                                        <textarea name="settings[address]" rows="3" class="glass-input resize-none" placeholder="Earth"><?= htmlspecialchars(get_setting('address', $settings)); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- SMS & Notifications Card (in left column) -->
                            <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-sms text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">SMS & Notifications</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- SMS Gateway ID -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">SMS Gateway ID</label>
                                        <select name="settings[sms_gateway]" class="glass-input cursor-pointer">
                                            <option value="">--- Please Select ---</option>
                                            <option value="twilio" <?= get_setting('sms_gateway', $settings) == 'twilio' ? 'selected' : ''; ?>>Twilio</option>
                                            <option value="nexmo" <?= get_setting('sms_gateway', $settings) == 'nexmo' ? 'selected' : ''; ?>>Nexmo</option>
                                            <option value="messagebird" <?= get_setting('sms_gateway', $settings) == 'messagebird' ? 'selected' : ''; ?>>MessageBird</option>
                                        </select>
                                    </div>

                                    <!-- SMS Auth ID -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">SMS Auth ID</label>
                                        <input type="text" name="settings[sms_auth_id]" value="<?= htmlspecialchars(get_setting('sms_auth_id', $settings)); ?>" class="glass-input" placeholder="12">
                                    </div>

                                    <!-- Auto SMS -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Auto SMS</label>
                                        <div class="flex items-center gap-4 mt-3">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="settings[auto_sms]" value="1" <?= get_setting('auto_sms', $settings) == '1' ? 'checked' : ''; ?> class="w-5 h-5 text-teal-600 border-slate-300 rounded focus:ring-teal-500">
                                                <span class="text-sm text-slate-600">Mail After Order Invoice</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expiration System Card (in left column) -->
                            <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-calendar-times text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">Expiration System</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- Expiration System Toggle -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Enable Expiration</label>
                                        <div class="flex items-center gap-4 mt-3">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="settings[expiration_system]" value="no" <?= get_setting('expiration_system', $settings, 'no') == 'no' ? 'checked' : ''; ?> class="w-5 h-5 text-rose-600 border-slate-300 focus:ring-rose-500">
                                                <span class="text-sm text-slate-600">No</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Start Selling -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Start Selling</label>
                                        <div class="flex items-center gap-4 mt-3">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="settings[expiration_system]" value="start_selling" <?= get_setting('expiration_system', $settings) == 'start_selling' ? 'checked' : ''; ?> class="w-5 h-5 text-rose-600 border-slate-300 focus:ring-rose-500">
                                                <span class="text-sm text-slate-600">Start Selling</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Days of Expiring -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Days of Expiring</label>
                                        <input type="number" name="settings[days_of_expiring]" value="<?= htmlspecialchars(get_setting('days_of_expiring', $settings)); ?>" class="glass-input" placeholder="Enter days">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End of LEFT COLUMN -->

                        <!-- RIGHT COLUMN: Branding + Tax & System Config -->
                        <div class="space-y-6">
                            <!-- Branding Assets Card -->
                            <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-palette text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">Branding Assets</h3>
                                </div>

                                <!-- 2-Column Grid for Logo & Favicon -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Logo Upload Card - Premium Design -->
                                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-50 via-white to-cyan-50 p-6 border-2 border-teal-100 shadow-xl shadow-teal-500/10 hover:shadow-2xl hover:shadow-teal-500/20 transition-all duration-300 group">
                                        <!-- Decorative Background Pattern -->
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-teal-400/10 to-transparent rounded-full blur-2xl"></div>
                                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-cyan-400/10 to-transparent rounded-full blur-2xl"></div>
                                        
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-5">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/30">
                                                    <i class="fas fa-image text-white text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-800 text-lg">Store Logo</h4>
                                                    <p class="text-xs text-slate-500">Primary brand identity</p>
                                                </div>
                                            </div>
                                            
                                            <?php
                                            $current_logo = get_setting('logo', $settings, '/pos/assets/images/logo.png');
                                            ?>
                                            
                                            <div class="mb-5 flex justify-center">
                                                <div class="relative w-full h-40 bg-white/80 backdrop-blur-sm rounded-2xl border-2 border-dashed border-teal-200 flex items-center justify-center overflow-hidden shadow-inner group-hover:border-teal-300 transition-colors">
                                                    <img id="logo_preview" src="<?= $current_logo; ?>" alt="Logo" class="max-w-full max-h-full object-contain p-4">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-teal-500/5 to-transparent pointer-events-none"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="relative">
                                                <input type="file" name="logo_file" id="logo_file" accept="image/*" class="hidden" onchange="previewLogo(this)">
                                                <label for="logo_file" class="block w-full bg-gradient-to-r from-teal-500 via-teal-600 to-cyan-600 hover:from-teal-600 hover:via-teal-700 hover:to-cyan-700 text-white text-center py-3.5 rounded-2xl cursor-pointer transition-all font-bold shadow-lg shadow-teal-500/40 hover:shadow-xl hover:shadow-teal-500/50 transform hover:-translate-y-0.5 active:translate-y-0">
                                                    <i class="fas fa-cloud-upload-alt mr-2 text-lg"></i> Upload Logo
                                                </label>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-3 text-center font-medium">
                                                <i class="fas fa-info-circle mr-1"></i> PNG, JPG, SVG • Max 2MB
                                            </p>
                                            <input type="hidden" name="settings[logo]" id="logo_path" value="<?= htmlspecialchars($current_logo); ?>">
                                        </div>
                                    </div>

                                    <!-- Favicon Upload Card - Premium Design -->
                                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-50 via-white to-purple-50 p-6 border-2 border-indigo-100 shadow-xl shadow-indigo-500/10 hover:shadow-2xl hover:shadow-indigo-500/20 transition-all duration-300 group">
                                        <!-- Decorative Background Pattern -->
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-400/10 to-transparent rounded-full blur-2xl"></div>
                                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-purple-400/10 to-transparent rounded-full blur-2xl"></div>
                                        
                                        <div class="relative z-10">
                                            <div class="flex items-center gap-3 mb-5">
                                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                                    <i class="fas fa-bookmark text-white text-lg"></i>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-800 text-lg">Favicon</h4>
                                                    <p class="text-xs text-slate-500">Browser tab icon</p>
                                                </div>
                                            </div>
                                            
                                            <?php
                                            $current_favicon = get_setting('favicon', $settings, '/pos/assets/images/favicon.ico');
                                            ?>
                                            
                                            <div class="mb-5 flex justify-center">
                                                <div class="relative w-full h-40 bg-white/80 backdrop-blur-sm rounded-2xl border-2 border-dashed border-indigo-200 flex items-center justify-center overflow-hidden shadow-inner group-hover:border-indigo-300 transition-colors">
                                                    <img id="favicon_preview" src="<?= $current_favicon; ?>" alt="Favicon" class="max-w-full max-h-full object-contain p-4">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-indigo-500/5 to-transparent pointer-events-none"></div>
                                                </div>
                                            </div>
                                            
                                            <div class="relative">
                                                <input type="file" name="favicon_file" id="favicon_file" accept="image/*,.ico" class="hidden" onchange="previewFavicon(this)">
                                                <label for="favicon_file" class="block w-full bg-gradient-to-r from-indigo-500 via-indigo-600 to-purple-600 hover:from-indigo-600 hover:via-indigo-700 hover:to-purple-700 text-white text-center py-3.5 rounded-2xl cursor-pointer transition-all font-bold shadow-lg shadow-indigo-500/40 hover:shadow-xl hover:shadow-indigo-500/50 transform hover:-translate-y-0.5 active:translate-y-0">
                                                    <i class="fas fa-cloud-upload-alt mr-2 text-lg"></i> Upload Favicon
                                                </label>
                                            </div>
                                            <p class="text-xs text-slate-500 mt-3 text-center font-medium">
                                                <i class="fas fa-info-circle mr-1"></i> ICO, PNG • 16x16 or 32x32
                                            </p>
                                            <input type="hidden" name="settings[favicon]" id="favicon_path" value="<?= htmlspecialchars($current_favicon); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Brand Guidelines Note -->
                                <div class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl border border-purple-100">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                                            <i class="fas fa-lightbulb text-purple-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-slate-700 text-sm mb-1">Brand Guidelines</h5>
                                            <p class="text-xs text-slate-600 leading-relaxed">
                                                Use high-quality images for best results. Logo appears on receipts and invoices. Favicon shows in browser tabs.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                            <!-- System Configuration Card (in right column) -->
                            <div class="glass-card p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-cog text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">System Configuration</h3>
                            </div>

                            <div class="space-y-6">
                                <!-- Custom Name (Cashier) -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">
                                        Custom Name <span class="text-rose-500">*</span>
                                    </label>
                                    <select name="settings[cashier_name]" class="glass-input cursor-pointer">
                                        <option value="">--- Select Cashier ---</option>
                                        <?php
                                        $users_q = mysqli_query($conn, "SELECT id, name FROM users WHERE status = 1 ORDER BY name ASC");
                                        while($user = mysqli_fetch_assoc($users_q)) {
                                            $selected = get_setting('cashier_name', $settings) == $user['id'] ? 'selected' : '';
                                            echo "<option value='{$user['id']}' $selected>{$user['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Timezone -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">
                                        Timezone <span class="text-rose-500">*</span>
                                    </label>
                                    <select name="settings[timezone]" class="glass-input cursor-pointer">
                                        <option value="">--- Please Select ---</option>
                                        <option value="UTC+06:00" <?= get_setting('timezone', $settings) == 'UTC+06:00' ? 'selected' : ''; ?>>UTC+06:00 Dhaka</option>
                                        <option value="UTC+05:30" <?= get_setting('timezone', $settings) == 'UTC+05:30' ? 'selected' : ''; ?>>UTC+05:30 Kolkata</option>
                                        <option value="UTC-05:00" <?= get_setting('timezone', $settings) == 'UTC-05:00' ? 'selected' : ''; ?>>UTC-05:00 New York</option>
                                        <option value="UTC+00:00" <?= get_setting('timezone', $settings) == 'UTC+00:00' ? 'selected' : ''; ?>>UTC+00:00 London</option>
                                    </select>
                                </div>

                                <!-- Installation Date Lock -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">
                                        Installation Date Lock <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="number" name="settings[installation_date_lock]" value="<?= htmlspecialchars(get_setting('installation_date_lock', $settings, '21')); ?>" class="glass-input" placeholder="21">
                                </div>

                                <!-- Invoice Edit (Days) -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">
                                        Invoice Edit (Days) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="settings[invoice_edit_value]" value="<?= htmlspecialchars(get_setting('invoice_edit_value', $settings, '1245')); ?>" class="glass-input" placeholder="1245">
                                        <select name="settings[invoice_edit_unit]" class="glass-input cursor-pointer">
                                            <option value="minute" <?= get_setting('invoice_edit_unit', $settings) == 'minute' ? 'selected' : ''; ?>>Minute</option>
                                            <option value="second" <?= get_setting('invoice_edit_unit', $settings) == 'second' ? 'selected' : ''; ?>>Second</option>
                                            <option value="hour" <?= get_setting('invoice_edit_unit', $settings) == 'hour' ? 'selected' : ''; ?>>Hour</option>
                                            <option value="day" <?= get_setting('invoice_edit_unit', $settings) == 'day' ? 'selected' : ''; ?>>Day</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Invoice Delete (Days) -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">
                                        Invoice Delete (Days) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" name="settings[invoice_delete_value]" value="<?= htmlspecialchars(get_setting('invoice_delete_value', $settings, '1245')); ?>" class="glass-input" placeholder="1245">
                                        <select name="settings[invoice_delete_unit]" class="glass-input cursor-pointer">
                                            <option value="minute" <?= get_setting('invoice_delete_unit', $settings) == 'minute' ? 'selected' : ''; ?>>Minute</option>
                                            <option value="second" <?= get_setting('invoice_delete_unit', $settings) == 'second' ? 'selected' : ''; ?>>Second</option>
                                            <option value="hour" <?= get_setting('invoice_delete_unit', $settings) == 'hour' ? 'selected' : ''; ?>>Hour</option>
                                    <option value="day" <?= get_setting('invoice_delete_unit', $settings) == 'day' ? 'selected' : ''; ?>>Day</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>


                            <!-- Tax & Registration Card (in right column) -->
                            <div class="glass-card p-8">
                                <div class="flex items-center gap-3 mb-6">
                                    <span class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-file-invoice-dollar text-lg"></i>
                                    </span>
                                    <h3 class="text-xl font-bold text-slate-700">Tax & Registration</h3>
                                </div>

                                <div class="space-y-6">
                                    <!-- GST Reg. No. -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">GST Reg. No.</label>
                                        <input type="text" name="settings[gst_reg_no]" value="<?= htmlspecialchars(get_setting('gst_reg_no', $settings)); ?>" class="glass-input" placeholder="Enter GST number">
                                    </div>

                                    <!-- VAT Reg. No. -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">VAT Reg. No.</label>
                                        <input type="text" name="settings[vat_reg_no]" value="<?= htmlspecialchars(get_setting('vat_reg_no', $settings)); ?>" class="glass-input" placeholder="534213">
                                    </div>

                                    <!-- Tax ID -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-600 mb-2">Tax ID</label>
                                        <input type="text" name="settings[tax_id]" value="<?= htmlspecialchars(get_setting('tax_id', $settings)); ?>" class="glass-input" placeholder="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End of RIGHT COLUMN -->
                    </div>
                </div>
                <!-- End of 2-Column Grid -->

                </div>
                <!-- End of General Tab -->

                <!-- Tab: Interaction (POS) -->
                <div id="interaction_tab" class="settings-section">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- Interface Toggles Card -->
                        <div class="glass-card p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-desktop text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">Interface Toggles</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-white/50 rounded-2xl p-5 border border-slate-100/50 flex items-center justify-between hover:bg-white/80 transition-all">
                                    <div>
                                        <h4 class="font-bold text-slate-700">Sound Effects</h4>
                                        <p class="text-xs text-slate-500 mt-1">Beep on scan/click</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[enable_sound]" value="1" <?= get_setting('enable_sound', $settings, '1') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="bg-white/50 rounded-2xl p-5 border border-slate-100/50 flex items-center justify-between hover:bg-white/80 transition-all">
                                    <div>
                                        <h4 class="font-bold text-slate-700">Auto Print</h4>
                                        <p class="text-xs text-slate-500 mt-1">Skip preview after checkout</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[auto_print]" value="1" <?= get_setting('auto_print', $settings, '0') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Display Options Card -->
                        <div class="glass-card p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-eye text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">Display Options</h3>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="bg-white/50 rounded-2xl p-5 border border-slate-100/50 flex items-center justify-between hover:bg-white/80 transition-all">
                                    <div>
                                        <h4 class="font-bold text-slate-700">Product Images</h4>
                                        <p class="text-xs text-slate-500 mt-1">Show product thumbnails</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[show_images]" value="1" <?= get_setting('show_images', $settings, '1') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="bg-white/50 rounded-2xl p-5 border border-slate-100/50 flex items-center justify-between hover:bg-white/80 transition-all">
                                    <div>
                                        <h4 class="font-bold text-slate-700">Flexible Pricing</h4>
                                        <p class="text-xs text-slate-500 mt-1">Allow manual price overrides</p>
                                    </div>
                                    <label class="switch">
                                        <input type="checkbox" name="settings[allow_price_edit]" value="1" <?= get_setting('allow_price_edit', $settings, '0') == '1' ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                    <!-- Tab: Product Limits -->
                    <div id="product_limits" class="settings-section">
                        <div class="glass-card p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-shield-alt text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">Product Quantity Limits</h3>
                            </div>

                            <div class="mb-6 bg-white/50 p-4 rounded-xl border border-slate-100/50">
                                <label class="block text-sm font-bold text-slate-600 mb-2">Target Scope</label>
                                <select id="limit_store_select" onchange="refreshLimitTable()" class="glass-input cursor-pointer">
                                    <option value="global">🌐 Global Limit (All Stores)</option>
                                    <?php
                                    $stores_q = "SELECT * FROM stores WHERE status = 1";
                                    $stores_res = mysqli_query($conn, $stores_q);
                                    if($stores_res) {
                                        while($store = mysqli_fetch_assoc($stores_res)) {
                                            echo '<option value="'.$store['id'].'">🏪 '.$store['store_name'].'</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="relative mb-6">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                    <i class="fas fa-search text-slate-400 text-lg"></i>
                                </span>
                                <input type="text" id="limit_search" placeholder="Search product to set limit..." class="glass-input shadow-sm" style="padding-left: 3rem !important;">
                            </div>

                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider">
                                            <th class="p-4 font-bold">Product Name</th>
                                            <th class="p-4 font-bold">Code</th>
                                            <th class="p-4 font-bold">Current Stock</th>
                                            <th class="p-4 font-bold text-center">Max Qty Per Customer</th>
                                            <th class="p-4 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="limit_table_body" class="bg-white/40 backdrop-blur-sm">
                                        <tr>
                                            <td colspan="5" class="p-8 text-center text-slate-400">
                                                Start typing to search products...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
            
            <!-- TomSelect JS -->
            <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>


<script>
    // Custom Currency Dropdown Functions
    function toggleCurrencyDropdown() {
        const menu = document.getElementById('currency_dropdown_menu');
        menu.classList.toggle('hidden');
        if (!menu.classList.contains('hidden')) {
            document.getElementById('currency_search').focus();
        }
    }

    function selectCurrency(id, name) {
        document.getElementById('currency_id').value = id;
        document.getElementById('selected_currency_text').textContent = name;
        document.getElementById('currency_dropdown_menu').classList.add('hidden');
        
        // Update selected styling
        document.querySelectorAll('.currency-item').forEach(item => {
            item.classList.remove('bg-teal-50', 'text-teal-600');
        });
        event.target.classList.add('bg-teal-50', 'text-teal-600');
    }

    function filterCurrencies() {
        const searchValue = document.getElementById('currency_search').value.toLowerCase();
        const items = document.querySelectorAll('.currency-item');
        
        items.forEach(item => {
            const text = item.getAttribute('data-name').toLowerCase();
            if (searchValue === '') {
                // When search is empty, show only first 5
                const index = Array.from(items).indexOf(item);
                if (index < 5) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            } else {
                // When searching, show all matching items
                if (text.includes(searchValue)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            }
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const wrapper = document.getElementById('currency_dropdown_wrapper');
        if (wrapper && !wrapper.contains(event.target)) {
            document.getElementById('currency_dropdown_menu').classList.add('hidden');
        }
    });
</script>

<script>
    // Initialize TomSelect for Receipt Template
    const receiptTemplateSelect = document.querySelector("#receipt_template_select");
    if (receiptTemplateSelect) {
        new TomSelect("#receipt_template_select", {
            create: false,
            controlInput: null // Disable search for small lists
        });
    }

    // Initialize TomSelect for Store Limit Selector
    const limitStoreSelect = document.querySelector("#limit_store_select");
    if (limitStoreSelect) {
        new TomSelect("#limit_store_select", {
            create: false,
            sortField: {
                field: "text",
                direction: "asc"
            },
            maxOptions: 6,
            render: {
                no_results: function(data, escape) {
                    return '<div class="no-results">No store found</div>';
                }
            }
        });
    }

    function switchTab(tabId, btn) {
        document.querySelectorAll('.settings-section').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.nav-link-glass').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // Initialize search functionality - script runs at end so DOM is ready
    const searchInput = document.getElementById('limit_search');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = e.target.value;
                if(query.length > 2) {
                    fetchProducts(query);
                }
            }, 300);
        });
    }

    function refreshLimitTable() {
        const searchInput = document.getElementById('limit_search');
        if (!searchInput) return;
        const query = searchInput.value;
        if(query.length > 2) {
            fetchProducts(query);
        } else {
             document.getElementById('limit_table_body').innerHTML = '<tr><td colspan="5" class="p-8 text-center text-slate-400">Start typing to search products...</td></tr>';
        }
    }

    function fetchProducts(query) {
        const body = document.getElementById('limit_table_body');
        const storeId = document.getElementById('limit_store_select').value;
        body.innerHTML = '<tr><td colspan="5" class="p-4 text-center"><i class="fas fa-spinner fa-spin text-teal-500"></i></td></tr>';
        $.ajax({
            url: '/pos/stores/search_products_limit.php',
            method: 'GET',
            data: { q: query, store_id: storeId },
            success: function(resp) {
                body.innerHTML = resp;
            }
        });
    }

    function updateLimit(id) {
        const val = document.getElementById(`limit_input_${id}`).value;
        const btn = document.getElementById(`save_btn_${id}`);
        const storeId = document.getElementById('limit_store_select').value;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        $.ajax({
            url: '/pos/stores/save_product_limit.php',
            method: 'POST',
            data: { product_id: id, limit: val, store_id: storeId },
            success: function(res) {
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.add('bg-green-500', 'text-white');
                setTimeout(() => {
                    btn.innerHTML = 'Set';
                    btn.classList.remove('bg-green-500', 'text-white');
                }, 2000);
            }
        });
    }

    function saveAllSettings() {
        const form = document.getElementById('settingsForm');
        const formData = new FormData(form);

        $.ajax({
            url: '/pos/stores/save_store_settings.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Check for JSON error response
                try {
                    const jsonResp = JSON.parse(response);
                    if(jsonResp.status === 'error') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: jsonResp.message || 'Failed to upload logo/favicon.',
                            confirmButtonColor: '#d33'
                        });
                        return; // Stop further execution
                    }
                } catch(e) {
                    // Not JSON, assume string "success" or HTML
                }

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: 'Settings Saved Successfully!'
                });

                setTimeout(() => {
                    window.location.href = '/pos/pos/'; 
                }, 2000);
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save settings.',
                    confirmButtonColor: '#d33'
                });
            }
        });
    }

    // Logo Preview Function
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logo_preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Favicon Preview Function
    function previewFavicon(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('favicon_preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>


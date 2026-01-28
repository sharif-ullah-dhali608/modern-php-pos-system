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

    /* TomSelect Customization for Glass Theme */
    .ts-control {
        background: rgba(255,255,255,0.8) !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 12px 16px !important;
        box-shadow: none !important;
        font-size: 15px;
    }
    .ts-control.focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1) !important;
    }
    .ts-dropdown {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 5px;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
    }
    .ts-wrapper .ts-dropdown-content {
        max-height: 220px !important;
        overflow-y: auto !important;
        scroll-behavior: smooth;
    }
    .ts-dropdown .option {
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 2px;
    }
    .ts-dropdown .active {
        background: #f0fdfa !important;
        color: #0d9488 !important;
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
        
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
            
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
                        <div class="nav-link-glass" onclick="switchTab('pos', this)">
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
                        <div class="glass-card p-8 min-h-[400px]">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-globe text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">Localization & Layouts</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Currency Selector with TomSelect -->
                                <div>
                                    <?php
                                    $store_info_q = mysqli_query($conn, "SELECT currency_id FROM stores WHERE id = '$store_id'");
                                    $store_info = mysqli_fetch_assoc($store_info_q);
                                    $current_curr = $store_info['currency_id']; 
                                    ?>
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Store Currency</label>
                                    <select id="currency_select" name="currency_id" placeholder="Search currency..." autocomplete="off">
                                        <option value="">Select Currency...</option>
                                        <?php
                                        $curr_q = mysqli_query($conn, "SELECT * FROM currencies WHERE status = 1 ORDER BY sort_order ASC, currency_name ASC");
                                        while($curr = mysqli_fetch_assoc($curr_q)) {
                                            $selected = ($curr['id'] == $current_curr) ? 'selected' : '';
                                            echo "<option value='{$curr['id']}' $selected>{$curr['currency_name']} ({$curr['code']})</option>";
                                        }
                                        ?>
                                    </select>
                                    <p class="text-xs text-slate-400 mt-2 ml-1">Type to search. Limited view enabled for cleaner UI.</p>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-slate-600 mb-2">Invoice Footer Note</label>
                                    <textarea name="settings[invoice_footer]" rows="3" class="glass-input resize-none" placeholder="e.g. Thank you for shopping with us!"><?= htmlspecialchars(get_setting('invoice_footer', $settings)); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: POS Interface -->
                    <div id="pos" class="settings-section">
                        <div class="glass-card p-8">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-desktop text-lg"></i>
                                </span>
                                <h3 class="text-xl font-bold text-slate-700">Interface Toggles</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Option Card -->
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
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<!-- TomSelect JS -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

<script>
    // Initialize TomSelect for Currency
    new TomSelect("#currency_select", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        },
        maxOptions: 6, // Limit visible options to 6. Others appear only on search.
        render: {
            no_results: function(data, escape) {
                return '<div class="no-results">No currency found</div>';
            }
        }
    });

    // Initialize TomSelect for Receipt Template
    new TomSelect("#receipt_template_select", {
        create: false,
        controlInput: null // Disable search for small lists
    });

    // Initialize TomSelect for Store Limit Selector
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

    function switchTab(tabId, btn) {
        document.querySelectorAll('.settings-section').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.nav-link-glass').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // Reuse existing logic for limits...
    const searchInput = document.getElementById('limit_search');
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

    function refreshLimitTable() {
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
</script>


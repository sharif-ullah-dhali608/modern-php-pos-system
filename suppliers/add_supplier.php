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
$btn_name = "save_supplier_btn";
$btn_text = "Save Supplier";
$page_title = "Add Supplier";

// Default Data Structure
$d = [
    'id' => '', 'name' => '', 'code_name' => '', 'trade_license_num' => '',
    'bank_account_num' => '', 'email' => '', 'mobile' => '', 'address' => '',
    'city' => '', 'state' => '', 'country' => 'Bangladesh', 
    'status' => '1', 'sort_order' => '0'
];

$selected_stores = [];

// Check if edit mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_supplier_btn";
    $btn_text = "Update Supplier";
    $page_title = "Edit Supplier";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM suppliers WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        
        // Fetching mapped stores from the pivot table
        $map_query = "SELECT store_id FROM supplier_store_map WHERE payment_method_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        while($map_row = mysqli_fetch_assoc($map_result)) {
            $selected_stores[] = $map_row['store_id'];
        }
    } else {
        $_SESSION['message'] = "Supplier not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/list");
        exit(0);
    }
}

// Fetch active stores
$stores_query = "SELECT id, store_name FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) { $all_stores[] = $store; }

// Standard Country List (Fast)
$country_list = [
    "Bangladesh", "India", "Pakistan", "United States", "United Kingdom", "Canada", "Australia", 
    "China", "Japan", "Saudi Arabia", "United Arab Emirates", "Malaysia", "Singapore", "Germany", "France", "Italy"
];
if(!in_array($d['country'], $country_list) && !empty($d['country'])){
    array_unshift($country_list, $d['country']);
}

include('../includes/header.php');
?>

<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css"></noscript>

<style>
    .iti { width: 100%; }
    .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags.png");}
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags@2x.png");}
    }
    
    /* Logic Validation Styles */
    input.error-border, select.error-border, textarea.error-border { border-color: #ef4444 !important; background-color: #fef2f2; }
    .error-msg-text { color: #ef4444; font-size: 0.75rem; margin-top: 4px; display: none; }
    
    #valid-msg { color: #10b981; margin-top: 5px; font-size: 0.85rem; display: none; }
    #error-msg { color: #ef4444; margin-top: 5px; font-size: 0.85rem; display: none; }

    /* Spinner */
    .loader-sm {
        border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%;
        width: 16px; height: 16px; animation: spin 1s linear infinite;
        display: none; position: absolute; right: 30px; top: 12px;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

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
                        <a href="/pos/suppliers/supplier_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Onboard New Supplier' : 'Update Supplier Information'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/suppliers/save_supplier.php" method="POST" id="supplierForm" novalidate autocomplete="off">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="supplier_id" value="<?= $d['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <div class="lg:col-span-2 space-y-6 glass-card rounded-xl p-6 shadow-lg border border-slate-200 bg-white">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Supplier Name <span class="text-red-600">*</span></label>
                                        <div class="relative">
                                            <input type="text" name="name" id="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                                placeholder="e.g., Global Traders Ltd.">
                                            <i class="fas fa-building absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <div class="error-msg-text" id="error-name">Supplier Name is required</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Code Name <span class="text-red-600">*</span></label>
                                        <div class="relative">
                                            <input type="text" name="code_name" id="code_name" value="<?= htmlspecialchars($d['code_name']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all uppercase"
                                                placeholder="e.g., GTL-001">
                                            <i class="fas fa-barcode absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <div class="error-msg-text" id="error-code_name">Code Name is required</div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Trade License Number <span class="text-red-600">*</span></label>
                                        <div class="relative">
                                            <input type="text" name="trade_license_num" id="trade_license_num" value="<?= htmlspecialchars($d['trade_license_num']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                                placeholder="License No.">
                                            <i class="fas fa-id-card absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <div class="error-msg-text" id="error-trade">Trade License is required</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Bank Account Number</label>
                                        <div class="relative">
                                            <input type="text" name="bank_account_num" value="<?= htmlspecialchars($d['bank_account_num']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all input-numeric"
                                                placeholder="Account No. (Optional)">
                                            <i class="fas fa-university absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address <span class="text-red-600">*</span></label>
                                        <div class="relative">
                                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                                placeholder="info@example.com">
                                            <i class="fas fa-envelope absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                                        </div>
                                        <div class="error-msg-text" id="error-email">Please enter a valid email</div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Mobile Number <span class="text-red-600">*</span></label>
                                        <div class="relative">
                                            <input type="hidden" name="mobile" id="full_mobile" value="<?= htmlspecialchars($d['mobile']); ?>">
                                            
                                            <input type="tel" id="phone" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all input-numeric"
                                                maxlength="15"
                                                placeholder="017..."
                                                onkeypress="return (event.charCode >= 48 && event.charCode <= 57)"
                                                >
                                            
                                            <span id="valid-msg" class="hide"><i class="fas fa-check-circle"></i> Valid</span>
                                            <span id="error-msg" class="hide"></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="glass-card bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
                                    <h3 class="text-sm font-bold text-slate-600 uppercase mb-4 border-b pb-2">Location Details</h3>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Country</label>
                                            <select name="country" id="country_select" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all">
                                                <option value="">Select Country</option>
                                                <?php foreach($country_list as $country): ?>
                                                    <option value="<?= $country; ?>" <?= ($d['country'] == $country) ? 'selected' : ''; ?>><?= $country; ?></option>
                                                <?php endforeach; ?>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">State / Division</label>
                                            <div class="relative">
                                                <div id="state_loader" class="loader-sm"></div>
                                                <select name="state" id="state_select" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all <?= empty($d['state']) ? 'hidden' : ''; ?>">
                                                    <?php if(!empty($d['state'])): ?>
                                                        <option value="<?= htmlspecialchars($d['state']); ?>"><?= $d['state']; ?></option>
                                                    <?php else: ?>
                                                        <option value="">Select State</option>
                                                    <?php endif; ?>
                                                </select>
                                                <input type="text" name="state_text" id="state_text" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 <?= !empty($d['state']) ? 'hidden' : ''; ?>" 
                                                    placeholder="Enter State" value="<?= htmlspecialchars($d['state']); ?>">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">City</label>
                                            <input type="text" name="city" value="<?= htmlspecialchars($d['city']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                                placeholder="Enter City">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Full Address <span class="text-red-600">*</span></label>
                                        <textarea name="address" id="address" rows="2" 
                                            class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                            placeholder="Street, Building, Flat No..."><?= htmlspecialchars($d['address']); ?></textarea>
                                        <div class="error-msg-text" id="error-address">Address is required</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div class="relative">
                                <?php 
                                    $store_label = "Payment Available In"; 
                                    $search_placeholder = "Search branches for payment...";
                                    include('../includes/store_select_component.php'); 
                                ?>
                                </div>

                                <?php 
                                    $current_status = $d['status'];  
                                    $status_title = "Supplier";      
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";

                                    include('../includes/status_card.php'); 
                                ?>
                                
                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">
                                    <label class="block text-sm font-semibold text-slate-700 mb-4">Sort Order <span class="text-red-600">*</span></label>
                                    <div class="relative">
                                        <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                            class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all"
                                            min="0" oninput="validity.valid||(value='');" required>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <button type="button" id="submitBtn" onclick="processForm()"
                                        class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold py-3 rounded-lg shadow-lg transition-all transform hover:scale-[1.01]">
                                        <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                    </button>
                                    
                                    <a href="supplier_list.php" class="block w-full bg-slate-100 text-slate-700 font-semibold py-3 rounded-lg text-center hover:bg-slate-200 transition-all">
                                        <i class="fas fa-times mr-2"></i> Cancel
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js" defer></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // 1. STRICT NUMERIC INPUT LOGIC (Solves your main problem)
    // Select all inputs with class 'input-numeric'
    const numericInputs = document.querySelectorAll('.input-numeric');
    numericInputs.forEach(input => {
        // Prevent typing non-digits immediately
        input.addEventListener('input', function(e) {
            // Remove any character that is not 0-9
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        // Prevent pasting non-digits
        input.addEventListener('paste', function(e) {
            let pasteData = (e.clipboardData || window.clipboardData).getData('text');
            if(!/^\d+$/.test(pasteData)) {
                e.preventDefault();
                // Optional: Strip and insert logic could go here
            }
        });
    });

    // 2. PHONE VALIDATION SETUP
    let iti; 
    const phoneInput = document.querySelector("#phone");
    const fullMobileInput = document.querySelector("#full_mobile");
    const errorMsg = document.querySelector("#error-msg");
    const validMsg = document.querySelector("#valid-msg");

    const initPhone = () => {
        if (typeof window.intlTelInput === 'undefined') { setTimeout(initPhone, 100); return; }

        let defaultCountryCode = "<?= strtolower(substr($d['country'], 0, 2) == 'ba' ? 'bd' : 'bd'); ?>"; 

        iti = window.intlTelInput(phoneInput, {
            initialCountry: defaultCountryCode,
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
        });

        // Set initial value if edit mode
        if(fullMobileInput.value) {
            iti.setNumber(fullMobileInput.value);
        }

        const reset = () => {
            phoneInput.classList.remove("border-red-500", "border-green-500", "error-border");
            errorMsg.innerHTML = "";
            errorMsg.style.display = "none";
            validMsg.style.display = "none";
        };

        const validatePhone = () => {
            reset();
            if (phoneInput.value.trim()) {
                if (iti.isValidNumber()) {
                    validMsg.style.display = "block";
                    phoneInput.classList.add("border-green-500");
                    fullMobileInput.value = iti.getNumber(); // Save full format (+880...)
                    return true;
                } else {
                    phoneInput.classList.add("error-border");
                    const errorCode = iti.getValidationError();
                    const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];
                    errorMsg.innerHTML = errorMap[errorCode] || "Invalid number";
                    errorMsg.style.display = "block";
                    return false;
                }
            }
            return false;
        };

        phoneInput.addEventListener('blur', validatePhone);
        phoneInput.addEventListener('keyup', reset);
    };
    initPhone();

    // 3. LOGICAL FORM VALIDATION
    window.processForm = function() {
        let isValid = true;
        
        // Helper to show error
        const showError = (id, show) => {
            const el = document.getElementById(id);
            const errEl = document.getElementById('error-' + id);
            if(show) {
                el.classList.add('error-border');
                if(errEl) errEl.style.display = 'block';
                isValid = false;
            } else {
                el.classList.remove('error-border');
                if(errEl) errEl.style.display = 'none';
            }
        };

        // Validate Name
        const name = document.getElementById('name');
        showError('name', name.value.trim() === "");

        // Validate Code Name
        const code = document.getElementById('code_name');
        showError('code_name', code.value.trim() === "");

        // Validate Trade License
        const trade = document.getElementById('trade_license_num');
        showError('trade_license_num', trade.value.trim() === "");

        // Validate Email
        const email = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        showError('email', !emailPattern.test(email.value));

        // Validate Address
        const address = document.getElementById('address');
        showError('address', address.value.trim() === "");

        // Validate Phone (Must use ITI logic)
        if(!iti.isValidNumber()) {
            phoneInput.classList.add("error-border");
            errorMsg.innerHTML = "Valid Mobile Number Required";
            errorMsg.style.display = "block";
            isValid = false;
        } else {
            fullMobileInput.value = iti.getNumber();
        }

        // Final Submission
        if(isValid) {
            document.getElementById('supplierForm').submit();
        } else {
            // Shake effect or scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // 4. DYNAMIC LOCATION LOGIC
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const stateText = document.getElementById('state_text');
    const stateLoader = document.getElementById('state_loader');

    countrySelect.addEventListener('change', function() {
        const country = this.value;
        if(!country || country === 'Other') {
            stateSelect.classList.add('hidden');
            stateText.classList.remove('hidden');
            return;
        }

        stateLoader.style.display = 'block';
        
        fetch('https://countriesnow.space/api/v0.1/countries/states', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ country: country })
        })
        .then(res => res.json())
        .then(data => {
            stateLoader.style.display = 'none';
            if(!data.error && data.data.states.length > 0) {
                stateSelect.innerHTML = '<option value="">Select State</option>';
                data.data.states.forEach(s => {
                    let opt = document.createElement('option');
                    opt.value = s.name;
                    opt.text = s.name;
                    stateSelect.appendChild(opt);
                });
                stateSelect.classList.remove('hidden');
                stateSelect.setAttribute('name', 'state');
                stateText.classList.add('hidden');
                stateText.removeAttribute('name');
            } else {
                // Fallback to text if no states found
                stateSelect.classList.add('hidden');
                stateText.classList.remove('hidden');
            }
        })
        .catch(() => {
            stateLoader.style.display = 'none';
            stateSelect.classList.add('hidden');
            stateText.classList.remove('hidden');
        });
    });
});
</script>
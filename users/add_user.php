<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// URL ID Parsing
if(!isset($_GET['id'])){
    $uri_segments = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $last_segment = end($uri_segments);
    if(is_numeric($last_segment)) $_GET['id'] = $last_segment;
}

$mode = "create";
$btn_name = "save_user_btn";
$btn_text = "Save";
$page_title = "New User";

// Default Data Structure matching Add Customer style
$d = [
    'id' => '', 
    'name' => '', 
    'email' => '', 
    'phone' => '', 
    'dob' => '', 
    'sex' => 'Male',
    'group_id' => '', 
    'address' => '', 
    'city' => '', 
    'state' => '', 
    'country' => 'Bangladesh',
    'status' => '1', 
    'sort_order' => '0', 
    'user_image' => ''
];
$selected_stores = [];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_user_btn";
    $btn_text = "Update";
    $page_title = "Edit User";
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id='$id' LIMIT 1");
    if($row = mysqli_fetch_assoc($res)){
        $d = array_merge($d, $row);
        // Map database enums back to human readable values for the form
        if($d['sex'] == 'M') $d['sex'] = 'Male';
        elseif($d['sex'] == 'F') $d['sex'] = 'Female';
        elseif($d['sex'] == 'O') $d['sex'] = 'Other';

        $map_res = mysqli_query($conn, "SELECT store_id FROM user_store_map WHERE user_id='$id'");
        while($m = mysqli_fetch_assoc($map_res)) $selected_stores[] = $m['store_id'];
    }
}

// Fetch active stores for the store selector
$stores_query = "SELECT id, store_name FROM stores WHERE status='1' ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) { $all_stores[] = $store; }

// Standard Country List from add_customer.php
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
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .iti { width: 100%; }
    .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags.png");}
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
      .iti__flag {background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/img/flags@2x.png");}
    }
    
    /* Validation Styles */
    input.error-border, select.error-border, textarea.error-border { border-color: #ef4444 !important; background-color: #fef2f2; }
    .error-msg-text { color: #ef4444; font-size: 0.75rem; margin-top: 4px; display: none; }
    
    #valid-msg { color: #10b981; margin-top: 5px; font-size: 0.85rem; display: none; }
    #error-msg { color: #ef4444; margin-top: 5px; font-size: 0.85rem; display: none; }

    /* Section Headers */
    .section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .section-header i { color: #3b82f6; }

    /* Custom scroll for store list */
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }

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
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <!-- Header Section -->
                <div class="mb-6 slide-in">
                    <div class="flex items-center gap-4 mb-2">
                        <a href="/pos/users/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Assign role and access to new user' : 'Update user profile and permissions'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl p-8 slide-in bg-white border border-slate-200 shadow-xl">
                    <form action="/pos/users/save" method="POST" enctype="multipart/form-data" id="userForm" novalidate autocomplete="off">
                        <?php if($mode == 'edit'): ?>
                            <input type="hidden" name="user_id" value="<?= $d['id'] ?>">
                            <input type="hidden" name="old_image" value="<?= $d['user_image'] ?>">
                            <input type="hidden" name="update_user_btn" value="true">
                        <?php else: ?>
                            <input type="hidden" name="save_user_btn" value="true">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Main Form Area -->
                            <div class="lg:col-span-2 space-y-8 glass-card rounded-xl p-8 border border-slate-200 shadow-sm bg-white">
                                
                                <!-- Identity & Account Section -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-id-card"></i>
                                        <span>Identity & Login Account</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Full Name <span class="text-red-600">*</span></label>
                                            <input type="text" name="name" id="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-semibold text-sm"
                                                placeholder="e.g. John Doe">
                                            <div class="error-msg-text" id="error-name">Full name is required</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Email Address <span class="text-red-600">*</span></label>
                                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                placeholder="email@example.com">
                                            <div class="error-msg-text" id="error-email">Valid email is required</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Phone Number <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="hidden" name="phone" id="full_phone" value="<?= htmlspecialchars($d['phone']); ?>">
                                                <input type="tel" id="phone" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                    placeholder="017...">
                                                <div class="flex justify-between items-center mt-1">
                                                    <span id="valid-msg" class="hide text-[10px] text-green-600"><i class="fas fa-check-circle"></i> Valid</span>
                                                    <span id="error-msg" class="hide text-[10px] text-red-600"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Password <?= $mode=='create'?'<span class="text-red-600">*</span>':'(Leave blank to keep old)' ?></label>
                                            <input type="password" name="password" id="password" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                placeholder="Min 6 characters">
                                            <div class="error-msg-text" id="error-password">Password must be at least 6 characters</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Role & Personal Section -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-user-shield"></i>
                                        <span>Role & Personal Details</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">User Group / Role <span class="text-red-600">*</span></label>
                                            <select name="group_id" id="group_id" class="select2 w-full">
                                                <option value="">-- Select Group --</option>
                                                <?php 
                                                $groups = mysqli_query($conn, "SELECT id, name FROM user_groups");
                                                while($g = mysqli_fetch_assoc($groups)): ?>
                                                    <option value="<?= $g['id'] ?>" <?= $d['group_id']==$g['id']?'selected':'' ?>><?= $g['name'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <div class="error-msg-text" id="error-group_id">Please select a user group</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Date of Birth <span class="text-red-600">*</span></label>
                                            <input type="date" name="dob" id="dob" value="<?= htmlspecialchars($d['dob']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm">
                                            <div class="error-msg-text" id="error-dob">Date of birth is required</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Gender <span class="text-red-600">*</span></label>
                                            <select name="sex" id="sex" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-medium text-sm">
                                                <option value="Male" <?= $d['sex']=='Male'?'selected':'' ?>>Male</option>
                                                <option value="Female" <?= $d['sex']=='Female'?'selected':'' ?>>Female</option>
                                                <option value="Other" <?= $d['sex']=='Other'?'selected':'' ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Details Section -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span>Location Details</span>
                                    </div>
                                    <div class="grid grid-cols-1 gap-5">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Full Address</label>
                                            <textarea name="address" rows="2" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                placeholder="Street, Area, etc."><?= htmlspecialchars($d['address']); ?></textarea>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">City <span class="text-red-600">*</span></label>
                                                <input type="text" name="city" id="city" value="<?= htmlspecialchars($d['city']); ?>" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                    placeholder="City">
                                                <div class="error-msg-text" id="error-city">Required</div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">State <span class="text-red-600">*</span></label>
                                                <div class="relative">
                                                    <div id="state_loader" class="loader-sm"></div>
                                                    <select name="state" id="state_select" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium <?= empty($d['state']) ? 'hidden' : ''; ?>">
                                                        <?php if(!empty($d['state'])): ?>
                                                            <option value="<?= htmlspecialchars($d['state']); ?>"><?= $d['state']; ?></option>
                                                        <?php else: ?>
                                                            <option value="">Select State</option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <input type="text" name="state_text" id="state_text" 
                                                        class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-sm <?= !empty($d['state']) ? 'hidden' : ''; ?>" 
                                                        placeholder="Enter State" value="<?= htmlspecialchars($d['state']); ?>">
                                                </div>
                                                <div class="error-msg-text" id="error-state">Required</div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Country <span class="text-red-600">*</span></label>
                                                <select name="country" id="country_select" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium">
                                                    <option value="">Select Country</option>
                                                    <?php foreach($country_list as $country): ?>
                                                        <option value="<?= $country; ?>" <?= ($d['country'] == $country) ? 'selected' : ''; ?>><?= $country; ?></option>
                                                    <?php endforeach; ?>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <div class="error-msg-text" id="error-country_select">Required</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar Section -->
                            <div class="space-y-6">
                                <!-- Profile Photo Section -->
                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">
                                    <label class="block text-sm font-semibold text-slate-700 mb-4 text-left">User Photo</label>
                                    
                                    <div id="image-drop-zone" class="relative border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100 hover:border-blue-400 transition-all cursor-pointer overflow-hidden min-h-[160px] flex flex-col items-center justify-center p-4">
                                        <input type="file" name="user_image" id="user-image-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="previewImage(this)">
                                        
                                        <div id="upload-placeholder" class="text-center <?= !empty($d['user_image']) ? 'hidden' : ''; ?>">
                                            <div class="w-16 h-16 bg-white text-slate-400 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm border border-slate-100">
                                                <i class="fas fa-image text-2xl"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-700">Click to upload photo</p>
                                            <p class="text-xs text-slate-400 mt-1">Select user profile picture</p>
                                        </div>

                                        <div id="preview-container" class="relative group <?= empty($d['user_image']) ? 'hidden' : ''; ?>">
                                            <img id="profile-preview" src="<?= !empty($d['user_image']) ? $d['user_image'] : ''; ?>" 
                                                 class="max-h-32 rounded-lg shadow-sm object-cover" alt="Profile"
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($d['name']); ?>&background=random&size=100'; this.onerror=null;">
                                            <div class="mt-2 text-center">
                                                 <span class="text-xs font-semibold text-blue-600 group-hover:underline">Click to change</span>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-3 italic text-center"><i class="fas fa-info-circle mr-1"></i> Max size: 2MB (JPG, PNG)</p>
                                </div>

                                <!-- Assign Store Section -->
                                <div class="relative">
                                    <?php 
                                        $store_label = "Assign Stores"; 
                                        $search_placeholder = "Search Store...";
                                        include('../includes/store_select_component.php'); 
                                    ?>
                                    <div class="error-msg-text" id="error-store">Assign at least one store</div>
                                </div>

                                <!-- Status Section -->
                                <?php 
                                    $current_status = $d['status'];  
                                    $status_title = "User";      
                                    $card_id = "status-card";
                                    $label_id = "status-label";
                                    $input_id = "status_input";
                                    $toggle_id = "status_toggle";

                                    include('../includes/status_card.php'); 
                                ?>

                                <!-- Sort Order -->
                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">                          
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wider">Sort Order <span class="text-red-600">*</span></label>
                                        <input type="number" name="sort_order" id="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                            class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"
                                            min="0">
                                        <div class="error-msg-text" id="error-sort_order">Sort Order is required</div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="grid grid-cols-2 gap-3 pt-2">
                                    <button type="button" onclick="processUserForm()"
                                        class="col-span-1 bg-teal-800 hover:bg-teal-700 text-white font-semibold py-3 rounded-lg shadow-md transition-all">
                                        <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                    </button>
                                    
                                    <a href="/pos/users/list" 
                                        class="col-span-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-lg shadow-md transition-all text-center flex items-center justify-center">
                                        <i class="fas fa-times-circle mr-2"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

<script>
    // 0. Image Preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('profile-preview');
                const container = document.getElementById('preview-container');
                const placeholder = document.getElementById('upload-placeholder');
                
                preview.src = e.target.result;
                container.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

$(document).ready(function() {
    $('.select2').select2({ width: '100%', placeholder: 'Search and Select Role...' });

    // 1. PHONE VALIDATION SETUP
    let iti; 
    const phoneInput = document.querySelector("#phone");
    const fullPhoneInput = document.querySelector("#full_phone");
    const errorMsg = document.querySelector("#error-msg");
    const validMsg = document.querySelector("#valid-msg");

    const initPhone = () => {
        if (typeof window.intlTelInput === 'undefined') { setTimeout(initPhone, 100); return; }

        iti = window.intlTelInput(phoneInput, {
            initialCountry: "bd",
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
        });

        if(fullPhoneInput.value) { iti.setNumber(fullPhoneInput.value); }

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
                    fullPhoneInput.value = iti.getNumber(); 
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

    // 2. FORM SUBMISSION LOGIC
    window.processUserForm = function() {
        let isValid = true;
        
        const showError = (id, show) => {
            const el = document.getElementById(id);
            const errEl = document.getElementById('error-' + id);
            if(el) {
                if(show) {
                    el.classList.add('error-border');
                    if(errEl) errEl.style.display = 'block';
                    isValid = false;
                } else {
                    el.classList.remove('error-border');
                    if(errEl) errEl.style.display = 'none';
                }
            }
        };

        // Required Field Validation
        showError('name', document.getElementById('name').value.trim() === "");
        showError('city', document.getElementById('city').value.trim() === "");
        showError('sort_order', document.getElementById('sort_order').value === "");
        showError('dob', document.getElementById('dob').value === "");

        // State Validation (Select or Text)
        const stateSelectElement = document.getElementById('state_select');
        const stateTextElement = document.getElementById('state_text');
        const isStateSelectVisible = !stateSelectElement.classList.contains('hidden');
        if(isStateSelectVisible) showError('state_select', stateSelectElement.value === "");
        else showError('state_text', stateTextElement.value.trim() === "");

        // Country Validation
        showError('country_select', document.getElementById('country_select').value === "");
        
        // Email Validation
        const email = document.getElementById('email');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        showError('email', email.value.trim() === "" || !emailPattern.test(email.value));

        // Phone Validation
        if (phoneInput.value.trim() === "" || !iti.isValidNumber()) {
            phoneInput.classList.add("error-border");
            errorMsg.innerHTML = "Valid phone number is required";
            errorMsg.style.display = "block";
            isValid = false;
        } else {
            fullPhoneInput.value = iti.getNumber();
        }

        // Password Validation (only for create mode or if typed)
        const password = document.getElementById('password');
        if("<?= $mode ?>" === "create") {
            showError('password', password.value.trim().length < 6);
        } else if(password.value.trim() !== "") {
            showError('password', password.value.trim().length < 6);
        }

        // Group Validation
        const groupId = $('#group_id').val();
        if(groupId === "") {
            $('#group_id').next('.select2').find('.select2-selection').addClass('error-border');
            document.getElementById('error-group_id').style.display = 'block';
            isValid = false;
        } else {
            $('#group_id').next('.select2').find('.select2-selection').removeClass('error-border');
            document.getElementById('error-group_id').style.display = 'none';
        }

        // Store Selection Validation
        const storeCheckboxes = document.querySelectorAll('input[name="stores[]"]:checked');
        const storeErrorMsg = document.getElementById('error-store');
        if(storeCheckboxes.length === 0) {
            if(storeErrorMsg) storeErrorMsg.style.display = 'block';
            isValid = false;
        } else {
            if(storeErrorMsg) storeErrorMsg.style.display = 'none';
        }

        if(isValid) {
            document.getElementById('userForm').submit();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please check the form for missing or invalid information.',
                confirmButtonColor: '#0d9488'
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // 4. DYNAMIC STATE LOADER
    const countrySelect = document.getElementById('country_select');
    const stateSelectElement = document.getElementById('state_select');
    const stateTextElement = document.getElementById('state_text');
    const stateLoader = document.getElementById('state_loader');

    const loadStates = (country, currentState = "") => {
        if(!country || country === 'Other') {
            stateSelectElement.classList.add('hidden');
            stateTextElement.classList.remove('hidden');
            stateTextElement.setAttribute('name', 'state');
            stateSelectElement.removeAttribute('name');
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
                stateSelectElement.innerHTML = '<option value="">Select State</option>';
                data.data.states.forEach(s => {
                    let opt = document.createElement('option');
                    opt.value = s.name;
                    opt.text = s.name;
                    if(s.name === currentState) opt.selected = true;
                    stateSelectElement.appendChild(opt);
                });
                stateSelectElement.classList.remove('hidden');
                stateSelectElement.setAttribute('name', 'state');
                stateTextElement.classList.add('hidden');
                stateTextElement.removeAttribute('name');
            } else {
                stateSelectElement.classList.add('hidden');
                stateSelectElement.removeAttribute('name');
                stateTextElement.classList.remove('hidden');
                stateTextElement.setAttribute('name', 'state');
            }
        })
        .catch(() => {
            stateLoader.style.display = 'none';
            stateSelectElement.classList.add('hidden');
            stateSelectElement.removeAttribute('name');
            stateTextElement.classList.remove('hidden');
            stateTextElement.setAttribute('name', 'state');
        });
    };

    if(countrySelect.value) loadStates(countrySelect.value, "<?= $d['state']; ?>");
    countrySelect.addEventListener('change', function() { loadStates(this.value); });

    // Handle Session Messages via SWAL
    <?php if(isset($_SESSION['message'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['msg_type']; ?>',
            title: '<?php echo ($_SESSION['msg_type'] == "error") ? "Oops..." : "Success!"; ?>',
            text: '<?php echo $_SESSION['message']; ?>',
            confirmButtonColor: '#0d9488'
        });
        <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
    <?php endif; ?>
});
</script>

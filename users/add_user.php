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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* ADD STORE DESIGN SYSTEM */
    .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    .slide-in { animation: slideIn 0.4s ease-out forwards; opacity: 0; transform: translateY(20px); }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }

    /* Input & Select2 Customization */
    .iti { width: 100%; }
    .select2-container .select2-selection--single { height: 50px; border-radius: 0.75rem; border-color: #cbd5e1; padding-top: 10px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { top: 12px; right: 10px; }
    .select2-container--default .select2-selection--multiple { border-radius: 0.75rem; border-color: #cbd5e1; min-height: 50px; padding: 5px; }

    /* Validation & Scroll */
    .error-msg-text { color: #e11d48; font-size: 0.75rem; font-weight: 700; margin-top: 4px; display: none; align-items: center; gap: 4px; }
    input.error-border, select.error-border, textarea.error-border { border-color: #e11d48 !important; background-color: #fff1f2; }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    /* Loader */
    .loader-sm {
        border: 2px solid #f3f3f3; border-top: 2px solid #0d9488; border-radius: 50%;
        width: 16px; height: 16px; animation: spin 1s linear infinite;
        display: none; position: absolute; right: 12px; top: 16px; z-index: 10;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    /* Select2 Custom Teal Highlight */
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #0d9488 !important;
        color: white !important;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <div class="mb-8 slide-in">
                    <div class="flex items-center gap-4 mb-4">
                        <a href="/pos/users/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Assign role and access to new user' : 'Update user profile and permissions'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="/pos/users/save" method="POST" enctype="multipart/form-data" id="userForm" novalidate autocomplete="off">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="user_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $d['user_image'] ?>">
                        <input type="hidden" name="update_user_btn" value="true">
                    <?php else: ?>
                        <input type="hidden" name="save_user_btn" value="true">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        
                        <div class="lg:col-span-8 space-y-6">
                            
                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-1 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white flex items-center justify-center text-lg shadow-md shadow-emerald-900/40"><i class="fas fa-id-card"></i></span>
                                    Identity & Account
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="relative w-full group md:col-span-2">
                                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-teal-600 transition-all placeholder-slate-400 font-medium" placeholder="Full Name *">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-pen-nib"></i>
                                        <div class="error-msg-text" id="error-name"><i class="fas fa-exclamation-circle"></i> Full name is required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all placeholder-slate-400" placeholder="Email Address *">
                                        <i class="absolute right-4 top-4 text-slate-400 peer-focus:text-teal-600 transition-colors text-lg fas fa-envelope"></i>
                                        <div class="error-msg-text" id="error-email"><i class="fas fa-exclamation-circle"></i> Valid email is required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="hidden" name="phone" id="full_phone" value="<?= htmlspecialchars($d['phone']); ?>">
                                        <input type="tel" id="phone" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all placeholder-slate-400" placeholder="017...">
                                        
                                        <div class="flex justify-between items-center mt-1">
                                            <span id="valid-msg" class="hide text-[10px] text-green-600 font-bold hidden"><i class="fas fa-check-circle"></i> Valid</span>
                                            <span id="error-msg" class="hide text-[10px] text-rose-600 font-bold hidden"></span>
                                        </div>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="password" name="password" id="password" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all placeholder-slate-400" 
                                            placeholder="Password <?= $mode=='create'?'*':'(Leave blank to keep)'; ?>">
                                        <button type="button" id="togglePassword" class="absolute right-4 top-4 text-slate-400 hover:text-teal-600 transition-colors text-lg">
                                            <i class="fas fa-eye" id="eyeIcon"></i>
                                        </button>
                                        <div class="error-msg-text" id="error-password"><i class="fas fa-exclamation-circle"></i> Min 6 characters required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="password" name="confirm_password" id="confirm_password" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all placeholder-slate-400" 
                                            placeholder="Confirm Password <?= $mode=='create'?'*':''; ?>">
                                        <div class="error-msg-text" id="error-confirm_password"><i class="fas fa-exclamation-circle"></i> Passwords do not match</div>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-2 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center text-lg shadow-md shadow-teal-600/40"><i class="fas fa-user-shield"></i></span>
                                    Role & Personal Details
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="relative w-full group">
                                        <select name="group_id" id="group_id" class="select2 w-full">
                                            <option value="">-- Select Role --</option>
                                            <?php 
                                            $groups = mysqli_query($conn, "SELECT id, name FROM user_groups");
                                            while($g = mysqli_fetch_assoc($groups)): ?>
                                                <option value="<?= $g['id'] ?>" <?= $d['group_id']==$g['id']?'selected':'' ?>><?= $g['name'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="error-msg-text" id="error-group_id"><i class="fas fa-exclamation-circle"></i> Role required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="date" name="dob" id="dob" value="<?= htmlspecialchars($d['dob']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all">
                                        <div class="error-msg-text" id="error-dob"><i class="fas fa-exclamation-circle"></i> Min 18 years required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <select name="sex" id="sex" class="select2 peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all cursor-pointer">
                                            <option value="Male" <?= $d['sex']=='Male'?'selected':'' ?>>Male</option>
                                            <option value="Female" <?= $d['sex']=='Female'?'selected':'' ?>>Female</option>
                                            <option value="Other" <?= $d['sex']=='Other'?'selected':'' ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-rose-600 text-white flex items-center justify-center text-lg shadow-md shadow-rose-600/40"><i class="fas fa-map-marked-alt"></i></span>
                                    Location Details
                                </h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 relative w-full group">
                                        <textarea name="address" rows="2" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all resize-none" 
                                            placeholder="Full Address"><?= htmlspecialchars($d['address']); ?></textarea>
                                        <i class="absolute right-4 top-4 text-slate-400 fas fa-map-pin"></i>
                                    </div>

                                    <div class="relative w-full group">
                                        <input type="text" name="city" id="city" value="<?= htmlspecialchars($d['city']); ?>" 
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all" placeholder="City *">
                                        <div class="error-msg-text" id="error-city"><i class="fas fa-exclamation-circle"></i> Required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <div id="state_loader" class="loader-sm"></div>
                                        <select name="state" id="state_select" class="select2 peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all cursor-pointer <?= empty($d['state']) ? 'hidden' : ''; ?>">
                                            <?php if(!empty($d['state'])): ?>
                                                <option value="<?= htmlspecialchars($d['state']); ?>"><?= $d['state']; ?></option>
                                            <?php else: ?>
                                                <option value="">Select State</option>
                                            <?php endif; ?>
                                        </select>
                                        <input type="text" name="state_text" id="state_text" value="<?= htmlspecialchars($d['state']); ?>"
                                            class="peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all <?= !empty($d['state']) ? 'hidden' : ''; ?>" placeholder="State/Province *">
                                        <div class="error-msg-text" id="error-state"><i class="fas fa-exclamation-circle"></i> Required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <select name="country" id="country_select" class="select2 peer block py-3.5 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 appearance-none focus:outline-none focus:ring-2 focus:ring-teal-600 transition-all cursor-pointer">
                                            <option value="">Select Country *</option>
                                            <?php foreach($country_list as $country): ?>
                                                <option value="<?= $country; ?>" <?= ($d['country'] == $country) ? 'selected' : ''; ?>><?= $country; ?></option>
                                            <?php endforeach; ?>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="error-msg-text" id="error-country_select"><i class="fas fa-exclamation-circle"></i> Required</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-6">
                            
                            <?php 
                                $current_status = $d['status'];  
                                $status_title = "User";      
                                $card_id = "status-card";
                                $label_id = "status-label";
                                $input_id = "status_input";
                                $toggle_id = "status_toggle";

                                include('../includes/status_card.php'); 
                            ?>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-2 bg-white">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Profile Photo</h3>
                                
                                <div id="image-drop-zone" class="relative border-2 border-dashed border-slate-300 rounded-xl hover:border-teal-500 transition-colors bg-slate-50 flex flex-col items-center justify-center p-6 text-center cursor-pointer group h-48 overflow-hidden">
                                    <input type="file" name="user_image" id="user-image-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="previewImage(this)">
                                    
                                    <div id="upload-placeholder" class="<?= !empty($d['user_image']) ? 'hidden' : ''; ?>">
                                        <div class="w-12 h-12 bg-white text-slate-400 rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm border border-slate-200 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-camera text-xl"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-600">Click to Upload</p>
                                        <p class="text-xs text-slate-400">Max 2MB</p>
                                    </div>

                                    <div id="preview-container" class="absolute inset-0 w-full h-full <?= empty($d['user_image']) ? 'hidden' : ''; ?>">
                                        <img id="profile-preview" src="<?= !empty($d['user_image']) ? $d['user_image'] : ''; ?>" class="w-full h-full object-cover rounded-xl">
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="text-white text-sm font-bold"><i class="fas fa-pen"></i> Change</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                                <?php 
                                    $store_label = "Assign Stores"; 
                                    $search_placeholder = "Search Store...";
                                    include('../includes/store_select_component.php'); 
                                ?>
                                <div class="error-msg-text" id="error-store"><i class="fas fa-exclamation-circle"></i> Select at least one store</div>
                            </div>

                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-3 bg-white">
                                <div class="relative w-full group">
                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1 block">Sort Order</label>
                                    <input type="number" name="sort_order" id="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                        class="peer block py-3 px-4 w-full text-sm text-slate-800 bg-white rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-600 font-bold" min="0">
                                    <div class="error-msg-text" id="error-sort_order"><i class="fas fa-exclamation-circle"></i> Required</div>
                                </div>
                            </div>

                            <div class="slide-in delay-3 pt-1">
                                <div class="grid grid-cols-2 gap-4">
                                    <button type="button" onclick="processUserForm()" 
                                        class="col-span-1 py-3.5 rounded-xl bg-[#064e3b] hover:bg-[#053d2e] text-white font-bold text-base shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group">
                                        <i class="fas fa-save"></i>
                                        <span><?= $btn_text; ?></span>
                                    </button>
                                    
                                    <button type="reset" onclick="location.reload()" 
                                        class="col-span-1 py-3.5 rounded-xl bg-red-800 hover:bg-red-700 text-white font-bold text-base shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group">
                                        <i class="fas fa-undo"></i>
                                        <span>Reset</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
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
    $('.select2').each(function() {
        let placeholder = $(this).find('option:first').text() || 'Search and Select...';
        if(placeholder.includes('-- Select') || placeholder.includes('Select ')) {
            // Keep it
        } else {
            placeholder = 'Select...';
        }
        $(this).select2({ width: '100%', placeholder: placeholder });
    });

    // DOB Max Date Restriction (18 Years)
    const dobInput = document.getElementById('dob');
    if(dobInput) {
        const today = new Date();
        const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
        dobInput.max = maxDate.toISOString().split('T')[0];
    }

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

    // 2. PASSWORD TOGGLE & MATCHING LOGIC
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const toggleBtn = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const errConfirm = document.getElementById('error-confirm_password');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            confirmInput.setAttribute('type', type);
            eyeIcon.classList.toggle('fa-eye');
            eyeIcon.classList.toggle('fa-eye-slash');
        });
    }

    const validateMatch = () => {
        if (confirmInput.value.length > 0) {
            if (passwordInput.value !== confirmInput.value) {
                confirmInput.classList.add('error-border');
                errConfirm.style.display = 'block';
            } else {
                confirmInput.classList.remove('error-border');
                errConfirm.style.display = 'none';
            }
        } else {
            confirmInput.classList.remove('error-border');
            errConfirm.style.display = 'none';
        }
    };

    passwordInput.addEventListener('input', validateMatch);
    confirmInput.addEventListener('input', validateMatch);

    // 3. FORM SUBMISSION LOGIC
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

        showError('name', document.getElementById('name').value.trim() === "");
        showError('city', document.getElementById('city').value.trim() === "");
        showError('sort_order', document.getElementById('sort_order').value === "");
        
        // DOB check with 18+ validation
        const dobVal = document.getElementById('dob').value;
        if(dobVal === "") {
            showError('dob', true);
        } else {
            const dob = new Date(dobVal);
            const today = new Date();
            const minAgeDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            if(dob > minAgeDate) {
                showError('dob', true);
                document.getElementById('error-dob').innerHTML = '<i class="fas fa-exclamation-circle"></i> User must be 18+';
            } else {
                showError('dob', false);
            }
        }

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
        const confirm = document.getElementById('confirm_password');
        if("<?= $mode ?>" === "create") {
            showError('password', password.value.trim().length < 6);
            showError('confirm_password', confirm.value !== password.value);
        } else if(password.value.trim() !== "") {
            showError('password', password.value.trim().length < 6);
            showError('confirm_password', confirm.value !== password.value);
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
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'error',
                title: 'Please check the form for errors!',
                background: '#1e293b',
                color: '#fff',
                iconColor: '#fff',
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

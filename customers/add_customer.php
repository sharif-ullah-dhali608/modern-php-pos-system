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
$btn_name = "save_customer_btn";
$btn_text = "Save";
$page_title = "New Customer";

// Default Data Structure (Matches your Screenshot fields)
$d = [
    'id' => '', 
    'name' => '', 
    'company_name' => '',
    'code_name' => '',
    'customer_group' => '',
    'membership_level' => '',
    'opening_balance' => '0',
    'credit_limit' => '0',
    'reward_points' => '0',
    'mobile' => '', 
    'dob' => '', 
    'email' => '', 
    'sex' => 'Male', 
    'age' => '', 
    'image' => '',
    'address' => '', 
    'city' => '', 
    'state' => '', 
    'country' => 'Bangladesh', 
    'status' => 'Active', 
    'sort_order' => '0'
];

$selected_stores = [];

// Check if edit mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_customer_btn";
    $btn_text = "Update";
    $page_title = "Edit Customer";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM customers WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $fetched_data = mysqli_fetch_assoc($result);
        $d = array_merge($d, $fetched_data);
        
        // Fetching mapped stores from the pivot table (customer_stores_map)
        $map_query = "SELECT store_id FROM customer_stores_map WHERE customer_id='$id'";
        $map_result = mysqli_query($conn, $map_query);
        while($map_row = mysqli_fetch_assoc($map_result)) {
            $selected_stores[] = $map_row['store_id'];
        }
    } else {
        $_SESSION['message'] = "Customer not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/customer_list.php");
        exit(0);
    }
}

// Fetch active stores for the store selector
$stores_query = "SELECT id, store_name FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
while($store = mysqli_fetch_assoc($stores_result)) { $all_stores[] = $store; }

// Standard Country List
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
                        <a href="/pos/customers/customer_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Add details for new customer' : 'Update customer information'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card rounded-xl p-8 slide-in">
                    <form action="/pos/customers/save_customer.php" method="POST" id="customerForm" enctype="multipart/form-data" novalidate autocomplete="off">
                        
                        <?php if(isset($_GET['id'])): ?>
                            <input type="hidden" name="customer_id" value="<?= $d['id']; ?>">
                            <input type="hidden" name="update_customer_btn" value="true">
                        <?php else: ?>
                            <input type="hidden" name="save_customer_btn" value="true">
                        <?php endif; ?>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            <div class="lg:col-span-2 space-y-8 glass-card rounded-xl p-8 shadow-lg border border-slate-200 bg-white">
                                
                                <!-- Identity & Classification -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-id-card"></i>
                                        <span>Identity & Classification</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-6 gap-5">
                                        <div class="md:col-span-1">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Code <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="code_name" id="code_name" value="<?= htmlspecialchars($d['code_name']); ?>" 
                                                    class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-mono text-sm"
                                                    placeholder="CUST-001">
                                            </div>
                                            <div class="error-msg-text" id="error-code_name">Required</div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Full Name <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="name" id="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all font-semibold text-sm"
                                                    placeholder="e.g. John Doe">
                                            </div>
                                            <div class="error-msg-text" id="error-name">Required</div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Company</label>
                                            <div class="relative">
                                                <input type="text" name="company_name" value="<?= htmlspecialchars($d['company_name']); ?>" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                    placeholder="Company Name (Optional)">
                                            </div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Customer Group <span class="text-red-600">*</span></label>
                                            <select name="customer_group" id="customer_group" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer text-sm font-medium">
                                                <option value="" <?= empty($d['customer_group']) ? 'selected' : ''; ?>>Select Group</option>
                                                <option value="Retail" <?= $d['customer_group'] == 'Retail' ? 'selected' : ''; ?>>Retail</option>
                                                <option value="Wholesale" <?= $d['customer_group'] == 'Wholesale' ? 'selected' : ''; ?>>Wholesale</option>
                                                <option value="VIP" <?= $d['customer_group'] == 'VIP' ? 'selected' : ''; ?>>VIP</option>
                                                <option value="Corporate" <?= $d['customer_group'] == 'Corporate' ? 'selected' : ''; ?>>Corporate</option>
                                            </select>
                                            <div class="error-msg-text" id="error-customer_group">Required</div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Membership Level <span class="text-red-600">*</span></label>
                                            <select name="membership_level" id="membership_level" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all cursor-pointer text-sm font-medium">
                                                <option value="" <?= empty($d['membership_level']) ? 'selected' : ''; ?>>Select Level</option>
                                                <option value="Standard" <?= $d['membership_level'] == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                                <option value="Silver" <?= $d['membership_level'] == 'Silver' ? 'selected' : ''; ?>>Silver</option>
                                                <option value="Gold" <?= $d['membership_level'] == 'Gold' ? 'selected' : ''; ?>>Gold</option>
                                                <option value="Platinum" <?= $d['membership_level'] == 'Platinum' ? 'selected' : ''; ?>>Platinum</option>
                                            </select>
                                            <div class="error-msg-text" id="error-membership_level">Required</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact & Personal Details -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-address-book"></i>
                                        <span>Contact & Personal</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Phone <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="hidden" name="mobile" id="full_mobile" value="<?= htmlspecialchars($d['mobile']); ?>">
                                                <input type="tel" id="phone" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all input-numeric text-sm"
                                                    placeholder="017...">
                                                <div class="flex justify-between items-center mt-1">
                                                    <span id="valid-msg" class="hide text-[10px] text-green-600"><i class="fas fa-check-circle"></i> Valid</span>
                                                    <span id="error-msg" class="hide text-[10px] text-red-600"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Email Address</label>
                                            <div class="relative">
                                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                    placeholder="email@example.com">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Date Of Birth</label>
                                            <input type="date" name="dob" value="<?= htmlspecialchars($d['dob']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Sex <span class="text-red-600">*</span></label>
                                            <select name="sex" id="sex" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium">
                                                <option value="Male" <?= $d['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?= $d['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="Other" <?= $d['sex'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Age</label>
                                            <input type="number" name="age" value="<?= htmlspecialchars($d['age']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"
                                                placeholder="Age">
                                        </div>
                                    </div>
                                </div>

                                <!-- Financial Status -->
                                <div>
                                    <div class="section-header">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Financial Status</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Opening Balance <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="number" step="0.01" name="opening_balance" id="opening_balance" value="<?= htmlspecialchars($d['opening_balance']); ?>" 
                                                    class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-right font-mono text-sm"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="error-msg-text" id="error-opening_balance">Required</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Credit Limit <span class="text-red-600">*</span></label>
                                            <div class="relative">
                                                <input type="number" step="0.01" name="credit_limit" id="credit_limit" value="<?= htmlspecialchars($d['credit_limit']); ?>" 
                                                    class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-right font-mono text-sm"
                                                    placeholder="0.00">
                                            </div>
                                            <div class="error-msg-text" id="error-credit_limit">Required</div>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Reward Points</label>
                                            <input type="number" name="reward_points" value="<?= htmlspecialchars($d['reward_points']); ?>" 
                                                class="w-full bg-white border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-right text-sm"
                                                placeholder="0">
                                        </div>
                                    </div>
                                </div>

                                <!-- Location Details -->
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
                            
                            <div class="space-y-6">
                                
                                <!-- Profile Image Section -->
                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">
                                    <label class="block text-sm font-semibold text-slate-700 mb-4 text-left">Profile Picture</label>
                                    
                                    <div id="image-drop-zone" class="relative border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100 hover:border-blue-400 transition-all cursor-pointer overflow-hidden min-h-[160px] flex flex-col items-center justify-center p-4">
                                        <input type="file" name="image" id="image-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/*" onchange="previewImage(this)">
                                        
                                        <div id="upload-placeholder" class="text-center <?= !empty($d['image']) ? 'hidden' : ''; ?>">
                                            <div class="w-16 h-16 bg-white text-slate-400 rounded-xl flex items-center justify-center mx-auto mb-3 shadow-sm border border-slate-100">
                                                <i class="fas fa-image text-2xl"></i>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-700">Click to upload image</p>
                                            <p class="text-xs text-slate-400 mt-1">Select your profile picture</p>
                                        </div>

                                        <div id="preview-container" class="relative group <?= empty($d['image']) ? 'hidden' : ''; ?>">
                                            <img id="profile-preview" src="<?= !empty($d['image']) ? '/pos/uploads/customers/'.$d['image'] : ''; ?>" 
                                                 class="max-h-32 rounded-lg shadow-sm object-cover" alt="Profile"
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($d['name']); ?>&background=random&size=100'; this.onerror=null;">
                                            <div class="mt-2 text-center">
                                                 <span class="text-xs font-semibold text-blue-600 group-hover:underline">Click to change</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-xs text-slate-400 mt-3 italic text-center"><i class="fas fa-info-circle mr-1"></i> Max size: 2MB (JPG, PNG)</p>
                                </div>

                                <div class="relative">
                                    <?php 
                                        // Using the component as per your structure, but passing correct labels
                                        $store_label = "Available Store"; 
                                        $search_placeholder = "Search Store...";
                                        // The component likely uses $all_stores and $selected_stores variables
                                        include('../includes/store_select_component.php'); 
                                    ?>
                                    <div class="error-msg-text" id="error-store">Please select at least one store</div>
                                </div>
                                <?php 
                                        $current_status = $d['status'];  
                                        $status_title = "Customer";      
                                        $card_id = "status-card";
                                        $label_id = "status-label";
                                        $input_id = "status_input";
                                        $toggle_id = "status_toggle";

                                        include('../includes/status_card.php'); 
                                    ?>

                                <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm bg-white">                          
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Sort Order <span class="text-red-600">*</span></label>
                                        <input type="number" name="sort_order" id="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                            class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600 transition-all"
                                            min="0">
                                        <div class="error-msg-text" id="error-sort_order">Sort Order is required</div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <button type="button" id="submitBtn" onclick="processForm()"
                                        class="col-span-1 bg-teal-800 hover:bg-teal-700 text-white font-semibold py-3 rounded-lg shadow-md transition-all">
                                        <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                    </button>
                                    
                                    <button type="reset" onclick="location.reload()" 
                                        class="col-span-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-lg shadow-md transition-all">
                                        <i class="fas fa-undo mr-2"></i> Reset
                                    </button>
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

document.addEventListener("DOMContentLoaded", function() {

    // 1. NUMERIC INPUT FILTER
    const numericInputs = document.querySelectorAll('.input-numeric');
    numericInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
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

        let defaultCountryCode = "<?= strtolower(substr($d['country'], 0, 2) == 'Ba' ? 'bd' : 'bd'); ?>"; 

        iti = window.intlTelInput(phoneInput, {
            initialCountry: defaultCountryCode,
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
        });

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
                    fullMobileInput.value = iti.getNumber(); 
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
            return true; // Empty phone is valid if not required (Screen shows no * for phone)
        };

        phoneInput.addEventListener('blur', validatePhone);
        phoneInput.addEventListener('keyup', reset);
    };
    initPhone();

    // 3. FORM SUBMISSION LOGIC
    window.processForm = function() {
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
        showError('code_name', document.getElementById('code_name').value.trim() === "");
        showError('customer_group', document.getElementById('customer_group').value === "");
        showError('membership_level', document.getElementById('membership_level').value === "");
        showError('opening_balance', document.getElementById('opening_balance').value === "");
        showError('credit_limit', document.getElementById('credit_limit').value === "");
        showError('city', document.getElementById('city').value.trim() === "");
        showError('sort_order', document.getElementById('sort_order').value === "");

        // State Validation (Select or Text)
        const stateSelectVal = document.getElementById('state_select').value;
        const stateTextVal = document.getElementById('state_text').value.trim();
        const isStateSelectVisible = !document.getElementById('state_select').classList.contains('hidden');
        
        if(isStateSelectVisible) {
            showError('state_select', stateSelectVal === "");
        } else {
            showError('state_text', stateTextVal === "");
        }

        // Country Validation
        showError('country_select', document.getElementById('country_select').value === "");

        // Email Validation (Optional but must be valid format if entered)
        const email = document.getElementById('email');
        if(email.value.trim() !== "") {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            showError('email', !emailPattern.test(email.value));
        }

        // Phone Update & Validation
        if (phoneInput.value.trim() === "") {
             phoneInput.classList.add("error-border");
             if(errorMsg) {
                 errorMsg.innerHTML = "Phone number is required";
                 errorMsg.style.display = "block";
             }
             isValid = false;
        } else {
             if(iti.isValidNumber()) {
                fullMobileInput.value = iti.getNumber();
             } else {
                 phoneInput.classList.add("error-border");
                 isValid = false;
             }
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
            document.getElementById('customerForm').submit();
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };

    // 4. DYNAMIC STATE LOADER (Same as supplier)
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const stateText = document.getElementById('state_text');
    const stateLoader = document.getElementById('state_loader');

    const loadStates = (country, currentState = "") => {
        if(!country || country === 'Other') {
            stateSelect.classList.add('hidden');
            stateText.classList.remove('hidden');
            stateText.setAttribute('name', 'state');
            stateSelect.removeAttribute('name');
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
                    if(s.name === currentState) opt.selected = true;
                    stateSelect.appendChild(opt);
                });
                stateSelect.classList.remove('hidden');
                stateSelect.setAttribute('name', 'state');
                stateText.classList.add('hidden');
                stateText.removeAttribute('name');
            } else {
                stateSelect.classList.add('hidden');
                stateSelect.removeAttribute('name');
                stateText.classList.remove('hidden');
                stateText.setAttribute('name', 'state');
            }
        })
        .catch(() => {
            stateLoader.style.display = 'none';
            stateSelect.classList.add('hidden');
            stateSelect.removeAttribute('name');
            stateText.classList.remove('hidden');
            stateText.setAttribute('name', 'state');
        });
    };

    // Trigger on page load
    if(countrySelect.value) {
        loadStates(countrySelect.value, "<?= $d['state']; ?>");
    }

    countrySelect.addEventListener('change', function() {
        loadStates(this.value);
    });
});
</script>
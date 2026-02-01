<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Initialize Variables
$mode = "add";
$bank_id = "";
$account_name = "";
$account_details = "";
$initial_balance = "0"; 
$account_no = "";
$contact_person = "";
$phone_number = "";
$url = "";
$status = 1; 
$sort_order = 0;
$selected_stores = [];

$page_title = "Add Bank Account";
$btn_text = "Save Bank";
$btn_name = "save_bank";

// Check for Edit Mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $bank_id = mysqli_real_escape_string($conn, $_GET['id']);
    $page_title = "Edit Bank Account";
    $btn_text = "Update Bank";
    
    // Fetch Bank Details
    $query = "SELECT * FROM bank_accounts WHERE id='$bank_id' LIMIT 1";
    $query_run = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_assoc($query_run);
        $account_name = $row['account_name'];
        $account_details = $row['account_details'];
        $initial_balance = $row['initial_balance'];
        $account_no = $row['account_no'];
        $contact_person = $row['contact_person'];
        $phone_number = $row['phone_number'];
        $url = $row['url'];
        $status = $row['status'];
        $sort_order = $row['sort_order'];
        
        // Fetch Associated Stores
        $store_query = "SELECT store_id FROM bank_account_to_store WHERE account_id='$bank_id'";
        $store_run = mysqli_query($conn, $store_query);
        if($store_run) {
            while($s_row = mysqli_fetch_assoc($store_run)) {
                $selected_stores[] = $s_row['store_id'];
            }
        }
    } else {
        $_SESSION['status_code'] = "error";
        header("Location: /pos/accounting/bank/list");
        exit(0);
    }
}

// Handle Form Submission
if(isset($_POST['save_bank'])) {
    $account_name = mysqli_real_escape_string($conn, $_POST['account_name']);
    $account_details = mysqli_real_escape_string($conn, $_POST['account_details']);
    $account_no = mysqli_real_escape_string($conn, $_POST['account_no']);
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $url = mysqli_real_escape_string($conn, $_POST['url']);
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 0;
    $sort_order = mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];
    
    if($mode == "add") {
        $query = "INSERT INTO bank_accounts (account_name, account_details, initial_balance, account_no, contact_person, phone_number, url, status, sort_order) 
                  VALUES ('$account_name', '$account_details', '$initial_balance', '$account_no', '$contact_person', '$phone_number', '$url', '$status', '$sort_order')";
        $query_run = mysqli_query($conn, $query);
        
        if($query_run) {
            $new_bank_id = mysqli_insert_id($conn);
            
            foreach($stores as $store_id) {
                $store_id = mysqli_real_escape_string($conn, $store_id);
                mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, status, sort_order) VALUES ('$store_id', '$new_bank_id', 1, 0)");
            }
            
            $_SESSION['status'] = "Bank Account Added Successfully";
            $_SESSION['status_code'] = "success";
            header("Location: /pos/accounting/bank/list");
            exit(0);
        } else {
            $_SESSION['status'] = "Something Went Wrong";
            $_SESSION['status_code'] = "error";
        }
    } else {
        $update_query = "UPDATE bank_accounts SET 
                        account_name='$account_name', 
                        account_details='$account_details', 
                        account_no='$account_no', 
                        contact_person='$contact_person', 
                        phone_number='$phone_number', 
                        url='$url', 
                        status='$status', 
                        sort_order='$sort_order' 
                        WHERE id='$bank_id'";
        $update_run = mysqli_query($conn, $update_query);
        
        if($update_run) {
            $existing_stores = [];
            $check_maps = mysqli_query($conn, "SELECT store_id FROM bank_account_to_store WHERE account_id='$bank_id'");
            while($cmap = mysqli_fetch_assoc($check_maps)) {
                $existing_stores[] = $cmap['store_id'];
            }
            
            foreach($stores as $store_id) {
                if(!in_array($store_id, $existing_stores)) {
                   $store_id = mysqli_real_escape_string($conn, $store_id);
                   mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, status, sort_order) VALUES ('$store_id', '$bank_id', 1, 0)");
                }
            }
            
            if(!empty($stores)) {
                 $store_ids_str = implode(',', array_map('intval', $stores));
                 mysqli_query($conn, "DELETE FROM bank_account_to_store WHERE account_id='$bank_id' AND store_id NOT IN ($store_ids_str)");
            } else {
                 mysqli_query($conn, "DELETE FROM bank_account_to_store WHERE account_id='$bank_id'");
            }
            
            $_SESSION['status'] = "Bank Account Updated Successfully";
            $_SESSION['status_code'] = "success";
            header("Location: /pos/accounting/bank/list");
            exit(0);
        } else {
            $_SESSION['status'] = "Update Failed";
            $_SESSION['status_code'] = "error";
        }
    }
}

// Fetch All Stores for Component
$all_stores = [];
$stores_q = "SELECT id, store_name FROM stores WHERE status=1";
$stores_run = mysqli_query($conn, $stores_q);
if($stores_run) {
    while($s = mysqli_fetch_assoc($stores_run)) {
        $all_stores[] = $s;
    }
}

include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-[1600px] mx-auto">
                <!-- Header -->
                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/accounting/bank/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Manage your bank account details and store associations</p>
                        </div>
                    </div>
                </div>

                <form action="" method="POST" class="slide-in delay-100" id="bankForm" novalidate>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Column 1: Basic Information -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden h-fit">
                            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                <i class="fas fa-university text-teal-600"></i> Account Data
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Account Name -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Account Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="account_name" id="account_name" value="<?= htmlspecialchars($account_name); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Account Name is required.</span>
                                </div>

                                <!-- Account No -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Account No. <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="account_no" id="account_no" value="<?= htmlspecialchars($account_no); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Account No. is required.</span>
                                </div>

                                <!-- Account Details -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">Details</label>
                                    <textarea name="account_details" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all"><?= htmlspecialchars($account_details); ?></textarea>
                                </div>

                                <!-- Contact Person -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Contact Person <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="contact_person" id="contact_person" value="<?= htmlspecialchars($contact_person); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Contact Person is required.</span>
                                </div>

                                <!-- Phone Number -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="phone_number" id="phone_number" value="<?= htmlspecialchars($phone_number); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Phone Number is required.</span>
                                </div>

                                <!-- Internal Banking URL -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">Wallet/Banking Url</label>
                                    <input type="text" name="url" value="<?= htmlspecialchars($url); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Store Mapping -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden h-fit">
                            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                <i class="fas fa-store text-teal-600"></i> Store Association    
                            </h3>
                            
                            <div>
                                <?php
                                $store_label = "Available Store Access";
                                $search_placeholder = "Filter stores...";
                                $store_list_class = "min-h-[400px]"; // Adjusted to fill column
                                include('../includes/store_select_component.php');
                                ?>
                            </div>
                            
                            <div class="mt-6 p-4 bg-teal-50 rounded-lg border border-teal-100">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-info-circle text-teal-600 mt-1"></i>
                                    <p class="text-sm text-teal-800">
                                        Selected stores will be able to perform transactions (Deposit/Withdraw) using this bank account.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Column 3: Status & Settings -->
                        <div class="space-y-6">
                            <!-- Status Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-1">
                                <?php
                                    $current_status = $status;
                                    $status_title = 'Bank Account';
                                    $card_id = 'bank-status-card';
                                    $label_id = 'bank-status-label';
                                    $input_id = 'bank-status-input';
                                    $toggle_id = 'bank-status-toggle';
                                    include('../includes/status_card.php');
                                ?>
                            </div>

                            <!-- Additional Settings -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-orange-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-cog text-orange-600"></i> Configuration
                                </h3>
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Sort Order <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="sort_order" id="sort_order" value="<?= htmlspecialchars($sort_order); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Sort Order is required.</span>
                                    <p class="text-xs text-slate-500 mt-2">Determines the display order in lists.</p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4">
                                <button type="submit" name="save_bank" class="w-full py-4 bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-700 transition-all shadow-lg shadow-teal-500/30 flex items-center justify-center text-lg mb-3">
                                    <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                </button>
                                <a href="/pos/Accounting/bank_list.php" class="w-full py-3 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all flex items-center justify-center">
                                    <i class="fas fa-times mr-2"></i> Cancel
                                </a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Status Toggle
    if(typeof initStatusToggle === 'function') {
        initStatusToggle('bank-status-card', 'bank-status-toggle', 'bank-status-input', 'bank-status-label');
    }

    // Form Validation
    const form = document.getElementById('bankForm');
    const requiredIds = ['account_name', 'account_no', 'contact_person', 'phone_number', 'sort_order'];

    form.addEventListener('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // Reset errors
        document.querySelectorAll('.error-msg').forEach(el => el.classList.add('hidden'));

        requiredIds.forEach(id => {
            const input = document.getElementById(id);
            if(!input.value.trim()) {
                const errorSpan = input.parentNode.querySelector('.error-msg');
                if(errorSpan) {
                    errorSpan.classList.remove('hidden');
                    isValid = false;
                    if(!firstError) firstError = input;
                }
            }
        });

        if(!isValid) {
            e.preventDefault();
            if(firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });

    // Clear error on input
    requiredIds.forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', function() {
            if(this.value.trim()) {
                const errorSpan = this.parentNode.querySelector('.error-msg');
                if(errorSpan) errorSpan.classList.add('hidden');
            }
        });
    });
});
</script>

<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// ==========================================
// URL ID PARSING
// ==========================================
if(!isset($_GET['id'])){
    $uri_segments = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $last_segment = end($uri_segments);
    if(is_numeric($last_segment)){
        $_GET['id'] = $last_segment;
    }
}

// Default Mode Settings
$mode = "create";
$btn_name = "save_giftcard_btn";
$btn_text = "Create Giftcard";
$page_title = "Add Giftcard";

// Initialize default values
$d = [
    'id' => '', 
    'card_no' => '', 
    'value' => '', 
    'balance' => '', 
    'customer_id' => '', 
    'expiry_date' => '', 
    'image' => '', 
    'status' => '1'
];

// Fetch Dropdown Data 
$customers = mysqli_query($conn, "SELECT id, name FROM customers WHERE status='1' ORDER BY name ASC");

// Edit Mode Logic
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_giftcard_btn";
    $btn_text = "Update Giftcard";
    $page_title = "Edit Giftcard";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Fetch Basic Data with customer name
    $query = "SELECT g.*, c.name as customer_name FROM giftcards g 
              LEFT JOIN customers c ON g.customer_id = c.id 
              WHERE g.id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Giftcard not found";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/list");
        exit(0);
    }
}

include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-7xl mx-auto">
                
                <?php if(isset($_SESSION['message'])): 
                    $msgType = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : "error";
                    $swalIcon = ($msgType == "success") ? "success" : "error";
                    $bgColor = ($msgType == "success") ? "#059669" : "#1e293b";
                    $safeMessage = json_encode($_SESSION['message']);
                ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });

                    Toast.fire({
                        icon: '<?= $swalIcon; ?>',
                        title: <?= $safeMessage; ?>,
                        background: '<?= $bgColor; ?>',
                        color: '#fff',
                        iconColor: '#fff',
                        customClass: {
                            popup: 'rounded-2xl shadow-2xl px-5 py-2'
                        }
                    });
                });
                </script>
                <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
                <?php endif; ?>

                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/giftcard/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Fill in the details to <?= $mode == 'create' ? 'create a new' : 'update'; ?> giftcard.</p>
                        </div>
                    </div>
                </div>

                <form action="/pos/giftcard/save" method="POST" enctype="multipart/form-data" id="giftcardForm" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="giftcard_id" value="<?= $d['id']; ?>">
                        <input type="hidden" name="old_image" value="<?= htmlspecialchars($d['image']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 gap-6">
                        
                        <div class="space-y-6 slide-in delay-100">
                            <!-- Basic Information Section -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-credit-card text-teal-600"></i> Card Information
                                </h3>
                                
                                <div class="grid grid-cols-1 gap-5">
                                    <!-- Row 1: Card Number & Customer -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Card Number <span class="text-red-500">*</span></label>
                                            <div class="flex">
                                                <div class="relative w-full">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-credit-card text-slate-400"></i>
                                                    </div>
                                                    <input type="text" name="card_no" id="card_no" value="<?= htmlspecialchars($d['card_no']); ?>" class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-l-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-mono uppercase" placeholder="Scan or Enter Code" maxlength="64">
                                                </div>
                                                <button type="button" onclick="generateCardNumber()" class="bg-teal-800 hover:bg-teal-600 text-white px-4 py-3 rounded-r-lg font-medium transition-colors" title="Generate Card Number">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Card Number is required.</span>
                                        </div>

                                        <div class="form-group relative">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Customer</label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-chevron-up text-slate-400"></i>
                                                </div>
                                                <input 
                                                    type="text" 
                                                    id="customer_search" 
                                                    placeholder="Search customer..." 
                                                    class="w-full bg-slate-50 border-2 border-teal-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none pr-10 transition-all"
                                                    autocomplete="off"
                                                >
                                                <input type="hidden" name="customer_id" id="customer_id" value="<?= $d['customer_id']; ?>">
                                                <span class="error-msg text-xs text-red-500 mt-1 hidden">Customer is required.</span>
                                                
                                                <!-- Dropdown List -->
                                                <div id="customer_dropdown" class="absolute left-0 right-0 mt-1 bg-white border border-teal-500 rounded-lg max-h-60 overflow-y-auto hidden shadow-2xl z-[9999]">
                                                    <div class="p-0">
                                                        <?php 
                                                        if($customers && mysqli_num_rows($customers) > 0):
                                                            mysqli_data_seek($customers, 0);
                                                            while($customer = mysqli_fetch_assoc($customers)): 
                                                        ?>
                                                            <div 
                                                                class="customer-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-100 last:border-b-0" 
                                                                data-id="<?= $customer['id']; ?>" 
                                                                data-name="<?= htmlspecialchars($customer['name']); ?>"
                                                            >
                                                                <span class="font-medium text-slate-800"><?= htmlspecialchars($customer['name']); ?></span>
                                                            </div>
                                                        <?php 
                                                            endwhile;
                                                        endif; 
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 2: Value, Balance, Expiry Date -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Card Value (USD) <span class="text-red-500">*</span></label>
                                            <input type="number" step="0.01" name="value" id="value" value="<?= $d['value']; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-bold" placeholder="0.00" min="0">
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Card Value is required.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Current Balance (USD)</label>
                                            <input type="number" step="0.01" name="balance" id="balance" value="<?= $d['balance']; ?>" class="w-full bg-blue-50/30 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none font-bold" placeholder="0.00" min="0" <?= $mode == 'create' ? 'readonly' : ''; ?>>
                                            <small class="text-slate-500">Auto-set to card value on creation</small>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Expiry Date</label>
                                            <input type="date" name="expiry_date" id="expiry_date" value="<?= $d['expiry_date']; ?>" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 outline-none">
                                            <span class="error-msg text-xs text-red-500 mt-1 hidden">Expiry Date is required.</span>
                                        </div>
                                    </div>

                                    <!-- Row 3: Image Upload & Status -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div class="form-group">
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Card Logo</label>
                                            <div class="flex items-center gap-3">
                                                <label class="bg-teal-700 hover:bg-teal-800 text-white px-6 py-3 rounded-lg font-medium cursor-pointer transition-all flex items-center gap-2 shadow-md hover:shadow-lg">
                                                    <i class="fas fa-upload"></i>Choose File
                                                    <input type="file" name="image" id="image" accept="image/*" class="hidden" onchange="previewImage(event)">
                                                </label>
                                                <div id="file-name-display" class="flex-1 bg-slate-50 border border-slate-300 rounded-lg px-4 py-3 text-slate-600 text-sm">
                                                    <?php if(!empty($d['image'])): ?>
                                                        <?= basename($d['image']); ?>
                                                    <?php else: ?>
                                                        No file chosen
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-slate-500 text-xs block mt-2">Max size: 2MB. Format: JPG, PNG.</small>
                                        </div>

                                        <!-- Status Card (Reusable Component) -->
                                        <div>
                                            <?php
                                                $current_status = $d['status'] ?? '1';
                                                $status_title = 'Giftcard';
                                                $card_id = 'giftcard-status-card';
                                                $label_id = 'giftcard-status-label';
                                                $input_id = 'giftcard-status-input';
                                                $toggle_id = 'giftcard-status-toggle';
                                                include('../includes/status_card.php');
                                            ?>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Card Preview (Full Width) -->
                        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden slide-in delay-300">
                            <div class="absolute top-0 right-0 opacity-10">
                                <i class="fas fa-credit-card text-8xl"></i>
                            </div>
                            <div class="relative z-10">
                                <div class="flex justify-between items-start mb-16">
                                    <div>
                                        <p class="text-sm font-semibold opacity-80">Giftcard</p>
                                        <p id="preview-card-no-display" class="text-xl font-bold mt-1"><?= $d['card_no'] ?: '••••••••••••••••'; ?></p>
                                    </div>
                                    <div id="preview-logo-container" class="w-16 h-16 bg-white/10 rounded-lg flex items-center justify-center">
                                        <?php if(!empty($d['image'])): ?>
                                            <img src="<?= $d['image']; ?>" alt="Logo" class="w-full h-full object-contain">
                                        <?php else: ?>
                                            <i class="fas fa-image text-white/50 text-2xl"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="text-center mb-12">
                                    <p id="preview-value-display" class="text-5xl font-bold">USD <?= $d['value'] ?: '0.00'; ?></p>
                                    <p class="text-xs opacity-80 mt-2 uppercase tracking-wider">Card Balance</p>
                                </div>

                                <div class="bg-white rounded-lg mb-8 flex items-center justify-center" style="padding: 12px; margin: 0 -20px; width: 50%; height: 100px; margin-left: auto; margin-right: auto;">
                                    <svg id="barcode-container" width="100%" height="70" style="display: none;"></svg>
                                </div>

                                <div class="flex justify-between items-end">
                                    <div>
                                        <p class="text-xs opacity-80">Card Holder</p>
                                        <p class="font-semibold">GiftCard</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs opacity-80">Expiry</p>
                                        <p id="preview-expiry-display" class="font-semibold"><?= $d['expiry_date'] ?: 'MM/YY'; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-8 flex items-center justify-center gap-3">
                        <button type="submit" name="<?= $btn_name; ?>" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="fas fa-save"></i> <?= $btn_text; ?>
                        </button>
                        <a href="/pos/giftcard/list" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
// Generate barcode
function generateBarcode(cardNo) {
    if (cardNo && cardNo.length > 0) {
        JsBarcode("#barcode-container", cardNo, {
            format: "CODE128",
            width: 3,
            height: 80,
            displayValue: false,
            margin: 0
        });
    }
}

// Toggle Status (handled by status_card.php component)
// Component automatically syncs checkbox to hidden input

// Generate random card number (16 digits)
function generateCardNumber() {
    let cardNo = '';
    for (let i = 0; i < 16; i++) {
        cardNo += Math.floor(Math.random() * 10);
    }
    document.getElementById('card_no').value = cardNo;
    document.getElementById('preview-card-no-display').textContent = cardNo;
    generateBarcode(cardNo);
}

// Image Preview
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        // Update file name display
        document.getElementById('file-name-display').textContent = file.name;
        
        // Update logo in card preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-logo-container').innerHTML = '<img src="' + e.target.result + '" alt="Logo" class="w-full h-full object-contain">';
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('image-preview').innerHTML = '<div class="text-center"><i class="fas fa-image text-slate-300 text-4xl mb-2"></i><p class="text-slate-500 text-sm">Click to upload image</p></div>';
    document.getElementById('image').value = '';
    document.getElementById('image-preview').nextElementSibling.style.display = 'none';
}

// Live Preview Update
document.getElementById('card_no')?.addEventListener('keyup', function() {
    document.getElementById('preview-card-no-display').textContent = this.value || '••••••••••••••••';
    generateBarcode(this.value);
});

document.getElementById('value')?.addEventListener('change', function() {
    document.getElementById('preview-value-display').textContent = 'USD ' + (parseFloat(this.value) || 0).toFixed(2);
});

document.getElementById('expiry_date')?.addEventListener('change', function() {
    document.getElementById('preview-expiry-display').textContent = this.value || 'MM/YY';
});

// Auto-set balance to value on creation
document.getElementById('value')?.addEventListener('change', function() {
    if (document.querySelector('input[name="giftcard_id"]') === null) {
        document.getElementById('balance').value = this.value;
    }
});

// Form Validation
document.getElementById('giftcardForm')?.addEventListener('submit', function(e) {
    let isValid = true;
    const cardNo = document.getElementById('card_no').value.trim();
    const value = document.getElementById('value').value;
    const customerId = document.getElementById('customer_id').value;
    const expiryDate = document.getElementById('expiry_date').value;

    // Clear all previous error messages
    document.querySelectorAll('.error-msg').forEach(msg => msg.classList.add('hidden'));

    // Validate Card Number
    if (!cardNo) {
        document.querySelector('#card_no').closest('.form-group').querySelector('.error-msg').classList.remove('hidden');
        isValid = false;
    }

    // Validate Card Value
    if (!value || value <= 0) {
        document.querySelector('#value').closest('.form-group').querySelector('.error-msg').classList.remove('hidden');
        isValid = false;
    }

    // Validate Customer
    if (!customerId) {
        document.querySelector('#customer_id').closest('.form-group').querySelector('.error-msg').classList.remove('hidden');
        isValid = false;
    }

    // Validate Expiry Date
    if (!expiryDate) {
        document.querySelector('#expiry_date').closest('.form-group').querySelector('.error-msg').classList.remove('hidden');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        // Scroll to first error
        document.querySelector('.error-msg:not(.hidden)').closest('.form-group').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});

// Initialize status card component and customer search
document.addEventListener('DOMContentLoaded', function() {
    initCustomerSearch();
    initStatusToggle('giftcard-status-card', 'giftcard-status-toggle', 'giftcard-status-input', 'giftcard-status-label');
    
    // Initialize barcode if card_no exists (edit mode)
    var cardNoInput = document.getElementById('card_no');
    if (cardNoInput && cardNoInput.value) {
        generateBarcode(cardNoInput.value);
        // Show the barcode container
        var barcodeContainer = document.getElementById('barcode-container');
        if (barcodeContainer) {
            barcodeContainer.style.display = 'block';
        }
    }
});

// Searchable Customer Field
function initCustomerSearch() {
    const searchInput = document.getElementById('customer_search');
    const dropdown = document.getElementById('customer_dropdown');
    const customerOptions = document.querySelectorAll('.customer-option');
    const customerIdInput = document.getElementById('customer_id');
    
    if (!searchInput) return;
    
    // Pre-fill customer name if editing (PHP-injected value takes priority)
    <?php if($mode == 'edit' && !empty($d['customer_name'])): ?>
    searchInput.value = "<?= htmlspecialchars($d['customer_name']); ?>";
    <?php else: ?>
    // Fallback: try to find from dropdown options
    if (customerIdInput.value) {
        const selectedOption = document.querySelector('.customer-option[data-id="' + customerIdInput.value + '"]');
        if (selectedOption) {
            searchInput.value = selectedOption.getAttribute('data-name');
        }
    }
    <?php endif; ?>
    
    // Show dropdown on focus
    searchInput.addEventListener('focus', () => {
        dropdown.classList.remove('hidden');
    });
    
    // Filter customers as user types
    searchInput.addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        
        customerOptions.forEach(option => {
            const customerName = option.getAttribute('data-name').toLowerCase();
            if (customerName.includes(searchTerm) || searchTerm === '') {
                option.style.display = 'block';
            } else {
                option.style.display = 'none';
            }
        });
    });
    
    // Select customer
    customerOptions.forEach(option => {
        option.addEventListener('click', function() {
            const customerId = this.getAttribute('data-id');
            const customerName = this.getAttribute('data-name');
            
            searchInput.value = customerName;
            customerIdInput.value = customerId;
            dropdown.classList.add('hidden');
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.form-group') && !e.target.closest('#customer_search')) {
            dropdown.classList.add('hidden');
        }
    });
}
</script>


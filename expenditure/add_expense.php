<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Initialize Variables
$mode = "add";
$expense_id = "";
$store_id = "";
$reference_no = 'EXP-' . time() . rand(100,999);
$category_id = "";
$title = "";
$amount = ""; 
$returnable = "0"; 
$note = "";
$attachment = "";
$selected_stores = []; // For store component

$page_title = "Add Expense";
$btn_text = "Save Expense";
$btn_name = "save_expense_btn";

// Check for Edit Mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $expense_id = mysqli_real_escape_string($conn, $_GET['id']);
    $page_title = "Edit Expense";
    $btn_text = "Update Expense";
    $btn_name = "update_expense_btn";
    
    // Fetch Expense Details
    $query = "SELECT * FROM expenses WHERE id='$expense_id' LIMIT 1";
    $query_run = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_assoc($query_run);
        $store_id = $row['store_id']; // Primary store
        $selected_stores[] = $row['store_id']; // For component
        $reference_no = $row['reference_no'];
        $category_id = $row['category_id'];
        $title = $row['title'];
        $amount = $row['amount'];
        $returnable = $row['returnable'];
        $note = $row['note']; // Using 'note' based on DB fix
        $attachment = $row['attachment'];
    } else {
        $_SESSION['status_code'] = "error";
        header("Location: /pos/expenditure/expense_list");
        exit(0);
    }
}

// Fetch Categories for Dropdown
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM expense_category WHERE status='1' ORDER BY category_name ASC");

// Fetch All Stores for Component (Required by store_select_component.php)
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
                        <a href="/pos/expenditure/expense_list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Manage your business expenditures</p>
                        </div>
                    </div>
                </div>

                <form action="/pos/expenditure/save_expense" method="POST" enctype="multipart/form-data" class="slide-in delay-100" id="expenseForm" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="expense_id" value="<?= $expense_id ?>">
                        <input type="hidden" name="old_attachment" value="<?= $attachment ?>">
                        <input type="hidden" name="update_expense_btn" value="true">
                    <?php else: ?>
                        <input type="hidden" name="save_expense_btn" value="true">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                        
                        <!-- Column 1: Expense Data -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden h-full min-h-[500px]">
                            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                <i class="fas fa-file-invoice-dollar text-teal-600"></i> Expense Data
                            </h3>
                            
                            <div class="space-y-5">
                                <!-- Title -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                                        Expense Title <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($title); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all placeholder-slate-400" placeholder="e.g. Office Rent">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Title is required.</span>
                                </div>

                                <!-- Category (Custom Dropdown) -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative group w-full bg-white border border-teal-500 rounded-lg transition-all" id="category_selector_container">
                                        <div class="flex items-center px-4 py-2.5 text-sm font-bold text-slate-600 outline-none cursor-pointer" onclick="toggleCategoryDropdown()">
                                            <input type="text" id="category_search_input" 
                                                   class="w-full bg-transparent border-none outline-none font-bold text-slate-600 placeholder-slate-400 cursor-pointer"
                                                   placeholder="-- Select Category --"
                                                   readonly
                                                   value="<?= $category_id ? '' : '' ?>">
                                            <div class="pl-2 text-slate-400"><i class="fas fa-chevron-down text-xs"></i></div>
                                        </div>
                                        <input type="hidden" name="category_id" id="category_id" value="<?= $category_id ?>">
                                        
                                        <!-- Dropdown List -->
                                        <div id="category_dropdown" class="absolute left-0 top-full mt-1 w-full bg-white border border-slate-200 rounded-lg hidden shadow-xl z-[50] overflow-hidden p-2">
                                            <div class="mb-2">
                                                <input type="text" id="cat_filter" class="w-full bg-white border-2 border-blue-600 rounded-md px-3 py-2 text-slate-700 text-sm font-bold focus:outline-none placeholder-slate-500" placeholder="Search..." onkeyup="filterCategories()">
                                            </div>
                                            <div id="category_list" class="max-h-48 overflow-y-auto custom-scroll space-y-1">
                                                <!-- Options populated here -->
                                                <?php 
                                                // Reset pointer for reuse
                                                mysqli_data_seek($categories, 0);
                                                $cat_name_selected = "";
                                                while($cat = mysqli_fetch_assoc($categories)): 
                                                    if($cat['category_id'] == $category_id) $cat_name_selected = $cat['category_name'];
                                                ?>
                                                    <div class="cat-option px-3 py-2 hover:bg-teal-500 hover:text-white cursor-pointer transition-colors rounded-md text-sm font-bold flex items-center justify-between text-teal-300" 
                                                         onclick="selectCategory('<?= $cat['category_id'] ?>', '<?= htmlspecialchars($cat['category_name']) ?>')">
                                                        <span><?= htmlspecialchars($cat['category_name']) ?></span>
                                                        <?php if($cat['category_id'] == $category_id): ?>
                                                            <i class="fas fa-check text-xs"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden" id="cat_error">Category is required.</span>
                                </div>

                                <!-- Amount -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">
                                        Amount <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" name="amount" id="amount" value="<?= htmlspecialchars($amount); ?>" step="0.01" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" placeholder="0.00">
                                    <span class="error-msg text-xs text-red-500 mt-1 hidden">Valid Amount is required.</span>
                                </div>

                                <!-- Reference No (Barcod Style) -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Reference No <span class="text-red-500">*</span></label>
                                    <div class="flex">
                                        <div class="relative w-full">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-barcode text-slate-400"></i>
                                            </div>
                                            <input type="text" name="reference_no" id="reference_no" value="<?= htmlspecialchars($reference_no); ?>" class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-l-lg px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-mono uppercase" placeholder="Generate or Enter Reference" <?= $mode == 'edit' ? 'readonly' : '' ?>>
                                        </div>
                                        <?php if($mode != 'edit'): ?>
                                        <button type="button" onclick="generateReference()" class="bg-teal-800 hover:bg-teal-600 text-white px-4 py-3 rounded-r-lg font-medium transition-colors" title="Generate New">
                                            <i class="fas fa-random"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[10px] text-slate-400 mt-1 pl-1">Click icon to auto-generate</p>
                                </div>

                                <!-- Note -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Note / Description</label>
                                    <textarea name="note" id="note" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 font-medium text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all"><?= htmlspecialchars($note); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Store Mapping (Height matched) -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden h-full min-h-[500px] flex flex-col">
                            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2 shrink-0">
                                <i class="fas fa-store text-teal-600"></i> Store Mapping <span class="text-red-500">*</span>
                            </h3>
                            
                            <div class="flex-1 flex flex-col">
                                <?php
                                $store_label = "Available In Stores";
                                $search_placeholder = "Filter stores...";
                                $store_list_class = "flex-1 overflow-y-auto min-h-[300px]"; 
                                include('../includes/store_select_component.php');
                                ?>
                                <span class="error-msg text-xs text-red-500 mt-1 hidden" id="store_error">Please select at least one store.</span>
                            </div>
                        </div>

                        <!-- Column 3: Status & Configurations -->
                        <div class="space-y-6">
                            <!-- Returnable Status (Redesigned) -->
                            <div id="returnable-card" class="rounded-xl shadow-sm border p-6 relative overflow-hidden transition-all duration-300 <?= $returnable=='1' ? 'bg-teal-900 border-teal-800' : 'bg-white border-slate-200' ?>">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-bold mb-1 content-label <?= $returnable=='1' ? 'text-white' : 'text-slate-800' ?>">Returnable?</h3>
                                        <p class="text-[11px] font-bold uppercase tracking-widest status-text <?= $returnable=='1' ? 'text-teal-300' : 'text-slate-400' ?>">
                                            <?= $returnable=='1' ? 'Result: REFUNDABLE AMOUNT' : 'Result: NON-REFUNDABLE' ?>
                                        </p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="returnable" id="returnable-toggle" value="1" class="sr-only peer" <?= $returnable == '1' ? 'checked' : ''; ?> onchange="toggleReturnableStyle()">
                                        <div class="w-12 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-teal-500"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Attachments (Moved Here) -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden h-fit">
                                <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-paperclip text-blue-600"></i> Attachments
                                </h3>
                                
                                <div class="upload-box relative rounded-xl p-6 text-center cursor-pointer group border-2 border-dashed border-slate-200 hover:border-teal-400 transition-colors bg-slate-50/50" id="drop-zone">
                                    <input type="file" name="attachment[]" id="docs_upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" multiple onchange="handleFileSelect(this)">
                                    <div class="mb-2">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-slate-300 group-hover:text-teal-500 transition-colors"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 group-hover:text-teal-600">Upload Receipt</p>
                                </div>
                                
                                <!-- Preview Container -->
                                <div id="preview_container" class="mt-4 space-y-2">
                                    <?php if(!empty($attachment)): 
                                        $existing_files = explode(',', $attachment);
                                        foreach($existing_files as $att): 
                                            if(empty($att)) continue;
                                    ?>
                                        <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-slate-200 flex items-center justify-center text-slate-500 shrink-0">
                                                <i class="fas fa-file"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[10px] font-bold text-slate-700 truncate"><?= basename($att) ?></p>
                                                <p class="text-[9px] text-slate-400">Existing</p>
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4">
                                <button type="submit" name="<?= $btn_name; ?>" class="w-full py-4 bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-700 transition-all shadow-lg shadow-teal-500/30 flex items-center justify-center text-lg mb-3">
                                    <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                </button>
                                <a href="/pos/expenditure/expense_list" class="w-full py-3 bg-white border-2 border-slate-200 text-slate-600 font-bold rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all flex items-center justify-center">
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
// PHP provided selected value if editing
const initialCatName = "<?= htmlspecialchars($cat_name_selected) ?>";
if(initialCatName) {
    document.getElementById('category_search_input').value = initialCatName;
}

// Category Dropdown Logic
function toggleCategoryDropdown() {
    const dropdown = document.getElementById('category_dropdown');
    dropdown.classList.toggle('hidden');
    if(!dropdown.classList.contains('hidden')) {
        document.getElementById('cat_filter').focus();
    }
}

function selectCategory(id, name) {
    document.getElementById('category_id').value = id;
    document.getElementById('category_search_input').value = name;
    document.getElementById('category_dropdown').classList.add('hidden');
    // Validation cleanup
    const container = document.getElementById('category_selector_container');
    const error = document.getElementById('cat_error');
    container.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
    error.classList.add('hidden');
}

function filterCategories() {
    const input = document.getElementById('cat_filter').value.toLowerCase();
    const options = document.querySelectorAll('.cat-option');
    options.forEach(opt => {
        const text = opt.innerText.toLowerCase();
        opt.style.display = text.includes(input) ? 'flex' : 'none';
    });
}

// Close Dropdown when clicking outside
document.addEventListener('click', function(e) {
    const container = document.getElementById('category_selector_container');
    const dropdown = document.getElementById('category_dropdown');
    if (!container.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

// Returnable Style Logic
function toggleReturnableStyle() {
    const toggle = document.getElementById('returnable-toggle');
    const card = document.getElementById('returnable-card');
    const label = card.querySelector('.content-label');
    const statusText = card.querySelector('.status-text');
    
    if(toggle.checked) {
        // Active State (Dark Green)
        card.classList.remove('bg-white', 'border-slate-200');
        card.classList.add('bg-teal-900', 'border-teal-800');
        
        label.classList.remove('text-slate-800');
        label.classList.add('text-white');
        
        statusText.classList.remove('text-slate-400');
        statusText.classList.add('text-teal-300');
        statusText.innerText = 'Result: REFUNDABLE AMOUNT';
    } else {
        // Inactive State (White)
        card.classList.remove('bg-teal-900', 'border-teal-800');
        card.classList.add('bg-white', 'border-slate-200');
        
        label.classList.remove('text-white');
        label.classList.add('text-slate-800');
        
        statusText.classList.remove('text-teal-300');
        statusText.classList.add('text-slate-400');
        statusText.innerText = 'Result: NON-REFUNDABLE';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Generate Reference Script
    window.generateReference = function() {
        const timestamp = Math.floor(Date.now() / 1000); 
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        document.getElementById('reference_no').value = 'EXP-' + timestamp + random;
    };

    // Form Validation (Matches bank_add.php style)
    const form = document.getElementById('expenseForm');
    const requiredIds = ['title', 'amount']; // Note: category is custom handled

    form.addEventListener('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // Reset errors
        document.querySelectorAll('.error-msg').forEach(el => el.classList.add('hidden'));

        // Check Text Inputs
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

        // Check Category (Custom)
        const catId = document.getElementById('category_id').value;
        if(!catId) {
             document.getElementById('cat_error').classList.remove('hidden');
             const catContainer = document.getElementById('category_selector_container');
             catContainer.classList.add('border-red-500', 'ring-2', 'ring-red-200');
             isValid = false;
             if(!firstError) firstError = catContainer;
        }

        // Check Stores Checkboxes
        const checkboxes = document.querySelectorAll('input[name="stores[]"]');
        const checkedOne = Array.prototype.slice.call(checkboxes).some(x => x.checked);
        if(!checkedOne) {
            document.getElementById('store_error').classList.remove('hidden');
            isValid = false;
            // Scroll to store section if it's the first error
            const storeSection = document.getElementById('store_error').closest('.bg-white');
             if(!firstError && storeSection) firstError = storeSection;
        }

        if(!isValid) {
            e.preventDefault();
            if(firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if(firstError.focus) firstError.focus();
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
    
    // Clear store error on checkbox change
    const checkboxes = document.querySelectorAll('input[name="stores[]"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const checkedOne = Array.prototype.slice.call(checkboxes).some(x => x.checked);
            if(checkedOne) {
                document.getElementById('store_error').classList.add('hidden');
            }
        });
    });
});

// File Upload Logic (Simplified matching bank_add doesn't explicitly have it but add request did)
const dt = new DataTransfer();
const fileInput = document.getElementById('docs_upload');
const previewContainer = document.getElementById('preview_container');

function handleFileSelect(input) {
    if (!input.files.length) return;
    for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        dt.items.add(file);
        createPreview(file);
    }
    input.files = dt.files;
}

function createPreview(file) {
    const div = document.createElement('div');
    div.className = 'p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3 mb-2';
    
    div.innerHTML = `
        <div class="w-8 h-8 rounded bg-slate-200 flex items-center justify-center text-slate-500 shrink-0">
            <i class="fas fa-file"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[10px] font-bold text-slate-700 truncate">${file.name}</p>
            <p class="text-[9px] text-slate-400">${(file.size/1024).toFixed(1)} KB</p>
        </div>
        <button type="button" class="text-red-500 hover:text-red-700 p-1" onclick="removeFile('${file.name}', this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    previewContainer.appendChild(div);
}

function removeFile(fileName, btn) {
    const newDt = new DataTransfer();
    for (let i = 0; i < dt.items.length; i++) {
        if (fileInput.files[i].name !== fileName) {
            newDt.items.add(fileInput.files[i]);
        }
    }
    dt.items.clear();
    for (let i = 0; i < newDt.items.length; i++) {
        dt.items.add(newDt.items[i].getAsFile());
    }
    fileInput.files = dt.files;
    btn.closest('div').remove();
}
</script>
<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$mode = "create";
$btn_text = "Save Expense";
$page_title = "New Expense";

$d = [
    'id' => '',
    'store_id' => '',
    'reference_no' => 'EXP-' . time(),
    'category_id' => '',
    'title' => '',
    'amount' => '',
    'returnable' => '0',
    'note' => '',
    'attachment' => '',
    'status' => '1'
];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_text = "Update Expense";
    $page_title = "Edit Expense";
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $res = mysqli_query($conn, "SELECT * FROM expenses WHERE id='$id' LIMIT 1");
    if($row = mysqli_fetch_assoc($res)){
        $d = array_merge($d, $row);
    }
}

// Fetch Categories & Stores (Joined with Currency)
$categories = mysqli_query($conn, "SELECT category_id, category_name FROM expense_category WHERE status='1' ORDER BY category_name ASC");

// Fetch active stores with Currency Symbol
$stores_query = "SELECT s.id, s.store_name, c.symbol_left, c.symbol_right 
                 FROM stores s 
                 LEFT JOIN currencies c ON s.currency_id = c.id 
                 WHERE s.status=1 ORDER BY s.store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$all_stores = [];
$store_currencies = []; // Map store_id => symbol

while($store = mysqli_fetch_assoc($stores_result)) { 
    $all_stores[] = $store; 
    // Determine symbol
    $sym = $store['symbol_left'] ?: $store['symbol_right'];
    $store_currencies[$store['id']] = $sym ?: '৳'; // Default fallback
}

// For Edit Mode: Pre-select store
$selected_stores = [];
if($mode == 'edit' && !empty($d['store_id'])) {
    $selected_stores[] = $d['store_id']; 
}

include('../includes/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="/pos/assets/css/expenditureCss/add_expenseCss.css">

<style>
/* Additional Styles for File Preview */
.file-preview-card {
    transition: all 0.2s;
    position: relative;
    overflow: hidden;
}
.file-preview-card:hover .remove-file-btn {
    opacity: 1;
}
.remove-file-btn {
    opacity: 0;
    transition: opacity 0.2s;
    background: rgba(239, 68, 68, 0.9);
    color: white;
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
                        <a href="/pos/expenditure/expense_list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>Track and manage your business expenditures</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="/pos/expenditure/save_expense" method="POST" enctype="multipart/form-data" id="expenseForm" novalidate autocomplete="off">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="expense_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="old_attachment" value="<?= $d['attachment'] ?>">
                        <input type="hidden" name="update_expense_btn" value="true">
                    <?php else: ?>
                        <input type="hidden" name="save_expense_btn" value="true">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <div class="lg:col-span-8 space-y-6">
                            <div class="glass-card rounded-2xl p-8 shadow-xl border border-slate-200 slide-in delay-1 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-orange-500 text-white flex items-center justify-center text-lg shadow-lg shadow-rose-200"><i class="fas fa-file-invoice-dollar"></i></span>
                                    Expense Details
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2 relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Expense Title *</label>
                                        <input type="text" name="title" id="title" value="<?= htmlspecialchars($d['title']); ?>" 
                                            class="peer block py-4 px-5 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all font-semibold" placeholder="What was this expense for?">
                                        <div class="error-msg-text" id="error-title"><i class="fas fa-exclamation-circle"></i> Title is required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Category *</label>
                                        <select name="category_id" id="category_id" class="select2">
                                            <option value="">-- Select Category --</option>
                                            <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                                                <option value="<?= $cat['category_id'] ?>" <?= $d['category_id']==$cat['category_id']?'selected':'' ?>><?= $cat['category_name'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="error-msg-text" id="error-category_id"><i class="fas fa-exclamation-circle"></i> Category is required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Amount *</label>
                                        <div class="relative">
                                            <span id="currency-display" class="absolute left-5 top-1/2 -translate-y-1/2 font-bold text-slate-400">/=</span>
                                            <input type="number" name="amount" id="amount" value="<?= htmlspecialchars($d['amount']); ?>" step="0.01"
                                                class="peer block py-4 pl-12 pr-5 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all font-bold" placeholder="0.00">
                                        </div>
                                        <div class="error-msg-text" id="error-amount"><i class="fas fa-exclamation-circle"></i> Valid amount is required</div>
                                    </div>

                                    <div class="md:col-span-2 relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Note / Description</label>
                                        <textarea name="note" rows="3" 
                                            class="peer block py-4 px-5 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all resize-none" 
                                            placeholder="Additional details..."><?= htmlspecialchars($d['note']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-6">
                            <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-200 slide-in delay-2 bg-white">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Organizational Info</h3>
                                
                                <div class="space-y-6">
                                    <div class="relative w-full group">
                                        <!-- Reusable Store Selection Component -->
                                        <?php 
                                            $store_label = "Expense Available In"; 
                                            $search_placeholder = "Search branches...";
                                            include('../includes/store_select_component.php'); 
                                        ?>
                                        <div class="error-msg-text" id="error-store_id"><i class="fas fa-exclamation-circle"></i> Please select at least one store</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Reference No</label>
                                        <div class="relative flex items-center">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                <i class="fas fa-barcode"></i>
                                            </div>
                                            <input type="text" name="reference_no" id="reference_no" value="<?= htmlspecialchars($d['reference_no']); ?>" 
                                                class="peer block w-full pl-10 pr-12 py-3 text-sm text-slate-600 bg-slate-50 rounded-l-xl border border-slate-200 border-r-0 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all font-mono tracking-wider placeholder-slate-400" 
                                                <?= $mode == 'edit' ? 'readonly style="background-color: #f1f5f9; cursor: not-allowed;"' : 'placeholder="SCAN OR ENTER CODE"' ?>>
                                            
                                            <?php if($mode != 'edit'): ?>
                                                <button type="button" onclick="generateReference()" class="h-[46px] w-12 bg-teal-700 hover:bg-teal-800 text-white rounded-r-xl flex items-center justify-center transition-colors shadow-sm" title="Generate New Reference">
                                                    <i class="fas fa-random"></i>
                                                </button>
                                            <?php else: ?>
                                                <div class="h-[46px] w-12 bg-slate-200 text-slate-400 rounded-r-xl flex items-center justify-center border border-slate-200 border-l-0">
                                                    <i class="fas fa-lock"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($mode != 'edit'): ?>
                                            <p class="text-[10px] text-slate-400 mt-1 cursor-pointer hover:text-teal-600 transition-colors" onclick="generateReference()">Click icon to auto-generate</p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Returnable Toggle Design -->
                                    <div class="relative w-full group">
                                        <div class="glass-card rounded-xl p-4 border border-slate-200 bg-slate-50 flex items-center justify-between transition-colors duration-300" id="returnable-card">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-800 mb-1">Returnable?</label>
                                                <p class="text-[10px] uppercase tracking-wider font-semibold text-slate-500" id="returnable-status-text">Is this amount refundable later?</p>
                                            </div>
                                            
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="returnable" id="returnable-toggle" value="1" class="sr-only peer" <?= $d['returnable'] == '1' ? 'checked' : ''; ?> onchange="toggleReturnableStyle()">
                                                <div class="w-14 h-8 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-teal-800"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card rounded-2xl p-6 shadow-xl border border-slate-200 slide-in delay-3 bg-white">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Attachments</h3>
                                <div class="upload-box relative rounded-xl p-8 text-center cursor-pointer group border-2 border-dashed border-slate-200 hover:border-teal-400 transition-colors" id="drop-zone">
                                    <input type="file" name="attachment[]" id="docs_upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" multiple onchange="handleFileSelect(this)">
                                    <div id="upload-placeholder-icon" class="text-slate-300 group-hover:scale-110 transition-transform mb-2">
                                        <i class="fas fa-cloud-upload-alt text-4xl group-hover:text-teal-500"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-500 group-hover:text-teal-600">Click to upload Receipt (PDF/Image)</p>
                                    <p class="mt-1 text-[9px] text-slate-400">Supports multiple files</p>
                                </div>
                                
                                <!-- Preview Container -->
                                <div id="preview_container" class="mt-4 space-y-2">
                                    <?php if(!empty($d['attachment'])): 
                                        $existing_files = explode(',', $d['attachment']); // Assuming comma separated
                                        foreach($existing_files as $att): 
                                            if(empty($att)) continue;
                                    ?>
                                        <div class="file-preview-card p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center gap-3">
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

                            <div class="slide-in delay-3 pt-2">
                                <button type="submit" 
                                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-teal-600 to-teal-800 hover:from-teal-700 hover:to-teal-900 text-white font-bold text-lg shadow-lg hover:shadow-teal-500/30 transition-all duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-0.5 group">
                                    <i class="fas fa-paper-plane group-hover:rotate-12 transition-transform"></i>
                                    <span><?= $btn_text; ?></span>
                                </button>
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
<script src="/pos/assets/js/expenditureJs/add_expenseJs.js"></script>

<script>
// Currency Data from PHP
const STORE_CURRENCIES = <?= json_encode($store_currencies); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // 1. Currency Logic
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    const currencyDisplay = document.getElementById('currency-display');
    const selectAllBtn = document.getElementById('selectAllStores');

    function updateCurrency() {
        const checkedStores = Array.from(storeCheckboxes).filter(cb => cb.checked);
        
        if (checkedStores.length === 0) {
            currencyDisplay.innerText = '/='; // Default when nothing selected
        } else if (checkedStores.length === 1) {
            const sid = checkedStores[0].value;
            currencyDisplay.innerText = STORE_CURRENCIES[sid] || '৳';
        } else {
            // Multiple stores selected
            currencyDisplay.innerText = '/='; 
        }
    }

    storeCheckboxes.forEach(cb => cb.addEventListener('change', updateCurrency));
    if(selectAllBtn) selectAllBtn.addEventListener('change', updateCurrency);
    
    // Initial check
    updateCurrency();
});

// 2. File Upload Logic (DataTransfer for Delete)
const dt = new DataTransfer();
const fileInput = document.getElementById('docs_upload');
const previewContainer = document.getElementById('preview_container');

function handleFileSelect(input) {
    if (!input.files.length) return;

    for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        // Add to DataTransfer
        dt.items.add(file);
        
        // Create Preview
        createPreview(file, dt.items.length - 1);
    }
    
    // Update input files
    input.files = dt.files;
}

function createPreview(file, index) {
    const isImage = file.type.startsWith('image/');
    const reader = new FileReader();

    const div = document.createElement('div');
    div.className = 'file-preview-card p-2 rounded-lg bg-white border border-slate-200 flex items-center gap-3 shadow-sm mb-2';
    div.dataset.name = file.name; // Use name as ID for removal matching if needed, or just reliable index is hard

    // Icon/Image
    const iconDiv = document.createElement('div');
    iconDiv.className = 'w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 overflow-hidden shrink-0 relative';
    
    if (isImage) {
        const img = document.createElement('img');
        img.className = 'w-full h-full object-cover';
        reader.onload = (e) => { img.src = e.target.result; };
        reader.readAsDataURL(file);
        iconDiv.appendChild(img);
    } else {
        iconDiv.innerHTML = '<i class="fas fa-file-pdf text-red-500"></i>';
    }

    // Info
    const infoDiv = document.createElement('div');
    infoDiv.className = 'flex-1 min-w-0';
    infoDiv.innerHTML = `
        <p class="text-[11px] font-bold text-slate-700 truncate">${file.name}</p>
        <p class="text-[9px] text-slate-400 uppercase">${(file.size/1024).toFixed(1)} KB</p>
    `;

    // Delete Button
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'remove-file-btn w-6 h-6 rounded-full flex items-center justify-center text-xs absolute right-2 top-1/2 -translate-y-1/2';
    delBtn.innerHTML = '<i class="fas fa-times"></i>';
    delBtn.onclick = function() {
        removeFile(file.name, div);
    };

    div.appendChild(iconDiv);
    div.appendChild(infoDiv);
    div.appendChild(delBtn);
    previewContainer.appendChild(div);
}

function removeFile(fileName, element) {
    // Create new DataTransfer
    const newDt = new DataTransfer();
    
    // Copy all files EXCEPT the one to remove
    for (let i = 0; i < dt.items.length; i++) {
        if (fileInput.files[i].name !== fileName) {
            newDt.items.add(fileInput.files[i]);
        }
    }
    
    // Update Global dt and input
    dt.items.clear();
    for (let i = 0; i < newDt.items.length; i++) {
        dt.items.add(newDt.items[i].getAsFile());
    }
    fileInput.files = dt.files;
    
    // Remove UI
    element.remove();
}

// 3. Generate Reference
function generateReference() {
    const timestamp = Math.floor(Date.now() / 1000); // Unix timestamp
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    document.getElementById('reference_no').value = 'EXP-' + timestamp + random;
}

// 4. Returnable Toggle Style
function toggleReturnableStyle() {
    const toggle = document.getElementById('returnable-toggle');
    const card = document.getElementById('returnable-card');
    const text = document.getElementById('returnable-status-text');
    const label = card.querySelector('label');

    if(toggle.checked) {
        // Active State (Dark Green) - Remove glass-card to force solid color
        card.className = "rounded-xl p-4 border border-teal-900 bg-teal-900 flex items-center justify-between transition-colors duration-300 shadow-lg";
        label.className = "block text-sm font-bold text-white mb-1";
        text.className = "text-[10px] uppercase tracking-wider font-semibold text-teal-200";
        text.innerText = "REFUNDABLE AMOUNT";
    } else {
        // Inactive State (Default)
        card.className = "glass-card rounded-xl p-4 border border-slate-200 bg-slate-50 flex items-center justify-between transition-colors duration-300";
        label.className = "block text-sm font-bold text-slate-800 mb-1";
        text.className = "text-[10px] uppercase tracking-wider font-semibold text-slate-500";
        text.innerText = "NON-REFUNDABLE";
    }
}

// Initial Call
toggleReturnableStyle();

// Initialize Select2 with Custom Styling and Limit
$(document).ready(function() {
    $('.select2').select2({
        width: '100%',
        minimumResultsForSearch: 0 // Always show search
    });
});
</script>

<style>
/* Select2 Customization for Teal Theme */
.select2-container--default .select2-selection--single {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem; /* rounded-xl */
    height: 54px;
    display: flex;
    align-items: center;
    transition: all 0.3s;
}
.select2-container--default .select2-selection--single:hover {
    border-color: #0d9488; /* teal-600 */
}
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1e293b;
    font-weight: 600;
    font-size: 0.875rem;
    padding-left: 1.25rem;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 52px;
    right: 15px;
}
.select2-dropdown {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    z-index: 9999;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 8px 12px;
    outline: none;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #0d9488;
    box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.1);
}
.select2-results__options {
    max-height: 200px; /* Approx 5 items (40px each) */
    overflow-y: auto;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #f0fdfa; /* teal-50 */
    color: #0f766e; /* teal-700 */
    font-weight: 600;
}
.select2-container--default .select2-results__option--selected {
    background-color: #ccfbf1; /* teal-100 */
    color: #115e59;
}
</style>

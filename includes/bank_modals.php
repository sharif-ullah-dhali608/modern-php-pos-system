<?php
// Fetch Dropdown Data
$bank_accounts = [];
$income_sources = [];
$expense_categories = [];

// Only fetch if connection exists
if(isset($conn)) {
    // Bank Accounts
    $ba_q = mysqli_query($conn, "SELECT id, account_name, account_no FROM bank_accounts WHERE status=1");
    if($ba_q) {
        while($row = mysqli_fetch_assoc($ba_q)) {
            $bank_accounts[] = $row;
        }
    }

    // Income Sources
    $is_q = mysqli_query($conn, "SELECT source_id as id, source_name as name FROM income_sources WHERE status=1");
    if($is_q) {
        while($row = mysqli_fetch_assoc($is_q)) {
            $income_sources[] = $row;
        }
    }

    // Expense Categories
    $ec_q = mysqli_query($conn, "SELECT category_id as id, category_name as name FROM expense_category WHERE status=1");
    if($ec_q) {
        while($row = mysqli_fetch_assoc($ec_q)) {
            $expense_categories[] = $row;
        }
    }
}
?>

<!-- Shared Styles for Custom Dropdown -->
<style>
    .custom-dropdown-results::-webkit-scrollbar { width: 6px; }
    .custom-dropdown-results::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-dropdown-results::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-dropdown-results::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    /* Remove Spinners from Number Input */
    .no-spinner::-webkit-inner-spin-button, 
    .no-spinner::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    .no-spinner { 
        -moz-appearance: textfield; 
    }
</style>

<!-- Deposit Modal -->
<div id="depositModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('depositModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Removed overflow-hidden to allow dropdowns to overflow if needed, managing rounded corners on header/footer -->
            <div class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-teal-100">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-teal-500 to-emerald-600 px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i> Deposit to Bank
                    </h3>
                    <button type="button" class="text-white/80 hover:text-white transition-colors" onclick="closeModal('depositModal')">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="/pos/Accounting/bank_transaction_handler.php" method="POST" enctype="multipart/form-data" id="depositForm" novalidate>
                    <input type="hidden" name="action" value="deposit">
                    <div class="px-6 py-6 space-y-4">
                        
                        <!-- Attachment Preview -->
                        <div class="flex justify-center mb-4">
                             <div class="w-32 h-32 border-2 border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-slate-400 cursor-pointer hover:border-teal-500 hover:text-teal-500 transition-all bg-slate-50 relative overflow-hidden" onclick="document.getElementById('dep_attachment').click()">
                                <img id="dep_preview" class="hidden w-full h-full object-cover">
                                <div id="dep_placeholder" class="text-center p-2">
                                    <i class="fas fa-image text-3xl mb-1"></i>
                                    <span class="text-xs block">Click to Upload</span>
                                </div>
                                <input type="file" name="attachment" id="dep_attachment" class="hidden" accept="image/*" onchange="previewFile(this, 'dep_preview', 'dep_placeholder')">
                            </div>
                        </div>

                        <!-- Income Source (Custom) -->
                        <div class="relative group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Income Source</label>
                            <input type="hidden" name="source_id" id="dep_source_id">
                            <div class="relative">
                                <input type="text" id="dep_source_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            <div id="dep_source_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1">
                                <!-- Results -->
                            </div>
                        </div>

                        <!-- Account (Custom) -->
                        <div class="relative group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Account <span class="text-red-500">*</span></label>
                            <input type="hidden" name="account_id" id="dep_account_id">
                            <div class="relative">
                                <input type="text" id="dep_account_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                             <span class="text-red-500 text-xs hidden error-msg">This field is required</span>
                            <div id="dep_account_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1">
                                <!-- Results -->
                            </div>
                        </div>

                        <!-- Ref No & About -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Ref. No. <i class="fas fa-question-circle text-teal-500" title="Reference Number"></i> <span class="text-red-500">*</span></label>
                                <input type="text" name="ref_no" id="dep_ref_no" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-medium">
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            </div>
                             <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">About <span class="text-red-500">*</span></label>
                                <input type="text" name="title" id="dep_title" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-medium">
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="amount" id="dep_amount" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-bold text-teal-700 no-spinner">
                            <span class="text-red-500 text-xs hidden error-msg">Required</span>
                        </div>

                        <!-- Details -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Details</label>
                            <textarea name="details" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all text-sm font-medium"></textarea>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-slate-50 flex justify-end rounded-b-2xl">
                        <button type="submit" class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-teal-500/30 flex items-center justify-center gap-2">
                             <i class="fas fa-plus"></i> Deposit Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdrawModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('withdrawModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-orange-100">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-minus-circle"></i> Withdraw from Bank
                    </h3>
                    <button type="button" class="text-white/80 hover:text-white transition-colors" onclick="closeModal('withdrawModal')">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="/pos/Accounting/bank_transaction_handler.php" method="POST" enctype="multipart/form-data" id="withdrawForm" novalidate>
                    <input type="hidden" name="action" value="withdraw">
                    <div class="px-6 py-6 space-y-4">
                        
                         <!-- Attachment Preview -->
                        <div class="flex justify-center mb-4">
                             <div class="w-32 h-32 border-2 border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-slate-400 cursor-pointer hover:border-orange-500 hover:text-orange-500 transition-all bg-slate-50 relative overflow-hidden" onclick="document.getElementById('with_attachment').click()">
                                <img id="with_preview" class="hidden w-full h-full object-cover">
                                <div id="with_placeholder" class="text-center p-2">
                                    <i class="fas fa-image text-3xl mb-1"></i>
                                    <span class="text-xs block">Click to Upload</span>
                                </div>
                                <input type="file" name="attachment" id="with_attachment" class="hidden" accept="image/*" onchange="previewFile(this, 'with_preview', 'with_placeholder')">
                            </div>
                        </div>

                        <!-- Expense Category (Custom) -->
                         <div class="relative group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Expense Category <span class="text-red-500">*</span></label>
                            <input type="hidden" name="exp_category_id" id="with_exp_id">
                            <div class="relative">
                                <input type="text" id="with_exp_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                             <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            <div id="with_exp_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1">
                                <!-- Results -->
                            </div>
                        </div>

                        <!-- Account (Custom) -->
                        <div class="relative group">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Account <span class="text-red-500">*</span></label>
                            <input type="hidden" name="account_id" id="with_account_id">
                            <div class="relative">
                                <input type="text" id="with_account_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                             <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            <div id="with_account_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1">
                                <!-- Results -->
                            </div>
                        </div>

                         <!-- Ref No & About -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Ref. No. <i class="fas fa-question-circle text-orange-500"></i> <span class="text-red-500">*</span></label>
                                <input type="text" name="ref_no" id="with_ref_no" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-medium">
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            </div>
                             <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">About <span class="text-red-500">*</span></label>
                                <input type="text" name="title" id="with_title" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-medium">
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="amount" id="with_amount" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-bold text-orange-700 no-spinner">
                            <span class="text-red-500 text-xs hidden error-msg">Required</span>
                        </div>

                         <!-- Details -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Details</label>
                            <textarea name="details" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all text-sm font-medium"></textarea>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-slate-50 rounded-b-2xl flex justify-end">
                        <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-orange-500/30 flex items-center justify-center gap-2">
                             <i class="fas fa-paper-plane"></i> Withdraw Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div id="transferModal" class="fixed inset-0 z-[100] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('transferModal')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-blue-100">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-exchange-alt"></i> Transfer Balance
                    </h3>
                    <button type="button" class="text-white/80 hover:text-white transition-colors" onclick="closeModal('transferModal')">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="/pos/Accounting/bank_transaction_handler.php" method="POST" id="transferForm" novalidate>
                    <input type="hidden" name="action" value="transfer">
                    <div class="px-6 py-6 space-y-4">
                        
                        <!-- From & To Accounts -->
                        <div class="grid grid-cols-1 gap-4">
                            <!-- From Account -->
                             <div class="relative group">
                                <label class="block text-sm font-semibold text-slate-700 mb-1">From Account <span class="text-red-500">*</span></label>
                                <input type="hidden" name="from_account_id" id="tr_from_id">
                                <div class="relative">
                                    <input type="text" id="tr_from_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                                <div id="tr_from_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1"></div>
                            </div>

                            <div class="flex justify-center -my-2 z-10">
                                <div class="bg-blue-50 rounded-full p-2 border border-blue-100">
                                    <i class="fas fa-arrow-down text-blue-500"></i>
                                </div>
                            </div>
                            
                            <!-- To Account -->
                            <div class="relative group">
                                <label class="block text-sm font-semibold text-slate-700 mb-1">To Account <span class="text-red-500">*</span></label>
                                <input type="hidden" name="account_id" id="tr_to_id">
                                <div class="relative">
                                    <input type="text" id="tr_to_search" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium placeholder:font-normal cursor-pointer" placeholder="--- Please Select ---" autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none transition-transform duration-200 chevron-icon">
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </div>
                                </div>
                                <span class="text-red-500 text-xs hidden error-msg">Required</span>
                                <div id="tr_to_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-dropdown-results p-1"></div>
                            </div>
                        </div>

                         <!-- Ref No & About -->
                        <div class="grid grid-cols-2 gap-4 mt-2">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Ref. No. <i class="fas fa-question-circle text-blue-500"></i></label>
                                <input type="text" name="ref_no" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium">
                            </div>
                             <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">About</label>
                                <input type="text" name="title" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium">
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="amount" id="tr_amount" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-bold text-blue-700 no-spinner">
                            <span class="text-red-500 text-xs hidden error-msg">Required</span>
                        </div>

                         <!-- Details -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Details</label>
                            <textarea name="details" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm font-medium"></textarea>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-slate-50 rounded-b-2xl flex justify-end">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-xl transition-all shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                             <i class="fas fa-random"></i> Transfer Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Data from PHP
const accountsData = <?= json_encode($bank_accounts); ?>;
const incomeSourcesData = <?= json_encode($income_sources); ?>;
const expenseData = <?= json_encode($expense_categories); ?>;

document.addEventListener('DOMContentLoaded', function() {
    
    // --- Reusable Custom Dropdown Component ---
    function setupCustomDropdown(config) {
        const { searchId, inputId, dropdownId, data, placeholderText = '--- Please Select ---' } = config;
        
        const searchEl = document.getElementById(searchId);
        const inputEl = document.getElementById(inputId);
        const dropdownEl = document.getElementById(dropdownId);
        
        if(!searchEl || !dropdownEl) return;
        
        const chevronEl = searchEl.nextElementSibling; // Assuming structure
        
        // Render Function
        function renderItems(items) {
            if(items.length > 0) {
                let html = '';
                items.forEach(item => {
                    // For Bank Accounts, show Account No
                    const displayName = item.account_no 
                                      ? `${item.account_name} <span class="text-slate-400 text-xs ml-1 font-normal">(${item.account_no})</span>` 
                                      : item.name || item.account_name;
                    
                    const searchValue = (item.name || item.account_name).toLowerCase();
                    const idValue = item.id;
                    
                    html += `
                        <div class="dropdown-item px-4 py-3 hover:bg-slate-50 cursor-pointer transition-colors border-b border-slate-50 last:border-b-0 rounded-lg font-medium text-slate-700 text-sm flex items-center gap-2" 
                             data-id="${idValue}" 
                             data-name="${item.name || item.account_name}">
                             ${displayName}
                        </div>
                    `;
                });
                dropdownEl.innerHTML = html;
                
                // Add Click Listeners
                dropdownEl.querySelectorAll('.dropdown-item').forEach(el => {
                    el.addEventListener('click', function() {
                        const name = this.getAttribute('data-name');
                        const id = this.getAttribute('data-id');
                        
                        searchEl.value = name;
                        inputEl.value = id;
                        
                        // Error handling (remove error)
                        const errorMsg = searchEl.closest('.relative.group').querySelector('.error-msg');
                        if(errorMsg) errorMsg.classList.add('hidden');
                        
                        dropdownEl.classList.add('hidden');
                        if(chevronEl) chevronEl.classList.remove('rotate-180');
                    });
                });
                
            } else {
                dropdownEl.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs font-bold uppercase tracking-wide">No results found</div>';
            }
        }
        
        // Focus Event
        searchEl.addEventListener('focus', function() {
            dropdownEl.classList.remove('hidden');
            if(chevronEl) chevronEl.classList.add('rotate-180');
            renderItems(data.slice(0, 7)); // Show top 7 initially
        });
        
        // Blur / Click Outside handled generically below or via timeout
        // Use document click listener for robust closing
        
        // Search Input
        searchEl.addEventListener('input', function() {
             const val = this.value.toLowerCase();
             const filtered = data.filter(item => {
                 const name = (item.name || item.account_name).toLowerCase();
                 const no = (item.account_no || '').toLowerCase();
                 return name.includes(val) || no.includes(val);
             });
             renderItems(filtered.slice(0, 10));
             
             // Clear ID if typing and invalid
             if(!filtered.find(f => (f.name || f.account_name).toLowerCase() === val)) {
                 inputEl.value = '';
             }
        });
        
        // Close when clicking outside specific dropdown
        document.addEventListener('click', function(e) {
            if(!searchEl.contains(e.target) && !dropdownEl.contains(e.target)) {
                 dropdownEl.classList.add('hidden');
                 if(chevronEl) chevronEl.classList.remove('rotate-180');
            }
        });
    }

    // Initialize Dropdowns
    setupCustomDropdown({
        searchId: 'dep_source_search',
        inputId: 'dep_source_id',
        dropdownId: 'dep_source_dropdown',
        data: incomeSourcesData
    });
    
    setupCustomDropdown({
        searchId: 'dep_account_search',
        inputId: 'dep_account_id',
        dropdownId: 'dep_account_dropdown',
        data: accountsData
    });
    
    setupCustomDropdown({
        searchId: 'with_exp_search',
        inputId: 'with_exp_id',
        dropdownId: 'with_exp_dropdown',
        data: expenseData
    });
    
    setupCustomDropdown({
        searchId: 'with_account_search',
        inputId: 'with_account_id',
        dropdownId: 'with_account_dropdown',
        data: accountsData
    });
    
    setupCustomDropdown({
        searchId: 'tr_from_search',
        inputId: 'tr_from_id',
        dropdownId: 'tr_from_dropdown',
        data: accountsData
    });
    
    setupCustomDropdown({
        searchId: 'tr_to_search',
        inputId: 'tr_to_id',
        dropdownId: 'tr_to_dropdown',
        data: accountsData
    });


    // --- Generic Form Validation ---
    function setupFormValidation(formId, requiredIds) {
        const form = document.getElementById(formId);
        if(!form) return;
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            requiredIds.forEach(id => {
                const el = document.getElementById(id);
                // Check hidden inputs for dropdowns
                if(!el.value || el.value.trim() === '') {
                    isValid = false;
                    // Find container error message
                    // Using closest group or parent
                    let container = el.closest('.group') || el.closest('div');
                    let errorMsg = container.querySelector('.error-msg');
                    if(errorMsg) errorMsg.classList.remove('hidden');
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                // Find first visible error
                const firstError = form.querySelector('.error-msg:not(.hidden)');
                if(firstError) {
                    firstError.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Clear errors on input
        requiredIds.forEach(id => {
            const el = document.getElementById(id);
             // For hidden inputs, the dropdown logic clears error. 
             // For text inputs:
             if(el.type !== 'hidden') {
                 el.addEventListener('input', function() {
                     if(this.value.trim()) {
                         let container = this.closest('div');
                         let errorMsg = container.querySelector('.error-msg');
                         if(errorMsg) errorMsg.classList.add('hidden');
                     }
                 });
             }
        });
    }

    // Validation Init
    setupFormValidation('depositForm', ['dep_account_id', 'dep_ref_no', 'dep_title', 'dep_amount']);
    setupFormValidation('withdrawForm', ['with_exp_id', 'with_account_id', 'with_ref_no', 'with_title', 'with_amount']);
    setupFormValidation('transferForm', ['tr_from_id', 'tr_to_id', 'tr_amount']);

});

window.openModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
    }
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = ''; 
    }
}

window.previewFile = function(input, previewId, placeholderId) {
    const preview = document.getElementById(previewId);
    const placeholder = document.getElementById(placeholderId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>

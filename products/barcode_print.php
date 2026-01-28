<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Barcode Print Control";
include('../includes/header.php');

$user_id = $_SESSION['auth_user']['user_id'];
$user_role = $_SESSION['auth_user']['role_as'];

// Fetch allowed stores
$allowed_stores_q = mysqli_query($conn, "SELECT s.id, s.store_name FROM stores s 
                                       JOIN user_store_map usm ON s.id = usm.store_id 
                                       WHERE usm.user_id = '$user_id' AND s.status = '1'");

// Fallback removed to ensure strict mapping compliance. 
// If an admin has no mappings, they MUST be assigned to see stores.

$allowed_stores = [];
while($row = mysqli_fetch_assoc($allowed_stores_q)) {
    $allowed_stores[] = $row;
}

$selected_store_id = null;
$store = null;
$currency_symbol = '$';

if (!empty($allowed_stores)) {
    // Check if session store is in allowed list, else pick first allowed
    $allowed_ids = array_column($allowed_stores, 'id');
    $selected_store_id = (isset($_SESSION['store_id']) && in_array($_SESSION['store_id'], $allowed_ids)) 
                         ? $_SESSION['store_id'] 
                         : $allowed_stores[0]['id'];

    $current_store_q = mysqli_query($conn, "SELECT s.*, c.symbol_left, c.symbol_right FROM stores s 
                                             LEFT JOIN currencies c ON s.currency_id = c.id 
                                             WHERE s.id = '$selected_store_id'");
    $store = mysqli_fetch_assoc($current_store_q);
    $currency_symbol = $store['symbol_left'] ?: ($store['symbol_right'] ?: '$');
}
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-[#f8fafc]">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-8 max-w-7xl mx-auto">
                
                <div class="mb-8 slide-in">
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">Barcode Generate</h1>
                    <p class="text-slate-500 font-medium">Create professional product labels for your inventory.</p>
                </div>

                <?php if(empty($allowed_stores)): ?>
                <!-- ACCESS DENIED STATE -->
                <div class="bg-white rounded-[3rem] p-12 shadow-2xl shadow-slate-200/40 text-center border-2 border-slate-50 slide-in">
                    <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-lock text-rose-500 text-4xl"></i>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">No Store Access Assigned</h2>
                    <p class="text-slate-500 font-medium max-w-md mx-auto mb-8">
                        Your account has not been assigned to any stores yet. Please contact your administrator to grant access to the appropriate branch.
                    </p>
                    <a href="/pos" class="inline-flex items-center gap-3 bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <?php else: ?>

                <!-- Unique Add Product Search Section -->
                <div class="bg-[#cbd5e1]/30 rounded-[2.5rem] p-4 mb-8 shadow-sm border border-white/50 backdrop-blur-sm">
                    <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] items-center gap-6 px-10 py-4">
                         <div class="flex items-center gap-4">
                              <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-400 border border-slate-100">
                                 <i class="fas fa-barcode text-2xl"></i>
                              </div>
                              <span class="text-lg font-extrabold text-slate-700 tracking-tight">Add Product</span>
                         </div>
                         
                         <div class="flex items-center gap-4">
                             <?php if(empty($allowed_stores)): ?>
                                 <div class="bg-rose-50 border border-rose-100 rounded-2xl px-6 py-4 flex items-center gap-3">
                                     <i class="fas fa-exclamation-circle text-rose-500 text-xl"></i>
                                     <span class="text-rose-600 font-bold text-sm">No Store Permission Found!</span>
                                 </div>
                             <?php elseif(count($allowed_stores) > 1): ?>
                             <!-- Custom Store Search Dropdown -->
                             <div class="relative group" id="store_selector_container">
                                 <div class="flex items-center bg-white border-2 border-slate-100 focus-within:border-teal-500 rounded-2xl shadow-sm transition-all min-w-[240px]">
                                     <div class="pl-4 text-slate-400">
                                         <i class="fas fa-store-alt text-sm"></i>
                                     </div>
                                     <input 
                                         type="text" 
                                         id="store_search_input" 
                                         placeholder="Search Store..." 
                                         class="w-full bg-transparent border-none rounded-2xl px-3 py-4 outline-none font-bold text-slate-700 text-sm"
                                         value="<?= htmlspecialchars($store['store_name']); ?>"
                                         autocomplete="off"
                                     >
                                     <div class="pr-4 text-slate-300">
                                         <i class="fas fa-chevron-down text-[10px]"></i>
                                     </div>
                                 </div>
                                 
                                 <div id="store_dropdown" class="absolute left-0 right-0 mt-3 bg-white border-2 border-teal-500 rounded-[1.5rem] max-h-[250px] overflow-y-auto hidden shadow-2xl z-[10000] overflow-hidden">
                                     <div id="store_results_container" class="p-1 custom-scroll scroll-teal">
                                         <?php 
                                         $count = 0;
                                         foreach($allowed_stores as $as): 
                                             if($count >= 5) break; // Limit to 5 initial view as requested
                                         ?>
                                             <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 last:border-b-0 flex items-center gap-3 rounded-xl" 
                                                  data-id="<?= $as['id']; ?>" 
                                                  data-name="<?= htmlspecialchars($as['store_name']); ?>">
                                                 <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-black text-xs">
                                                     <?= strtoupper(substr($as['store_name'], 0, 1)); ?>
                                                 </div>
                                                 <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($as['store_name']); ?></div>
                                             </div>
                                         <?php 
                                             $count++;
                                         endforeach; ?>
                                     </div>
                                 </div>
                             </div>
                             <?php endif; ?>
                        
                        <div class="relative group flex-1">
                            <input 
                                type="text" 
                                id="product_search" 
                                placeholder="<?= $selected_store_id ? 'Search Product by Name or Code...' : 'No Store Permission - Search Disabled'; ?>" 
                                class="w-full bg-white border-2 border-transparent focus:border-teal-500 rounded-3xl px-8 py-5 shadow-2xl shadow-slate-200/50 outline-none font-bold text-slate-700 placeholder:text-slate-300 transition-all duration-300 <?= !$selected_store_id ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                autocomplete="off"
                                <?= !$selected_store_id ? 'disabled' : ''; ?>
                            >
                            
                            <!-- Search Results Dropdown -->
                            <div id="product_dropdown" class="absolute left-0 right-0 mt-3 bg-white border-2 border-teal-500 rounded-[2.5rem] max-h-[400px] overflow-y-auto hidden shadow-[0_20px_50px_rgba(13,148,136,0.15)] z-[9999] overflow-hidden">
                                <div id="search_results_container" class="p-2 custom-scroll scroll-teal">
                                    <div class="p-4 text-center text-slate-400 font-bold">Loading products...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Table Area -->
                <div class="overflow-hidden rounded-[2.5rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/40 mb-10 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[600px]">
                        <thead>
                            <tr class="bg-teal-50/50 text-teal-800 text-[10px] uppercase font-black tracking-widest border-b border-teal-100">
                                <th class="p-6">Product Name with Code</th>
                                <th class="p-6 text-center">Unit Price</th>
                                <th class="p-6 text-center">Quantity</th>
                                <th class="p-6 text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody id="selected_products_body" class="divide-y divide-slate-100">
                             <tr id="empty_row">
                                <td colspan="4" class="p-16 text-center">
                                    <div class="flex flex-col items-center gap-4 opacity-20">
                                        <i class="fas fa-box-open text-6xl"></i>
                                        <span class="font-extrabold text-xl tracking-tight">No products selected yet.</span>
                                    </div>
                                </td>
                             </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Settings Controls Area -->
                <div class="bg-white rounded-[3.5rem] p-12 border border-slate-200 shadow-2xl shadow-slate-200/30 mb-10">
                    
                    <!-- Page Layout Selection -->
                    <div class="flex flex-col md:flex-row items-center gap-8 border-b border-slate-100 pb-12 mb-12">
                        <label class="w-[200px] shrink-0 font-black text-slate-800 tracking-tight text-xl">Page Layout</label>
                        <div class="flex-1 w-full grid grid-cols-2 lg:grid-cols-4 gap-4" id="layout_options">
                            <!-- Custom Radio Box Style for Layout -->
                            <?php 
                            $layouts = [
                                '40' => ['label' => '40 labels (A4)', 'desc' => '4x10 Grid'],
                                '30' => ['label' => '30 labels (Letter)', 'desc' => '3x10 Grid'],
                                '24' => ['label' => '24 labels (A4)', 'desc' => '3x8 Grid'],
                                '20' => ['label' => '20 labels (Large)', 'desc' => '2x10 Grid'],
                                '18' => ['label' => '18 labels (A4)', 'desc' => '3x6 Grid'],
                                '14' => ['label' => '14 labels (XL)', 'desc' => '2x7 Grid'],
                                '12' => ['label' => '12 labels (A4)', 'desc' => '2x6 Grid'],
                                '10' => ['label' => '10 labels (XXL)', 'desc' => '2x5 Grid']
                            ];
                            foreach($layouts as $val => $info): ?>
                            <label class="cursor-pointer group">
                                <input type="radio" name="layout_option" value="<?= $val; ?>" class="hidden peer" <?= $val == '40' ? 'checked' : ''; ?>>
                                <div class="p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl text-center transition-all peer-checked:bg-teal-50 peer-checked:border-teal-500 peer-checked:text-teal-900 group-hover:border-teal-200">
                                    <span class="block font-black text-sm uppercase tracking-tighter"><?= $info['label']; ?></span>
                                    <span class="block text-[10px] font-bold text-slate-400 mt-1 opacity-80"><?= $info['desc']; ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Field Selection -->
                    <div class="flex flex-col md:flex-row items-start gap-8 mb-12">
                        <label class="w-[200px] shrink-0 font-black text-slate-800 tracking-tight text-xl mt-2">Fields Visibility</label>
                        <div class="flex-1 flex flex-wrap gap-x-10 gap-y-6">
                            <?php 
                            $fields = [
                                'field_site' => 'Site name',
                                'field_name' => 'Product name',
                                'field_code' => 'Product code',
                                'field_price' => 'Price',
                                'field_currency' => 'Currency',
                                'field_unit' => 'Unit',
                                'field_category' => 'Category',
                                'field_image' => 'Product Image'
                            ];
                            foreach($fields as $name => $lbl): ?>
                            <label class="custom-checkbox">
                                <input type="checkbox" name="<?= $name; ?>" <?= in_array($name, ['field_site', 'field_name', 'field_code', 'field_price', 'field_currency', 'field_image']) ? 'checked' : ''; ?>>
                                <span class="checkmark shadow-sm"></span>
                                <span class="label-text"><?= $lbl; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center gap-8">
                        <button id="generate_btn" 
                                class="relative group btn-premium overflow-hidden btn-teal py-5 px-12 rounded-[2.5rem] flex items-center gap-4 shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                <?= !$selected_store_id ? 'disabled style="display:none;"' : ''; ?>>
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                            <i class="fas fa-bolt group-hover:animate-pulse relative z-10"></i> 
                            <span class="btn-text uppercase tracking-[0.25em] text-sm relative z-10 font-black text-white">Generate Labels</span>
                        </button>
                        
                        <button id="reset_btn" 
                                class="relative group btn-premium overflow-hidden btn-rose py-5 px-12 rounded-[2.5rem] flex items-center gap-4 shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                                <?= !$selected_store_id ? 'disabled style="display:none;"' : ''; ?>>
                            <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                            <i class="fas fa-redo-alt relative z-10"></i> 
                            <span class="uppercase tracking-[0.25em] text-sm relative z-10 font-black text-white">Reset All</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Layout Preview Area -->
                <div id="barcode_preview_section" class="hidden slide-in">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="w-2 h-8 bg-green-500 rounded-full"></div>
                            <h2 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Sheet Preview</h2>
                        </div>
                        <button onclick="printBarcodes()" class="btn-confirm-print shadow-xl shadow-green-500/20">
                            <i class="fas fa-print"></i> Print Now
                        </button>
                    </div>
                    
                    <div class="bg-white border-2 border-slate-100 rounded-[3rem] p-12 shadow-2xl shadow-slate-200/20 min-h-[600px] overflow-hidden">
                        <div id="print_area" class="w-full mx-auto bg-white">
                             <!-- Dynamically Generated Labels Grid -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<style>
    /* Premium Buttons */
    .btn-teal { background: linear-gradient(to right, #0d9488, #059669); }
    .btn-rose { background: linear-gradient(to right, #f43f5e, #e11d48); }
    
    .btn-premium { border: none; outline: none; }
    .btn-premium:active { transform: scale(0.95); }

    /* Premium Checkbox */
    .custom-checkbox {
        display: flex;
        align-items: center;
        position: relative;
        padding-left: 35px;
        cursor: pointer;
        user-select: none;
    }
    .custom-checkbox input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
    .checkmark {
        position: absolute;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        height: 24px;
        width: 24px;
        background-color: #f1f5f9;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .custom-checkbox:hover input ~ .checkmark { border-color: #0d9488; }
    .custom-checkbox input:checked ~ .checkmark {
        background-color: #0d9488;
        border-color: #0d9488;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
    }
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
        left: 7.5px;
        top: 3.5px;
        width: 6px;
        height: 11px;
        border: solid white;
        border-width: 0 2.5px 2.5px 0;
        transform: rotate(45deg);
    }
    .custom-checkbox input:checked ~ .checkmark:after { display: block; }
    .label-text { font-weight: 800; color: #475569; font-size: 14px; transition: color 0.3s; }
    .custom-checkbox input:checked ~ .label-text { color: #0f172a; }

    /* Action Buttons */
    .btn-generate {
        background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
        color: white;
        padding: 20px 45px;
        border-radius: 2rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 15px 35px rgba(58, 123, 213, 0.4);
        transition: all 0.3s ease;
        border: none;
    }
    .btn-generate:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(58, 123, 213, 0.5); }
    .btn-generate:active { transform: translateY(1px); }

    .btn-reset {
        background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        color: white;
        padding: 20px 45px;
        border-radius: 2rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 15px 35px rgba(255, 75, 43, 0.4);
        transition: all 0.3s ease;
        border: none;
    }
    .btn-reset:hover { transform: translateY(-3px); box-shadow: 0 20px 40px rgba(255, 75, 43, 0.5); }
    
    .btn-confirm-print {
        background-color: #10b981;
        color: white;
        padding: 14px 30px;
        border-radius: 1.25rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
    }
    .btn-confirm-print:hover { background-color: #059669; transform: scale(1.05); }

    .scroll-teal::-webkit-scrollbar { width: 6px; }
    .scroll-teal::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 10px; }

    /* Hide Number Arrows */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] { -moz-appearance: textfield; }

    @media print {
        body * { visibility: hidden; }
        #print_area, #print_area * { visibility: visible; }
        #print_area { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; }
        .app-wrapper, .navbar-fixed-top, .app-header { display: none !important; }
        .lg\:ml-64 { margin-left: 0 !important; }
        @page { margin: 1cm; size: A4; }
    }
    
    .barcode-label {
        border: 1px solid #eee;
        padding: 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: white;
        min-height: 100px;
    }
    .barcode-grid { display: grid; width: 100%; gap: 2mm; }
</style>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
    let selectedProducts = [];
    let currency_symbol = "<?= $currency_symbol; ?>";
    let site_name = "<?= htmlspecialchars($store['store_name'] ?? 'Modern POS'); ?>";
    let current_store_id = "<?= $selected_store_id ?: '0'; ?>";

    $(document).ready(function() {
        if(current_store_id == '0') {
             $('#barcode_preview_section').hide();
             return;
        }
        // Store Selector Logic
        const storeSearchInput = $('#store_search_input');
        const storeDropdown = $('#store_dropdown');
        const allStores = <?= json_encode($allowed_stores); ?>;

        storeSearchInput.on('focus', function() {
            storeDropdown.removeClass('hidden');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#store_selector_container').length) {
                storeDropdown.addClass('hidden');
            }
        });

        storeSearchInput.on('input', function() {
            const val = $(this).val().toLowerCase();
            const filtered = allStores.filter(s => s.store_name.toLowerCase().includes(val)).slice(0, 5);
            
            let html = '';
            if(filtered.length > 0) {
                filtered.forEach(s => {
                    html += `
                        <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 last:border-b-0 flex items-center gap-3 rounded-xl" 
                             data-id="${s.id}" 
                             data-name="${s.store_name}">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-black text-xs">
                                ${s.store_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="font-bold text-slate-700 text-sm">${s.store_name}</div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="p-4 text-center text-slate-400 font-bold text-xs uppercase tracking-widest">No Stores matched</div>';
            }
            $('#store_results_container').html(html);
        });

        $(document).on('click', '.store-option', function() {
            const sid = $(this).data('id');
            const sname = $(this).data('name');
            
            storeSearchInput.val(sname);
            storeDropdown.addClass('hidden');
            
            if(current_store_id == sid) return;

            $.ajax({
                url: '/pos/stores/get_store_details.php',
                type: 'GET',
                data: { id: sid },
                dataType: 'json',
                success: function(data) {
                    if(data) {
                        current_store_id = sid;
                        site_name = data.store_name;
                        currency_symbol = data.currency_symbol;
                        
                        // Clear products if store changes to avoid stock mismatch
                        if(selectedProducts.length > 0) {
                            Swal.fire({
                                title: 'Store Changed',
                                text: "The selected products have been cleared to ensure accurate stock for the new store.",
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                        resetAll();
                        fetchSearchHints(''); 
                    }
                }
            });
        });

        fetchSearchHints('');

        $('#product_search').on('focus', function() {
            $('#product_dropdown').removeClass('hidden');
        });

        $('#product_search').on('input', function() {
            fetchSearchHints($(this).val());
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.relative.group').length) {
                $('#product_dropdown').addClass('hidden');
            }
        });

        $(document).on('click', '.product-option', function() {
            const prod = $(this).data();
            addProductToTable(prod);
            $('#product_dropdown').addClass('hidden');
            $('#product_search').val('');
        });

        $(document).on('input', '.qty-input', function() {
            const id = $(this).closest('tr').data('id');
            const qty = parseInt($(this).val()) || 0;
            selectedProducts = selectedProducts.map(p => p.id === id ? {...p, qty} : p);
        });

        // Auto-select text on focus
        $(document).on('focus click', '.qty-input', function() {
            $(this).select();
        });

        $(document).on('click', '.delete-btn', function() {
            const id = $(this).closest('tr').data('id');
            selectedProducts = selectedProducts.filter(p => p.id !== id);
            $(this).closest('tr').remove();
            if(selectedProducts.length === 0) $('#empty_row').show();
        });

        $('#reset_btn').on('click', function() {
            resetAll();
        });

        $('#generate_btn').on('click', function() {
            if(selectedProducts.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Empty',
                    text: 'Please select at least one product to generate barcodes.',
                    confirmButtonColor: '#3a7bd5'
                });
                return;
            }

            const $btn = $(this);
            const $text = $btn.find('.btn-text');
            const $icon = $btn.find('i');
            const originalText = $text.text();
            const originalIconClass = $icon.attr('class');

            // Set Loading State
            $btn.attr('disabled', true).addClass('opacity-75 cursor-not-allowed');
            $text.text('Generating...');
            $icon.attr('class', 'fas fa-spinner fa-spin relative z-10');

            setTimeout(() => {
                generateLabelsGrid();
                
                // Reset Button State after generation
                $btn.attr('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                $text.text(originalText);
                $icon.attr('class', originalIconClass);
            }, 800);
        });
    });

    function resetAll() {
        selectedProducts = [];
        $('#selected_products_body tr:not(#empty_row)').remove();
        $('#empty_row').show();
        $('#barcode_preview_section').addClass('hidden');
    }

    function fetchSearchHints(query) {
        if(!current_store_id || current_store_id == '0') return;
        $.ajax({
            url: '/pos/products/search_products_barcode.php',
            method: 'GET',
            data: { 
                q: query,
                store_id: current_store_id
            },
            success: function(resp) {
                $('#search_results_container').html(resp);
            }
        });
    }

    function addProductToTable(prod) {
        $('#empty_row').hide();
        
        if(selectedProducts.find(p => p.id === prod.id)) {
             $(`tr[data-id="${prod.id}"]`).find('.qty-input').focus();
             return;
        }

        const initialQty = parseInt(prod.stock) || 1;

        const productObj = {
            id: prod.id,
            name: prod.name,
            code: prod.code,
            price: prod.price,
            image: prod.image,
            category: prod.category,
            unit: prod.unit,
            qty: initialQty
        };
        
        selectedProducts.push(productObj);

        const row = `
            <tr data-id="${prod.id}" class="hover:bg-slate-50 transition-all font-medium text-slate-700 group">
                <td class="p-6">
                    <div class="flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl border-2 border-slate-100 bg-white overflow-hidden shrink-0 shadow-sm transition-transform group-hover:scale-110">
                            <img src="${prod.image}" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <div class="font-extrabold text-slate-900 text-lg tracking-tight">${prod.name}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[10px] font-black text-teal-600 tracking-widest bg-teal-50 px-2 py-0.5 rounded uppercase">#${prod.code}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">${prod.category}</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="p-6 text-center">
                    <span class="bg-slate-900 text-white px-4 py-1.5 rounded-xl text-xs font-black shadow-lg shadow-slate-900/20">${currency_symbol}${parseFloat(prod.price).toFixed(2)}</span>
                </td>
                <td class="p-6 text-center">
                    <input type="number" class="qty-input w-24 bg-slate-50 border-2 border-slate-100 rounded-2xl px-4 py-3 text-center font-black outline-none focus:border-teal-500 transition-all" value="${initialQty}" min="1">
                </td>
                <td class="p-6 text-center">
                    <button class="delete-btn text-rose-400 hover:text-rose-600 transition-all transform hover:scale-125">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#selected_products_body').append(row);
    }

    function printBarcodes() {
        const printWindow = window.open('', '_blank', 'width=1000,height=900');
        if (!printWindow) {
            alert('Please allow popups to print.');
            return;
        }

        const content = $('#print_area').html();
        
        let css = `
            @page { size: A4; margin: 10mm; }
            body { margin: 0; padding: 0; font-family: 'Inter', sans-serif; background: white; }
            * { box-sizing: border-box; }
            img { max-width: 100%; height: auto; display: block; margin: 0 auto; }
            .inv-barcode-container svg { max-width: 100%; height: auto; display: block; margin: 0 auto; }
            .barcode-grid { display: grid; width: 100%; gap: 5mm; }
            .barcode-label { border: 1px solid #ccc; padding: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; page-break-inside: avoid; background: white; border-radius: 4px; }
            .barcode-label img { margin-bottom: 5px; border-radius: 4px; max-height: 25px; }
            .barcode-label .site-name { font-size: 9px; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
            .barcode-label .product-name { font-size: 10px; font-weight: 700; margin-bottom: 2px; line-height: 1.1; }
            .barcode-label .price-tag { font-size: 11px; font-weight: 800; margin-top: 5px; }
            .barcode-label .cat-unit { font-size: 7px; font-weight: 600; color: #666; text-transform: uppercase; margin-top: 2px; }
            svg { width: 100% !important; height: auto !important; }
        `;

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Barcodes</title>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
                <style>${css}</style>
            </head>
            <body>
                ${content}
                <script>
                    window.onload = function() {
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    function generateLabelsGrid() {
        const layoutCount = parseInt($('input[name="layout_option"]:checked').val());
        const printArea = $('#print_area');
        printArea.empty();
        $('#barcode_preview_section').removeClass('hidden');

        let columns = 0;
        switch(layoutCount) {
            case 40: columns = 4; break;
            case 30: columns = 3; break;
            case 24: columns = 3; break;
            case 20: columns = 2; break;
            case 18: columns = 3; break;
            case 14: columns = 2; break;
            case 12: columns = 2; break;
            case 10: columns = 2; break;
            default: columns = 4;
        }

        const grid = $(`<div class="barcode-grid" style="grid-template-columns: repeat(${columns}, 1fr);"></div>`);
        
        selectedProducts.forEach(prod => {
            for(let i = 0; i < prod.qty; i++) {
                const label = $(`
                    <div class="barcode-label">
                        ${$('input[name="field_site"]').is(':checked') ? `<div style="font-size: 8px; font-weight: 900; text-transform: uppercase;">${site_name}</div>` : ''}
                        ${$('input[name="field_image"]').is(':checked') ? `<img src="${prod.image}" style="max-height: 25px; object-fit: contain; margin: 4px 0;">` : ''}
                        ${$('input[name="field_category"]').is(':checked') ? `<div style="font-size: 8px; color: #64748b; font-weight: 800; text-transform: uppercase;">${prod.category}</div>` : ''}
                        ${$('input[name="field_name"]').is(':checked') ? `<div style="font-size: 10px; font-weight: 800; line-height: 1.1; margin: 2px 0;">${prod.name}</div>` : ''}
                        <svg class="barcode-item" data-code="${prod.code}"></svg>
                        <div style="display:flex; justify-content: space-between; align-items: center; width: 100%; padding: 0 5px; margin-top: 2px;">
                             ${$('input[name="field_code"]').is(':checked') ? `<span style="font-size: 8px; font-weight: 900; color: #475569;">#${prod.code}</span>` : ''}
                             ${$('input[name="field_unit"]').is(':checked') ? `<span style="font-size: 8px; font-weight: 700; background: #f1f5f9; padding: 2px 4px; border-radius: 4px;">${prod.unit}</span>` : ''}
                             ${$('input[name="field_price"]').is(':checked') ? `<span style="font-size: 11px; font-weight: 900; color: #000;">${$('input[name="field_currency"]').is(':checked') ? currency_symbol : ''}${parseFloat(prod.price).toFixed(2)}</span>` : ''}
                        </div>
                    </div>
                `);
                grid.append(label);
            }
        });

        printArea.append(grid);

        $(".barcode-item").each(function() {
            JsBarcode(this, $(this).data('code'), {
                format: "CODE128",
                width: 1.5,
                height: 35,
                displayValue: false,
                margin: 5
            });
        });

        $('html, body').animate({
            scrollTop: $("#barcode_preview_section").offset().top - 50
        }, 1000);
    }
</script>

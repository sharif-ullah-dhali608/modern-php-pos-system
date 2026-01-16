<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// --- EDIT LOGIC UPGRADE ---
$is_edit = false;
$edit_id = null;
$q_data = [];
$q_items = [];

if(isset($_GET['id'])) {
    $is_edit = true;
    $edit_id = (int)$_GET['id'];
    
    // Fetch Main Quotation Data
    $q_res = mysqli_query($conn, "SELECT * FROM quotations WHERE id='$edit_id' LIMIT 1");
    if(mysqli_num_rows($q_res) > 0) {
        $q_data = mysqli_fetch_assoc($q_res);
        
        // Fetch All Items for this Quotation
        $items_res = mysqli_query($conn, "SELECT qi.*, p.product_name, p.product_code, p.thumbnail, p.opening_stock 
                                         FROM quotation_items qi 
                                         JOIN products p ON qi.product_id = p.id 
                                         WHERE qi.quotation_id='$edit_id'");
        while($row = mysqli_fetch_assoc($items_res)) {
            $q_items[] = $row;
        }
    }
}

// Fetch Data for Selects
$products_query = "SELECT id, product_name, selling_price, product_code, thumbnail, opening_stock, tax_rate_id, tax_method FROM products WHERE status='1'";
$products_res  = mysqli_query($conn, $products_query);
$products = mysqli_fetch_all($products_res, MYSQLI_ASSOC);

$taxes_res     = mysqli_query($conn, "SELECT id, name, taxrate as rate FROM taxrates WHERE status='1'");
$taxes = mysqli_fetch_all($taxes_res, MYSQLI_ASSOC);

$suppliers = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status='1'");

include('../includes/header.php');
?>

<style>
    /* --- PROFESSIONAL INPUT & SELECT2 STYLING --- */
    .table-input, 
    .select2-container .select2-selection--single,
    input[type="date"], input[type="text"], input[type="number"], select {
        height: 42px !important; 
        line-height: 1.5;
        border-radius: 6px !important; 
        border: 1px solid #cbd5e1 !important; 
        background: #ffffff !important;
        font-size: 14px !important;
        width: 100% !important;
        box-sizing: border-box;
        display: flex !important;
        align-items: center !important;
        transition: all 0.2s ease-in-out;
    }

    /* Select2 Specific Alignment fixes */
    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 40px !important; 
        padding-left: 12px !important;
        color: #334155 !important;
    }
    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }

    /* Focus State - Teal Color */
    .table-input:focus, 
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0d9488 !important; 
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1) !important;
        outline: none !important;
    }

    /* --- ERROR VALIDATION STYLING (RED BORDER) --- */
    .is-invalid {
        border-color: #ef4444 !important; /* Red Border */
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; /* Red Glow */
        background-color: #fef2f2 !important; /* Light Red BG */
    }
    
    /* Select2 এর জন্য স্পেশাল লাল বর্ডার */
    .select2-container.is-invalid .select2-selection--single {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }

    /* Table Inputs */
    .table-input { 
        padding: 0 10px; 
        text-align: center; 
    }
    
    /* Tax Method & Small Selects */
    .tax-method-select {
        height: 30px !important;
        background: #f1f5f9 !important;
        border: none !important;
        font-size: 11px !important;
        font-weight: 700;
        color: #475569;
        cursor: pointer;
    }

    .product-row-result { display: flex; align-items: center; gap: 10px; padding: 5px; }
    .product-thumb-inline { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; border: 1px solid #e2e8f0; }

    .ref-gen-btn {
        height: 42px;
        background: #0d9488; color: white; padding: 0 15px; 
        border-radius: 0 6px 6px 0;
        display: flex; align-items: center; justify-content: center; transition: 0.3s;
        cursor: pointer;
        border: 1px solid #0d9488;
    }
    .ref-gen-btn:hover { background: #0f766e; }
    
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .req-star { color: #ef4444; font-weight: bold; margin-left: 3px; }
    
    /* Error Message Area */
    #error_msg {
        display: none;
        padding: 10px;
        background-color: #fee2e2;
        border: 1px solid #ef4444;
        color: #b91c1c;
        border-radius: 8px;
        text-align: center;
        font-weight: bold;
        margin-bottom: 10px;
        font-size: 13px;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col min-h-screen bg-slate-50">       
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        
        <div class="content-scroll-area custom-scroll flex-1 p-4 md:p-6">
            <form action="/pos/quotations/save_quotation.php" method="POST" id="quotationForm" novalidate>
                
                <?php if($is_edit): ?>
                    <input type="hidden" name="quotation_id" value="<?= $q_data['id']; ?>">
                <?php endif; ?>

                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">
                            <?= $is_edit ? 'Edit Quotation' : 'Add Quotation' ?>
                        </h1>
                        <p class="text-slate-500 text-sm">Create/Edit quotations with images and stock tracking.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="lg:col-span-3 space-y-6">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-teal-500"></div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Reference No <span class="req-star">*</span></label>
                                    <div class="flex items-center">
                                        <input type="text" name="ref_no" id="ref_no" 
                                               value="<?= $is_edit ? $q_data['ref_no'] : ''; ?>" 
                                               class="table-input text-left !rounded-r-none !border-r-0 font-bold bg-slate-100 cursor-not-allowed" 
                                               readonly style="width: calc(100% - 45px) !important;">
                                        
                                        <button type="button" 
                                                onclick="generateRef(true)" 
                                                class="ref-gen-btn <?= $is_edit ? '!bg-slate-400 !border-slate-400 !cursor-not-allowed' : '' ?>" 
                                                <?= $is_edit ? 'disabled' : '' ?> style="width: 45px;">
                                            <i class="fas <?= $is_edit ? 'fa-ban' : 'fa-random' ?>"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Customer <span class="req-star">*</span></label>
                                    <select name="customer_id" id="customer_id" class="select2 w-full">
                                        <option value="">Select Customer</option>
                                        <option value="1" <?= ($is_edit && $q_data['customer_id'] == 1) ? 'selected' : ''; ?>>Walk-in Customer</option>
                                        <option value="2" <?= ($is_edit && $q_data['customer_id'] == 2) ? 'selected' : ''; ?>>Regular Customer</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Supplier <span class="req-star">*</span></label>
                                    <select name="supplier_id" id="supplier_select" class="select2 w-full">
                                        <option value="">Select Supplier</option>
                                        <?php foreach($suppliers as $s): ?>
                                            <option value="<?= $s['id']; ?>" <?= ($is_edit && $q_data['supplier_id'] == $s['id']) ? 'selected' : ''; ?>><?= $s['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Date <span class="req-star">*</span></label>
                                    <input type="date" name="date" id="date" value="<?= $is_edit ? $q_data['date'] : date('Y-m-d'); ?>" class="table-input px-3">
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-2">Status <span class="req-star">*</span></label>
                                    <select name="status" class="table-input px-3">
                                        <option value="1" <?= ($is_edit && isset($q_data['status']) && $q_data['status'] == 1) ? 'selected' : ''; ?>>Sent / Active</option>
                                        <option value="0" <?= ($is_edit && isset($q_data['status']) && $q_data['status'] == 0) ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="p-4 bg-slate-50/50 border-b flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                        <i class="fas fa-shopping-cart text-teal-600"></i> Items Details
                                    </h3>
                                    <span id="product_warning_badge" class="hidden text-red-600 text-[11px] font-bold bg-red-100 px-3 py-1 rounded-full border border-red-200 animate-pulse">
                                        ⚠️ Please add at least one product!
                                    </span>
                                </div>
                                <button type="button" onclick="addRow()" class="bg-teal-700 hover:bg-teal-800 text-white px-5 py-2 rounded-lg text-xs font-bold shadow-sm transition-all">+ Add Row</button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left" id="quotation_table">
                                    <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase font-bold border-b">
                                        <tr>
                                            <th class="p-4 min-w-[300px]">Product Information <span class="req-star">*</span></th>
                                            <th class="p-4 text-center w-24">Stock</th>
                                            <th class="p-4 text-center w-32">Price <span class="req-star">*</span></th>
                                            <th class="p-4 text-center w-40">Tax & Method</th>
                                            <th class="p-4 text-center w-24">Qty <span class="req-star">*</span></th>
                                            <th class="p-4 text-right w-32">Subtotal</th>
                                            <th class="p-4 w-12"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="quotation_tbody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border p-6">
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Terms & Conditions</label>
                            <textarea name="terms" rows="2" class="w-full border border-slate-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Special notes..."><?= $is_edit ? $q_data['terms'] : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-xl border border-slate-200 p-6 lg:sticky lg:top-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="fas fa-file-invoice-dollar text-teal-600"></i> Summary</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between text-slate-600">
                                    <span class="text-sm font-medium">Subtotal</span>
                                    <span class="font-bold text-slate-800" id="summary_sub">0.00</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Discount Amount</label>
                                    <input type="number" id="discount" name="discount" class="table-input text-right font-bold" value="<?= $is_edit ? $q_data['discount'] : '0'; ?>" step="0.01">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Order Tax (%)</label>
                                    <input type="number" id="order_tax" name="order_tax" class="table-input text-right font-bold" value="<?= $is_edit ? $q_data['order_tax_rate'] : '0'; ?>">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Shipping Cost</label>
                                    <input type="number" id="shipping" name="shipping" class="table-input text-right font-bold" value="<?= $is_edit ? $q_data['shipping_cost'] : '0'; ?>">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Others Charge</label>
                                    <input type="number" id="others_charge" name="others_charge" class="table-input text-right font-bold" value="<?= $is_edit ? $q_data['others_charge'] : '0'; ?>">
                                </div>
                                <div class="pt-6 mt-6 border-t-2 border-slate-50">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Grand Total</span>
                                    <div class="text-3xl font-black text-teal-600 mt-1">TK <span id="summary_total">0.00</span></div>
                                    

                                    <button type="button" 
                                            onclick="triggerSubmit('<?= $is_edit ? 'update' : 'save'; ?>')"
                                            class="w-full bg-teal-800 hover:bg-teal-900 text-white font-bold py-4 rounded-xl shadow-lg mt-6 transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-save"></i> <?= $is_edit ? 'Update Quotation' : 'Save Quotation'; ?>
                                    </button>
                                    
                                    <button type="button" onclick="location.href='/pos/quotations/add'" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl shadow-lg mt-1 transition-all flex items-center justify-center gap-2">
                                        <i class="fas fa-refresh"></i> Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const products = <?= json_encode($products); ?>;
const taxes = <?= json_encode($taxes); ?>;
const editItems = <?= $is_edit ? json_encode($q_items) : '[]'; ?>;

// --- GENERATE REF FUNCTION ---
function generateRef(force = false) {
    if(force || $('#ref_no').val() == "") {
        $('#ref_no').val('QT-' + Math.floor(1000 + Math.random() * 9000));
        $('#ref_no').removeClass('is-invalid');
    }
}

function formatProduct(p) {
    if (!p.id) return p.text;
    const img = $(p.element).data('img') || '/pos/assets/img/no-image.png';
    const stock = $(p.element).data('stock');
    return $(`
        <div class="product-row-result">
            <img src="${img}" class="product-thumb-inline" onerror="this.src='/pos/assets/img/no-image.png'"/>
            <div>
                <div class="font-bold text-slate-700">${p.text}</div>
                <div class="text-[10px] text-slate-400">Stock Available: ${stock}</div>
            </div>
        </div>
    `);
}

function addRow(data = null) {
    const row = `
    <tr class="hover:bg-slate-50/50 transition-colors">
        <td class="p-4">
            <div class="flex items-center gap-3">
                <img src="${data ? (data.thumbnail || '/pos/assets/img/no-image.png') : '/pos/assets/img/no-image.png'}" class="selected-product-img product-thumb-inline ${data ? '' : 'hidden'}">
                <div class="flex-1">
                    <select name="product_id[]" class="product-select" onchange="productChanged(this)">
                        <option value=""></option>
                        ${products.map(p => `
                            <option value="${p.id}" 
                                ${data && data.product_id == p.id ? 'selected' : ''}
                                data-price="${p.selling_price}" 
                                data-img="${p.thumbnail}" 
                                data-stock="${p.opening_stock}" 
                                data-taxid="${p.tax_rate_id}" 
                                data-taxmethod="${p.tax_method}">${p.product_name} (${p.product_code})
                            </option>`).join('')}
                    </select>
                </div>
            </div>
        </td>
        <td class="p-4 text-center font-bold text-teal-600 available-stock">${data ? data.opening_stock : '0'}</td>
        <td class="p-4"><input type="number" name="price[]" value="${data ? data.price : ''}" class="table-input item-price" step="0.01" oninput="calculate()"></td>
        <td class="p-4">
            <select name="item_tax[]" class="table-input item-tax mb-2" onchange="calculate()">
                <option value="0" data-rate="0">No Tax</option>
                ${taxes.map(t => `
                    <option value="${t.id}" 
                        ${data && data.tax_rate_id == t.id ? 'selected' : ''}
                        data-rate="${t.rate}">${t.name} (${t.rate}%)
                    </option>`).join('')}
            </select>
            <select name="tax_method[]" class="tax-method-select" onchange="calculate()">
                <option value="exclusive" ${data && data.tax_method == 'exclusive' ? 'selected' : ''}>Exclusive (+ Tax)</option>
                <option value="inclusive" ${data && data.tax_method == 'inclusive' ? 'selected' : ''}>Inclusive (Tax Inc.)</option>
            </select>
        </td>
        <td class="p-4"><input type="number" name="qty[]" value="${data ? data.qty : '1'}" class="table-input item-qty" oninput="calculate()"></td>
        <td class="p-4 text-right font-bold text-slate-700 item-subtotal">0.00</td>
        <td class="p-4 text-center">
            <button type="button" onclick="$(this).closest('tr').remove(); calculate();" class="text-slate-300 hover:text-red-500"><i class="fas fa-trash-alt"></i></button>
        </td>
    </tr>`;
    
    $('#quotation_tbody').append(row);
    $('.product-select').last().select2({ 
        placeholder: "Search Product...", 
        templateResult: formatProduct,
        width: '100%' 
    });
    if(data) calculate(); 
}

function productChanged(el) {
    const opt = $(el).find(':selected');
    const row = $(el).closest('tr');
    
    // Select2 error remove
    $(el).closest('td').find('.select2-container').removeClass('is-invalid');

    const imgPath = opt.data('img') || '/pos/assets/img/no-image.png';
    row.find('.selected-product-img').attr('src', imgPath).removeClass('hidden');
    row.find('.available-stock').text(opt.data('stock'));
    row.find('.item-price').val(opt.data('price')).removeClass('is-invalid');
    row.find('.item-tax').val(opt.data('taxid'));
    row.find('.tax-method-select').val(opt.data('taxmethod') || 'exclusive');
    calculate();
}

function calculate() {
    let grandSub = 0;
    let hasProduct = false; // Flag to check product existence

    $('#quotation_tbody tr').each(function() {
        const p = parseFloat($(this).find('.item-price').val()) || 0;
        const q = parseFloat($(this).find('.item-qty').val()) || 0;
        const rate = parseFloat($(this).find('.item-tax option:selected').data('rate')) || 0;
        const method = $(this).find('.tax-method-select').val();
        
        // Check if product is selected in this row
        const productId = $(this).find('.product-select').val();
        if(productId && productId !== "") {
            hasProduct = true;
        }

        let subtotal = p * q;
        if (method === 'exclusive') subtotal += (subtotal * rate / 100);

        $(this).find('.item-subtotal').text(subtotal.toFixed(2));
        grandSub += subtotal;
    });

    // --- WARNING MESSAGE TOGGLE LOGIC ---
    if(hasProduct) {
        $('#product_warning_badge').addClass('hidden'); // Hide warning
    } else {
        $('#product_warning_badge').removeClass('hidden'); // Show warning
    }

    const discount = parseFloat($('#discount').val()) || 0;
    const oTaxRate = parseFloat($('#order_tax').val()) || 0;
    const shipping = parseFloat($('#shipping').val()) || 0;
    const others = parseFloat($('#others_charge').val()) || 0;
    
    const taxableAmount = grandSub - discount;
    const orderTaxAmount = (taxableAmount * oTaxRate) / 100;

    $('#summary_sub').text(grandSub.toFixed(2));
    $('#summary_total').text((taxableAmount + orderTaxAmount + shipping + others).toFixed(2));
}

// ✅ NEW VALIDATION LOGIC - Triggered by Button Click
function triggerSubmit(actionType) {
    let isValid = true;
    let hasProduct = false;
    let firstError = null;
    
    $('#error_msg').hide().text(''); // Reset summary error

    // 1. Basic Fields Check
    $('#ref_no, #customer_id, #supplier_select, #date').each(function() {
        if(!$(this).val()) {
            isValid = false;
            $(this).addClass('is-invalid');
            if($(this).hasClass('select2-hidden-accessible')) {
                $(this).next('.select2-container').addClass('is-invalid');
            }
            if(!firstError) firstError = $(this);
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.select2-container').removeClass('is-invalid');
        }
    });

    // 2. Product Table Validation
    $('#quotation_tbody tr').each(function() {
        const prodSelect = $(this).find('.product-select');
        const prod = prodSelect.val();
        const qtyInput = $(this).find('.item-qty');
        const qty = parseFloat(qtyInput.val()) || 0;
        
        if(prod) {
            hasProduct = true;
            if(qty <= 0) {
                isValid = false;
                qtyInput.addClass('is-invalid');
            } else {
                qtyInput.removeClass('is-invalid');
            }
        }
    });

    // Main Validation Checks
    if(!hasProduct) {
        // Show button error msg
        $('#error_msg').text('⚠️ Please add at least one product!').fadeIn();
        // Also ensure badge is visible (calculate does this, but double check)
        $('#product_warning_badge').removeClass('hidden'); 
        return; 
    }

    if(!isValid) {
        $('#error_msg').text('⚠️ Please fill out all required fields marked in red.').fadeIn();
        if(firstError) {
            $('html, body').animate({ scrollTop: firstError.offset().top - 100 }, 500);
        }
        return; 
    }

    // Submit Form
    const form = document.getElementById('quotationForm');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = actionType + '_quotation_btn';
    hiddenInput.value = '1';
    form.appendChild(hiddenInput);
    
    form.submit();
}

$(document).ready(() => { 
    $('#supplier_select').select2({ placeholder: "Select Supplier", width: '100%' });
    $('#customer_id').select2({ placeholder: "Select Customer", width: '100%' });
    
    $('input, select').removeAttr('required');

    if(editItems.length > 0) {
        editItems.forEach(item => { addRow(item); });
    } else {
        addRow();
        generateRef(); 
    }
    
    $('#order_tax, #shipping, #discount, #others_charge').on('input', calculate);
    
    $(document).on('input change', '.table-input, select', function() {
        if($(this).val()) {
            $(this).removeClass('is-invalid');
            $(this).next('.select2-container').removeClass('is-invalid');
            $('#error_msg').hide();
        }
    });
});
</script>
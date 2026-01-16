<?php
session_start();
include('../config/dbcon.php');

// Security Check: Ensure user is logged in
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

/// ==========================================
// EDIT MODE LOGIC START
// ==========================================
$mode = "create";
$page_title = "New Purchase";
$btn_text = "Confirm Purchase";
$edit_data = [];
$edit_items = [];

if(isset($_GET['invoice_id'])) {
    $mode = "edit";
    $page_title = "Edit Purchase";
    $btn_text = "Update Purchase";
    $invoice_id = mysqli_real_escape_string($conn, $_GET['invoice_id']);

    // Fetch main purchase info
    $info_q = mysqli_query($conn, "SELECT * FROM purchase_info WHERE invoice_id = '$invoice_id' LIMIT 1");
    if(mysqli_num_rows($info_q) > 0) {
        $edit_data = mysqli_fetch_assoc($info_q);
        
        $edit_data['order_tax'] = $edit_data['order_tax'] ?? 0; 
        $edit_data['shipping_charge'] = $edit_data['shipping_charge'] ?? 0;
        $edit_data['discount_amount'] = $edit_data['discount_amount'] ?? 0;

        // Fetch items associated with this invoice
        $items_q = mysqli_query($conn, "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'");
        while($row = mysqli_fetch_assoc($items_q)) {
            $edit_items[] = $row;
        }
        $edit_images = [];
        $imgs_q = mysqli_query($conn, "SELECT * FROM purchase_image WHERE invoice_id = '$invoice_id'");
        while($img_row = mysqli_fetch_assoc($imgs_q)) {
            $edit_images[] = $img_row;
        }
        // =========================================================

        $paid_q = mysqli_query($conn, "SELECT SUM(amount) as paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'");
        $paid_res = mysqli_fetch_assoc($paid_q);
        $edit_data['total_paid'] = $paid_res['paid'] ?? 0;

        $pm_q = mysqli_query($conn, "SELECT pmethod_id FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase' ORDER BY id ASC LIMIT 1");
        $pm_res = mysqli_fetch_assoc($pm_q);
        $edit_data['pmethod_id'] = $pm_res['pmethod_id'] ?? '';
    }
}
// ==========================================
// EDIT MODE LOGIC END
// ==========================================

// Fetch Dropdown Data from Database
$suppliers = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status='1'");
$products = mysqli_query($conn, "SELECT id, product_name, product_code, opening_stock, selling_price, purchase_price FROM products WHERE status='1'");
$payment_methods = mysqli_query($conn, "SELECT id, name FROM payment_methods WHERE status='1' ORDER BY name ASC");
include('../includes/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* Unique Design for Inputs matching Category Page style */
    .unique-input {
        width: 100%;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        outline: none;
        transition: all 0.3s;
    }
    .unique-input:focus {
        border-color: #0d9488;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
    }

    /* Select2 Custom Design */
    .select2-container--default .select2-selection--single {
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0.75rem !important;
        height: 50px !important;
        padding-top: 10px !important;
        transition: all 0.3s ease;
    }
    
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #115e59 !important;
        box-shadow: 0 0 0 3px rgba(17, 94, 89, 0.1) !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #115e59 !important;
    }

    .select2-search--dropdown {
        display: block !important;
    }

    .select2-container { width: 100% !important; }

    #purchaseTable .product-col {
        width: 250px;
    }

    #purchaseTable input {
        width: 100% !important;
        min-width: 90px !important;
        border: 1px solid #cbd5e1;
        border-radius: 0.5rem;
        padding: 8px;
        text-align: center;
        background-color: #f8fafc;
    }

    .select2-dropdown {
        min-width: 300px !important; 
        border: 1px solid #115e59 !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        z-index: 9999 !important;
    }

    .summary-input {
        width: 100px;
        text-align: right;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        padding: 6px 10px;
        font-weight: bold;
        outline: none;
    }

    .loading-spinner {
        display: none;
        width: 1.2rem;
        height: 1.2rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 0;
        border-radius: 12px;
        width: 100%;
        max-width: 500px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">        
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-7xl mx-auto">
                
                <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/purchases/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1"><?= $mode == 'edit' ? 'Update existing stock entry.' : 'Record new stock entry from suppliers.'; ?></p>
                        </div>
                    </div>
                </div>

                <form action="/pos/purchases/save_purchase.php" method="POST" id="purchaseForm" enctype="multipart/form-data">
                    
                    <input type="hidden" name="invoice_id_hidden" value="<?= $edit_data['invoice_id'] ?? ''; ?>">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <div class="lg:col-span-2 space-y-6 slide-in delay-100">
                            
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="flex items-start gap-4 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-300">
                                    <div id="file-preview-container" class="flex flex-wrap gap-2">
                                        <?php 
                                        $has_attachment = false; 
                                        $attachment_path = $edit_data['attachment'] ?? '';

                                        if(!empty($attachment_path)) {
                                            $images = explode(',', $attachment_path);
                                            foreach($images as $img) {
                                                $clean_path = str_replace('/pos', '', $img);
                                                if(file_exists(".." . $clean_path)) {
                                                    $has_attachment = true;
                                                    break;
                                                }
                                            }
                                        }
                                        ?>

                                        <?php if($mode == 'edit' && !empty($edit_images)): ?>
                                            <?php foreach($edit_images as $img): ?>
                                                <div class="relative w-20 h-20 border-2 border-teal-500 rounded-lg overflow-hidden bg-white shadow-sm group">
                                                    <?php 
                                                    $ext = strtolower($img['file_type']);
                                                    if(in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])): ?>
                                                        <img src="<?= $img['file_path']; ?>" class="w-full h-full object-cover">
                                                    <?php elseif($ext == 'pdf'): ?>
                                                        <div class="flex flex-col items-center justify-center h-full text-red-500 bg-red-50">
                                                            <i class="fas fa-file-pdf text-2xl"></i>
                                                            <span class="text-[8px] font-bold mt-1">PDF</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex flex-col items-center justify-center h-full text-blue-500 bg-blue-50">
                                                            <i class="fas fa-file-excel text-2xl"></i>
                                                            <span class="text-[8px] font-bold mt-1"><?= strtoupper($ext); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="w-16 h-16 bg-white rounded-lg flex items-center justify-center border shadow-sm text-slate-400">
                                                <i class="fas fa-file-upload text-2xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Attachment / Receipt (Images, PDF, Doc)</label>
                                        <div class="flex items-center gap-2">
                                            <label for="attachment" class="bg-teal-800 hover:bg-teal-900 text-white px-4 py-1.5 rounded-lg text-sm font-semibold cursor-pointer transition-all">Choose Files</label>
                                            <input type="file" name="attachment[]" id="attachment" class="hidden" accept="image/*,.pdf,.doc,.docx" multiple onchange="handleFilePreview(this)">
                                            <span id="file-count" class="text-slate-400 text-sm italic">
                                                <?= $has_attachment ? "Previous file loaded" : "No files selected"; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-file-invoice text-teal-600"></i> General Information
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Purchase Date *</label>
                                        <input type="date" name="purchase_date" value="<?= $edit_data['purchase_date'] ?? date('Y-m-d'); ?>" class="unique-input">
                                    </div>
                                    <div class="form-group">
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Reference No *</label>
                                        <input type="text" name="reference_no" value="<?= $edit_data['invoice_id'] ?? strtoupper(substr(md5(time()), 0, 8)); ?>" class="unique-input font-mono tracking-widest uppercase" <?= $mode == 'edit' ? 'readonly' : ''; ?>>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Supplier * (Searchable)</label>
                                    <select name="supplier_id" id="supplier_id" required class="w-full select2">
                                        <option value="">Search and select...</option>
                                        <?php mysqli_data_seek($suppliers, 0); while($sup = mysqli_fetch_assoc($suppliers)): ?>
                                            <option value="<?= $sup['id']; ?>" <?= (isset($edit_data['sup_id']) && $edit_data['sup_id'] == $sup['id']) ? 'selected' : ''; ?>><?= $sup['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                                <div class="p-4 bg-slate-50 border-b flex justify-between items-center">
                                    <h3 class="font-bold text-slate-700 flex items-center gap-2"><i class="fas fa-cubes text-teal-600"></i> Stock Items</h3>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="openImportModal()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md">
                                            <i class="fas fa-file-import mr-1"></i> Purchase with file (CSV)
                                        </button>
                                        <button type="button" onclick="addRow()" class="bg-teal-800 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-xs font-bold shadow-md">
                                            <i class="fas fa-plus mr-1"></i> Add Row
                                        </button>
                                    </div>
                                </div>
                                <div class="overflow-x-auto custom-scroll">
                                    <table class="w-full text-left" id="purchaseTable">
                                        <thead class="text-[11px] uppercase tracking-wider font-bold text-slate-500 bg-slate-50/50">
                                            <tr>
                                                <th class="px-6 py-4 border-b product-col">Product *</th>
                                                <th class="px-4 py-4 border-b w-20">Stock</th>
                                                <th class="px-2 py-4 border-b">Qty *</th>
                                                <th class="px-2 py-4 border-b">Cost *</th>
                                                <th class="px-2 py-4 border-b">Sell Price</th>
                                                <th class="px-2 py-4 border-b">Tax</th>
                                                <th class="px-4 py-4 border-b text-right">Total</th>
                                                <th class="px-4 py-4 border-b text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 slide-in delay-200">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Note / Remarks</label>
                                <textarea name="note" rows="3" class="unique-input text-sm resize-none" placeholder="Enter purchase notes..."><?= $edit_data['purchase_note'] ?? ''; ?></textarea>
                            </div>

                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4 sticky top-24">
                                <div class="space-y-3 border-b border-dashed pb-4">
                                    <div class="flex justify-between items-center text-xs text-slate-500">
                                        <span>Subtotal</span>
                                        <span id="subtotal_display" class="font-bold text-slate-700">0.00</span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs text-slate-500">
                                        <span>Order Tax (%)</span>
                                        <input type="number" name="order_tax" id="order_tax" oninput="calculateSummary()" class="summary-input" value="<?= $edit_data['order_tax'] ?? 0; ?>">
                                    </div>
                                    <div class="flex justify-between items-center text-xs text-slate-500">
                                        <span>Shipping Charge</span>
                                        <input type="number" name="shipping_charge" id="shipping_charge" oninput="calculateSummary()" class="summary-input" value="<?= $edit_data['shipping_charge'] ?? 0; ?>">
                                    </div>
                                    <div class="flex justify-between items-center text-xs text-slate-500">
                                        <span>Discount Amount</span>
                                        <input type="number" name="discount_amount" id="discount_amount" oninput="calculateSummary()" class="summary-input" value="<?= $edit_data['discount_amount'] ?? 0; ?>">
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t mt-2">
                                        <span class="text-slate-800 font-bold uppercase text-[10px]">Payable Amount</span>
                                        <span id="grand_total_display" class="font-bold text-lg text-teal-800">0.00</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Payment Method</label>
                                    <select name="payment_method" id="payment_id" class="w-full select2">
                                        <option value="">Search and select...</option>
                                        <?php mysqli_data_seek($payment_methods, 0); while($method = mysqli_fetch_assoc($payment_methods)): ?>
                                            <option value="<?= $method['id']; ?>" <?= (isset($edit_data['pmethod_id']) && $edit_data['pmethod_id'] == $method['id']) ? 'selected' : ''; ?>><?= $method['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1 uppercase">Amount Paid *</label>
                                    <input type="number" step="0.01" name="paid_amount" id="paid_amount" oninput="calculateSummary()" class="unique-input font-bold text-teal-800 text-lg" value="<?= $edit_data['total_paid'] ?? '0.00'; ?>">
                                </div>

                               <div class="grid grid-cols-2 gap-2 text-center">
                                    <div class="p-2 bg-red-50 rounded-lg border border-red-100">
                                        <span class="text-[9px] font-bold text-red-500 uppercase block">Due</span>
                                        <span id="due_text" class="font-bold text-red-700 text-xs">0.00</span>
                                    </div>
                                    <div class="p-2 bg-green-50 rounded-lg border border-green-100">
                                        <span class="text-[9px] font-bold text-green-500 uppercase block">Change</span>
                                        <span id="change_text" class="font-bold text-green-700 text-xs">0.00</span>
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-2">
                                    <button type="submit" name="save_purchase_btn" id="submitBtn" class="flex-1 bg-teal-800 hover:bg-teal-900 text-white font-bold py-3 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-sm">
                                        <span class="btn-text"><?= $btn_text; ?></span>
                                        <div class="loading-spinner"></div>
                                    </button>
                                    <button type="reset" id="resetBtn" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl shadow-lg transition-all flex justify-center items-center gap-2 text-sm">
                                        <span class="btn-text">Reset</span>
                                        <div class="loading-spinner" style="border-top-color: #fca5a5;"></div>
                                    </button>
                                </div>  
                        </div>
                    </div>
                </form>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<div id="importModal" class="modal">
    <div class="modal-content slide-in">
        <div class="flex items-center justify-between p-4 border-b bg-slate-800 text-white rounded-t-xl">
            <h3 class="font-bold flex items-center gap-2"><i class="fas fa-file-csv"></i> Stock Import</h3>
            <button onclick="closeImportModal()" class="text-white hover:text-slate-300"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <form id="importForm" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Export Product Of</label>
                    <select name="import_supplier_id" class="unique-input">
                        <option value="">- Please Select -</option>
                        <?php 
                        mysqli_data_seek($suppliers, 0);
                        while($sup = mysqli_fetch_assoc($suppliers)): ?>
                            <option value="<?= $sup['id']; ?>"><?= $sup['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Select CSV File</label>
                    <input type="file" name="csv_file" class="w-full border rounded-lg p-2 bg-slate-50" accept=".csv">
                </div>
                <button type="submit" class="w-full bg-teal-700 text-white font-bold py-3 rounded-xl shadow-md hover:bg-teal-800 flex items-center justify-center gap-2">
                    <i class="fas fa-upload"></i> Import Now
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Modal Controls
function openImportModal() { document.getElementById('importModal').style.display = 'block'; }
function closeImportModal() { document.getElementById('importModal').style.display = 'none'; }

window.onclick = function(event) {
    let modal = document.getElementById('importModal');
    if (event.target == modal) closeImportModal();
}

$(document).ready(function() {
    function initDropdowns() {
        $('.select2').select2({
            placeholder: "Search and select...",
            width: '100%',
            allowClear: true
        });
    }

    initDropdowns();

    // ==========================================
    // AUTO LOAD ROWS FOR EDIT MODE START
    // ==========================================
 
        <?php if($mode == 'edit' && !empty($edit_items)): ?>
            <?php foreach($edit_items as $item): ?>
                addRow(
                    '<?= $item['item_id']; ?>', 
                    '<?= $item['item_quantity']; ?>', 
                    '<?= $item['item_purchase_price']; ?>', 
                    '<?= $item['item_selling_price']; ?>',
                    '<?= $item['item_tax']; ?>' 
                );
            <?php endforeach; ?>
        <?php elseif(isset($_GET['product_id'])): 
            $pid = intval($_GET['product_id']);
            $product_q = mysqli_query($conn, "SELECT id, selling_price, purchase_price FROM products WHERE id = $pid LIMIT 1");
            if(mysqli_num_rows($product_q) > 0) {
                $p = mysqli_fetch_assoc($product_q);
                echo "addRow('{$p['id']}', 1, '{$p['purchase_price']}', '{$p['selling_price']}', 0);";
            }
        ?>
        <?php else: ?>
            addRow(); 
        <?php endif; ?>
    // ==========================================
    // AUTO LOAD ROWS FOR EDIT MODE END
    // ==========================================

    $('#purchaseForm').on('submit', function(e) {
        // Add hidden input to ensure save_purchase_btn is included in POST
        if($('#purchaseForm').find('input[name="save_purchase_btn"]').length === 0) {
            $('#purchaseForm').append('<input type="hidden" name="save_purchase_btn" value="1">');
        }
        $('#submitBtn').prop('disabled', true).find('.loading-spinner').show();
    });

    $('#resetBtn').on('click', function(e) {
        if(!confirm("Reset all form data?")) {
            e.preventDefault();
            return;
        }
        location.reload();
    });

    setTimeout(function() {
        calculateSummary();
    }, 500);

    // Toast Mixin
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // CSV Import Handler
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');
        
        $.ajax({
            url: 'ajax_parse_purchase_csv.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.status == 'success') {
                    if(response.items && response.items.length > 0) {
                        response.items.forEach(function(item) {
                            addRow(
                                item.item_id, 
                                item.item_quantity, 
                                item.item_purchase_price, 
                                item.item_selling_price, 
                                item.item_tax
                            );
                        });
                        
                        let msg = response.count + " items imported successfully!";
                        if(response.missing && response.missing.length > 0) {
                             Toast.fire({
                                icon: 'warning',
                                title: 'Partial Import',
                                html: msg + '<br><br><b>Missing Products:</b><br>' + response.missing.join(", ")
                             });
                        } else {
                             Toast.fire({
                                icon: 'success',
                                title: 'Import Successful',
                                text: msg
                             });
                        }
                        
                        closeImportModal();
                        $('#importForm')[0].reset();
                    } else {
                        if(response.missing && response.missing.length > 0) {
                             Toast.fire({
                                icon: 'error',
                                title: 'Items Not Found',
                                html: 'The following products were not found:<br>' + response.missing.join(", ")
                             });
                        } else {
                             Toast.fire({
                                icon: 'info',
                                title: 'No Items',
                                text: 'No valid items found in CSV.'
                             });
                        }
                    }
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                Toast.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Failed to process request. Check console.'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});

function handleFilePreview(input) {
    const container = document.getElementById('file-preview-container');
    const countSpan = document.getElementById('file-count');
    container.innerHTML = ''; 

    if (input.files.length > 0) {
        countSpan.innerText = `${input.files.length} new file(s) selected`;
        Array.from(input.files).forEach(file => {
            const div = document.createElement('div');
            div.className = "w-20 h-20 bg-white rounded-lg border-2 border-dashed border-teal-400 shadow-sm overflow-hidden flex items-center justify-center relative";
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                div.innerHTML = `<div class="text-center text-red-500"><i class="fas fa-file-pdf text-2xl"></i><p class="text-[8px] font-bold">PDF</p></div>`;
            } else {
                div.innerHTML = `<div class="text-center text-blue-500"><i class="fas fa-file-excel text-2xl"></i><p class="text-[8px] font-bold">FILE</p></div>`;
            }
            container.appendChild(div);
        });
    }
}

const productList = [
    <?php 
    mysqli_data_seek($products, 0);
    while($p = mysqli_fetch_assoc($products)){
        echo "{id: '{$p['id']}', name: '{$p['product_name']}', code: '{$p['product_code']}', stock: '{$p['opening_stock']}', cost: '{$p['purchase_price']}', sell: '{$p['selling_price']}'},";
    }
    ?>
];


function addRow(pId = '', qty = 1, cost = 0, sell = 0, tax = 0) {
    const tbody = document.querySelector('#purchaseTable tbody');
    const rowId = Date.now() + Math.floor(Math.random() * 100);
    const tr = document.createElement('tr');
    tr.id = `row_${rowId}`;
    tr.className = "hover:bg-slate-50/50 transition-colors item-row";
    
    tr.innerHTML = `
        <td class="px-6 py-4 product-col">
            <select name="items[${rowId}][product_id]" required class="table-select2" onchange="updateRowInfo(this, ${rowId})">
                <option value="">Search and select...</option>
                ${productList.map(p => `<option value="${p.id}" ${p.id == pId ? 'selected' : ''}>${p.name} (${p.code})</option>`).join('')}
            </select>
        </td>
        <td class="px-4 py-4 text-xs font-bold text-slate-400" id="stock_${rowId}">0</td>
        <td class="px-2 py-4"><input type="number" name="items[${rowId}][qty]" id="qty_${rowId}" value="${qty}" oninput="calculateRow(${rowId})"></td>
        <td class="px-2 py-4"><input type="number" step="0.01" name="items[${rowId}][cost]" id="cost_${rowId}" value="${cost}" oninput="calculateRow(${rowId})"></td>
        <td class="px-2 py-4"><input type="number" step="0.01" name="items[${rowId}][sell]" id="sell_${rowId}" value="${sell}"></td>
        <td class="px-2 py-4"><input type="number" name="items[${rowId}][tax]" id="tax_${rowId}" value="${tax}" oninput="calculateRow(${rowId})"></td>
        <td class="px-4 py-4 text-right font-bold text-slate-700 text-xs row-subtotal" id="subtotal_${rowId}">0.00</td>
        <td class="px-4 py-4 text-center">
            <button type="button" onclick="removeRow(${rowId})" class="text-red-400 hover:text-red-600 transition-colors"><i class="fas fa-trash-alt"></i></button>
        </td>
    `;
    tbody.appendChild(tr);

    
    $(`#row_${rowId} .table-select2`).select2({ width: '100%' });
    if(pId) {
        const p = productList.find(i => i.id == pId);
        if(p) document.getElementById(`stock_${rowId}`).innerText = p.stock;
        calculateRow(rowId); 
    }
}

function updateRowInfo(select, rowId) {
    const p = productList.find(i => i.id == select.value);
    if(p) {
        document.getElementById(`stock_${rowId}`).innerText = p.stock;
        document.getElementById(`cost_${rowId}`).value = p.cost;
        document.getElementById(`sell_${rowId}`).value = p.sell;
        calculateRow(rowId);
    }
}

function calculateRow(rowId) {
    const qty = parseFloat(document.getElementById(`qty_${rowId}`).value) || 0;
    const cost = parseFloat(document.getElementById(`cost_${rowId}`).value) || 0;
    const tax = parseFloat(document.getElementById(`tax_${rowId}`).value) || 0;
    
    // Row Total calculation
    const rowTotal = (qty * cost) + tax;
    document.getElementById(`subtotal_${rowId}`).innerText = rowTotal.toFixed(2);
    calculateSummary();
}

function removeRow(rowId) { 
    document.getElementById(`row_${rowId}`).remove(); 
    calculateSummary(); 
}

function calculateSummary() {
    let itemsTotal = 0;
    document.querySelectorAll('.row-subtotal').forEach(s => {
        itemsTotal += parseFloat(s.innerText) || 0;
    });
    
    document.getElementById('subtotal_display').innerText = itemsTotal.toFixed(2);
    const taxPct = parseFloat(document.getElementById('order_tax').value) || 0;
    const ship = parseFloat(document.getElementById('shipping_charge').value) || 0;
    const disc = parseFloat(document.getElementById('discount_amount').value) || 0;
    const paid = parseFloat(document.getElementById('paid_amount').value) || 0;

    const taxValue = (itemsTotal * taxPct) / 100;
    const grand = (itemsTotal + taxValue + ship) - disc;
    
    document.getElementById('grand_total_display').innerText = grand.toFixed(2);

    if (paid >= grand) {
        document.getElementById('due_text').innerText = "0.00";
        document.getElementById('change_text').innerText = (paid - grand).toFixed(2);
    } else {
        document.getElementById('due_text').innerText = (grand - paid).toFixed(2);
        document.getElementById('change_text').innerText = "0.00";
    }
}
</script>
<?php
// Start output buffering to prevent any output before headers
ob_start();
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    ob_end_clean();
    header("Location: /pos/login");
    exit(0);
}

$user_id = $_SESSION['auth_user']['user_id'] ?? $_SESSION['auth_user']['id'] ?? 1;

// Get store_id from session or get first available store
$store_id = $_SESSION['store_id'] ?? $_SESSION['auth_user']['store_id'] ?? null;

// Validate store_id exists in database, if not get first available store
if($store_id) {
    $store_check = mysqli_query($conn, "SELECT id FROM stores WHERE id = $store_id AND status = 1 LIMIT 1");
    if(!$store_check || mysqli_num_rows($store_check) == 0) {
        $store_id = null; // Invalid store_id, reset it
    }
}

// If no valid store_id, get first available store
if(!$store_id) {
    $store_query = mysqli_query($conn, "SELECT id FROM stores WHERE status = 1 ORDER BY id ASC LIMIT 1");
    if($store_query && mysqli_num_rows($store_query) > 0) {
        $store_row = mysqli_fetch_assoc($store_query);
        $store_id = (int)$store_row['id'];
    } else {
        // No stores available - this is a critical error
        error_log("Purchase Save: No stores available in database!");
        $_SESSION['message'] = "Error: No stores available. Please create a store first.";
        $_SESSION['msg_type'] = "error";
        ob_end_clean();
        header("Location: /pos/purchases/add");
        exit(0);
    }
}

// Handle direct GET requests gracefully (redirect to list)
// Only redirect if it's a GET request (POST requests should proceed to handlers below)
if($_SERVER['REQUEST_METHOD'] === 'GET') {
    ob_end_clean();
    header("Location: /pos/purchases/list");
    exit(0);
}

// Handle AJAX requests with JSON responses (only set when needed)
if(isset($_POST['payment_modal_btn']) || isset($_POST['view_purchase_btn']) || isset($_POST['return_modal_btn']) || 
   isset($_POST['process_payment_btn']) || isset($_POST['process_return_btn']) || isset($_POST['pay_all_btn']) || 
   isset($_POST['delete_btn'])) {
    header('Content-Type: application/json');
}

// ============================================
// PAYMENT MODAL - Return HTML for payment form
// ============================================
if(isset($_POST['payment_modal_btn']) && isset($_POST['invoice_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    
    // 1. Fetch Purchase Info with tax, shipping, and discount details
    $purchase_query = "SELECT pi.*, s.name as supplier_name 
                       FROM purchase_info pi
                       LEFT JOIN suppliers s ON pi.sup_id = s.id
                       WHERE pi.invoice_id = '$invoice_id' LIMIT 1";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if(!$purchase_result || mysqli_num_rows($purchase_result) == 0) {
        echo json_encode(['status' => 400, 'message' => 'Purchase not found']);
        exit;
    }
    
    $purchase = mysqli_fetch_assoc($purchase_result);
    
    // 2. Variables for calculation from database
    $order_tax_pct = floatval($purchase['order_tax'] ?? 0);
    $shipping = floatval($purchase['shipping_charge'] ?? 0);
    $discount = floatval($purchase['discount_amount'] ?? 0);
    
    // Source of Truth: Use total_sell for the final payable amount
    $total_amount = floatval($purchase['total_sell'] ?? 0);
    
    // 3. Calculate Item Subtotal (Sum of all item_totals)
    $items_query = "SELECT SUM(item_total) as subtotal FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_result = mysqli_query($conn, $items_query);
    $items_data = mysqli_fetch_assoc($items_result);
    $items_subtotal = floatval($items_data['subtotal'] ?? 0);
    
    // Calculate Order Tax Amount
    $order_tax_amount = ($items_subtotal * $order_tax_pct) / 100;
    
    // 4. Calculate Paid Amount from logs
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_data = mysqli_fetch_assoc($paid_result);
    $paid_amount = floatval($paid_data['total_paid'] ?? 0);
    
    $due_amount = $total_amount - $paid_amount;
    
    // Get purchase items detail list
    $items_detail_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_detail_result = mysqli_query($conn, $items_detail_query);
    
    ob_start();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <h4 class="font-bold text-slate-700 mb-2">Payment Method</h4>
                <select id="pmethod_select" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500">
                    <option value="">Select Payment Method</option>
                    <?php 
                    $pm_q = mysqli_query($conn, "SELECT * FROM payment_methods WHERE status = 1 ORDER BY name ASC");
                    while($pm = mysqli_fetch_assoc($pm_q)): ?>
                        <option value="<?= $pm['id']; ?>"><?= htmlspecialchars($pm['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-4">
                <h4 class="font-bold text-slate-700 mb-2">Payment Action</h4>
                <button type="button" onclick="fullPayment()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2 transition-all">
                    <i class="fas fa-dollar-sign"></i> Full Payment
                </button>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Pay Amount</label>
                        <input type="number" step="0.01" id="pay_amount" value="<?= $due_amount; ?>" 
                               class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" 
                               oninput="calculatePayment()">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Note</label>
                        <textarea id="payment_note" rows="2" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" placeholder="Note Here"></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-slate-700">Billing Summary</h4>
                <span class="font-mono text-sm text-slate-600 font-bold"><?= htmlspecialchars($invoice_id); ?></span>
            </div>

            <div class="space-y-2 mb-4 max-h-40 overflow-y-auto custom-scroll">
                <?php 
                $count = 1;
                while($item = mysqli_fetch_assoc($items_detail_result)): 
                    $qty = floatval($item['item_quantity']) - floatval($item['return_quantity']);
                ?>
                    <div class="flex justify-between text-xs text-slate-600">
                        <span><?= $count++; ?>. <?= htmlspecialchars($item['item_name']); ?> (x<?= $qty; ?>)</span>
                        <span class="font-bold"><?= number_format($item['item_total'], 2); ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <div class="space-y-2 border-t border-slate-300 pt-3">
                <div class="flex justify-between text-sm">
                    <span>Subtotal (Items + Item Tax)</span>
                    <span class="font-bold"><?= number_format($items_subtotal, 2); ?></span>
                </div>
                <div class="flex justify-between text-sm text-blue-600">
                    <span>(+) Order Tax (<?= $order_tax_pct; ?>%)</span>
                    <span class="font-bold"><?= number_format($order_tax_amount, 2); ?></span>
                </div>
                <div class="flex justify-between text-sm text-blue-600">
                    <span>(+) Shipping Charge</span>
                    <span class="font-bold"><?= number_format($shipping, 2); ?></span>
                </div>
                <div class="flex justify-between text-sm text-red-500">
                    <span>(-) Discount</span>
                    <span class="font-bold"><?= number_format($discount, 2); ?></span>
                </div>

                <div class="bg-slate-200 p-2 rounded mt-2">
                    <div class="flex justify-between text-base font-black text-slate-800">
                        <span>Total Payable Amount</span>
                        <span><?= number_format($total_amount, 2); ?></span>
                    </div>
                </div>
                
                <div class="flex justify-between text-sm text-green-700 font-bold px-2 mt-2">
                    <span>Total Paid Previously</span>
                    <span><?= number_format($paid_amount, 2); ?></span>
                </div>
                
                <div class="bg-pink-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold text-red-700">
                        <span>Current Due</span>
                        <span id="bill_due"><?= number_format($due_amount, 2); ?></span>
                    </div>
                </div>
                
                <div class="bg-yellow-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold text-yellow-800">
                        <span>Change / Balance</span>
                        <span id="bill_balance">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
        <button onclick="closePaymentModal()" class="px-6 py-2 bg-slate-400 hover:bg-slate-500 text-white rounded-lg font-bold">Close</button>
        <button onclick="submitPayment()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold shadow-lg transition-all">Pay Now →</button>
    </div>
    
    <script>
    // Local Toast initialization to ensure it exists within the modal context
    const ModalToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#1e3a3a',
        color: '#fff'
    });

    var invoiceId = '<?= $invoice_id; ?>';
    var dueAmount = parseFloat('<?= $due_amount; ?>') || 0;

    function fullPayment() {
        document.getElementById('pay_amount').value = dueAmount.toFixed(2);
        calculatePayment();
    }
    
    function calculatePayment() {
        var pay = parseFloat(document.getElementById('pay_amount').value) || 0;
        var currentDue = dueAmount - pay;
        var balance = pay > dueAmount ? pay - dueAmount : 0;
        
        document.getElementById('bill_due').textContent = (currentDue > 0 ? currentDue : 0).toFixed(2);
        document.getElementById('bill_balance').textContent = balance.toFixed(2);
    }
    
   function submitPayment() {
        var payAmount = parseFloat(document.getElementById('pay_amount').value) || 0;
        var pmethodId = document.getElementById('pmethod_select').value;
        var note = document.getElementById('payment_note').value;
        
        if(!pmethodId) {
            Swal.fire({ icon: 'warning', text: 'Please select a payment method.' });
            return;
        }
        
        if(payAmount <= 0) {
            Swal.fire({ icon: 'warning', text: 'Please enter a valid amount.' });
            return;
        }

        // Direct AJAX Submission
        $.ajax({
            url: '/pos/purchases/save_purchase.php',
            type: 'POST',
            data: {
                process_payment_btn: true,
                invoice_id: invoiceId,
                pay_amount: payAmount,
                pmethod_id: pmethodId,
                payment_note: note
            },
            dataType: 'json',
            success: function(response) {
                if(response.status == 200) {
                    closePaymentModal();
                    
                    // Dark Green Success Toast (Matching your screenshot)
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        background: '#1e3a3a', // Dark Green background from your image
                        color: '#fff'
                    });

                    Toast.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Payment Processed Successfully!'
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }
        });
    }
    </script>
    <?php
    $html = ob_get_clean();
    echo json_encode(['status' => 200, 'html' => $html]);
    exit;
}
// ============================================
// VIEW PURCHASE MODAL - Return HTML for view
// ============================================
if(isset($_POST['view_purchase_btn']) && isset($_POST['invoice_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);

    // Fetch main purchase information including supplier and creator details
    $purchase_query = "SELECT pi.*, s.name as supplier_name, u.name as creator_name 
                       FROM purchase_info pi
                       LEFT JOIN suppliers s ON pi.sup_id = s.id
                       LEFT JOIN users u ON pi.created_by = u.id 
                       WHERE pi.invoice_id = '$invoice_id' LIMIT 1";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if(!$purchase_result || mysqli_num_rows($purchase_result) == 0) {
        echo json_encode(['status' => 400, 'message' => 'Purchase not found']);
        exit;
    }
    
    $purchase = mysqli_fetch_assoc($purchase_result);
    
    // Assign global totals from the purchase_info record
    $order_tax = floatval($purchase['order_tax'] ?? 0);
    $shipping_charge = floatval($purchase['shipping_charge'] ?? 0);
    $discount_amount = floatval($purchase['discount_amount'] ?? 0);
    $grand_total = floatval($purchase['total_sell'] ?? 0);

    // Fetch all items linked to this specific invoice
    $items_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_result = mysqli_query($conn, $items_query);
    
    $subtotal = 0;
    $total_item_tax = 0;
    $items_html = "";
    
    while($item = mysqli_fetch_assoc($items_result)) {
        // Fix: Determine if this is a Return List view or a regular Purchase view.
        // If coming from the Return List, we should show the 'return_quantity'.
        // Check a flag or the current script context to decide. 
        // For this fix, we will show 'return_quantity' if it is greater than 0, 
        // otherwise show the full 'item_quantity'.
        $is_return_view = (floatval($item['return_quantity']) > 0);
        $display_qty = $is_return_view ? floatval($item['return_quantity']) : floatval($item['item_quantity']);
        $qty_label = $is_return_view ? "Returned" : "Pieces";

        $item_tax = floatval($item['item_tax'] ?? 0);
        
        // Fix: Calculate item subtotal using the display quantity to avoid 0.00 results
        $item_subtotal = ($display_qty * floatval($item['item_purchase_price']));
        
        $subtotal += $item_subtotal;
        $total_item_tax += $item_tax;

        $items_html .= '<tr>
            <td class="border border-slate-300 p-2 text-sm">'.htmlspecialchars($item['item_name']).' (x'.number_format($display_qty, 2).' '.$qty_label.')</td>
            <td class="border border-slate-300 p-2 text-right text-sm">'.number_format($item['item_purchase_price'], 2).'</td>
            <td class="border border-slate-300 p-2 text-right text-sm">'.number_format($item_tax, 2).'</td>
            <td class="border border-slate-300 p-2 text-right text-sm font-bold">'.number_format($item['item_total'], 2).'</td>
        </tr>';
    }
    
    // Fetch payment history to calculate current balance and due status
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_data = mysqli_fetch_assoc($paid_result);
    $paid_amount = floatval($paid_data['total_paid'] ?? 0);
    
    $due_amount = $grand_total - $paid_amount;
    
    ob_start();
    ?>
    <div class="space-y-6 p-4">
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4 border-b pb-2">General Information</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><label class="text-xs text-slate-500 font-bold block">Date</label><p class="text-sm font-semibold"><?= date('d M Y', strtotime($purchase['purchase_date'] ?? $purchase['created_at'])); ?></p></div>
                <div><label class="text-xs text-slate-500 font-bold block">Invoice Id</label><p class="text-sm font-semibold font-mono text-blue-600"><?= htmlspecialchars($purchase['invoice_id']); ?></p></div>
                <div><label class="text-xs text-slate-500 font-bold block">Supplier</label><p class="text-sm font-semibold"><?= htmlspecialchars($purchase['supplier_name'] ?? 'No Supplier'); ?></p></div>
                <div><label class="text-xs text-slate-500 font-bold block">Payment Status</label><span class="px-2 py-1 rounded text-xs font-bold <?= $purchase['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?= ucfirst($purchase['payment_status']); ?></span></div>
            </div>
        </div>
                
        <div>
            <h3 class="font-bold text-lg text-slate-800 mb-4">Product List</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="border border-slate-300 p-2 text-left text-xs font-bold">Product</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Unit Cost</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Item Tax</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Row Total</th>
                        </tr>
                    </thead>
                    <tbody><?= $items_html; ?></tbody>
                </table>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="text-sm text-slate-600 italic pt-4">
                <strong>Note:</strong> <?= htmlspecialchars($purchase['purchase_note'] ?? 'N/A'); ?><br>
                <strong>Created By:</strong> <?= htmlspecialchars($purchase['creator_name'] ?? 'Admin'); ?>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-2">
                <div class="flex justify-between text-sm"><span>Subtotal (Excl. Tax)</span><span class="font-bold"><?= number_format($subtotal, 2); ?></span></div>
                <div class="flex justify-between text-sm text-blue-600"><span>(+) Total Item Tax</span><span><?= number_format($total_item_tax, 2); ?></span></div>
                <div class="flex justify-between text-sm"><span>(+) Order Tax (<?= $purchase['order_tax']; ?>%)</span><span><?= number_format(($subtotal * $order_tax / 100), 2); ?></span></div>
                <div class="flex justify-between text-sm"><span>(+) Shipping Charge</span><span><?= number_format($shipping_charge, 2); ?></span></div>
                <div class="flex justify-between text-sm text-red-500"><span>(-) Discount</span><span><?= number_format($discount_amount, 2); ?></span></div>
                <div class="flex justify-between border-t border-slate-300 pt-2 font-black text-lg text-teal-700"><span>Payable Amount</span><span><?= number_format($grand_total, 2); ?></span></div>
                <div class="flex justify-between text-sm text-green-600 font-bold"><span>Total Paid</span><span><?= number_format($paid_amount, 2); ?></span></div>
                <div class="flex justify-between text-sm text-red-600 font-bold border-t border-dashed pt-1"><span>Due Amount</span><span><?= number_format($due_amount, 2); ?></span></div>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    echo json_encode(['status' => 200, 'html' => $html]);
    exit;
}

// ============================================
// RETURN MODAL - Return HTML for return form
// ============================================
if(isset($_POST['return_modal_btn']) && isset($_POST['invoice_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);

    // Get purchase info
    $purchase_query = "SELECT pi.*, s.name as supplier_name 
              FROM purchase_info pi
              LEFT JOIN suppliers s ON pi.sup_id = s.id
                       WHERE pi.invoice_id = '$invoice_id' LIMIT 1";
    $purchase_result = mysqli_query($conn, $purchase_query);
    
    if(!$purchase_result || mysqli_num_rows($purchase_result) == 0) {
        echo json_encode(['status' => 400, 'message' => 'Purchase not found']);
        exit;
    }
    
    $purchase = mysqli_fetch_assoc($purchase_result);
    
    // Get purchase items
    $items_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_result = mysqli_query($conn, $items_query);
        
        // Calculate totals
    $total_query = "SELECT SUM(item_total) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $total_result = mysqli_query($conn, $total_query);
    $total_data = mysqli_fetch_assoc($total_result);
    $total_amount = floatval($total_data['total_amount'] ?? 0);
    
    // Calculate paid
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_data = mysqli_fetch_assoc($paid_result);
    $paid_amount = floatval($paid_data['total_paid'] ?? 0);
    $due_amount = $total_amount - $paid_amount;
    
    ob_start();
    ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Side: Order Summary -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Order Summary</h3>
            <div class="mb-4">
                <span class="text-xs text-slate-500">Invoice ID:</span>
                <span class="font-mono font-bold"><?= htmlspecialchars($invoice_id); ?></span>
                </div>
                
            <div class="space-y-2 mb-4">
                <?php 
                mysqli_data_seek($items_result, 0);
                $item_count = 1;
                while($item = mysqli_fetch_assoc($items_result)): 
                    $qty = floatval($item['item_quantity']) - floatval($item['return_quantity']);
                ?>
                    <div class="flex justify-between text-sm">
                        <span><?= $item_count; ?> <?= htmlspecialchars($item['item_name']); ?> (x<?= number_format($qty, 2); ?> Pieces)</span>
                        <span class="font-bold"><?= number_format($item['item_total'], 2); ?></span>
                    </div>
                <?php 
                    $item_count++;
                endwhile; 
                ?>
            </div>
            
            <div class="space-y-2 border-t pt-3">
                <div class="flex justify-between text-sm">
                        <span>Subtotal</span>
                    <span class="font-bold"><?= number_format($total_amount, 2); ?></span>
                    </div>
                <div class="flex justify-between text-sm">
                        <span>Discount</span>
                        <span>0.00</span>
                    </div>
                <div class="flex justify-between text-sm">
                        <span>Order Tax</span>
                        <span>0.00</span>
                    </div>
                <div class="flex justify-between text-sm">
                        <span>Previous Due</span>
                        <span>0.00</span>
                    </div>
                
                <div class="bg-slate-200 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                            <span>Payable Amount (items)</span>
                        <span><?= number_format($total_amount, 2); ?></span>
                        </div>
                </div>
                
                <?php if($paid_amount > 0): ?>
                    <div class="bg-green-100 p-2 rounded mt-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-green-800">Paid by <?= htmlspecialchars($purchase['supplier_name'] ?? 'Unknown'); ?> on <?= date('Y-m-d H:i:s'); ?></span>
                            <span class="font-bold text-green-800"><?= number_format($paid_amount, 2); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="bg-pink-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-red-800">Due</span>
                        <span class="text-red-800"><?= number_format($due_amount, 2); ?></span>
                        </div>
                </div>
                
                <div class="bg-yellow-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-yellow-800">Balance</span>
                        <span class="text-yellow-800">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
        <!-- Right Side: Return Items -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Return Item</h3>
            
            <form id="returnForm">
                <div class="overflow-x-auto mb-4">
                    <table class="w-full border-collapse">
                    <thead>
                            <tr class="bg-slate-200">
                                <th class="border border-slate-300 p-2 text-center text-xs font-bold">Yes/No</th>
                                <th class="border border-slate-300 p-2 text-left text-xs font-bold">Product Name</th>
                                <th class="border border-slate-300 p-2 text-center text-xs font-bold">Return Quantity</th>
                        </tr>
                    </thead>
                        <tbody id="returnItemsBody">
                            <?php 
                            mysqli_data_seek($items_result, 0);
                            while($item = mysqli_fetch_assoc($items_result)): 
                                $available_qty = floatval($item['item_quantity']) - floatval($item['return_quantity']);
                                if($available_qty <= 0) continue;
                            ?>
                                <tr>
                                    <td class="border border-slate-300 p-2 text-center">
                                        <input type="checkbox" name="return_items[<?= $item['id']; ?>][selected]" 
                                               value="1" onchange="toggleReturnQty(this, <?= $item['id']; ?>, <?= $available_qty; ?>)">
                            </td>
                                    <td class="border border-slate-300 p-2 text-sm">
                                        <?= htmlspecialchars($item['item_name']); ?> (x<?= number_format($available_qty, 2); ?> Pieces)
                            </td>
                                    <td class="border border-slate-300 p-2">
                                        <input type="number" step="0.01" name="return_items[<?= $item['id']; ?>][qty]" 
                                               id="return_qty_<?= $item['id']; ?>" 
                                               value="0" min="0" max="<?= $available_qty; ?>" 
                                               class="w-full border border-slate-300 rounded p-2 text-center" 
                                               disabled>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Type Any Note</label>
                    <textarea name="return_note" rows="3" 
                              class="w-full border border-slate-300 rounded-lg p-3" 
                              placeholder="Enter return note..."></textarea>
                </div>
            </form>
            </div>
        </div>
        
    <div class="bg-slate-700 px-6 py-4 flex justify-between items-center border-t border-slate-300">
        <button onclick="closeReturnModal()" class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold flex items-center gap-2">
            <i class="fas fa-times"></i>
            <span>X Close</span>
            </button>
        <button onclick="submitReturn()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            <span>Return Now →</span>
            </button>
        </div>
        
        <script>
    var invoiceId = '<?= $invoice_id; ?>';
    
    function toggleReturnQty(checkbox, itemId, maxQty) {
        var qtyInput = document.getElementById('return_qty_' + itemId);
        if(checkbox.checked) {
            qtyInput.disabled = false;
            qtyInput.value = maxQty;
            } else {
            qtyInput.disabled = true;
            qtyInput.value = 0;
            }
    }
        
        function submitReturn() {
    var formData = $('#returnForm').serialize();
    
    
    if ($('#returnForm input[type="checkbox"]:checked').length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Items Selected',
            text: 'Please select at least one item to return.',
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: formData + '&process_return_btn=1&invoice_id=' + invoiceId,
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                closeReturnModal();

                
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: '#1e3a3a', 
                    color: '#fff'          
                });

                Toast.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Purchase returned successfully!'
                });

               
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error processing return request'
            });
        }
    });
}
    </script>
    <?php
    $html = ob_get_clean();
        echo json_encode(['status' => 200, 'html' => $html]);
    exit;
}

// ============================================
// PROCESS PAYMENT
// ============================================
if(isset($_POST['process_payment_btn']) && isset($_POST['invoice_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $pay_amount = floatval($_POST['pay_amount'] ?? 0);
    $pmethod_id = intval($_POST['pmethod_id'] ?? 0);
    $payment_note = mysqli_real_escape_string($conn, $_POST['payment_note'] ?? '');
    
    if($pay_amount <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Invalid payment amount']);
        exit;
    }
    
    // Generate reference number
    $reference_no = 'CT' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    mysqli_begin_transaction($conn);
    try {
    // Insert payment log
        $insert_log = "INSERT INTO purchase_logs (sup_id, reference_no, ref_invoice_id, type, pmethod_id, description, amount, store_id, created_by) 
                       VALUES (
                           (SELECT sup_id FROM purchase_info WHERE invoice_id = '$invoice_id' LIMIT 1),
                           '$reference_no',
                           '$invoice_id',
                           'purchase',
                           '$pmethod_id',
                           " . ($payment_note ? "'$payment_note'" : "'Paid while purchasing'") . ",
                           '$pay_amount',
                           '$store_id',
                           '$user_id'
                       )";
        
        if(!mysqli_query($conn, $insert_log)) {
            throw new Exception(mysqli_error($conn));
        }
        
        // Calculate total paid
        $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
        $paid_result = mysqli_query($conn, $paid_query);
        $paid_data = mysqli_fetch_assoc($paid_result);
        $total_paid = floatval($paid_data['total_paid']);
        
        // Calculate total amount
        $total_query = "SELECT SUM(item_total) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $total_result = mysqli_query($conn, $total_query);
        $total_data = mysqli_fetch_assoc($total_result);
        $total_amount = floatval($total_data['total_amount']);
        
        // Update payment status
        $payment_status = ($total_paid >= $total_amount) ? 'paid' : 'due';
        $update_status = "UPDATE purchase_info SET payment_status = '$payment_status' WHERE invoice_id = '$invoice_id'";
        if(!mysqli_query($conn, $update_status)) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_commit($conn);
        echo json_encode(['status' => 200, 'message' => 'Payment processed successfully']);
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// PROCESS RETURN
// ============================================
if(isset($_POST['process_return_btn']) && isset($_POST['invoice_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $return_items = $_POST['return_items'] ?? [];
    $return_note = mysqli_real_escape_string($conn, $_POST['return_note'] ?? '');
    
    if(empty($return_items)) {
        echo json_encode(['status' => 400, 'message' => 'No items selected for return']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    try {
        $total_return_amount = 0; // Track total return value
        
        foreach($return_items as $item_id => $data) {
            if(isset($data['selected']) && $data['selected'] == '1') {
                $item_id = intval($item_id);
                $return_qty = floatval($data['qty'] ?? 0);
                
                if($return_qty > 0) {
                    // Get item details
                    $item_query = "SELECT * FROM purchase_item WHERE id = '$item_id' AND invoice_id = '$invoice_id' LIMIT 1";
                    $item_result = mysqli_query($conn, $item_query);
                    if($item_result && mysqli_num_rows($item_result) > 0) {
                        $item = mysqli_fetch_assoc($item_result);
                        $current_return_qty = floatval($item['return_quantity']);
                        $original_qty = floatval($item['item_quantity']);
                        $unit_price = floatval($item['item_purchase_price']);
                        $item_tax = floatval($item['item_tax'] ?? 0);
                        
                        // Calculate new return quantity
                        $new_return_qty = $current_return_qty + $return_qty;
                        
                        // Calculate remaining quantity
                        $remaining_qty = $original_qty - $new_return_qty;
                        
                        // Calculate new item_total based on remaining qty
                        // Tax is proportionally distributed
                        $tax_per_unit = ($original_qty > 0) ? ($item_tax / $original_qty) : 0;
                        $new_item_total = ($remaining_qty * $unit_price) + ($remaining_qty * $tax_per_unit);
                        
                        // Calculate return amount (for tracking)
                        $return_amount = ($return_qty * $unit_price) + ($return_qty * $tax_per_unit);
                        $total_return_amount += $return_amount;
                        
                        // Update item: return_quantity and item_total
                        $update_item = "UPDATE purchase_item SET 
                                        return_quantity = '$new_return_qty',
                                        item_total = '$new_item_total'
                                        WHERE id = '$item_id'";
                        if(!mysqli_query($conn, $update_item)) {
                            throw new Exception(mysqli_error($conn));
                        }
                        
                        // Update product stock (reduce stock)
                        $product_id = intval($item['item_id']);
                        $update_stock = "UPDATE products SET opening_stock = opening_stock - $return_qty WHERE id = '$product_id'";
                        if(!mysqli_query($conn, $update_stock)) {
                            throw new Exception(mysqli_error($conn));
                        }
                    }
                }
            }
        }
        
        // Recalculate total_sell for the purchase
        $new_total_query = "SELECT COALESCE(SUM(item_total), 0) as new_total FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $new_total_result = mysqli_query($conn, $new_total_query);
        $new_total_data = mysqli_fetch_assoc($new_total_result);
        $new_total_sell = floatval($new_total_data['new_total']);
        
        // Get order-level charges
        $purchase_query = "SELECT order_tax, shipping_charge, discount_amount FROM purchase_info WHERE invoice_id = '$invoice_id' LIMIT 1";
        $purchase_result = mysqli_query($conn, $purchase_query);
        $purchase_data = mysqli_fetch_assoc($purchase_result);
        
        $order_tax_pct = floatval($purchase_data['order_tax'] ?? 0);
        $shipping = floatval($purchase_data['shipping_charge'] ?? 0);
        $discount = floatval($purchase_data['discount_amount'] ?? 0);
        
        // Apply order tax, shipping, discount to new total
        $order_tax_amount = ($new_total_sell * $order_tax_pct) / 100;
        $final_total = ($new_total_sell + $order_tax_amount + $shipping) - $discount;
        
        // Check if ALL items are fully returned (remaining qty = 0 for all)
        $remaining_check = "SELECT COUNT(*) as active_items FROM purchase_item 
                           WHERE invoice_id = '$invoice_id' 
                           AND (item_quantity - return_quantity) > 0";
        $remaining_result = mysqli_query($conn, $remaining_check);
        $remaining_data = mysqli_fetch_assoc($remaining_result);
        $active_items = intval($remaining_data['active_items']);
        
        // Set status based on remaining items
        if($active_items == 0) {
            // All items returned - mark as 'returned'
            $new_status = 'returned';
        } else {
            $new_status = 'partial_return';
        }

        // Recalculate Payment Status
        $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
        $paid_result = mysqli_query($conn, $paid_query);
        $paid_data = mysqli_fetch_assoc($paid_result);
        $total_paid = floatval($paid_data['total_paid']);

        $due_amount = $final_total - $total_paid;
        
        if($due_amount > 0.001) {
            $payment_status = 'due';
        } elseif(abs($due_amount) <= 0.001) {
            $payment_status = 'paid';
        } else {
            $payment_status = 'receivable';
        }
        
        // Update purchase_info with new total and status
        $update_purchase = "UPDATE purchase_info SET 
                            total_sell = '$final_total',
                            status = '$new_status',
                            payment_status = '$payment_status'
                            WHERE invoice_id = '$invoice_id'";
        if(!mysqli_query($conn, $update_purchase)) {
            throw new Exception(mysqli_error($conn));
        }
        
        // Log the return in purchase_logs
        if($total_return_amount > 0) {
            $return_ref = 'RTN-' . strtoupper(substr(md5(microtime()), 0, 6));
            $return_log = "INSERT INTO purchase_logs (sup_id, reference_no, ref_invoice_id, type, description, amount, store_id, created_by) 
                          VALUES (
                              (SELECT sup_id FROM purchase_info WHERE invoice_id = '$invoice_id' LIMIT 1),
                              '$return_ref',
                              '$invoice_id',
                              'return',
                              " . ($return_note ? "'Return: $return_note'" : "'Product return'") . ",
                              '$total_return_amount',
                              '$store_id',
                              '$user_id'
                          )";
            mysqli_query($conn, $return_log);
        }
        
        mysqli_commit($conn);
        
        $msg = $active_items == 0 ? 'All items returned successfully! Purchase marked as returned.' : 'Partial return processed successfully!';
        echo json_encode(['status' => 200, 'message' => $msg]);
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// PAY ALL SELECTED
// ============================================
if(isset($_POST['pay_all_btn']) && isset($_POST['invoice_ids'])) {
    $invoice_ids = $_POST['invoice_ids'];
    
    if(!is_array($invoice_ids) || empty($invoice_ids)) {
        echo json_encode(['status' => 400, 'message' => 'No invoices selected']);
        exit;
    }
    
    mysqli_begin_transaction($conn);
    try {
        foreach($invoice_ids as $invoice_id) {
            $invoice_id = mysqli_real_escape_string($conn, $invoice_id);
            
            // Calculate due
            $total_query = "SELECT SUM(item_total) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
            $total_result = mysqli_query($conn, $total_query);
            $total_data = mysqli_fetch_assoc($total_result);
            $total_amount = floatval($total_data['total_amount'] ?? 0);
            
            $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
            $paid_result = mysqli_query($conn, $paid_query);
            $paid_data = mysqli_fetch_assoc($paid_result);
            $paid_amount = floatval($paid_data['total_paid'] ?? 0);
            $due_amount = $total_amount - $paid_amount;
            
            if($due_amount > 0) {
                // Get default payment method
                $pmethod_query = "SELECT id FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC, id ASC LIMIT 1";
                $pmethod_result = mysqli_query($conn, $pmethod_query);
                $pmethod_id = 1; // Default
                if($pmethod_result && mysqli_num_rows($pmethod_result) > 0) {
                    $pmethod_data = mysqli_fetch_assoc($pmethod_result);
                    $pmethod_id = intval($pmethod_data['id']);
                }
                
                // Generate reference
                $reference_no = 'CT' . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Insert payment log
                $insert_log = "INSERT INTO purchase_logs (sup_id, reference_no, ref_invoice_id, type, pmethod_id, description, amount, store_id, created_by) 
                               VALUES (
                                   (SELECT sup_id FROM purchase_info WHERE invoice_id = '$invoice_id' LIMIT 1),
                                   '$reference_no',
                                   '$invoice_id',
                                   'purchase',
                                   '$pmethod_id',
                                   'Bulk payment',
                                   '$due_amount',
                                   '$store_id',
                                   '$user_id'
                               )";
                
                if(!mysqli_query($conn, $insert_log)) {
                    throw new Exception(mysqli_error($conn));
                }
                
                // Update status
                $update_status = "UPDATE purchase_info SET payment_status = 'paid' WHERE invoice_id = '$invoice_id'";
                if(!mysqli_query($conn, $update_status)) {
                    throw new Exception(mysqli_error($conn));
                }
            }
        }
        
        mysqli_commit($conn);
        echo json_encode(['status' => 200, 'message' => 'All payments processed successfully']);
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// DELETE PURCHASE
// ============================================
if(isset($_POST['delete_btn']) && isset($_POST['delete_id'])) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    mysqli_begin_transaction($conn);
    try {
        // Get purchase items to restore stock
        $items_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $items_result = mysqli_query($conn, $items_query);
        
        while($item = mysqli_fetch_assoc($items_result)) {
            $returned_qty = floatval($item['return_quantity']);
            $actual_qty = floatval($item['item_quantity']) - $returned_qty;
            
            // Restore stock (only non-returned items)
            $product_id = intval($item['item_id']);
            $update_stock = "UPDATE products SET opening_stock = opening_stock - $actual_qty WHERE id = '$product_id'";
            mysqli_query($conn, $update_stock);
        }
        
        // Delete related records
        mysqli_query($conn, "DELETE FROM purchase_logs WHERE ref_invoice_id = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM purchase_item WHERE invoice_id = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM purchase_info WHERE invoice_id = '$invoice_id'");
        
        mysqli_commit($conn);
        echo json_encode(['status' => 200, 'message' => 'Purchase deleted successfully']);
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
    }
    exit;
}

// ============================================================
// SAVE PURCHASE (From Add Purchase Form)
// ============================================================


if(isset($_POST['save_purchase_btn']))
{
    error_log("Purchase Save: Handler called");
    
    // Form theke data collect kora (Already apnar code-e ache)
    $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date'] ?? date('Y-m-d'));
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
    $payment_method = !empty($_POST['payment_method']) ? (int)$_POST['payment_method'] : NULL;
    $paid_amount = (float)($_POST['paid_amount'] ?? 0);
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
    $order_tax = (float)($_POST['order_tax'] ?? 0);
    $shipping_charge = (float)($_POST['shipping_charge'] ?? 0);
    $discount_amount = (float)($_POST['discount_amount'] ?? 0);

    // ==========================================================
    // EDIT vs CREATE Logic Start
    // ==========================================================
    $invoice_id_hidden = mysqli_real_escape_string($conn, $_POST['invoice_id_hidden'] ?? '');

    mysqli_begin_transaction($conn);
    try {
        if(!empty($invoice_id_hidden)) {
            // EDIT MODE logic
            $invoice_id = $invoice_id_hidden;
            
            // 1. Purono stock reverse (minus) kora
            $old_items_q = mysqli_query($conn, "SELECT item_id, item_quantity FROM purchase_item WHERE invoice_id = '$invoice_id'");
            while($old_item = mysqli_fetch_assoc($old_items_q)) {
                $pid = $old_item['item_id'];
                $old_qty = $old_item['item_quantity'];
                mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock - $old_qty WHERE id = $pid");
            }
            
            // 2. Purono record delete (notun data insert korar jonno)
            mysqli_query($conn, "DELETE FROM purchase_info WHERE invoice_id = '$invoice_id'");
            mysqli_query($conn, "DELETE FROM purchase_item WHERE invoice_id = '$invoice_id'");
            mysqli_query($conn, "DELETE FROM purchase_logs WHERE ref_invoice_id = '$invoice_id'");
        } else {
            // CREATE MODE logic: Notun invoice generate
            $invoice_id = strtoupper(substr(md5(time() . rand()), 0, 9));
        }
        // ==========================================================
        // EDIT vs CREATE Logic End
        // ==========================================================

        // Validation: Item check
        $items = $_POST['items'] ?? [];
        $valid_items = [];
        foreach($items as $item) {
            if(!empty($item['product_id']) && (float)($item['qty'] ?? 0) > 0) {
                $valid_items[] = $item;
            }
        }

        if(empty($valid_items)) { throw new Exception("Please add at least one item."); }

        // Totals calculate kora
       
        $subtotal = 0;
        $total_items_qty = 0;
        $total_item_tax = 0; 

        foreach($valid_items as $item) {
            $qty = (float)$item['qty'];
            $cost = (float)$item['cost'];
            $row_tax = (float)($item['tax'] ?? 0); 
            
            $subtotal += ($qty * $cost) + $row_tax;
            $total_items_qty += $qty;
            // $total_item_tax += $row_tax;
        }

        $order_tax_amount = ($subtotal * $order_tax) / 100;
        $grand_total = ($subtotal + $order_tax_amount + $shipping_charge) - $discount_amount;
        // 1. Insert into purchase_info
        if ($paid_amount > ($grand_total + 0.01)) {
        $payment_status = 'receivable'; // Overpayment condition
    } elseif ($paid_amount < ($grand_total - 0.01)) {
        $payment_status = 'due';
    } else {
        $payment_status = 'paid';
    }
        // 1. Insert into purchase_info
        $payment_status = ($paid_amount >= $grand_total) ? 'paid' : 'due';
        $insert_info = "INSERT INTO purchase_info (invoice_id, store_id, sup_id, total_item,order_tax, shipping_charge, discount_amount, total_sell, purchase_note, payment_status, created_by, purchase_date) 
                        VALUES ('$invoice_id', '$store_id', '$supplier_id', '$total_items_qty','$order_tax','$shipping_charge','$discount_amount', '$grand_total', '$note', '$payment_status', '$user_id', '$purchase_date')";
        
        if(!mysqli_query($conn, $insert_info)) { throw new Exception("Info insert failed: ".mysqli_error($conn)); }
        if (isset($_FILES['attachment']['name']) && !empty($_FILES['attachment']['name'][0])) {
            $upload_dir = '../uploads/purchases/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['attachment']['name'] as $key => $val) {
                if ($_FILES['attachment']['error'][$key] == 0) {
                    $original_name = $_FILES['attachment']['name'][$key];
                    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
                    $file_name = time() . '_' . rand(100, 999) . '.' . $ext;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'][$key], $target_file)) {
                        $db_path = '/pos/uploads/purchases/' . $file_name;
                       
                        mysqli_query($conn, "INSERT INTO purchase_image (invoice_id, file_path, file_type) VALUES ('$invoice_id', '$db_path', '$ext')");
                    }
                }
            }
        }

        // 2. Insert Items & Update Stock
       foreach($valid_items as $item) {
            $product_id = (int)$item['product_id'];
            $qty = (float)$item['qty'];
            $cost = (float)$item['cost'];
            $sell = (float)$item['sell'];
            $row_tax = (float)($item['tax'] ?? 0); 
            $p_q = mysqli_query($conn, "SELECT product_name, category_id, brand_id FROM products WHERE id = $product_id");
            $p_data = mysqli_fetch_assoc($p_q);
            
            
            $item_total = ($qty * $cost) + $row_tax; 
            
            
            mysqli_query($conn, "INSERT INTO purchase_item (
                invoice_id, store_id, item_id, category_id, brand_id, item_name, 
                item_purchase_price, item_selling_price, item_quantity, item_total, item_tax
            ) VALUES (
                '$invoice_id', '$store_id', '$product_id', '{$p_data['category_id']}', 
                '{$p_data['brand_id']}', '".mysqli_real_escape_string($conn, $p_data['product_name'])."', 
                '$cost', '$sell', '$qty', '$item_total', '$row_tax'
            )");
            
          
            mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock + $qty WHERE id = $product_id");
        }

        // 3. Payment Logs
        if($paid_amount > 0 && $payment_method) {
            $pay_ref = 'PAY-' . strtoupper(substr(md5(microtime()), 0, 6));
            mysqli_query($conn, "INSERT INTO purchase_logs (sup_id, reference_no, ref_invoice_id, type, pmethod_id, description, amount, store_id, created_by) 
                                 VALUES ('$supplier_id', '$pay_ref', '$invoice_id', 'purchase', '$payment_method', 'Paid amount', '$paid_amount', '$store_id', '$user_id')");
        }

        mysqli_commit($conn);
        $_SESSION['message'] = "Purchase " . (!empty($invoice_id_hidden) ? "Updated" : "Saved") . " Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/purchases/list");
        exit(0);

    } catch(Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/purchases/add");
        exit(0);
    }
}

// ============================================================
// DELETE PURCHASE
// ============================================================
if(isset($_POST['delete_purchase_btn']))
{
    $purchase_id = (int)$_POST['purchase_id'];
    
    // Get invoice_id first
    $query = mysqli_query($conn, "SELECT invoice_id FROM purchase_info WHERE info_id = $purchase_id LIMIT 1");
    if($data = mysqli_fetch_assoc($query)) {
        $invoice_id = $data['invoice_id'];
        
        // Get items to reverse stock
        $items_query = mysqli_query($conn, "SELECT item_id, item_quantity, return_quantity FROM purchase_item WHERE invoice_id = '$invoice_id'");
        
        mysqli_begin_transaction($conn);
        
        try {
            // Reverse stock updates
            while($item = mysqli_fetch_assoc($items_query)) {
                $product_id = $item['item_id'];
                $qty = (float)$item['item_quantity'] - (float)($item['return_quantity'] ?? 0);
                
                if($qty > 0) {
                    mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock - $qty WHERE id = $product_id");
                }
            }
            
            // Delete purchase_logs
            mysqli_query($conn, "DELETE FROM purchase_logs WHERE ref_invoice_id = '$invoice_id'");
            
            // Delete purchase_item
            mysqli_query($conn, "DELETE FROM purchase_item WHERE invoice_id = '$invoice_id'");
            
            // Delete purchase_info
            mysqli_query($conn, "DELETE FROM purchase_info WHERE info_id = $purchase_id");
            
            mysqli_commit($conn);
            echo json_encode(['status' => 200, 'message' => 'Purchase deleted successfully']);
        } catch(Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 404, 'message' => 'Purchase not found']);
    }
    
    exit(0);
}

// If we reach here, no POST handler matched - this shouldn't happen for valid requests
// But if it does, redirect to purchase list
ob_end_clean();
header("Location: /pos/purchases/list");
exit(0);
?>


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
    
    // Calculate totals
    $items_query = "SELECT SUM(item_total) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $items_result = mysqli_query($conn, $items_query);
    $items_data = mysqli_fetch_assoc($items_result);
    $total_amount = floatval($items_data['total_amount'] ?? 0);
    
    // Calculate paid amount
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_data = mysqli_fetch_assoc($paid_result);
    $paid_amount = floatval($paid_data['total_paid'] ?? 0);
    
    $due_amount = $total_amount - $paid_amount;
    
    // Get payment methods
    $pmethods_query = "SELECT * FROM payment_methods WHERE status = 1 ORDER BY name ASC";
    $pmethods_result = mysqli_query($conn, $pmethods_query);
    
    // Get purchase items for billing summary
    $items_detail_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_detail_result = mysqli_query($conn, $items_detail_query);
    
    ob_start();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Side: Payment Form -->
        <div class="space-y-4">
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <h4 class="font-bold text-slate-700 mb-2">Payment Method</h4>
                <select id="pmethod_select" class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" onchange="loadPaymentMethod(this.value)">
                    <option value="">Select Payment Method</option>
                    <?php while($pm = mysqli_fetch_assoc($pmethods_result)): ?>
                        <option value="<?= $pm['id']; ?>"><?= htmlspecialchars($pm['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                    </div>
            
            <div id="paymentMethodDetails" class="space-y-4">
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <h4 class="font-bold text-slate-700 mb-2" id="pmethod_title">Payment Method</h4>
                    <button type="button" onclick="fullPayment()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2">
                        <i class="fas fa-dollar-sign"></i>
                        Full Payment
                    </button>
                    </div>
                
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Pay Amount</label>
                        <input type="number" step="0.01" id="pay_amount" name="pay_amount" value="<?= $due_amount; ?>" 
                               class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" 
                               placeholder="Input An Amount" oninput="calculatePayment()">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Note</label>
                        <textarea id="payment_note" name="payment_note" rows="3" 
                                  class="w-full border border-slate-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500" 
                                  placeholder="Note Here"></textarea>
                    </div>
                    </div>
                    </div>
                </div>
        
        <!-- Right Side: Billing Details -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-slate-700">Full Payment</h4>
                <span class="font-mono text-sm text-slate-600"><?= htmlspecialchars($invoice_id); ?></span>
            </div>

            <h3 class="font-bold text-lg text-slate-800 mb-4">Billing Details</h3>
            
            <div class="space-y-2 mb-4">
                <?php 
                mysqli_data_seek($items_detail_result, 0);
                $item_count = 1;
                while($item = mysqli_fetch_assoc($items_detail_result)): 
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
            
            <div class="space-y-2 border-t border-slate-300 pt-3">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span class="font-bold" id="bill_subtotal"><?= number_format($total_amount, 2); ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Order Tax</span>
                    <span class="font-bold">0.00</span>
            </div>

                <div class="bg-slate-200 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                        <span>Payable Amount ( Items)</span>
                        <span><?= number_format($total_amount, 2); ?></span>
                        </div>
                        </div>
                
                <?php if($paid_amount > 0): ?>
                    <div class="bg-green-100 p-2 rounded mt-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-green-800">Paid by <?= htmlspecialchars($purchase['supplier_name'] ?? 'Unknown'); ?> on <?= date('Y-m-d H:i:s'); ?> by <?= htmlspecialchars($_SESSION['auth_user']['name'] ?? 'Your Name'); ?></span>
                            <span class="font-bold text-green-800"><?= number_format($paid_amount, 2); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="bg-pink-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-red-800">Due</span>
                        <span class="text-red-800" id="bill_due"><?= number_format($due_amount, 2); ?></span>
                    </div>
                </div>
                
                <div class="bg-yellow-100 p-2 rounded mt-2">
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-yellow-800">Balance</span>
                        <span class="text-yellow-800" id="bill_balance">0.00</span>
                    </div>
                </div>
            </div>
                    </div>
                </div>

    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
        <button onclick="closePaymentModal()" class="px-6 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold">
            <i class="fas fa-times mr-2"></i>Close
        </button>
        <button onclick="submitPayment()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-bold">
            <i class="fas fa-dollar-sign mr-2"></i>Pay Now →
        </button>
    </div>
    
    <script>
    var invoiceId = '<?= $invoice_id; ?>';
    var totalAmount = <?= $total_amount; ?>;
    var paidAmount = <?= $paid_amount; ?>;
    var dueAmount = <?= $due_amount; ?>;
    var selectedPmethodId = null;
    
    function loadPaymentMethod(pmethodId) {
        selectedPmethodId = pmethodId;
        if(pmethodId) {
            // You can load payment method details here if needed
            document.getElementById('pmethod_title').textContent = 'Pmethod ' + document.getElementById('pmethod_select').options[document.getElementById('pmethod_select').selectedIndex].text;
        }
    }
    
    function fullPayment() {
        document.getElementById('pay_amount').value = dueAmount.toFixed(2);
        calculatePayment();
    }
    
    function calculatePayment() {
        var payAmount = parseFloat(document.getElementById('pay_amount').value) || 0;
        var newPaid = paidAmount + payAmount;
        var newDue = totalAmount - newPaid;
        var balance = payAmount > dueAmount ? payAmount - dueAmount : 0;
        
        document.getElementById('bill_due').textContent = newDue.toFixed(2);
        document.getElementById('bill_balance').textContent = balance.toFixed(2);
    }
    
    function submitPayment() {
        var payAmount = parseFloat(document.getElementById('pay_amount').value) || 0;
        var pmethodId = selectedPmethodId || document.getElementById('pmethod_select').value;
        var note = document.getElementById('payment_note').value;
        
        if(!pmethodId) {
            alert('Please select a payment method');
            return;
        }
        
        if(payAmount <= 0) {
            alert('Please enter a valid payment amount');
            return;
        }
        
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
                    alert('Payment processed successfully!');
                    closePaymentModal();
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error processing payment');
            }
        });
    }
    
    // Initialize calculation
    calculatePayment();
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

    // Get purchase info
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
        
        // Calculate totals
    $items_query = "SELECT * FROM purchase_item WHERE invoice_id = '$invoice_id'";
    $items_result = mysqli_query($conn, $items_query);
    
    $total_amount = 0;
    while($item = mysqli_fetch_assoc($items_result)) {
        $total_amount += floatval($item['item_total']);
    }
    
    // Calculate paid amount
    $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM purchase_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_data = mysqli_fetch_assoc($paid_result);
    $paid_amount = floatval($paid_data['total_paid'] ?? 0);
    $due_amount = $total_amount - $paid_amount;
    
    // Get payment logs
    $payment_logs_query = "SELECT pl.*, pm.name as pmethod_name 
                           FROM purchase_logs pl 
                           LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id 
                           WHERE pl.ref_invoice_id = '$invoice_id' 
                           ORDER BY pl.created_at ASC";
    $payment_logs_result = mysqli_query($conn, $payment_logs_query);
    
    // Re-fetch items for display
    mysqli_data_seek($items_result, 0);
    
    ob_start();
    ?>
    <div class="space-y-6">
        <!-- General Information -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">General Information</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                    <label class="text-xs text-slate-500 font-bold">Date</label>
                    <p class="text-sm font-semibold"><?= date('d M Y', strtotime($purchase['purchase_date'] ?? $purchase['created_at'])); ?></p>
                    </div>
                    <div>
                    <label class="text-xs text-slate-500 font-bold">Invoice Id</label>
                    <p class="text-sm font-semibold font-mono"><?= htmlspecialchars($purchase['invoice_id']); ?></p>
                    </div>
                <div>
                    <label class="text-xs text-slate-500 font-bold">Supplier</label>
                    <p class="text-sm font-semibold"><?= htmlspecialchars($purchase['supplier_name'] ?? 'No Supplier'); ?></p>
                </div>
                <div>
                    <label class="text-xs text-slate-500 font-bold">Payment Status</label>
                    <p class="text-sm font-semibold">
                        <span class="px-2 py-1 rounded <?= $purchase['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?= ucfirst($purchase['payment_status']); ?>
                        </span>
                    </p>
            </div>
                <div>
                    <label class="text-xs text-slate-500 font-bold">Note</label>
                    <p class="text-sm"><?= htmlspecialchars($purchase['purchase_note'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="text-xs text-slate-500 font-bold">Created By</label>
                    <p class="text-sm font-semibold"><?= htmlspecialchars($purchase['creator_name'] ?? 'Your Name'); ?></p>
                </div>
            </div>
                </div>
                
        <!-- Product List -->
                <div>
            <h3 class="font-bold text-lg text-slate-800 mb-4">Product List</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="border border-slate-300 p-2 text-left text-xs font-bold">Product</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Cost</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Sub Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($items_result, 0);
                        while($item = mysqli_fetch_assoc($items_result)): 
                            $qty = floatval($item['item_quantity']) - floatval($item['return_quantity']);
                        ?>
                            <tr>
                                <td class="border border-slate-300 p-2 text-sm">
                                    <?= htmlspecialchars($item['item_name']); ?> (x<?= number_format($qty, 2); ?> Pieces)
                                </td>
                                <td class="border border-slate-300 p-2 text-right text-sm"><?= number_format($item['item_purchase_price'], 2); ?></td>
                                <td class="border border-slate-300 p-2 text-right text-sm font-bold"><?= number_format($item['item_total'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Summary -->
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <h3 class="font-bold text-lg text-slate-800 mb-4">Summary</h3>
            <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                    <span class="font-bold"><?= number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Order Tax</span>
                            <span>0.00</span>
                        </div>
                            <div class="flex justify-between">
                    <span>Shipping Charge</span>
                    <span>0.00</span>
                            </div>
                            <div class="flex justify-between">
                    <span>Others Charge</span>
                    <span>0.00</span>
                            </div>
                            <div class="flex justify-between">
                    <span>Discount</span>
                    <span>0.00</span>
                            </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="font-bold">Paid Amount</span>
                    <span class="font-bold text-green-600"><?= number_format($paid_amount, 2); ?></span>
                        </div>
                <div class="flex justify-between">
                    <span class="font-bold text-red-600">Due Amount</span>
                    <span class="font-bold text-red-600"><?= number_format($due_amount, 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Payments -->
        <?php if(mysqli_num_rows($payment_logs_result) > 0): ?>
        <div>
            <h3 class="font-bold text-lg text-slate-800 mb-4">Payments</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="border border-slate-300 p-2 text-left text-xs font-bold">Description</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Amount</th>
                            <th class="border border-slate-300 p-2 text-right text-xs font-bold">Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($payment_logs_result, 0);
                        while($log = mysqli_fetch_assoc($payment_logs_result)): 
                            $log_date = date('Y-m-d H:i:s', strtotime($log['created_at']));
                            $pmethod = htmlspecialchars($log['pmethod_name'] ?? 'Unknown');
                        ?>
                            <tr class="bg-green-50">
                                <td class="border border-slate-300 p-2 text-sm">
                                    Paid on <?= $log_date; ?> (via <?= $pmethod; ?>) by <?= htmlspecialchars($_SESSION['auth_user']['name'] ?? 'Your Name'); ?>
                                </td>
                                <td class="border border-slate-300 p-2 text-right text-sm font-bold"><?= number_format($log['amount'], 2); ?></td>
                                <td class="border border-slate-300 p-2 text-right text-sm">0.00</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
        </div>
        </div>
        <?php endif; ?>
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
            
            $.ajax({
            url: '/pos/purchases/save_purchase.php',
            type: 'POST',
            data: formData + '&process_return_btn=1&invoice_id=' + invoiceId,
            dataType: 'json',
                success: function(response) {
                    if(response.status == 200) {
                    alert('Return processed successfully!');
                    closeReturnModal();
                        location.reload();
                    } else {
                    alert('Error: ' + response.message);
                    }
            },
            error: function() {
                alert('Error processing return');
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
                        $new_return_qty = $current_return_qty + $return_qty;
            
            // Update return quantity
                        $update_item = "UPDATE purchase_item SET return_quantity = '$new_return_qty' WHERE id = '$item_id'";
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
        
        mysqli_commit($conn);
        echo json_encode(['status' => 200, 'message' => 'Return processed successfully']);
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


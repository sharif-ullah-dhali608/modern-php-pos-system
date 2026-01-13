<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$invoice_id_param = $_GET['id'] ?? '';
// Handle 'INV-...' vs ID
if(strpos($invoice_id_param, 'INV') !== false) {
    // It's the string ID
    $query = "SELECT si.*, c.name as customer_name FROM selling_info si LEFT JOIN customers c ON si.customer_id = c.id WHERE si.invoice_id = '$invoice_id_param'";
} else {
    // It's probably info_id (int) or invoice_id
    $query = "SELECT si.*, c.name as customer_name FROM selling_info si LEFT JOIN customers c ON si.customer_id = c.id WHERE si.info_id = '$invoice_id_param' OR si.invoice_id = '$invoice_id_param'";
}

$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);

if(!$invoice){
    die("Invoice not found.");
}

// Fetch Items
$items_result = mysqli_query($conn, "SELECT * FROM selling_item WHERE invoice_id = '{$invoice['invoice_id']}'");
$items = [];
while($item = mysqli_fetch_assoc($items_result)){
    $items[] = $item;
}

$page_title = "Return > " . $invoice['invoice_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #e2e8f0; } /* Slate background */
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        /* Modal specific styling mimicking the image */
        .header-strip {
            background: #84cc16; /* Lime green */
            color: white;
            padding: 10px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-title {
            text-align: center;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 6px;
            color: #334155;
        }
        .highlight-row {
            background: #e2e8f0;
            padding: 8px 10px;
            font-weight: 700;
            border-radius: 4px;
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-4">

    <!-- Container mimicking a modal -->
    <div class="bg-white w-full max-w-6xl h-[85vh] rounded-lg shadow-2xl flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="header-strip">
            <div class="flex items-center gap-2">
                <i class="fas fa-list"></i> Return > <?= $invoice['invoice_id']; ?> <i class="fas fa-sync-alt animate-spin text-xs"></i>
            </div>
            <button onclick="window.history.back()" class="bg-red-500 rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        <?php if(isset($_SESSION['message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '<?= strpos($_SESSION['message'], 'Error') !== false ? 'error' : 'success' ?>',
                    title: 'Attention',
                    text: '<?= $_SESSION['message']; ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            });
        </script>
        <?php unset($_SESSION['message']); endif; ?>

        <!-- Body -->
        <div class="flex flex-1 overflow-hidden">
            
            <!-- LEFT PANEL: Order Summary -->
            <div class="w-5/12 border-r border-slate-200 p-6 overflow-y-auto bg-slate-50">
                <div class="bg-indigo-50 p-2 rounded text-center text-xs text-indigo-700 font-bold mb-4">
                    Invoice Id: <?= $invoice['invoice_id']; ?>
                </div>

                <div class="section-title">Order Summary</div>
                
                <div class="space-y-2 mb-6 text-xs">
                    <?php foreach($items as $idx => $item): ?>
                    <div class="flex justify-between border-b border-gray-100 pb-1">
                        <span><?= $idx+1; ?>. <?= htmlspecialchars($item['item_name']); ?> (x<?= number_format($item['qty_sold'], 0); ?>)</span>
                        <span class="font-semibold"><?= number_format($item['subtotal'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-1">
                    <div class="summary-row"><span>Subtotal</span><span><?= number_format($invoice['total_items'] ?? $invoice['grand_total'], 2); ?></span></div>
                    <div class="summary-row"><span>Discount</span><span><?= number_format($invoice['discount_amount'], 2); ?></span></div>
                    <div class="summary-row"><span>Order Tax</span><span><?= number_format($invoice['tax_amount'], 2); ?></span></div>
                    <div class="summary-row"><span>Shipping Charge</span><span><?= number_format($invoice['shipping_charge'], 2); ?></span></div>
                    <div class="summary-row"><span>Other Charge</span><span><?= number_format($invoice['other_charge'], 2); ?></span></div>
                    <div class="summary-row"><span>Interest Amount</span><span>0.00</span></div>
                    <div class="summary-row text-red-500"><span>Previous Due</span><span>0.00</span></div>
                </div>

                <div class="summary-row highlight-row mt-4 mb-2">
                    <span>Payable Amount (<?= count($items); ?> items)</span>
                    <span><?= number_format($invoice['grand_total'], 2); ?></span>
                </div>
                
                <div class="space-y-1">
                     <?php
                     // Re-fetch payments logic if needed, simplify for now
                     ?>
                    <div class="summary-row bg-green-50 p-1 text-green-700 font-semibold rounded">
                        <span>Prev. Due Paid</span>
                        <span>0.00</span>
                    </div>
                    <!-- Payment History Stub -->
                    <div class="summary-row bg-green-100 p-1 text-green-800 font-semibold rounded">
                        <span>Paid by Customer</span>
                        <span><?= number_format($invoice['payment_status'] == 'paid' ? $invoice['grand_total'] : 0, 2); ?></span> 
                        <!-- Logic for paid amount needs refinement if partial payments -->
                    </div>
                     <div class="summary-row bg-red-50 p-1 text-red-700 font-semibold rounded">
                        <span>Due</span>
                        <span><?= $invoice['payment_status'] == 'paid' ? '0.00' : number_format($invoice['grand_total'], 2); ?></span>
                    </div>
                </div>

            </div>

            <!-- RIGHT PANEL: Return Item Form -->
            <div class="w-7/12 p-6 overflow-y-auto relative">
                <form id="returnForm" action="process_return.php" method="POST">
                    <input type="hidden" name="invoice_id" value="<?= $invoice['invoice_id']; ?>">
                    <input type="hidden" name="store_id" value="<?= $invoice['store_id']; ?>">
                    <input type="hidden" name="customer_id" value="<?= $invoice['customer_id']; ?>">
                    
                    <div class="section-title text-left border-b pb-2 mb-4">Return Item</div>

                    <table class="w-full text-xs text-left mb-4">
                        <thead class="bg-gray-100 text-gray-600 font-bold uppercase">
                            <tr>
                                <th class="p-3 w-10 text-center">Yes/No</th>
                                <th class="p-3">Product Name</th>
                                <th class="p-3 w-32 text-center">Return Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($items as $item): 
                                $available = $item['qty_sold'] - $item['return_item'];
                                if($available <= 0) continue;
                            ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-3 text-center align-top pt-4">
                                    <input type="checkbox" name="return_items[<?= $item['id']; ?>][selected]" value="1" 
                                           class="w-4 h-4 text-emerald-500 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer item-check"
                                           data-item-id="<?= $item['id']; ?>">
                                 </td>
                                <td class="p-3 align-top pt-4">
                                    <div class="font-semibold text-slate-700"><?= htmlspecialchars($item['item_name']); ?></div>
                                    <div class="text-slate-400 mt-1">Available: <?= number_format($available, 2); ?> (Sold: <?= number_format($item['qty_sold'], 2); ?>)</div>
                                    
                                    <textarea name="return_items[<?= $item['id']; ?>][note]" 
                                              placeholder="Type Note" 
                                              class="w-full mt-2 text-xs border border-gray-200 rounded p-2 focus:outline-none focus:border-emerald-500 input-note hidden"
                                              rows="2"></textarea>
                                </td>
                                <td class="p-3 align-top pt-4 text-center">
                                    <input type="number" name="return_items[<?= $item['id']; ?>][qty]" 
                                           value="<?= $available; ?>" max="<?= $available; ?>" min="0.01" step="0.01"
                                           class="w-full border border-gray-300 rounded px-2 py-1.5 text-center font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:outline-none input-qty disabled:bg-gray-100 disabled:text-gray-400"
                                           disabled>
                                    <input type="hidden" name="return_items[<?= $item['id']; ?>][price]" value="<?= $item['price_sold']; ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </form>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-white border-t p-4 flex justify-end gap-3 sticky bottom-0 z-10">
            <button onclick="window.history.back()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded shadow font-semibold text-sm transition flex items-center gap-2">
                <i class="fas fa-times"></i> Close
            </button>
            <button onclick="document.getElementById('returnForm').submit()" class="bg-emerald-500 hover:bg-emerald-600 text-white px-6 py-2 rounded shadow font-semibold text-sm transition flex items-center gap-2">
                <i class="fas fa-save"></i> Return Now &rarr;
            </button>
        </div>

    </div>

    <script>
        // Toggle inputs based on checkbox
        document.querySelectorAll('.item-check').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const row = this.closest('tr');
                const qtyInput = row.querySelector('.input-qty');
                const noteInput = row.querySelector('.input-note');
                
                if (this.checked) {
                    qtyInput.disabled = false;
                    noteInput.classList.remove('hidden');
                    row.classList.add('bg-emerald-50');
                } else {
                    qtyInput.disabled = true;
                    noteInput.classList.add('hidden');
                    row.classList.remove('bg-emerald-50');
                }
            });
        });
    </script>
</body>
</html>

<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    echo "Unauthorized";
    exit(0);
}

$invoice_id = $_GET['id'] ?? '';

if(empty($invoice_id)){
    echo "Invalid Invoice ID";
    exit;
}

// Fetch Selling Info with store VAT number
$query = "SELECT si.*, c.name as customer_name, c.mobile as customer_phone, c.address as customer_address, 
          s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email, s.vat_number,
          curr.symbol_left, curr.symbol_right, curr.currency_name as currency_full_name
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          LEFT JOIN stores s ON si.store_id = s.id
          LEFT JOIN currencies curr ON s.currency_id = curr.id
          WHERE si.info_id = '$invoice_id' OR si.invoice_id = '$invoice_id'";

$result = mysqli_query($conn, $query);
$invoice = mysqli_fetch_assoc($result);

if(!$invoice){
    echo "Invoice not found";
    exit;
}

// Fetch Items
$items_query = "SELECT * FROM selling_item WHERE invoice_id = '{$invoice['invoice_id']}'";
$items_result = mysqli_query($conn, $items_query);

// Check if this is an installment invoice
$installment_query = "SELECT * FROM installment_orders WHERE invoice_id = '{$invoice['invoice_id']}'";
$installment_result = mysqli_query($conn, $installment_query);
$installment_order = mysqli_fetch_assoc($installment_result);
$is_installment = ($installment_order !== null);

// Fetch installment payments if this is an installment invoice
$installment_payments = [];
$down_payment_amount = 0;
$total_installment_paid = 0;
$total_installment_due = 0;

if($is_installment){
    $down_payment_amount = $installment_order['initial_amount'] ?? 0;
    
    // Get all installment payments
    $inst_pay_query = "SELECT * FROM installment_payments WHERE invoice_id = '{$invoice['invoice_id']}' ORDER BY payment_date ASC";
    $inst_pay_result = mysqli_query($conn, $inst_pay_query);
    while($ip = mysqli_fetch_assoc($inst_pay_result)){
        $installment_payments[] = $ip;
        $total_installment_paid += $ip['paid'];
        $dueAmount = $ip['payable'] - $ip['paid'];
        if($dueAmount < 0.05) $dueAmount = 0;
        $total_installment_due += $dueAmount;
    }
}

// Fetch regular payment logs (for payment method details)
$payments_query = "SELECT sl.*, pm.name as pmethod_name, pm.id as method_id
                   FROM sell_logs sl 
                   LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
                   WHERE sl.ref_invoice_id = '{$invoice['invoice_id']}' 
                   AND sl.type IN ('payment', 'full_payment', 'partial_payment')
                   ORDER BY sl.created_at ASC";
$payments_result = mysqli_query($conn, $payments_query);
?>
<!-- Styles from invoice_view.php -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* Scoped styles for modal content to match invoice_view.php body/container */
    .receipt-container {
        background: white;
        max-width: 480px; /* Receipt width */
        margin: 0 auto;
        padding: 20px; /* Reduced padding for compact look */
        padding-bottom: 40px; /* Added extra bottom padding for "long" look */
        /* box-shadow: 0 10px 25px rgba(0,0,0,0.2); Removed box-shadow as it's inside a modal usually */
        font-size: 11px; /* Small font like POS receipt */
        color: #000;
        font-family: 'Inter', sans-serif;
        min-height: 80vh; /* Ensure it feels "long" even with less content */
    }
    .mono { font-family: 'Courier Prime', monospace; }
    
    /* Ensure the modal body itself allows for full height scrolling */
    .invoice-modal-body {
        max-height: 98vh !important; /* Maximized height */
        padding: 0 !important; /* Reset padding to let container handle it */
    }
    
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; border-bottom: 1px dashed #000; padding: 5px 0; font-weight: bold; text-transform: uppercase; }
    td { padding: 4px 0; vertical-align: top; }
    .text-right { text-align: right; }
    .border-top-dashed { border-top: 1px dashed #000; }
    .border-bottom-dashed { border-bottom: 1px dashed #000; }
    
    /* Print specific overrides */
    @media print {
        @page { size: A4 portrait; margin: 0; }
        html, body { background-color: #fff !important; margin: 0 !important; padding: 0 !important; visibility: hidden; height: 100%; }
        
        /* Show only the receipt container */
        .receipt-container, .receipt-container * {
            visibility: visible;
        }
        
        .receipt-container {
            position: fixed; /* Use fixed to anchor to page viewport */
            left: 0;
            top: 0;
            width: 100% !important;
            max-width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 1in !important; /* 1 Inch gap on all 4 sides */
            box-sizing: border-box !important;
            box-shadow: none !important;
            border: none !important;
            background: white !important;
            z-index: 9999;
        }
        
        .no-print { display: none !important; }
        
        /* Hide parent modal artifacts */
        .invoice-modal-header, .close, .pos-modal-header { display: none !important; }
        
        /* Force background colors */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Button Styles for Modal (Tailwind doesn't load in AJAX) */
    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        font-weight: bold;
        text-decoration: none;
        color: white;
        font-size: 13px;
        cursor: pointer;
        border: none;
        transition: opacity 0.2s;
        font-family: inherit;
    }
    .action-btn:hover { opacity: 0.9; }
    .btn-print-custom { background-color: #06b6d4; } /* Cyan */
    .btn-close-custom { background-color: #64748b; } /* Slate */
</style>

<div class="receipt-container" id="printableArea">
    
    <!-- Header -->
    <div class="text-center mb-4">
        <!-- Logo -->
        <div class="flex justify-center mb-1">
            <img src="/pos/assets/images/logo-fav.png" style="max-height: 50px;" alt="Logo">
        </div>
        
        <h1 class="font-bold text-sm uppercase mt-1"><?= htmlspecialchars($invoice['store_name'] ?? 'STORE NAME'); ?></h1>
        <div class="text-[10px] text-gray-600">
            <?= htmlspecialchars($invoice['store_address'] ?? 'Store Address'); ?><br>
            Mobile: <?= htmlspecialchars($invoice['store_phone'] ?? ''); ?>, Email: <?= htmlspecialchars($invoice['store_email'] ?? ''); ?>
            <?php if (!empty($invoice['vat_number'])): ?>
            <br>BIN: <?= htmlspecialchars($invoice['vat_number']); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="mb-3 leading-tight text-[10px]">
        <table style="width: 100%">
            <tr>
                <td style="width: 25%">Invoice ID:</td>
                <td class="font-bold"><?= $invoice['invoice_id']; ?></td>
            </tr>
            <tr>
                <td>Date:</td>
                <td><?= date('d M Y h:i A', strtotime($invoice['created_at'])); ?></td>
            </tr>
            <tr>
                <td>Customer:</td>
                <td><?= htmlspecialchars($invoice['customer_name'] ?? 'Walking Customer'); ?></td>
            </tr>
            <tr>
                <td>Phone:</td>
                <td><?= htmlspecialchars($invoice['customer_phone'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td>Address:</td>
                <td><?= htmlspecialchars($invoice['customer_address'] ?? '-'); ?></td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <div class="mb-2">
        <h3 class="text-center font-bold uppercase border-bottom-dashed mb-1 pb-1">Invoice</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%">SL</th>
                    <th style="width: 45%">Name</th>
                    <th class="text-right" style="width: 15%">Qty</th>
                    <th class="text-right" style="width: 15%">Price</th>
                    <th class="text-right" style="width: 20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sl = 1;
                // Reset pointer just in case
                if(mysqli_num_rows($items_result) > 0) mysqli_data_seek($items_result, 0);
                while($item = mysqli_fetch_assoc($items_result)): 
                ?>
                <tr>
                    <td><?= $sl++; ?></td>
                    <td>
                        <?= htmlspecialchars($item['item_name']); ?>
                        <!-- <div class="text-[9px] text-gray-500"><?= $item['description'] ?? ''; ?></div> -->
                    </td>
                    <td class="text-right"><?= number_format($item['qty_sold'], 2); ?></td>
                    <td class="text-right"><?= number_format($item['price_sold'], 2); ?></td>
                    <td class="text-right"><?= number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="border-top-dashed pt-1 mb-2">
        <table style="width: 100%; font-weight: 500;">
            <tr>
                <td class="text-right" style="width: 60%">Total Amount:</td>
                <td class="text-right"><?= number_format($invoice['total_items'] ?? $invoice['grand_total'], 2); ?></td> <!-- Fallback if total_items not set correctly -->
            </tr>
            
            <?php if($invoice['tax_amount'] > 0): ?>
            <tr>
                <td class="text-right">Order Tax:</td>
                <td class="text-right"><?= number_format($invoice['tax_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if($invoice['discount_amount'] > 0): ?>
            <tr>
                <td class="text-right">Discount:</td>
                <td class="text-right"><?= number_format($invoice['discount_amount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if($invoice['shipping_charge'] > 0): ?>
            <tr>
                <td class="text-right">Shipping Chrg:</td>
                <td class="text-right"><?= number_format($invoice['shipping_charge'], 2); ?></td>
            </tr>
            <?php endif; ?>

            <tr class="font-bold border-top-dashed border-bottom-dashed">
                <td class="text-right py-1">Total Due:</td>
                <?php 
                // User Request: Total Due = Total amount - down payment (for installments)
                $display_due = $invoice['grand_total'];
                if($is_installment && $down_payment_amount > 0) {
                    $display_due = $invoice['grand_total'] - $down_payment_amount;
                }
                ?>
                <td class="text-right py-1"><?= number_format($display_due, 2); ?></td>
            </tr>
            
             <?php
             // Calculate Total Paid and Due (Handling Installment Logic Integration)
             if($is_installment){
                 $amount_paid = $down_payment_amount + $total_installment_paid;
                 $due = round($total_installment_due, 2);
                 if($due < 0.05) $due = 0;
                 $paid_label = "Amt Paid (Inc. Inst):";
             } else {
                 if(mysqli_num_rows($payments_result) > 0) mysqli_data_seek($payments_result, 0);
                 $amount_paid = 0;
                 while($pay = mysqli_fetch_assoc($payments_result)){
                     $amount_paid += $pay['amount'];
                 }
                 $due = $invoice['grand_total'] - $amount_paid;
                 $paid_label = "Amount Paid:";
             }
             ?>
             
            <tr>
                <td class="text-right pt-1"><?= $paid_label; ?></td>
                <td class="text-right pt-1"><?= number_format($amount_paid, 2); ?></td>
            </tr>
            <tr>
                <td class="text-right">Due:</td>
                <td class="text-right"><?= number_format($due, 2); ?></td>
            </tr>
        </table>
    </div>
    
    <!-- In Words -->
    <div class="text-[9px] italic mb-3 text-gray-500">
        In Text: <span id="in-words" class="uppercase">...</span>
        <!-- Hidden input for total to be read by parent JS -->
        <input type="hidden" id="base-grand-total" value="<?= $invoice['grand_total'] ?? 0; ?>">
    </div>

    <!-- Payments -->
    <div class="mb-3">
        <h4 class="font-bold border-bottom-dashed mb-1 pb-1">Payments</h4>
        <table>
            <thead>
               <tr>
                   <th style="width: 10%">SL</th>
                   <th style="width: 40%">Type</th>
                   <th style="width: 25%">Method</th>
                   <th class="text-right" style="width: 25%">Amount</th>
               </tr> 
            </thead>
            <tbody>
                <?php 
                $p_sl = 1;

                if(mysqli_num_rows($payments_result) > 0):
                    mysqli_data_seek($payments_result, 0);
                    
                    // Validation Pools (Budgets)
                    // We only display payments that can be "explained" by the installment records
                    $down_payment_budget = $is_installment ? $down_payment_amount : 0;
                    $installment_payment_budget = $is_installment ? $total_installment_paid : 0;
                    
                    // Tolerance for floating point matching
                    $epsilon = 0.05;

                    while($pay = mysqli_fetch_assoc($payments_result)): 
                        $amount = floatval($pay['amount']);
                        $is_valid_row = false;
                        $display_type = "Regular Payment";

                        if ($is_installment) {
                            // 1. Check if it fits Down Payment Budget
                            if ($down_payment_budget > 0 && abs($amount - $down_payment_budget) <= $epsilon) {
                                $display_type = "Down Payment";
                                $down_payment_budget -= $amount; // Consume budget (usually fully)
                                if($down_payment_budget < 0) $down_payment_budget = 0;
                                $is_valid_row = true;
                            } 
                            // 2. Check if it fits Installment Payment Budget
                            else if ($installment_payment_budget > 0 && ($installment_payment_budget - $amount) >= -$epsilon) {
                                $display_type = "Installment Payment";
                                $installment_payment_budget -= $amount;
                                $is_valid_row = true;
                            }
                            // 3. Phantom Payment (Doesn't fit either budget) -> Skip
                            else {
                                continue; 
                            }
                        } else {
                            // Non-installment invoice: Show all
                            $is_valid_row = true;
                        }

                        if ($is_valid_row):
                            $method_name = $pay['pmethod_name'] ?? 'Cash on Hand';
                ?>
                <tr>
                    <td><?= $p_sl++; ?></td>
                    <td>
                        <div style="font-weight: 600;"><?= $display_type; ?></div>
                        <div class="text-[9px] text-gray-500"><?= date('d M Y', strtotime($pay['created_at'])); ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($method_name); ?></div>
                    </td>
                    <td class="text-right" style="font-weight: 600; color: #10b981;"><?= number_format($amount, 2); ?></td>
                </tr>
                <?php 
                        endif;
                    endwhile; 
                else:
                ?>
                <tr>
                    <td colspan="4" class="text-center text-gray-500 italic">No payments found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="text-center mt-4">
        <div class="flex justify-center">
            <svg id="barcode-modal"></svg>
        </div>
        <div class="text-[9px] text-gray-500 mt-1">
            Thank you for choosing us!<br>
            For Support: <?= htmlspecialchars($invoice['store_name'] ?? ''); ?>
        </div>
        <div class="text-[8px] text-gray-400 mt-1">Developed by STS</div>
    </div>

    <!-- Action Buttons (Screen Only) -->
    <div class="no-print mt-6" style="display: flex; gap: 10px; margin-top: 25px;">
        <button onclick="window.print()" class="action-btn btn-print-custom">
            <i class="fas fa-print" style="margin-right: 6px;"></i> Print
        </button>
        <!--
        <button class="action-btn" style="background-color: #10b981;">
            <i class="fas fa-envelope" style="margin-right: 6px;"></i> Email
        </button>
        -->
        <a href="javascript:void(0)" onclick="if(typeof closeInvoiceModal === 'function') { closeInvoiceModal(); } else { const m = document.getElementById('invoiceViewModal'); if(m) { m.remove(); } else { window.history.back(); } }" class="action-btn btn-close-custom">
            <i class="fas fa-times" style="margin-right: 6px;"></i> Close
        </a>
    </div>

</div>

<script>
    // Use a unique ID for modal barcode to avoid conflict if multiple on page
    JsBarcode("#barcode-modal", "<?= $invoice['invoice_id']; ?>", {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: true,
        fontSize: 10
    });
</script>

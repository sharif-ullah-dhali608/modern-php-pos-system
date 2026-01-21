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
          s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email, s.vat_number
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          LEFT JOIN stores s ON si.store_id = s.id
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
        $total_installment_due += ($ip['payable'] - $ip['paid']);
    }
}

// Fetch regular payment logs (for payment method details)
$payments_query = "SELECT sl.*, pm.name as pmethod_name, pm.id as method_id
                   FROM sell_logs sl 
                   LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
                   WHERE sl.ref_invoice_id = '{$invoice['invoice_id']}'
                   ORDER BY sl.created_at ASC";
$payments_result = mysqli_query($conn, $payments_query);

// Debug: Check what we're getting from sell_logs
// Uncomment to debug:
// echo "<!-- DEBUG SELL_LOGS:\n";
// mysqli_data_seek($payments_result, 0);
// while($debug_log = mysqli_fetch_assoc($payments_result)){
//     echo "ID: {$debug_log['id']}, Amount: {$debug_log['amount']}, Method: {$debug_log['pmethod_name']} (ID: {$debug_log['method_id']}), Date: {$debug_log['created_at']}\n";
// }
// echo "-->\n";
// mysqli_data_seek($payments_result, 0);
?>
<style>
    .invoice-modal-content {
        background: white;
        max-width: 650px;
        width: 100%;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        color: #000;
    }
    
    .invoice-modal-header {
        background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);
        color: white;
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 12px 12px 0 0;
        position: relative;
    }
    
    /* Hide close button */
    .invoice-modal-header button,
    .invoice-modal-header .close,
    .invoice-modal-header [onclick*="close"] {
        display: none !important;
    }
    
    .invoice-modal-body {
        padding: 20px 25px;
        max-height: 95vh;
        overflow-y: auto;
    }
    
    .text-center { text-align: center; }
    .company-logo {
        max-height: 45px;
        margin: 0 auto 8px;
        display: block;
    }
    .company-name {
        font-size: 16px;
        font-weight: 800;
        margin: 0 0 3px 0;
        text-transform: uppercase;
        color: #1e293b;
    }
    .company-info {
        font-size: 9px;
        color: #64748b;
        line-height: 1.4;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 85px 1fr;
        gap: 4px;
        font-size: 10px;
        margin: 15px 0;
        text-align: left;
        background: #f8fafc;
        padding: 10px;
        border-radius: 6px;
    }
    .info-label { color: #64748b; font-weight: 500; }
    .info-value { font-weight: 600; color: #1e293b; }
    
    .section-title {
        text-align: center;
        font-weight: 800;
        border-bottom: 2px solid #000;
        padding-bottom: 4px;
        margin: 15px 0 10px;
        font-size: 11px;
        letter-spacing: 1px;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-bottom: 12px;
    }
    thead tr { border-bottom: 1px dashed #000; }
    th {
        padding: 5px 3px;
        text-align: left;
        font-weight: 700;
        font-size: 9px;
        text-transform: uppercase;
        color: #475569;
    }
    th.text-right, td.text-right { text-align: right; }
    th.text-center, td.text-center { text-align: center; }
    tbody tr { border-bottom: 1px dashed #e2e8f0; }
    td { padding: 6px 3px; font-size: 10px; }
    
    .totals-table {
        background: #f8fafc;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .totals-table td {
        padding: 4px 0;
        font-size: 10px;
    }
    .total-row {
        font-weight: 700;
        border-top: 1px dashed #000;
        border-bottom: 1px dashed #000;
        padding-top: 6px !important;
        padding-bottom: 6px !important;
    }
    
    .payments-title {
        font-weight: 700;
        font-size: 11px;
        margin: 15px 0 8px;
        padding-bottom: 4px;
        border-bottom: 1px dashed #cbd5e1;
        color: #0d9488;
    }
    
    .footer-text {
        text-align: center;
        font-size: 9px;
        margin-top: 15px;
        padding-top: 12px;
        border-top: 1px dashed #cbd5e1;
        color: #64748b;
        line-height: 1.5;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
        padding: 12px 20px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        border-radius: 0 0 12px 12px;
    }
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 11px;
        transition: all 0.2s;
    }
    .btn-print { background: #0ea5e9; color: white; }
    .btn-print:hover { background: #0284c7; }
    .btn-email { background: #10b981; color: white; }
    .btn-email:hover { background: #059669; }
    .btn-back { background: #64748b; color: white; }
    .btn-back:hover { background: #475569; }
    
    @media print {
        /* Reset everything for clean print */
        * {
            box-shadow: none !important;
        }
        
        /* Reset body and page */
        body { 
            background: white !important; 
            padding: 0 !important; 
            margin: 0 !important; 
        }
        
        /* Remove ALL modal styling for print */
        .invoice-modal-content { 
            max-width: 100% !important; 
            width: 100% !important;
            box-shadow: none !important; 
            border-radius: 0 !important;
            background: white !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Hide modal header completely in print */
        .invoice-modal-header { 
            display: none !important;
        }
        
        /* Adjust body for print - full width */
        .invoice-modal-body { 
            max-height: none !important; 
            overflow: visible !important; 
            padding: 0 20px !important;
            background: white !important;
            margin: 0 !important;
            box-shadow: none !important;
        }
        
        /* Hide action buttons */
        .action-buttons { 
            display: none !important; 
        }
        
        /* Ensure colors print correctly */
        .invoice-modal-header,
        .badge,
        .totals-table,
        .info-grid { 
            print-color-adjust: exact !important; 
            -webkit-print-color-adjust: exact !important; 
        }
        
        /* Prevent page breaks inside tables */
        table { 
            page-break-inside: avoid !important; 
        }
        
        /* Adjust font sizes for print */
        .company-name { font-size: 18px !important; }
        .company-info { font-size: 10px !important; }
        .info-grid { font-size: 11px !important; }
        table { font-size: 11px !important; }
        th { font-size: 10px !important; }
        td { font-size: 11px !important; }
        
        /* Remove background colors from info-grid and totals-table for cleaner print */
        .info-grid,
        .totals-table {
            background: white !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        /* Ensure proper spacing */
        .section-title {
            margin: 20px 0 15px !important;
        }
        
        /* Print badges with borders instead of backgrounds */
        .badge {
            border: 1px solid currentColor !important;
            background: white !important;
        }
        .badge-down {
            color: #1e40af !important;
            border-color: #1e40af !important;
        }
        .badge-installment {
            color: #065f46 !important;
            border-color: #065f46 !important;
        }
        
        /* Remove any remaining shadows or effects */
        .invoice-modal-content,
        .invoice-modal-header,
        .invoice-modal-body,
        .info-grid,
        .totals-table,
        table,
        .badge {
            box-shadow: none !important;
            filter: none !important;
        }
    }
    
    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .badge-down { background: #dbeafe; color: #1e40af; }
    .badge-installment { background: #d1fae5; color: #065f46; }
</style>

<div class="invoice-modal-content">
    <div class="invoice-modal-header">
        <div style="font-size: 12px; font-weight: 600;">
            <i class="fas fa-file-invoice"></i> Invoice - <?= htmlspecialchars($invoice['invoice_id']); ?>
        </div>
    </div>

    <div class="invoice-modal-body">
        <div class="text-center">
            <img src="/pos/assets/images/logo-fav.png" class="company-logo" alt="Logo">
            <h1 class="company-name"><?= htmlspecialchars($invoice['store_name'] ?? 'STORE NAME'); ?></h1>
            <div class="company-info">
                <?= htmlspecialchars($invoice['store_address'] ?? 'Store Address'); ?><br>
                Mobile: <?= htmlspecialchars($invoice['store_phone'] ?? '--'); ?>, 
                Email: <?= htmlspecialchars($invoice['store_email'] ?? '--'); ?>
                <?php if (!empty($invoice['vat_number'])): ?>
                <br>BIN Number: <?= htmlspecialchars($invoice['vat_number']); ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-label">Invoice ID:</div>
            <div class="info-value"><?= htmlspecialchars($invoice['invoice_id']); ?></div>
            
            <div class="info-label">Date:</div>
            <div class="info-value"><?= date('d M Y, h:i A', strtotime($invoice['created_at'])); ?></div>
            
            <div class="info-label">Customer:</div>
            <div class="info-value"><?= htmlspecialchars($invoice['customer_name'] ?? 'Walking Customer'); ?></div>
            
            <div class="info-label">Phone:</div>
            <div class="info-value"><?= htmlspecialchars($invoice['customer_phone'] ?? '--'); ?></div>
            
            <div class="info-label">Address:</div>
            <div class="info-value"><?= htmlspecialchars($invoice['customer_address'] ?? '-'); ?></div>
        </div>

        <div class="section-title">INVOICE</div>

        <div style="margin-bottom: 12px;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">SL</th>
                        <th style="width: 50%">NAME</th>
                        <th class="text-right" style="width: 15%">QTY</th>
                        <th class="text-right" style="width: 15%">PRICE</th>
                        <th class="text-right" style="width: 15%">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sl = 1;
                    $calculated_subtotal = 0;
                    while($item = mysqli_fetch_assoc($items_result)): 
                        $calculated_subtotal += $item['subtotal'];
                    ?>
                    <tr>
                        <td><?= $sl++; ?></td>
                        <td><?= htmlspecialchars($item['item_name']); ?></td>
                        <td class="text-right"><?= number_format($item['qty_sold'], 2); ?></td>
                        <td class="text-right"><?= number_format($item['price_sold'], 2); ?></td>
                        <td class="text-right"><?= number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-table">
            <table style="margin-bottom: 0;">
                <tr>
                    <td class="text-right" style="width: 65%">Subtotal:</td>
                    <td class="text-right"><?= number_format($calculated_subtotal, 2); ?></td>
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
                    <td class="text-right">-<?= number_format($invoice['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if($invoice['shipping_charge'] > 0): ?>
                <tr>
                    <td class="text-right">Shipping:</td>
                    <td class="text-right"><?= number_format($invoice['shipping_charge'], 2); ?></td>
                </tr>
                <?php endif; ?>

                <tr class="total-row">
                    <td class="text-right">Grand Total:</td>
                    <td class="text-right"><?= number_format($invoice['grand_total'], 2); ?></td>
                </tr>
                
                <?php if($is_installment && isset($installment_order['interest_amount']) && $installment_order['interest_amount'] > 0): ?>
                <tr>
                    <td class="text-right" style="padding-top: 6px; color: #64748b;">Interest Amount:</td>
                    <td class="text-right" style="padding-top: 6px; color: #f59e0b; font-weight: 600;"><?= number_format($installment_order['interest_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php
                // Calculate Amount Paid and Due based on installment or regular payment
                if($is_installment){
                    // For installment: Down Payment + All Installment Payments
                    $amount_paid = $down_payment_amount + $total_installment_paid;
                    // Due: Remaining installments (round to avoid 0.01 issues)
                    $due_amount = round($total_installment_due, 2);
                    // If due is less than 0.01, consider it as 0
                    if($due_amount < 0.01) $due_amount = 0;
                } else {
                    // For regular invoice: Sum of all payments from sell_logs
                    mysqli_data_seek($payments_result, 0);
                    $amount_paid = 0;
                    while($pay = mysqli_fetch_assoc($payments_result)){
                        $amount_paid += $pay['amount'];
                    }
                    $due_amount = max(0, $invoice['grand_total'] - $amount_paid);
                }
                ?>
                
                <tr>
                    <td class="text-right" style="padding-top: 6px;"><?= $is_installment ? 'Amount Paid With Interest:' : 'Amount Paid:'; ?></td>
                    <td class="text-right" style="padding-top: 6px; color: #10b981; font-weight: 700;"><?= number_format($amount_paid, 2); ?></td>
                </tr>
                <tr>
                    <td class="text-right">Due Amount:</td>
                    <td class="text-right" style="color: <?= $due_amount > 0 ? '#ef4444' : '#10b981'; ?>; font-weight: 700;"><?= number_format($due_amount, 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Payments Section -->
        <?php if($is_installment && ($down_payment_amount > 0 || count($installment_payments) > 0)): ?>
        <div class="payments-title">Payments</div>
        <table>
            <thead>
               <tr>
                   <th style="width: 8%">SL</th>
                   <th style="width: 35%">TYPE</th>
                   <th style="width: 35%">METHOD</th>
                   <th class="text-right" style="width: 22%">AMOUNT</th>
               </tr> 
            </thead>
            <tbody>
                <?php 
                $p_sl = 1;
                
                // Show Down Payment first
                if($down_payment_amount > 0):
                    // Get down payment method from sell_logs (first payment is down payment)
                    mysqli_data_seek($payments_result, 0);
                    $down_payment_method = 'Cash on Hand';
                    $down_payment_date = $invoice['created_at'];
                    $first_payment = mysqli_fetch_assoc($payments_result);
                    if($first_payment){
                        $down_payment_method = $first_payment['pmethod_name'] ?? 'Cash on Hand';
                        $down_payment_date = $first_payment['created_at'];
                    }
                ?>
                <tr>
                    <td><?= $p_sl++; ?></td>
                    <td>
                        <span class="badge badge-down">Down Payment</span>
                        <div style="font-size: 8px; color: #64748b; margin-top: 2px;"><?= date('d M Y, h:i A', strtotime($down_payment_date)); ?></div>
                    </td>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($down_payment_method); ?></td>
                    <td class="text-right" style="font-weight: 600; color: #10b981;"><?= number_format($down_payment_amount, 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php 
                // Show Installment Payments (only paid ones)
                // First, collect all sell_logs payments into an array
                mysqli_data_seek($payments_result, 0);
                $all_sell_logs = [];
                while($log = mysqli_fetch_assoc($payments_result)){
                    $all_sell_logs[] = $log;
                }
                
                // Track which sell_log we're on
                $sell_log_index = 0;
                
                // If there's a down payment, skip the first sell_log
                if($down_payment_amount > 0){
                    $sell_log_index = 1;
                }
                
                foreach($installment_payments as $inst_index => $ip):
                    if($ip['paid'] > 0):
                        // Get the corresponding sell_log entry
                        $inst_method = 'Cash on Hand';
                        $inst_date = $ip['payment_date'];
                        
                        // Try to match by index first
                        if(isset($all_sell_logs[$sell_log_index])){
                            $matched_log = $all_sell_logs[$sell_log_index];
                            $inst_method = $matched_log['pmethod_name'] ?? 'Cash on Hand';
                            $inst_date = $matched_log['created_at'];
                            $sell_log_index++; // Increment AFTER using
                        } else {
                            // Fallback: try to match by amount
                            foreach($all_sell_logs as $log){
                                if(abs($log['amount'] - $ip['paid']) < 0.01){
                                    $inst_method = $log['pmethod_name'] ?? 'Cash on Hand';
                                    $inst_date = $log['created_at'];
                                    break;
                                }
                            }
                        }
                ?>
                <tr>
                    <td><?= $p_sl++; ?></td>
                    <td>
                        <span class="badge badge-installment">Installment</span>
                        <div style="font-size: 8px; color: #64748b; margin-top: 2px;"><?= date('d M Y, h:i A', strtotime($inst_date)); ?></div>
                    </td>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($inst_method); ?></td>
                    <td class="text-right" style="font-weight: 600; color: #10b981;"><?= number_format($ip['paid'], 2); ?></td>
                </tr>
                <?php 
                    endif;
                endforeach; 
                ?>
            </tbody>
        </table>
        <?php elseif(mysqli_num_rows($payments_result) > 0): ?>
        <!-- Regular payments for non-installment invoices -->
        <div class="payments-title">Payments</div>
        <table>
            <thead>
               <tr>
                   <th style="width: 8%">SL</th>
                   <th style="width: 57%">METHOD</th>
                   <th class="text-right" style="width: 35%">AMOUNT</th>
               </tr> 
            </thead>
            <tbody>
                <?php 
                mysqli_data_seek($payments_result, 0);
                $p_sl = 1;
                while($pay = mysqli_fetch_assoc($payments_result)): 
                ?>
                <tr>
                    <td><?= $p_sl++; ?></td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($pay['pmethod_name'] ?? 'Cash on Hand'); ?></div>
                        <div style="font-size: 8px; color: #64748b;"><?= date('d M Y, h:i A', strtotime($pay['created_at'])); ?></div>
                    </td>
                    <td class="text-right" style="font-weight: 600; color: #10b981;"><?= number_format($pay['amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <div class="footer-text">
            Thank you for choosing us!<br>
            For Support: <?= htmlspecialchars($invoice['store_name']); ?> | Developed by STS
        </div>
    </div>

    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-print">
            <i class="fas fa-print"></i> Print
        </button>
        <button class="btn btn-email">
            <i class="fas fa-envelope"></i> Email
        </button>
        <button onclick="closeInvoiceModal()" class="btn btn-back">
            <i class="fas fa-times"></i> Close
        </button>
    </div>
</div>

<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$invoice_id = $_GET['id'] ?? '';

// Fallback: Check standard URL path if GET is empty (e.g. /invoice_view.php/1)
if(empty($invoice_id) && isset($_SERVER['PATH_INFO'])){
    $invoice_id = trim($_SERVER['PATH_INFO'], '/');
}

if(empty($invoice_id)){
    echo "Invalid Invoice ID";
    exit;
}

// Fetch Selling Info
// Note: Adjusted query to use `invoice_id` if that's what's passed, or `info_id`. 
// The link in invoice.php uses 'info_id' but let's support both or check what's passed.
$query = "SELECT si.*, c.name as customer_name, c.mobile as customer_phone, c.address as customer_address, 
          s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email
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

// Fetch Payments
$payments_query = "SELECT sl.*, pm.name as pmethod_name 
                   FROM sell_logs sl 
                   LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
                   WHERE sl.ref_invoice_id = '{$invoice['invoice_id']}'";
$payments_result = mysqli_query($conn, $payments_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?= $invoice['invoice_id']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #526658; font-family: 'Inter', sans-serif; min-height: 100vh; padding: 20px; }
        .receipt-container {
            background: white;
            max-width: 480px; /* Receipt width */
            margin: 0 auto;
            padding: 20px; /* Reduced padding for compact look */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            font-size: 11px; /* Small font like POS receipt */
            color: #000;
        }
        .mono { font-family: 'Courier Prime', monospace; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 1px dashed #000; padding: 5px 0; font-weight: bold; text-transform: uppercase; }
        td { padding: 4px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .border-top-dashed { border-top: 1px dashed #000; }
        .border-bottom-dashed { border-bottom: 1px dashed #000; }
        
        @media print {
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; max-width: 100%; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            /* Force background colors */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        
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
                    <td><?= htmlspecialchars($invoice['customer_phone'] ?? $invoice['customer_mobile'] ?? 'N/A'); ?></td>
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
                    while($item = mysqli_fetch_assoc($items_result)): 
                    ?>
                    <tr>
                        <td><?= $sl++; ?></td>
                        <td>
                            <?= htmlspecialchars($item['item_name']); ?>
                            <div class="text-[9px] text-gray-500"><?= $item['description'] ?? ''; ?></div>
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
                    <td class="text-right" style="width: 60%">Total Amt:</td>
                    <td class="text-right"><?= number_format($invoice['total_items'] ?? 0, 2); ?></td> <!-- Assuming total_items holds subtotal in this logic or derived -->
                </tr>
                <!-- Since total_items name is ambiguous in schema (count or sum?), schema says decimal, usually means amount -->
                <!-- Let's calculate from grand total backwards if needed or use fields -->
                
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
                    <td class="text-right py-1"><?= number_format($invoice['grand_total'], 2); ?></td>
                </tr>
                
                <!-- Paid logic -->
                <!-- We need to sum payments -->
                 <?php
                 // Reset payment pointer for displaying later
                 mysqli_data_seek($payments_result, 0);
                 $total_paid = 0;
                 while($pay = mysqli_fetch_assoc($payments_result)){
                     $total_paid += $pay['amount'];
                 }
                 $due = $invoice['grand_total'] - $total_paid;
                 ?>
                 
                <tr>
                    <td class="text-right pt-1">Amount Paid:</td>
                    <td class="text-right pt-1"><?= number_format($total_paid, 2); ?></td>
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
            <script>
                function numberToWords(num) {
                    const single = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
                    const double = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                    const formatTrio = (trio) => {
                        let res = '';
                        if (trio[0] !== '0') {
                            res += single[parseInt(trio[0])] + ' Hundred ';
                        }
                        if (trio[1] === '1') {
                            res += double[parseInt(trio[2])];
                        } else {
                            res += tens[parseInt(trio[1])] + (trio[1] !== '0' && trio[2] !== '0' ? '-' : '') + single[parseInt(trio[2])];
                        }
                        return res.trim();
                    };

                    let [integer, decimal] = num.toFixed(2).split('.');
                    integer = integer.padStart(12, '0');
                    const units = ['Billion', 'Million', 'Thousand', ''];
                    let result = '';

                    for (let i = 0; i < 4; i++) {
                        let trio = integer.substring(i * 3, i * 3 + 3);
                        let word = formatTrio(trio);
                        if (word) {
                            result += word + ' ' + units[i] + ' ';
                        }
                    }

                    result = result.trim() || 'Zero';
                    
                    if (decimal !== '00') {
                        let decimalWord = formatTrio('0' + decimal);
                        result += ' and ' + decimalWord + ' Cents';
                    }
                    
                    return result + ' Only';
                }
                
                document.getElementById('in-words').textContent = numberToWords(<?= (float)($invoice['grand_total'] ?? 0); ?>); 
            </script>
        </div>

        <!-- Payments -->
        <?php if(mysqli_num_rows($payments_result) > 0): ?>
        <div class="mb-3">
            <h4 class="font-bold border-bottom-dashed mb-1">Payments</h4>
            <table>
                <thead>
                   <tr>
                       <th>SL</th>
                       <th>Method</th>
                       <th class="text-right">Amount</th>
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
                        <td><?= htmlspecialchars($pay['pmethod_name'] ?? 'Cash'); ?> <span class="text-[9px] text-gray-400"><?= date('d M Y', strtotime($pay['created_at'])); ?></span></td>
                        <td class="text-right"><?= number_format($pay['amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-4">
            <div class="flex justify-center">
                <svg id="barcode"></svg>
            </div>
            <div class="text-[9px] text-gray-500 mt-1">
                Thank you for choosing us!<br>
                For Support: support@pos.com
            </div>
            <div class="text-[8px] text-gray-400 mt-1">@codecanoyn.net</div>
        </div>

        <!-- Action Buttons (Screen Only) -->
        <div class="no-print mt-6 flex flex-row gap-2">
            <button onclick="window.print()" class="flex-1 bg-cyan-500 hover:bg-cyan-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <button class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-envelope mr-1"></i> Email
            </button>
            <a href="javascript:history.back()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

    </div>

    <script>
        JsBarcode("#barcode", "<?= $invoice['invoice_id']; ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 40,
            displayValue: true,
            fontSize: 10
        });
    </script>
</body>
</html>

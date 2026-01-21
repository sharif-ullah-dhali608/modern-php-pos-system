<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

// Get log ID from URL
$id = 0;
if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
} else {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    $last_segment = end($segments);
    if(is_numeric($last_segment)) {
        $id = intval($last_segment);
    }
}

if($id <= 0) {
    header("Location: sell_log.php");
    exit(0);
}

// Get log info with all related data
$query = "SELECT sl.*, 
          c.name as customer_name, c.mobile as customer_phone, c.address as customer_address,
          pm.name as pmethod_name, pm.code as pmethod_code,
          u.name as created_by_name,
          s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email
          FROM sell_logs sl 
          LEFT JOIN customers c ON sl.customer_id = c.id 
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id
          LEFT JOIN users u ON sl.created_by = u.id
          LEFT JOIN stores s ON sl.store_id = s.id
          WHERE sl.id = $id";

$result = mysqli_query($conn, $query);
$log = mysqli_fetch_assoc($result);

if(!$log) {
    echo "Payment log not found";
    exit;
}

// Get related invoice if available
$invoice = null;
$invoice_items = [];
if(!empty($log['ref_invoice_id'])) {
    $inv_query = mysqli_query($conn, "SELECT * FROM selling_info WHERE invoice_id = '{$log['ref_invoice_id']}'");
    $invoice = mysqli_fetch_assoc($inv_query);
    
    if($invoice) {
        $items_query = mysqli_query($conn, "SELECT * FROM selling_item WHERE invoice_id = '{$log['ref_invoice_id']}'");
        while($item = mysqli_fetch_assoc($items_query)) {
            $invoice_items[] = $item;
        }
    }
}

// Fix payment method display - show 'Cash' if NULL
$payment_method_display = $log['pmethod_name'] ?? 'Cash';
if (empty($payment_method_display) || $payment_method_display === 'N/A') {
    $payment_method_display = 'Cash';
}

// Fix phone display - use walking customer mobile from invoice if customer_id is NULL
$phone_display = $log['customer_phone'] ?? null;
if (empty($phone_display) && $invoice && !empty($invoice['customer_mobile'])) {
    $phone_display = $invoice['customer_mobile'];
}
if (empty($phone_display)) {
    $phone_display = 'N/A';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Log - <?= $log['reference_no']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsbarcode/3.11.5/JsBarcode.all.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #526658; font-family: 'Inter', sans-serif; min-height: 100vh; padding: 20px; }
        .receipt-container {
            background: white;
            max-width: 480px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            font-size: 11px;
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
            
            <h1 class="font-bold text-sm uppercase mt-1"><?= htmlspecialchars($log['store_name'] ?? 'STORE NAME'); ?></h1>
            <div class="text-[10px] text-gray-600">
                <?= htmlspecialchars($log['store_address'] ?? 'Store Address'); ?><br>
                Mobile: <?= htmlspecialchars($log['store_phone'] ?? ''); ?>, Email: <?= htmlspecialchars($log['store_email'] ?? ''); ?>
            </div>
        </div>

        <!-- Type Badge -->
        <div class="text-center mb-3">
            <span class="inline-block <?= $log['type'] === 'payment' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?> px-4 py-1 rounded-full text-xs font-bold uppercase">
                <i class="fas fa-<?= $log['type'] === 'payment' ? 'money-bill-wave' : 'exchange-alt'; ?> mr-1"></i> 
                <?= strtoupper($log['type']); ?>
            </span>
        </div>

        <!-- Info Grid -->
        <div class="mb-3 leading-tight text-[10px]">
            <table style="width: 100%">
                <tr>
                    <td style="width: 30%">Reference No:</td>
                    <td class="font-bold"><?= $log['reference_no']; ?></td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td><?= date('d M Y h:i A', strtotime($log['created_at'])); ?></td>
                </tr>
                <tr>
                    <td>Type:</td>
                    <td class="font-bold uppercase"><?= $log['type']; ?></td>
                </tr>
                <tr>
                    <td>Customer:</td>
                    <td><?= htmlspecialchars($log['customer_name'] ?? 'Walking Customer'); ?></td>
                </tr>
                <tr>
                    <td>Phone:</td>
                    <td><?= htmlspecialchars($phone_display); ?></td>
                </tr>
                <tr>
                    <td>Payment Method:</td>
                    <td class="font-bold"><?= htmlspecialchars($payment_method_display); ?></td>
                </tr>
                <?php if(!empty($log['transaction_id'])): ?>
                <tr>
                    <td>Transaction ID:</td>
                    <td class="font-bold"><?= htmlspecialchars($log['transaction_id']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Related Invoice:</td>
                    <td class="font-bold"><?= htmlspecialchars($log['ref_invoice_id'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Created By:</td>
                    <td><?= htmlspecialchars($log['created_by_name'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>

        <!-- Payment Amount -->
        <div class="border-top-dashed border-bottom-dashed py-2 mb-2">
            <table style="width: 100%;">
                <tr class="font-bold text-lg">
                    <td class="text-right" style="width: 60%"><?= $log['type'] === 'payment' ? 'Payment Amount:' : 'Amount:'; ?></td>
                    <td class="text-right text-emerald-600">৳<?= number_format($log['amount'], 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Description -->
        <?php if(!empty($log['description'])): ?>
        <div class="mb-3 p-2 bg-gray-50 rounded text-[10px]">
            <strong>Description:</strong> <?= htmlspecialchars($log['description']); ?>
        </div>
        <?php endif; ?>

        <!-- Related Invoice Items (if any) -->
        <?php if($invoice && count($invoice_items) > 0): ?>
        <div class="mb-2">
            <h3 class="text-center font-bold uppercase border-bottom-dashed mb-1 pb-1">Related Invoice Items</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">SL</th>
                        <th style="width: 50%">Name</th>
                        <th class="text-right" style="width: 15%">Qty</th>
                        <th class="text-right" style="width: 30%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sl = 1;
                    foreach($invoice_items as $item): 
                    ?>
                    <tr>
                        <td><?= $sl++; ?></td>
                        <td><?= htmlspecialchars($item['item_name']); ?></td>
                        <td class="text-right"><?= number_format($item['qty_sold'], 2); ?></td>
                        <td class="text-right"><?= number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="border-top-dashed pt-1 mt-1">
                <table style="width: 100%; font-weight: 500;">
                    <tr>
                        <td class="text-right" style="width: 60%">Invoice Total:</td>
                        <td class="text-right"><?= number_format($invoice['grand_total'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="text-right">Invoice Status:</td>
                        <td class="text-right">
                            <span class="<?= ($invoice['payment_status'] ?? '') === 'paid' ? 'text-emerald-600' : 'text-red-600'; ?> font-bold uppercase">
                                <?= $invoice['payment_status'] ?? 'N/A'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

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
                        if (trio[0] !== '0') res += single[parseInt(trio[0])] + ' Hundred ';
                        if (trio[1] === '1') res += double[parseInt(trio[2])];
                        else res += tens[parseInt(trio[1])] + (trio[1] !== '0' && trio[2] !== '0' ? '-' : '') + single[parseInt(trio[2])];
                        return res.trim();
                    };
                    let [integer, decimal] = num.toFixed(2).split('.');
                    integer = integer.padStart(12, '0');
                    const units = ['Billion', 'Million', 'Thousand', ''];
                    let result = '';
                    for (let i = 0; i < 4; i++) {
                        let trio = integer.substring(i * 3, i * 3 + 3);
                        let word = formatTrio(trio);
                        if (word) result += word + ' ' + units[i] + ' ';
                    }
                    result = result.trim() || 'Zero';
                    if (decimal !== '00') result += ' and ' + formatTrio('0' + decimal) + ' Cents';
                    return result + ' Only';
                }
                document.getElementById('in-words').textContent = numberToWords(<?= (float)($log['amount'] ?? 0); ?>); 
            </script>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4">
            <div class="flex justify-center">
                <svg id="barcode"></svg>
            </div>
            <div class="text-[9px] text-gray-500 mt-1">
                Thank you for choosing us!<br>
                For Support: support@pos.com
            </div>
        </div>

        <!-- Action Buttons (Screen Only) -->
        <div class="no-print mt-6 flex flex-row gap-2">
            <button onclick="window.print()" class="flex-1 bg-cyan-500 hover:bg-cyan-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-print mr-1"></i> Print
            </button>
            <button class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-envelope mr-1"></i> Email
            </button>
            <a href="/pos/sell/log" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

    </div>

    <script>
        JsBarcode("#barcode", "<?= $log['reference_no']; ?>", {
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

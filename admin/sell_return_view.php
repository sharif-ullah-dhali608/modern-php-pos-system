<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

// Get return ID from URL
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
    header("Location: sell_return.php");
    exit(0);
}

// Get return info with all related data
$query = "SELECT si.*, c.name as customer_name, c.mobile as customer_phone, c.address as customer_address, 
          s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email,
          curr.symbol_left, curr.symbol_right, curr.currency_name as currency_full_name,
          u.name as created_by_name
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          LEFT JOIN stores s ON si.store_id = s.id
          LEFT JOIN currencies curr ON s.currency_id = curr.id
          LEFT JOIN users u ON si.created_by = u.id
          WHERE si.info_id = $id AND si.inv_type = 'return'";

$result = mysqli_query($conn, $query);
$return = mysqli_fetch_assoc($result);

if(!$return) {
    echo "Return record not found";
    exit;
}

// Fetch Items
$items_query = "SELECT * FROM selling_item WHERE invoice_id = '{$return['invoice_id']}'";
$items_result = mysqli_query($conn, $items_query);

// Get original invoice info if available
$original_invoice = null;
if(!empty($return['ref_invoice_id'])) {
    $orig_query = mysqli_query($conn, "SELECT * FROM selling_info WHERE invoice_id = '{$return['ref_invoice_id']}'");
    $original_invoice = mysqli_fetch_assoc($orig_query);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return - <?= $return['invoice_id']; ?></title>
    <link rel="stylesheet" href="/pos/assets/css/output.css">
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
            
            <h1 class="font-bold text-sm uppercase mt-1"><?= htmlspecialchars($return['store_name'] ?? 'STORE NAME'); ?></h1>
            <div class="text-[10px] text-gray-600">
                <?= htmlspecialchars($return['store_address'] ?? 'Store Address'); ?><br>
                Mobile: <?= htmlspecialchars($return['store_phone'] ?? ''); ?>, Email: <?= htmlspecialchars($return['store_email'] ?? ''); ?>
            </div>
        </div>

        <!-- Return Badge -->
        <div class="text-center mb-3">
            <span class="inline-block bg-red-100 text-red-700 px-4 py-1 rounded-full text-xs font-bold uppercase">
                <i class="fas fa-undo-alt mr-1"></i> SELL RETURN
            </span>
        </div>

        <!-- Info Grid -->
        <div class="mb-3 leading-tight text-[10px]">
            <table style="width: 100%">
                <tr>
                    <td style="width: 30%">Return ID:</td>
                    <td class="font-bold"><?= $return['invoice_id']; ?></td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td><?= date('d M Y h:i A', strtotime($return['created_at'])); ?></td>
                </tr>
                <tr>
                    <td>Original Invoice:</td>
                    <td class="font-bold"><?= htmlspecialchars($return['ref_invoice_id'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Customer:</td>
                    <td><?= htmlspecialchars($return['customer_name'] ?? 'Walking Customer'); ?></td>
                </tr>
                <tr>
                    <td>Phone:</td>
                    <td><?= htmlspecialchars($return['customer_phone'] ?? $return['customer_mobile'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Address:</td>
                    <td><?= htmlspecialchars($return['customer_address'] ?? '-'); ?></td>
                </tr>
                <tr>
                    <td>Returned By:</td>
                    <td><?= htmlspecialchars($return['created_by_name'] ?? 'N/A'); ?></td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <div class="mb-2">
            <h3 class="text-center font-bold uppercase border-bottom-dashed mb-1 pb-1">Returned Items</h3>
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
        <div class="border-top-dashed pt-1 mb-2">
            <table style="width: 100%; font-weight: 500;">
                <tr>
                    <td class="text-right" style="width: 60%">Subtotal:</td>
                    <td class="text-right"><?= number_format($return['total_items'] ?? 0, 2); ?></td>
                </tr>
                
                <?php if($return['tax_amount'] > 0): ?>
                <tr>
                    <td class="text-right">Tax:</td>
                    <td class="text-right"><?= number_format($return['tax_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                
                <?php if($return['discount_amount'] > 0): ?>
                <tr>
                    <td class="text-right">Discount:</td>
                    <td class="text-right"><?= number_format($return['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>

                <tr class="font-bold border-top-dashed border-bottom-dashed">
                    <td class="text-right py-1 text-red-600">Total Return Amount:</td>
                    <td class="text-right py-1 text-red-600"><?= number_format($return['grand_total'], 2); ?></td>
                </tr>
            </table>
        </div>

        <!-- Return Note -->
        <?php if(!empty($return['invoice_note'])): ?>
        <div class="mb-3 p-2 bg-gray-50 rounded text-[10px]">
            <strong>Return Note:</strong> <?= htmlspecialchars($return['invoice_note']); ?>
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
                    const currencyName = "<?= $return['currency_full_name'] ?? 'Taka'; ?>";
                    return result + ' ' + currencyName + ' Only';
                }
                document.getElementById('in-words').textContent = numberToWords(<?= (float)($return['grand_total'] ?? 0); ?>); 
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
            <a href="/pos/sell/return" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded font-bold text-xs transition flex items-center justify-center">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>

    </div>

    <script>
        JsBarcode("#barcode", "<?= $return['invoice_id']; ?>", {
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

<?php
session_start();
require_once('../config/dbcon.php');

header('Content-Type: application/json');

try {
    if(!isset($_SESSION['auth'])){
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit(0);
    }

    $invoice_id = mysqli_real_escape_string($conn, $_GET['invoice_id'] ?? '');

    if(empty($invoice_id)) {
        echo json_encode(['success' => false, 'message' => 'Invoice ID required']);
        exit(0);
    }

    // Fetch invoice and store/customer info
    $invoice_query = "SELECT si.*, 
                      s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email,
                      c.name as customer_name, c.mobile as customer_phone
                      FROM selling_info si
                      LEFT JOIN stores s ON si.store_id = s.id
                      LEFT JOIN customers c ON si.customer_id = c.id
                      WHERE si.invoice_id = '$invoice_id'";

    $invoice_result = mysqli_query($conn, $invoice_query);
    
    if(!$invoice_result) {
        echo json_encode(['success' => false, 'message' => 'Database query error: ' . mysqli_error($conn)]);
        exit(0);
    }
    
    $invoice = mysqli_fetch_assoc($invoice_result);

    if(!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found for ID: ' . $invoice_id]);
        exit(0);
    }

    // Fetch items
    $items_query = "SELECT item_name, qty_sold, price_sold, subtotal 
                    FROM selling_item 
                    WHERE invoice_id = '$invoice_id' AND invoice_type = 'sale'
                    ORDER BY id ASC";

    $items_result = mysqli_query($conn, $items_query);
    $items = [];

    if($items_result) {
        while($item = mysqli_fetch_assoc($items_result)) {
            $items[] = [
                'name' => $item['item_name'],
                'qty' => floatval($item['qty_sold']),
                'price' => floatval($item['price_sold']),
                'total' => floatval($item['subtotal']),
                'unit' => ''
            ];
        }
    }

    // Calculate totals
    $returns_query = "SELECT IFNULL(SUM(grand_total), 0) as returns FROM selling_info WHERE ref_invoice_id = '$invoice_id' AND inv_type = 'return'";
    $returns_result = mysqli_query($conn, $returns_query);
    $returns = $returns_result ? (mysqli_fetch_assoc($returns_result)['returns'] ?? 0) : 0;
    
    $net_amount = $invoice['grand_total'] - $returns;

    $paid_query = "SELECT IFNULL(SUM(amount), 0) as paid FROM sell_logs WHERE ref_invoice_id = '$invoice_id' AND type IN ('payment', 'full_payment', 'partial_payment', 'due_paid')";
    $paid_result = mysqli_query($conn, $paid_query);
    $paid_amount = $paid_result ? (mysqli_fetch_assoc($paid_result)['paid'] ?? 0) : 0;

    $current_due = max(0, $net_amount - $paid_amount);

    // Get payment method name
    $payment_method = 'Cash';
    $pm_query = "SELECT pm.name FROM sell_logs sl 
                 LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id 
                 WHERE sl.ref_invoice_id = '$invoice_id' 
                 ORDER BY sl.created_at DESC LIMIT 1";
    $pm_result = mysqli_query($conn, $pm_query);
    if($pm_result && $pm_row = mysqli_fetch_assoc($pm_result)) {
        $payment_method = $pm_row['name'] ?? 'Cash';
    }

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoice['invoice_id'],
        'store' => [
            'name' => $invoice['store_name'] ?? 'Modern POS',
            'address' => $invoice['store_address'] ?? '',
            'city' => '',
            'phone' => $invoice['store_phone'] ?? '',
            'email' => $invoice['store_email'] ?? ''
        ],
        'customer' => [
            'name' => $invoice['customer_name'] ?? 'Walking Customer',
            'phone' => $invoice['customer_phone'] ?? ''
        ],
        'items' => $items,
        'totals' => [
            'subtotal' => floatval($invoice['subtotal'] ?? 0),
            'discount' => floatval($invoice['discount'] ?? 0),
            'tax' => floatval($invoice['tax_amount'] ?? 0),
            'shipping' => floatval($invoice['shipping_charge'] ?? 0),
            'grandTotal' => floatval($net_amount),
            'paid' => floatval($paid_amount),
            'due' => floatval($current_due),
            'previousDue' => 0,
            'totalDue' => floatval($current_due)
        ],
        'payment_method' => $payment_method
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

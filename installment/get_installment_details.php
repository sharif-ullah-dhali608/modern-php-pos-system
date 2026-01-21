<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Fetch installment order details
    $query = "SELECT io.*, si.grand_total as sale_total, si.created_at as sale_date, 
                     c.name as customer_name, c.mobile as customer_mobile,
                     s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email
              FROM installment_orders io
              JOIN selling_info si ON io.invoice_id = si.invoice_id
              JOIN customers c ON si.customer_id = c.id
              JOIN stores s ON io.store_id = s.id
              WHERE io.id = $id";
              
    $res = mysqli_query($conn, $query);
    $installment = mysqli_fetch_assoc($res);

    if ($installment) {
        $invoice_id = $installment['invoice_id'];
        
        // Fetch scheduled payments
        $pay_query = "SELECT * FROM installment_payments WHERE invoice_id = '$invoice_id' ORDER BY payment_date ASC";
        $pay_res = mysqli_query($conn, $pay_query);
        $payments = [];
        while ($row = mysqli_fetch_assoc($pay_res)) {
            $payments[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $installment,
            'payments' => $payments
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Installment not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
}
?>

<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

if($action === 'pay_installment'){
    $payment_id = mysqli_real_escape_string($conn, $_POST['payment_id'] ?? '');
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id'] ?? '');
    $amount_received = floatval($_POST['amount_received'] ?? 0);
    $payment_method_id = mysqli_real_escape_string($conn, $_POST['payment_method_id'] ?? '');
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
    
    // Validate payment data with detailed error messages
    if(empty($payment_id)){
        echo json_encode(['success' => false, 'message' => 'Payment ID is required']);
        exit;
    }
    
    if(empty($invoice_id)){
        echo json_encode(['success' => false, 'message' => 'Invoice ID is required']);
        exit;
    }
    
    if($amount_received <= 0){
        echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than 0. Received: ' . $amount_received]);
        exit;
    }
    
    if(empty($payment_method_id)){
        echo json_encode(['success' => false, 'message' => 'Payment method is required']);
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get current payment details
        $payment_query = "SELECT * FROM installment_payments WHERE id = '$payment_id'";
        $payment_result = mysqli_query($conn, $payment_query);
        $payment = mysqli_fetch_assoc($payment_result);
        
        if(!$payment){
            throw new Exception('Payment not found');
        }
        
        
        // Update payment record
        $new_paid = $payment['paid'] + $amount_received;
        $new_due = $payment['payable'] - $new_paid;
        
        // Handle rounding errors - treat due <= 0.01 as fully paid
        if ($new_due <= 0.01) {
            $new_due = 0;
            $new_status = 'paid';
        } else {
            $new_status = 'due';
        }
        
        $update_query = "UPDATE installment_payments 
                        SET paid = '$new_paid', 
                            due = '$new_due', 
                            payment_status = '$new_status'
                        WHERE id = '$payment_id'";
        
        if(!mysqli_query($conn, $update_query)){
            throw new Exception('Failed to update payment');
        }
        
        // Get invoice details for store_id and customer_id
        $invoice_query = "SELECT store_id, customer_id FROM selling_info WHERE invoice_id = '$invoice_id'";
        $invoice_result = mysqli_query($conn, $invoice_query);
        $invoice_data = mysqli_fetch_assoc($invoice_result);
        
        $store_id = $invoice_data['store_id'] ?? 1;
        $customer_id = $invoice_data['customer_id'] ?? 0;
        $created_by = $_SESSION['auth_user']['user_id'] ?? 1;
        
        // Get transaction ID if provided
        $transaction_id = isset($_POST['transaction_id']) ? mysqli_real_escape_string($conn, $_POST['transaction_id']) : '';
        $transaction_id_sql = !empty($transaction_id) ? "'$transaction_id'" : 'NULL';
        
        // Insert payment log
        $reference_no = 'PAY-' . time() . '-' . rand(1000, 9999);
        $log_query = "INSERT INTO sell_logs (customer_id, reference_no, ref_invoice_id, type, amount, store_id, created_by, pmethod_id, transaction_id, created_at) 
                     VALUES ('$customer_id', '$reference_no', '$invoice_id', 'payment', '$amount_received', '$store_id', '$created_by', '$payment_method_id', $transaction_id_sql, NOW())";
        
        if(!mysqli_query($conn, $log_query)){
            throw new Exception('Failed to create payment log');
        }
        
        
        
        // Note: installment_orders table doesn't have total_paid/total_due columns
        // Payment tracking is done through installment_payments table only
        
        // Check if customer has fully paid all installments
        // If so, update has_installment = 0 in customers table
        $check_due_query = "SELECT SUM(ip.due) as total_remaining_due 
                           FROM installment_payments ip
                           JOIN selling_info si ON ip.invoice_id = si.invoice_id
                           WHERE si.customer_id = '$customer_id'";
        $check_due_result = mysqli_query($conn, $check_due_query);
        $due_data = mysqli_fetch_assoc($check_due_result);
        $total_remaining_due = $due_data['total_remaining_due'] ?? 0;
        
        if ($total_remaining_due <= 0.01) {
            mysqli_query($conn, "UPDATE customers SET has_installment = 0 WHERE id = '$customer_id'");
        }
        
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment processed successfully',
            'new_paid' => $new_paid,
            'new_due' => $new_due,
            'status' => $new_status
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>

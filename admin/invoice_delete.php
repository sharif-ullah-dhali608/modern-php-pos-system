<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

// Security Check
if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit(0);
}

// Handle both GET 'id' and POST 'delete_id'
$id = intval($_GET['id'] ?? $_POST['delete_id'] ?? $_POST['id'] ?? 0);

if($id <= 0) {
    $_SESSION['message'] = "Invalid Invoice ID";
    $_SESSION['msg_type'] = "error";
    header("Location: invoice.php");
    exit(0);
}

// Get invoice info first
$invoice_query = mysqli_query($conn, "SELECT * FROM selling_info WHERE info_id = $id");
$invoice = mysqli_fetch_assoc($invoice_query);

if(!$invoice) {
    $_SESSION['message'] = "Invoice not found";
    $_SESSION['msg_type'] = "error";
    header("Location: invoice.php");
    exit(0);
}

$invoice_id = $invoice['invoice_id'];
$customer_id = $invoice['customer_id'];
$grand_total = floatval($invoice['grand_total']);

mysqli_begin_transaction($conn);

try {
    // Get total payments made for this invoice
    $payment_result = mysqli_query($conn, "SELECT IFNULL(SUM(amount), 0) as total_paid FROM sell_logs WHERE ref_invoice_id = '$invoice_id' AND type = 'payment'");
    $payment_row = mysqli_fetch_assoc($payment_result);
    $total_paid = floatval($payment_row['total_paid']);
    
    // Calculate due that was added to customer
    $due_added = $grand_total - $total_paid;
    
    // Revert customer current_due if needed
    if($customer_id && $due_added > 0) {
        mysqli_query($conn, "UPDATE customers SET current_due = current_due - $due_added WHERE id = $customer_id");
    }
    
    // Restore product stock
    $items_result = mysqli_query($conn, "SELECT item_id, qty_sold FROM selling_item WHERE invoice_id = '$invoice_id' AND invoice_type = 'sale'");
    while($item = mysqli_fetch_assoc($items_result)) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['qty_sold']);
        mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock + $qty WHERE id = $item_id");
    }
    
    // Delete related records
    mysqli_query($conn, "DELETE FROM sell_logs WHERE ref_invoice_id = '$invoice_id'");
    mysqli_query($conn, "DELETE FROM selling_item WHERE invoice_id = '$invoice_id'");
    mysqli_query($conn, "DELETE FROM selling_info WHERE info_id = $id");
    
    mysqli_commit($conn);
    
    $_SESSION['message'] = "Invoice deleted successfully";
    $_SESSION['msg_type'] = "success";
    
    // Redirect back to the list page (Clean URL) or Referer
    $redirect_url = '/pos/sell/list';
    if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pos') !== false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
    
    header("Location: $redirect_url");
    exit(0);
    
} catch(Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
    
    $redirect_url = '/pos/sell/list';
    if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pos') !== false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
    
    header("Location: $redirect_url");
    exit(0);
}
?>

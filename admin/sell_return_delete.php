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
    $_SESSION['message'] = "Invalid ID";
    $_SESSION['msg_type'] = "error";
    header("Location: sell_return.php");
    exit(0);
}

// Get return info first
$return_query = mysqli_query($conn, "SELECT * FROM selling_info WHERE info_id = $id AND inv_type = 'return'");
$return = mysqli_fetch_assoc($return_query);

if(!$return) {
    $_SESSION['message'] = "Return record not found";
    $_SESSION['msg_type'] = "error";
    header("Location: sell_return.php");
    exit(0);
}

$invoice_id = $return['invoice_id'];
$customer_id = $return['customer_id'];
$grand_total = floatval($return['grand_total']);

mysqli_begin_transaction($conn);

try {
    // Restore product stock back (reverse the return - products go back out of stock)
    $items_result = mysqli_query($conn, "SELECT item_id, qty_sold FROM selling_item WHERE invoice_id = '$invoice_id'");
    while($item = mysqli_fetch_assoc($items_result)) {
        $item_id = intval($item['item_id']);
        $qty = floatval($item['qty_sold']);
        // Return was adding stock, so deleting return should remove stock
        mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock - $qty WHERE id = $item_id");
    }
    
    // Restore customer due if applicable
    if($customer_id) {
        // If return reduced due, deletion should add it back
        mysqli_query($conn, "UPDATE customers SET current_due = current_due + $grand_total WHERE id = $customer_id");
    }
    
    // Delete related records
    mysqli_query($conn, "DELETE FROM selling_item WHERE invoice_id = '$invoice_id'");
    mysqli_query($conn, "DELETE FROM selling_info WHERE info_id = $id");
    
    mysqli_commit($conn);
    $_SESSION['message'] = "Return record deleted successfully";
    $_SESSION['msg_type'] = "success";
    
    // Redirect back to clean URL or Referer
    $redirect_url = '/pos/sell/return';
    if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pos') !== false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
    
    header("Location: $redirect_url");
    exit(0);
    
} catch(Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['message'] = "Error: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
    
    $redirect_url = '/pos/sell/return';
    if(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'pos') !== false) {
        $redirect_url = $_SERVER['HTTP_REFERER'];
    }
    
    header("Location: $redirect_url");
    exit(0);
}
?>

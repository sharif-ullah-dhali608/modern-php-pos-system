<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    $_SESSION['message'] = "Unauthorized access";
    header("Location: /pos/installment/list");
    exit;
}

if (isset($_POST['delete_btn'])) {
    $id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    // Get invoice_id first to delete related payments
    $check_query = "SELECT invoice_id FROM installment_orders WHERE id = $id";
    $check_res = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_res) > 0) {
        $row = mysqli_fetch_assoc($check_res);
        $invoice_id = $row['invoice_id'];
        
        // 1. Delete payments
        mysqli_query($conn, "DELETE FROM installment_payments WHERE invoice_id = '$invoice_id'");
        
        // 2. Delete the installment order
        $delete_query = "DELETE FROM installment_orders WHERE id = $id";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['message'] = "Installment order deleted successfully";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete order";
        }
    } else {
        $_SESSION['message'] = "Order not found";
    }
}

header("Location: /pos/installment/list");
exit;
?>

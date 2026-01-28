<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $user_id = $_SESSION['auth_user']['user_id'];

    // 1. Fetch transfer info to verify status
    $check_q = mysqli_query($conn, "SELECT * FROM transfers WHERE id = '$id'");
    if (mysqli_num_rows($check_q) === 0) {
        echo json_encode(['status' => 404, 'message' => 'Transfer not found']);
        exit;
    }

    $transfer = mysqli_fetch_assoc($check_q);
    if ($transfer['status'] !== 'Sent') {
        echo json_encode(['status' => 400, 'message' => 'Only "Sent" transfers can be cancelled.']);
        exit;
    }

    $from_store_id = $transfer['from_store_id'];

    mysqli_begin_transaction($conn);

    try {
        // 2. Fetch items to reverse stock
        $items_q = mysqli_query($conn, "SELECT * FROM transfer_items WHERE transfer_id = '$id'");
        while ($item = mysqli_fetch_assoc($items_q)) {
            $product_id = $item['product_id'];
            $qty = $item['quantity'];

            // Increase stock in source store
            $update_stock = "UPDATE product_store_map 
                            SET stock = stock + $qty 
                            WHERE product_id = '$product_id' AND store_id = '$from_store_id'";
            
            if (!mysqli_query($conn, $update_stock)) {
                throw new Exception("Failed to update stock for product ID: $product_id");
            }
        }

        // 3. Update transfer status
        $update_status = "UPDATE transfers 
                         SET status = 'Cancelled', 
                             cancelled_by = '$user_id',
                             updated_at = NOW() 
                         WHERE id = '$id'";
        
        if (!mysqli_query($conn, $update_status)) {
            throw new Exception("Failed to update transfer status");
        }

        mysqli_commit($conn);
        echo json_encode(['status' => 200, 'message' => 'Transfer cancelled successfully and stock restored.']);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 400, 'message' => 'Invalid request']);
}

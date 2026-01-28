<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
    exit;
}

$id = mysqli_real_escape_string($conn, $_POST['id']);
$user_id = $_SESSION['auth_user']['user_id'];

mysqli_begin_transaction($conn);

try {
    // 1. Fetch transfer details
    $transfer_q = mysqli_query($conn, "SELECT * FROM transfers WHERE id = '$id' AND status = 'Sent' FOR UPDATE");
    $transfer = mysqli_fetch_assoc($transfer_q);

    if (!$transfer) {
        throw new Exception('Transfer not found or already processed.');
    }

    $to_store_id = $transfer['to_store_id'];

    // 2. Fetch transfer items
    $items_q = mysqli_query($conn, "SELECT * FROM transfer_items WHERE transfer_id = '$id'");
    
    while ($item = mysqli_fetch_assoc($items_q)) {
        $p_id = $item['product_id'];
        $qty = $item['quantity'];

        // 3. Add stock to destination store
        // Check if map exists
        $check_map = mysqli_query($conn, "SELECT id FROM product_store_map WHERE product_id = '$p_id' AND store_id = '$to_store_id'");
        
        if (mysqli_num_rows($check_map) > 0) {
            $update_query = "UPDATE product_store_map SET stock = stock + $qty WHERE product_id = '$p_id' AND store_id = '$to_store_id'";
            if (!mysqli_query($conn, $update_query)) {
                throw new Exception("Failed to update stock for product ID: $p_id");
            }
        } else {
            // Create map if it doesn't exist
            $insert_query = "INSERT INTO product_store_map (product_id, store_id, stock) VALUES ('$p_id', '$to_store_id', '$qty')";
            if (!mysqli_query($conn, $insert_query)) {
                throw new Exception("Failed to create stock map for product ID: $p_id");
            }
        }
    }

    // 4. Update transfer status
    $update_status = "UPDATE transfers SET status = 'Received', received_by = '$user_id' WHERE id = '$id'";
    if (!mysqli_query($conn, $update_status)) {
        throw new Exception('Failed to update transfer status.');
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 200, 'message' => 'Stock received and inventory updated successfully.']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
?>

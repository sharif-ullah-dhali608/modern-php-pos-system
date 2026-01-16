<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit(0);
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_add_qty') {
    $product_id = intval($_POST['product_id']);
    $store_id = intval($_POST['store_id']);
    $qty = floatval($_POST['qty']);

    if($product_id > 0 && $store_id > 0) {
        // Update product_store_map
        $update_query = "UPDATE product_store_map SET stock = stock + $qty WHERE product_id = $product_id AND store_id = $store_id";
        if(mysqli_query($conn, $update_query)) {
            // Also update global products.opening_stock for consistency if needed
            mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock + $qty WHERE id = $product_id");
            
            echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update stock']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>

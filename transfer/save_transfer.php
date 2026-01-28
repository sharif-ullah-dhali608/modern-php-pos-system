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

$from_store_id = mysqli_real_escape_string($conn, $_POST['from_store_id']);
$to_store_id = mysqli_real_escape_string($conn, $_POST['to_store_id']);
$ref_no = mysqli_real_escape_string($conn, $_POST['ref_no'] ?: 'TR-' . date('YmdHis'));
$status = mysqli_real_escape_string($conn, $_POST['status']);
$note = mysqli_real_escape_string($conn, $_POST['note']);
$created_by = $_SESSION['auth_user']['user_id'];

$product_ids = $_POST['product_id'] ?? [];
$product_names = $_POST['product_name'] ?? [];
$quantities = $_POST['quantity'] ?? [];

if (empty($product_ids)) {
    echo json_encode(['status' => 400, 'message' => 'No products selected for transfer.']);
    exit;
}

// Handle Attachment
$attachment_path = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . uniqid() . '.' . $ext;
    $upload_dir = '../uploads/transfers/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename);
    $attachment_path = 'uploads/transfers/' . $filename;
}

mysqli_begin_transaction($conn);

try {
    $total_items = count($product_ids);
    $total_qty = array_sum($quantities);

    // Insert into transfers table
    $query = "INSERT INTO transfers (ref_no, from_store_id, to_store_id, note, total_item, total_quantity, created_by, status, attachment) 
              VALUES ('$ref_no', '$from_store_id', '$to_store_id', '$note', '$total_items', '$total_qty', '$created_by', '$status', '$attachment_path')";
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Failed to save transfer header: ' . mysqli_error($conn));
    }

    $transfer_id = mysqli_insert_id($conn);

    // Insert items and deduct stock
    foreach ($product_ids as $index => $p_id) {
        $qty = mysqli_real_escape_string($conn, $quantities[$index]);
        $p_name = mysqli_real_escape_string($conn, $product_names[$index]);
        $p_id = mysqli_real_escape_string($conn, $p_id);

        // Record Item
        $item_query = "INSERT INTO transfer_items (store_id, transfer_id, product_id, product_name, quantity) 
                       VALUES ('$from_store_id', '$transfer_id', '$p_id', '$p_name', '$qty')";
        if (!mysqli_query($conn, $item_query)) {
            throw new Exception('Failed to save transfer item: ' . mysqli_error($conn));
        }

        // Deduct stock from source store
        $check_stock = mysqli_query($conn, "SELECT stock FROM product_store_map WHERE product_id = '$p_id' AND store_id = '$from_store_id' FOR UPDATE");
        $stock_data = mysqli_fetch_assoc($check_stock);
        
        if (!$stock_data || $stock_data['stock'] < $qty) {
            throw new Exception("Insufficient stock for product: $p_name");
        }

        $update_stock = "UPDATE product_store_map SET stock = stock - $qty WHERE product_id = '$p_id' AND store_id = '$from_store_id'";
        if (!mysqli_query($conn, $update_stock)) {
            throw new Exception('Failed to update source stock: ' . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 200, 'message' => 'Stock transfer initiated successfully.']);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
}
?>

<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['status' => 401, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Fetch transfer basic info
    $query = "SELECT t.*, 
              fs.store_name as from_store, 
              ts.store_name as to_store 
              FROM transfers t
              JOIN stores fs ON t.from_store_id = fs.id
              JOIN stores ts ON t.to_store_id = ts.id
              WHERE t.id = '$id'";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $transfer = mysqli_fetch_assoc($result);
        
        // Format date
        $transfer['created_at'] = date('d M Y, h:i A', strtotime($transfer['created_at']));
        
        // Fetch items
        $items_query = "SELECT * FROM transfer_items WHERE transfer_id = '$id'";
        $items_result = mysqli_query($conn, $items_query);
        $items = [];
        while ($row = mysqli_fetch_assoc($items_result)) {
            $items[] = $row;
        }
        
        echo json_encode([
            'status' => 200,
            'transfer' => $transfer,
            'items' => $items
        ]);
    } else {
        echo json_encode(['status' => 404, 'message' => 'Transfer not found']);
    }
} else {
    echo json_encode(['status' => 400, 'message' => 'ID missing']);
}

<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (!empty($id)) {
    $query = "SELECT s.*, c.symbol_left, c.symbol_right 
              FROM stores s 
              LEFT JOIN currencies c ON s.currency_id = c.id 
              WHERE s.id = '$id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $currency_symbol = $row['symbol_left'] ?: ($row['symbol_right'] ?: '$');
        
        echo json_encode([
            'store_name' => $row['store_name'],
            'currency_symbol' => $currency_symbol
        ]);
    } else {
        echo json_encode(['error' => 'Store not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid ID']);
}
?>

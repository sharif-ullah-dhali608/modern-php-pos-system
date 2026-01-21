<?php
session_start();
require_once('../config/dbcon.php');

header('Content-Type: application/json');

if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit(0);
}

$invoice_id = mysqli_real_escape_string($conn, $_GET['invoice_id'] ?? '');

if(empty($invoice_id)) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID required']);
    exit(0);
}

// Fetch invoice items
$items_query = "SELECT item_name, qty_sold, price_sold, subtotal 
                FROM selling_item 
                WHERE invoice_id = '$invoice_id' AND invoice_type = 'sale'
                ORDER BY id ASC";

$items_result = mysqli_query($conn, $items_query);
$items = [];

while($item = mysqli_fetch_assoc($items_result)) {
    $items[] = [
        'name' => $item['item_name'],
        'qty' => floatval($item['qty_sold']),
        'price' => floatval($item['price_sold']),
        'total' => floatval($item['subtotal'])
    ];
}

echo json_encode([
    'success' => true,
    'items' => $items
]);

<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    echo json_encode(['count' => 0]);
    exit(0);
}

$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

$query = "SELECT COUNT(*) as alert_count 
          FROM products p
          JOIN product_store_map psm ON p.id = psm.product_id
          WHERE p.status = 1 AND psm.stock <= p.alert_quantity";

if($store_id > 0) {
    $query .= " AND psm.store_id = $store_id";
}

$result = mysqli_query($conn, $query);
$count = 0;
if($result) {
    $data = mysqli_fetch_assoc($result);
    $count = (int)$data['alert_count'];
}

echo json_encode(['count' => $count]);
?>

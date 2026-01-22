<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50; 

// Initial load usually requests empty search with limit 5 (as requested by user)
if(empty($search)) {
    // If specific initial limit requested, respect it, otherwise default to 50
    // But user specifically said "database theke 5 ta dekhabe" for initial view
    // So if 'page' is not set or search is empty, we might defaulting to 5 if called from our specific UI logic, 
    // but standard Select2 might fetch normally.
    // Let's just follow standard search logic: matches everything if empty.
    $query = "SELECT id, currency_name, code, symbol_left, symbol_right FROM currencies WHERE status = 1 ORDER BY sort_order ASC, currency_name ASC LIMIT $limit";
} else {
    $query = "SELECT id, currency_name, code, symbol_left, symbol_right FROM currencies 
              WHERE status = 1 
              AND (currency_name LIKE '%$search%' OR code LIKE '%$search%') 
              ORDER BY sort_order ASC, currency_name ASC LIMIT $limit";
}

$result = mysqli_query($conn, $query);
$currencies = [];

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $symbol = $row['symbol_left'] ? $row['symbol_left'] : $row['symbol_right'];
        $currencies[] = [
            'id' => $row['id'],
            'text' => $row['currency_name'] . ' (' . $row['code'] . ') ' . $symbol
        ];
    }
}

echo json_encode(['results' => $currencies]);
?>

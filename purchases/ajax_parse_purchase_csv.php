<?php
include('../config/dbcon.php');
session_start();

if(!isset($_SESSION['auth'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file'];
    $allowed = ['csv', 'txt']; // txt sometimes used for csv
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    if(!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file format. Only CSV allowed.']);
        exit;
    }

    // Detect Delimiter
    $delimiter = ',';
    $line = fgets(fopen($file['tmp_name'], 'r'));
    if(stripos($line, ';') !== false && stripos($line, ',') === false) {
        $delimiter = ';';
    }
    
    $handle = fopen($file['tmp_name'], 'r');
    
    // Remove BOM if present
    $bom = fread($handle, 3);
    if ($bom != "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $items = [];
    $missing_items = [];
    $row_count = 0;
    
    while(($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        $row_count++;
        
        // Skip Header Row
        if($row_count == 1) { continue; }
        
        // Skip empty rows
        if(empty($data[0])) { continue; }

        $code = trim($data[0]); // Product Code
        $qty = floatval($data[1] ?? 1); // Quantity
        $cost = isset($data[2]) ? floatval($data[2]) : null; // Cost
        $sell = isset($data[3]) ? floatval($data[3]) : 0; // Sell Price (Optional)
        $tax = isset($data[4]) ? floatval($data[4]) : 0; // Tax (Optional)

        // Find Product ID by Code OR Name
        $code_esc = mysqli_real_escape_string($conn, $code);
        $query = "SELECT id, purchase_price, selling_price, product_name FROM products WHERE product_code = '$code_esc' OR product_name = '$code_esc' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if(mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            
            // Use CSV cost if provided, else DB cost
            $final_cost = ($cost !== null && $cost > 0) ? $cost : $product['purchase_price'];
            $final_sell = ($sell > 0) ? $sell : $product['selling_price'];
            // Tax: if provided in CSV use it? Or calculate? Expected is Tax Amount.
            $final_tax = $tax;

            $items[] = [
                'item_id' => $product['id'],
                'product_name' => $product['product_name'], // Debug info
                'item_quantity' => $qty,
                'item_purchase_price' => $final_cost,
                'item_selling_price' => $final_sell,
                'item_tax' => $final_tax
            ];
        } else {
            $missing_items[] = $code;
        }
    }
    
    fclose($handle);
    
    echo json_encode([
        'status' => 'success',
        'count' => count($items),
        'items' => $items,
        'missing' => $missing_items
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
?>
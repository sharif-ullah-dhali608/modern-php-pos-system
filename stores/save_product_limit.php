<?php
include('../config/dbcon.php');

if(isset($_POST['product_id']) && isset($_POST['limit'])) {
    $id = intval($_POST['product_id']);
    $limit = intval($_POST['limit']);
    $store_id = $_POST['store_id'] ?? 'global';
    
    if($store_id === 'global') {
        $query = "UPDATE products SET per_customer_limit = '$limit' WHERE id = '$id'";
    } else {
        $store_id = intval($store_id);
        // Check if map exists, if not insert (logic depends on system, assuming map exists for active products)
        // Using ON DUPLICATE KEY UPDATE might be safer if we assume unique(product_id, store_id)
        // But for now, let's try UPDATE first, if affected rows 0, maybe Insert? 
        // Actually, simple UPDATE is safer for existing maps. 
        $query = "UPDATE product_store_map SET per_customer_limit = '$limit' WHERE product_id = '$id' AND store_id = '$store_id'";
        
        // If we want to ensure it works even if map doesn't exist (unlikely for displayed product), we would check.
        // But Search only returns products.
    }

    if(mysqli_query($conn, $query)) {
        echo "success";
    } else {
        echo "error";
    }
}
?>

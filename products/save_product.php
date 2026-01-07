<?php
session_start();
include('../config/dbcon.php');

// --- DEBUGGING ON ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// --- HELPER FUNCTION FOR IMAGE UPLOAD ---
function uploadImage($file) {
    $targetDir = "../uploads/products/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    // Using uniqid() to prevent overwriting
    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            // Return Web Path (Adjust '/pos' if your folder name is different)
            return "/pos/uploads/products/" . $fileName;
        }
    }
    return null;
}

// --- HELPER TO GET SERVER PATH ---
function getServerPath($webPath) {
    // Converts "/pos/uploads/..." to "../uploads/..."
    return ".." . str_replace("/pos", "", $webPath);
}

// ==========================================
// 1. AJAX DELETE GALLERY IMAGE
// ==========================================
if(isset($_POST['delete_gallery_image'])) {
    $image_id = mysqli_real_escape_string($conn, $_POST['image_id']);
    
    $query = "SELECT image_path FROM product_images WHERE id='$image_id' LIMIT 1";
    $query_run = mysqli_query($conn, $query);

    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_assoc($query_run);
        $server_path = getServerPath($row['image_path']);
        
        if(file_exists($server_path)) {
            unlink($server_path);
        }

        if(mysqli_query($conn, "DELETE FROM product_images WHERE id='$image_id'")) {
            echo "success";
        } else {
            echo "db_error";
        }
    } else {
        echo "not_found";
    }
    exit(0);
}

// ==========================================
// 2. CREATE PRODUCT LOGIC
// ==========================================
if(isset($_POST['save_product_btn'])) {
    
    $product_name       = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code       = mysqli_real_escape_string($conn, $_POST['product_code']);
    $purchase_price     = mysqli_real_escape_string($conn, $_POST['purchase_price']);
    $selling_price      = mysqli_real_escape_string($conn, $_POST['selling_price']);
    $description        = mysqli_real_escape_string($conn, $_POST['description']);
    $opening_stock      = mysqli_real_escape_string($conn, $_POST['opening_stock']);
    $barcode_symbology  = mysqli_real_escape_string($conn, $_POST['barcode_symbology']);
    $tax_method         = mysqli_real_escape_string($conn, $_POST['tax_method']);
    $wholesale_price    = mysqli_real_escape_string($conn, $_POST['wholesale_price']);
    $profit_margin      = mysqli_real_escape_string($conn, $_POST['profit_margin']);
    $sort_order         = (int)$_POST['sort_order'];
    $category_id        = (int)$_POST['category_id'];
    $unit_id            = (int)$_POST['unit_id'];
    $alert_quantity     = (int)$_POST['alert_quantity'];
    $status             = isset($_POST['status']) ? (int)$_POST['status'] : 0; 

    // DUPLICATE CHECK
    $check_duplicate = mysqli_query($conn, "SELECT id FROM products WHERE product_name='$product_name' OR product_code='$product_code' LIMIT 1");
    if(mysqli_num_rows($check_duplicate) > 0) {
        $_SESSION['message'] = "Error: Product Name or Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/add");
        exit(0);
    }

    $brand_id = ((int)$_POST['brand_id'] > 0) ? "'".(int)$_POST['brand_id']."'" : "NULL";
    $tax_rate_id = ((int)$_POST['tax_rate_id'] > 0) ? "'".(int)$_POST['tax_rate_id']."'" : "NULL";
    $box_id = ((int)$_POST['box_id'] > 0) ? "'".(int)$_POST['box_id']."'" : "NULL";
    $currency_id = ((int)$_POST['currency_id'] > 0) ? "'".(int)$_POST['currency_id']."'" : "NULL";
    $expire_date_input = $_POST['expire_date'];
    $expire_date = !empty($expire_date_input) ? "'$expire_date_input'" : "NULL";
    $store_ids = $_POST['stores'] ?? $_POST['store_ids'] ?? [];

    // INSERT
    $query = "INSERT INTO products (
                product_name, product_code, barcode_symbology, category_id, brand_id, unit_id,
                purchase_price, selling_price, wholesale_price, profit_margin, tax_rate_id, tax_method, 
                alert_quantity, description, status, thumbnail, opening_stock, expire_date, 
                box_id, currency_id, sort_order
              ) VALUES (
                '$product_name', '$product_code', '$barcode_symbology', '$category_id', $brand_id, '$unit_id',
                '$purchase_price', '$selling_price', '$wholesale_price', '$profit_margin', $tax_rate_id, '$tax_method', 
                '$alert_quantity', '$description', '$status', '', '$opening_stock', $expire_date, 
                $box_id, $currency_id, '$sort_order'
              )";
    
    if(mysqli_query($conn, $query)) {
        $product_id = mysqli_insert_id($conn); 
        
        $main_thumbnail = null; 
        if(isset($_FILES['thumbnails']['name']) && count($_FILES['thumbnails']['name']) > 0) {
            $countfiles = count($_FILES['thumbnails']['name']);
            for($i=0; $i<$countfiles; $i++){
                if($_FILES['thumbnails']['name'][$i] != ""){
                    $fileData = [
                        'name' => $_FILES['thumbnails']['name'][$i],
                        'type' => $_FILES['thumbnails']['type'][$i],
                        'tmp_name' => $_FILES['thumbnails']['tmp_name'][$i],
                        'error' => $_FILES['thumbnails']['error'][$i],
                        'size' => $_FILES['thumbnails']['size'][$i]
                    ];
                    $uploadedPath = uploadImage($fileData);
                    if($uploadedPath){
                        if($main_thumbnail === null){
                            $main_thumbnail = $uploadedPath;
                        } else {
                            mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$uploadedPath')");
                        }
                    }
                }
            }
        }
        if($main_thumbnail){
            mysqli_query($conn, "UPDATE products SET thumbnail='$main_thumbnail' WHERE id='$product_id'");
        }

        if(!empty($store_ids)){
            foreach($store_ids as $store_id) {
                $store_id = (int)$store_id;
                mysqli_query($conn, "INSERT INTO product_store_map (store_id, product_id) VALUES ('$store_id', '$product_id')");
            }
        }
        $_SESSION['message'] = "Product created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/products/list");
    } else {
        echo mysqli_error($conn); exit(0);
    }
    exit(0);
}

// ==========================================
// 3. UPDATE PRODUCT LOGIC (FIXED AUTO-SELECT)
// ==========================================
if(isset($_POST['update_product_btn'])) {
    $product_id = (int)$_POST['product_id'];

    $product_name       = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code       = mysqli_real_escape_string($conn, $_POST['product_code']);
    $purchase_price     = mysqli_real_escape_string($conn, $_POST['purchase_price']);
    $selling_price      = mysqli_real_escape_string($conn, $_POST['selling_price']);
    $description        = mysqli_real_escape_string($conn, $_POST['description']);
    $opening_stock      = mysqli_real_escape_string($conn, $_POST['opening_stock']);
    $barcode_symbology  = mysqli_real_escape_string($conn, $_POST['barcode_symbology']);
    $tax_method         = mysqli_real_escape_string($conn, $_POST['tax_method']);
    $wholesale_price    = mysqli_real_escape_string($conn, $_POST['wholesale_price']);
    $profit_margin      = mysqli_real_escape_string($conn, $_POST['profit_margin']);
    $sort_order         = (int)$_POST['sort_order'];
    $category_id        = (int)$_POST['category_id'];
    $unit_id            = (int)$_POST['unit_id'];
    $alert_quantity     = (int)$_POST['alert_quantity'];
    $status             = isset($_POST['status']) ? (int)$_POST['status'] : 0; 
    
    // Check duplicates
    $check_update_dup = mysqli_query($conn, "SELECT id FROM products WHERE (product_name='$product_name' OR product_code='$product_code') AND id != '$product_id' LIMIT 1");
    if(mysqli_num_rows($check_update_dup) > 0) {
        $_SESSION['message'] = "Error: Product Name or Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/edit/$product_id");
        exit(0);
    }

    // Optional Fields
    $brand_id = ((int)$_POST['brand_id'] > 0) ? "'".(int)$_POST['brand_id']."'" : "NULL";
    $tax_rate_id = ((int)$_POST['tax_rate_id'] > 0) ? "'".(int)$_POST['tax_rate_id']."'" : "NULL";
    $box_id = ((int)$_POST['box_id'] > 0) ? "'".(int)$_POST['box_id']."'" : "NULL";
    $currency_id = ((int)$_POST['currency_id'] > 0) ? "'".(int)$_POST['currency_id']."'" : "NULL";
    $expire_date_input = $_POST['expire_date'];
    $expire_date = !empty($expire_date_input) ? "'$expire_date_input'" : "NULL";
    $store_ids = $_POST['stores'] ?? $_POST['store_ids'] ?? [];

    // --- A. IMAGE LOGIC (MAIN & AUTO PROMOTE) ---
    $old_thumbnail = $_POST['old_thumbnail'] ?? '';
    $final_thumbnail_path = $old_thumbnail;

    // 1. Check if user physically removed the main image
    if(isset($_POST['remove_main_image']) && $_POST['remove_main_image'] == '1'){
        $server_path = getServerPath($old_thumbnail);
        if(file_exists($server_path) && !empty($old_thumbnail)) {
            unlink($server_path);
        }
        $final_thumbnail_path = ""; // Set to empty to trigger promotion
    }

    // 2. AUTO PROMOTE: If main image is empty, find one from gallery
    if(empty($final_thumbnail_path)) {
        // Get ALL gallery images for this product, ordered by ID (Oldest first)
        $gallery_q = mysqli_query($conn, "SELECT id, image_path FROM product_images WHERE product_id='$product_id' ORDER BY id ASC");
        
        while($g_img = mysqli_fetch_assoc($gallery_q)) {
            $check_path = getServerPath($g_img['image_path']);
            
            if(file_exists($check_path)) {
                // Found a valid image! Promote it.
                $final_thumbnail_path = $g_img['image_path'];
                
                // Remove this specific image from gallery table
                $gid = $g_img['id'];
                mysqli_query($conn, "DELETE FROM product_images WHERE id='$gid'");
                
                // Stop loop, we found our main image
                break; 
            } else {
                // Image record exists but file is missing? Delete broken record.
                $gid = $g_img['id'];
                mysqli_query($conn, "DELETE FROM product_images WHERE id='$gid'");
            }
        }
    }

    // --- B. HANDLE NEW UPLOADS ---
    // If user uploaded new files, process them
    if(isset($_FILES['thumbnails']['name']) && count($_FILES['thumbnails']['name']) > 0) {
        $countfiles = count($_FILES['thumbnails']['name']);
        for($i=0; $i<$countfiles; $i++){
            if($_FILES['thumbnails']['name'][$i] != ""){
                $fileData = [
                    'name' => $_FILES['thumbnails']['name'][$i],
                    'type' => $_FILES['thumbnails']['type'][$i],
                    'tmp_name' => $_FILES['thumbnails']['tmp_name'][$i],
                    'error' => $_FILES['thumbnails']['error'][$i],
                    'size' => $_FILES['thumbnails']['size'][$i]
                ];
                $uploadedPath = uploadImage($fileData);
                if($uploadedPath){
                    // If we STILL don't have a main image (gallery was empty), use this one
                    if(empty($final_thumbnail_path)) {
                        $final_thumbnail_path = $uploadedPath;
                    } else {
                        // Else add to gallery
                        mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$uploadedPath')");
                    }
                }
            }
        }
    }

    // --- C. UPDATE DB ---
    $query = "UPDATE products SET 
                product_name='$product_name', 
                product_code='$product_code', 
                barcode_symbology='$barcode_symbology', 
                category_id='$category_id', 
                brand_id=$brand_id, 
                unit_id='$unit_id', 
                purchase_price='$purchase_price', 
                selling_price='$selling_price', 
                wholesale_price='$wholesale_price',
                profit_margin='$profit_margin',
                tax_rate_id=$tax_rate_id, 
                tax_method='$tax_method', 
                alert_quantity='$alert_quantity', 
                description='$description', 
                opening_stock='$opening_stock',
                expire_date=$expire_date, 
                box_id=$box_id,
                currency_id=$currency_id,
                sort_order='$sort_order',
                status='$status',
                thumbnail='$final_thumbnail_path' 
              WHERE id='$product_id'";
    
    if(mysqli_query($conn, $query)) {
        // Store Mapping
        mysqli_query($conn, "DELETE FROM product_store_map WHERE product_id='$product_id'");
        if(!empty($store_ids)){
            foreach($store_ids as $store_id) {
                $store_id = (int)$store_id;
                mysqli_query($conn, "INSERT INTO product_store_map (store_id, product_id) VALUES ('$store_id', '$product_id')");
            }
        }

        $_SESSION['message'] = "Product updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/products/list");
    } else {
        echo mysqli_error($conn); exit(0);
    }
    exit(0);
}

// ==========================================
// 4. DELETE / CLEANUP LOGIC
// ==========================================
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    
    $img_q = mysqli_query($conn, "SELECT thumbnail FROM products WHERE id='$id'");
    if($row = mysqli_fetch_assoc($img_q)){
        $file_path = getServerPath($row['thumbnail']);
        if(file_exists($file_path) && !empty($row['thumbnail'])) unlink($file_path);
    }

    $gal_q = mysqli_query($conn, "SELECT image_path FROM product_images WHERE product_id='$id'");
    while($g_row = mysqli_fetch_assoc($gal_q)){
        $g_path = getServerPath($g_row['image_path']);
        if(file_exists($g_path)) unlink($g_path);
    }

    mysqli_query($conn, "DELETE FROM product_store_map WHERE product_id='$id'");
    
    if(mysqli_query($conn, "DELETE FROM products WHERE id='$id'")) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: /pos/products/list");
    exit(0);
}

// TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    if(mysqli_query($conn, "UPDATE products SET status='$status' WHERE id='$id'")){
        $_SESSION['message'] = "Status updated!";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: /pos/products/list");
    exit(0);
}

header("Location: /pos/products/list");
exit(0);
?>
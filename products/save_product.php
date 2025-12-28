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
    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return "/pos/uploads/products/" . $fileName;
        }
    }
    return null;
}

// --- CREATE PRODUCT ---
if(isset($_POST['save_product_btn'])) {
    
    // 1. Mandatory Fields (String)
    $product_name       = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code       = mysqli_real_escape_string($conn, $_POST['product_code']);
    $purchase_price     = mysqli_real_escape_string($conn, $_POST['purchase_price']);
    $selling_price      = mysqli_real_escape_string($conn, $_POST['selling_price']);
    $description        = mysqli_real_escape_string($conn, $_POST['description']);
    $opening_stock      = mysqli_real_escape_string($conn, $_POST['opening_stock']);
    $barcode_symbology  = mysqli_real_escape_string($conn, $_POST['barcode_symbology']);
    $tax_method         = mysqli_real_escape_string($conn, $_POST['tax_method']);
    
    // 2. New Integrated Fields
    $wholesale_price    = mysqli_real_escape_string($conn, $_POST['wholesale_price']);
    $profit_margin      = mysqli_real_escape_string($conn, $_POST['profit_margin']);
    $sort_order         = (int)$_POST['sort_order'];

    // 3. Numeric Fields (Mandatory)
    $category_id        = (int)$_POST['category_id'];
    $unit_id            = (int)$_POST['unit_id'];
    $alert_quantity     = (int)$_POST['alert_quantity'];
    $status             = isset($_POST['status']) ? 1 : 0; 

    // --- 4. NULLABLE FIELDS HANDLING (FIX FOR FOREIGN KEY ERRORS) ---
    
    // Brand Logic
    $brand_input = (int)$_POST['brand_id'];
    $brand_id = ($brand_input > 0) ? "'$brand_input'" : "NULL";

    // Tax Rate Logic
    $tax_method = mysqli_real_escape_string($conn, $_POST['tax_method']);
    $tax_input = (int)$_POST['tax_rate_id'];
    $tax_rate_id = ($tax_input > 0) ? "'$tax_input'" : "NULL";

    // Supplier Logic
    $supplier_input = (int)$_POST['supplier_id'];
    $supplier_id = ($supplier_input > 0) ? "'$supplier_input'" : "NULL";

    // Box Logic
    $box_input = (int)$_POST['box_id'];
    $box_id = ($box_input > 0) ? "'$box_input'" : "NULL";

    // Currency Logic
    $currency_input = (int)$_POST['currency_id'];
    $currency_id = ($currency_input > 0) ? "'$currency_input'" : "NULL";

    // Expire Date Logic
    $expire_date_input = $_POST['expire_date'];
    $expire_date = !empty($expire_date_input) ? "'$expire_date_input'" : "NULL";

    $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : [];

    // Validation
    if(empty($product_name) || empty($product_code) || empty($selling_price) || $category_id == 0 || $unit_id == 0) {
        $_SESSION['message'] = "Name, Code, Category, Unit and Price are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/add");
        exit(0);
    }

    // Duplicate Check
    $check = mysqli_query($conn, "SELECT id FROM products WHERE product_code='$product_code'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Product Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/add");
        exit(0);
    }

    // Image Upload
    $thumbnail_path = "";
    if(isset($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['name'] != "") {
        $uploadedParams = uploadImage($_FILES['thumbnail']);
        if($uploadedParams) {
            $thumbnail_path = $uploadedParams;
        }
    }

    // Insert Query including new fields
    $query = "INSERT INTO products (
                product_name, product_code, barcode_symbology, category_id, brand_id, unit_id,
                purchase_price, selling_price, wholesale_price, profit_margin, tax_rate_id, tax_method, 
                alert_quantity, description, status, thumbnail, opening_stock, expire_date, 
                supplier_id, box_id, currency_id, sort_order
              ) VALUES (
                '$product_name', '$product_code', '$barcode_symbology', '$category_id', $brand_id, '$unit_id',
                '$purchase_price', '$selling_price', '$wholesale_price', '$profit_margin', $tax_rate_id, '$tax_method', 
                '$alert_quantity', '$description', '$status', '$thumbnail_path', '$opening_stock', $expire_date, 
                $supplier_id, $box_id, $currency_id, '$sort_order'
              )";
    
    if(mysqli_query($conn, $query)) {
        $product_id = mysqli_insert_id($conn); 
        
        // Store Mapping
        if(!empty($store_ids)){
            foreach($store_ids as $store_id) {
                $store_id = (int)$store_id;
                $store_query = "INSERT INTO product_store_map (store_id, product_id) VALUES ('$store_id', '$product_id')";
                mysqli_query($conn, $store_query);
            }
        }
        
        $_SESSION['message'] = "Product created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/products/list");
    } else {
        echo "<h1>SQL Insert Error!</h1>";
        echo "<p>" . mysqli_error($conn) . "</p>";
        echo "<pre>" . $query . "</pre>";
        exit(0);
    }
    exit(0);
}

// --- UPDATE PRODUCT ---
if(isset($_POST['update_product_btn'])) {
    
    $product_id = (int)$_POST['product_id'];

    // Mandatory Fields
    $product_name       = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code       = mysqli_real_escape_string($conn, $_POST['product_code']);
    $purchase_price     = mysqli_real_escape_string($conn, $_POST['purchase_price']);
    $selling_price      = mysqli_real_escape_string($conn, $_POST['selling_price']);
    $description        = mysqli_real_escape_string($conn, $_POST['description']);
    $opening_stock      = mysqli_real_escape_string($conn, $_POST['opening_stock']);
    $barcode_symbology  = mysqli_real_escape_string($conn, $_POST['barcode_symbology']);
    $tax_method         = mysqli_real_escape_string($conn, $_POST['tax_method']);
    
    // Updated Fields
    $wholesale_price    = mysqli_real_escape_string($conn, $_POST['wholesale_price']);
    $profit_margin      = mysqli_real_escape_string($conn, $_POST['profit_margin']);
    $sort_order         = (int)$_POST['sort_order'];

    $category_id        = (int)$_POST['category_id'];
    $unit_id            = (int)$_POST['unit_id'];
    $alert_quantity     = (int)$_POST['alert_quantity'];

if(isset($_POST['status'])) {
        $status = (int)$_POST['status']; 
    } else {
        $status = 0; // Default to inactive if not set
    }

    // --- NULLABLE FIELDS HANDLING ---
    $brand_input = (int)$_POST['brand_id'];
    $brand_id = ($brand_input > 0) ? "'$brand_input'" : "NULL";

    $tax_input = (int)$_POST['tax_rate_id'];
    $tax_rate_id = ($tax_input > 0) ? "'$tax_input'" : "NULL";

    $supplier_input = (int)$_POST['supplier_id'];
    $supplier_id = ($supplier_input > 0) ? "'$supplier_input'" : "NULL";

    $box_input = (int)$_POST['box_id'];
    $box_id = ($box_input > 0) ? "'$box_input'" : "NULL";

    $currency_input = (int)$_POST['currency_id'];
    $currency_id = ($currency_input > 0) ? "'$currency_input'" : "NULL";

    $expire_date_input = $_POST['expire_date'];
    $expire_date = !empty($expire_date_input) ? "'$expire_date_input'" : "NULL";
    
    $old_thumbnail      = $_POST['old_thumbnail'];
    $store_ids          = isset($_POST['store_ids']) ? $_POST['store_ids'] : [];

    // Duplicate Code Check
    $check = mysqli_query($conn, "SELECT id FROM products WHERE product_code='$product_code' AND id != '$product_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Product Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/products/edit/$product_id");
        exit(0);
    }

    // Image Logic
    $thumbnail_path = $old_thumbnail;
    if(isset($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['name'] != "") {
        $uploadedParams = uploadImage($_FILES['thumbnail']);
        if($uploadedParams) {
            $thumbnail_path = $uploadedParams;
            $old_file_path = ".." . str_replace("/pos", "", $old_thumbnail);
            if(file_exists($old_file_path) && !empty($old_thumbnail)){
                unlink($old_file_path);
            }
        }
    }

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
                supplier_id=$supplier_id,
                box_id=$box_id,
                currency_id=$currency_id,
                sort_order='$sort_order',
                status='$status', 
                thumbnail='$thumbnail_path'
              WHERE id='$product_id'";
    
    if(mysqli_query($conn, $query)) {
        
        // Update Store Mappings
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
        echo "<h1>SQL Update Error!</h1>";
        echo "<p>" . mysqli_error($conn) . "</p>";
        echo "<pre>" . $query . "</pre>";
        exit(0);
    }
    exit(0);
}

// --- DELETE PRODUCT ---
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];

    $img_q = mysqli_query($conn, "SELECT thumbnail FROM products WHERE id='$id'");
    if($row = mysqli_fetch_assoc($img_q)){
        $file_path = ".." . str_replace("/pos", "", $row['thumbnail']);
        if(file_exists($file_path) && !empty($row['thumbnail'])){
            unlink($file_path);
        }
    }

    mysqli_query($conn, "DELETE FROM product_store_map WHERE product_id='$id'");
    
    if(mysqli_query($conn, "DELETE FROM products WHERE id='$id'")) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/products/list");
    exit(0);
}

// --- TOGGLE STATUS ---
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    
    if(mysqli_query($conn, "UPDATE products SET status='$status' WHERE id='$id'")){
        $_SESSION['message'] = "Status updated!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update status.";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/products/list");
    exit(0);
}

header("Location: /pos/products/list");
exit(0);
?>
<?php
session_start();
include('../config/dbcon.php');

// Response Header
header('Content-Type: application/json');

// Security Check
if(!isset($_SESSION['auth'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit(0);
}

// --- HELPER FUNCTION FOR IMAGE UPLOAD ---
function uploadImageInternal($file) {
    // Relative path from this file (ajax_add_product.php is in /pos/pos/)
    // Target is /pos/uploads/products/
    // So we need to go up to root "../" then "uploads/products/"
    // wait, pos/pos/ is 2 deep from root assuming root is "pos" folder?
    // File structure:
    // /Applications/MAMP/htdocs/pos/pos/ajax_add_product.php
    // /Applications/MAMP/htdocs/pos/uploads/products/
    // So path is ../uploads/products/
    
    $targetDir = "../uploads/products/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            // Return Web Path stored in DB (e.g. /pos/uploads/products/...)
            // If main application is in /pos subfolder of htdocs
            return "/pos/uploads/products/" . $fileName;
        }
    }
    return null;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    try {
        $product_name       = mysqli_real_escape_string($conn, $_POST['product_name']);
        $product_code       = mysqli_real_escape_string($conn, $_POST['product_code']);
        $purchase_price     = mysqli_real_escape_string($conn, $_POST['purchase_price']);
        $selling_price      = mysqli_real_escape_string($conn, $_POST['selling_price']);
        $description        = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
        $opening_stock      = mysqli_real_escape_string($conn, $_POST['opening_stock'] ?? '0');
        $barcode_symbology  = mysqli_real_escape_string($conn, $_POST['barcode_symbology'] ?? 'code128');
        $tax_method         = mysqli_real_escape_string($conn, $_POST['tax_method'] ?? 'exclusive');
        $wholesale_price    = mysqli_real_escape_string($conn, $_POST['wholesale_price'] ?? '0');
        $profit_margin      = mysqli_real_escape_string($conn, $_POST['profit_margin'] ?? '0');
        $sort_order         = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $category_id        = (int)$_POST['category_id'];
        $unit_id            = (int)$_POST['unit_id'];
        $alert_quantity     = (int)($_POST['alert_quantity'] ?? 5);
        $status             = 1; 

        // DUPLICATE CHECK
        $check_duplicate = mysqli_query($conn, "SELECT id FROM products WHERE product_name='$product_name' OR product_code='$product_code' LIMIT 1");
        if(mysqli_num_rows($check_duplicate) > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Product Name or Code already exists!']);
            exit(0);
        }

        $brand_id = ((int)($_POST['brand_id'] ?? 0) > 0) ? "'".(int)$_POST['brand_id']."'" : "NULL";
        $tax_rate_id = ((int)($_POST['tax_rate_id'] ?? 0) > 0) ? "'".(int)$_POST['tax_rate_id']."'" : "NULL";
        $box_id = ((int)($_POST['box_id'] ?? 0) > 0) ? "'".(int)$_POST['box_id']."'" : "NULL";
        $currency_id = ((int)($_POST['currency_id'] ?? 0) > 0) ? "'".(int)$_POST['currency_id']."'" : "NULL";
        $expire_date_input = $_POST['expire_date'] ?? '';
        $expire_date = !empty($expire_date_input) ? "'$expire_date_input'" : "NULL";
        
        // For Quick Add, we assume current store (or passed store_id)
        // Or default to all stores?
        // Usually Quick Add implies availability in the current POS interaction context.
        // Let's check if store_id is passed, if not, use the session or valid stores logic.
        // Add Product usually adds to mapping.
        // We will default to mapping to ALL active stores if no specific logic, 
        // OR better yet, map to the store currently selected in POS if available.
        // But the safest bet is mapping to the store from which it is created?
        // Let's assume we map to the store selected in POS dropdown + maybe others.
        // For simplicity: Map to selected store ID from POS request.
        
        $current_store_id = isset($_POST['current_store_id']) ? (int)$_POST['current_store_id'] : 0; 

        // INSERT QUERY
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
            
            // Image Upload
            $main_thumbnail = null;
            if(isset($_FILES['product_image']['name']) && $_FILES['product_image']['name'] != ""){
                // Single image upload for Quick Add
                $fileData = [
                    'name' => $_FILES['product_image']['name'],
                    'type' => $_FILES['product_image']['type'],
                    'tmp_name' => $_FILES['product_image']['tmp_name'],
                    'error' => $_FILES['product_image']['error'],
                    'size' => $_FILES['product_image']['size']
                ];
                $uploadedPath = uploadImageInternal($fileData);
                if($uploadedPath){
                    $main_thumbnail = $uploadedPath;
                    mysqli_query($conn, "UPDATE products SET thumbnail='$main_thumbnail' WHERE id='$product_id'");
                    // Also add to product_images
                    mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$uploadedPath')");
                }
            }

            // Map Store
            if($current_store_id > 0) {
                mysqli_query($conn, "INSERT INTO product_store_map (store_id, product_id, stock) VALUES ('$current_store_id', '$product_id', '$opening_stock')");
            } else {
                // Default: Map to all stores (only first store gets the opening stock to avoid duplication)
                $stores_q = mysqli_query($conn, "SELECT id FROM stores WHERE status=1");
                $first = true;
                while($s = mysqli_fetch_assoc($stores_q)){
                    $sid = $s['id'];
                    $initial_stock = $first ? $opening_stock : 0;
                    mysqli_query($conn, "INSERT INTO product_store_map (store_id, product_id, stock) VALUES ('$sid', '$product_id', '$initial_stock')");
                    $first = false;
                }
            }

            // --- AUTO-GENERATE PURCHASE ENTRY IF OPENING STOCK > 0 ---
            if((float)$opening_stock > 0) {
                $store_for_purchase = ($current_store_id > 0) ? $current_store_id : 1; 
                // If current_store_id is 0, we use 1 as fallback for purchase record
                if($current_store_id == 0) {
                    $st_q = mysqli_query($conn, "SELECT id FROM stores WHERE status=1 LIMIT 1");
                    if($st_data = mysqli_fetch_assoc($st_q)) $store_for_purchase = $st_data['id'];
                }

                $total_amount = (float)$purchase_price * (float)$opening_stock;
                $user_id = $_SESSION['auth_user']['user_id'] ?? $_SESSION['auth_user']['id'] ?? 1;
                $invoice_id = 'OP-' . strtoupper(substr(md5(time() . $product_id), 0, 7));
                $purchase_date = date('Y-m-d');
                
                // Get a supplier (Default to 1 or any available)
                $sup_q = mysqli_query($conn, "SELECT id FROM suppliers LIMIT 1");
                $sup_data = mysqli_fetch_assoc($sup_q);
                $supplier_id = $sup_data['id'] ?? 1;

                // 1. Insert into purchase_info
                $insert_info = "INSERT INTO purchase_info (invoice_id, store_id, sup_id, total_item, total_sell, purchase_note, payment_status, created_by, purchase_date) 
                                VALUES ('$invoice_id', '$store_for_purchase', '$supplier_id', '$opening_stock', '$total_amount', 'Opening Stock Entry (POS Quick Add)', 'paid', '$user_id', '$purchase_date')";
                mysqli_query($conn, $insert_info);

                // 2. Insert into purchase_item
                $brand_id_val = ($brand_id == "NULL") ? "NULL" : $brand_id;
                mysqli_query($conn, "INSERT INTO purchase_item (
                    invoice_id, store_id, item_id, category_id, brand_id, item_name, 
                    item_purchase_price, item_selling_price, item_quantity, item_total
                ) VALUES (
                    '$invoice_id', '$store_for_purchase', '$product_id', '$category_id', 
                    $brand_id_val, '".mysqli_real_escape_string($conn, $product_name)."', 
                    '$purchase_price', '$selling_price', '$opening_stock', '$total_amount'
                )");

                // 3. Insert into purchase_logs (Marked as Paid)
                $pay_ref = 'PAY-' . strtoupper(substr(md5(microtime()), 0, 6));
                $pm_q = mysqli_query($conn, "SELECT id FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC LIMIT 1");
                $pm_data = mysqli_fetch_assoc($pm_q);
                $pm_id = $pm_data['id'] ?? 1;

                mysqli_query($conn, "INSERT INTO purchase_logs (sup_id, reference_no, ref_invoice_id, type, pmethod_id, description, amount, store_id, created_by) 
                                     VALUES ('$supplier_id', '$pay_ref', '$invoice_id', 'purchase', '$pm_id', 'Initial stock payment', '$total_amount', '$store_for_purchase', '$user_id')");
            }

            echo json_encode(['status' => 'success', 'message' => 'Product added successfully!', 'product_id' => $product_id, 'product_name' => $product_name]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . mysqli_error($conn)]);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
    exit(0);
}
?>

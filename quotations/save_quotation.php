<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// ---------------------------------------------------------
// 1. VIEW QUOTATION (AJAX) - Logic to fetch Data & Images
// ---------------------------------------------------------
if(isset($_POST['view_quotation_btn']))
{
    $quotation_id = mysqli_real_escape_string($conn, $_POST['quotation_id']);

    // A. Fetch Quotation & Supplier Details (Address, Trade Lic etc.)
    $query = "SELECT q.*, 
              s.name as supplier_name, 
              s.mobile as s_mobile, 
              s.email as s_email, 
              s.address as s_address, 
              s.city as s_city, 
              s.country as s_country, 
              s.trade_license_num, 
              s.bank_account_num
              FROM quotations q
              LEFT JOIN suppliers s ON q.supplier_id = s.id
              WHERE q.id='$quotation_id'";

    $query_run = mysqli_query($conn, $query);

    if($query_run && mysqli_num_rows($query_run) > 0)
    {
        $quotation_data = mysqli_fetch_assoc($query_run);

        // B. Fetch Items with Product Image (Thumbnail)
        // Note: We are joining 'products' table to get 'thumbnail'\
// B. Fetch Items with Product Image (Thumbnail)
        $items_query = "SELECT qi.*, p.product_name, p.product_code, p.thumbnail 
                        FROM quotation_items qi
                        LEFT JOIN products p ON qi.product_id = p.id
                        WHERE qi.quotation_id = '$quotation_id'";

        $items_query_run = mysqli_query($conn, $items_query);
        $items = [];

        if($items_query_run)
        {
            while($item = mysqli_fetch_assoc($items_query_run))
            {
                // Path Fix: Database e path jodi '/pos/uploads/products/...' thake 
                // tobe setai direct use kora valo.
                
                if(!empty($item['thumbnail'])) {
                    // Jodi database e full path (/pos/uploads/...) thake:
                    $item['image_path'] = $item['thumbnail']; 
                } else {
                    // Default image jodi image na thake
                    $item['image_path'] = '/pos/assets/images/no-image.png'; 
                }

                $items[] = $item;
            }
        }

        // Send Data back to Javascript
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 200,
            'data' => $quotation_data,
            'items' => $items
        ]);
    }
    else
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => 404, 'message' => 'Quotation not found']);
    }
    exit; // Stop execution here for AJAX calls
}

// ---------------------------------------------------------
// 2. CREATE QUOTATION (Save)
// ---------------------------------------------------------
if(isset($_POST['save_quotation_btn'])) {
    
    $ref_no         = mysqli_real_escape_string($conn, $_POST['ref_no']);
    $customer_id    = (int)$_POST['customer_id'];
    $supplier_id    = (int)$_POST['supplier_id'];
    $date           = mysqli_real_escape_string($conn, $_POST['date']);
    $terms          = mysqli_real_escape_string($conn, $_POST['terms']);
    
    $discount       = (float)$_POST['discount'];
    $order_tax_rate = (float)$_POST['order_tax'];
    $shipping_cost  = (float)$_POST['shipping'];
    $others_charge  = (float)$_POST['others_charge'];
    $status         = isset($_POST['status']) ? (int)$_POST['status'] : 1; 

    // Arrays from form
    $product_ids    = $_POST['product_id']; 
    $prices         = $_POST['price'];      
    $qtys           = $_POST['qty'];        
    $item_taxes     = $_POST['item_tax'];   
    $tax_methods    = $_POST['tax_method']; 

    // Check Duplicate Reference
    $check_query = "SELECT id FROM quotations WHERE ref_no='$ref_no'";
    $check_run = mysqli_query($conn, $check_query);
    if(mysqli_num_rows($check_run) > 0) {
        $_SESSION['message'] = "Reference No '$ref_no' already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_quotation.php");
        exit(0);
    }

    mysqli_begin_transaction($conn);

    try {
        // Insert Main Quotation
        $query = "INSERT INTO quotations (ref_no, customer_id, supplier_id, date, discount, order_tax_rate, shipping_cost, others_charge, terms, status) 
                  VALUES ('$ref_no', '$customer_id', '$supplier_id', '$date', '$discount', '$order_tax_rate', '$shipping_cost', '$others_charge', '$terms', '$status')";
        
        if(!mysqli_query($conn, $query)) throw new Exception(mysqli_error($conn));
        $quotation_id = mysqli_insert_id($conn);

        $grand_subtotal = 0;

        // Loop through items
        foreach($product_ids as $key => $p_id) {
            if(!empty($p_id)) {
                $p_id = (int)$p_id;
                $price = (float)$prices[$key];
                $qty = (float)$qtys[$key];
                $tax_id = (int)$item_taxes[$key];
                $method = mysqli_real_escape_string($conn, $tax_methods[$key]);

                // Fetch tax rate for this item
                $tax_res = mysqli_query($conn, "SELECT taxrate FROM taxrates WHERE id='$tax_id' LIMIT 1");
                $tax_rate = ($row = mysqli_fetch_assoc($tax_res)) ? (float)$row['taxrate'] : 0;
                
                // Calculate Item Subtotal based on Tax Method
                if ($method == 'exclusive') {
                    $item_total = ($price * $qty) * (1 + $tax_rate/100);
                } else {
                    $item_total = ($price * $qty);
                }
                
                $grand_subtotal += $item_total;

                $item_query = "INSERT INTO quotation_items (quotation_id, product_id, price, qty, tax_rate_id, tax_method, subtotal) 
                               VALUES ('$quotation_id', '$p_id', '$price', '$qty', '$tax_id', '$method', '$item_total')";
                if(!mysqli_query($conn, $item_query)) throw new Exception(mysqli_error($conn));
            }
        }

        // Calculate Grand Total
        $taxable_amount = $grand_subtotal - $discount;
        $order_tax_amount = ($taxable_amount * $order_tax_rate) / 100;
        $grand_total = $taxable_amount + $order_tax_amount + $shipping_cost + $others_charge;

        // Update Totals
        mysqli_query($conn, "UPDATE quotations SET subtotal='$grand_subtotal', grand_total='$grand_total' WHERE id='$quotation_id'");

        mysqli_commit($conn);
        $_SESSION['message'] = "Quotation #$ref_no Created!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/quotations/list");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['msg_type'] = "error";
        header("Location: add_quotation.php");
    }
    exit(0);
}

// ---------------------------------------------------------
// 3. UPDATE QUOTATION
// ---------------------------------------------------------
if(isset($_POST['update_quotation_btn'])) {
    
    $quotation_id   = (int)$_POST['quotation_id'];
    $ref_no         = mysqli_real_escape_string($conn, $_POST['ref_no']);
    $customer_id    = (int)$_POST['customer_id'];
    $supplier_id    = (int)$_POST['supplier_id'];
    $date           = mysqli_real_escape_string($conn, $_POST['date']);
    $terms          = mysqli_real_escape_string($conn, $_POST['terms']);
    
    $discount       = (float)$_POST['discount'];
    $order_tax_rate = (float)$_POST['order_tax'];
    $shipping_cost  = (float)$_POST['shipping'];
    $others_charge  = (float)$_POST['others_charge'];
    $status         = (int)$_POST['status'];

    $product_ids    = $_POST['product_id']; 
    $prices         = $_POST['price'];      
    $qtys           = $_POST['qty'];        
    $item_taxes     = $_POST['item_tax'];   
    $tax_methods    = $_POST['tax_method']; 

    // Check if Ref No exists for OTHER quotation
    $check_query = "SELECT id FROM quotations WHERE ref_no='$ref_no' AND id != '$quotation_id'";
    $check_run = mysqli_query($conn, $check_query);
    if(mysqli_num_rows($check_run) > 0) {
        $_SESSION['message'] = "Reference No '$ref_no' already used by another quotation!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_quotation.php?id=$quotation_id");
        exit(0);
    }

    mysqli_begin_transaction($conn);
    try {
        // Update Main Table
        $update_q = "UPDATE quotations SET 
                        ref_no='$ref_no', customer_id='$customer_id', supplier_id='$supplier_id', 
                        date='$date', discount='$discount', order_tax_rate='$order_tax_rate', 
                        shipping_cost='$shipping_cost', others_charge='$others_charge', terms='$terms',
                        status='$status'
                      WHERE id='$quotation_id'";
        if(!mysqli_query($conn, $update_q)) throw new Exception(mysqli_error($conn));

        // Delete Old Items (Simplest way to update lines)
        mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id='$quotation_id'");
        
        $grand_subtotal = 0;
        
        // Insert New Items
        foreach($product_ids as $key => $p_id) {
            if(!empty($p_id)) {
                $p_id = (int)$p_id; 
                $price = (float)$prices[$key]; 
                $qty = (float)$qtys[$key];
                $tax_id = (int)$item_taxes[$key]; 
                $method = mysqli_real_escape_string($conn, $tax_methods[$key]);
                
                $tax_res = mysqli_query($conn, "SELECT taxrate FROM taxrates WHERE id='$tax_id' LIMIT 1");
                $tax_rate = ($row = mysqli_fetch_assoc($tax_res)) ? (float)$row['taxrate'] : 0;
                
                if ($method == 'exclusive') {
                    $item_total = ($price * $qty) * (1 + $tax_rate/100);
                } else {
                    $item_total = ($price * $qty);
                }

                $grand_subtotal += $item_total;

                $item_query = "INSERT INTO quotation_items (quotation_id, product_id, price, qty, tax_rate_id, tax_method, subtotal) 
                               VALUES ('$quotation_id', '$p_id', '$price', '$qty', '$tax_id', '$method', '$item_total')";
                if(!mysqli_query($conn, $item_query)) throw new Exception(mysqli_error($conn));
            }
        }

        // Recalculate Grand Total
        $taxable_amount = $grand_subtotal - $discount;
        $order_tax_amount = ($taxable_amount * $order_tax_rate) / 100;
        $grand_total = $taxable_amount + $order_tax_amount + $shipping_cost + $others_charge;

        mysqli_query($conn, "UPDATE quotations SET subtotal='$grand_subtotal', grand_total='$grand_total' WHERE id='$quotation_id'");

        mysqli_commit($conn);
        $_SESSION['message'] = "Quotation Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/quotations/list");

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Update Failed: " . $e->getMessage();
        $_SESSION['msg_type'] = "error";
        header("Location: add_quotation.php?id=$quotation_id");
    }
    exit(0);
}

// ---------------------------------------------------------
// 4. DELETE QUOTATION
// ---------------------------------------------------------
if(isset($_POST['delete_btn'])) { 
    $id = isset($_POST['delete_id']) ? (int)$_POST['delete_id'] : 0;
    
    if($id > 0) {
        // Delete items first (Good practice even if CASCADE exists)
        mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id='$id'");
        
        // Delete quotation
        if(mysqli_query($conn, "DELETE FROM quotations WHERE id='$id'")) {
            $_SESSION['message'] = "Quotation deleted successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid ID";
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/quotations/list");
    exit(0);
}

// ---------------------------------------------------------
// 5. TOGGLE STATUS
// ---------------------------------------------------------
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status']; 
    
    // Update Logic
    $query = "UPDATE quotations SET status='$status' WHERE id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating status: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/quotations/list");
    exit(0);
}

// Fallback redirect
header("Location: /pos/quotations/list");
exit(0);
?>
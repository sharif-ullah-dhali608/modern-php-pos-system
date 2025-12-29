<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// --- CREATE QUOTATION ---
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
    // [FIX] Status Capture
    $status         = isset($_POST['status']) ? (int)$_POST['status'] : 1; 

    $product_ids    = $_POST['product_id']; 
    $prices         = $_POST['price'];      
    $qtys           = $_POST['qty'];        
    $item_taxes     = $_POST['item_tax'];   
    $tax_methods    = $_POST['tax_method']; 

    // Check Duplicate
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
        $query = "INSERT INTO quotations (ref_no, customer_id, supplier_id, date, discount, order_tax_rate, shipping_cost, others_charge, terms, status) 
                  VALUES ('$ref_no', '$customer_id', '$supplier_id', '$date', '$discount', '$order_tax_rate', '$shipping_cost', '$others_charge', '$terms', '$status')";
        
        if(!mysqli_query($conn, $query)) throw new Exception(mysqli_error($conn));
        $quotation_id = mysqli_insert_id($conn);

        $grand_subtotal = 0;

        foreach($product_ids as $key => $p_id) {
            if(!empty($p_id)) {
                $p_id = (int)$p_id;
                $price = (float)$prices[$key];
                $qty = (float)$qtys[$key];
                $tax_id = (int)$item_taxes[$key];
                $method = mysqli_real_escape_string($conn, $tax_methods[$key]);

                $tax_res = mysqli_query($conn, "SELECT taxrate FROM taxrates WHERE id='$tax_id' LIMIT 1");
                $tax_rate = ($row = mysqli_fetch_assoc($tax_res)) ? (float)$row['taxrate'] : 0;
                
                $item_total = ($method == 'exclusive') ? ($price * $qty) * (1 + $tax_rate/100) : ($price * $qty);
                $grand_subtotal += $item_total;

                $item_query = "INSERT INTO quotation_items (quotation_id, product_id, price, qty, tax_rate_id, tax_method, subtotal) 
                               VALUES ('$quotation_id', '$p_id', '$price', '$qty', '$tax_id', '$method', '$item_total')";
                mysqli_query($conn, $item_query);
            }
        }

        $taxable_amount = $grand_subtotal - $discount;
        $order_tax_amount = ($taxable_amount * $order_tax_rate) / 100;
        $grand_total = $taxable_amount + $order_tax_amount + $shipping_cost + $others_charge;

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

// --- UPDATE QUOTATION ---
if(isset($_POST['update_quotation_btn'])) {
    $quotation_id = (int)$_POST['quotation_id'];
    $ref_no         = mysqli_real_escape_string($conn, $_POST['ref_no']);
    $customer_id    = (int)$_POST['customer_id'];
    $supplier_id    = (int)$_POST['supplier_id'];
    $date           = mysqli_real_escape_string($conn, $_POST['date']);
    $terms          = mysqli_real_escape_string($conn, $_POST['terms']);
    $discount       = (float)$_POST['discount'];
    $order_tax_rate = (float)$_POST['order_tax'];
    $shipping_cost  = (float)$_POST['shipping'];
    $others_charge  = (float)$_POST['others_charge'];
    
    // ✅ [FIX] Status Capture Here
    $status         = (int)$_POST['status'];

    $product_ids    = $_POST['product_id']; 
    $prices         = $_POST['price'];      
    $qtys           = $_POST['qty'];        
    $item_taxes     = $_POST['item_tax'];   
    $tax_methods    = $_POST['tax_method']; 

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
        // ✅ [FIX] Status added to Update Query
        $update_q = "UPDATE quotations SET 
                        ref_no='$ref_no', customer_id='$customer_id', supplier_id='$supplier_id', 
                        date='$date', discount='$discount', order_tax_rate='$order_tax_rate', 
                        shipping_cost='$shipping_cost', others_charge='$others_charge', terms='$terms',
                        status='$status'
                     WHERE id='$quotation_id'";
        mysqli_query($conn, $update_q);

        mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id='$quotation_id'");
        
        $grand_subtotal = 0;
        foreach($product_ids as $key => $p_id) {
            if(!empty($p_id)) {
                $p_id = (int)$p_id; $price = (float)$prices[$key]; $qty = (float)$qtys[$key];
                $tax_id = (int)$item_taxes[$key]; $method = mysqli_real_escape_string($conn, $tax_methods[$key]);
                
                $tax_res = mysqli_query($conn, "SELECT taxrate FROM taxrates WHERE id='$tax_id' LIMIT 1");
                $tax_rate = ($row = mysqli_fetch_assoc($tax_res)) ? (float)$row['taxrate'] : 0;
                $item_total = ($method == 'exclusive') ? ($price * $qty) * (1 + $tax_rate/100) : ($price * $qty);
                $grand_subtotal += $item_total;

                mysqli_query($conn, "INSERT INTO quotation_items (quotation_id, product_id, price, qty, tax_rate_id, tax_method, subtotal) 
                                     VALUES ('$quotation_id', '$p_id', '$price', '$qty', '$tax_id', '$method', '$item_total')");
            }
        }

        $grand_total = ($grand_subtotal - $discount) * (1 + $order_tax_rate/100) + $shipping_cost + $others_charge;
        mysqli_query($conn, "UPDATE quotations SET subtotal='$grand_subtotal', grand_total='$grand_total' WHERE id='$quotation_id'");

        mysqli_commit($conn);
        $_SESSION['message'] = "Quotation Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/quotations/list");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Update Failed!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_quotation.php?id=$quotation_id");
    }
    exit(0);
}

// --- DELETE QUOTATION ---
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id='$id'");
    
    if(mysqli_query($conn, "DELETE FROM quotations WHERE id='$id'")) {
        $_SESSION['message'] = "Quotation deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/quotations/list");
    exit(0);
}

// --- TOGGLE STATUS ---
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    
    if(mysqli_query($conn, "UPDATE quotations SET status='$status' WHERE id='$id'")){
        $_SESSION['message'] = "Quotation status updated!";
        $_SESSION['msg_type'] = "success";
    }
    header("Location: /pos/quotations/list");
    exit(0);
}

header("Location: /pos/quotations/list");
exit(0);
?>
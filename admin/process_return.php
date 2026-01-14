<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $original_invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $store_id = intval($_POST['store_id']);
    $customer_id = intval($_POST['customer_id']);
    $return_items = $_POST['return_items'] ?? [];
    $user_id = $_SESSION['auth_user']['user_id'] ?? 1;

    if(empty($return_items)){
        $_SESSION['message'] = "Error: No items selected for return";
        header("Location: sell_return_create.php?id=$original_invoice_id");
        exit;
    }

    // Generate Return Invoice ID
    $return_invoice_id = "RET-" . date('ymd') . rand(1000, 9999);
    
    $total_return_amount = 0;
    $total_items = 0;

    // Start Transaction
    mysqli_begin_transaction($conn);

    try {
        // 1. Process Items
        foreach($return_items as $info_item_id => $data){
            if(isset($data['selected']) && $data['selected'] == 1){
                $qty = floatval($data['qty']);
                $price = floatval($data['price']);
                $subtotal = $qty * $price;
                $note = mysqli_real_escape_string($conn, $data['note'] ?? '');

                if($qty <= 0) continue;

                $total_return_amount += $subtotal;
                $total_items += $qty;

                // Fetch original item details to copy item_id etc
                $info_item_id_safe = mysqli_real_escape_string($conn, $info_item_id);
                $orig_item_query = mysqli_query($conn, "SELECT * FROM selling_item WHERE id = '$info_item_id_safe'");
                $orig_item = mysqli_fetch_assoc($orig_item_query);

                if(!$orig_item) continue;

                $item_id = $orig_item['item_id'];
                $item_name = mysqli_real_escape_string($conn, $orig_item['item_name']);

                // Insert into selling_item for the return invoice
                $insert_item = "INSERT INTO selling_item 
                    (invoice_id, invoice_type, store_id, item_id, item_name, price_sold, qty_sold, subtotal, created_by) 
                    VALUES 
                    ('$return_invoice_id', 'return', '$store_id', '$item_id', '$item_name', '$price', '$qty', '$subtotal', '$user_id')";
                
                if(!mysqli_query($conn, $insert_item)){
                    throw new Exception("Error inserting return item: " . mysqli_error($conn));
                }

                // Update original selling_item record to track returned quantity
                $remaining_qty = floatval($orig_item['qty_sold']) - floatval($orig_item['return_item']);
                if($qty > $remaining_qty + 0.0001) { // Added small tolerance
                    throw new Exception("Cannot return more than available quantity for item: " . $item_name . ". Available: " . $remaining_qty);
                }

                if(!mysqli_query($conn, "UPDATE selling_item SET return_item = return_item + $qty WHERE id = '$info_item_id_safe'")){
                    throw new Exception("Error updating original item: " . mysqli_error($conn));
                }

                // Update global product stock (Increment opening_stock as it's a return)
                if(!mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock + $qty WHERE id = $item_id")){
                    throw new Exception("Error updating product stock: " . mysqli_error($conn));
                }
                
                // Update store-specific stock in product_store_map
                // First check if mapping exists
                $psm_check = mysqli_query($conn, "SELECT id FROM product_store_map WHERE product_id = $item_id AND store_id = $store_id");
                if($psm_check && mysqli_num_rows($psm_check) > 0) {
                    // Update existing mapping
                    if(!mysqli_query($conn, "UPDATE product_store_map SET stock = stock + $qty WHERE product_id = $item_id AND store_id = $store_id")){
                        throw new Exception("Error updating store stock: " . mysqli_error($conn));
                    }
                } else {
                    // Create mapping with returned stock
                    if(!mysqli_query($conn, "INSERT INTO product_store_map (product_id, store_id, stock) VALUES ($item_id, $store_id, $qty)")){
                        throw new Exception("Error creating store stock mapping: " . mysqli_error($conn));
                    }
                }
            }
        }

        if($total_return_amount == 0){
             throw new Exception("No valid items selected to return.");
        }

        // 2. Insert into selling_info
        $customer_id_sql = $customer_id > 0 ? $customer_id : "NULL";
        $insert_info = "INSERT INTO selling_info 
            (invoice_id, inv_type, store_id, customer_id, ref_invoice_id, invoice_note, grand_total, status, payment_status, created_by, created_at) 
            VALUES 
            ('$return_invoice_id', 'return', '$store_id', $customer_id_sql, '$original_invoice_id', 'Return for $original_invoice_id', '$total_return_amount', 'completed', 'paid', '$user_id', NOW())";

        if(!mysqli_query($conn, $insert_info)){
             throw new Exception("Error creating return invoice: " . mysqli_error($conn));
        }

        // 3. Update customer balance - Smart distribution of return amount
        // Logic: First clear any current_due, then add remaining to opening_balance (wallet)
        if($customer_id > 0) {
            // Get customer's current due
            $cust_query = mysqli_query($conn, "SELECT current_due FROM customers WHERE id = $customer_id");
            $cust_data = mysqli_fetch_assoc($cust_query);
            $current_due = floatval($cust_data['current_due'] ?? 0);
            
            if($current_due > 0) {
                // Customer has due - first deduct from due
                $deduct_from_due = min($total_return_amount, $current_due);
                $remaining_for_wallet = $total_return_amount - $deduct_from_due;
                
                // Deduct from current_due
                if($deduct_from_due > 0) {
                    if(!mysqli_query($conn, "UPDATE customers SET current_due = current_due - $deduct_from_due WHERE id = $customer_id")) {
                        throw new Exception("Error updating customer due balance: " . mysqli_error($conn));
                    }
                }
                
                // Add remaining to opening_balance (wallet)
                if($remaining_for_wallet > 0) {
                    if(!mysqli_query($conn, "UPDATE customers SET opening_balance = opening_balance + $remaining_for_wallet WHERE id = $customer_id")) {
                        throw new Exception("Error updating customer opening balance: " . mysqli_error($conn));
                    }
                }
            } else {
                // No due - add full return amount to opening_balance (wallet)
                if(!mysqli_query($conn, "UPDATE customers SET opening_balance = opening_balance + $total_return_amount WHERE id = $customer_id")) {
                    throw new Exception("Error updating customer credit balance: " . mysqli_error($conn));
                }
            }
        }

        // Commit
        mysqli_commit($conn);
        
        $_SESSION['message'] = "Return processed successfully. ID: $return_invoice_id";
        header("Location: sell_return.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Error: " . $e->getMessage();
        header("Location: sell_return_create.php?id=$original_invoice_id");
        exit;
    }

} else {
    header("Location: sell_return.php");
    exit;
}
?>

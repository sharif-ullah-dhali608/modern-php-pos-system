<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

// Security Check
if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit(0);
}

$user_id = $_SESSION['auth_user']['id'] ?? 1;
$action = $_POST['action'] ?? '';

switch($action) {
    case 'process_payment':
        processPayment($conn, $user_id);
        break;
    case 'hold_order':
        holdOrder($conn, $user_id);
        break;
    case 'quick_add_customer':
        quickAddCustomer($conn, $user_id);
        break;
    case 'quick_add_product':
        quickAddProduct($conn, $user_id);
        break;
    case 'quick_add_giftcard':
        quickAddGiftcard($conn, $user_id);
        break;
    case 'search_products':
        searchProducts($conn);
        break;
    case 'get_held_orders':
        getHeldOrders($conn);
        break;
    case 'resume_held_order':
        resumeHeldOrder($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Generate unique invoice ID
function generateInvoiceId($conn) {
    $prefix = 'INV';
    $date = date('Ymd');
    $query = "SELECT MAX(info_id) as max_id FROM selling_info WHERE invoice_id LIKE '$prefix$date%'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $next = ($row['max_id'] ?? 0) + 1;
    return $prefix . $date . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// Process payment and complete sale
function processPayment($conn, $user_id) {
    $cart = json_decode($_POST['cart'], true);
    $customer_id = intval($_POST['customer_id']) ?: null;
    $store_id = intval($_POST['store_id']);
    $payment_method_id = intval($_POST['payment_method_id']);
    $discount = floatval($_POST['discount']);
    $tax_percent = floatval($_POST['tax_percent']);
    $shipping = floatval($_POST['shipping']);
    $other_charge = floatval($_POST['other_charge']);
    $amount_received = floatval($_POST['amount_received']);
    $sale_date = mysqli_real_escape_string($conn, $_POST['sale_date']);
    
    if(empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Calculate totals
        $subtotal = 0;
        foreach($cart as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        $tax_amount = ($subtotal * $tax_percent) / 100;
        $grand_total = $subtotal - $discount + $tax_amount + $shipping + $other_charge;
        
        // Generate invoice ID
        $invoice_id = generateInvoiceId($conn);
        
        // Get customer mobile if customer selected
        $customer_mobile = '';
        if($customer_id) {
            $cq = mysqli_query($conn, "SELECT mobile FROM customers WHERE id = $customer_id");
            if($cr = mysqli_fetch_assoc($cq)) {
                $customer_mobile = $cr['mobile'];
            }
        }
        
        // Insert selling_info
        $customer_id_sql = $customer_id ? $customer_id : 'NULL';
        $info_sql = "INSERT INTO selling_info 
            (invoice_id, inv_type, store_id, customer_id, customer_mobile, total_items, 
             discount_amount, tax_amount, shipping_charge, other_charge, grand_total,
             status, payment_status, created_by, created_at) 
            VALUES 
            ('$invoice_id', 'sale', $store_id, $customer_id_sql, '$customer_mobile', $subtotal,
             $discount, $tax_amount, $shipping, $other_charge, $grand_total,
             'completed', 'paid', $user_id, '$sale_date')";
        
        if(!mysqli_query($conn, $info_sql)) {
            throw new Exception('Failed to create sale record: ' . mysqli_error($conn));
        }
        
        // Insert selling_items
        foreach($cart as $item) {
            $item_id = intval($item['id']);
            $item_name = mysqli_real_escape_string($conn, $item['name']);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $item_subtotal = $qty * $price;
            
            $item_sql = "INSERT INTO selling_item 
                (invoice_id, invoice_type, store_id, item_id, item_name, qty_sold, 
                 price_sold, subtotal, created_by) 
                VALUES 
                ('$invoice_id', 'sale', $store_id, $item_id, '$item_name', $qty,
                 $price, $item_subtotal, $user_id)";
            
            if(!mysqli_query($conn, $item_sql)) {
                throw new Exception('Failed to add item: ' . mysqli_error($conn));
            }
            
            // Update product stock
            mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock - $qty WHERE id = $item_id");
        }
        
        // Insert sell_logs for payment
        $ref_no = 'PAY' . date('YmdHis');
        $log_sql = "INSERT INTO sell_logs 
            (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by) 
            VALUES 
            ($customer_id_sql, '$ref_no', '$invoice_id', 'payment', $payment_method_id, $amount_received, $store_id, $user_id)";
        
        if(!mysqli_query($conn, $log_sql)) {
            throw new Exception('Failed to log payment: ' . mysqli_error($conn));
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sale completed successfully',
            'invoice_id' => $invoice_id,
            'grand_total' => $grand_total
        ]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Hold order
function holdOrder($conn, $user_id) {
    $cart = json_decode($_POST['cart'], true);
    $customer_id = intval($_POST['customer_id']) ?: null;
    $store_id = intval($_POST['store_id']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    
    if(empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // Calculate totals
        $subtotal = 0;
        foreach($cart as $item) {
            $subtotal += $item['price'] * $item['qty'];
        }
        
        // Generate invoice ID
        $invoice_id = 'HOLD' . date('YmdHis');
        
        // Insert selling_info with hold status
        $customer_id_sql = $customer_id ? $customer_id : 'NULL';
        $info_sql = "INSERT INTO selling_info 
            (invoice_id, inv_type, store_id, customer_id, invoice_note, total_items, 
             grand_total, status, payment_status, created_by) 
            VALUES 
            ('$invoice_id', 'sale', $store_id, $customer_id_sql, '$note', $subtotal,
             $subtotal, 'hold', 'due', $user_id)";
        
        if(!mysqli_query($conn, $info_sql)) {
            throw new Exception('Failed to hold order: ' . mysqli_error($conn));
        }
        
        // Insert selling_items with hold_status = 1
        foreach($cart as $item) {
            $item_id = intval($item['id']);
            $item_name = mysqli_real_escape_string($conn, $item['name']);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $item_subtotal = $qty * $price;
            
            $item_sql = "INSERT INTO selling_item 
                (invoice_id, invoice_type, store_id, item_id, item_name, qty_sold, 
                 price_sold, subtotal, hold_status, created_by) 
                VALUES 
                ('$invoice_id', 'sale', $store_id, $item_id, '$item_name', $qty,
                 $price, $item_subtotal, 1, $user_id)";
            
            if(!mysqli_query($conn, $item_sql)) {
                throw new Exception('Failed to add item: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order held successfully',
            'invoice_id' => $invoice_id
        ]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Quick add customer
function quickAddCustomer($conn, $user_id) {
    // Basic
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    
    // Expanded Fields
    $code_name = mysqli_real_escape_string($conn, $_POST['code_name'] ?? '');
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
    $customer_group = mysqli_real_escape_string($conn, $_POST['customer_group'] ?? 'Retail');
    $membership_level = mysqli_real_escape_string($conn, $_POST['membership_level'] ?? 'Standard');
    $sex = mysqli_real_escape_string($conn, $_POST['sex'] ?? 'Male');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    
    // Financial && Location
    $opening_balance = floatval($_POST['opening_balance'] ?? 0);
    $credit_limit = floatval($_POST['credit_limit'] ?? 0);
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $country = mysqli_real_escape_string($conn, $_POST['country'] ?? 'Bangladesh');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    
    $sort_order = intval($_POST['sort_order'] ?? 0);

    // Validation
    if(empty($name) || empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'Name and mobile are required']);
        return;
    }
    
    // Check if mobile exists
    $check = mysqli_query($conn, "SELECT id FROM customers WHERE mobile = '$mobile'");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile number already exists']);
        return;
    }
    
    $sql = "INSERT INTO customers (
                code_name, name, company_name, customer_group, membership_level, sex, 
                mobile, email, opening_balance, credit_limit, city, state, country, 
                address, sort_order, status, created_by
            ) VALUES (
                '$code_name', '$name', '$company_name', '$customer_group', '$membership_level', '$sex',
                '$mobile', '$email', $opening_balance, $credit_limit, '$city', '$state', '$country',
                '$address', $sort_order, 1, $user_id
            )";
    
    if(mysqli_query($conn, $sql)) {
        $customer_id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Customer added successfully',
            'customer_id' => $customer_id,
            'name' => $name,
            'mobile' => $mobile
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add customer: ' . mysqli_error($conn)]);
    }
}

// Quick add product
function quickAddProduct($conn, $user_id) {
    // Required
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $product_code = mysqli_real_escape_string($conn, $_POST['product_code']);
    $category_id = intval($_POST['category_id']);
    $unit_id = intval($_POST['unit_id'] ?? 0);
    $selling_price = floatval($_POST['selling_price']);
    
    // Optional
    $brand_id = intval($_POST['brand_id'] ?? 0);
    $purchase_price = floatval($_POST['purchase_price'] ?? 0);
    $tax_rate_id = intval($_POST['tax_rate_id'] ?? 0);
    $tax_method = mysqli_real_escape_string($conn, $_POST['tax_method'] ?? 'exclusive');
    $opening_stock = intval($_POST['opening_stock'] ?? 0);
    $alert_quantity = intval($_POST['alert_quantity'] ?? 5);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $expire_date = !empty($_POST['expire_date']) ? "'".mysqli_real_escape_string($conn, $_POST['expire_date'])."'" : "NULL";
    $barcode_symbology = mysqli_real_escape_string($conn, $_POST['barcode_symbology'] ?? 'code128');

    $store_id = intval($_POST['store_id'] ?? 0); // If 0, logic below handles mapping
    
    if(empty($product_name) || empty($product_code) || !$category_id || !$selling_price || !$unit_id) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        return;
    }
    
    // Check if code exists
    $check = mysqli_query($conn, "SELECT id FROM products WHERE product_code = '$product_code'");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Product code already exists']);
        return;
    }
    
    $sql = "INSERT INTO products (
                product_name, product_code, barcode_symbology, category_id, brand_id, unit_id,
                purchase_price, selling_price, tax_rate_id, tax_method,
                opening_stock, alert_quantity, expire_date, description,
                status, created_by
            ) VALUES (
                '$product_name', '$product_code', '$barcode_symbology', $category_id, $brand_id, $unit_id,
                $purchase_price, $selling_price, $tax_rate_id, '$tax_method',
                $opening_stock, $alert_quantity, $expire_date, '$description',
                1, $user_id
            )";
    
    if(mysqli_query($conn, $sql)) {
        $product_id = mysqli_insert_id($conn);
        
        // Map to store (If explicit store_id provided, use it. Otherwise map to ALL or Default)
        // Current POS behavior: If "All Stores" selected (store_id=0), map to ALL active stores? 
        // Logic from ajax_add_product.php: if store_id > 0 map to one. Else map to all.
        
        if($store_id > 0) {
            mysqli_query($conn, "INSERT INTO product_store_map (product_id, store_id) VALUES ($product_id, $store_id)");
        } else {
            // Map to all stores
            $stores = mysqli_query($conn, "SELECT id FROM stores WHERE status=1");
            while($store = mysqli_fetch_assoc($stores)) {
                 mysqli_query($conn, "INSERT INTO product_store_map (product_id, store_id) VALUES ($product_id, {$store['id']})");
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added successfully',
            'product_id' => $product_id,
            'product_name' => $product_name,
            'selling_price' => $selling_price
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . mysqli_error($conn)]);
    }
}

// Search products
function searchProducts($conn) {
    $query = mysqli_real_escape_string($conn, $_POST['query'] ?? '');
    
    $sql = "SELECT id, product_name, product_code, selling_price, opening_stock, thumbnail 
            FROM products 
            WHERE status = 1 AND (product_name LIKE '%$query%' OR product_code LIKE '%$query%')
            LIMIT 20";
    
    $result = mysqli_query($conn, $sql);
    $products = [];
    while($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
}

// Get held orders
function getHeldOrders($conn) {
    $store_id = intval($_POST['store_id'] ?? 0);
    
    $sql = "SELECT si.*, c.name as customer_name 
            FROM selling_info si 
            LEFT JOIN customers c ON si.customer_id = c.id 
            WHERE si.status = 'hold'";
    if($store_id) {
        $sql .= " AND si.store_id = $store_id";
    }
    $sql .= " ORDER BY si.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $orders = [];
    while($row = mysqli_fetch_assoc($result)) {
        // Get items
        $items_result = mysqli_query($conn, "SELECT * FROM selling_item WHERE invoice_id = '{$row['invoice_id']}'");
        $row['items'] = [];
        while($item = mysqli_fetch_assoc($items_result)) {
            $row['items'][] = $item;
        }
        $orders[] = $row;
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

// Resume held order
function resumeHeldOrder($conn) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    
    // Get order info
    $order_result = mysqli_query($conn, "SELECT * FROM selling_info WHERE invoice_id = '$invoice_id' AND status = 'hold'");
    if(mysqli_num_rows($order_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // Get items
    $items_result = mysqli_query($conn, "SELECT * FROM selling_item WHERE invoice_id = '$invoice_id'");
    $items = [];
    while($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'id' => $item['item_id'],
            'name' => $item['item_name'],
            'price' => floatval($item['price_sold']),
            'qty' => intval($item['qty_sold'])
        ];
    }
    
    // Delete hold records
    mysqli_query($conn, "DELETE FROM selling_item WHERE invoice_id = '$invoice_id'");
    mysqli_query($conn, "DELETE FROM selling_info WHERE invoice_id = '$invoice_id'");
    
    echo json_encode([
        'success' => true, 
        'order' => $order,
        'items' => $items
    ]);
}

// Quick add giftcard
function quickAddGiftcard($conn, $user_id) {
    // Get and validate input
    $card_no = mysqli_real_escape_string($conn, trim($_POST['card_no'] ?? ''));
    $value = floatval($_POST['value'] ?? 0);
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
    
    // Validation
    if(empty($card_no)) {
        echo json_encode(['success' => false, 'message' => 'Card number is required']);
        return;
    }
    
    if($card_no && strlen($card_no) < 8) {
        echo json_encode(['success' => false, 'message' => 'Card number must be at least 8 characters']);
        return;
    }
    
    if($value <= 0) {
        echo json_encode(['success' => false, 'message' => 'Card value must be greater than 0']);
        return;
    }
    
    // Check if card number already exists
    $check = mysqli_query($conn, "SELECT id FROM giftcards WHERE card_no = '$card_no'");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['success' => false, 'message' => 'Card number already exists']);
        return;
    }
    
    // Set balance equal to value on creation
    $balance = $value;
    
    // Set customer_id to NULL if not provided
    $customer_id_sql = $customer_id > 0 ? $customer_id : 'NULL';
    
    // Set expiry_date to NULL if not provided
    $expiry_date_sql = !empty($expiry_date) ? "'$expiry_date'" : 'NULL';
    
    // Insert giftcard
    $sql = "INSERT INTO giftcards (
                card_no, value, balance, customer_id, expiry_date, 
                status, created_by, created_at, updated_at
            ) VALUES (
                '$card_no', $value, $balance, $customer_id_sql, $expiry_date_sql,
                1, $user_id, NOW(), NOW()
            )";
    
    if(mysqli_query($conn, $sql)) {
        $giftcard_id = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true, 
            'message' => 'Giftcard created successfully',
            'giftcard_id' => $giftcard_id,
            'card_no' => $card_no,
            'value' => $value
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create giftcard: ' . mysqli_error($conn)]);
    }
}
?>

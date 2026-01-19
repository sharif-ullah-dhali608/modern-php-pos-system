<?php
session_start();
require_once('../config/dbcon.php');

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
    case 'register_payment':
        registerPayment($conn, $user_id);
        break;
    case 'delete_held_order':
        deleteHeldOrder($conn);
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
    $payment_method_input = $_POST['payment_method_id'] ?? '';
    $payment_method_id = 'NULL'; // Default to NULL for SQL
    
    // ... (rest of variables)
    $tax_percent = floatval($_POST['tax_percent']);
    $discount = floatval($_POST['discount']);
    $shipping = floatval($_POST['shipping']);
    $other_charge = floatval($_POST['other_charge']);
    $amount_received = floatval($_POST['amount_received']);
    $sale_date = mysqli_real_escape_string($conn, $_POST['sale_date']);
    $previous_due = floatval($_POST['previous_due'] ?? 0); // Previous due from payment modal
    
    if(empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }
    
    // Validate store_id exists
    if ($store_id) {
        $store_check = mysqli_query($conn, "SELECT id FROM stores WHERE id = $store_id");
        if (mysqli_num_rows($store_check) == 0) {
            echo json_encode(['success' => false, 'message' => "Invalid store selected (ID: $store_id)"]);
            return;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please select a store']);
        return;
    }
    
    // Calculate totals first to check for due payment
    $subtotal = 0;
    foreach($cart as $item) {
        $subtotal += $item['price'] * ($item['quantity'] ?? $item['qty'] ?? 1);
    }
    $tax_amount = (($subtotal - $discount) * $tax_percent) / 100;
    $grand_total = $subtotal - $discount + $tax_amount + $shipping + $other_charge;
    
    // Calculate total paid including applied payments (bKash, Nagad, Giftcard, etc.)
    $payments = json_decode($_POST['payments'] ?? '[]', true);
    $applied_payments_total = 0;
    if (is_array($payments)) {
        foreach ($payments as $p) {
            $applied_payments_total += floatval($p['amount'] ?? 0);
        }
    }
    $total_paid = $amount_received + $applied_payments_total;
    
    // Check if Walking Customer is trying to make due payment
    // Allow a small epsilon for floating point comparison issues
    if (($customer_id === null || $customer_id == 0) && ($total_paid < ($grand_total - 0.01))) {
        echo json_encode([
            'success' => false, 
            'message' => 'Walking Customer cannot make due payments. Please pay the full amount.',
            'is_walking_customer_error' => true
        ]);
        return;
    }

    // 0. STOCK VALIDATION (Server-Side)
    foreach($cart as $item) {
        $item_id = intval($item['id']);
        $req_qty = floatval($item['quantity'] ?? $item['qty'] ?? 1);
        $item_name = $item['name'];

        // Check stock in product_store_map
        $stock_query = mysqli_query($conn, "SELECT stock FROM product_store_map WHERE product_id = $item_id AND store_id = $store_id");
        if ($stock_query && mysqli_num_rows($stock_query) > 0) {
            $row = mysqli_fetch_assoc($stock_query);
            $current_stock = floatval($row['stock']);
            if ($req_qty > $current_stock) {
                echo json_encode(['success' => false, 'message' => "Insufficient stock for '$item_name'. Available: $current_stock"]);
                return;
            }
        } else {
            // Check global stock if not in map (fallback)
            $stock_query = mysqli_query($conn, "SELECT opening_stock FROM products WHERE id = $item_id");
            $row = mysqli_fetch_assoc($stock_query);
            $current_stock = floatval($row['opening_stock']);
            if ($req_qty > $current_stock) {
                 echo json_encode(['success' => false, 'message' => "Insufficient stock for '$item_name'. Available: $current_stock"]);
                 return;
            }
        }
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Calculate totals first
        $subtotal = 0;
        foreach($cart as $item) {
            $subtotal += $item['price'] * ($item['quantity'] ?? $item['qty'] ?? 1);
        }
        $tax_amount = (($subtotal - $discount) * $tax_percent) / 100;
        $grand_total = $subtotal - $discount + $tax_amount + $shipping + $other_charge;
        
        // 2. Generate invoice ID and get mobile
        $invoice_id = generateInvoiceId($conn);
        $customer_mobile = '';
        if($customer_id) {
            $cq = mysqli_query($conn, "SELECT mobile FROM customers WHERE id = $customer_id");
            if($cr = mysqli_fetch_assoc($cq)) $customer_mobile = $cr['mobile'];
        } else {
            $customer_mobile = isset($_POST['walking_customer_mobile']) ? mysqli_real_escape_string($conn, $_POST['walking_customer_mobile']) : '';
        }

        // 3. Process Payments (deduct from giftcard/wallet but don't log individually)
        $payments = json_decode($_POST['payments'] ?? '[]', true);
        $total_paid_from_applied = 0;
        $customer_id_sql = $customer_id ? $customer_id : 'NULL';
        $payment_methods_used = []; // Track all payment methods for description

        foreach ($payments as $p) {
            $p_type = $p['type'];
            $p_amount = floatval($p['amount']);
            if ($p_amount <= 0) continue;
            
            $total_paid_from_applied += $p_amount;

            if ($p_type === 'giftcard') {
                if (!$customer_id) throw new Exception("Customer required for Gift Card payment");
                $remaining_deduct = $p_amount;
                $cards = mysqli_query($conn, "SELECT id, balance FROM giftcards WHERE customer_id = $customer_id AND status = 1 AND balance > 0 ORDER BY expiry_date ASC");
                while($card = mysqli_fetch_assoc($cards)) {
                    if ($remaining_deduct <= 0) break;
                    $deduct = min($remaining_deduct, $card['balance']);
                    mysqli_query($conn, "UPDATE giftcards SET balance = balance - $deduct WHERE id = {$card['id']}");
                    $remaining_deduct -= $deduct;
                }
                if ($remaining_deduct > 0.01) throw new Exception("Insufficient Gift Card balance");
                $payment_methods_used[] = "Gift Card: " . number_format($p_amount, 2);

            } elseif ($p_type === 'opening_balance') {
                if (!$customer_id) throw new Exception("Customer required for Opening Balance payment");
                $cust_query = mysqli_query($conn, "SELECT opening_balance FROM customers WHERE id = $customer_id");
                $cust_row = mysqli_fetch_assoc($cust_query);
                $current_wallet = floatval($cust_row['opening_balance'] ?? 0);
                if ($current_wallet < $p_amount) throw new Exception("Insufficient Opening Balance");
                mysqli_query($conn, "UPDATE customers SET opening_balance = opening_balance - $p_amount WHERE id = $customer_id");
                $payment_methods_used[] = "Wallet: " . number_format($p_amount, 2);
            } else {
                // Other applied payment methods (Nagad, bKash, etc.)
                $pm_name_query = mysqli_query($conn, "SELECT name FROM payment_methods WHERE id = " . intval($p_type));
                $pm_name = mysqli_fetch_assoc($pm_name_query)['name'] ?? ucfirst($p_type);
                $payment_methods_used[] = $pm_name . ": " . number_format($p_amount, 2);
            }
        }

        // Setup payment_method_id for primary payment
        $payment_method_id = 'NULL';
        $primary_method_name = 'Cash';
        if ($payment_method_input !== 'credit' && $payment_method_input !== '') {
            $payment_method_id = intval($payment_method_input);
            if ($payment_method_id > 0) {
                $pm_name_query = mysqli_query($conn, "SELECT name FROM payment_methods WHERE id = $payment_method_id");
                $pm_row = mysqli_fetch_assoc($pm_name_query);
                $primary_method_name = $pm_row['name'] ?? 'Cash';
            } else {
                $payment_method_id = 'NULL';
            }
        } else if ($payment_method_input === 'credit') {
             $pm_query = mysqli_query($conn, "SELECT id, name FROM payment_methods WHERE code = 'credit' LIMIT 1");
             if ($pm = mysqli_fetch_assoc($pm_query)) {
                 $payment_method_id = $pm['id'];
                 $primary_method_name = $pm['name'];
             }
        }

        // Add primary payment method to list if amount received > 0
        // Combine with existing if same method already exists from applied payments
        if ($amount_received > 0) {
            $found_existing = false;
            foreach ($payment_methods_used as &$existing) {
                if (strpos($existing, $primary_method_name . ':') === 0) {
                    // Same method exists, extract amount and add to it
                    preg_match('/: ([\d,]+\.?\d*)/', $existing, $matches);
                    $existing_amount = floatval(str_replace(',', '', $matches[1] ?? 0));
                    $new_total = $existing_amount + $amount_received;
                    $existing = $primary_method_name . ": " . number_format($new_total, 2);
                    $found_existing = true;
                    break;
                }
            }
            unset($existing);
            
            if (!$found_existing) {
                $payment_methods_used[] = $primary_method_name . ": " . number_format($amount_received, 2);
            }
        }

        $total_amount_paid = $total_paid_from_applied + $amount_received;
        
        // Determine log type based on payment
        if ($total_amount_paid >= $grand_total - 0.01) {
            $log_type = 'full_payment';
        } elseif ($total_amount_paid <= 0.01) {
            $log_type = 'full_due';
        } else {
            $log_type = 'partial_payment';
        }
        
        // Create description with payment methods
        if ($log_type === 'full_due') {
            $log_description = 'Pay Later';
            $log_amount = $grand_total; // Show due amount
        } else {
            $log_description = implode(', ', $payment_methods_used);
            $log_amount = min($total_amount_paid, $grand_total); // Amount for this sale only
        }
        
        // Create sell_log entry for the sale payment
        $primary_ref_no = 'SAL' . date('YmdHis') . rand(10, 99);
        mysqli_query($conn, "INSERT INTO sell_logs 
            (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by, description) 
            VALUES ($customer_id_sql, '$primary_ref_no', '$invoice_id', '$log_type', $payment_method_id, $log_amount, $store_id, $user_id, '$log_description')");

        // If partial payment, also create a PARTIAL DUE entry for the remaining amount
        if ($log_type === 'partial_payment') {
            $due_amount = $grand_total - $total_amount_paid;
            $due_ref_no = 'SAL' . date('YmdHis') . rand(10, 99) . 'D';
            mysqli_query($conn, "INSERT INTO sell_logs 
                (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by, description) 
                VALUES ($customer_id_sql, '$due_ref_no', '$invoice_id', 'partial_due', NULL, $due_amount, $store_id, $user_id, 'Pay Later')");
        }

        $payment_status = ($total_amount_paid >= $grand_total - 0.01) ? 'paid' : 'due';

        // 4. Record selling_info
        $info_sql = "INSERT INTO selling_info 
            (invoice_id, inv_type, store_id, customer_id, customer_mobile, total_items, 
             discount_amount, tax_amount, shipping_charge, other_charge, grand_total,
             status, payment_status, created_by, created_at) 
            VALUES 
            ('$invoice_id', 'sale', $store_id, $customer_id_sql, '$customer_mobile', $subtotal,
             $discount, $tax_amount, $shipping, $other_charge, $grand_total,
             'completed', '$payment_status', $user_id, '$sale_date')";
        
        if(!mysqli_query($conn, $info_sql)) throw new Exception('Failed to create sale: ' . mysqli_error($conn));
        
        // 5. Record items & stock
        foreach($cart as $item) {
            $item_id = intval($item['id']);
            $item_name = mysqli_real_escape_string($conn, $item['name']);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $item_subtotal = $qty * $price;
            $item_sql = "INSERT INTO selling_item (invoice_id, invoice_type, store_id, item_id, item_name, qty_sold, price_sold, subtotal, created_by) 
                        VALUES ('$invoice_id', 'sale', $store_id, $item_id, '$item_name', $qty, $price, $item_subtotal, $user_id)";
            if(!mysqli_query($conn, $item_sql)) throw new Exception('Failed to add item: ' . mysqli_error($conn));
            
            // Update global stock
            mysqli_query($conn, "UPDATE products SET opening_stock = opening_stock - $qty WHERE id = $item_id");
            
            // Update store-specific stock
            $psm_check = mysqli_query($conn, "SELECT id FROM product_store_map WHERE product_id = $item_id AND store_id = $store_id");
            if($psm_check && mysqli_num_rows($psm_check) > 0) {
                mysqli_query($conn, "UPDATE product_store_map SET stock = stock - $qty WHERE product_id = $item_id AND store_id = $store_id");
            }
        }
        
        // 6. Update customer current_due
        // Logic: 
        // - total_payable = grand_total (current sale) + previous_due (from payment modal)
        // - total_paid = amount_received + applied_payments
        // - If paid > grand_total, excess goes to previous due
        // - current_due change = grand_total (new due from current sale) - min(paid, grand_total) for current + any previous_due paid
        if ($customer_id) {
            $total_payable = $grand_total + $previous_due;
            
            // Calculate how payment is distributed
            $paid_for_current_sale = min($total_amount_paid, $grand_total);
            $excess_payment = max(0, $total_amount_paid - $grand_total);
            $paid_for_previous_due = min($excess_payment, $previous_due);
            
            // Current sale's unpaid portion (adds to customer due)
            $current_sale_due = max(0, $grand_total - $paid_for_current_sale);
            
            // Net change in customer's current_due:
            // + current_sale_due (new due from this sale)
            // - paid_for_previous_due (payment towards old due)
            $due_change = $current_sale_due - $paid_for_previous_due;
            
            if ($due_change != 0) {
                mysqli_query($conn, "UPDATE customers SET current_due = current_due + $due_change WHERE id = $customer_id");
            }
            
            // 7. Log previous due payment as single 'due_paid' entry
            if ($paid_for_previous_due > 0) {
                // Create single due_paid log entry
                $due_ref_no = "DUE" . date('YmdHis') . rand(10, 99);
                $due_description = mysqli_real_escape_string($conn, $primary_method_name . ": " . number_format($paid_for_previous_due, 2) . " (via sale $invoice_id)");
                
                mysqli_query($conn, "INSERT INTO sell_logs 
                    (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by, description) 
                    VALUES 
                    ($customer_id, '$due_ref_no', '', 'due_paid', $payment_method_id, $paid_for_previous_due, $store_id, $user_id, '$due_description')");
                
                // Still allocate to old invoices for invoice list calculation (create payment entries silently)
                $remaining_to_allocate = $paid_for_previous_due;
                $old_invoices_query = "SELECT si.invoice_id, si.store_id, si.grand_total,
                    IFNULL((SELECT SUM(si2.grand_total) FROM selling_info si2 WHERE si2.ref_invoice_id = si.invoice_id AND si2.inv_type = 'return'), 0) as return_amount,
                    IFNULL((SELECT SUM(sl.amount) FROM sell_logs sl WHERE sl.ref_invoice_id = si.invoice_id AND sl.type IN ('full_payment','partial_payment','payment','due_paid')), 0) as paid_amount
                FROM selling_info si 
                WHERE si.customer_id = $customer_id AND si.inv_type = 'sale' AND si.invoice_id != '$invoice_id'
                ORDER BY si.created_at ASC";
                
                $old_inv_result = mysqli_query($conn, $old_invoices_query);
                if ($old_inv_result) {
                    while ($old_inv = mysqli_fetch_assoc($old_inv_result)) {
                        if ($remaining_to_allocate <= 0.01) break;
                        $invoice_due = floatval($old_inv['grand_total']) - floatval($old_inv['return_amount']) - floatval($old_inv['paid_amount']);
                        if ($invoice_due <= 0.01) continue;
                        
                        $allocate_amount = min($remaining_to_allocate, $invoice_due);
                        if ($allocate_amount > 0.01) {
                            $old_invoice_id = mysqli_real_escape_string($conn, $old_inv['invoice_id']);
                            // This is a hidden entry just for invoice calculations - type 'payment' for backward compatibility
                            mysqli_query($conn, "INSERT INTO sell_logs 
                                (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by, description) 
                                VALUES ($customer_id, '$due_ref_no', '$old_invoice_id', 'payment', $payment_method_id, $allocate_amount, ".intval($old_inv['store_id']).", $user_id, 'Allocated from due payment')");
                            $remaining_to_allocate -= $allocate_amount;
                        }
                    }
                }
            }
        }
        
        mysqli_commit($conn);
        $new_balance = 0;
        $updated_opening_balance = 0;
        $updated_giftcard_balance = 0;
        
        if ($customer_id) {
            // Fetch updated customer balances
            $nb_res = mysqli_query($conn, "SELECT current_due, opening_balance FROM customers WHERE id = $customer_id");
            $nb_row = mysqli_fetch_assoc($nb_res);
            $new_balance = floatval($nb_row['current_due']);
            $updated_opening_balance = floatval($nb_row['opening_balance']);
            
            // Fetch updated giftcard balance
            $gc_res = mysqli_query($conn, "SELECT COALESCE(SUM(balance), 0) as gc_balance FROM giftcards WHERE customer_id = $customer_id AND status = 1");
            $gc_row = mysqli_fetch_assoc($gc_res);
            $updated_giftcard_balance = floatval($gc_row['gc_balance']);
        }
        
        // Fetch updated stock alert count
        $alert_res = mysqli_query($conn, "SELECT COUNT(*) as alert_count FROM products WHERE status = 1 AND opening_stock <= alert_quantity");
        $alert_count = mysqli_fetch_assoc($alert_res)['alert_count'] ?? 0;

        echo json_encode([
            'success' => true, 
            'message' => 'Sale completed', 
            'invoice_id' => $invoice_id, 
            'grand_total' => $grand_total, 
            'new_balance' => $new_balance,
            'opening_balance' => $updated_opening_balance,
            'giftcard_balance' => $updated_giftcard_balance,
            'alert_count' => $alert_count
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
    $note = mysqli_real_escape_string($conn, $_POST['note']); // reference
    
    if(empty($cart)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        return;
    }
    
    // Generate ref_no (like 46c660 in image)
    $ref_no = substr(md5(uniqid()), 0, 8);
    
    // Get Customer Mobile and Name
    $customer_name = 'Walking Customer';
    $customer_mobile = '0170000000000';
    if($customer_id) {
        $c_res = mysqli_query($conn, "SELECT name, mobile FROM customers WHERE id = $customer_id");
        if($row = mysqli_fetch_assoc($c_res)) {
            $customer_name = $row['name'];
            $customer_mobile = $row['mobile'];
        }
    }
    $order_title = "hold-" . $customer_name;
    
    // Totals
    $discount = floatval($_POST['discount'] ?? 0);
    $tax_percent = floatval($_POST['tax_percent'] ?? 0);
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $shipping = floatval($_POST['shipping'] ?? 0);
    $other_charge = floatval($_POST['other_charge'] ?? 0);
    $grand_total = floatval($_POST['grand_total'] ?? 0);
    
    $subtotal = 0;
    foreach($cart as $item) {
        $subtotal += floatval($item['price']) * floatval($item['qty']);
    }

    mysqli_begin_transaction($conn);
    
    try {
        // 1. Insert into holding_info
        $c_id_sql = $customer_id ? $customer_id : 'NULL';
        $info_sql = "INSERT INTO holding_info 
            (store_id, order_title, ref_no, customer_id, customer_mobile, invoice_note, total_items, created_by) 
            VALUES ($store_id, '$order_title', '$ref_no', $c_id_sql, '$customer_mobile', '$note', " . count($cart) . ", $user_id)";
        
        if(!mysqli_query($conn, $info_sql)) {
            throw new Exception('Failed to save holding info: ' . mysqli_error($conn));
        }

        // 2. Insert into holding_price
        $price_sql = "INSERT INTO holding_price 
            (ref_no, store_id, subtotal, discount_type, discount_amount, item_tax, order_tax, shipping_type, shipping_amount, others_charge, payable_amount) 
            VALUES ('$ref_no', $store_id, $subtotal, 'plain', $discount, 0, $tax_amount, 'plain', $shipping, $other_charge, $grand_total)";
        
        if(!mysqli_query($conn, $price_sql)) {
            throw new Exception('Failed to save holding price: ' . mysqli_error($conn));
        }

        // 3. Insert into holding_item
        foreach($cart as $item) {
            $item_id = intval($item['id']);
            $item_name = mysqli_real_escape_string($conn, $item['name']);
            $qty = floatval($item['qty']);
            $price = floatval($item['price']);
            $item_total = $qty * $price;
            
            // Get product extra details (category, brand, etc)
            $p_res = mysqli_query($conn, "SELECT category_id, brand_id, supplier_id FROM products WHERE id = $item_id");
            $p_data = mysqli_fetch_assoc($p_res);
            $cat_id = intval($p_data['category_id'] ?? 1);
            $brand_id = intval($p_data['brand_id'] ?? 1);
            $sup_id = intval($p_data['supplier_id'] ?? 1);
            
            $item_sql = "INSERT INTO holding_item 
                (ref_no, store_id, item_id, category_id, brand_id, sup_id, item_name, item_price, item_quantity, item_total) 
                VALUES ('$ref_no', $store_id, $item_id, $cat_id, $brand_id, $sup_id, '$item_name', $price, $qty, $item_total)";
            
            if(!mysqli_query($conn, $item_sql)) {
                throw new Exception('Failed to save holding item: ' . mysqli_error($conn));
            }
        }
        
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Order held successfully',
            'order' => [
                'invoice_id' => $ref_no,
                'invoice_note' => $note,
                'customer_name' => $customer_name,
                'created_at' => date('Y-m-d H:i:s'),
                'total_items' => count($cart),
                'grand_total' => $grand_total,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'item_tax' => $tax_amount,
                'shipping_amount' => $shipping,
                'others_charge' => $other_charge,
                'items' => array_map(function($item) {
                     return [
                         'item_name' => $item['name'],
                         'item_price' => $item['price'],
                         'item_quantity' => $item['qty'],
                         'item_total' => $item['price'] * $item['qty']
                     ];
                }, $cart)
            ]
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
    
    $sql = "SELECT hi.*, hp.*, 
                   COALESCE(c.name, 'Walking Customer') as customer_name,
                   COALESCE(c.mobile, '0170000000000') as customer_phone,
                   COALESCE(c.current_due, 0) as customer_balance
            FROM holding_info hi 
            JOIN holding_price hp ON hi.ref_no = hp.ref_no
            LEFT JOIN customers c ON hi.customer_id = c.id 
            WHERE 1=1";
    if($store_id) {
        $sql .= " AND hi.store_id = $store_id";
    }
    $sql .= " ORDER BY hi.created_at DESC";
    
    $result = mysqli_query($conn, $sql);
    $orders = [];
    while($row = mysqli_fetch_assoc($result)) {
        // Map fields to match what frontend expects
        $row['invoice_id'] = $row['ref_no'];
        $row['grand_total'] = floatval($row['payable_amount']);
        $row['tax_amount'] = floatval($row['order_tax']);
        $row['shipping_charge'] = floatval($row['shipping_amount']);
        $row['other_charge'] = floatval($row['others_charge']);
        $row['discount_amount'] = floatval($row['discount_amount']);
        $row['subtotal'] = floatval($row['subtotal']);
        
        // Get items
        $items_result = mysqli_query($conn, "SELECT * FROM holding_item WHERE ref_no = '{$row['ref_no']}'");
        $row['items'] = [];
        while($item = mysqli_fetch_assoc($items_result)) {
            // Map fields for frontend
            $item['price_sold'] = $item['item_price'];
            $item['qty_sold'] = $item['item_quantity'];
            $item['subtotal'] = $item['item_total'];
            $row['items'][] = $item;
        }
        $orders[] = $row;
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

// Resume held order
function resumeHeldOrder($conn) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']); // This is ref_no
    
    // Get order info
    $sql = "SELECT hi.*, hp.*, 
                   COALESCE(c.name, 'Walking Customer') as customer_name,
                   COALESCE(c.mobile, '0170000000000') as customer_phone,
                   COALESCE(c.current_due, 0) as customer_balance
            FROM holding_info hi 
            JOIN holding_price hp ON hi.ref_no = hp.ref_no
            LEFT JOIN customers c ON hi.customer_id = c.id 
            WHERE hi.ref_no = '$invoice_id'";
    
    $order_result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($order_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    $row = mysqli_fetch_assoc($order_result);
    // Map fields for frontend
    $row['invoice_id'] = $row['ref_no'];
    $row['grand_total'] = floatval($row['payable_amount']);
    $row['tax_amount'] = floatval($row['order_tax']);
    $row['shipping_charge'] = floatval($row['shipping_amount']);
    $row['other_charge'] = floatval($row['others_charge']);
    $row['discount_amount'] = floatval($row['discount_amount']);
    $row['subtotal'] = floatval($row['subtotal']);
    $row['tax_percent'] = 0; 
    
    // Get items
    $items_result = mysqli_query($conn, "SELECT * FROM holding_item WHERE ref_no = '$invoice_id'");
    $items = [];
    while($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'id' => $item['item_id'],
            'name' => $item['item_name'],
            'price' => floatval($item['item_price']),
            'qty' => floatval($item['item_quantity'])
        ];
    }
    
    // Delete hold records from all 3 tables
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM holding_item WHERE ref_no = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM holding_price WHERE ref_no = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM holding_info WHERE ref_no = '$invoice_id'");
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Failed to delete holding records']);
        return;
    }
    
    echo json_encode([
        'success' => true, 
        'order' => $row,
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

function registerPayment($conn, $user_id) {
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']);
    $amount_received = floatval($_POST['amount_received'] ?? $_POST['amount'] ?? 0);
    $pmethod_id = intval($_POST['payment_method_id'] ?? 0);
    $note = mysqli_real_escape_string($conn, $_POST['note'] ?? '');
    $store_id = intval($_POST['store_id']);
    $customer_id = intval($_POST['customer_id']);
    
    // Parse applied payments from JSON
    $payments = json_decode($_POST['payments'] ?? '[]', true);
    if (!is_array($payments)) $payments = [];
    
    // Calculate total and collect payment method descriptions
    $total_from_applied = 0;
    $payment_methods_desc = [];
    
    foreach ($payments as $p) {
        $p_amount = floatval($p['amount'] ?? 0);
        if ($p_amount <= 0) continue;
        
        $total_from_applied += $p_amount;
        $p_label = $p['label'] ?? ucfirst($p['type'] ?? 'Other');
        $payment_methods_desc[] = $p_label . ": " . number_format($p_amount, 2);
    }
    
    // Add primary payment method
    if ($amount_received > 0) {
        $primary_method_name = 'Cash';
        if ($pmethod_id > 0) {
            $pm_query = mysqli_query($conn, "SELECT name FROM payment_methods WHERE id = $pmethod_id");
            $pm_row = mysqli_fetch_assoc($pm_query);
            $primary_method_name = $pm_row['name'] ?? 'Cash';
        }
        $payment_methods_desc[] = $primary_method_name . ": " . number_format($amount_received, 2);
    }
    
    // Total amount to be paid
    $total_amount = $amount_received + $total_from_applied;
    
    if($total_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than 0']);
        return;
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        $customer_id_sql = $customer_id > 0 ? $customer_id : 'NULL';
        
        // Create SINGLE due_paid log entry
        $ref_no = 'DUE' . date('YmdHis') . rand(10, 99);
        $pmethod_id_sql = $pmethod_id > 0 ? $pmethod_id : 'NULL';
        $log_description = mysqli_real_escape_string($conn, implode(', ', $payment_methods_desc));
        
        $log_sql = "INSERT INTO sell_logs 
            (customer_id, reference_no, ref_invoice_id, type, pmethod_id, amount, store_id, created_by, description) 
            VALUES 
            ($customer_id_sql, '$ref_no', '$invoice_id', 'due_paid', $pmethod_id_sql, $total_amount, $store_id, $user_id, '$log_description')";
        
        if(!mysqli_query($conn, $log_sql)) {
            throw new Exception('Failed to record payment log: ' . mysqli_error($conn));
        }
        
        // Update customer due balance
        if ($customer_id) {
            mysqli_query($conn, "UPDATE customers SET current_due = current_due - $total_amount WHERE id = $customer_id");
        }
        
        // 3. Update Invoice Status in selling_info
        $query = "SELECT si.*, 
                  c.name as customer_name, c.mobile as customer_phone,
                  st.store_name, st.address as store_address, st.phone as store_phone, st.email as store_email,
                  (si.grand_total - IFNULL((SELECT SUM(grand_total) FROM selling_info WHERE ref_invoice_id = si.invoice_id AND inv_type = 'return'), 0)) as net_amount,
                  IFNULL((SELECT SUM(amount) FROM sell_logs WHERE ref_invoice_id = si.invoice_id AND type IN ('payment', 'full_payment', 'partial_payment', 'due_paid')), 0) as paid_amount
                  FROM selling_info si 
                  LEFT JOIN customers c ON si.customer_id = c.id
                  LEFT JOIN stores st ON si.store_id = st.id
                  WHERE si.invoice_id = '$invoice_id'";
        
        $res = mysqli_query($conn, $query);
        $invoice = mysqli_fetch_assoc($res);
        
        if($invoice) {
            $new_status = ($invoice['paid_amount'] >= $invoice['net_amount'] - 0.01) ? 'paid' : 'due';
            mysqli_query($conn, "UPDATE selling_info SET payment_status = '$new_status' WHERE invoice_id = '$invoice_id'");
        }
        
        // 4. Fetch invoice items
        $items_query = "SELECT item_name as name, qty_sold as qty, price_sold as price FROM selling_item WHERE invoice_id = '$invoice_id'";
        $items_result = mysqli_query($conn, $items_query);
        $items = [];
        while($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }
        
        mysqli_commit($conn);
        
        // Return complete data for invoice modal
        echo json_encode([
            'success' => true, 
            'message' => 'Payment registered successfully',
            'invoice_id' => $invoice_id,
            'customer_name' => $invoice['customer_name'] ?? 'Customer',
            'customer_phone' => $invoice['customer_phone'] ?? '',
            'store_name' => $invoice['store_name'] ?? 'Modern POS',
            'store_address' => $invoice['store_address'] ?? '',
            'store_phone' => $invoice['store_phone'] ?? '',
            'store_email' => $invoice['store_email'] ?? '',
            'items' => $items,
            'subtotal' => floatval($invoice['total_items'] ?? 0),
            'discount' => floatval($invoice['discount_amount'] ?? 0),
            'tax' => floatval($invoice['tax_amount'] ?? 0),
            'shipping' => floatval($invoice['shipping_charge'] ?? 0),
            'grand_total' => floatval($invoice['net_amount'] ?? 0),
            'paid' => floatval($invoice['paid_amount'] ?? 0),
            'due' => max(0, floatval($invoice['net_amount'] ?? 0) - floatval($invoice['paid_amount'] ?? 0))
        ]);
        
    } catch(Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Delete held order
function deleteHeldOrder($conn) {
    if(!isset($_POST['invoice_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invoice ID missing']);
        return;
    }
    $invoice_id = mysqli_real_escape_string($conn, $_POST['invoice_id']); // This is ref_no
    
    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM holding_item WHERE ref_no = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM holding_price WHERE ref_no = '$invoice_id'");
        mysqli_query($conn, "DELETE FROM holding_info WHERE ref_no = '$invoice_id'");
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>

<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// --- 1. INSERT NEW CUSTOMER ---
if(isset($_POST['save_customer_btn'])) {
    
    // Sanitize Inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    // Customers often have a loyalty card number or code
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name'])); 
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']); 
    
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    
    // --- State Logic ---
    $state = '';
    if(!empty($_POST['state'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state']);
    } elseif(!empty($_POST['state_text'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state_text']);
    }

    $country = mysqli_real_escape_string($conn, $_POST['country']);
    
    // Details (Optional)
    $details = isset($_POST['details']) ? mysqli_real_escape_string($conn, $_POST['details']) : ''; 
    
    // New Fields
    $sex = mysqli_real_escape_string($conn, $_POST['sex']);
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : 0;
    $dob = !empty($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : NULL;
    $opening_balance = !empty($_POST['opening_balance']) ? mysqli_real_escape_string($conn, $_POST['opening_balance']) : 0.00;
    
    $customer_group = mysqli_real_escape_string($conn, $_POST['customer_group']);
    $membership_level = mysqli_real_escape_string($conn, $_POST['membership_level']);
    $credit_limit = !empty($_POST['credit_limit']) ? mysqli_real_escape_string($conn, $_POST['credit_limit']) : 0.00;
    $reward_points = !empty($_POST['reward_points']) ? (int)$_POST['reward_points'] : 0;
    
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0; 
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    
    // Handle Image Upload
    $image = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = time() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/customers/' . $image);
    }
    
    // Handle Stores (Mapping customers to stores)
    $stores = isset($_POST['store_ids']) ? $_POST['store_ids'] : (isset($_POST['stores']) ? $_POST['stores'] : []);

    // Validation
    if(empty($name) || empty($mobile)) {
        $_SESSION['message'] = "Name and Mobile are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add");
        exit(0);
    }

    // Check duplicate code (if code is provided)
    if(!empty($code_name)){
        $check = mysqli_query($conn, "SELECT id FROM customers WHERE code_name='$code_name'");
        if(mysqli_num_rows($check) > 0) {
            $_SESSION['message'] = "Customer Code already exists!";
            $_SESSION['msg_type'] = "error";
            header("Location: /pos/customers/add");
            exit(0);
        }
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add");
        exit(0);
    }

    // Insert Query
    // Note: Removed trade_license and bank_account as they are supplier specific usually
    $dob_val = $dob ? "'$dob'" : "NULL";
    $query = "INSERT INTO customers (name, company_name, code_name, email, mobile, address, city, state, country, details, image, status, sort_order, sex, age, dob, opening_balance, customer_group, membership_level, credit_limit, reward_points) 
              VALUES ('$name', '$company_name', '$code_name', '$email', '$mobile', '$address', '$city', '$state', '$country', '$details', '$image', '$status', '$sort_order', '$sex', '$age', $dob_val, '$opening_balance', '$customer_group', '$membership_level', '$credit_limit', '$reward_points')";
    
    if(mysqli_query($conn, $query)) {
        $customer_id = mysqli_insert_id($conn);
        
        // Insert store-customer relationships
        // Assumes table name is 'customer_stores_map'
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO customer_stores_map (store_id, customer_id) VALUES ('$store_id', '$customer_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Customer added successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/customers/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add");
    }
    exit(0);
}

// --- 2. UPDATE CUSTOMER ---
if(isset($_POST['update_customer_btn'])) {
    
    $customer_id = (int)mysqli_real_escape_string($conn, $_POST['customer_id']);
    
    // Sanitize Inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);

    // New Fields
    $sex = mysqli_real_escape_string($conn, $_POST['sex']);
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : 0;
    $dob = !empty($_POST['dob']) ? mysqli_real_escape_string($conn, $_POST['dob']) : NULL;
    $opening_balance = !empty($_POST['opening_balance']) ? mysqli_real_escape_string($conn, $_POST['opening_balance']) : 0.00;
    
    $customer_group = mysqli_real_escape_string($conn, $_POST['customer_group']);
    $membership_level = mysqli_real_escape_string($conn, $_POST['membership_level']);
    $credit_limit = !empty($_POST['credit_limit']) ? mysqli_real_escape_string($conn, $_POST['credit_limit']) : 0.00;
    $reward_points = !empty($_POST['reward_points']) ? (int)$_POST['reward_points'] : 0;
    
    // --- State Logic ---
    $state = '';
    if(!empty($_POST['state'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state']);
    } elseif(!empty($_POST['state_text'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state_text']);
    }

    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $details = isset($_POST['details']) ? mysqli_real_escape_string($conn, $_POST['details']) : '';
    
    if(isset($_POST['status'])) {
        $status = (int)$_POST['status']; 
    } else {
        $status = 0; 
    }
    
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['store_ids']) ? $_POST['store_ids'] : (isset($_POST['stores']) ? $_POST['stores'] : []);

    // Handle Image Upload
    $image_query_part = "";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_image = time() . '.' . $ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/customers/' . $new_image)) {
            $image_query_part = "image='$new_image', ";
            // Optional: Delete old image
            $old_image_query = mysqli_query($conn, "SELECT image FROM customers WHERE id='$customer_id'");
            $old_data = mysqli_fetch_assoc($old_image_query);
            if(!empty($old_data['image']) && file_exists('../uploads/customers/' . $old_data['image'])) {
                unlink('../uploads/customers/' . $old_data['image']);
            }
        }
    }

    // Validation
    if(empty($name) || empty($mobile)) {
        $_SESSION['message'] = "Name and Mobile are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add?id=$customer_id");
        exit(0);
    }

    // Check duplicate code (exclude current customer ID)
    if(!empty($code_name)){
        $check = mysqli_query($conn, "SELECT id FROM customers WHERE code_name='$code_name' AND id != '$customer_id'");
        if(mysqli_num_rows($check) > 0) {
            $_SESSION['message'] = "Customer Code already exists!";
            $_SESSION['msg_type'] = "error";
            header("Location: /pos/customers/add?id=$customer_id");
            exit(0);
        }
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add?id=$customer_id");
        exit(0);
    }

    // Update Query
    $query = "UPDATE customers SET 
              name='$name', 
              company_name='$company_name',
              code_name='$code_name', 
              email='$email', 
              mobile='$mobile', 
              address='$address',
              city='$city',
              state='$state',
              country='$country',
              details='$details',
              $image_query_part
              status='$status', 
              sort_order='$sort_order',
              sex='$sex',
              age='$age',
              dob=" . ($dob ? "'$dob'" : "NULL") . ",
              opening_balance='$opening_balance',
              customer_group='$customer_group',
              membership_level='$membership_level',
              credit_limit='$credit_limit',
              reward_points='$reward_points'
              WHERE id='$customer_id'";
    
    if(mysqli_query($conn, $query)) {
        // Delete existing store-customer relationships
        mysqli_query($conn, "DELETE FROM customer_stores_map WHERE customer_id='$customer_id'");
        
        // Insert new store-customer relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO customer_stores_map (store_id, customer_id) VALUES ('$store_id', '$customer_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Customer updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/customers/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/customers/add?id=$customer_id");
    }
    exit(0);
}

// --- 3. DELETE CUSTOMER ---
if(isset($_POST['delete_btn'])) {
    $customer_id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);

    // Check usage in store map (Optional: You might want to check ORDERS table instead)
    /* Note: It is better to check if the customer has any SALES/ORDERS before deleting.
       Assuming table name is 'orders' or 'sales'. Example logic below:
       
       $check_orders = mysqli_query($conn, "SELECT count(*) as count FROM orders WHERE customer_id='$customer_id'");
       if(mysqli_fetch_assoc($check_orders)['count'] > 0) { ... error ... }
    */

    // Basic check on mapping table
    $check_usage = mysqli_query($conn, "SELECT COUNT(*) as count FROM customer_stores_map WHERE customer_id='$customer_id'");
    $usage = mysqli_fetch_assoc($check_usage);
    
    // If you want to force delete even if mapped, remove this if block.
    // Usually, we delete the map first, then the customer.
    
    // Clean up mapping
    mysqli_query($conn, "DELETE FROM customer_stores_map WHERE customer_id='$customer_id'");

    // Delete Customer
    $query = "DELETE FROM customers WHERE id='$customer_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Customer deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/customers/list");
    exit(0);
}

// --- 4. TOGGLE STATUS (AJAX/Form) ---
if(isset($_POST['toggle_status_btn'])) {
    $customer_id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE customers SET status='$status' WHERE id='$customer_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/customers/list");
    exit(0);
}

// If no action matched, redirect
header("Location: /pos/customers/list");
exit(0);
?>
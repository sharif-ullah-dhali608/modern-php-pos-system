<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// --- 1. INSERT NEW SUPPLIER ---
if(isset($_POST['save_supplier_btn'])) {
    
    // Sanitize Inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']); // Full mobile number
    
    // --- FIX: Added Missing Fields ---
    $trade_license_num = isset($_POST['trade_license_num']) ? mysqli_real_escape_string($conn, $_POST['trade_license_num']) : '';
    $bank_account_num = isset($_POST['bank_account_num']) ? mysqli_real_escape_string($conn, $_POST['bank_account_num']) : '';
    
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    
    // --- FIX: State Logic (Dropdown vs Text) ---
    $state = '';
    if(!empty($_POST['state'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state']);
    } elseif(!empty($_POST['state_text'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state_text']);
    }

    $country = mysqli_real_escape_string($conn, $_POST['country']);
    
    // Details (Optional)
    $details = isset($_POST['details']) ? mysqli_real_escape_string($conn, $_POST['details']) : ''; 
    
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0; 
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    
    // Handle Stores (Support both 'stores' and 'store_ids')
    $stores = isset($_POST['store_ids']) ? $_POST['store_ids'] : (isset($_POST['stores']) ? $_POST['stores'] : []);

    // Validation
    if(empty($name) || empty($code_name) || empty($mobile)) {
        $_SESSION['message'] = "Name, Code Name, and Mobile are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add");
        exit(0);
    }

    // Check duplicate code
    $check = mysqli_query($conn, "SELECT id FROM suppliers WHERE code_name='$code_name'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Supplier Code Name already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add");
        exit(0);
    }

    // Insert Query
    $query = "INSERT INTO suppliers (name, code_name, trade_license_num, bank_account_num, email, mobile, address, city, state, country, details, status, sort_order) 
              VALUES ('$name', '$code_name', '$trade_license_num', '$bank_account_num', '$email', '$mobile', '$address', '$city', '$state', '$country', '$details', '$status', '$sort_order')";
    
    if(mysqli_query($conn, $query)) {
        $supplier_id = mysqli_insert_id($conn);
        
        // Insert store-supplier relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO supplier_stores_map (store_id, supplier_id) VALUES ('$store_id', '$supplier_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Supplier onboarded successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/suppliers/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add");
    }
    exit(0);
}

// --- 2. UPDATE SUPPLIER ---
// NOTE: This block ONLY runs if 'update_supplier_btn' is present in POST data
if(isset($_POST['update_supplier_btn'])) {
    
    $supplier_id = (int)mysqli_real_escape_string($conn, $_POST['supplier_id']);
    
    // Sanitize Inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    
    // --- FIX: Added Missing Fields ---
    $trade_license_num = isset($_POST['trade_license_num']) ? mysqli_real_escape_string($conn, $_POST['trade_license_num']) : '';
    $bank_account_num = isset($_POST['bank_account_num']) ? mysqli_real_escape_string($conn, $_POST['bank_account_num']) : '';

    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    
    // --- FIX: State Logic ---
    $state = '';
    if(!empty($_POST['state'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state']);
    } elseif(!empty($_POST['state_text'])) {
        $state = mysqli_real_escape_string($conn, $_POST['state_text']);
    }

    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $details = isset($_POST['details']) ? mysqli_real_escape_string($conn, $_POST['details']) : '';
    
    // Status Logic
    $status = isset($_POST['status']) ? 1 : 0; 
    
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['store_ids']) ? $_POST['store_ids'] : (isset($_POST['stores']) ? $_POST['stores'] : []);

    // Validation
    if(empty($name) || empty($code_name) || empty($mobile)) {
        $_SESSION['message'] = "Name, Code Name, and Mobile are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add?id=$supplier_id");
        exit(0);
    }

    // Check duplicate code (exclude current supplier ID)
    $check = mysqli_query($conn, "SELECT id FROM suppliers WHERE code_name='$code_name' AND id != '$supplier_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Supplier Code Name already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add?id=$supplier_id");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add?id=$supplier_id");
        exit(0);
    }

    // Update Query
    $query = "UPDATE suppliers SET 
              name='$name', 
              code_name='$code_name', 
              trade_license_num='$trade_license_num',
              bank_account_num='$bank_account_num',
              email='$email', 
              mobile='$mobile', 
              address='$address',
              city='$city',
              state='$state',
              country='$country',
              details='$details',
              status='$status', 
              sort_order='$sort_order' 
              WHERE id='$supplier_id'";
    
    if(mysqli_query($conn, $query)) {
        // Delete existing store-supplier relationships
        mysqli_query($conn, "DELETE FROM supplier_stores_map WHERE supplier_id='$supplier_id'");
        
        // Insert new store-supplier relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO supplier_stores_map (store_id, supplier_id) VALUES ('$store_id', '$supplier_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Supplier updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/suppliers/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/add?id=$supplier_id");
    }
    exit(0);
}

// --- 3. DELETE SUPPLIER ---
if(isset($_POST['delete_btn'])) {
    $supplier_id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);

    // Check usage in store map
    $check_usage = mysqli_query($conn, "SELECT COUNT(*) as count FROM supplier_stores_map WHERE supplier_id='$supplier_id'");
    $usage = mysqli_fetch_assoc($check_usage);
    
    if($usage['count'] > 0) {
        $_SESSION['message'] = "Cannot delete supplier! They are assigned to stores.";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/suppliers/list");
        exit(0);
    }

    // Delete Supplier
    $query = "DELETE FROM suppliers WHERE id='$supplier_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Supplier deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/suppliers/list");
    exit(0);
}

// --- 4. TOGGLE STATUS (AJAX/Form) ---
if(isset($_POST['toggle_status_btn'])) {
    $supplier_id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE suppliers SET status='$status' WHERE id='$supplier_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/suppliers/list");
    exit(0);
}

// If no action matched, redirect
header("Location: /pos/suppliers/list");
exit(0);
?>
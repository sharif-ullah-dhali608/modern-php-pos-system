<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// --- 1. INSERT NEW CURRENCY ---
if(isset($_POST['save_currency_btn'])) {
    $currency_name = mysqli_real_escape_string($conn, $_POST['currency_name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $symbol_left = mysqli_real_escape_string($conn, $_POST['symbol_left']);
    $symbol_right = mysqli_real_escape_string($conn, $_POST['symbol_right']);
    $decimal_place = (int)mysqli_real_escape_string($conn, $_POST['decimal_place']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation
    if(empty($currency_name) || empty($code)) {
        $_SESSION['message'] = "Currency name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add");
        exit(0);
    }

    // Check duplicate code
    $check = mysqli_query($conn, "SELECT id FROM currencies WHERE code='$code'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Currency code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add");
        exit(0);
    }

    // Insert currency
    $query = "INSERT INTO currencies (currency_name, code, symbol_left, symbol_right, decimal_place, status, sort_order) 
              VALUES ('$currency_name', '$code', '$symbol_left', '$symbol_right', '$decimal_place', '$status', '$sort_order')";
    
    if(mysqli_query($conn, $query)) {
        $currency_id = mysqli_insert_id($conn);
        
        // Insert store-currency relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO store_currency (store_id, currency_id) VALUES ('$store_id', '$currency_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Currency created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/currency/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add");
    }
    exit(0);
}

// --- 2. UPDATE CURRENCY ---
if(isset($_POST['update_currency_btn'])) {
    $currency_id = (int)mysqli_real_escape_string($conn, $_POST['currency_id']);
    $currency_name = mysqli_real_escape_string($conn, $_POST['currency_name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $symbol_left = mysqli_real_escape_string($conn, $_POST['symbol_left']);
    $symbol_right = mysqli_real_escape_string($conn, $_POST['symbol_right']);
    $decimal_place = (int)mysqli_real_escape_string($conn, $_POST['decimal_place']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation
    if(empty($currency_name) || empty($code)) {
        $_SESSION['message'] = "Currency name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add?id=$currency_id");
        exit(0);
    }

    // Check duplicate code (exclude current)
    $check = mysqli_query($conn, "SELECT id FROM currencies WHERE code='$code' AND id != '$currency_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Currency code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add?id=$currency_id");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add?id=$currency_id");
        exit(0);
    }

    // Update currency
    $query = "UPDATE currencies SET 
              currency_name='$currency_name', 
              code='$code', 
              symbol_left='$symbol_left', 
              symbol_right='$symbol_right', 
              decimal_place='$decimal_place', 
              status='$status', 
              sort_order='$sort_order' 
              WHERE id='$currency_id'";
    
    if(mysqli_query($conn, $query)) {
        // Delete existing store-currency relationships
        mysqli_query($conn, "DELETE FROM store_currency WHERE currency_id='$currency_id'");
        
        // Insert new store-currency relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO store_currency (store_id, currency_id) VALUES ('$store_id', '$currency_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Currency updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/currency/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/add?id=$currency_id");
    }
    exit(0);
}

// --- 3. DELETE CURRENCY ---
if(isset($_POST['delete_btn'])) {
    $currency_id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);

    // Check if currency is being used
    $check_usage = mysqli_query($conn, "SELECT COUNT(*) as count FROM store_currency WHERE currency_id='$currency_id'");
    $usage = mysqli_fetch_assoc($check_usage);
    
    if($usage['count'] > 0) {
        $_SESSION['message'] = "Cannot delete currency! It is assigned to stores.";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/currency/list");
        exit(0);
    }

    // Delete currency
    $query = "DELETE FROM currencies WHERE id='$currency_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Currency deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/currency/list");
    exit(0);
}

// --- 4. TOGGLE STATUS ---
if(isset($_POST['toggle_status_btn'])) {
    $currency_id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE currencies SET status='$status' WHERE id='$currency_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/currency/list");
    exit(0);
}

// If no action matched, redirect
header("Location: /pos/currency/list");
exit(0);
?>


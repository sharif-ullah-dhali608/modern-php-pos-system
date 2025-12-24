<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php"); 
    exit(0);
}

// --- 1. INSERT NEW BOX ---
if(isset($_POST['save_box_btn'])) {
    $box_name = mysqli_real_escape_string($conn, $_POST['box_name']);
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $barcode_id = mysqli_real_escape_string($conn, $_POST['barcode_id']);
    $box_details = mysqli_real_escape_string($conn, $_POST['box_details']); // Optional
    $shelf_number = mysqli_real_escape_string($conn, $_POST['shelf_number']);
    $storage_type = mysqli_real_escape_string($conn, $_POST['storage_type']);
    $max_capacity = (int)mysqli_real_escape_string($conn, $_POST['max_capacity']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation (Mandatory Fields)
    if(empty($box_name) || empty($code_name) || empty($barcode_id) || empty($shelf_number) || empty($storage_type) || empty($max_capacity)) {
        $_SESSION['message'] = "All * marked fields are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/add");
        exit(0);
    }

    // Check duplicate Code Name OR Barcode ID
    $check = mysqli_query($conn, "SELECT id FROM boxes WHERE code_name='$code_name' OR barcode_id='$barcode_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Box Code Name or Barcode ID already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/add");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active branch/store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/add");
        exit(0);
    }

    // Insert Box
    $query = "INSERT INTO boxes (box_name, code_name, barcode_id, box_details, shelf_number, storage_type, max_capacity, status, sort_order) 
              VALUES ('$box_name', '$code_name', '$barcode_id', '$box_details', '$shelf_number', '$storage_type', '$max_capacity', '$status', '$sort_order')";
    
    if(mysqli_query($conn, $query)) {
        $box_id = mysqli_insert_id($conn);
        
        // Insert store-box relationships (Pivot Table: box_stores)
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO box_stores (store_id, box_id) VALUES ('$store_id', '$box_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Storage Box created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/boxes/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/add");
    }
    exit(0);
}

// --- 2. UPDATE BOX ---
if(isset($_POST['update_box_btn'])) {
    $box_id = (int)mysqli_real_escape_string($conn, $_POST['box_id']);
    $box_name = mysqli_real_escape_string($conn, $_POST['box_name']);
    $code_name = strtoupper(mysqli_real_escape_string($conn, $_POST['code_name']));
    $barcode_id = mysqli_real_escape_string($conn, $_POST['barcode_id']);
    $box_details = mysqli_real_escape_string($conn, $_POST['box_details']);
    $shelf_number = mysqli_real_escape_string($conn, $_POST['shelf_number']);
    $storage_type = mysqli_real_escape_string($conn, $_POST['storage_type']);
    $max_capacity = (int)mysqli_real_escape_string($conn, $_POST['max_capacity']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation
    if(empty($box_name) || empty($code_name) || empty($barcode_id) || empty($shelf_number) || empty($storage_type) || empty($max_capacity)) {
        $_SESSION['message'] = "All * marked fields are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/edit/$box_id");
        exit(0);
    }

    // Check duplicate Code/Barcode (exclude current ID)
    $check = mysqli_query($conn, "SELECT id FROM boxes WHERE (code_name='$code_name' OR barcode_id='$barcode_id') AND id != '$box_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Box Code Name or Barcode ID already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/edit/$box_id");
        exit(0);
    }

    // Check if stores are selected
    if(empty($stores)) {
        $_SESSION['message'] = "Please select at least one active branch/store!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/edit/$box_id");
        exit(0);
    }

    // Update Box
    $query = "UPDATE boxes SET 
              box_name='$box_name', 
              code_name='$code_name', 
              barcode_id='$barcode_id', 
              box_details='$box_details', 
              shelf_number='$shelf_number', 
              storage_type='$storage_type', 
              max_capacity='$max_capacity', 
              status='$status', 
              sort_order='$sort_order' 
              WHERE id='$box_id'";
    
    if(mysqli_query($conn, $query)) {
        // Delete existing store-box relationships
        mysqli_query($conn, "DELETE FROM box_stores WHERE box_id='$box_id'");
        
        // Insert new store-box relationships
        foreach($stores as $store_id) {
            $store_id = (int)mysqli_real_escape_string($conn, $store_id);
            $store_query = "INSERT INTO box_stores (store_id, box_id) VALUES ('$store_id', '$box_id')";
            mysqli_query($conn, $store_query);
        }
        
        $_SESSION['message'] = "Box information updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/boxes/list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/boxes/edit/$box_id");
    }
    exit(0);
}

// --- 3. DELETE BOX ---
if(isset($_POST['delete_btn'])) {
    $box_id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);

    // Optional: Check if Box has products inside (If you have a products table with box_id)
    // For now, we just delete the box and its store associations
    
    // First Delete Pivot Table Entries
    $delete_pivot = "DELETE FROM box_stores WHERE box_id='$box_id'";
    mysqli_query($conn, $delete_pivot);

    // Then Delete Main Box
    $query = "DELETE FROM boxes WHERE id='$box_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Box deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/boxes/list");
    exit(0);
}

// --- 4. TOGGLE STATUS ---
if(isset($_POST['toggle_status_btn'])) {
    $box_id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE boxes SET status='$status' WHERE id='$box_id'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Box status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/boxes/list");
    exit(0);
}

// If no action matched, redirect
header("Location: /pos/boxes/list");
exit(0);
?>
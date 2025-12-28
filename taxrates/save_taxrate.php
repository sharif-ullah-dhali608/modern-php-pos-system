<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK: Ensure user is logged in
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

/**
 * CREATE LOGIC: Save new Taxrate and its Store mappings
 */
if(isset($_POST['save_taxrate_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $taxrate = (float)mysqli_real_escape_string($conn, $_POST['taxrate']);
    $status = (int)$_POST['status']; // Handled by status_card.php toggle
    $sort_order = (int)$_POST['sort_order'];
    $stores = isset($_POST['stores']) ? $_POST['stores'] : []; // Array of selected store IDs

    // Validate required fields
    if(empty($name) || empty($code) || empty($stores)) {
        $_SESSION['message'] = "Name, Code, and at least one Store are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
        exit(0);
    }

    // Check for duplicate Tax Code
    $dup = mysqli_query($conn, "SELECT id FROM taxrates WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Tax code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
        exit(0);
    }

    // Insert into primary 'taxrates' table
    $q = "INSERT INTO taxrates (name, code, taxrate, status, sort_order) VALUES ('$name', '$code', '$taxrate', '$status', '$sort_order')";
    
    if(mysqli_query($conn, $q)) {
        $taxrate_id = mysqli_insert_id($conn); // Get the ID of the new taxrate

        // Map taxrate to selected stores in 'taxrate_store_map'
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO taxrate_store_map (taxrate_id, store_id) VALUES ('$taxrate_id', '$store_id')");
        }

        $_SESSION['message'] = "Taxrate created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/taxrates/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
    }
    exit(0);
}

/**
 * UPDATE LOGIC: Update Taxrate info and refresh Store mappings
 */
if(isset($_POST['update_taxrate_btn'])) {
    $id = (int)$_POST['taxrate_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $taxrate = (float)mysqli_real_escape_string($conn, $_POST['taxrate']);
    $status = (int)$_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation
    if(empty($name) || empty($code) || empty($stores)) {
        $_SESSION['message'] = "Please fill all required fields!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
        exit(0);
    }

    // Check for duplicate code excluding current record
    $dup = mysqli_query($conn, "SELECT id FROM taxrates WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Tax code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
        exit(0);
    }

    // Update primary 'taxrates' table
    $q = "UPDATE taxrates SET name='$name', code='$code', taxrate='$taxrate', status='$status', sort_order='$sort_order' WHERE id='$id'";
    
    if(mysqli_query($conn, $q)) {
        // Step 1: Remove old store associations
        mysqli_query($conn, "DELETE FROM taxrate_store_map WHERE taxrate_id='$id'");

        // Step 2: Insert new store associations
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO taxrate_store_map (taxrate_id, store_id) VALUES ('$id', '$store_id')");
        }

        $_SESSION['message'] = "Taxrate updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/taxrates/list");
    } else {
        $_SESSION['message'] = "Error updating taxrate: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
    }
    exit(0);
}

/**
 * DELETE LOGIC: Delete Taxrate and its mappings
 */
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    
    // First delete from pivot table due to potential foreign key constraints
    mysqli_query($conn, "DELETE FROM taxrate_store_map WHERE taxrate_id='$id'");
    // Then delete from primary table
    mysqli_query($conn, "DELETE FROM taxrates WHERE id='$id'");
    
    $_SESSION['message'] = "Taxrate deleted successfully!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/taxrates/list");
    exit(0);
}

/**
 * TOGGLE STATUS LOGIC
 */
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    mysqli_query($conn, "UPDATE taxrates SET status='$status' WHERE id='$id'");
    
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/taxrates/list");
    exit(0);
}

header("Location: /pos/taxrates/list");
exit(0);
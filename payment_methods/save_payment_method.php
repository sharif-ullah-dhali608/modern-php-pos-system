<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin");
    exit(0);
}

// CREATE SECTION
if(isset($_POST['save_payment_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)$_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    if(empty($name) || empty($code) || empty($stores)) {
        $_SESSION['message'] = "Name, Code, and at least one store are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/payment-methods/add");
        exit(0);
    }

    $check_code = mysqli_query($conn, "SELECT id FROM payment_methods WHERE code='$code' LIMIT 1");
    if(mysqli_num_rows($check_code) > 0) {
        $_SESSION['message'] = "Error: Payment code '$code' already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/payment-methods/add");
        exit(0);
    }

    $q = "INSERT INTO payment_methods (name, code, details, status, sort_order) VALUES ('$name', '$code', '$details', '$status', '$sort_order')";
    if(mysqli_query($conn, $q)) {
        $payment_id = mysqli_insert_id($conn);
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO payment_store_map (payment_method_id, store_id) VALUES ('$payment_id', '$store_id')");
        }
        $_SESSION['message'] = "Payment method created!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/payment-methods/list");
    } else {
        $_SESSION['message'] = "Something went wrong!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/payment-methods/add");
    }
    exit(0);
}

if(isset($_POST['update_payment_btn'])) {
    $id = (int)$_POST['payment_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)$_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    $check_code = mysqli_query($conn, "SELECT id FROM payment_methods WHERE code='$code' AND id != '$id' LIMIT 1");
    if(mysqli_num_rows($check_code) > 0) {
        $_SESSION['message'] = "Error: Code '$code' is already used by another payment method!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/payment-methods/edit/$id"); 
        exit(0);
    }

    $q = "UPDATE payment_methods SET name='$name', code='$code', details='$details', status='$status', sort_order='$sort_order' WHERE id='$id'";
    if(mysqli_query($conn, $q)) {
        mysqli_query($conn, "DELETE FROM payment_store_map WHERE payment_method_id='$id'");
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO payment_store_map (payment_method_id, store_id) VALUES ('$id', '$store_id')");
        }
        $_SESSION['message'] = "Payment method updated!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/payment-methods/list");
    } else {
        $_SESSION['message'] = "Update failed!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/payment-methods/edit/$id");
    }
    exit(0);
}

// DELETE & TOGGLE SECTION
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    mysqli_query($conn, "DELETE FROM payment_methods WHERE id='$id'");
    $_SESSION['message'] = "Payment method deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/payment-methods/list");
    exit(0);
}

if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    
    mysqli_query($conn, "UPDATE payment_methods SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/payment-methods/list");
    exit(0);
}

header("Location: /pos/payment-methods/list");
exit(0);
?>
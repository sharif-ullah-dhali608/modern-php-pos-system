<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// CREATE
if(isset($_POST['save_payment_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM payment_methods WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php");
        exit(0);
    }

    $q = "INSERT INTO payment_methods (name, code, details, status, sort_order) VALUES ('$name', '$code', '$details', '$status', '$sort_order')";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Payment method created!";
        $_SESSION['msg_type'] = "success";
        header("Location: payment_method_list.php");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php");
    }
    exit(0);
}

// UPDATE
if(isset($_POST['update_payment_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['payment_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php?id=$id");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM payment_methods WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php?id=$id");
        exit(0);
    }

    $q = "UPDATE payment_methods SET name='$name', code='$code', details='$details', status='$status', sort_order='$sort_order' WHERE id='$id'";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Payment method updated!";
        $_SESSION['msg_type'] = "success";
        header("Location: payment_method_list.php");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: add_payment_method.php?id=$id");
    }
    exit(0);
}

// DELETE
if(isset($_POST['delete_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);
    mysqli_query($conn, "DELETE FROM payment_methods WHERE id='$id'");
    $_SESSION['message'] = "Payment method deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: payment_method_list.php");
    exit(0);
}

// TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE payment_methods SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: payment_method_list.php");
    exit(0);
}

header("Location: payment_method_list.php");
exit(0);
?>


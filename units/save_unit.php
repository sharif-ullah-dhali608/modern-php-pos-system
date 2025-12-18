<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// CREATE
if(isset($_POST['save_unit_btn'])) {
    $unit_name = mysqli_real_escape_string($conn, $_POST['unit_name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($unit_name) || empty($code)) {
        $_SESSION['message'] = "Unit name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM units WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add");
        exit(0);
    }

    $q = "INSERT INTO units (unit_name, code, details, status, sort_order) VALUES ('$unit_name', '$code', '$details', '$status', '$sort_order')";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Unit created!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/units/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add");
    }
    exit(0);
}

// UPDATE
if(isset($_POST['update_unit_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['unit_id']);
    $unit_name = mysqli_real_escape_string($conn, $_POST['unit_name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($unit_name) || empty($code)) {
        $_SESSION['message'] = "Unit name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add?id=$id");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM units WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add?id=$id");
        exit(0);
    }

    $q = "UPDATE units SET unit_name='$unit_name', code='$code', details='$details', status='$status', sort_order='$sort_order' WHERE id='$id'";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Unit updated!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/units/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/units/add?id=$id");
    }
    exit(0);
}

// DELETE
if(isset($_POST['delete_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);
    mysqli_query($conn, "DELETE FROM units WHERE id='$id'");
    $_SESSION['message'] = "Unit deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/units/list");
    exit(0);
}

// TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE units SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/units/list");
    exit(0);
}

header("Location: /pos/units/list");
exit(0);
?>


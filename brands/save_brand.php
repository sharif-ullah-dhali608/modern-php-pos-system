<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// CREATE
if(isset($_POST['save_brand_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $thumbnail = mysqli_real_escape_string($conn, $_POST['thumbnail']);
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM brands WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
        exit(0);
    }

    $q = "INSERT INTO brands (name, code, thumbnail, details, status, sort_order) VALUES ('$name', '$code', '$thumbnail', '$details', '$status', '$sort_order')";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Brand created!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/brands/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
    }
    exit(0);
}

// UPDATE
if(isset($_POST['update_brand_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['brand_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $thumbnail = mysqli_real_escape_string($conn, $_POST['thumbnail']);
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add?id=$id");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM brands WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add?id=$id");
        exit(0);
    }

    $q = "UPDATE brands SET name='$name', code='$code', thumbnail='$thumbnail', details='$details', status='$status', sort_order='$sort_order' WHERE id='$id'";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Brand updated!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/brands/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/edit?id=$id");
    }
    exit(0);
}

// DELETE
if(isset($_POST['delete_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);
    mysqli_query($conn, "DELETE FROM brands WHERE id='$id'");
    $_SESSION['message'] = "Brand deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/brands/list");
    exit(0);
}

// TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE brands SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/brands/list");
    exit(0);
}

header("Location: /pos/brands/list");
exit(0);
?>


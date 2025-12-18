<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// CREATE
if(isset($_POST['save_taxrate_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $taxrate = (float)mysqli_real_escape_string($conn, $_POST['taxrate']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM taxrates WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
        exit(0);
    }

    $q = "INSERT INTO taxrates (name, code, taxrate, status, sort_order) VALUES ('$name', '$code', '$taxrate', '$status', '$sort_order')";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Taxrate created!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/taxrates/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add");
    }
    exit(0);
}

// UPDATE
if(isset($_POST['update_taxrate_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['taxrate_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $taxrate = (float)mysqli_real_escape_string($conn, $_POST['taxrate']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM taxrates WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
        exit(0);
    }

    $q = "UPDATE taxrates SET name='$name', code='$code', taxrate='$taxrate', status='$status', sort_order='$sort_order' WHERE id='$id'";
    if(mysqli_query($conn, $q)) {
        $_SESSION['message'] = "Taxrate updated!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/taxrates/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/taxrates/add?id=$id");
    }
    exit(0);
}

// DELETE
if(isset($_POST['delete_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);
    mysqli_query($conn, "DELETE FROM taxrates WHERE id='$id'");
    $_SESSION['message'] = "Taxrate deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/taxrates/list");
    exit(0);
}

// TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE taxrates SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/taxrates/list");
    exit(0);
}

header("Location: /pos/taxrates/list");
exit(0);
?>


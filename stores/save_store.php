<?php
session_start();
include('../config/dbcon.php');

// --- 1. INSERT NEW STORE ---
if(isset($_POST['save_store_btn']))
{
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $store_code = mysqli_real_escape_string($conn, $_POST['store_code']);
    $business_type = mysqli_real_escape_string($conn, $_POST['business_type']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city_zip = mysqli_real_escape_string($conn, $_POST['city_zip']);
    $vat_number = mysqli_real_escape_string($conn, $_POST['vat_number']);
    $timezone = mysqli_real_escape_string($conn, $_POST['timezone']);
    $max_line_disc = mysqli_real_escape_string($conn, $_POST['max_line_disc']);
    $max_inv_disc = mysqli_real_escape_string($conn, $_POST['max_inv_disc']);
    $approval_disc = mysqli_real_escape_string($conn, $_POST['approval_disc']);
    $overselling = mysqli_real_escape_string($conn, $_POST['overselling']);
    $low_stock = mysqli_real_escape_string($conn, $_POST['low_stock']);
    
    // FIX: Convert empty daily_target to '0' to avoid MySQL error
    $daily_target = mysqli_real_escape_string($conn, $_POST['daily_target']);
    if (empty($daily_target)) {
        $daily_target = '0'; 
    }
    
    $open_time = mysqli_real_escape_string($conn, $_POST['open_time']);
    $close_time = mysqli_real_escape_string($conn, $_POST['close_time']);

    $status = isset($_POST['status']) ? 1 : 0;
    $allow_manual_price = isset($_POST['allow_manual_price']) ? 1 : 0;
    $allow_backdate = isset($_POST['allow_backdate']) ? 1 : 0;

    // Duplicate Check
    $check = mysqli_query($conn, "SELECT id FROM stores WHERE store_code='$store_code'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Store Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_store.php"); // Stay on add page
        exit(0);
    }

    $query = "INSERT INTO stores (store_name, store_code, business_type, email, phone, address, city_zip, vat_number, timezone, max_line_disc, max_inv_disc, approval_disc, overselling, low_stock, status, daily_target, open_time, close_time, allow_manual_price, allow_backdate) VALUES ('$store_name', '$store_code', '$business_type', '$email', '$phone', '$address', '$city_zip', '$vat_number', '$timezone', '$max_line_disc', '$max_inv_disc', '$approval_disc', '$overselling', '$low_stock', '$status', '$daily_target', '$open_time', '$close_time', '$allow_manual_price', '$allow_backdate')";

    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Store Created Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: store_list.php"); // Redirect to list after success
    } else {
        $_SESSION['message'] = "Database Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: add_store.php");
    }
    exit(0);
}

// --- 2. UPDATE STORE ---
if(isset($_POST['update_store_btn']))
{
    $store_id = mysqli_real_escape_string($conn, $_POST['store_id']);
    
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $store_code = mysqli_real_escape_string($conn, $_POST['store_code']);
    $business_type = mysqli_real_escape_string($conn, $_POST['business_type']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city_zip = mysqli_real_escape_string($conn, $_POST['city_zip']);
    $vat_number = mysqli_real_escape_string($conn, $_POST['vat_number']);
    $timezone = mysqli_real_escape_string($conn, $_POST['timezone']);
    $max_line_disc = mysqli_real_escape_string($conn, $_POST['max_line_disc']);
    $max_inv_disc = mysqli_real_escape_string($conn, $_POST['max_inv_disc']);
    $approval_disc = mysqli_real_escape_string($conn, $_POST['approval_disc']);
    $overselling = mysqli_real_escape_string($conn, $_POST['overselling']);
    $low_stock = mysqli_real_escape_string($conn, $_POST['low_stock']);
    
    // FIX: Convert empty daily_target to '0' to avoid MySQL error
    $daily_target = mysqli_real_escape_string($conn, $_POST['daily_target']);
    if (empty($daily_target)) {
        $daily_target = '0'; 
    }
    
    $open_time = mysqli_real_escape_string($conn, $_POST['open_time']);
    $close_time = mysqli_real_escape_string($conn, $_POST['close_time']);

    $status = isset($_POST['status']) ? 1 : 0;
    $allow_manual_price = isset($_POST['allow_manual_price']) ? 1 : 0;
    $allow_backdate = isset($_POST['allow_backdate']) ? 1 : 0;

    // Check Duplicate Code (Exclude Current ID)
    $check = mysqli_query($conn, "SELECT id FROM stores WHERE store_code='$store_code' AND id != '$store_id'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['message'] = "Store Code already exists in another branch!";
        $_SESSION['msg_type'] = "error";
        header("Location: add_store.php?id=$store_id"); // Stay on edit page
        exit(0);
    }

    $query = "UPDATE stores SET 
                store_name='$store_name', store_code='$store_code', business_type='$business_type', 
                email='$email', phone='$phone', address='$address', city_zip='$city_zip', 
                vat_number='$vat_number', timezone='$timezone', max_line_disc='$max_line_disc', 
                max_inv_disc='$max_inv_disc', approval_disc='$approval_disc', overselling='$overselling', 
                low_stock='$low_stock', status='$status', daily_target='$daily_target', 
                open_time='$open_time', close_time='$close_time', allow_manual_price='$allow_manual_price', 
                allow_backdate='$allow_backdate' 
              WHERE id='$store_id'";

    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Store Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: store_list.php"); 
    } else {
        $_SESSION['message'] = "Update Failed: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: add_store.php?id=$store_id"); 
    }
    exit(0);
}

// --- 3. DELETE STORE ---
if(isset($_POST['delete_store_btn']))
{
    $store_id = mysqli_real_escape_string($conn, $_POST['delete_id']);

    $query = "DELETE FROM stores WHERE id='$store_id'";
    $query_run = mysqli_query($conn, $query);

    if($query_run) {
        $_SESSION['message'] = "Store Deleted Successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Deletion Failed: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: store_list.php");
    exit(0);
}
?>
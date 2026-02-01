<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

if(isset($_POST['delete_btn'])) {
    $bank_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    // 1. Delete Mappings
    $delete_maps = "DELETE FROM bank_account_to_store WHERE account_id='$bank_id'";
    mysqli_query($conn, $delete_maps);
    
    // 2. Delete Account
    $query = "DELETE FROM bank_accounts WHERE id='$bank_id'";
    $query_run = mysqli_query($conn, $query);
    
    if($query_run) {
        $_SESSION['status'] = "Bank Account Deleted Successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Deletion Failed: " . mysqli_error($conn);
        $_SESSION['status_code'] = "error";
    }
} else {
    // If accessed directly without post
    $_SESSION['status'] = "Invalid Request";
    $_SESSION['status_code'] = "error";
}

header("Location: /pos/accounting/bank/list");
exit(0);
?>

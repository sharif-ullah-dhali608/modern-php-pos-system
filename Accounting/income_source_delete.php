<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

if(isset($_POST['delete_btn'])) {
    $id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    // Check dependencies if needed (e.g., transactions using this source)
    // For now, straight delete or soft delete. User schema has is_hide, so maybe soft delete?
    // User requested standard delete functionality in list view which typically maps to DELETE or Status update.
    // Standard list component uses this file for physical delete usually.
    
    $query = "DELETE FROM income_sources WHERE source_id='$id' ";
    $query_run = mysqli_query($conn, $query);

    if($query_run) {
        $_SESSION['status'] = "Income Source Deleted Successfully";
        $_SESSION['status_code'] = "success";
    } else {
        $_SESSION['status'] = "Deletion Failed: " . mysqli_error($conn);
        $_SESSION['status_code'] = "error";
    }
    
    header("Location: /pos/accounting/income-source/list");
    exit(0);
}
?>

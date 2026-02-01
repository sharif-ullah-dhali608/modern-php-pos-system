<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

if(isset($_POST['toggle_status_btn']))
{
    $id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $query = "UPDATE bank_accounts SET status='$status' WHERE id='$id'";
    $query_run = mysqli_query($conn, $query);

    if($query_run)
    {
        echo json_encode(['status' => 'success', 'message' => 'Status updated successfully!']);
    }
    else
    {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status: ' . mysqli_error($conn)]);
    }
    exit(0);
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit(0);
?>

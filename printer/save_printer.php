<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

if (isset($_POST['save_printer_btn']) || isset($_POST['update_printer_btn'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $profile = mysqli_real_escape_string($conn, $_POST['profile']);
    $char_per_line = mysqli_real_escape_string($conn, $_POST['char_per_line']);
    $ip_address = mysqli_real_escape_string($conn, $_POST['ip_address']);
    $port = mysqli_real_escape_string($conn, $_POST['port']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = mysqli_real_escape_string($conn, $_POST['sort_order']);
    
    // Support both 'stores' (from component) and 'store_ids' (old way)
    $store_ids = [];
    if(isset($_POST['stores'])) {
        $store_ids = $_POST['stores'];
    } elseif(isset($_POST['store_ids'])) {
        $store_ids = $_POST['store_ids'];
    }

    mysqli_begin_transaction($conn);

    try {
        if (isset($_POST['save_printer_btn'])) {
            $query = "INSERT INTO printers (title, type, profile, char_per_line, ip_address, port, status, sort_order) 
                      VALUES ('$title', '$type', '$profile', '$char_per_line', '$ip_address', '$port', '$status', '$sort_order')";
            mysqli_query($conn, $query);
            $printer_id = mysqli_insert_id($conn);
            $msg = "Printer added successfully!";
        } else {
            $printer_id = mysqli_real_escape_string($conn, $_POST['printer_id']);
            $query = "UPDATE printers SET title='$title', type='$type', profile='$profile', char_per_line='$char_per_line', 
                      ip_address='$ip_address', port='$port', status='$status', sort_order='$sort_order' 
                      WHERE printer_id='$printer_id'";
            mysqli_query($conn, $query);
            
            // Clear existing mapping
            mysqli_query($conn, "DELETE FROM printer_store_map WHERE printer_id = '$printer_id'");
            $msg = "Printer updated successfully!";
        }

        // Insert new mapping
        foreach ($store_ids as $store_id) {
            $store_id = mysqli_real_escape_string($conn, $store_id);
            mysqli_query($conn, "INSERT INTO printer_store_map (printer_id, store_id) VALUES ('$printer_id', '$store_id')");
        }

        mysqli_commit($conn);
        $_SESSION['message'] = $msg;
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/printer/list");
        exit(0);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['message'] = "Something went wrong: " . $e->getMessage();
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/printer/add");
        exit(0);
    }
} 
// Standard AJAX / Reusable List Delete (Velocity POS Standard)
else if (isset($_POST['delete_id']) && isset($_POST['delete_btn'])) {
    $id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    $query = "DELETE FROM printers WHERE printer_id = '$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Printer deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to delete printer!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/printer/list");
    exit(0);
}
// Legacy/Direct AJAX Delete (Fallback)
else if (isset($_POST['id']) && isset($_POST['action']) && $_POST['action'] == 'delete_printer') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $query = "DELETE FROM printers WHERE printer_id = '$id'";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 200, 'message' => 'Printer deleted successfully']);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Failed to delete printer']);
    }
    exit(0);
}
// Standard Reusable List Status Toggle
else if (isset($_POST['item_id']) && isset($_POST['toggle_status_btn'])) {
    $id = mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE printers SET status='$status' WHERE printer_id='$id'";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update status!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/printer/list");
    exit(0);
}

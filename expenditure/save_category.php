<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){ exit(0); }

function createSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return $string;
}

// 1. SAVE NEW CATEGORY
if(isset($_POST['save_category_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $details = mysqli_real_escape_string($conn, $_POST['category_details']);
    $status = isset($_POST['status']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $slug = createSlug($name);

    // Check if slug exists
    $check_slug = mysqli_query($conn, "SELECT category_id FROM expense_category WHERE category_slug='$slug' LIMIT 1");
    if(mysqli_num_rows($check_slug) > 0) {
        $slug = $slug . '-' . time();
    }

    if($name != "") {
        $query = "INSERT INTO expense_category (category_name, category_slug, category_details, status, sort_order) 
                  VALUES ('$name', '$slug', '$details', '$status', '$sort_order')";
        
        if(mysqli_query($conn, $query)) {
            $_SESSION['message'] = "Category Created Successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: /pos/expenditure/category_list");
        } else {
            $_SESSION['message'] = "Error: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
            header("Location: /pos/expenditure/category_add");
        }
    } else {
        $_SESSION['message'] = "Name is required!";
        header("Location: /pos/expenditure/category_add");
    }
    exit(0);
}

// 2. UPDATE CATEGORY
if(isset($_POST['update_category_btn'])) {
    $id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $details = mysqli_real_escape_string($conn, $_POST['category_details']);
    $status = isset($_POST['status']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    $update_query = "UPDATE expense_category SET 
                        category_name='$name', 
                        category_details='$details', 
                        status='$status', 
                        sort_order='$sort_order' 
                    WHERE category_id='$id'";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Category Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/expenditure/category_list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/expenditure/category_edit/$id");
    }
    exit(0);
}

// 3. DELETE CATEGORY
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    
    $query = "DELETE FROM expense_category WHERE category_id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Category deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/expenditure/category_list");
    exit(0);
}

// 4. TOGGLE STATUS
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    
    $query = "UPDATE expense_category SET status='$status' WHERE category_id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Status updated!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/expenditure/category_list");
    exit(0);
}

header("Location: /pos/expenditure/category_list");
exit(0);
?>
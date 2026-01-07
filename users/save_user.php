<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){ exit(0); }

function uploadUserImage($file) {
    $targetDir = "../uploads/users/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = uniqid() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    if(move_uploaded_file($file["tmp_name"], $targetFilePath)) return "/pos/uploads/users/" . $fileName;
    return null;
}

// 1. SAVE NEW USER
if(isset($_POST['save_user_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $group_id = (int)$_POST['group_id'];
    $dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? ''); 
    $sex_val = $_POST['sex'] ?? '';
    $sex = ($sex_val == 'Male') ? 'M' : (($sex_val == 'Female') ? 'F' : (($sex_val == 'Other') ? 'O' : 'M'));
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $country = mysqli_real_escape_string($conn, $_POST['country'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $store_ids = $_POST['stores'] ?? $_POST['store_ids'] ?? [];

    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
    
    if(mysqli_num_rows($check_email) > 0) {
        $_SESSION['message'] = "Email already exists! Please use a different email.";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/users/add"); 
        exit(0);
    }
    
    $image = "";
    if(!empty($_FILES['user_image']['name'])) $image = uploadUserImage($_FILES['user_image']);

    if($name != "" && $email != "") {
        $query = "INSERT INTO users (name, email, phone, password, group_id, dob, sex, address, city, state, country, status, sort_order, user_image) 
                  VALUES ('$name', '$email', '$phone', '$password', '$group_id', '$dob', '$sex', '$address', '$city', '$state', '$country', '$status', '$sort_order', '$image')";
        
        if(mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            foreach($store_ids as $sid) {
                mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) VALUES ('$user_id', '$sid')");
            }
            $_SESSION['message'] = "User Created Successfully!";
            $_SESSION['msg_type'] = "success";
            header("Location: /pos/users/list");
        } else {
            die("DB Error: " . mysqli_error($conn));
        }
    } else {
        $_SESSION['message'] = "Please fill all required fields!";
        header("Location: /pos/users/add");
    }
}

// 2. UPDATE USER
if(isset($_POST['update_user_btn'])) {
    $user_id = (int)$_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $group_id = (int)$_POST['group_id'];
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $sex_val = $_POST['sex'] ?? '';
    $sex = ($sex_val == 'Male') ? 'M' : (($sex_val == 'Female') ? 'F' : (($sex_val == 'Other') ? 'O' : 'M'));
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($conn, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($conn, $_POST['state'] ?? '');
    $country = mysqli_real_escape_string($conn, $_POST['country'] ?? '');
    $status = (int)($_POST['status'] ?? 1);
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    
    $image = $_POST['old_image'];
    if(!empty($_FILES['user_image']['name'])) $image = uploadUserImage($_FILES['user_image']);

    $update_query = "UPDATE users SET 
                        name='$name', 
                        email='$email', 
                        phone='$phone', 
                        group_id='$group_id', 
                        dob='$dob', 
                        sex='$sex',
                        address='$address',
                        city='$city',
                        state='$state',
                        country='$country',
                        status='$status', 
                        sort_order='$sort_order', 
                        user_image='$image' ";
    
    if(!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_query .= ", password='$new_pass' ";
    }
    
    $update_query .= " WHERE id='$user_id'";
    
    if(mysqli_query($conn, $update_query)) {
        mysqli_query($conn, "DELETE FROM user_store_map WHERE user_id='$user_id'");
        $store_ids = $_POST['stores'] ?? $_POST['store_ids'] ?? [];
        foreach($store_ids as $sid) {
            mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) VALUES ('$user_id', '$sid')");
        }
        $_SESSION['message'] = "User Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/users/list");
        exit(0);
    }
}

// 3. TOGGLE USER STATUS
if(isset($_POST['toggle_status_btn'])) {
    $user_id = (int)$_POST['item_id'];
    $status = (int)$_POST['status'];
    
    $query = "UPDATE users SET status='$status' WHERE id='$user_id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "User status updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating status: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/users/list");
    exit(0);
}

// 4. DELETE USER
if(isset($_POST['delete_btn'])) {
    $user_id = (int)$_POST['delete_id'];
    
    $query = "DELETE FROM users WHERE id='$user_id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "User deleted successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting user: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/users/list");
    exit(0);
}

// Catch-all redirect
header("Location: /pos/users/list");
exit(0);
?>
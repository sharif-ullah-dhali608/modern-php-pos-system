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
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $group_id = (int)$_POST['group_id'];
    $dob = mysqli_real_escape_string($conn, $_POST['dob'] ?? ''); 
    $store_ids = $_POST['store_ids'] ?? [];

    $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
    
    if(mysqli_num_rows($check_email) > 0) {
       
        $_SESSION['message'] = "Email already exists! Please use a different email.";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/users/add"); 
        exit(0);
    }
    $image = "";
    if(!empty($_FILES['user_image']['name'])) $image = uploadUserImage($_FILES['user_image']);

    // Final Validation check for DB error
    if($username != "" && $email != "" && $dob != "") {
        $query = "INSERT INTO users (username, email, mobile, password, group_id, dob, user_image) 
                  VALUES ('$username', '$email', '$mobile', '$password', '$group_id', '$dob', '$image')";
        
        if(mysqli_query($conn, $query)) {
            $user_id = mysqli_insert_id($conn);
            foreach($store_ids as $sid) {
                mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) VALUES ('$user_id', '$sid')");
            }
            $_SESSION['message'] = "User Created Successfully!";
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
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $group_id = (int)$_POST['group_id'];
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    
    $image = $_POST['old_image'];
    if(!empty($_FILES['user_image']['name'])) $image = uploadUserImage($_FILES['user_image']);

    $update_query = "UPDATE users SET username='$username', email='$email', mobile='$mobile', group_id='$group_id', dob='$dob', user_image='$image' ";
    
    if(!empty($_POST['password'])) {
        $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_query .= ", password='$new_pass' ";
    }
    
    $update_query .= " WHERE id='$user_id'";
    
    if(mysqli_query($conn, $update_query)) {
        mysqli_query($conn, "DELETE FROM user_store_map WHERE user_id='$user_id'");
        foreach(($_POST['store_ids'] ?? []) as $sid) {
            mysqli_query($conn, "INSERT INTO user_store_map (user_id, store_id) VALUES ('$user_id', '$sid')");
        }
        $_SESSION['message'] = "User Updated Successfully!";
        header("Location: /pos/users/list");
    }
}
?>
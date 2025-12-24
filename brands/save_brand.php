<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

function uploadImage($file) {
    $targetDir = "../uploads/brands/";
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Allow certain file formats
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return "/pos/uploads/brands/" . $fileName;
        }
    }
    return null;
}

// --- CREATE BRAND ---
if(isset($_POST['save_brand_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    
    $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : [];

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM brands WHERE code='$code'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Brand Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
        exit(0);
    }

    $thumbnail_path = "";
    if(isset($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['name'] != "") {
        $uploadedParams = uploadImage($_FILES['thumbnail']);
        if($uploadedParams) {
            $thumbnail_path = $uploadedParams;
        }
    }

    $query = "INSERT INTO brands (name, code, thumbnail, details, status, sort_order) VALUES ('$name', '$code', '$thumbnail_path', '$details', '$status', '$sort_order')";
    
    if(mysqli_query($conn, $query)) {
        $brand_id = mysqli_insert_id($conn);

        if(!empty($store_ids)) {
            foreach($store_ids as $store_id) {
                $store_id = mysqli_real_escape_string($conn, $store_id);
                $store_query = "INSERT INTO brand_store (brand_id, store_id) VALUES ('$brand_id', '$store_id')";
                mysqli_query($conn, $store_query);
            }
        }

        $_SESSION['message'] = "Brand created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/brands/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add");
    }
    exit(0);
}

if(isset($_POST['update_brand_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['brand_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $code = strtoupper(mysqli_real_escape_string($conn, $_POST['code']));
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    $sort_order = (int)mysqli_real_escape_string($conn, $_POST['sort_order']);
    $old_thumbnail = $_POST['old_thumbnail'];

    $store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : [];

    if(empty($name) || empty($code)) {
        $_SESSION['message'] = "Name and code are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add?id=$id");
        exit(0);
    }

    $dup = mysqli_query($conn, "SELECT id FROM brands WHERE code='$code' AND id!='$id'");
    if(mysqli_num_rows($dup) > 0){
        $_SESSION['message'] = "Brand Code already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add?id=$id");
        exit(0);
    }

    $thumbnail_path = $old_thumbnail;
    if(isset($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['name'] != "") {
        $uploadedParams = uploadImage($_FILES['thumbnail']);
        if($uploadedParams) {
            $thumbnail_path = $uploadedParams;
            $old_file_path = ".." . str_replace("/pos", "", $old_thumbnail);
            if(file_exists($old_file_path) && !empty($old_thumbnail)){
                unlink($old_file_path);
            }
        }
    }

    // Update Brand Data
    $query = "UPDATE brands SET name='$name', code='$code', thumbnail='$thumbnail_path', details='$details', status='$status', sort_order='$sort_order' WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
     
        mysqli_query($conn, "DELETE FROM brand_store WHERE brand_id='$id'");

        if(!empty($store_ids)) {
            foreach($store_ids as $store_id) {
                $store_id = mysqli_real_escape_string($conn, $store_id);
                $store_query = "INSERT INTO brand_store (brand_id, store_id) VALUES ('$id', '$store_id')";
                mysqli_query($conn, $store_query);
            }
        }

        $_SESSION['message'] = "Brand updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/brands/list");
    } else {
        $_SESSION['message'] = "Error: ".mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/brands/add?id=$id");
    }
    exit(0);
}

if(isset($_POST['delete_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    $img_q = mysqli_query($conn, "SELECT thumbnail FROM brands WHERE id='$id'");
    if($row = mysqli_fetch_assoc($img_q)){
        $file_path = ".." . str_replace("/pos", "", $row['thumbnail']);
        if(file_exists($file_path) && !empty($row['thumbnail'])){
            unlink($file_path);
        }
    }

    mysqli_query($conn, "DELETE FROM brand_store WHERE brand_id='$id'");
    
    if(mysqli_query($conn, "DELETE FROM brands WHERE id='$id'")){
        $_SESSION['message'] = "Brand deleted!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Something went wrong!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/brands/list");
    exit(0);
}

if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    
    if(mysqli_query($conn, "UPDATE brands SET status='$status' WHERE id='$id'")){
        $_SESSION['message'] = "Status updated!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update status.";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/brands/list");
    exit(0);
}

header("Location: /pos/brands/list");
exit(0);
?>
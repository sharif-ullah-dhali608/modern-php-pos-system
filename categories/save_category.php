<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin");
    exit(0);
}

/**
 * Image Upload Helper function specifically for categories
 */
function uploadImage($file) {
    $targetDir = "../uploads/categories/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . '_' . basename($file["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'webp');
    if(in_array(strtolower($fileType), $allowTypes)){
        if(move_uploaded_file($file["tmp_name"], $targetFilePath)){
            return "/pos/uploads/categories/" . $fileName;
        }
    }
    return null;
}

// --- CREATE CATEGORY SECTION ---
if(isset($_POST['save_category_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_code = strtoupper(mysqli_real_escape_string($conn, $_POST['category_code']));
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $parent_id = (int)$_POST['parent_id'];
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)$_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    $visibility_pos = isset($_POST['visibility_pos']) ? 1 : 0;
    
    // Using 'stores' key from your store_select_component
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Validation
    if(empty($name) || empty($category_code) || empty($stores)) {
        $_SESSION['message'] = "Name, Code, and at least one Store are required!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/add");
        exit(0);
    }

    // Check for Duplicate Code or Slug
    $check_dup = mysqli_query($conn, "SELECT id FROM categories WHERE category_code='$category_code' OR slug='$slug' LIMIT 1");
    if(mysqli_num_rows($check_dup) > 0) {
        $_SESSION['message'] = "Category Code or Slug already exists!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/add");
        exit(0);
    }

    $thumbnail_path = "";
    if(!empty($_FILES['thumbnail']['name'])) {
        $thumbnail_path = uploadImage($_FILES['thumbnail']);
    }

    $query = "INSERT INTO categories (name, category_code, slug, parent_id, thumbnail, details, status, sort_order, visibility_pos) 
              VALUES ('$name', '$category_code', '$slug', '$parent_id', '$thumbnail_path', '$details', '$status', '$sort_order', '$visibility_pos')";
    
    if(mysqli_query($conn, $query)) {
        $category_id = mysqli_insert_id($conn);
        // Pivot table mapping
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO category_store_map (category_id, store_id) VALUES ('$category_id', '$store_id')");
        }
        $_SESSION['message'] = "Category created successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/categories/list");
    } else {
        $_SESSION['message'] = "Something went wrong!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/add");
    }
    exit(0);
}

// --- UPDATE CATEGORY SECTION ---
if(isset($_POST['update_category_btn'])) {
    $id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_code = strtoupper(mysqli_real_escape_string($conn, $_POST['category_code']));
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    $parent_id = (int)$_POST['parent_id'];
    $details = mysqli_real_escape_string($conn, $_POST['details']);
    $status = (int)$_POST['status'];
    $sort_order = (int)$_POST['sort_order'];
    $visibility_pos = isset($_POST['visibility_pos']) ? 1 : 0;
    $old_thumbnail = $_POST['old_thumbnail'];
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];

    // Duplicate check excluding current ID
    $check_dup = mysqli_query($conn, "SELECT id FROM categories WHERE (category_code='$category_code' OR slug='$slug') AND id != '$id' LIMIT 1");
    if(mysqli_num_rows($check_dup) > 0) {
        $_SESSION['message'] = "Code or Slug used by another category!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/edit/$id"); 
        exit(0);
    }

    $thumbnail_path = $old_thumbnail;
    if(!empty($_FILES['thumbnail']['name'])) {
        $new_path = uploadImage($_FILES['thumbnail']);
        if($new_path) {
            $thumbnail_path = $new_path;
            // Clean up old file
            $old_file_path = ".." . str_replace("/pos", "", $old_thumbnail);
            if(file_exists($old_file_path) && !empty($old_thumbnail)) @unlink($old_file_path);
        }
    }

    $query = "UPDATE categories SET name='$name', category_code='$category_code', slug='$slug', parent_id='$parent_id', 
              thumbnail='$thumbnail_path', details='$details', status='$status', sort_order='$sort_order', visibility_pos='$visibility_pos' 
              WHERE id='$id'";
    
    if(mysqli_query($conn, $query)) {
        // Refresh store mappings
        mysqli_query($conn, "DELETE FROM category_store_map WHERE category_id='$id'");
        foreach($stores as $store_id) {
            $store_id = (int)$store_id;
            mysqli_query($conn, "INSERT INTO category_store_map (category_id, store_id) VALUES ('$id', '$store_id')");
        }
        $_SESSION['message'] = "Category updated successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/categories/list");
    } else {
        $_SESSION['message'] = "Update failed!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/categories/edit/$id");
    }
    exit(0);
}

// --- DELETE SECTION ---
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    
    // Delete physical thumbnail
    $img_q = mysqli_query($conn, "SELECT thumbnail FROM categories WHERE id='$id'");
    if($row = mysqli_fetch_assoc($img_q)){
        $file_path = ".." . str_replace("/pos", "", $row['thumbnail']);
        if(file_exists($file_path) && !empty($row['thumbnail'])) @unlink($file_path);
    }

    // Clear mappings and data
    mysqli_query($conn, "DELETE FROM category_store_map WHERE category_id='$id'");
    mysqli_query($conn, "DELETE FROM categories WHERE id='$id'");
    
    $_SESSION['message'] = "Category and its mappings deleted!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/categories/list");
    exit(0);
}

// --- TOGGLE STATUS SECTION ---
if(isset($_POST['toggle_status_btn'])) {
    $id = (int)mysqli_real_escape_string($conn, $_POST['item_id']);
    $status = (int)mysqli_real_escape_string($conn, $_POST['status']);
    
    mysqli_query($conn, "UPDATE categories SET status='$status' WHERE id='$id'");
    $_SESSION['message'] = "Category status updated!";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/categories/list");
    exit(0);
}

header("Location: /pos/categories/list");
exit(0);
?>
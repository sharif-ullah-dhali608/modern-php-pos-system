<?php
include('../config/dbcon.php');

if(isset($_POST['update_setting'])) {
    $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 1; 
    // Fallback ID or get form session if available
    
    $key = mysqli_real_escape_string($conn, $_POST['key']);
    $value = mysqli_real_escape_string($conn, $_POST['value']);
    
    // Check if exists
    $check = mysqli_query($conn, "SELECT id FROM pos_settings WHERE store_id = '$store_id' AND setting_key = '$key'");
    
    if(mysqli_num_rows($check) > 0) {
        $sql = "UPDATE pos_settings SET setting_value = '$value' WHERE store_id = '$store_id' AND setting_key = '$key'";
    } else {
        $sql = "INSERT INTO pos_settings (store_id, setting_key, setting_value) VALUES ('$store_id', '$key', '$value')";
    }
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    exit;
}

if(isset($_POST['store_id'])) {
    $store_id = intval($_POST['store_id']);

    // --- Update Store Currency if set ---
    if(isset($_POST['currency_id'])) {
        $currency_id = intval($_POST['currency_id']);
        mysqli_query($conn, "UPDATE stores SET currency_id = '$currency_id' WHERE id = '$store_id'");
    }
    // ------------------------------------

    // --- Handle Logo Upload ---
    if(isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
        $upload_dir = '../uploads/branding/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        
        if(in_array($file_ext, $allowed_exts) && $_FILES['logo_file']['size'] <= 5242880) { // Increased to 5MB
            $new_filename = 'logo_' . $store_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['logo_file']['tmp_name'], $upload_path)) {
                $logo_path = '/pos/uploads/branding/' . $new_filename;
                $_POST['settings']['logo'] = $logo_path;
            } else {
                 echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded logo file. Check permissions.']);
                 exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid logo file. Max 5MB, types: png, jpg, svg, webp.']);
            exit;
        }
    }

    // --- Handle Favicon Upload ---
    if(isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] == 0) {
        $upload_dir = '../uploads/branding/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['favicon_file']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['ico', 'png', 'jpg', 'jpeg'];
        
        if(in_array($file_ext, $allowed_exts) && $_FILES['favicon_file']['size'] <= 2097152) { // Increased to 2MB
            $new_filename = 'favicon_' . $store_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['favicon_file']['tmp_name'], $upload_path)) {
                $favicon_path = '/pos/uploads/branding/' . $new_filename;
                $_POST['settings']['favicon'] = $favicon_path;
            }
        }
    }

    if(isset($_POST['settings'])) {
    $store_id = intval($_POST['store_id']);
    $settings = $_POST['settings'];
    
    foreach($settings as $key => $value) {
        $key = mysqli_real_escape_string($conn, $key);
        $value = mysqli_real_escape_string($conn, $value);
        
        // Check if exists
        $check = mysqli_query($conn, "SELECT id FROM pos_settings WHERE store_id = '$store_id' AND setting_key = '$key'");
        
        if(mysqli_num_rows($check) > 0) {
            $sql = "UPDATE pos_settings SET setting_value = '$value' WHERE store_id = '$store_id' AND setting_key = '$key'";
        } else {
            $sql = "INSERT INTO pos_settings (store_id, setting_key, setting_value) VALUES ('$store_id', '$key', '$value')";
        }
        mysqli_query($conn, $sql);
    }
    
    // Handle unchecked checkboxes (if not in POST, set to 0)
    $checkboxes = ['enable_sound', 'auto_print', 'show_images', 'allow_price_edit'];
    foreach($checkboxes as $cb) {
        if(!isset($settings[$cb])) {
            // If checkbox was unchecked, it won't be in $_POST['settings'][$cb]
            // We need to set it to 0 explicitly
             $check = mysqli_query($conn, "SELECT id FROM pos_settings WHERE store_id = '$store_id' AND setting_key = '$cb'");
             if(mysqli_num_rows($check) > 0) {
                 mysqli_query($conn, "UPDATE pos_settings SET setting_value = '0' WHERE store_id = '$store_id' AND setting_key = '$cb'");
             } else {
                 mysqli_query($conn, "INSERT INTO pos_settings (store_id, setting_key, setting_value) VALUES ('$store_id', '$cb', '0')");
             }
        }
    }
    
    echo "success";
}
}
?>
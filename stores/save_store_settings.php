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

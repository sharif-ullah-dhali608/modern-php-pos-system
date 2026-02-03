<?php
session_start();

if (!isset($_SESSION['auth'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if (isset($_POST['store_id'])) {
    $new_store_id = intval($_POST['store_id']);
    
    // Security Check: Is this store assigned to the user?
    // Admins can switch to any store if we treat their assigned_stores as 'all' or if we bypass check
    // For now, strict check against session assigned_stores
    
    $allowed_stores = isset($_SESSION['assigned_stores']) ? $_SESSION['assigned_stores'] : [];
    
    // If Admin, and allowed_stores has 1 but they might want any, skipping strict check might be desired IF we load ALL stores for admin.
    // However, logic in auth_user.php sets assigned_stores.
    // IF admin has fallback [1], they can only see 1 unless we mapped all.
    // Ideally, for Admin, we should allow switching if we trust they are admin.
    
    $is_admin = isset($_SESSION['auth_user']['role_as']) && $_SESSION['auth_user']['role_as'] == 'admin';
    
    if (in_array($new_store_id, $allowed_stores) || $is_admin) {
        $_SESSION['store_id'] = $new_store_id;
        $_SESSION['message'] = "Switched to Store ID: $new_store_id";
        $_SESSION['msg_type'] = "success";
        unset($_SESSION['must_select_store']); // Clear the forced selection flag
    } else {
        $_SESSION['message'] = "Access to Store ID $new_store_id Denied";
        $_SESSION['msg_type'] = "error";
    }
}

// Redirect back to whence they came
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/pos/index.php';
header("Location: $redirect");
exit(0);

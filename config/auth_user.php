<?php
ob_start(); 
session_start();

include('dbcon.php');

// --------------------------------------------------------
// NEW AJAX ENTRY POINT FOR CAPTCHA VERIFICATION (Frontend Call)
// --------------------------------------------------------
if(isset($_POST['action_type']) && $_POST['action_type'] == 'captcha_verify')
{
    header('Content-Type: application/json');

    $user_captcha = isset($_POST['captcha_code']) ? $_POST['captcha_code'] : "";
    $server_captcha = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : "";

    if($user_captcha == $server_captcha && !empty($user_captcha)) {
        // CAPTCHA correct, return success to frontend
        echo json_encode(['status' => 'success']);
    } else {
        // CAPTCHA incorrect, return failure to frontend
        echo json_encode(['status' => 'error', 'message' => 'Invalid captcha code.']);
    }
    // EXIT immediately after AJAX response
    exit(); 
}

// --------------------------------------------------------
// ORIGINAL FORM SUBMISSION (Only proceeds if CAPTCHA was already verified by AJAX)
// --------------------------------------------------------
if(isset($_POST['login_btn']))
{
    // 1. Get Inputs & Sanitize
    $user_captcha = mysqli_real_escape_string($conn, $_POST['captcha_code']);
    $server_captcha = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : "";
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 2. CAPTCHA CHECK (Minimal check, since JS did the main validation)
    // If the hidden field is empty (shouldn't happen)
    if(empty($user_captcha) || $user_captcha != $server_captcha)
    {
        // Should not hit here if JS logic is correct, but kept as a server-side fail-safe
        $_SESSION['captcha_error'] = "Captcha code validation failed on the server. Please try again.";
        $_SESSION['input_email'] = $email;
        $_SESSION['input_password'] = $password; // Retain password on server error
        
        header("Location: /pos/login");
        exit(0);
    }
    
    // 3. CHECK EMAIL & PASSWORD
    $query = "SELECT u.*, g.slug as group_slug FROM users u 
              LEFT JOIN user_groups g ON u.group_id = g.id 
              WHERE u.email='$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

      if(mysqli_num_rows($result) > 0)
    {
        $data = mysqli_fetch_array($result);

        // --- PASSWORD CHECK ---
        // Support both hashed passwords and plain text for backward compatibility (optional but recommended during transition)
        $is_password_valid = false;
        if (password_verify($password, $data['password'])) {
            $is_password_valid = true;
        } elseif ($password === $data['password']) {
            // If it matches as plain text, we should probably re-hash it, but for now just allow login
            $is_password_valid = true;
        }

        if($is_password_valid) 
        {
            // --- LOGIN SUCCESS ---
            $_SESSION['auth'] = true;
            
            // Determine role based on group slug (preserve group name for accurate display)
            $group_slug = isset($data['group_slug']) ? strtolower(trim($data['group_slug'])) : '';
            if ($group_slug === '') {
                $role = 'user';
            } else {
                $role = $group_slug; // e.g., admin, salesman, cashier, etc.
            }

            // Store user info in session including image so navbar can show avatar
            $_SESSION['auth_user'] = [
                'user_id' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'role_as' => $role,
                'image' => isset($data['user_image']) ? $data['user_image'] : ''
            ];

            $_SESSION['message'] = "Welcome Dashboard"; 
            $_SESSION['msg_type'] = "success";

            unset($_SESSION['input_email']);
            unset($_SESSION['input_password']);
            unset($_SESSION['error_field']);

            header("Location: /pos");
            exit(0);
        }
        else
        {
            // --- WRONG PASSWORD ---
            $_SESSION['message'] = "Invalid Email & Password!";
            $_SESSION['input_email'] = $email;    
            $_SESSION['input_password'] = $password;
            $_SESSION['error_field'] = "password"; 
            
            header("Location: /pos/login");
            exit(0);
        }
    }

    else
    {
        // --- WRONG EMAIL ---
        $_SESSION['message'] = "Invalid Login Credentials!";
        $_SESSION['input_email'] = $email;    
        $_SESSION['input_password'] = $password;
        $_SESSION['error_field'] = "email";   
        
        header("Location: /pos/login");
        exit(0);
    }
}
else
{
    $_SESSION['message'] = "You are not allowed to access this file";
    header("Location: /pos/login");
    exit(0);
}

ob_end_flush();
?>

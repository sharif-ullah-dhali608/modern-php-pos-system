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
    
    // 3. CHECK EMAIL (Password retain logic added for failure scenarios)
    $check_email_query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($conn, $check_email_query);

    if(mysqli_num_rows($check_email_run) > 0)
    {
        $data = mysqli_fetch_array($check_email_run);

        // NOTE: Comparing plain text passwords 
        if($password == $data['password']) 
        {
            // --- LOGIN SUCCESS ---
            $_SESSION['auth'] = true;
            $_SESSION['auth_user'] = [
                'user_id' => $data['id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'role_as' => $data['role_as']
            ];

            $_SESSION['message'] = "Welcome Dashboard"; 
            $_SESSION['msg_type'] = "success";

            
            unset($_SESSION['input_email']);
            unset($_SESSION['input_password']); // Clear retained password
            unset($_SESSION['error_field']);

            header("Location: /pos");
            exit(0);
        }
        else
        {
            // --- WRONG PASSWORD ---
            $_SESSION['message'] = "Invalid Email & Password!";
            $_SESSION['input_email'] = $email;    
            $_SESSION['input_password'] = $password; // RETAIN PASSWORD
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
        $_SESSION['input_password'] = $password; // RETAIN PASSWORD
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
<?php
ob_start(); 
session_start();

include('dbcon.php');

if(isset($_POST['login_btn']))
{
    // 1. Get Inputs & Sanitize
    $user_captcha = mysqli_real_escape_string($conn, $_POST['captcha_code']);
    $server_captcha = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : "";
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 2. CAPTCHA CHECK
    if($user_captcha != $server_captcha)
    {
        $_SESSION['message'] = "Incorrect Captcha Code!";
        $_SESSION['input_email'] = $email; // Save email so user doesn't type it again
        // No specific error field for captcha, or you could set 'captcha' if you had logic for it
        header("Location: ../signIn.php");
        exit(0);
    }

    // 3. CHECK EMAIL FIRST (To see if email is the "Wrong Field")
    $check_email_query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $check_email_run = mysqli_query($conn, $check_email_query);

    if(mysqli_num_rows($check_email_run) > 0)
    {
        // Email Exists -> Now Check Password
        $data = mysqli_fetch_array($check_email_run);

        // NOTE: Comparing plain text passwords as per your code. 
        // Ideally, use password_verify($password, $data['password']) if using hashes.
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
            
            // Clear any error sessions
            unset($_SESSION['input_email']);
            unset($_SESSION['error_field']);

            header("Location: ../index.php");
            exit(0);
        }
        else
        {
            // --- WRONG PASSWORD ---
            // Email was correct, but password failed.
            $_SESSION['message'] = "Invalid Email & Password!";
            $_SESSION['input_email'] = $email;    // Keep the email in the input
            $_SESSION['error_field'] = "password"; // Triggers Red Border on Password Field only
            
            header("Location: ../signIn.php");
            exit(0);
        }
    }
    else
    {
        // --- WRONG EMAIL ---
        // Email didn't exist in database.
        $_SESSION['message'] = "Email address not found!";
        $_SESSION['input_email'] = $email;    // Keep the typo so user can fix it
        $_SESSION['error_field'] = "email";   // Triggers Red Border on Email Field only
        
        header("Location: ../signIn.php");
        exit(0);
    }
}
else
{
    $_SESSION['message'] = "You are not allowed to access this file";
    header("Location: ../signIn.php");
    exit(0);
}

ob_end_flush();
?>
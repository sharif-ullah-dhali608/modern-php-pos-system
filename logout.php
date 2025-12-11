<?php
session_start();

if(isset($_SESSION['auth'])){
    unset($_SESSION['auth']);
    unset($_SESSION['auth_user']);
    

}

session_destroy();

header("Location: /pos/login");
exit(0);
?>
<?php
session_start();

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}
?>
<h1>Welcome to the Dashboard</h1>
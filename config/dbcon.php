<?php
$host = "localhost";
$username = "root";
$password = "root";
$database = "pos_system";


$conn = mysqli_connect($host, $username, $password, $database);

if(!$conn)
{
    die("Connection Failed: ". mysqli_connect_error());
}
?>
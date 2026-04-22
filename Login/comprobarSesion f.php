<?php
    session_start();
    if (!isset($_SESSION['user'])) {
        if(file_exists('login.php')){
            header('location: login.php');
        }else{
            header('location: Login/login.php');
        }
    }
?>

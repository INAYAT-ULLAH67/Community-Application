<?php
session_start();
include_once('config/db.php');

// 1. Checking! if user is already logged in
if (isset($_SESSION['student_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: frontened/admin_dash.php");
    } else {
        header("Location: frontened/student_dash.php");
    }
    exit();
}

// 2. Display Login Page
include('frontened/login.html');
?>
<?php
// 1. Enable Error Reporting to stop the 500 error and show the real problem
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('../config/db.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if session exists
    if (!isset($_SESSION['student_id'])) {
        die("Session error: User not logged in.");
    }

    $s_id = (int)$_SESSION['student_id'];
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // Basic Validation
    if ($new_pass !== $confirm_pass) {
        header("Location: ../frontened/my_profile.php?status=error&msg=match");
        exit();
    }

    // Fetch hash
    $query = "SELECT password FROM Students WHERE student_id = $s_id";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($old_pass, $user['password'])) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = "UPDATE Students SET password = '$new_hash' WHERE student_id = $s_id";
            
            if (mysqli_query($con, $update)) {
                header("Location: ../frontened/my_profile.php?status=success");
            } else {
                echo "Database Error: " . mysqli_error($con);
            }
        } else {
            header("Location: ../frontened/my_profile.php?status=error&msg=wrong_old");
        }
    } else {
        echo "User not found in database.";
    }
}
?>
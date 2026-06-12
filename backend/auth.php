<?php
// echo "Auth file reached!";
// die();
session_start();
include_once('../config/db.php'); // Step back to access config

if (isset($_POST['login_btn'])) {
    // 1. Capture and Sanitize the input
    // We use mysqli_real_escape_string for the username to prevent SQL injection
    $username = mysqli_real_escape_string($con, $_POST['username']);
    $password = $_POST['password']; // Raw password from user input(eg: dbinayat)
    // 2. Query the database for the username
    $query = "SELECT * FROM Students WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($con, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result); // data base result row 
        
        // 3. THE KEY: Check if the raw password matches the Hash in DB
        if (password_verify($password, $row['password'])) {
            
            // Success! Create the Session variables
            $_SESSION['student_id'] = $row['student_id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['dept'] = $row['dept_prefix'];
            $_SESSION['batch'] = $row['batch_year'];

            // 4. Redirect based on role
           if ($row['role'] == 'admin') {
    // Check this path carefully:
                header("Location: ../frontened/admin_dash.php"); 
            } else {
                header("Location: ../frontened/student_dash.php");
            }
            exit();

        } else {
            // Wrong password - Send back to index with an error message
            header("Location: ../index.php?error=Incorrect Password");
            exit();
        }
    } else {
        // User not found
        header("Location: ../index.php?error=User Not Found");
        exit();
    }
} else {
    // Redirect if they try to access this file directly via URL
    header("Location: ../index.php");
    exit();
}
?>
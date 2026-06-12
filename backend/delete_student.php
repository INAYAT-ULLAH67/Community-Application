<?php
// 1. Force error reporting ON for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//session---------> used to manage informatin across different pages
session_start();
include_once('../config/db.php');

// 2. Authorization Check
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access. You must be an admin.");
}

// 3. Verify GET variables exist
if (!isset($_GET['id']) || !isset($_GET['dept']) || !isset($_GET['batch'])) {
    die("Missing parameters. Required: id, dept, batch.");
}

// 4. Sanitize Inputs
$id    = (int)$_GET['id'];
$batch = (int)$_GET['batch'];
$dept  = mysqli_real_escape_string($con, $_GET['dept']);

// 5. Transaction Logic - Everything succeeds or everything fails
mysqli_begin_transaction($con);

try {
    // A. Delete from Payments (The table that was blocking the delete)
    if (!mysqli_query($con, "DELETE FROM Payments WHERE student_id=$id AND dept_prefix='$dept' AND batch_year=$batch")) {
        throw new Exception("Failed to delete from Payments: " . mysqli_error($con));
    }

    // B. Delete from Phone Directory
    if (!mysqli_query($con, "DELETE FROM Student_Phones WHERE student_id=$id AND dept_prefix='$dept' AND batch_year=$batch")) {
        throw new Exception("Failed to delete from Student_Phones: " . mysqli_error($con));
    }

    // C. Delete from Leadership roles
    if (!mysqli_query($con, "DELETE FROM Undergraduate_Leadership WHERE student_id=$id AND dept_prefix='$dept' AND batch_year=$batch")) {
        throw new Exception("Failed to delete from Leadership: " . mysqli_error($con));
    }

    // D. FINAL STEP: Delete the main student record
    // This will now work because all "Child" records are gone.
    $delete_student = mysqli_query($con, "DELETE FROM Students WHERE student_id=$id AND dept_prefix='$dept' AND batch_year=$batch");

    if ($delete_student) {
        mysqli_commit($con);
        // Success! Redirect back
        header("Location: ../frontened/admin_dash.php?msg=deleted");
        exit();
    } else {
        throw new Exception(mysqli_error($con));
    }

} catch (Exception $e) {
    // Rollback changes if ANY query fails so we don't have partial data loss
    mysqli_rollback($con);
    
    echo "<div style='color: red; font-family: sans-serif; padding: 20px; border: 1px solid red; border-radius: 8px; max-width: 600px; margin: 20px auto;'>";
    echo "<h2>🚫 Delete Operation Failed</h2>";
    echo "<p><strong>Error Detail:</strong> " . $e->getMessage() . "</p>";
    echo "<hr>";
    echo "<p>If you see a new 'Foreign Key' error here, it means there is yet <strong>another</strong> table linked to this student that we haven't included in the code yet.</p>";
    echo "<a href='../frontened/admin_dash.php' style='display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>← Back to Dashboard</a>";
    echo "</div>";
}
?>
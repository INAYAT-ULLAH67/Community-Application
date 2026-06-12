<?php
include_once('../config/db.php');

// Find students with plain text (length < 60 is a good check for BCRYPT)
$query = "SELECT dept_prefix, batch_year, student_id, password FROM Students";
$result = mysqli_query($con, $query);

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $pass = $row['password'];
    
    // Check if it's already a BCRYPT hash (starts with $2y$)
    if (substr($pass, 0, 4) !== '$2y$') {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        
        $u = "UPDATE Students SET password = '$hashed' 
              WHERE dept_prefix = '{$row['dept_prefix']}' 
              AND batch_year = {$row['batch_year']} 
              AND student_id = {$row['student_id']}";
        mysqli_query($con, $u);
        $count++;
    }
}
echo "Migration Complete: $count passwords hashed.";
?>
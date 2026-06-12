<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include_once('../config/db.php');

// THE LOGIC: If db.php has an echo, it will show up. 
// We want to verify the session exists.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div style='color:red; padding:20px; border:2px solid red;'>";
    echo "<h3>Security Block Active</h3>";
    echo "Your Session Role is: " . (isset($_SESSION['role']) ? $_SESSION['role'] : "NOT SET") . "<br>";
    echo "Please log in as an Admin again.";
    echo "</div>";
    exit();
}

// THE LOGIC: Refined Query based on your DESCRIBE results
$query = "SELECT s.student_id, s.full_name, s.dept_prefix, s.batch_year, 
          IFNULL(p.amount_paid, 0) as amount_paid,
          CASE 
            WHEN p.amount_paid > 0 THEN 'Paid' 
            ELSE 'Pending' 
          END as payment_status
          FROM Students s
          LEFT JOIN Payments p ON s.student_id = p.student_id 
          AND s.dept_prefix = p.dept_prefix 
          AND s.batch_year = p.batch_year
          ORDER BY s.batch_year DESC, s.student_id ASC";

$result = mysqli_query($con, $query);

if (!$result) {
    die("Query Failed: " . mysqli_error($con));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Official Report | PC Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print { .no-print { display: none !important; } }
        .report-header { text-align: center; border-bottom: 2px solid #1b5e20; margin-bottom: 20px; }
    </style>
</head>
<body class="bg-light">
<div class="container my-5">
    <div class="card shadow p-5 bg-white">
        <div class="report-header">
            <h2 class="fw-bold">PAKHTOON COMMUNITY PORTAL</h2>
            <!--  date and current time-->
            <p>Official Payment Audit | Generated: <?php echo date('Y-m-d H:i'); ?></p>
        </div>

        <!-- Print Button -->
        <div class="mb-4 no-print text-end">
            <button onclick="window.print()" class="btn btn-success">Print Official Report</button>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Roll No</th>
                    <th>Full Name</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo "{$row['dept_prefix']}-{$row['batch_year']}{$row['student_id']}"; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td>Rs. <?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['payment_status'] == 'Paid') ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $row['payment_status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
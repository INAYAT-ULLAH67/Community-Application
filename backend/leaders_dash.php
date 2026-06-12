<?php
/**
 * SECTION: SESSION & DATABASE INITIALIZATION
 */
session_start();
include_once('../config/db.php');

/**
 * SECTION: ACCESS CONTROL
 */
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

/**
 * SECTION: DATA FETCHING (TRIPLE JOIN)
 */

$sql = "SELECT 
            s.full_name, 
            s.dept_prefix, 
            s.batch_year, 
            s.student_id, 
            r.role_name, 
            l.tenure_year 
        FROM Students s 
        JOIN Undergraduate_Leadership l 
            ON s.student_id = l.student_id 
            AND s.dept_prefix = l.dept_prefix 
            AND s.batch_year = l.batch_year
        JOIN Roles r 
            ON l.role_id = r.role_id
        ORDER BY l.tenure_year DESC, r.role_name ASC";

$res = mysqli_query($con, $sql);

if (!$res) {
    die("Database Error: " . mysqli_error($con));
}
// we are taking tota no of leaders!
$count_leaders = mysqli_num_rows($res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership Hub | Pakhtoon Community</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --pakhtoon-green: #1b5e20; --pakhtoon-dark: #0a2d0d; --leader-gold: #ffc107; }
        body { background-color: #f8f9fa; }
        .navbar { background-color: var(--pakhtoon-green) !important; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 5px; }
        .stat-card { border-left: 5px solid var(--leader-gold); border-radius: 10px; }
        .table-card { border-radius: 15px; border: none; }
        .leader-badge { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-weight: 600; }
    </style>
</head>
<body>




<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-star-fill text-warning"></i> Leadership Info</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-inline">Admin: <strong><?php echo $_SESSION['full_name']; ?></strong></span>
            <a href="logout.php" class="btn btn-light btn-sm fw-bold text-danger">Logout</a>
        </div>
    </div>
</nav>





<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 p-3 sidebar shadow">
            <h5 class="text-center py-3">PC Portal V1.0</h5>
            <hr>
            <nav class="nav flex-column mt-3">
                <a class="nav-link" href="../frontened/admin_dash.php"><i class="bi bi-people-fill me-2"></i> Students</a>
                <a class="nav-link active" href="leaders_dash.php"><i class="bi bi-star-fill me-2"></i> Leaders</a>
                <a class="nav-link" href="phone_directory.php"><i class="bi bi-telephone-fill me-2"></i> Phone Directory</a>
                <a class="nav-link" href="../backend/departments.php"><i class="bi bi-building-fill me-2"></i> Departments</a>
                <a class="nav-link" href="payment_records.php"><i class="bi bi-cash-stack me-2"></i> Payment Records</a>
                <a class="nav-link" href="../backend/generate_report.php" target="_blank"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate Report</a>

            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h2 class="fw-bold text-dark">Community Leadership</h2>
                    <p class="text-muted">Currently serving cabinet and historical leadership records.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="card stat-card shadow-sm p-3 bg-white d-inline-block text-start" style="min-width: 220px;">
                        <small class="text-muted d-block text-uppercase fw-bold">Cabinet Size</small>
                        <h3 class="mb-0 text-dark fw-bold"><?php echo $count_leaders; ?> <span class="fs-6 fw-normal text-muted">Positions filled</span></h3>
                    </div>
                </div>
            </div>

            <div class="card table-card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-award-fill text-warning me-2"></i>Cabinet Directory</h5>
                    <!-- <button class="btn btn-dark btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#appointLeaderModal">
                        <i class="bi bi-person-plus-fill me-1 text-warning"></i> Appoint New Leader
                    </button> -->
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>Leader Name</th>
                                    <th>Position</th>
                                    <th>Roll Number</th>
                                    <th class="text-center">Tenure Year</th>
                                    <!-- <th class="text-center">Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($count_leaders > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($res)): 
                                        $short_year = substr($row['batch_year'], -2);// if we have 2024 then we get 24 only
                                        $roll_no = "{$row['dept_prefix']}-{$short_year}{$row['student_id']}";
                                    ?>
                                    <tr>
                                        <td><span class="fw-bold text-dark"><?php echo $row['full_name']; ?></span></td>
                                        <td>
                                            <span class="badge leader-badge px-3 py-2 text-uppercase">
                                                <?php echo $row['role_name']; ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-dark px-2"><?php echo $roll_no; ?></span></td>
                                        <td class="text-center fw-bold text-muted"><?php echo $row['tenure_year']; ?></td>
                                        <!-- <td class="text-center">
                                            <button class="btn btn-sm btn-light border me-1"><i class="bi bi-pencil-square text-primary"></i></button>
                                            <button class="btn btn-sm btn-light border"><i class="bi bi-trash3-fill text-danger"></i></button>
                                        </td> -->
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-5">No leaders found in database.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
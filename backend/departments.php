<?php
session_start();
include_once('../config/db.php');

// Access Control
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

$message = "";
$error_details = "";

/**
 * SECTION: ADD NEW DEPARTMENT
 */
if (isset($_POST['add_dept_btn'])) {
    $prefix = strtoupper(trim(mysqli_real_escape_string($con, $_POST['dept_prefix'])));
    $name = trim(mysqli_real_escape_string($con, $_POST['dept_name']));

    $sql = "INSERT INTO Departments (dept_prefix, dept_name) VALUES ('$prefix', '$name')";

    try {
        if (mysqli_query($con, $sql)) {
            header("Location: departments.php?msg=success");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            header("Location: departments.php?msg=duplicate&prefix=$prefix");
            exit();
        } else {
            header("Location: departments.php?msg=db_error");
            exit();
        }
    }
}

/**
 * SECTION: DELETE DEPARTMENT (With Protection)
 */
if (isset($_GET['delete'])) {
    $del_prefix = mysqli_real_escape_string($con, $_GET['delete']);
    
    try {
        $sql_del = "DELETE FROM Departments WHERE dept_prefix = '$del_prefix'";
        if (mysqli_query($con, $sql_del)) {
            header("Location: departments.php?msg=deleted");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1451) {
            header("Location: departments.php?msg=fk_error&dept=$del_prefix");
            exit();
        } else {
            header("Location: departments.php?msg=db_error");
            exit();
        }
    }
}

/**
 * SECTION: MESSAGE HANDLING
 */
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'success':
            $message = "success";
            break;
        case 'deleted':
            $message = "deleted_success";
            break;
        case 'duplicate':
            $message = "error";
            $error_details = "The Prefix '" . htmlspecialchars($_GET['prefix'] ?? '') . "' or Name already exists.";
            break;
        case 'fk_error':
            $message = "error";
            $error_details = "Cannot delete '" . htmlspecialchars($_GET['dept'] ?? '') . "' because students are currently registered in it.";
            break;
        case 'db_error':
            $message = "error";
            $error_details = "A database error occurred.";
            break;
    }
}

$query = "SELECT * FROM Departments ORDER BY dept_name ASC";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departments | Pakhtoon Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --pakhtoon-green: #1b5e20; --pakhtoon-dark: #0a2d0d; }
        body { background-color: #f8f9fa; }
        .navbar { background-color: var(--pakhtoon-green) !important; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 5px; }
        .stat-card { border-left: 5px solid var(--pakhtoon-green); border-radius: 10px; }
        .table-card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-check"></i> Pakhtoon Admin</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3 d-none d-md-inline">Logged in as: <strong><?php echo $_SESSION['full_name']; ?></strong></span>
            <a href="../backend/logout.php" class="btn btn-light btn-sm fw-bold text-danger">Logout</a>
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
                <a class="nav-link" href="leaders_dash.php"><i class="bi bi-star-fill me-2"></i> Leaders</a>
                <a class="nav-link" href="phone_directory.php"><i class="bi bi-telephone-fill me-2"></i> Phone Directory</a>
                <a class="nav-link active" href="departments.php"><i class="bi bi-building-fill me-2"></i> Departments</a>
                <a class="nav-link" href="payment_records.php"><i class="bi bi-cash-stack me-2"></i> Payments</a>
                <a class="nav-link" href="../backend/generate_report.php" target="_blank"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate Report</a>

            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="mb-4">
                <h2 class="fw-bold text-dark">University Departments</h2>
                <p class="text-muted">Register and manage academic departments within the community.</p>
            </div>

            <?php if($message == "success"): ?>
                <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
                    <i class="bi bi-check-circle-fill me-2"></i> Department added successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif($message == "deleted_success"): ?>
                <div class="alert alert-warning border-0 shadow-sm alert-dismissible fade show">
                    <i class="bi bi-trash-fill me-2"></i> Department removed.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif($message == "error"): ?>
                <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo $error_details; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="card dept-card shadow-sm mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-success">Add New Department</h5>
                        </div>
                        <div class="card-body">
                            <form action="departments.php" method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Dept Prefix (e.g., CS)</label>
                                    <input type="text" name="dept_prefix" class="form-control" maxlength="5" required placeholder="CS">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Full Department Name</label>
                                    <input type="text" name="dept_name" class="form-control" required placeholder="Computer System Engineering">
                                </div>
                                <button type="submit" name="add_dept_btn" class="btn btn-success w-100 fw-bold">Register Department</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card dept-card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Prefix</th>
                                            <th>Full Department Name</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td class="ps-4"><span class="badge bg-dark px-3"><?php echo $row['dept_prefix']; ?></span></td>
                                            <td class="fw-bold text-primary"><?php echo $row['dept_name']; ?></td>
                                            <td class="text-center">
                                                <a href="departments.php?delete=<?php echo $row['dept_prefix']; ?>" 
                                                   class="btn btn-sm btn-outline-danger border-0"
                                                   onclick="return confirm('Are you sure? All student records associated with this prefix must be cleared first.')">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
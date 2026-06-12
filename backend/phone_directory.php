<?php
session_start();
include_once('../config/db.php');

// Access Control
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

$message = "";

/**
 * SECTION: ADD PHONE NUMBER
 */
if (isset($_POST['add_phone_btn'])) {
    $dept = strtoupper(trim(mysqli_real_escape_string($con, $_POST['dept_prefix'])));
    $batch_input = (int)$_POST['batch_year'];
    $batch = ($batch_input > 1000) ? ($batch_input % 100) : $batch_input;
    $s_id = (int)$_POST['student_id'];
    $phone = trim(mysqli_real_escape_string($con, $_POST['phone_no']));

    $check_student = "SELECT * FROM Students 
                      WHERE dept_prefix = '$dept' 
                      AND batch_year = $batch 
                      AND student_id = $s_id";
    
    $check_res = mysqli_query($con, $check_student);

    if (mysqli_num_rows($check_res) > 0) {
        $check_exists = "SELECT * FROM Student_Phones 
                         WHERE dept_prefix='$dept' AND batch_year=$batch 
                         AND student_id=$s_id AND phone_no='$phone'";
        
        if (mysqli_num_rows(mysqli_query($con, $check_exists)) > 0) {
            header("Location: phone_directory.php?msg=duplicate");
            exit();
        } else {
            $sql_phone = "INSERT INTO Student_Phones (dept_prefix, batch_year, student_id, phone_no) 
                          VALUES ('$dept', $batch, $s_id, '$phone')";
            if (mysqli_query($con, $sql_phone)) {
                header("Location: phone_directory.php?msg=success");
            } else {
                header("Location: phone_directory.php?msg=error");
            }
            exit();
        }
    } else {
        header("Location: phone_directory.php?msg=not_found");
        exit();
    }
}

/**
 * SECTION: UPDATE PHONE NUMBER
 */
if (isset($_POST['update_phone_btn'])) {
    $old_phone = mysqli_real_escape_string($con, $_POST['old_phone_no']);
    $new_phone = trim(mysqli_real_escape_string($con, $_POST['new_phone_no']));
    $u_dept = mysqli_real_escape_string($con, $_POST['u_dept']);
    $u_batch = (int)$_POST['u_batch'];
    $u_id = (int)$_POST['u_id'];

    $sql_update = "UPDATE Student_Phones 
                   SET phone_no = '$new_phone' 
                   WHERE dept_prefix = '$u_dept' 
                   AND batch_year = $u_batch 
                   AND student_id = $u_id 
                   AND phone_no = '$old_phone'";

    if (mysqli_query($con, $sql_update)) {
        header("Location: phone_directory.php?msg=updated");
        exit();
    } else {
        header("Location: phone_directory.php?msg=error");
        exit();
    }
}

// Handle message notifications via clean GET parameters
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'success') $message = "success";
    elseif ($_GET['msg'] == 'updated') $message = "updated_success";
    elseif ($_GET['msg'] == 'not_found') $message = "not_found";
    elseif ($_GET['msg'] == 'duplicate') $message = "duplicate";
    elseif ($_GET['msg'] == 'error') $message = "error";
}

/**
 * SECTION: DIRECTORY QUERY
 */
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $search_query = " WHERE s.full_name LIKE '%$search%' OR s.dept_prefix LIKE '%$search%' ";
}

$query = "SELECT s.full_name, s.dept_prefix, s.batch_year, s.student_id, 
                 GROUP_CONCAT(p.phone_no SEPARATOR ', ') as numbers
          FROM Students s
          LEFT JOIN Student_Phones p ON s.dept_prefix = p.dept_prefix 
               AND s.batch_year = p.batch_year 
               AND s.student_id = p.student_id
          $search_query
          GROUP BY s.dept_prefix, s.batch_year, s.student_id
          ORDER BY s.full_name ASC";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phone Directory | Pakhtoon Community</title>
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
        
        /* Smooth fade transition for alerts */
        .auto-dismiss-alert {
            transition: opacity 0.5s ease, margin 0.5s ease, padding 0.5s ease, height 0.5s ease;
            overflow: hidden;
        }
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
                <a class="nav-link" href="../backend/leaders_dash.php"><i class="bi bi-star-fill me-2"></i> Leaders</a>
                <a class="nav-link active" href="phone_directory.php"><i class="bi bi-telephone-fill me-2"></i> Phone Directory</a>
                <a class="nav-link" href="../backend/departments.php"><i class="bi bi-building-fill me-2"></i> Departments</a>
                <a class="nav-link" href="../backend/payment_records.php"><i class="bi bi-cash-stack me-2"></i> Payment Records</a>
                <a class="nav-link" href="../backend/generate_report.php" target="_blank"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate Report</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark">Community Phone Directory</h2>
                    <p class="text-muted">Manage and search member contact details.</p>
                </div>
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-success px-4">Search</button>
                </form>
            </div>

            <?php if($message == "success"): ?>
                <div class="alert alert-success shadow-sm border-0 auto-dismiss-alert"><i class="bi bi-check-circle-fill me-2"></i> Number linked successfully!</div>
            <?php elseif($message == "updated_success"): ?>
                <div class="alert alert-info shadow-sm border-0 auto-dismiss-alert"><i class="bi bi-info-circle-fill me-2"></i> Phone number updated successfully!</div>
            <?php elseif($message == "not_found"): ?>
                <div class="alert alert-warning shadow-sm border-0 auto-dismiss-alert"><i class="bi bi-exclamation-triangle-fill me-2"></i> Student not found.</div>
            <?php elseif($message == "duplicate"): ?>
                <div class="alert alert-danger shadow-sm border-0 auto-dismiss-alert"><i class="bi bi-x-circle-fill me-2"></i> This number is already linked to this student.</div>
            <?php elseif($message == "error"): ?>
                <div class="alert alert-danger shadow-sm border-0 auto-dismiss-alert"><i class="bi bi-exclamation-octagon-fill me-2"></i> Something went wrong with the database.</div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="bi bi-plus-circle me-2"></i>Link Phone Number</h5>
                </div>
                <div class="card-body bg-light">
                    <form action="phone_directory.php" method="POST" class="row g-3">
                        <div class="col-md-2"><label class="form-label small fw-bold">Dept</label><input type="text" name="dept_prefix" class="form-control" placeholder="CS" required></div>
                        <div class="col-md-2"><label class="form-label small fw-bold">Batch</label><input type="number" name="batch_year" class="form-control" placeholder="2024" min="2000" required></div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Student ID</label>
                            <input type="number" name="student_id" class="form-control" placeholder="001" min="0" step="1" required>
                        </div>                        
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-primary">Phone Number</label>
                            <input type="text" name="phone_no" class="form-control border-primary" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" name="add_phone_btn" class="btn btn-success w-100 fw-bold">Add No</button></div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-4">Member Name</th>
                                    <th>Roll Number</th>
                                    <th>Phone Numbers</th>
                                    <th class="text-center">Quick Chat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo $row['full_name']; ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php 
                                                $display_year = $row['batch_year'] % 100;
                                                echo "{$row['dept_prefix']}-{$display_year}{$row['student_id']}"; 
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if($row['numbers']){
                                            $nums = explode(', ', $row['numbers']);
                                            foreach($nums as $n) {
                                                echo "<div class='d-inline-block me-2 mb-1'>";
                                                echo "<a href='tel:$n' class='badge bg-success text-decoration-none p-2 shadow-sm'><i class='bi bi-telephone-fill me-1'></i> $n</a>";
                                                echo "<button class='btn btn-sm text-primary p-0 ms-1 edit-phone-btn' 
                                                        data-bs-toggle='modal' data-bs-target='#editPhoneModal' 
                                                        data-dept='{$row['dept_prefix']}' data-batch='{$row['batch_year']}' 
                                                        data-id='{$row['student_id']}' data-phone='$n'><i class='bi bi-pencil-square'></i></button>";
                                                echo "</div>";
                                            }
                                        } else { echo "<span class='text-muted small italic'>No contact added</span>"; }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        if($row['numbers']){
                                            $first_num = explode(', ', $row['numbers'])[0];
                                            $wa_clean = preg_replace('/[^0-9]/', '', $first_num);
                                            echo "<a href='https://wa.me/$wa_clean' target='_blank' class='btn btn-sm btn-outline-success border-0 fw-bold'><i class='bi bi-whatsapp me-1'></i> Chat</a>";
                                        }
                                        ?>
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

<div class="modal fade" id="editPhoneModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold">Update Number</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="phone_directory.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="u_dept" id="u_dept">
                    <input type="hidden" name="u_batch" id="u_batch">
                    <input type="hidden" name="u_id" id="u_id">
                    <input type="hidden" name="old_phone_no" id="old_phone_no">
                    <label class="form-label small fw-bold">Enter New Number</label>
                    <input type="text" name="new_phone_no" id="new_phone_no" class="form-control" required>
                </div>
                <div class="modal-footer p-2">
                    <button type="submit" name="update_phone_btn" class="btn btn-primary btn-sm w-100 fw-bold">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle Modal Data Binding
document.addEventListener('click', function (e) {
    if (e.target.closest('.edit-phone-btn')) {
        const btn = e.target.closest('.edit-phone-btn');
        document.getElementById('u_dept').value = btn.getAttribute('data-dept');
        document.getElementById('u_batch').value = btn.getAttribute('data-batch');
        document.getElementById('u_id').value = btn.getAttribute('data-id');
        document.getElementById('old_phone_no').value = btn.getAttribute('data-phone');
        document.getElementById('new_phone_no').value = btn.getAttribute('data-phone');
    }
});

// AUTO-DISMISS ALERTS AFTER 3 SECONDS
document.addEventListener("DOMContentLoaded", function() {
    const alerts = document.querySelectorAll('.auto-dismiss-alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Smoothly reduce opacity
            alert.style.opacity = '0';
            setTimeout(function() {
                // Remove entirely from layout after fade finishes
                alert.style.display = 'none';
                
                // Clean URL parameters cleanly so refresh doesn't bring back the toast
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url);
            }, 500); 
        }, 3000); // 3000ms = 3 seconds
    });
});
</script>
</body>
</html>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * SECTION: SESSION & DATABASE INITIALIZATION
 */
session_start();
include_once('../config/db.php');

/**
 * SECTION: ACCESS CONTROL (RBAC)
 */
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

$message = ""; 
$error_details = "";

// Check for success message from redirect
if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $message = "success";
}

/**
 * SECTION: FORM PROCESSING (CREATE OPERATION)
 */
if (isset($_POST['add_btn'])) {
    $degree = (int)$_POST['degree_is'];

    if ($degree !== 1 && $degree !== 2) {
        $message = "error";
        $error_details = "Invalid Degree ID. Only 1 or 2 are permitted.";
    } else {
        $dept = strtoupper(trim(mysqli_real_escape_string($con, $_POST['dept_prefix'])));
        $batch_raw = (int)$_POST['batch_year'];
        // converting 2024 into 24 for reading 
        $batch = ($batch_raw > 1000) ? ($batch_raw % 100) : $batch_raw;
        $s_id = (int)$_POST['student_id'];
        $full_name = mysqli_real_escape_string($con, $_POST['full_name']);
        $email = trim(mysqli_real_escape_string($con, $_POST['email']));
        $username = trim(mysqli_real_escape_string($con, $_POST['username']));
        $role = mysqli_real_escape_string($con, $_POST['role']);
        $hashed_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO Students (dept_prefix, batch_year, student_id, full_name, email, degree_is, username, password, role) 
                VALUES ('$dept', $batch, $s_id, '$full_name', '$email', $degree, '$username', '$hashed_pass', '$role')";

        try {
            // whether we are inserting a leader or user!
            if (mysqli_query($con, $sql)) {
                if ($role === 'admin' && isset($_POST['leadership_role_id'])) {
                    $role_id = (int)$_POST['leadership_role_id']; 
                    $current_year = date("Y"); 
                    // 
                    $sql_leader = "INSERT INTO Undergraduate_Leadership (dept_prefix, batch_year, student_id, role_id, tenure_year) 
                                   VALUES ('$dept', $batch, $s_id, $role_id, '$current_year')";
                    mysqli_query($con, $sql_leader);
                }
                header("Location: admin_dash.php?msg=success");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            $message = "error";
            if ($e->getCode() == 1062) {
                if (strpos($e->getMessage(), 'email') !== false) {
                    $error_details = "The email '$email' is already registered.";
                } 
                // ye to masla hai?
                elseif (strpos($e->getMessage(), 'username') !== false) {
                    $error_details = "The username '$username' is already taken.";
                } elseif (strpos($e->getMessage(), 'PRIMARY') !== false) {
                    $error_details = "Roll Number $dept-$batch-$s_id is already registered.";
                } else {
                    $error_details = "Duplicate record found.";
                }
            } else {
                $error_details = "Database Error: " . $e->getMessage();
            }
        }
    }
}

/**
 * SECTION: STATISTICS AGGREGATION
 */
$count_query = "SELECT COUNT(*) as total FROM Students WHERE role = 'student'";
$count_result = mysqli_query($con, $count_query);
$count_data = mysqli_fetch_assoc($count_result);

/**
 * SECTION: FORM PROCESSING (UPDATE OPERATION)
 */
if (isset($_POST['update_btn'])) {
    $u_id = (int)$_POST['update_id'];
    $u_dept = mysqli_real_escape_string($con, $_POST['dept_prefix']);
    $u_batch = (int)$_POST['batch_year'];
    $u_full_name = mysqli_real_escape_string($con, $_POST['full_name']);
    $u_email = mysqli_real_escape_string($con, $_POST['email']);
    $u_role = mysqli_real_escape_string($con, $_POST['role']);
    $u_degree = (int)$_POST['degree_is'];

    $sql_update = "UPDATE Students SET 
                   full_name = '$u_full_name', 
                   email = '$u_email', 
                   role = '$u_role', 
                   degree_is = $u_degree 
                   WHERE student_id = $u_id 
                   AND dept_prefix = '$u_dept' 
                   AND batch_year = $u_batch";

    try {
        if (mysqli_query($con, $sql_update)) {
            header("Location: admin_dash.php?msg=success");
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        $message = "error";
        $error_details = "Update failed: Data conflict.";
    }
}

/**
 * SECTION: SEARCH BAR CONFIGURATION
 */
$search_where = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = mysqli_real_escape_string($con, trim($_GET['search']));
    // This allows system to search by full name, email, department prefix, or matching string values.
    $search_where = " WHERE full_name LIKE '%$search%' 
                      OR email LIKE '%$search%' 
                      OR dept_prefix LIKE '%$search%' 
                      OR student_id LIKE '%$search%' ";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Pakhtoon Community</title>
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
                <a class="nav-link active" href="admin_dash.php"><i class="bi bi-people-fill me-2"></i> Students</a>
                <a class="nav-link" href="../backend/leaders_dash.php"><i class="bi bi-star-fill me-2"></i> Leaders</a>
                <a class="nav-link" href="../backend/phone_directory.php"><i class="bi bi-telephone-fill me-2"></i> Phone Directory</a>
                <a class="nav-link" href="../backend/departments.php"><i class="bi bi-building-fill me-2"></i> Departments</a>
                <a class="nav-link" href="../backend/payment_records.php"><i class="bi bi-cash-stack me-2"></i> Payment Records</a>
                <a class="nav-link" href="../backend/generate_report.php" target="_blank"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate Report</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            
            <?php if($message == "success"): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Action completed successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php elseif($message == "error"): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Error:</strong> <?php echo $error_details; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <h2 class="fw-bold text-dark">Community Management</h2>
                    <p class="text-muted">Manage member profiles, add new Members, or update existing details below.   </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="card stat-card shadow-sm p-3 bg-white d-inline-block text-start" style="min-width: 200px;">
                        <small class="text-muted d-block">Active Students</small>
                        <h3 class="mb-0 text-success fw-bold"><?php echo $count_data['total']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="card table-card shadow-sm">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-primary">Member Directory</h5>
                    
                    <div class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0 justify-content-end">
                        <form action="admin_dash.php" method="GET" class="d-flex gap-1 mb-0">
                            <input type="text" name="search" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Search members..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="bi bi-search"></i></button>
                            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                                <a href="admin_dash.php" class="btn btn-sm btn-secondary fw-bold"><i class="bi bi-x-lg"></i></a>
                            <?php endif; ?>
                        </form>
                        <button class="btn btn-sm btn-success fw-bold shadow-sm py-2 px-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus-fill me-1"></i> Add New Member
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th>Roll No</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Dept</th>
                                    <th>Role</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Integrated the search logic criteria securely here
                                $sql = "SELECT * FROM Students $search_where ORDER BY role ASC, batch_year DESC, student_id ASC";
                                $res = mysqli_query($con, $sql);
                                
                                if (mysqli_num_rows($res) > 0) {
                                    while($row = mysqli_fetch_assoc($res)) {
                                        // taking data to display
                                        $display_year = $row['batch_year'] % 100; // Get last two digits of batch year
                                        $roll_no = "{$row['dept_prefix']}-{$display_year}{$row['student_id']}";
                                        $role_badge = ($row['role'] == 'admin') ? 'bg-danger' : 'bg-info';
                                        
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-dark px-2'>$roll_no</span></td>";
                                        echo "<td><span class='fw-bold'>{$row['full_name']}</span></td>";
                                        echo "<td><small>{$row['email']}</small></td>";
                                        echo "<td>{$row['dept_prefix']}</td>";
                                        echo "<td><span class='badge $role_badge'>" . ucfirst($row['role']) . "</span></td>";

                                        // delete logic 
                                        echo "<td class='text-center'>";
                                        $delete_url = "../backend/delete_student.php?id={$row['student_id']}&dept={$row['dept_prefix']}&batch={$row['batch_year']}";
                                        
                                        echo "    <button class='btn btn-sm btn-light border shadow-sm me-1 edit-btn' 
                                                    data-bs-toggle='modal' data-bs-target='#editStudentModal'
                                                    data-id='{$row['student_id']}' 
                                                    data-dept='{$row['dept_prefix']}'
                                                    data-batch='{$row['batch_year']}' 
                                                    data-name='{$row['full_name']}'
                                                    data-email='{$row['email']}' 
                                                    data-role='{$row['role']}'
                                                    data-degree='{$row['degree_is']}'>";
                                        echo "        <i class='bi bi-pencil-square text-primary'></i>";
                                        echo "    </button>";
                                        echo "    <a href='$delete_url' class='btn btn-sm btn-light border shadow-sm' onclick='return confirm(\"Your data from phone directory will also be deleted?\")'>";
                                        echo "        <i class='bi bi-trash3-fill text-danger'></i>";
                                        echo "    </a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted fw-bold'><i class='bi bi-folder-x me-2'></i>No member profiles found matching your search.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold">Register New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_dash.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">Dept</label><input type="text" name="dept_prefix" class="form-control" required placeholder="CS"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Batch</label><input type="number" name="batch_year" class="form-control" required placeholder="2024"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">ID</label><input type="number" name="student_id" class="form-control" required placeholder="001"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input type="email" name="email" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Degree</label>
                            <select name="degree_is" class="form-select" required><option value="1">Bachelor's</option><option value="2">Master's</option></select>
                        </div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Username</label><input type="text" name="username" class="form-control border-success" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Password</label><input type="password" name="password" class="form-control border-success" required></div>
                        <hr>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Privilege Role</label>
                            <select name="role" id="roleSelect" class="form-select border-primary" required onchange="toggleLeadershipOptions()">
                                <option value="student" selected>Student</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="leadershipSection" style="display: none;">
                            <label class="form-label small fw-bold text-warning">Leadership Title</label>
                            <select name="leadership_role_id" class="form-select border-warning shadow-sm">
                                <option value="" selected disabled>-- Select Role --</option>
                                <?php
                                $role_query = "SELECT * FROM Roles ORDER BY role_name ASC";
                                $role_res = mysqli_query($con, $role_query);
                                while($r = mysqli_fetch_assoc($role_res)) { echo "<option value='{$r['role_id']}'>{$r['role_name']}</option>"; }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_btn" class="btn btn-success px-4 fw-bold">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Edit Member Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="admin_dash.php" method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <input type="hidden" name="update_id" id="edit_id">
                        <input type="hidden" name="dept_prefix" id="edit_dept">
                        <input type="hidden" name="batch_year" id="edit_batch">
                        <div class="col-md-6"><label class="form-label small fw-bold">Full Name</label><input type="text" name="full_name" id="edit_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input type="email" name="email" id="edit_email" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Degree</label>
                            <select name="degree_is" id="edit_degree" class="form-select" required><option value="1">Bachelor's</option><option value="2">Master's</option></select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Privilege</label>
                            <select name="role" id="edit_role" class="form-select" required><option value="student">Student</option><option value="admin">Admin</option></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light"><button type="submit" name="update_btn" class="btn btn-primary px-4 fw-bold">Update</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Leadership Section
    function toggleLeadershipOptions() {
        const roleSelect = document.getElementById('roleSelect');
        const leadershipSection = document.getElementById('leadershipSection');
        leadershipSection.style.display = (roleSelect.value === 'admin') ? 'block' : 'none';
    }

    // Edit Modal Logic
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editStudentModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('edit_id').value = button.getAttribute('data-id');
                document.getElementById('edit_dept').value = button.getAttribute('data-dept');
                document.getElementById('edit_batch').value = button.getAttribute('data-batch');
                document.getElementById('edit_name').value = button.getAttribute('data-name');
                document.getElementById('edit_email').value = button.getAttribute('data-email');
                document.getElementById('edit_role').value = button.getAttribute('data-role');
                document.getElementById('edit_degree').value = button.getAttribute('data-degree');
            });
        }
    });
</script>
</body>
</html>
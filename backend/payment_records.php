<?php
session_start();
include_once('../config/db.php');

// Access Control
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

/**
 * SECTION: ACTION HANDLERS (DELETE & UPDATE)
 */

// 1. Delete Fund Logic
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    // Delete payments first due to foreign key constraints, then the fund
    mysqli_query($con, "DELETE FROM Payments WHERE fund_id = $id");
    mysqli_query($con, "DELETE FROM Funds WHERE fund_id = $id");
    header("Location: payment_records.php?msg=deleted");
    exit();
}

// 2. Update Fund Logic
if (isset($_POST['update_fund_btn'])) {
    $fid = (int)$_POST['fund_id'];
    $fname = mysqli_real_escape_string($con, $_POST['fund_name']);
    $famt = (float)$_POST['required_amount'];
    $fyear = (int)$_POST['target_year'];

    $update_sql = "UPDATE Funds SET fund_name='$fname', required_amount=$famt, target_year=$fyear WHERE fund_id=$fid";
    if (mysqli_query($con, $update_sql)) {
        header("Location: payment_records.php?msg=updated");
        exit();
    }
}

/**
 * SECTION: FETCH FUND SUMMARY (PROCEDURE)
 */
$summary_data = [];
$summary_query = "CALL GetFundSummary()";
$summary_res = mysqli_query($con, $summary_query);

if ($summary_res) {
    while ($row = mysqli_fetch_assoc($summary_res)) {
        $summary_data[] = $row;
    }
    // IMPORTANT: Clear buffer for next queries
    while(mysqli_next_result($con)){ ; } 
}

$message = "";
$error_details = "";

/**
 * SECTION: ADD NEW FUND TYPE
 */
if (isset($_POST['create_fund_btn'])) {
    $f_name = mysqli_real_escape_string($con, $_POST['fund_name']);
    $f_type = mysqli_real_escape_string($con, $_POST['fund_type']);
    $f_amount = (float)$_POST['required_amount'];
    $f_year = (int)$_POST['target_year'];

    $sql = "INSERT INTO Funds (fund_name, fund_type, required_amount, target_year) 
            VALUES ('$f_name', '$f_type', $f_amount, $f_year)";
    if (mysqli_query($con, $sql)) {
        header("Location: payment_records.php?msg=fund_created");
        exit();
    }
}

/**
 * SECTION: RECORD STUDENT PAYMENT
 */
if (isset($_POST['record_payment_btn'])) {
    $dept = strtoupper(trim(mysqli_real_escape_string($con, $_POST['dept_prefix'])));
    $batch = (int)$_POST['batch_year'];
    $batch = ($batch > 1000) ? ($batch % 100) : $batch;
    $s_id = (int)$_POST['student_id'];
    $fund_id = (int)$_POST['fund_id'];
    $amount = (float)$_POST['amount_paid'];

    $check = mysqli_query($con, "SELECT full_name FROM Students WHERE dept_prefix='$dept' AND batch_year=$batch AND student_id=$s_id");
    
    if (mysqli_num_rows($check) > 0) {
        $sql_pay = "INSERT INTO Payments (dept_prefix, batch_year, student_id, fund_id, amount_paid) 
                    VALUES ('$dept', $batch, $s_id, $fund_id, $amount)";
        if (mysqli_query($con, $sql_pay)) {
            header("Location: payment_records.php?msg=pay_success");
            exit();
        }
    } else {
        header("Location: payment_records.php?msg=not_found");
        exit();
    }
}

/**
 * SECTION: FETCH DATA FOR DROPDOWNS & HISTORY
 */
$funds_list = [];
$f_res = mysqli_query($con, "SELECT * FROM Funds ORDER BY target_year DESC, fund_id DESC");
while ($row = mysqli_fetch_assoc($f_res)) { $funds_list[] = $row; }

$history_query = "SELECT p.*, s.full_name, f.fund_name 
                  FROM Payments p
                  JOIN Students s ON p.dept_prefix = s.dept_prefix AND p.batch_year = s.batch_year AND p.student_id = s.student_id
                  JOIN Funds f ON p.fund_id = f.fund_id
                  ORDER BY p.payment_date DESC LIMIT 10";
$history_res = mysqli_query($con, $history_query);

// Handle Status Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'fund_created') $message = "success_fund";
    if ($_GET['msg'] == 'pay_success') $message = "success_pay";
    if ($_GET['msg'] == 'updated') $message = "success_update";
    if ($_GET['msg'] == 'deleted') $message = "success_delete";
    if ($_GET['msg'] == 'not_found') { $message = "error"; $error_details = "Student Roll Number not found!"; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance | Pakhtoon Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --pakhtoon-green: #1b5e20; --pakhtoon-dark: #0a2d0d; }
        body { background-color: #f8f9fa; }
        .navbar { background-color: var(--pakhtoon-green) !important; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; margin-bottom: 5px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 5px; }
        .progress { background-color: #e9ecef; border-radius: 5px; }
        .card-fund:hover { transform: translateY(-3px); transition: 0.3s; }
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
                <a class="nav-link" href="../backend/phone_directory.php"><i class="bi bi-telephone-fill me-2"></i> Phone Directory</a>
                <a class="nav-link" href="../backend/departments.php"><i class="bi bi-building-fill me-2"></i> Departments</a>
                <a class="nav-link active" href="../backend/payment_records.php"><i class="bi bi-cash-stack me-2"></i> Payment Records</a>
                <a class="nav-link" href="../backend/generate_report.php" target="_blank"><i class="bi bi-file-earmark-pdf-fill me-2"></i> Generate Report</a>

            </nav>
        </div>

        <!-- Main Dashboard Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Financial Records</h2>
                <button class="btn btn-dark fw-bold" data-bs-toggle="modal" data-bs-target="#addFundModal">
                    <i class="bi bi-plus-circle me-1"></i> Create New Fund
                </button>
            </div>

            <!-- Notification Alerts -->
            <?php if($message == "success_fund"): ?>
                <div class="alert alert-success border-0 shadow-sm">New Fund Goal created!</div>
            <?php elseif($message == "success_pay"): ?>
                <div class="alert alert-primary border-0 shadow-sm">Payment recorded successfully!</div>
            <?php elseif($message == "success_update"): ?>
                <div class="alert alert-info border-0 shadow-sm">Fund details updated!</div>
            <?php elseif($message == "success_delete"): ?>
                <div class="alert alert-warning border-0 shadow-sm">Fund and related payments deleted.</div>
            <?php elseif($message == "error"): ?>
                <div class="alert alert-danger border-0 shadow-sm"><?php echo $error_details; ?></div>
            <?php endif; ?>

            <!-- Active Fund Status Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-fill me-2"></i>Active Fund Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">


                                <?php foreach($summary_data as $fund): ?>
                                    <div class="col-md-4">


                                        <div class="p-3 border rounded bg-white shadow-sm card-fund">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($fund['fund_name']); ?></h6>
                                                    <small class="text-muted"><?php echo $fund['fund_type']; ?> (<?php echo $fund['target_year']; ?>)</small>
                                                </div>

                                                <!-- Action Dropdown -->
                                                 <!-- for delete and  update funds ! -->
                                                <div class="dropdown">
                                                    <button class="btn btn-link btn-sm text-dark p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                                        
                                                        <li><a class="dropdown-item small" href="#" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $fund['fund_id']; ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                        <li><a class="dropdown-item small text-danger" href="payment_records.php?delete_id=<?php echo $fund['fund_id']; ?>" onclick="return confirm('Deleting this fund will also delete ALL recorded payments for it. Proceed?')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                    </ul>
                                                </div>


                                            </div>

<!--  -->
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>Collected: <b>Rs. <?php echo number_format($fund['total_collected']); ?></b></span>
                                                <span class="badge bg-light text-success border">Goal: <?php echo number_format($fund['required_amount']); ?></span>
                                            </div>
<!--  -->
                                            <div class="progress mb-2" style="height: 8px;">
                                                <?php 
                                                    $percent = ($fund['required_amount'] > 0) ? ($fund['total_collected'] / $fund['required_amount']) * 100 : 0;
                                                    $percent = min($percent, 100); 
                                                ?>
                                                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: <?php echo $percent; ?>%"></div>
                                            </div>
                                            
                                            <small class="text-muted" style="font-size: 0.75rem;"><?php echo $fund['total_transactions']; ?> Payments Received</small>
                                        </div>
                                    </div>






                                    <!-- Modal for a specific fund -->
                                    <div class="modal fade" id="editModal<?php echo $fund['fund_id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content border-0 shadow">
                                                <form action="payment_records.php" method="POST">
                                                    <div class="modal-body p-4">
                                                        <h6 class="fw-bold mb-3">Update Fund Goal</h6>
                                                        <input type="hidden" name="fund_id" value="<?php echo $fund['fund_id']; ?>">


                                                        <div class="mb-2">
                                                            <label class="small fw-bold">Name</label>
                                                            <input type="text" name="fund_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fund['fund_name']); ?>" required>
                                                        </div>


                                                        <div class="mb-2">
                                                            <label class="small fw-bold">Year</label>
                                                            <input type="number" name="target_year" class="form-control form-control-sm" value="<?php echo $fund['target_year']; ?>" required>
                                                        </div>


                                                        <div class="mb-3">
                                                            <label class="small fw-bold">Required Amount</label>
                                                            <input type="number" name="required_amount" class="form-control form-control-sm" value="<?php echo $fund['required_amount']; ?>" required>
                                                        </div>


                                                        <div class="d-flex gap-2">
                                                            <button type="button" class="btn btn-light btn-sm w-100" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_fund_btn" class="btn btn-primary btn-sm w-100">Update</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>





            <!-- Transaction Forms & History Row -->
            <div class="row g-4">
                <!-- Payment Form -->
                <div class="col-md-5">
                    <div class="card shadow-sm p-4 border-0">
                        <h5 class="fw-bold text-success mb-4">Receive Payment</h5>
                        <form action="payment_records.php" method="POST">
                            <div class="row g-2 mb-3">
                                <div class="col-md-4"><label class="small fw-bold">Dept</label><input type="text" name="dept_prefix" class="form-control" placeholder="CS" required></div>
                                <div class="col-md-4"><label class="small fw-bold" >Batch</label><input type="number" name="batch_year" class="form-control" placeholder="24" min="0" required></div>
                                <div class="col-md-4"><label class="small fw-bold">ID</label><input type="number" name="student_id" class="form-control" placeholder="001" min="0" step="1" required></div>
                            </div>
                            <div class="mb-3">
                                <label class="small fw-bold">Select Fund</label>
                                <select name="fund_id" class="form-select border-success" required>
                                    <option value="" selected disabled>-- Choose Fund --</option>
                                    <?php foreach($funds_list as $f): ?>
                                        <option value="<?php echo $f['fund_id']; ?>"><?php echo "({$f['target_year']}) {$f['fund_name']}"; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4"><label class="small fw-bold text-primary">Amount Paid</label><input type="number" name="amount_paid" class="form-control border-primary" required></div>
                            <button type="submit" name="record_payment_btn" class="btn btn-success w-100 fw-bold py-2">Confirm Transaction</button>
                        </form>
                    </div>
                </div>


  
                
                <!-- History Table -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 fw-bold">Recent Transactions</div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Student</th>
                                            <th>Purpose</th>
                                            <th>Amount</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($p = mysqli_fetch_assoc($history_res)): ?>
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                                <small class="text-muted"><?php echo "{$p['dept_prefix']}-{$p['batch_year']}{$p['student_id']}"; ?></small>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['fund_name']); ?></span></td>
                                            <td class="fw-bold text-success">Rs. <?php echo number_format($p['amount_paid']); ?></td>
                                            <td class="small text-muted"><?php echo date('d M, H:i', strtotime($p['payment_date'])); ?></td>
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

<!-- Modal: Create New Fund -->
<div class="modal fade" id="addFundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Create New Fund Goal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="payment_records.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-bold">Fund Name</label><input type="text" name="fund_name" class="form-control" required></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold">Fund Type</label><select name="fund_type" class="form-select"><option value="Monthly">Monthly Dues</option><option value="Party">Party / Event</option></select></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Target Year</label><input type="number" name="target_year" class="form-control" value="2026" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold">Required Amount (Per Student)</label><input type="number" name="required_amount" class="form-control" required></div>
                    <button type="submit" name="create_fund_btn" class="btn btn-primary w-100 fw-bold py-2">Save Fund Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Clean URL after status messages
    if (window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete('msg');
        window.history.replaceState({path: url.href}, '', url.href);
    }
</script>
</body>
</html>
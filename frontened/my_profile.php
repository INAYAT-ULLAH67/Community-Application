<?php
session_start();
include_once('../config/db.php');

// Security Guard
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

$s_id = (int)$_SESSION['student_id'];
$dept = mysqli_real_escape_string($con, $_SESSION['dept']);
$batch = (int)$_SESSION['batch'];

// Fetch User Data
$profile_res = mysqli_query($con, "SELECT * FROM Students WHERE student_id = $s_id AND dept_prefix = '$dept' AND batch_year = $batch");
$user_data = mysqli_fetch_assoc($profile_res);

$full_name = htmlspecialchars($user_data['full_name'] ?? 'Student');
$phone = htmlspecialchars($user_data['phone_number'] ?? 'Not Linked');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Pakhtoon Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --pakhtoon-green: #1b5e20; --pakhtoon-dark: #0a2d0d; }
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        /* Top Navigation Header */
        .header-strip { 
            background: var(--pakhtoon-green); 
            color: white; 
            padding: 1rem 2rem; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .main-content { padding: 2rem; }

        .glass-card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: 100%;
        }

        .section-heading {
            font-size: 0.9rem;
            font-weight: 800;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        .section-heading i { margin-right: 8px; color: var(--pakhtoon-green); }

        /* Data Display Styling */
        .data-label { font-size: 0.8rem; color: #7f8c8d; margin-bottom: 2px; }
        .data-value { font-size: 1.1rem; color: #2c3e50; font-weight: 600; margin-bottom: 1.2rem; }

        /* Input Styling */
        .form-control {
            border: 1px solid #dcdde1;
            padding: 0.8rem;
            border-radius: 8px;
            background-color: #fbfbfc;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: var(--pakhtoon-green);
            box-shadow: 0 0 0 0.25rem rgba(27, 94, 32, 0.1);
        }

        .btn-update {
            background: var(--pakhtoon-green);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            transition: 0.3s;
        }
        .btn-update:hover { background: var(--pakhtoon-dark); transform: translateY(-1px); }

        .avatar-box {
            width: 65px;
            height: 65px;
            background: var(--pakhtoon-green);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="header-strip d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <i class="bi bi-person-circle fs-3 me-2"></i>
        <h5 class="m-0 fw-bold">Student Profile Manager</h5>
    </div>
    <a href="student_dash.php" class="btn btn-light btn-sm fw-bold"><i class="bi bi-arrow-left me-1"></i> Dashboard</a>
</div>

<div class="container-fluid main-content">
    
    <?php if(isset($_GET['status'])): ?>
        <div class="alert alert-<?php echo $_GET['status'] == 'success' ? 'success' : 'danger'; ?> shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="bi <?php echo $_GET['status'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill'; ?> me-2"></i>
            <div>
                <?php 
                    if($_GET['status'] == 'success') echo "Success! Your password has been updated.";
                    else echo "Update failed. Please check your current password and try again.";
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-xl-4 col-lg-5">
            <div class="glass-card p-4">
                <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                    <div class="avatar-box me-3"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                    <div>
                        <h4 class="m-0 fw-bold"><?php echo $full_name; ?></h4>
                        <span class="badge bg-success small">Verified Student</span>
                    </div>
                </div>

                <div class="section-heading"><i class="bi bi-book"></i> Academic Information</div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="data-label">Department</div>
                        <div class="data-value"><?php echo $dept; ?></div>
                    </div>
                    <div class="col-6">
                        <div class="data-label">Batch Year</div>
                        <div class="data-value">20<?php echo $batch; ?></div>
                    </div>
                </div>

                <div class="data-label">Registration Number</div>
                <div class="data-value text-primary"><?php echo "$dept-$batch$s_id"; ?></div>

                <div class="data-label">Linked Phone</div>
                <div class="data-value"><?php echo $phone; ?></div>

                <div class="mt-4 p-3 rounded bg-light border-start border-4 border-warning">
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle-fill text-warning me-1"></i> 
                        To change your name or department, please visit the Community Office.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="glass-card p-4">
                <div class="section-heading"><i class="bi bi-shield-lock"></i> Security & Password</div>
                
                <p class="text-muted small mb-4">We recommend using a unique password to keep your portal access secure.</p>

                <form action="../backend/update_password.php" method="POST">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary">Current Password</label>
                            <input type="password" name="old_pass" class="form-control" required placeholder="Enter your current password to authorize changes">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">New Password</label>
                            <input type="password" name="new_pass" class="form-control" required placeholder="At least 6 characters">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Confirm New Password</label>
                            <input type="password" name="confirm_pass" class="form-control" required placeholder="Repeat new password">
                        </div>

                        <div class="col-12 text-end border-top pt-4 mt-5">
                            <a href="../backend/logout.php" class="btn btn-link text-danger text-decoration-none me-3 fw-bold">Logout of Session</a>
                            <button type="submit" class="btn btn-update px-5 shadow-sm">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
include_once('../config/db.php'); 

// Security Guard
if (!isset($_SESSION['student_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php?error=Unauthorized");
    exit();
}

/** * SECTION: FETCH UPCOMING EVENTS (Party Funds)
 */
$events_query = "SELECT fund_name, required_amount, target_year 
                 FROM Funds 
                 WHERE fund_type = 'Party' 
                 ORDER BY target_year DESC, fund_id DESC";
$events_res = mysqli_query($con, $events_query);

// Fallback for names
$full_name = htmlspecialchars($_SESSION['full_name'] ?? 'Student');
$dept = htmlspecialchars($_SESSION['dept'] ?? 'N/A');
$batch = htmlspecialchars($_SESSION['batch'] ?? '00');
$student_id = htmlspecialchars($_SESSION['student_id'] ?? '000');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Panel | Pakhtoon Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root { --pakhtoon-green: #1b5e20; --pakhtoon-dark: #0a2d0d; }
        body { background-color: #f8f9fa; overflow-x: hidden; }
        .navbar { background-color: var(--pakhtoon-green) !important; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; padding-top: 20px; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; margin-bottom: 8px; padding: 10px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: white; background: rgba(255,255,255,0.1); border-radius: 5px; }
        .profile-card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .event-card { border-left: 4px solid var(--pakhtoon-green); background: #ffffff; transition: 0.3s; }
        .event-card:hover { transform: scale(1.02); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#"><i class="bi bi-shield-check"></i> Pakhtoon Community</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-2 d-none d-md-inline small">
                Logged in as: <strong><?php echo $full_name; ?></strong>
            </span>
            <div class="bg-white rounded-circle ms-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                 <i class="bi bi-person-fill text-dark"></i>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar d-none d-md-block shadow">
            <h5 class="text-center mb-4 border-bottom pb-3 mx-2 small text-uppercase opacity-50">Main Menu</h5>
            <nav class="nav flex-column">
                <a class="nav-link active" href="#"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a class="nav-link" href="../frontened/my_profile.php"><i class="bi bi-person-circle me-2"></i> My Profile</a>
                <a class="nav-link" href="#events-section"><i class="bi bi-calendar-event me-2"></i> Events</a>
                <hr class="bg-secondary mx-3">
                <a href="../backend/logout.php" class="nav-link text-danger fw-bold"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>
        </div>

        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-0">Assalamu Alaikum, <?php echo $full_name; ?>!</h2>
                    <p class="text-muted">Welcome to your student control panel.</p>
                </div>
                <span class="badge bg-success py-2 px-3"><i class="bi bi-check-circle-fill me-1"></i> Active Member</span>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card profile-card p-4 h-100">
                        <h5 class="fw-bold text-success mb-3"><i class="bi bi-mortarboard-fill me-2"></i>Academic Details</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="text-muted" width="40%">Department</td>
                                <td class="fw-bold"><?php echo $dept; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Batch</td>
                                <td class="fw-bold">20<?php echo $batch; ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Roll Number</td>
                                <td class="fw-bold text-primary"><?php echo $dept . "-" . $batch . "-" . $student_id; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card profile-card p-4 h-100 text-center shadow-sm border-0">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Community Status</h6>
                        
                        <div class="mb-3">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-person-badge text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>

                        <h2 class="text-dark fw-bold mb-1"><?php echo ucfirst($_SESSION['role']); ?></h2>
                        <p class="text-muted small mb-4">Official Member of Pakhtoon Community</p>
                        
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="p-2 border rounded bg-white">
                                    <div class="fw-bold text-success"><?php echo mysqli_num_rows($events_res); ?></div>
                                    <div class="extra-small text-muted" style="font-size: 10px;">OPEN EVENTS</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded bg-white">
                                    <div class="fw-bold text-primary">Active</div>
                                    <div class="extra-small text-muted" style="font-size: 10px;">PORTAL ACCESS</div>
                                </div>
                            </div>
                        </div>

                        <a href="#events-section" class="btn btn-primary w-100 fw-bold shadow-sm">
                            <i class="bi bi-calendar2-check me-2"></i>Browse Event List
                        </a>
                    </div>
                </div>
            </div>

            <div class="row mt-4 mb-5" id="events-section">
                <div class="col-12">
                    <div class="card profile-card p-4">
                        <h5 class="fw-bold mb-3 text-dark"><i class="bi bi-balloon-fill text-danger me-2"></i>Upcoming Events & Parties</h5>
                        <div class="row g-3">
                            <?php if ($events_res && mysqli_num_rows($events_res) > 0): ?>
                                <?php while($event = mysqli_fetch_assoc($events_res)): ?>
                                    <div class="col-md-4">
                                        <div class="card event-card shadow-sm h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge bg-soft-primary text-primary border border-primary mb-2"><?php echo $event['target_year']; ?></span>
                                                    <i class="bi bi-stars text-warning"></i>
                                                </div>
                                                <h6 class="fw-bold"><?php echo htmlspecialchars($event['fund_name']); ?></h6>
                                                <p class="small text-muted mb-3">Community gathering and celebration.</p>
                                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                                    <span class="fw-bold text-success">Rs. <?php echo number_format($event['required_amount']); ?></span>
                                                    <button class="btn btn-sm btn-success px-3">Register</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-3">
                                    <p class="text-muted small">No upcoming parties at this time. Check back later!</p>
                                </div>
                            <?php endif; ?>
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
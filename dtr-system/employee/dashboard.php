<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireEmployeeLogin();

// Get employee info
$stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id = ?");
$stmt->execute([$_SESSION['employee_id']]);
$employee = $stmt->fetch();

// Get today's record
$todayRecord = getTodayRecord($conn, $_SESSION['employee_id']);

// Get official time for today
$dayOfWeek = date('l');
$officialTime = getOfficialTime($conn, $_SESSION['employee_id'], $dayOfWeek);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">DTR System</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($employee['first_name']); ?>!</h2>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['success_message']; 
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Today's Status Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Today's Status</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3"><?php echo date('l, F d, Y'); ?></p>
                        
                        <?php if ($todayRecord): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <th>AM Clock In:</th>
                                        <td><?php echo formatTime($todayRecord['clock_in_am']); ?></td>
                                        <td><span class="badge bg-<?php echo $todayRecord['am_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                                            <?php echo $todayRecord['am_status'] ?? '--'; ?>
                                        </span></td>
                                    </tr>
                                    <tr>
                                        <th>AM Clock Out:</th>
                                        <td><?php echo formatTime($todayRecord['clock_out_am']); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th>PM Clock In:</th>
                                        <td><?php echo formatTime($todayRecord['clock_in_pm']); ?></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th>PM Clock Out:</th>
                                        <td><?php echo formatTime($todayRecord['clock_out_pm']); ?></td>
                                        <td>
                                            <?php if ($todayRecord['pm_status']): ?>
                                                <span class="badge bg-<?php echo $todayRecord['pm_status'] === 'OVERTIME' ? 'info' : 'success'; ?>">
                                                    <?php echo $todayRecord['pm_status']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Clock Out Buttons -->
                            <div class="d-grid gap-2 mt-3">
                                <?php if (!$todayRecord['clock_out_am']): ?>
                                    <button class="btn btn-warning" id="clockOutAMBtn">Clock Out (AM)</button>
                                <?php elseif (!$todayRecord['clock_in_pm']): ?>
                                    <button class="btn btn-primary" id="clockInPMBtn">Clock In (PM)</button>
                                <?php elseif (!$todayRecord['clock_out_pm']): ?>
                                    <button class="btn btn-warning" id="clockOutPMBtn">Clock Out (PM)</button>
                                <?php else: ?>
                                    <div class="alert alert-success mb-0">
                                        ✓ All clock entries completed for today
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No clock-in record for today yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Official Schedule Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Official Schedule (<?php echo $dayOfWeek; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($officialTime): ?>
                            <table class="table table-sm">
                                <tr>
                                    <th>AM Time In:</th>
                                    <td><?php echo date('h:i A', strtotime($officialTime['am_time_in'])); ?></td>
                                </tr>
                                <tr>
                                    <th>AM Time Out:</th>
                                    <td><?php echo date('h:i A', strtotime($officialTime['am_time_out'])); ?></td>
                                </tr>
                                <tr>
                                    <th>PM Time In:</th>
                                    <td><?php echo date('h:i A', strtotime($officialTime['pm_time_in'])); ?></td>
                                </tr>
                                <tr>
                                    <th>PM Time Out:</th>
                                    <td><?php echo date('h:i A', strtotime($officialTime['pm_time_out'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Grace Period:</th>
                                    <td><?php echo $officialTime['grace_period_minutes']; ?> minutes</td>
                                </tr>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No schedule set for <?php echo $dayOfWeek; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Quick Actions</h5>
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="view_records.php" class="btn btn-primary">
                                <i class="bi bi-calendar-check"></i> View My Records
                            </a>
                            <a href="edit_record.php" class="btn btn-secondary">
                                <i class="bi bi-pencil"></i> Edit Records
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Clock Out AM
        document.getElementById('clockOutAMBtn')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to clock out for AM?')) {
                fetch('../api/clock_out.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({period: 'am'})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });

        // Clock In PM
        document.getElementById('clockInPMBtn')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to clock in for PM?')) {
                fetch('../api/clock_out.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({period: 'pm_in'})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });

        // Clock Out PM
        document.getElementById('clockOutPMBtn')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to clock out for PM?')) {
                fetch('../api/clock_out.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({period: 'pm'})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });
    </script>
</body>
</html>
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

// Get filter parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get time records
$stmt = $conn->prepare("
    SELECT * FROM time_record 
    WHERE employee_id = ? 
    AND DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['employee_id'], $startDate, $endDate]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Time Records - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
   <?php
// Include header
require_once '../includes/header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Time Records</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card">
            <div class="card-body">
                <?php if (count($records) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>AM In</th>
                                    <th>AM Out</th>
                                    <th>AM Status</th>
                                    <th>PM In</th>
                                    <th>PM Out</th>
                                    <th>PM Status</th>
                                    <th>Total Hours</th>
                                    <th>Edited</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <?php
                                    $amHours = getTotalHours($record['clock_in_am'], $record['clock_out_am']);
                                    $pmHours = getTotalHours($record['clock_in_pm'], $record['clock_out_pm']);
                                    $totalHours = $amHours + $pmHours;
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($record['created_at'], 'M d, Y'); ?></td>
                                        <td><?php echo formatTime($record['clock_in_am']); ?></td>
                                        <td><?php echo formatTime($record['clock_out_am']); ?></td>
                                        <td>
                                            <?php if ($record['am_status']): ?>
                                                <span class="badge bg-<?php echo $record['am_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                                                    <?php echo $record['am_status']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatTime($record['clock_in_pm']); ?></td>
                                        <td><?php echo formatTime($record['clock_out_pm']); ?></td>
                                        <td>
                                            <?php if ($record['pm_status']): ?>
                                                <span class="badge bg-<?php echo $record['pm_status'] === 'OVERTIME' ? 'info' : ($record['pm_status'] === 'ON TIME' ? 'success' : 'warning'); ?>">
                                                    <?php echo $record['pm_status']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo number_format($totalHours, 2); ?> hrs</td>
                                        <td>
                                            <?php if ($record['is_edited']): ?>
                                                <span class="badge bg-info" title="<?php echo htmlspecialchars($record['edit_reason']); ?>">
                                                    Edited
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h5>Summary</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Total Records:</strong> <?php echo count($records); ?> days
                            </div>
                            <div class="col-md-4">
                                <strong>Total Hours:</strong> 
                                <?php 
                                $totalAllHours = 0;
                                foreach ($records as $record) {
                                    $totalAllHours += getTotalHours($record['clock_in_am'], $record['clock_out_am']);
                                    $totalAllHours += getTotalHours($record['clock_in_pm'], $record['clock_out_pm']);
                                }
                                echo number_format($totalAllHours, 2);
                                ?> hours
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No time records found for the selected date range.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdminLogin();

// Get all records
$stmt = $conn->query("
    SELECT tr.*, e.first_name, e.last_name, e.employee_id as emp_id, e.position
    FROM time_record tr
    JOIN employee e ON tr.employee_id = e.employee_id
    ORDER BY tr.created_at DESC
    LIMIT 100
");
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Records - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <?php
// Include header
require_once '../includes/header.php';
?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Delete Time Records</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="alert alert-warning">
            <strong><i class="bi bi-exclamation-triangle"></i> Warning:</strong> 
            Deleting records is permanent and cannot be undone. Please proceed with caution.
        </div>

        <!-- Records Table -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Time Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Record ID</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>AM In</th>
                                <th>AM Out</th>
                                <th>PM In</th>
                                <th>PM Out</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr id="record-<?php echo $record['record_id']; ?>">
                                    <td><?php echo $record['record_id']; ?></td>
                                    <td><?php echo htmlspecialchars($record['emp_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                    <td><?php echo formatDate($record['created_at'], 'M d, Y'); ?></td>
                                    <td><?php echo formatTime($record['clock_in_am']); ?></td>
                                    <td><?php echo formatTime($record['clock_out_am']); ?></td>
                                    <td><?php echo formatTime($record['clock_in_pm']); ?></td>
                                    <td><?php echo formatTime($record['clock_out_pm']); ?></td>
                                    <td>
                                        <?php if ($record['am_status']): ?>
                                            <span class="badge bg-<?php echo $record['am_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                                                AM: <?php echo $record['am_status']; ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($record['pm_status']): ?>
                                            <span class="badge bg-<?php echo $record['pm_status'] === 'OVERTIME' ? 'info' : 'success'; ?>">
                                                PM: <?php echo $record['pm_status']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteRecord(<?php echo $record['record_id']; ?>)">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
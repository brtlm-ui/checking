<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdminLogin();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM employee WHERE is_active = 1");
$totalEmployees = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM time_record WHERE DATE(created_at) = CURDATE()");
$todayRecords = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM time_record");
$totalRecords = $stmt->fetch()['total'];

// Get recent records
$stmt = $conn->query("
    SELECT tr.*, e.first_name, e.last_name, e.employee_id as emp_id
    FROM time_record tr
    JOIN employee e ON tr.employee_id = e.employee_id
    ORDER BY tr.created_at DESC
    LIMIT 10
");
$recentRecords = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">DTR System - Admin</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Admin Dashboard</h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body stat-card bg-primary text-white">
                        <h3><?php echo $totalEmployees; ?></h3>
                        <p>Active Employees</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body stat-card bg-success text-white">
                        <h3><?php echo $todayRecords; ?></h3>
                        <p>Clock-ins Today</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-body stat-card bg-info text-white">
                        <h3><?php echo $totalRecords; ?></h3>
                        <p>Total Records</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="manage_employees.php" class="btn btn-primary w-100">
                                    Manage Employees
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="manage_schedules.php" class="btn btn-warning w-100">
                                    Manage Schedules
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="view_all_records.php" class="btn btn-success w-100">
                                    View All Records
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="edit_record.php" class="btn btn-warning w-100">
                                    Edit Records
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="delete_record.php" class="btn btn-danger w-100">
                                    Delete Records
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Records -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recent Time Records</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>AM In</th>
                                        <th>AM Out</th>
                                        <th>PM In</th>
                                        <th>PM Out</th>
                                        <th>AM Status</th>
                                        <th>PM Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRecords as $record): ?>
                                        <tr>
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
                                                        <?php echo $record['am_status']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['pm_status']): ?>
                                                    <span class="badge bg-<?php echo $record['pm_status'] === 'OVERTIME' ? 'info' : 'success'; ?>">
                                                        <?php echo $record['pm_status']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" 
                                                   class="btn btn-sm btn-warning">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="view_all_records.php" class="btn btn-primary">View All Records →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
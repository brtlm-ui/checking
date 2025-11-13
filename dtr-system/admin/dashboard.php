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
    <?php
// Include header
require_once '../includes/header.php';
?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar: Modern Navigation -->
            <div class="col-md-3 mb-4">
                <div class="card h-100 sticky-top shadow-sm" style="top: 80px; border-radius: 20px; background: #f8f9fa;">
                    <div class="card-body p-0">
                        <nav class="nav flex-column py-3">
                            <a href="dashboard.php" class="nav-link px-4 py-3 active" style="border-radius: 12px;">
                                <i class="bi bi-speedometer2 me-2"></i> Dashboard
                            </a>
                            <a href="manage_employees.php" class="nav-link px-4 py-3" style="border-radius: 12px;">
                                <i class="bi bi-people me-2"></i> Manage Employees
                            </a>
                            <a href="manage_schedules.php" class="nav-link px-4 py-3" style="border-radius: 12px;">
                                <i class="bi bi-calendar-event me-2"></i> Manage Schedules
                            </a>
                            <a href="view_all_records.php" class="nav-link px-4 py-3" style="border-radius: 12px;">
                                <i class="bi bi-journal-text me-2"></i> View All Records
                            </a>
                            <a href="edit_record.php" class="nav-link px-4 py-3" style="border-radius: 12px;">
                                <i class="bi bi-pencil-square me-2"></i> Edit Records
                            </a>
                            <a href="delete_record.php" class="nav-link px-4 py-3" style="border-radius: 12px;">
                                <i class="bi bi-trash me-2"></i> Delete Records
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4">Admin Dashboard</h2>
                    </div>
                </div>
                <!-- Statistics Cards -->
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="stat-container">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $totalEmployees; ?></h3>
                                    <p>Active Employees</p>
                                </div>
                                <div class="stat-trend positive">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-container">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-clock-fill"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $todayRecords; ?></h3>
                                    <p>Clock-ins Today</p>
                                </div>
                                <div class="stat-trend">
                                    <i class="bi bi-activity"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-container">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-journal-text"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $totalRecords; ?></h3>
                                    <p>Total Records</p>
                                </div>
                                <div class="stat-trend">
                                    <i class="bi bi-bar-chart-fill"></i>
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
        <th>AM In Status</th>
        <th>AM Out</th>
        <th>AM Out Status</th>
        <th>PM In</th>
        <th>PM In Status</th>
        <th>PM Out</th>
        <th>PM Out Status</th>
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
            <td>
                <?php if ($record['am_in_status']): ?>
                    <span class="badge bg-<?php echo $record['am_in_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                        <?php echo $record['am_in_status']; ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><?php echo formatTime($record['clock_out_am']); ?></td>
            <td>
                <?php if ($record['am_out_status']): ?>
                    <span class="badge bg-<?php echo $record['am_out_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                        <?php echo $record['am_out_status']; ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><?php echo formatTime($record['clock_in_pm']); ?></td>
            <td>
                <?php if ($record['pm_in_status']): ?>
                    <span class="badge bg-<?php echo $record['pm_in_status'] === 'ON TIME' ? 'success' : 'warning'; ?>">
                        <?php echo $record['pm_in_status']; ?>
                    </span>
                <?php endif; ?>
            </td>
            <td><?php echo formatTime($record['clock_out_pm']); ?></td>
            <td>
                <?php if ($record['pm_out_status']): ?>
                    <span class="badge bg-<?php echo ($record['pm_out_status'] === 'OVERTIME') ? 'info' : (($record['pm_out_status'] === 'ON TIME') ? 'success' : 'warning'); ?>">
                        <?php echo $record['pm_out_status']; ?>
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
        </div>
    </div>

    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
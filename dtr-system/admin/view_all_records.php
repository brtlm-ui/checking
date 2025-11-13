<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdminLogin();

// Get filter parameters
$employeeFilter = $_GET['employee_id'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Build query
$query = "
    SELECT tr.*, e.first_name, e.last_name, e.employee_id as emp_id, e.position
    FROM time_record tr
    JOIN employee e ON tr.employee_id = e.employee_id
    WHERE DATE(tr.created_at) BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if (!empty($employeeFilter)) {
    $query .= " AND e.employee_id = ?";
    $params[] = $employeeFilter;
}

$query .= " ORDER BY tr.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Get all employees for filter dropdown
$employeesStmt = $conn->query("SELECT employee_id, first_name, last_name FROM employee WHERE is_active = 1 ORDER BY first_name");
$employees = $employeesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Records - DTR System</title>
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
            <h2>All Time Records</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <!-- Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id">
                            <option value="">All Employees</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['employee_id']; ?>" 
                                        <?php echo $employeeFilter == $emp['employee_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3">
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
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
    <tr>
        <th>Employee ID</th>
        <th>Name</th>
        <th>Position</th>
        <th>Date</th>
        <th>AM In</th>
        <th>AM In Status</th>
        <th>AM Out</th>
        <th>AM Out Status</th>
        <th>PM In</th>
        <th>PM In Status</th>
        <th>PM Out</th>
        <th>PM Out Status</th>
        <th>Total Hours</th>
        <th>Edited</th>
        <th>Actions</th>
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
            <td><?php echo htmlspecialchars($record['emp_id']); ?></td>
            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
            <td><?php echo htmlspecialchars($record['position']); ?></td>
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
            <td><?php echo number_format($totalHours, 2); ?> hrs</td>
            <td>
                <?php if ($record['is_edited']): ?>
                    <span class="badge bg-info" 
                          title="<?php echo htmlspecialchars($record['admin_notes']); ?>"
                          data-bs-toggle="tooltip">
                        Edited
                    </span>
                <?php endif; ?>
            </td>
            <td>
                <a href="edit_record.php?id=<?php echo $record['record_id']; ?>" 
                   class="btn btn-sm btn-warning">Edit</a>
                <button class="btn btn-sm btn-danger" 
                        onclick="deleteRecord(<?php echo $record['record_id']; ?>)">Delete</button>
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
                        No time records found for the selected filters.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
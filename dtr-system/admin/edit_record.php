<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdminLogin();

// Get record if editing
$record = null;
$employee = null;
if (isset($_GET['id'])) {
    $stmt = $conn->prepare("
        SELECT tr.*, e.first_name, e.last_name, e.employee_id as emp_id, e.position, e.department
        FROM time_record tr
        JOIN employee e ON tr.employee_id = e.employee_id
        WHERE tr.record_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $record = $stmt->fetch();
}

// Get all employees
$employeesStmt = $conn->query("SELECT employee_id, first_name, last_name FROM employee WHERE is_active = 1 ORDER BY first_name");
$employees = $employeesStmt->fetchAll();

// Get recent records
$recentStmt = $conn->query("
    SELECT tr.*, e.first_name, e.last_name, e.employee_id as emp_id
    FROM time_record tr
    JOIN employee e ON tr.employee_id = e.employee_id
    ORDER BY tr.created_at DESC
    LIMIT 50
");
$recentRecords = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Time Record - Admin</title>
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
            <h2>Edit Time Record</h2>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Select Record -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Select Record to Edit</h5>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <div class="list-group">
                            <?php foreach ($recentRecords as $rec): ?>
                                <a href="?id=<?php echo $rec['record_id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo ($record && $record['record_id'] == $rec['record_id']) ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($rec['emp_id'] . ' - ' . $rec['first_name'] . ' ' . $rec['last_name']); ?></h6>
                                    </div>
                                    <small>
                                        <?php echo formatDate($rec['created_at'], 'M d, Y'); ?><br>
                                        AM: <?php echo formatTime($rec['clock_in_am']); ?> - <?php echo formatTime($rec['clock_out_am']); ?><br>
                                        PM: <?php echo formatTime($rec['clock_in_pm']); ?> - <?php echo formatTime($rec['clock_out_pm']); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="col-md-8">
                <?php if ($record): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                Edit Record - <?php echo htmlspecialchars($record['emp_id'] . ' - ' . $record['first_name'] . ' ' . $record['last_name']); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Employee Info -->
                            <div class="alert alert-info">
                                <strong>Employee:</strong> <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?><br>
                                <strong>Position:</strong> <?php echo htmlspecialchars($record['position']); ?><br>
                                <strong>Department:</strong> <?php echo htmlspecialchars($record['department']); ?><br>
                                <strong>Date:</strong> <?php echo formatDate($record['created_at'], 'F d, Y (l)'); ?>
                            </div>

                            <form id="editRecordForm" method="POST" action="../api/update_record.php">
                                <input type="hidden" name="record_id" value="<?php echo $record['record_id']; ?>">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="clock_in_am" class="form-label">AM Clock In</label>
                                        <input type="datetime-local" class="form-control" id="clock_in_am" 
                                               name="clock_in_am" 
                                               value="<?php echo $record['clock_in_am'] ? date('Y-m-d\TH:i', strtotime($record['clock_in_am'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="clock_out_am" class="form-label">AM Clock Out</label>
                                        <input type="datetime-local" class="form-control" id="clock_out_am" 
                                               name="clock_out_am" 
                                               value="<?php echo $record['clock_out_am'] ? date('Y-m-d\TH:i', strtotime($record['clock_out_am'])) : ''; ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="clock_in_pm" class="form-label">PM Clock In</label>
                                        <input type="datetime-local" class="form-control" id="clock_in_pm" 
                                               name="clock_in_pm" 
                                               value="<?php echo $record['clock_in_pm'] ? date('Y-m-d\TH:i', strtotime($record['clock_in_pm'])) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="clock_out_pm" class="form-label">PM Clock Out</label>
                                        <input type="datetime-local" class="form-control" id="clock_out_pm" 
                                               name="clock_out_pm" 
                                               value="<?php echo $record['clock_out_pm'] ? date('Y-m-d\TH:i', strtotime($record['clock_out_pm'])) : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="edit_reason" class="form-label">Reason for Edit <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit_reason" name="edit_reason" 
                                              rows="3" required 
                                              placeholder="Please provide a detailed reason for editing this record"></textarea>
                                    <small class="text-muted">This will be logged and visible in the record history.</small>
                                </div>

                                <?php if ($record['is_edited']): ?>
                                    <div class="alert alert-warning">
                                        <strong><i class="bi bi-exclamation-triangle"></i> Previous Edit:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($record['edit_reason'])); ?><br>
                                        <small class="text-muted">
                                            Edited on: <?php echo formatDate($record['edited_at'], 'M d, Y h:i A'); ?>
                                            <?php if ($record['edited_by']): ?>
                                                by Admin ID: <?php echo $record['edited_by']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                                    <a href="view_all_records.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Please select a record from the list on the left to edit.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
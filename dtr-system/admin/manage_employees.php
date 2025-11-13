<?php
session_start();
error_reporting(0); 
ini_set('display_errors', 0);

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

requireAdminLogin();

$fieldErrors = [];
$successMessage = '';
$errorMessage = '';

// ✅ Handle success redirect alert
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $successMessage = 'Employee added successfully!';
}

// Handle deactivate/activate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['deactivate', 'activate'])) {
    $employeeId = $_POST['employee_id'] ?? '';
    $isActive = $_POST['action'] === 'activate' ? 1 : 0;
    
    try {
        $stmt = $conn->prepare("UPDATE employee SET is_active = ? WHERE employee_id = ?");
        if ($stmt->execute([$isActive, $employeeId])) {
            $_SESSION['success_message'] = 'Employee status updated successfully';
        } else {
            $_SESSION['error_message'] = 'Failed to update employee status';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Database error occurred';
    }
    
    header('Location: manage_employees.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    // Sanitize inputs
    $employeeId = trim($_POST['employee_id'] ?? '');
    $pin = trim($_POST['pin'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validate inputs
    if (empty($employeeId)) {
        $fieldErrors['employee_id'] = 'Employee ID is required';
    } elseif (!preg_match('/^\d{4}$/', $employeeId)) {
        $fieldErrors['employee_id'] = 'Employee ID must be exactly 4 digits';
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM employee WHERE employee_id = ?");
        $stmt->execute([$employeeId]);
        if ($stmt->fetchColumn() > 0) {
            $fieldErrors['employee_id'] = 'This Employee ID already exists';
        }
    }

    if (empty($pin)) {
        $fieldErrors['pin'] = 'PIN is required';
    } elseif (!preg_match('/^\d{6}$/', $pin)) {
        $fieldErrors['pin'] = 'PIN must be exactly 6 digits';
    }

    if (empty($firstName)) {
        $fieldErrors['first_name'] = 'First name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $firstName)) {
        $fieldErrors['first_name'] = 'First name must contain only letters (2–50 characters)';
    }

    if (empty($lastName)) {
        $fieldErrors['last_name'] = 'Last name is required';
    } elseif (!preg_match('/^[a-zA-Z\s]{2,50}$/', $lastName)) {
        $fieldErrors['last_name'] = 'Last name must contain only letters (2–50 characters)';
    }

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = 'Invalid email format';
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM employee WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $fieldErrors['email'] = 'This email is already registered';
            }
        }
    }

    if (empty($position)) {
        $fieldErrors['position'] = 'Position is required';
    }

    if (empty($department)) {
        $fieldErrors['department'] = 'Department is required';
    }

    // ✅ If valid, insert and redirect
    if (empty($fieldErrors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO employee (employee_id, pin, first_name, last_name, email, position, department, created_by_admin)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employeeId,
                $pin,
                $firstName,
                $lastName,
                $email,
                $position,
                $department,
                $_SESSION['admin_id']
            ]);

            // 🔹 Clear POST data and redirect to prevent resubmission
            header("Location: manage_employees.php?success=1");
            exit;

        } catch (PDOException $e) {
            $errorMessage = 'Database error occurred. Please try again.';
        }
    }
}

// Get all employees
$stmt = $conn->query("SELECT * FROM employee ORDER BY created_at DESC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .invalid-feedback { display:block; color:#dc3545; font-size:0.875rem; }
    </style>
</head>
<body class="bg-light">

<?php
// Include header
require_once '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Employees</h2>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Add Employee Form -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Employee</h5>
                </div>
                <div class="card-body">
                    <form method="POST" novalidate>
                        <input type="hidden" name="action" value="add">

                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <input type="text" name="employee_id" id="employee_id" class="form-control <?php echo isset($fieldErrors['employee_id']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['employee_id'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['employee_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="pin" class="form-label">PIN (6 digits)</label>
                            <input type="text" name="pin" id="pin" maxlength="6" class="form-control <?php echo isset($fieldErrors['pin']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['pin'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['pin'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control <?php echo isset($fieldErrors['first_name']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['first_name'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['first_name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control <?php echo isset($fieldErrors['last_name']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['last_name'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['last_name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email (optional)</label>
                            <input type="email" name="email" id="email" class="form-control <?php echo isset($fieldErrors['email']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['email'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['email'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <select name="position" id="position" class="form-select <?php echo isset($fieldErrors['position']) ? 'is-invalid' : ''; ?>">
                                <option value="">Select Position</option>
                                <?php
                                $positions = ['Instructor I','Professor','Assistant Professor','Program Chair','Dean'];
                                foreach ($positions as $p) {
                                    echo "<option value=\"$p\">$p</option>";
                                }
                                ?>
                            </select>
                            <?php if (isset($fieldErrors['position'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['position'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" name="department" id="department" class="form-control <?php echo isset($fieldErrors['department']) ? 'is-invalid' : ''; ?>" value="">
                            <?php if (isset($fieldErrors['department'])): ?>
                                <div class="invalid-feedback"><?= $fieldErrors['department'] ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Employee List -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">All Employees</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($emp['employee_id']) ?></td>
                                        <td><?= htmlspecialchars($emp['first_name'].' '.$emp['last_name']) ?></td>
                                        <td><?= htmlspecialchars($emp['position']) ?></td>
                                        <td><?= htmlspecialchars($emp['department']) ?></td>
                                        <td>
                                            <?php if ($emp['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Edit button always available -->
                                            <button type="button" class="btn btn-primary btn-sm me-1"
                                                    onclick='openEditModal(<?php echo htmlspecialchars(json_encode($emp), ENT_QUOTES, "UTF-8"); ?>)'>
                                                Edit
                                            </button>

                                            <?php if ($emp['is_active']): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($emp['employee_id']) ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">Deactivate</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="employee_id" value="<?= htmlspecialchars($emp['employee_id']) ?>">
                                                    <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                <!-- Edit Employee Modal -->
                <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Employee</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editEmployeeForm">
                                    <input type="hidden" name="employee_id" id="edit_employee_id">
                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" id="edit_email" name="email" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Position</label>
                                        <select id="edit_position" name="position" class="form-select" required>
                                            <option value="">Select Position</option>
                                            <?php foreach (['Instructor I','Professor','Assistant Professor','Program Chair','Dean'] as $p): ?>
                                                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <input type="text" id="edit_department" name="department" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">PIN (leave blank to keep current)</label>
                                        <input type="text" id="edit_pin" name="pin" maxlength="6" class="form-control">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveEditBtn">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
                    function openEditModal(emp) {
                        document.getElementById('edit_employee_id').value = emp.employee_id;
                        document.getElementById('edit_first_name').value = emp.first_name;
                        document.getElementById('edit_last_name').value = emp.last_name;
                        document.getElementById('edit_email').value = emp.email;
                        document.getElementById('edit_position').value = emp.position;
                        document.getElementById('edit_department').value = emp.department;
                        document.getElementById('edit_pin').value = '';
                        editModal.show();
                    }

                    document.getElementById('saveEditBtn').addEventListener('click', function() {
                        const form = document.getElementById('editEmployeeForm');
                        const fd = new FormData(form);

                        fetch('../api/update_employee.php', { method: 'POST', body: fd })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    // reload to show changes
                                    location.reload();
                                } else {
                                    alert(data.message || 'Unable to update employee');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                alert('Error updating employee');
                            });
                    });
                </script>
</body>
</html>

<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if employee data is in session
if (!isset($_SESSION['confirm_employee_id'])) {
    header('Location: login.php');
    exit();
}

// Get employee info
$stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id = ? AND is_active = 1");
$stmt->execute([$_SESSION['confirm_employee_id']]);
$employee = $stmt->fetch();

if (!$employee) {
    session_unset();
    header('Location: login.php');
    exit();
}

// Mask the name
$maskedFirstName = maskName($employee['first_name']);
$maskedLastName = maskName($employee['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Identity - DTR System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Confirm Your Identity</h2>
                            <p class="text-muted">Please verify your information</p>
                        </div>

                        <div class="employee-info mb-4">
                            <div class="mb-3">
                                <label class="form-label text-muted">Employee ID:</label>
                                <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Name:</label>
                                <div class="fs-5 fw-semibold">
                                    <?php echo htmlspecialchars($maskedFirstName . ' ' . $maskedLastName); ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Position:</label>
                                <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($employee['position']); ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Department:</label>
                                <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($employee['department']); ?></div>
                            </div>
                        </div>

                        <div id="confirm-error" class="alert alert-danger d-none"></div>
                        <div id="confirm-success" class="alert alert-success d-none"></div>

                        <form id="confirmForm">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="confirmBtn">
                                    <span id="btnText">Confirm & Clock In</span>
                                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/employee.js"></script>
</body>
</html>
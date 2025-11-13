<?php
// Get the current user type and name from session
$currentUserType = $_SESSION['user_type'] ?? '';
$currentUserName = '';
$dashboardLink = '';
$navbarColor = '';

if ($currentUserType === 'admin') {
    $currentUserName = $_SESSION['admin_name'] ?? '';
    $dashboardLink = '../admin/dashboard.php';
    $navbarColor = 'bg-success'; // Green for admin
} elseif ($currentUserType === 'employee') {
    $currentUserName = $_SESSION['employee_name'] ?? '';
    $dashboardLink = '../employee/dashboard.php';
    $navbarColor = 'bg-primary'; // Blue for employee
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'DTR System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark <?php echo $navbarColor; ?>">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $dashboardLink; ?>">
                DTR System <?php echo ucfirst($currentUserType); ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <?php echo htmlspecialchars($currentUserName); ?>
                </span>
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    Logout
                </button>
            </div>
        </div>
    </nav>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to logout?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="../<?php echo $currentUserType; ?>/logout.php" class="btn btn-primary">Yes, Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="container-fluid mt-4">

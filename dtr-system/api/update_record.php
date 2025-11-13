<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method';
    header('Location: ../employee/edit_record.php');
    exit();
}

// Check if user is logged in (employee or admin)
if (!isEmployeeLoggedIn() && !isAdminLoggedIn()) {
    header('Location: ../employee/login.php');
    exit();
}

$recordId = sanitizeInput($_POST['record_id'] ?? '');
$clockInAM = $_POST['clock_in_am'] ?? null;
$clockOutAM = $_POST['clock_out_am'] ?? null;
$clockInPM = $_POST['clock_in_pm'] ?? null;
$clockOutPM = $_POST['clock_out_pm'] ?? null;
$editReason = sanitizeInput($_POST['edit_reason'] ?? '');

if (empty($recordId) || empty($editReason)) {
    $_SESSION['error_message'] = 'Record ID and edit reason are required';
    header('Location: ' . (isAdminLoggedIn() ? '../admin/edit_record.php' : '../employee/edit_record.php') . '?id=' . $recordId);
    exit();
}

try {
    // Get the record
    $stmt = $conn->prepare("SELECT * FROM time_record WHERE record_id = ?");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch();

    if (!$record) {
        $_SESSION['error_message'] = 'Record not found';
        header('Location: ' . (isAdminLoggedIn() ? '../admin/edit_record.php' : '../employee/edit_record.php'));
        exit();
    }

    // If employee, verify ownership
    if (isEmployeeLoggedIn() && $record['employee_id'] != $_SESSION['employee_id']) {
        $_SESSION['error_message'] = 'Unauthorized access';
        header('Location: ../employee/edit_record.php');
        exit();
    }

    // Convert empty strings to NULL
    $clockInAM = !empty($clockInAM) ? $clockInAM . ':00' : null;
    $clockOutAM = !empty($clockOutAM) ? $clockOutAM . ':00' : null;
    $clockInPM = !empty($clockInPM) ? $clockInPM . ':00' : null;
    $clockOutPM = !empty($clockOutPM) ? $clockOutPM . ':00' : null;

    // Get official time for status calculation
    $dayOfWeek = getDayOfWeek($record['created_at']);
    $officialTime = getOfficialTime($conn, $record['employee_id'], $dayOfWeek);

    // Calculate new statuses
    $amStatus = null;
    $pmStatus = null;

    if ($officialTime && $clockInAM) {
        $officialAMIn = date('Y-m-d', strtotime($clockInAM)) . ' ' . $officialTime['am_time_in'];
        $amStatus = calculateStatus($clockInAM, $officialAMIn, $officialTime['grace_period_minutes']);
    }

    if ($officialTime && $clockOutPM) {
        $officialPMOut = date('Y-m-d', strtotime($clockOutPM)) . ' ' . $officialTime['pm_time_out'];
        $pmStatus = calculateOvertimeStatus($clockOutPM, $officialPMOut);
    }

    // Update record
    $editedBy = isAdminLoggedIn() ? $_SESSION['admin_id'] : null;
    
    $stmt = $conn->prepare("
        UPDATE time_record 
        SET clock_in_am = ?, 
            clock_out_am = ?, 
            clock_in_pm = ?, 
            clock_out_pm = ?,
            am_status = ?,
            pm_status = ?,
            is_edited = 1,
            edit_reason = ?,
            edited_at = NOW(),
            edited_by = ?
        WHERE record_id = ?
    ");
    
    $stmt->execute([
        $clockInAM, 
        $clockOutAM, 
        $clockInPM, 
        $clockOutPM,
        $amStatus,
        $pmStatus,
        $editReason,
        $editedBy,
        $recordId
    ]);

    $_SESSION['success_message'] = 'Record updated successfully';
    
    if (isAdminLoggedIn()) {
        header('Location: ../admin/edit_record.php?id=' . $recordId);
    } else {
        header('Location: ../employee/edit_record.php?id=' . $recordId);
    }

} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: ' . (isAdminLoggedIn() ? '../admin/edit_record.php' : '../employee/edit_record.php') . '?id=' . $recordId);
}
?>
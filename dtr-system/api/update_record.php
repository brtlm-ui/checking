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
    $dayOfWeek = getDayOfWeek($record['work_date'] ?? $record['created_at']);
    $officialTime = getOfficialTime($conn, $record['employee_id'], $dayOfWeek);

    // Calculate new statuses for all 4 entries
    $amInStatus = null;
    $amOutStatus = null;
    $pmInStatus = null;
    $pmOutStatus = null;

    if ($officialTime) {
        // Calculate AM IN status
        if ($clockInAM) {
            $officialAMIn = date('Y-m-d', strtotime($clockInAM)) . ' ' . $officialTime['am_time_in'];
            $amInStatus = calculateStatus($clockInAM, $officialAMIn, $officialTime['grace_period_minutes']);
        }

        // Calculate AM OUT status
        if ($clockOutAM) {
            $officialAMOut = date('Y-m-d', strtotime($clockOutAM)) . ' ' . $officialTime['am_time_out'];
            $amOutStatus = calculateClockOutStatus($clockOutAM, $officialAMOut, $officialTime['grace_period_minutes']);
        }

        // Calculate PM IN status
        if ($clockInPM) {
            $officialPMIn = date('Y-m-d', strtotime($clockInPM)) . ' ' . $officialTime['pm_time_in'];
            $pmInStatus = calculateStatus($clockInPM, $officialPMIn, $officialTime['grace_period_minutes']);
        }

        // Calculate PM OUT status
        if ($clockOutPM) {
            $officialPMOut = date('Y-m-d', strtotime($clockOutPM)) . ' ' . $officialTime['pm_time_out'];
            $pmOutStatus = calculateClockOutStatus($clockOutPM, $officialPMOut, $officialTime['grace_period_minutes']);
        }
    }

    // Update record
    $editedBy = isAdminLoggedIn() ? $_SESSION['admin_id'] : null;
    
    $stmt = $conn->prepare("
        UPDATE time_record 
        SET clock_in_am = ?, 
            clock_out_am = ?, 
            clock_in_pm = ?, 
            clock_out_pm = ?,
            am_in_status = ?,
            am_out_status = ?,
            pm_in_status = ?,
            pm_out_status = ?,
            is_edited = 1,
            admin_notes = ?,
            edited_at = NOW(),
            edited_by_admin = ?
        WHERE record_id = ?
    ");
    
    $stmt->execute([
        $clockInAM, 
        $clockOutAM, 
        $clockInPM, 
        $clockOutPM,
        $amInStatus,
        $amOutStatus,
        $pmInStatus,
        $pmOutStatus,
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
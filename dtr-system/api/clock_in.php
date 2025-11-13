<?php
session_start();
require_once '../config/timezone.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if employee is in confirmation session
if (!isset($_SESSION['confirm_employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

$employeeId = $_SESSION['confirm_employee_id'];

try {
    // Check if already clocked in today
    if (hasClockInToday($conn, $employeeId)) {
        echo json_encode(['success' => false, 'message' => 'You have already clocked in today']);
        exit();
    }

    // Get current day and official time
    $currentDateTime = date('Y-m-d H:i:s');
    $workDate = date('Y-m-d');
    $dayOfWeek = getDayOfWeek($currentDateTime);
    $officialTime = getOfficialTime($conn, $employeeId, $dayOfWeek);

    if (!$officialTime) {
        echo json_encode(['success' => false, 'message' => 'No schedule found for today']);
        exit();
    }

    // Calculate AM IN status
    $officialAMIn = date('Y-m-d') . ' ' . $officialTime['am_time_in'];
    $amInStatus = calculateStatus($currentDateTime, $officialAMIn, $officialTime['grace_period_minutes']);

    // Create time record with AM clock in
    $stmt = $conn->prepare("
        INSERT INTO time_record (employee_id, work_date, clock_in_am, am_in_status, created_at) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$employeeId, $workDate, $currentDateTime, $amInStatus, $currentDateTime]);

    // Set employee session
    $_SESSION['user_type'] = 'employee';
    $_SESSION['employee_id'] = $employeeId;
    unset($_SESSION['confirm_employee_id']);

    // Set success message
    $_SESSION['success_message'] = "Successfully clocked in at " . formatTime($currentDateTime) . " - Status: " . $amInStatus;

    echo json_encode([
        'success' => true,
        'message' => 'Clock in successful',
        'status' => $amInStatus,
        'time' => formatTime($currentDateTime),
        'redirect' => 'dashboard.php'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
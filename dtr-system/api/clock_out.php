<?php
session_start();
require_once '../config/timezone.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isEmployeeLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$period = $input['period'] ?? '';
$employeeId = $_SESSION['employee_id'];

if (empty($period)) {
    echo json_encode(['success' => false, 'message' => 'Period not specified']);
    exit();
}

try {
    // Get today's record
    $todayRecord = getTodayRecord($conn, $employeeId);

    if (!$todayRecord) {
        echo json_encode(['success' => false, 'message' => 'No clock-in record found for today']);
        exit();
    }

    $currentDateTime = date('Y-m-d H:i:s');
    $dayOfWeek = getDayOfWeek($currentDateTime);
    $officialTime = getOfficialTime($conn, $employeeId, $dayOfWeek);

    if ($period === 'am') {
        // Clock out AM
        if ($todayRecord['clock_out_am']) {
            echo json_encode(['success' => false, 'message' => 'Already clocked out for AM']);
            exit();
        }

        // Calculate AM OUT status (check for early leave)
        $officialAMOut = date('Y-m-d') . ' ' . $officialTime['am_time_out'];
        $amOutStatus = calculateClockOutStatus($currentDateTime, $officialAMOut, $officialTime['grace_period_minutes']);

        $stmt = $conn->prepare("UPDATE time_record SET clock_out_am = ?, am_out_status = ? WHERE record_id = ?");
        $stmt->execute([$currentDateTime, $amOutStatus, $todayRecord['record_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'AM clock out successful',
            'time' => formatTime($currentDateTime),
            'status' => $amOutStatus
        ]);

    } elseif ($period === 'pm_in') {
        // Clock in PM
        if (!$todayRecord['clock_out_am']) {
            echo json_encode(['success' => false, 'message' => 'Please clock out AM first']);
            exit();
        }

        if ($todayRecord['clock_in_pm']) {
            echo json_encode(['success' => false, 'message' => 'Already clocked in for PM']);
            exit();
        }

        // Calculate PM IN status
        $officialPMIn = date('Y-m-d') . ' ' . $officialTime['pm_time_in'];
        $pmInStatus = calculateStatus($currentDateTime, $officialPMIn, $officialTime['grace_period_minutes']);

        $stmt = $conn->prepare("UPDATE time_record SET clock_in_pm = ?, pm_in_status = ? WHERE record_id = ?");
        $stmt->execute([$currentDateTime, $pmInStatus, $todayRecord['record_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'PM clock in successful',
            'time' => formatTime($currentDateTime),
            'status' => $pmInStatus
        ]);

    } elseif ($period === 'pm') {
        // Clock out PM
        if (!$todayRecord['clock_in_pm']) {
            echo json_encode(['success' => false, 'message' => 'Please clock in PM first']);
            exit();
        }

        if ($todayRecord['clock_out_pm']) {
            echo json_encode(['success' => false, 'message' => 'Already clocked out for PM']);
            exit();
        }

        // Calculate PM OUT status (check for overtime or early leave)
        $officialPMOut = date('Y-m-d') . ' ' . $officialTime['pm_time_out'];
        $pmOutStatus = calculateClockOutStatus($currentDateTime, $officialPMOut, $officialTime['grace_period_minutes']);

        $stmt = $conn->prepare("UPDATE time_record SET clock_out_pm = ?, pm_out_status = ? WHERE record_id = ?");
        $stmt->execute([$currentDateTime, $pmOutStatus, $todayRecord['record_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'PM clock out successful',
            'time' => formatTime($currentDateTime),
            'status' => $pmOutStatus
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid period']);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
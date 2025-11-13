<?php
/**
 * Calculate attendance status (LATE, ON TIME, OVERTIME, etc.)
 */
function calculateStatus($clockTime, $officialTime, $gracePeriodMinutes = 15) {
    if (!$clockTime || !$officialTime) {
        return 'ABSENT';
    }
    
    $clock = new DateTime($clockTime);
    $official = new DateTime($officialTime);
    $graceTime = clone $official;
    $graceTime->modify("+{$gracePeriodMinutes} minutes");
    
    // For clock in - check if late
    if ($clock > $graceTime) {
        return 'LATE';
    }
    
    return 'ON TIME';
}

/**
 * Calculate overtime status
 */
function calculateOvertimeStatus($clockOutTime, $officialTimeOut) {
    if (!$clockOutTime || !$officialTimeOut) {
        return 'ON TIME';
    }
    
    $clockOut = new DateTime($clockOutTime);
    $officialOut = new DateTime($officialTimeOut);
    
    // If clocked out after official time, it's overtime
    if ($clockOut > $officialOut) {
        return 'OVERTIME';
    }
    
    return 'ON TIME';
}

/**
 * Mask name for privacy (e.g., "Edward" becomes "Edw***")
 */
function maskName($name) {
    if (strlen($name) <= 3) {
        return $name;
    }
    
    $visible = substr($name, 0, 3);
    $masked = str_repeat('*', strlen($name) - 3);
    
    return $visible . $masked;
}

/**
 * Format datetime to readable time
 */
function formatTime($datetime, $format = 'h:i A') {
    if (!$datetime) {
        return '--';
    }
    
    $date = new DateTime($datetime);
    $date->setTimezone(new DateTimeZone('Asia/Manila'));
    return $date->format($format);
}

/**
 * Format datetime to readable date
 */
function formatDate($datetime, $format = 'F d, Y') {
    if (!$datetime) {
        return '--';
    }
    
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Calculate total hours between two times
 */
function getTotalHours($clockIn, $clockOut) {
    if (!$clockIn || !$clockOut) {
        return 0;
    }
    
    $start = new DateTime($clockIn);
    $end = new DateTime($clockOut);
    $interval = $start->diff($end);
    
    $hours = $interval->h + ($interval->days * 24);
    $minutes = $interval->i;
    
    return round($hours + ($minutes / 60), 2);
}

/**
 * Get day of week from date
 */
function getDayOfWeek($date) {
    $datetime = new DateTime($date);
    return $datetime->format('l'); // Returns full day name (e.g., "Monday")
}

/**
 * Check if employee has already clocked in today
 */
function hasClockInToday($conn, $employeeId) {
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT record_id 
        FROM time_record 
        WHERE employee_id = ? 
        AND DATE(clock_in_am) = ?
    ");
    
    $stmt->execute([$employeeId, $today]);
    return $stmt->fetch() !== false;
}

/**
 * Get today's time record for employee
 */
function getTodayRecord($conn, $employeeId) {
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT * 
        FROM time_record 
        WHERE employee_id = ? 
        AND DATE(COALESCE(clock_in_am, clock_in_pm, created_at)) = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$employeeId, $today]);
    return $stmt->fetch();
}

/**
 * Get official time for employee on specific day
 */
function getOfficialTime($conn, $employeeId, $dayOfWeek) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM official_time 
        WHERE employee_id = ? 
        AND day_of_week = ? 
        AND is_active = 1
    ");
    
    $stmt->execute([$employeeId, $dayOfWeek]);
    return $stmt->fetch();
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate alert message HTML
 */
function alertMessage($message, $type = 'info') {
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alertClass[$type] ?? 'alert-info';
    
    return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}
?>
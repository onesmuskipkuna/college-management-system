<?php
/**
 * Attendance Management
 * Allows HR personnel to track employee attendance
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require HR role
Authentication::requireRole('hr');

// Initialize variables for attendance management
$attendance_records = []; // Fetch attendance records from the database
$message = '';

// Handle form submission for marking attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $employee_id = htmlspecialchars($_POST['employee_id']);
    $date = htmlspecialchars($_POST['date']);
    $status = htmlspecialchars($_POST['status']);

    // Insert attendance record into the database (pseudo code)
    // $result = markAttendance($employee_id, $date, $status);
    // if ($result) {
    //     $message = 'Attendance marked successfully!';
    // } else {
    //     $message = 'Failed to mark attendance.';
    // }
}

// Fetch existing attendance records from the database (pseudo code)
// $attendance_records = fetchAttendanceRecords();

?>

<div class="dashboard-container">
    <h1>Attendance Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="employee_id" placeholder="Employee ID" required>
        <input type="date" name="date" required>
        <select name="status" required>
            <option value="">Select Status</option>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
        </select>
        <button type="submit">Mark Attendance</button>
    </form>

    <h2>Attendance Records</h2>
    <ul>
        <?php foreach ($attendance_records as $record): ?>
            <li>Employee ID: <?= htmlspecialchars($record['employee_id']) ?> - Date: <?= htmlspecialchars($record['date']) ?> - Status: <?= htmlspecialchars($record['status']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Employee Management
 * Allows HR personnel to manage employee records
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require HR role
Authentication::requireRole('hr');

// Initialize variables for employee management
$employees = []; // Fetch employees from the database
$message = '';

// Handle form submission for adding a new employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = htmlspecialchars($_POST['name']);
    $position = htmlspecialchars($_POST['position']);
    $department = htmlspecialchars($_POST['department']);
    $email = htmlspecialchars($_POST['email']);
    $phone = htmlspecialchars($_POST['phone']);

    // Insert employee into the database (pseudo code)
    // $result = insertEmployee($name, $position, $department, $email, $phone);
    // if ($result) {
    //     $message = 'Employee added successfully!';
    // } else {
    //     $message = 'Failed to add employee.';
    // }
}

// Fetch existing employees from the database (pseudo code)
// $employees = fetchEmployees();

?>

<div class="dashboard-container">
    <h1>Employee Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="name" placeholder="Employee Name" required>
        <input type="text" name="position" placeholder="Position" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <button type="submit">Add Employee</button>
    </form>

    <h2>Existing Employees</h2>
    <ul>
        <?php foreach ($employees as $employee): ?>
            <li><?= htmlspecialchars($employee['name']) ?> - <?= htmlspecialchars($employee['position']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

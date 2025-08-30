<?php
/**
 * Manage Assignments
 * Allows teachers to create, edit, and delete assignments
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require teacher role
Authentication::requireRole('teacher');

// Initialize variables for assignment management
$assignments = []; // Fetch assignments from the database
$message = '';

// Handle form submission for adding a new assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $assignment_title = htmlspecialchars($_POST['assignment_title']);
    $due_date = htmlspecialchars($_POST['due_date']);
    $course_id = htmlspecialchars($_POST['course_id']);

    // Insert assignment into the database (pseudo code)
    // $result = insertAssignment($assignment_title, $due_date, $course_id);
    // if ($result) {
    //     $message = 'Assignment added successfully!';
    // } else {
    //     $message = 'Failed to add assignment.';
    // }
}

// Fetch existing assignments from the database (pseudo code)
// $assignments = fetchAssignments();

?>

<div class="dashboard-container">
    <h1>Manage Assignments</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="assignment_title" placeholder="Assignment Title" required>
        <input type="date" name="due_date" required>
        <select name="course_id" required>
            <option value="">Select Course</option>
            <!-- Populate courses from the database -->
        </select>
        <button type="submit">Add Assignment</button>
    </form>

    <h2>Existing Assignments</h2>
    <ul>
        <?php foreach ($assignments as $assignment): ?>
            <li><?= htmlspecialchars($assignment['title']) ?> - Due: <?= htmlspecialchars($assignment['due_date']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

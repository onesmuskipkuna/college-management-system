<?php
/**
 * Manage Exams
 * Allows headteachers to view, add, edit, and delete exam schedules
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Initialize variables for exam management
$exams = []; // Initialize exams array
$message = '';

// Fetch exams from the database
try {
    $sql = "SELECT * FROM exams"; // Example query
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching exams: ' . $e->getMessage();
}

// Handle form submission for adding a new exam
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $exam_name = htmlspecialchars($_POST['exam_name']);
    $exam_date = htmlspecialchars($_POST['exam_date']);
    $course_id = htmlspecialchars($_POST['course_id']);

    // Insert exam into the database
    try {
        $sql = "INSERT INTO exams (exam_name, exam_date, course_id) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssi", $exam_name, $exam_date, $course_id);
        $stmt->execute();
        $message = 'Exam added successfully!';
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

?>

<div class="dashboard-container">
    <h1>Manage Exams</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="exam_name" placeholder="Exam Name" required>
        <input type="date" name="exam_date" required>
        <select name="course_id" required>
            <option value="">Select Course</option>
            <!-- Populate courses from the database -->
        </select>
        <button type="submit">Add Exam</button>
    </form>

    <h2>Existing Exams</h2>
    <ul>
        <?php foreach ($exams as $exam): ?>
            <li><?= htmlspecialchars($exam['exam_name']) ?> on <?= htmlspecialchars($exam['exam_date']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Course Registration
 * Allows students to register for courses
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();

// Fetch available courses (pseudo code)
// $available_courses = fetchAvailableCourses();

// Handle course registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = htmlspecialchars($_POST['course_id']);

    // Register for the course (pseudo code)
    // $result = registerForCourse($user['id'], $course_id);
    // if ($result) {
    //     $message = 'Successfully registered for the course!';
    // } else {
    //     $message = 'Failed to register for the course.';
    // }
}

?>

<div class="dashboard-container">
    <h1>Course Registration</h1>
    <p><?= $message ?? '' ?></p>

    <form method="POST">
        <select name="course_id" required>
            <option value="">Select Course</option>
            <!-- Populate available courses from the database -->
        </select>
        <button type="submit">Register</button>
    </form>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

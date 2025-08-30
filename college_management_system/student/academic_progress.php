<?php
/**
 * Academic Progress
 * Displays the academic progress of students
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();

// Fetch academic progress data (pseudo code)
// $academic_progress = fetchAcademicProgress($user['id']);

?>

<div class="dashboard-container">
    <h1>Academic Progress Overview</h1>
    <p>Your current academic progress is displayed below.</p>

    <!-- Display academic progress data here -->
    <div class="progress-section">
        <h2>Progress Summary</h2>
        <ul>
            <!-- Loop through academic progress data and display -->
            <!-- Example: -->
            <!-- <li>Course: <?= htmlspecialchars($academic_progress['course_name']) ?> - Grade: <?= htmlspecialchars($academic_progress['grade']) ?></li> -->
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

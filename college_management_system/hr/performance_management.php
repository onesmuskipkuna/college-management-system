<?php
/**
 * Performance Management
 * Allows HR personnel to manage employee performance reviews
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require HR role
Authentication::requireRole('hr');

// Initialize variables for performance management
$performance_reviews = []; // Fetch performance reviews from the database
$message = '';

// Handle form submission for adding a new performance review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $employee_id = htmlspecialchars($_POST['employee_id']);
    $review_period = htmlspecialchars($_POST['review_period']);
    $rating = htmlspecialchars($_POST['rating']);
    $comments = htmlspecialchars($_POST['comments']);

    // Insert performance review into the database (pseudo code)
    // $result = addPerformanceReview($employee_id, $review_period, $rating, $comments);
    // if ($result) {
    //     $message = 'Performance review added successfully!';
    // } else {
    //     $message = 'Failed to add performance review.';
    // }
}

// Fetch existing performance reviews from the database (pseudo code)
// $performance_reviews = fetchPerformanceReviews();

?>

<div class="dashboard-container">
    <h1>Performance Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="employee_id" placeholder="Employee ID" required>
        <input type="text" name="review_period" placeholder="Review Period" required>
        <input type="number" name="rating" placeholder="Rating (1-5)" min="1" max="5" required>
        <textarea name="comments" placeholder="Comments" required></textarea>
        <button type="submit">Add Performance Review</button>
    </form>

    <h2>Performance Reviews</h2>
    <ul>
        <?php foreach ($performance_reviews as $review): ?>
            <li>Employee ID: <?= htmlspecialchars($review['employee_id']) ?> - Rating: <?= htmlspecialchars($review['rating']) ?> - Period: <?= htmlspecialchars($review['review_period']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

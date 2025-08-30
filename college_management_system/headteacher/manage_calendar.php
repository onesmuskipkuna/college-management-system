<?php
/**
 * Manage Academic Calendar
 * Create, edit, and delete academic terms and important dates
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

$user = Authentication::getCurrentUser();

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submission for adding/editing academic terms
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term_name = sanitizeInput($_POST['term_name'] ?? '');
    $start_date = sanitizeInput($_POST['start_date'] ?? '');
    $end_date = sanitizeInput($_POST['end_date'] ?? '');
    
    // Validation
    if (empty($term_name) || empty($start_date) || empty($end_date)) {
        $error_message = 'All fields are required.';
    } else {
        try {
            // Insert or update academic term in the database
            $query = "INSERT INTO academic_terms (term_name, start_date, end_date) VALUES (?, ?, ?)";
            $params = [$term_name, $start_date, $end_date];
            executeQuery($query, $params);
            $success_message = 'Academic term added successfully!';
        } catch (Exception $e) {
            $error_message = 'Error adding academic term: ' . $e->getMessage();
        }
    }
}

// Fetch existing academic terms
$academic_terms = fetchAll("SELECT * FROM academic_terms ORDER BY start_date ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Academic Calendar</h1>
                <p class="text-muted">Create, edit, and delete academic terms and important dates.</p>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Add New Academic Term</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="term_name" class="form-label">Term Name</label>
                    <input type="text" class="form-control" id="term_name" name="term_name" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Term</button>
            </form>
        </div>
    </div>

    <h5>Existing Academic Terms</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Term Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($academic_terms as $term): ?>
            <tr>
                <td><?= htmlspecialchars($term['term_name']) ?></td>
                <td><?= htmlspecialchars($term['start_date']) ?></td>
                <td><?= htmlspecialchars($term['end_date']) ?></td>
                <td>
                    <a href="edit_calendar.php?id=<?= $term['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_calendar.php?id=<?= $term['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

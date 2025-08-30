<?php
/**
 * Manage Teachers
 * Add, edit, and delete teacher records
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

// Handle form submission for adding/editing teachers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = sanitizeInput($_POST['teacher_id'] ?? '');
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $specialization = sanitizeInput($_POST['specialization'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error_message = 'All fields are required.';
    } else {
        try {
            // Insert or update teacher in the database
            if ($teacher_id) {
                // Update existing teacher
                $query = "UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, specialization = ? WHERE id = ?";
                $params = [$first_name, $last_name, $email, $phone, $specialization, $teacher_id];
            } else {
                // Insert new teacher
                $query = "INSERT INTO teachers (first_name, last_name, email, phone, specialization) VALUES (?, ?, ?, ?, ?)";
                $params = [$first_name, $last_name, $email, $phone, $specialization];
            }
            executeQuery($query, $params);
            $success_message = 'Teacher record saved successfully!';
        } catch (Exception $e) {
            $error_message = 'Error saving teacher record: ' . $e->getMessage();
        }
    }
}

// Fetch existing teachers
$teachers = fetchAll("SELECT * FROM teachers ORDER BY last_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Teachers</h1>
                <p class="text-muted">Add, edit, or remove teacher records.</p>
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
            <h5>Add/Edit Teacher</h5>
            <form method="POST">
                <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacher_id ?? '') ?>">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="specialization" class="form-label">Specialization</label>
                    <input type="text" class="form-control" id="specialization" name="specialization" value="<?= htmlspecialchars($specialization ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Save Teacher</button>
            </form>
        </div>
    </div>

    <h5>Existing Teachers</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Specialization</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($teachers as $teacher): ?>
            <tr>
                <td><?= htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']) ?></td>
                <td><?= htmlspecialchars($teacher['email']) ?></td>
                <td><?= htmlspecialchars($teacher['phone']) ?></td>
                <td><?= htmlspecialchars($teacher['specialization']) ?></td>
                <td>
                    <a href="?teacher_id=<?= $teacher['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_teacher.php?id=<?= $teacher['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

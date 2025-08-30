<?php
/**
 * Manage Student Admissions
 * Add, edit, and delete student admission records
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $student_id = (int)$_POST['student_id'] ?? 0;
            $first_name = sanitizeInput($_POST['first_name'] ?? '');
            $last_name = sanitizeInput($_POST['last_name'] ?? '');
            $course_id = (int)$_POST['course_id'] ?? 0;
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if (empty($first_name) || empty($last_name) || $course_id <= 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($student_id) {
                // Update existing student
                $query = "UPDATE students SET first_name = ?, last_name = ?, course_id = ?, status = ? WHERE id = ?";
                $params = [$first_name, $last_name, $course_id, $status, $student_id];
            } else {
                // Insert new student
                $query = "INSERT INTO students (first_name, last_name, course_id, status) VALUES (?, ?, ?, ?)";
                $params = [$first_name, $last_name, $course_id, $status];
            }

            executeQuery($query, $params);
            $message = "Student admission record has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing students
$students = fetchAll("SELECT * FROM students ORDER BY created_at DESC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Student Admissions</h1>
                <p class="text-muted">Add, edit, or remove student admission records.</p>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Add/Edit Student</h5>
            <form method="POST">
                <input type="hidden" name="student_id" value="<?= htmlspecialchars($student_id ?? '') ?>">
                <div class="mb-3">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select class="form-select" id="course_id" name="course_id" required>
                        <option value="">Select Course</option>
                        <?php
                        $courses = fetchAll("SELECT * FROM courses WHERE status = 'active'");
                        foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= (isset($course_id) && $course_id == $course['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= (isset($status) && $status === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (isset($status) && $status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Student</button>
            </form>
        </div>
    </div>

    <h5>Existing Students</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Course</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?= htmlspecialchars($student['first_name']) ?></td>
                <td><?= htmlspecialchars($student['last_name']) ?></td>
                <td><?= htmlspecialchars(fetchOne("SELECT course_name FROM courses WHERE id = ?", [$student['course_id']])['course_name']) ?></td>
                <td><?= htmlspecialchars($student['status']) ?></td>
                <td>
                    <a href="?student_id=<?= $student['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_student.php?id=<?= $student['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Manage Student Allocations
 * Assign students to transport vehicles/routes
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require transport role
Authentication::requireRole('transport');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $allocation_id = (int)$_POST['allocation_id'] ?? 0;
            $student_id = (int)$_POST['student_id'] ?? 0;
            $vehicle_id = (int)$_POST['vehicle_id'] ?? 0;

            if ($student_id <= 0 || $vehicle_id <= 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($allocation_id) {
                // Update existing allocation
                $query = "UPDATE student_allocations SET student_id = ?, vehicle_id = ? WHERE id = ?";
                $params = [$student_id, $vehicle_id, $allocation_id];
            } else {
                // Insert new allocation
                $query = "INSERT INTO student_allocations (student_id, vehicle_id) VALUES (?, ?)";
                $params = [$student_id, $vehicle_id];
            }

            executeQuery($query, $params);
            $message = "Student allocation has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing allocations
$allocations = fetchAll("SELECT * FROM student_allocations ORDER BY student_id ASC");
$students = fetchAll("SELECT * FROM students ORDER BY name ASC");
$vehicles = fetchAll("SELECT * FROM vehicles ORDER BY vehicle_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Student Allocations</h1>
                <p class="text-muted">Assign students to transport vehicles/routes.</p>
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
            <h5>Add/Edit Allocation</h5>
            <form method="POST">
                <input type="hidden" name="allocation_id" value="<?= htmlspecialchars($allocation_id ?? '') ?>">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select a student</option>
                        <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>" <?= ($student_id ?? 0) === $student['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($student['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="vehicle_id" class="form-label">Vehicle</label>
                    <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                        <option value="">Select a vehicle</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= $vehicle['id'] ?>" <?= ($vehicle_id ?? 0) === $vehicle['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['vehicle_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Allocation</button>
            </form>
        </div>
    </div>

    <h5>Existing Allocations</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Student</th>
                <th>Vehicle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($allocations as $allocation): ?>
            <tr>
                <td><?= htmlspecialchars(fetchOne("SELECT name FROM students WHERE id = ?", [$allocation['student_id']])['name']) ?></td>
                <td><?= htmlspecialchars(fetchOne("SELECT vehicle_name FROM vehicles WHERE id = ?", [$allocation['vehicle_id']])['vehicle_name']) ?></td>
                <td>
                    <a href="?allocation_id=<?= $allocation['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_allocation.php?id=<?= $allocation['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

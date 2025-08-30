<?php
/**
 * Manage Vehicles
 * Add, edit, and delete vehicle records
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
            $vehicle_id = (int)$_POST['vehicle_id'] ?? 0;
            $vehicle_name = sanitizeInput($_POST['vehicle_name'] ?? '');
            $capacity = (int)$_POST['capacity'] ?? 0;
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if (empty($vehicle_name) || $capacity <= 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($vehicle_id) {
                // Update existing vehicle
                $query = "UPDATE vehicles SET vehicle_name = ?, capacity = ?, status = ? WHERE id = ?";
                $params = [$vehicle_name, $capacity, $status, $vehicle_id];
            } else {
                // Insert new vehicle
                $query = "INSERT INTO vehicles (vehicle_name, capacity, status) VALUES (?, ?, ?)";
                $params = [$vehicle_name, $capacity, $status];
            }

            executeQuery($query, $params);
            $message = "Vehicle has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing vehicles
$vehicles = fetchAll("SELECT * FROM vehicles ORDER BY vehicle_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Vehicles</h1>
                <p class="text-muted">Add, edit, or remove vehicle records.</p>
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
            <h5>Add/Edit Vehicle</h5>
            <form method="POST">
                <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($vehicle_id ?? '') ?>">
                <div class="mb-3">
                    <label for="vehicle_name" class="form-label">Vehicle Name</label>
                    <input type="text" class="form-control" id="vehicle_name" name="vehicle_name" value="<?= htmlspecialchars($vehicle_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?= htmlspecialchars($capacity ?? 0) ?>" min="1" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </form>
        </div>
    </div>

    <h5>Existing Vehicles</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Vehicle Name</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicles as $vehicle): ?>
            <tr>
                <td><?= htmlspecialchars($vehicle['vehicle_name']) ?></td>
                <td><?= htmlspecialchars($vehicle['capacity']) ?></td>
                <td><?= htmlspecialchars($vehicle['status']) ?></td>
                <td>
                    <a href="?vehicle_id=<?= $vehicle['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

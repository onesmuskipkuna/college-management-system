<?php
/**
 * Manage Routes
 * Add, edit, and delete transport routes
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
            $route_id = (int)$_POST['route_id'] ?? 0;
            $route_name = sanitizeInput($_POST['route_name'] ?? '');
            $vehicle_id = (int)$_POST['vehicle_id'] ?? 0;
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if (empty($route_name) || $vehicle_id <= 0) {
                throw new Exception("All fields are required and must be valid.");
            }

            if ($route_id) {
                // Update existing route
                $query = "UPDATE routes SET route_name = ?, vehicle_id = ?, status = ? WHERE id = ?";
                $params = [$route_name, $vehicle_id, $status, $route_id];
            } else {
                // Insert new route
                $query = "INSERT INTO routes (route_name, vehicle_id, status) VALUES (?, ?, ?)";
                $params = [$route_name, $vehicle_id, $status];
            }

            executeQuery($query, $params);
            $message = "Route has been successfully saved.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch existing routes
$routes = fetchAll("SELECT * FROM routes ORDER BY route_name ASC");
$vehicles = fetchAll("SELECT * FROM vehicles ORDER BY vehicle_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Routes</h1>
                <p class="text-muted">Add, edit, or remove transport routes.</p>
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
            <h5>Add/Edit Route</h5>
            <form method="POST">
                <input type="hidden" name="route_id" value="<?= htmlspecialchars($route_id ?? '') ?>">
                <div class="mb-3">
                    <label for="route_name" class="form-label">Route Name</label>
                    <input type="text" class="form-control" id="route_name" name="route_name" value="<?= htmlspecialchars($route_name ?? '') ?>" required>
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
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Route</button>
            </form>
        </div>
    </div>

    <h5>Existing Routes</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Route Name</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $route): ?>
            <tr>
                <td><?= htmlspecialchars($route['route_name']) ?></td>
                <td><?= htmlspecialchars(fetchOne("SELECT vehicle_name FROM vehicles WHERE id = ?", [$route['vehicle_id']])['vehicle_name']) ?></td>
                <td><?= htmlspecialchars($route['status']) ?></td>
                <td>
                    <a href="?route_id=<?= $route['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_route.php?id=<?= $route['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

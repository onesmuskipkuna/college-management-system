<?php
/**
 * Resource Management
 * Manage educational resources including textbooks and digital materials
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

// Handle form submission for adding/editing resources
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resource_id = sanitizeInput($_POST['resource_id'] ?? '');
    $resource_name = sanitizeInput($_POST['resource_name'] ?? '');
    $resource_type = sanitizeInput($_POST['resource_type'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // Validation
    if (empty($resource_name) || empty($resource_type)) {
        $error_message = 'Resource name and type are required.';
    } else {
        try {
            // Insert or update resource in the database
            if ($resource_id) {
                // Update existing resource
                $query = "UPDATE resources SET resource_name = ?, resource_type = ?, description = ? WHERE id = ?";
                $params = [$resource_name, $resource_type, $description, $resource_id];
            } else {
                // Insert new resource
                $query = "INSERT INTO resources (resource_name, resource_type, description) VALUES (?, ?, ?)";
                $params = [$resource_name, $resource_type, $description];
            }
            executeQuery($query, $params);
            $success_message = 'Resource saved successfully!';
        } catch (Exception $e) {
            $error_message = 'Error saving resource: ' . $e->getMessage();
        }
    }
}

// Fetch existing resources
$resources = fetchAll("SELECT * FROM resources ORDER BY resource_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Manage Resources</h1>
                <p class="text-muted">Add, edit, or remove educational resources.</p>
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
            <h5>Add/Edit Resource</h5>
            <form method="POST">
                <input type="hidden" name="resource_id" value="<?= htmlspecialchars($resource_id ?? '') ?>">
                <div class="mb-3">
                    <label for="resource_name" class="form-label">Resource Name</label>
                    <input type="text" class="form-control" id="resource_name" name="resource_name" value="<?= htmlspecialchars($resource_name ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="resource_type" class="form-label">Resource Type</label>
                    <input type="text" class="form-control" id="resource_type" name="resource_type" value="<?= htmlspecialchars($resource_type ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Resource</button>
            </form>
        </div>
    </div>

    <h5>Existing Resources</h5>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Resource Name</th>
                <th>Resource Type</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resources as $resource): ?>
            <tr>
                <td><?= htmlspecialchars($resource['resource_name']) ?></td>
                <td><?= htmlspecialchars($resource['resource_type']) ?></td>
                <td><?= htmlspecialchars($resource['description']) ?></td>
                <td>
                    <a href="?resource_id=<?= $resource['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_resource.php?id=<?= $resource['id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

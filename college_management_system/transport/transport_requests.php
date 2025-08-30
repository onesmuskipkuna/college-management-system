<?php
/**
 * Transport Requests
 * Allow students to request transport services
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'request_transport') {
            $route_id = (int)$_POST['route_id'];
            $student_id = $user['id'];

            if ($route_id <= 0) {
                throw new Exception("Please select a valid route.");
            }

            // Create transport request record
            $request_data = [
                'student_id' => $student_id,
                'route_id' => $route_id,
                'request_date' => date('Y-m-d'),
                'status' => 'pending'
            ];

            insertRecord('transport_requests', $request_data);
            $message = "Transport request has been successfully submitted.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch available routes for transport
$routes = fetchAll("SELECT * FROM routes ORDER BY route_name ASC");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Request Transport</h1>
                <p class="text-muted">Select a route to request transport services.</p>
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
            <h5>Select Route</h5>
            <form method="POST">
                <div class="mb-3">
                    <label for="route_id" class="form-label">Route</label>
                    <select class="form-select" id="route_id" name="route_id" required>
                        <option value="">Select a route</option>
                        <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['route_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" name="action" value="request_transport">Request Transport</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

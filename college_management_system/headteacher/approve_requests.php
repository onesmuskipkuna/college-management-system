<?php
/**
 * Approve Requests
 * Allows headteachers to review and process various pending requests
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Initialize variables for requests management
$pending_requests = []; // Initialize pending requests array
$message = '';

// Fetch pending requests from the database
try {
    $sql = "SELECT * FROM requests WHERE status = 'pending'"; // Example query
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_requests = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching pending requests: ' . $e->getMessage();
}

// Handle approval/rejection of requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = htmlspecialchars($_POST['request_id']);
    $action = htmlspecialchars($_POST['action']);

    // Process approval or rejection
    try {
        if ($action === 'approve') {
            $sql = "UPDATE requests SET status = 'approved' WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $message = 'Request approved successfully!';
        } else {
            $sql = "UPDATE requests SET status = 'rejected' WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $message = 'Request rejected.';
        }
    } catch (Exception $e) {
        $message = 'Error processing request: ' . $e->getMessage();
    }
}

?>

<div class="dashboard-container">
    <h1>Approve Requests</h1>
    <p><?= $message ?></p>

    <h2>Pending Requests</h2>
    <ul>
        <?php foreach ($pending_requests as $request): ?>
            <li>
                <?= htmlspecialchars($request['description']) ?>
                <form method="POST">
                    <input type="hidden" name="request_id" value="<?= htmlspecialchars($request['id']) ?>">
                    <button type="submit" name="action" value="approve">Approve</button>
                    <button type="submit" name="action" value="reject">Reject</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

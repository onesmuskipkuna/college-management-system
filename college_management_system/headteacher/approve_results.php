<?php
/**
 * Approve Results
 * Allows headteachers to review and approve/reject pending exam results
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Initialize variables for results management
$pending_results = []; // Initialize pending results array
$message = '';

// Fetch pending results from the database
try {
    $sql = "SELECT * FROM results WHERE status = 'pending'"; // Example query
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_results = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching pending results: ' . $e->getMessage();
}

// Handle approval/rejection of results
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result_id = htmlspecialchars($_POST['result_id']);
    $action = htmlspecialchars($_POST['action']);

    // Process approval or rejection
    try {
        if ($action === 'approve') {
            $sql = "UPDATE results SET status = 'approved' WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $result_id);
            $stmt->execute();
            $message = 'Result approved successfully!';
        } else {
            $sql = "UPDATE results SET status = 'rejected' WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $result_id);
            $stmt->execute();
            $message = 'Result rejected.';
        }
    } catch (Exception $e) {
        $message = 'Error processing request: ' . $e->getMessage();
    }
}

?>

<div class="dashboard-container">
    <h1>Approve Results</h1>
    <p><?= $message ?></p>

    <h2>Pending Results</h2>
    <ul>
        <?php foreach ($pending_results as $result): ?>
            <li>
                <?= htmlspecialchars($result['student_name']) ?> - <?= htmlspecialchars($result['exam_name']) ?>
                <form method="POST">
                    <input type="hidden" name="result_id" value="<?= htmlspecialchars($result['id']) ?>">
                    <button type="submit" name="action" value="approve">Approve</button>
                    <button type="submit" name="action" value="reject">Reject</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

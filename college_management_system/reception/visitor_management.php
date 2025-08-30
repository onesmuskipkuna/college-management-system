<?php
/**
 * Visitor Management
 * Allows reception to manage visitor check-ins and check-outs
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require reception role
Authentication::requireRole('reception');

// Initialize variables for visitor management
$message = '';

// Handle form submission for visitor check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $visitor_name = htmlspecialchars($_POST['visitor_name']);
    $check_in_time = htmlspecialchars($_POST['check_in_time']);
    $purpose = htmlspecialchars($_POST['purpose']);

    // Insert visitor into the database
    $result = insertRecord('visitors', [
        'name' => $visitor_name,
        'check_in_time' => $check_in_time,
        'purpose' => $purpose
    ]);
    if ($result) {
        $message = 'Visitor checked in successfully!';
    } else {
        $message = 'Failed to check in visitor.';
    }

    // Fetch current visitors from the database
    $current_visitors = fetchAll("SELECT * FROM visitors");
}

// Fetch current visitors from the database (pseudo code)
// $current_visitors = fetchCurrentVisitors();

?>

<div class="dashboard-container">
    <h1>Visitor Management</h1>
    <p><?= $message ?></p>

    <form method="POST">
        <input type="text" name="visitor_name" placeholder="Enter Visitor Name" required>
        <input type="datetime-local" name="check_in_time" required>
        <input type="text" name="purpose" placeholder="Purpose of Visit" required>
        <button type="submit">Check In</button>
    </form>

    <h2>Current Visitors</h2>
    <ul>
        <?php // foreach ($current_visitors as $visitor): ?>
            <li>
                <?= htmlspecialchars($visitor['name']) ?> - Checked in at <?= htmlspecialchars($visitor['check_in_time']) ?>
            </li>
        <?php // endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

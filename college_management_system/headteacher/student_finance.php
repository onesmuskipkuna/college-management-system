<?php
/**
 * Student Finance
 * Displays an overview of student financial records
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Initialize variables for finance management
$financial_records = []; // Initialize financial records array
$message = '';

// Fetch financial records from the database
try {
    $sql = "SELECT * FROM student_finance"; // Example query
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $financial_records = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching financial records: ' . $e->getMessage();
}

?>

<div class="dashboard-container">
    <h1>Student Financial Reports</h1>
    <p><?= $message ?></p>

    <h2>Financial Overview</h2>
    <ul>
        <?php foreach ($financial_records as $record): ?>
            <li>
                <?= htmlspecialchars($record['student_name']) ?> - Balance: <?= htmlspecialchars($record['balance']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

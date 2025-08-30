<?php
/**
 * Fee Balance
 * Displays the fee balance for students
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();

// Fetch fee balance data (pseudo code)
// $fee_balance = fetchFeeBalance($user['id']);

?>

<div class="dashboard-container">
    <h1>Fee Balance Overview</h1>
    <p>Your current fee balance is: KSh <?= number_format($fee_balance ?? 0) ?></p>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

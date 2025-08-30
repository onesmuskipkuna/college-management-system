<?php
/**
 * Reception Overview
 * Provides a summary of reception operations and statistics
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require reception role
Authentication::requireRole('reception');

// Initialize variables for reception statistics
$totalVisitors = 0; // Fetch total visitors from the database
$pendingInquiries = 0; // Fetch pending inquiries from the database
$notificationCount = 0; // Fetch notifications count from the database

// Fetch statistics from the database (pseudo code)
// $totalVisitors = fetchTotalVisitors();
// $pendingInquiries = fetchPendingInquiries();
// $notificationCount = fetchNotificationCount();

?>

<div class="dashboard-container">
    <h1>Reception Overview</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= htmlspecialchars($totalVisitors) ?></h3>
            <p>Total Visitors</p>
        </div>
        <div class="stat-card">
            <h3><?= htmlspecialchars($pendingInquiries) ?></h3>
            <p>Pending Inquiries</p>
        </div>
        <div class="stat-card">
            <h3><?= htmlspecialchars($notificationCount) ?></h3>
            <p>Notifications</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Hostel Overview
 * Provides a summary of hostel occupancy and notifications
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

// Fetch hostel statistics (pseudo code)
// $occupancy = fetchOccupancy();
// $pending_requests = fetchPendingRequests();
// $notifications = fetchNotifications();

?>

<div class="dashboard-container">
    <h1>Hostel Overview</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $occupancy['total'] ?? 0 ?></h3>
            <p>Total Occupancy</p>
        </div>
        <div class="stat-card">
            <h3><?= $pending_requests['total'] ?? 0 ?></h3>
            <p>Pending Requests</p>
        </div>
        <div class="stat-card">
            <h3><?= $notifications['total'] ?? 0 ?></h3>
            <p>Notifications</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

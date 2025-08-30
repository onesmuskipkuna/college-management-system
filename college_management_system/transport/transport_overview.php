<?php
/**
 * Transport Overview
 * Provides a summary of transport operations and statistics
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

    // Fetch transport statistics from the database
    $total_vehicles = fetchOne("SELECT COUNT(*) as count FROM vehicles");
    $available_vehicles = fetchOne("SELECT COUNT(*) as count FROM vehicles WHERE available = 1");
    $pending_requests = fetchOne("SELECT COUNT(*) as count FROM transport_requests WHERE status = 'pending'");

?>

<div class="dashboard-container">
    <h1>Transport Overview</h1>
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?= $total_vehicles['count'] ?? 0 ?></h3>
            <p>Total Vehicles</p>
        </div>
        <div class="stat-card">
            <h3><?= $available_vehicles['count'] ?? 0 ?></h3>
            <p>Available Vehicles</p>
        </div>
        <div class="stat-card">
            <h3><?= $pending_requests['count'] ?? 0 ?></h3>
            <p>Pending Requests</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>

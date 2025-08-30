<?php
/**
 * Transport Management Dashboard
 * Manage transport schedules and student allocations
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require transport role
Authentication::requireRole('transport');

$user = Authentication::getCurrentUser();

// Get transport statistics
try {
    $stats = [
        'total_vehicles' => fetchOne("SELECT COUNT(*) as count FROM transport_vehicles")['count'] ?? 0,
        'active_routes' => fetchOne("SELECT COUNT(*) as count FROM transport_routes WHERE status = 'active'")['count'] ?? 0,
        'total_students' => fetchOne("SELECT COUNT(*) as count FROM students")['count'] ?? 0,
        'pending_requests' => fetchOne("SELECT COUNT(*) as count FROM transport_requests WHERE status = 'pending'")['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_vehicles' => 0, 'active_routes' => 0, 'total_students' => 0, 'pending_requests' => 0];
}

// Get recent transport requests
$recent_requests = fetchAll("SELECT * FROM transport_requests ORDER BY request_date DESC LIMIT 5");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Transport Management Dashboard</h1>
                <p class="text-muted">Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Transport Manager') ?>!</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">🚍</div>
                <div class="stat-content">
                    <h3><?= $stats['total_vehicles'] ?></h3>
                    <p>Total Vehicles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">🛣️</div>
                <div class="stat-content">
                    <h3><?= $stats['active_routes'] ?></h3>
                    <p>Active Routes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon">👨‍🎓</div>
                <div class="stat-content">
                    <h3><?= $stats['total_students'] ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card urgent">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?= $stats['pending_requests'] ?></h3>
                    <p>Pending Requests</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transport Requests -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transport Requests</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($recent_requests as $request): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($request['student_name']) ?></strong> - <?= htmlspecialchars($request['route']) ?> (<?= htmlspecialchars($request['request_date']) ?>)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container-fluid {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
}

.row {
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
}
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>

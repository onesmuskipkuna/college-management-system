<?php
/**
 * Hostel Manager Dashboard
 * Hostel operations and student accommodation management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require hostel role
Authentication::requireRole('hostel');

$user = Authentication::getCurrentUser();

// Get dashboard statistics
try {
    $stats = [
        'total_rooms' => 120, // Demo data
        'occupied_rooms' => 95, // Demo data
        'available_rooms' => 25, // Demo data
        'maintenance_rooms' => 5, // Demo data
        'total_residents' => 180, // Demo data
        'pending_applications' => 12 // Demo data
    ];
    
    $stats['occupancy_rate'] = round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100, 1);
} catch (Exception $e) {
    $stats = ['total_rooms' => 0, 'occupied_rooms' => 0, 'available_rooms' => 0, 'maintenance_rooms' => 0, 'total_residents' => 0, 'pending_applications' => 0, 'occupancy_rate' => 0];
}

// Get recent applications
$recent_applications = [
    ['student' => 'John Smith', 'course' => 'Computer Science', 'room_type' => 'Single', 'date' => '2024-01-15', 'status' => 'pending'],
    ['student' => 'Mary Johnson', 'course' => 'Business Management', 'room_type' => 'Double', 'date' => '2024-01-14', 'status' => 'approved'],
    ['student' => 'David Wilson', 'course' => 'Accounting', 'room_type' => 'Dormitory', 'date' => '2024-01-13', 'status' => 'pending'],
    ['student' => 'Sarah Brown', 'course' => 'Computer Science', 'room_type' => 'Single', 'date' => '2024-01-12', 'status' => 'review']
];

// Get room status overview
$room_blocks = [
    ['block' => 'Block A', 'total' => 30, 'occupied' => 28, 'available' => 2, 'maintenance' => 0],
    ['block' => 'Block B', 'total' => 30, 'occupied' => 25, 'available' => 4, 'maintenance' => 1],
    ['block' => 'Block C', 'total' => 30, 'occupied' => 22, 'available' => 6, 'maintenance' => 2],
    ['block' => 'Block D', 'total' => 30, 'occupied' => 20, 'available' => 8, 'maintenance' => 2]
];

// Get meal statistics
$meal_stats = [
    'breakfast_today' => 165,
    'lunch_today' => 172,
    'dinner_yesterday' => 168,
    'weekly_average' => 170
];

// Get maintenance requests
$maintenance_requests = [
    ['room' => 'A-101', 'issue' => 'Broken window', 'priority' => 'medium', 'reported' => '2 hours ago'],
    ['room' => 'B-205', 'issue' => 'Plumbing issue', 'priority' => 'high', 'reported' => '4 hours ago'],
    ['room' => 'C-310', 'issue' => 'Electrical problem', 'priority' => 'high', 'reported' => '1 day ago']
];
?>

<div class="dashboard-container">
    <div class="dashboard-header" style="background-color: #f0f4f8; padding: 20px; border-radius: 10px;">
        <h1>Hostel Manager Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Hostel Manager') ?>!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🏠</div>
            <div class="stat-content">
                <h3><?= $stats['total_rooms'] ?></h3>
                <p>Total Rooms</p>
            </div>
        </div>
        
        <div class="stat-card occupied">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?= $stats['occupied_rooms'] ?></h3>
                <p>Occupied Rooms</p>
            </div>
        </div>
        
        <div class="stat-card available">
            <div class="stat-icon">🟢</div>
            <div class="stat-content">
                <h3><?= $stats['available_rooms'] ?></h3>
                <p>Available Rooms</p>
            </div>
        </div>
        
        <div class="stat-card maintenance">
            <div class="stat-icon">🔧</div>
            <div class="stat-content">
                <h3><?= $stats['maintenance_rooms'] ?></h3>
                <p>Under Maintenance</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?= $stats['total_residents'] ?></h3>
                <p>Total Residents</p>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3><?= $stats['pending_applications'] ?></h3>
                <p>Pending Applications</p>
            </div>
        </div>
    </div>

    <!-- Occupancy Rate -->
    <div class="dashboard-section">
        <h2>Occupancy Overview</h2>
        <div class="occupancy-overview">
            <div class="occupancy-rate">
                <div class="rate-circle">
                    <div class="rate-text">
                        <span class="rate-number"><?= $stats['occupancy_rate'] ?>%</span>
                        <span class="rate-label">Occupancy Rate</span>
                    </div>
                </div>
            </div>
            <div class="occupancy-details">
                <div class="occupancy-item">
                    <span class="occupancy-label">Occupied:</span>
                    <span class="occupancy-value occupied"><?= $stats['occupied_rooms'] ?> rooms</span>
                </div>
                <div class="occupancy-item">
                    <span class="occupancy-label">Available:</span>
                    <span class="occupancy-value available"><?= $stats['available_rooms'] ?> rooms</span>
                </div>
                <div class="occupancy-item">
                    <span class="occupancy-label">Maintenance:</span>
                    <span class="occupancy-value maintenance"><?= $stats['maintenance_rooms'] ?> rooms</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="admit_student.php" class="action-card">
                <div class="action-icon">🏠</div>
                <h3>Admit Student</h3>
                <p>Process hostel admission applications</p>
            </a>
            
            <a href="manage_rooms.php" class="action-card">
                <div class="action-icon">🗂️</div>
                <h3>Manage Rooms</h3>
                <p>View and manage room allocations</p>
            </a>
            
            <a href="feeding_register.php" class="action-card">
                <div class="action-icon">🍽️</div>
                <h3>Feeding Register</h3>
                <p>Manage meal plans and dining records</p>
            </a>
            
            <div class="action-card" onclick="openMaintenanceModal()">
                <div class="action-icon">🔧</div>
                <h3>Maintenance Requests</h3>
                <p>Handle room maintenance and repairs</p>
            </div>
            
            <div class="action-card" onclick="openRoomSearch()">
                <div class="action-icon">🔍</div>
                <h3>Room Search</h3>
                <p>Find available rooms and check status</p>
            </div>
            
            <div class="action-card" onclick="generateReport()">
                <div class="action-icon">📊</div>
                <h3>Generate Reports</h3>
                <p>Create occupancy and financial reports</p>
            </div>
        </div>
    </div>

    <div class="dashboard-row">
        <!-- Room Block Status -->
        <div class="dashboard-section half-width">
            <h2>Room Block Status</h2>
            <div class="block-list">
                <?php foreach ($room_blocks as $block): ?>
                <div class="block-item">
                    <div class="block-header">
                        <h4><?= htmlspecialchars($block['block']) ?></h4>
                        <span class="block-total"><?= $block['occupied'] ?>/<?= $block['total'] ?></span>
                    </div>
                    <div class="block-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= ($block['occupied'] / $block['total']) * 100 ?>%"></div>
                        </div>
                    </div>
                    <div class="block-details">
                        <span class="detail-item occupied">Occupied: <?= $block['occupied'] ?></span>
                        <span class="detail-item available">Available: <?= $block['available'] ?></span>
                        <?php if ($block['maintenance'] > 0): ?>
                        <span class="detail-item maintenance">Maintenance: <?= $block['maintenance'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="dashboard-section half-width">
            <h2>Recent Applications</h2>
            <div class="application-list">
                <?php foreach ($recent_applications as $app): ?>
                <div class="application-item">
                    <div class="application-content">
                        <h4><?= htmlspecialchars($app['student']) ?></h4>
                        <p><strong>Course:</strong> <?= htmlspecialchars($app['course']) ?></p>
                        <p><strong>Room Type:</strong> <?= htmlspecialchars($app['room_type']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($app['date']) ?></p>
                        <span class="status-badge <?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                    </div>
                    <div class="application-actions">
                        <?php if ($app['status'] === 'pending'): ?>
                        <button class="btn btn-success btn-sm">Approve</button>
                        <button class="btn btn-danger btn-sm">Reject</button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm">Details</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Meal Statistics -->
    <div class="dashboard-section">
        <h2>Meal Statistics</h2>
        <div class="meal-stats">
            <div class="meal-stat-card">
                <div class="meal-icon">🌅</div>
                <div class="meal-content">
                    <h3><?= $meal_stats['breakfast_today'] ?></h3>
                    <p>Breakfast Today</p>
                </div>
            </div>
            
            <div class="meal-stat-card">
                <div class="meal-icon">☀️</div>
                <div class="meal-content">
                    <h3><?= $meal_stats['lunch_today'] ?></h3>
                    <p>Lunch Today</p>
                </div>
            </div>
            
            <div class="meal-stat-card">
                <div class="meal-icon">🌙</div>
                <div class="meal-content">
                    <h3><?= $meal_stats['dinner_yesterday'] ?></h3>
                    <p>Dinner Yesterday</p>
                </div>
            </div>
            
            <div class="meal-stat-card">
                <div class="meal-icon">📊</div>
                <div class="meal-content">
                    <h3><?= $meal_stats['weekly_average'] ?></h3>
                    <p>Weekly Average</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Requests -->
    <div class="dashboard-section">
        <h2>Maintenance Requests</h2>
        <div class="maintenance-list">
            <?php foreach ($maintenance_requests as $request): ?>
            <div class="maintenance-item">
                <div class="maintenance-content">
                    <h4>Room <?= htmlspecialchars($request['room']) ?></h4>
                    <p><?= htmlspecialchars($request['issue']) ?></p>
                    <p><strong>Reported:</strong> <?= htmlspecialchars($request['reported']) ?></p>
                    <span class="priority-badge <?= $request['priority'] ?>"><?= ucfirst($request['priority']) ?> Priority</span>
                </div>
                <div class="maintenance-actions">
                    <button class="btn btn-primary btn-sm">Assign</button>
                    <button class="btn btn-success btn-sm">Complete</button>
                    <button class="btn btn-secondary btn-sm">Details</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Room Search Modal -->
    <div id="room-search-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Room Search</h3>
                <span class="close" onclick="closeModal('room-search-modal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="search-form">
                    <div class="form-group">
                        <label for="room-type">Room Type:</label>
                        <select id="room-type">
                            <option value="">All Types</option>
                            <option value="single">Single</option>
                            <option value="double">Double</option>
                            <option value="dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room-block">Block:</label>
                        <select id="room-block">
                            <option value="">All Blocks</option>
                            <option value="A">Block A</option>
                            <option value="B">Block B</option>
                            <option value="C">Block C</option>
                            <option value="D">Block D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room-status">Status:</label>
                        <select id="room-status">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" onclick="searchRooms()">Search Rooms</button>
                </div>
                <div id="room-search-results" class="search-results"></div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-card.occupied {
    border-left: 4px solid #e74c3c;
}

.stat-card.available {
    border-left: 4px solid #27ae60;
}

.stat-card.maintenance {
    border-left: 4px solid #f39c12;
}

.stat-card.pending {
    border-left: 4px solid #3498db;
}

.stat-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.stat-content h3 {
    font-size: 2em;
    margin: 0;
    color: #2c3e50;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #7f8c8d;
}

.dashboard-section {
    margin-bottom: 30px;
}

.dashboard-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.occupancy-overview {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 40px;
    align-items: center;
}

.rate-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: conic-gradient(#3498db 0deg <?= $stats['occupancy_rate'] * 3.6 ?>deg, #ecf0f1 <?= $stats['occupancy_rate'] * 3.6 ?>deg 360deg);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.rate-circle::before {
    content: '';
    width: 120px;
    height: 120px;
    background: white;
    border-radius: 50%;
    position: absolute;
}

.rate-text {
    display: flex;
    flex-direction: column;
    align-items: center;
    z-index: 1;
}

.rate-number {
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
}

.rate-label {
    font-size: 0.9em;
    color: #7f8c8d;
}

.occupancy-details {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.occupancy-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #ecf0f1;
}

.occupancy-item:last-child {
    border-bottom: none;
}

.occupancy-label {
    font-weight: bold;
    color: #2c3e50;
}

.occupancy-value.occupied {
    color: #e74c3c;
}

.occupancy-value.available {
    color: #27ae60;
}

.occupancy-value.maintenance {
    color: #f39c12;
}

.dashboard-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.half-width {
    margin-bottom: 0;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.action-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.action-icon {
    font-size: 2.5em;
    margin-bottom: 10px;
}

.action-card h3 {
    color: #2c3e50;
    margin: 10px 0;
}

.action-card p {
    color: #7f8c8d;
    margin: 0;
}

.block-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.block-item {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
}

.block-item:last-child {
    border-bottom: none;
}

.block-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.block-header h4 {
    margin: 0;
    color: #2c3e50;
}

.block-total {
    font-weight: bold;
    color: #3498db;
}

.block-progress {
    margin-bottom: 10px;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: #ecf0f1;
    border-radius: 5px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    transition: width 0.3s ease;
}

.block-details {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
}

.detail-item.occupied {
    color: #e74c3c;
}

.detail-item.available {
    color: #27ae60;
}

.detail-item.maintenance {
    color: #f39c12;
}

.application-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.application-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.application-item:last-child {
    border-bottom: none;
}

.application-content h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.application-content p {
    margin: 2px 0;
    color: #7f8c8d;
    font-size: 0.9em;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.pending {
    background-color: #f39c12;
    color: white;
}

.status-badge.approved {
    background-color: #27ae60;
    color: white;
}

.status-badge.review {
    background-color: #3498db;
    color: white;
}

.application-actions {
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.meal-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.meal-stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.meal-icon {
    font-size: 2.5em;
    opacity: 0.8;
}

.meal-content h3 {
    font-size: 2em;
    margin: 0;
    color: #2c3e50;
}

.meal-content p {
    margin: 5px 0 0 0;
    color: #7f8c8d;
}

.maintenance-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.maintenance-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.maintenance-item:last-child {
    border-bottom: none;
}

.maintenance-content h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.maintenance-content p {
    margin: 2px 0;
    color: #7f8c8d;
    font-size: 0.9em;
}

.priority-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.priority-badge.high {
    background-color: #e74c3c;
    color: white;
}

.priority-badge.medium {
    background-color: #f39c12;
    color: white;
}

.priority-badge.low {
    background-color: #95a5a6;
    color: white;
}

.maintenance-actions {
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8em;
    transition: background-color 0.2s;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.75em;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn-success {
    background-color: #27ae60;
    color: white;
}

.btn-success:hover {
    background-color: #229954;
}

.btn-danger {
    background-color: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.search-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    color: #2c3e50;
    font-weight: bold;
}

.form-group select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.search-results {
    min-height: 100px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #e9ecef;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-row {
        grid-template-columns: 1fr;
    }
    
    .occupancy-overview {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .meal-stats {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .application-item, .maintenance-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .application-actions, .maintenance-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .search-form {
        grid-template-columns: 1fr;
    }

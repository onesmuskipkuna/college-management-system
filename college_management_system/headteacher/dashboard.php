<?php
/**
 * Head Teacher Dashboard
 * Administrative overview and management functions
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require head teacher role
Authentication::requireRole('headteacher');

$user = Authentication::getCurrentUser();

// Get dashboard statistics
try {
    $stats = [
        'total_teachers' => fetchOne("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'")['count'] ?? 0,
        'total_students' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0,
        'total_courses' => fetchOne("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 0,
        'pending_approvals' => 5 // Demo data
    ];
} catch (Exception $e) {
    $stats = ['total_teachers' => 0, 'total_students' => 0, 'total_courses' => 0, 'pending_approvals' => 0];
}

// Get recent activities
$recent_activities = [
    ['action' => 'New teacher registration approved', 'time' => '2 hours ago', 'type' => 'approval'],
    ['action' => 'Exam schedule updated', 'time' => '4 hours ago', 'type' => 'update'],
    ['action' => 'Student results approved', 'time' => '1 day ago', 'type' => 'approval'],
    ['action' => 'New course added', 'time' => '2 days ago', 'type' => 'addition']
];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Head Teacher Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Head Teacher') ?>!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-content">
                <h3><?= $stats['total_teachers'] ?></h3>
                <p>Active Teachers</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
                <h3><?= $stats['total_students'] ?></h3>
                <p>Active Students</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
                <h3><?= $stats['total_courses'] ?></h3>
                <p>Active Courses</p>
            </div>
        </div>
        
        <div class="stat-card urgent">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3><?= $stats['pending_approvals'] ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="manage_teachers.php" class="action-card">
                <div class="action-icon">👥</div>
                <h3>Manage Teachers</h3>
                <p>Add, edit, or remove teacher accounts</p>
            </a>
            
            <a href="manage_exams.php" class="action-card">
                <div class="action-icon">📝</div>
                <h3>Manage Exams</h3>
                <p>Schedule and oversee examinations</p>
            </a>
            
            <a href="approve_results.php" class="action-card">
                <div class="action-icon">✅</div>
                <h3>Approve Results</h3>
                <p>Review and approve student results</p>
            </a>
            
            <a href="calendar.php" class="action-card">
                <div class="action-icon">📅</div>
                <h3>School Calendar</h3>
                <p>Manage academic calendar and events</p>
            </a>
            
            <a href="approve_requests.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>Approve Requests</h3>
                <p>Review pending requests and applications</p>
            </a>
            
            <a href="student_finance.php" class="action-card">
                <div class="action-icon">💰</div>
                <h3>Financial Reports</h3>
                <p>View student financial summaries</p>
            </a>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="dashboard-section">
        <h2>Recent Activities</h2>
        <div class="activity-list">
            <?php foreach ($recent_activities as $activity): ?>
            <div class="activity-item <?= $activity['type'] ?>">
                <div class="activity-content">
                    <p><?= htmlspecialchars($activity['action']) ?></p>
                    <span class="activity-time"><?= htmlspecialchars($activity['time']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="dashboard-section">
        <h2>Pending Approvals</h2>
        <div class="approval-list">
            <div class="approval-item">
                <div class="approval-content">
                    <h4>Teacher Application - John Doe</h4>
                    <p>New mathematics teacher application pending review</p>
                    <div class="approval-actions">
                        <button class="btn btn-success">Approve</button>
                        <button class="btn btn-danger">Reject</button>
                        <button class="btn btn-secondary">Review</button>
                    </div>
                </div>
            </div>
            
            <div class="approval-item">
                <div class="approval-content">
                    <h4>Exam Results - Computer Science</h4>
                    <p>Final exam results submitted by Prof. Smith</p>
                    <div class="approval-actions">
                        <button class="btn btn-success">Approve</button>
                        <button class="btn btn-danger">Reject</button>
                        <button class="btn btn-secondary">Review</button>
                    </div>
                </div>
            </div>
            
            <div class="approval-item">
                <div class="approval-content">
                    <h4>Course Modification Request</h4>
                    <p>Request to update Business Management curriculum</p>
                    <div class="approval-actions">
                        <button class="btn btn-success">Approve</button>
                        <button class="btn btn-danger">Reject</button>
                        <button class="btn btn-secondary">Review</button>
                    </div>
                </div>
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.stat-card.urgent {
    border-left: 4px solid #e74c3c;
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
    color: #6c757d;
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
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.activity-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activity-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-content p {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.activity-time {
    color: #7f8c8d;
    font-size: 0.9em;
}

.approval-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.approval-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
}

.approval-item:last-child {
    border-bottom: none;
}

.approval-content p {
    color: #7f8c8d;
    margin: 5px 0 15px 0;
}

.approval-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.2s;
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

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .approval-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>

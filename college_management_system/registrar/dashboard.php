<?php
/**
 * Registrar Dashboard
 * Student admissions and academic administration
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();

// Get dashboard statistics
try {
    $stats = [
        'total_students' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0,
        'new_applications' => 12, // Demo data
        'pending_certificates' => 8, // Demo data
        'total_courses' => fetchOne("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 0
    ];
} catch (Exception $e) {
    $stats = ['total_students' => 0, 'new_applications' => 0, 'pending_certificates' => 0, 'total_courses' => 0];
}

// Get recent applications
$recent_applications = [
    ['name' => 'Jane Smith', 'course' => 'Computer Science', 'date' => '2024-01-15', 'status' => 'pending'],
    ['name' => 'John Doe', 'course' => 'Business Management', 'date' => '2024-01-14', 'status' => 'approved'],
    ['name' => 'Mary Johnson', 'course' => 'Accounting', 'date' => '2024-01-13', 'status' => 'pending'],
    ['name' => 'David Wilson', 'course' => 'Computer Science', 'date' => '2024-01-12', 'status' => 'review']
];

// Get pending certificates
$pending_certificates = [
    ['student' => 'Alice Brown', 'course' => 'Business Management', 'completion_date' => '2024-01-10', 'fee_status' => 'cleared'],
    ['student' => 'Bob Davis', 'course' => 'Accounting', 'completion_date' => '2024-01-08', 'fee_status' => 'pending'],
    ['student' => 'Carol White', 'course' => 'Computer Science', 'completion_date' => '2024-01-05', 'fee_status' => 'cleared']
];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Registrar Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Registrar') ?>!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
                <h3><?= $stats['total_students'] ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        
        <div class="stat-card new">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
                <h3><?= $stats['new_applications'] ?></h3>
                <p>New Applications</p>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">🎓</div>
            <div class="stat-content">
                <h3><?= $stats['pending_certificates'] ?></h3>
                <p>Pending Certificates</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
                <h3><?= $stats['total_courses'] ?></h3>
                <p>Active Courses</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="admission.php" class="action-card">
                <div class="action-icon">➕</div>
                <h3>New Student Admission</h3>
                <p>Register new students and create accounts</p>
            </a>
            
            <a href="student_management.php" class="action-card">
                <div class="action-icon">👥</div>
                <h3>Manage Students</h3>
                <p>View and edit student information</p>
            </a>
            
            <a href="manage_courses.php" class="action-card">
                <div class="action-icon">📖</div>
                <h3>Manage Courses</h3>
                <p>Add, edit, and manage academic programs</p>
            </a>
            
            <a href="certificate_issue.php" class="action-card">
                <div class="action-icon">🏆</div>
                <h3>Issue Certificates</h3>
                <p>Generate and issue completion certificates</p>
            </a>
            
            <a href="reports.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Academic Reports</h3>
                <p>Generate comprehensive academic reports</p>
            </a>
            
            <a href="student_finance.php" class="action-card">
                <div class="action-icon">💰</div>
                <h3>Student Finance</h3>
                <p>View student financial information</p>
            </a>
        </div>
    </div>

    <div class="dashboard-row">
        <!-- Recent Applications -->
        <div class="dashboard-section half-width">
            <h2>Recent Applications</h2>
            <div class="application-list">
                <?php foreach ($recent_applications as $app): ?>
                <div class="application-item">
                    <div class="application-content">
                        <h4><?= htmlspecialchars($app['name']) ?></h4>
                        <p><strong>Course:</strong> <?= htmlspecialchars($app['course']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($app['date']) ?></p>
                        <span class="status-badge <?= $app['status'] ?>"><?= ucfirst($app['status']) ?></span>
                    </div>
                    <div class="application-actions">
                        <?php if ($app['status'] === 'pending'): ?>
                        <button class="btn btn-success btn-sm">Approve</button>
                        <button class="btn btn-danger btn-sm">Reject</button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm">View</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pending Certificates -->
        <div class="dashboard-section half-width">
            <h2>Pending Certificates</h2>
            <div class="certificate-list">
                <?php foreach ($pending_certificates as $cert): ?>
                <div class="certificate-item">
                    <div class="certificate-content">
                        <h4><?= htmlspecialchars($cert['student']) ?></h4>
                        <p><strong>Course:</strong> <?= htmlspecialchars($cert['course']) ?></p>
                        <p><strong>Completed:</strong> <?= htmlspecialchars($cert['completion_date']) ?></p>
                        <span class="fee-status <?= $cert['fee_status'] ?>">
                            Fee: <?= ucfirst($cert['fee_status']) ?>
                        </span>
                    </div>
                    <div class="certificate-actions">
                        <?php if ($cert['fee_status'] === 'cleared'): ?>
                        <button class="btn btn-success btn-sm">Issue Certificate</button>
                        <?php else: ?>
                        <button class="btn btn-warning btn-sm">Pending Fee Clearance</button>
                        <?php endif; ?>
                        <button class="btn btn-secondary btn-sm">View Details</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Course Statistics -->
    <div class="dashboard-section">
        <h2>Course Enrollment Statistics</h2>
        <div class="course-stats">
            <div class="course-stat-item">
                <h4>Computer Science Diploma</h4>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 85%"></div>
                </div>
                <p>42/50 students enrolled (85%)</p>
            </div>
            
            <div class="course-stat-item">
                <h4>Business Management Certificate</h4>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 70%"></div>
                </div>
                <p>28/40 students enrolled (70%)</p>
            </div>
            
            <div class="course-stat-item">
                <h4>Accounting Certificate</h4>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 60%"></div>
                </div>
                <p>18/30 students enrolled (60%)</p>
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

.stat-card.new {
    border-left: 4px solid #27ae60;
}

.stat-card.pending {
    border-left: 4px solid #f39c12;
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

.application-list, .certificate-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.application-item, .certificate-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.application-item:last-child, .certificate-item:last-child {
    border-bottom: none;
}

.application-content h4, .certificate-content h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.application-content p, .certificate-content p {
    margin: 2px 0;
    color: #7f8c8d;
    font-size: 0.9em;
}

.status-badge, .fee-status {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.pending, .fee-status.pending {
    background-color: #f39c12;
    color: white;
}

.status-badge.approved, .fee-status.cleared {
    background-color: #27ae60;
    color: white;
}

.status-badge.review {
    background-color: #3498db;
    color: white;
}

.application-actions, .certificate-actions {
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

.btn-warning {
    background-color: #f39c12;
    color: white;
}

.btn-warning:hover {
    background-color: #e67e22;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

.course-stats {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.course-stat-item {
    margin-bottom: 20px;
}

.course-stat-item:last-child {
    margin-bottom: 0;
}

.course-stat-item h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    transition: width 0.3s ease;
}

.course-stat-item p {
    margin: 0;
    color: #7f8c8d;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
    
    .dashboard-row {
        grid-template-columns: 1fr;
    }
    
    .application-item, .certificate-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .application-actions, .certificate-actions {
        flex-direction: row;
        width: 100%;
    }
}
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>

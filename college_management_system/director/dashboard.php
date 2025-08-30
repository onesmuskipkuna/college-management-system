<?php
/**
 * Director Dashboard
 * Executive overview and system-wide management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require director role
Authentication::requireRole('director');

$user = Authentication::getCurrentUser();

// Get comprehensive statistics
try {
    $stats = [
        'total_students' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0,
        'total_teachers' => fetchOne("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'")['count'] ?? 0,
        'total_courses' => fetchOne("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 0,
        'monthly_revenue' => 2450000, // Demo data
        'pending_approvals' => 15, // Demo data
        'system_alerts' => 3 // Demo data
    ];
} catch (Exception $e) {
    $stats = ['total_students' => 0, 'total_teachers' => 0, 'total_courses' => 0, 'monthly_revenue' => 0, 'pending_approvals' => 0, 'system_alerts' => 0];
}

// Financial overview
$financial_data = [
    'total_fees_collected' => 1850000,
    'outstanding_fees' => 600000,
    'operational_expenses' => 1200000,
    'net_profit' => 650000,
    'collection_rate' => 75.5
];

// Department performance
$department_performance = [
    ['department' => 'Academic', 'performance' => 92, 'status' => 'excellent'],
    ['department' => 'Finance', 'performance' => 88, 'status' => 'good'],
    ['department' => 'HR', 'performance' => 85, 'status' => 'good'],
    ['department' => 'Student Services', 'performance' => 90, 'status' => 'excellent'],
    ['department' => 'Hostel', 'performance' => 82, 'status' => 'satisfactory']
];

// Recent activities requiring attention
$critical_activities = [
    ['activity' => 'Certificate authorization required for 8 students', 'priority' => 'high', 'time' => '2 hours ago'],
    ['activity' => 'Budget approval needed for IT infrastructure', 'priority' => 'medium', 'time' => '4 hours ago'],
    ['activity' => 'Staff performance review pending', 'priority' => 'medium', 'time' => '1 day ago'],
    ['activity' => 'Policy update requires director approval', 'priority' => 'low', 'time' => '2 days ago']
];

// System alerts
$system_alerts = [
    ['type' => 'security', 'message' => 'Multiple failed login attempts detected', 'severity' => 'high'],
    ['type' => 'financial', 'message' => 'Fee collection target 85% achieved', 'severity' => 'medium'],
    ['type' => 'system', 'message' => 'Database backup completed successfully', 'severity' => 'low']
];

// Monthly trends
$monthly_trends = [
    'student_enrollment' => ['current' => 245, 'previous' => 238, 'change' => 2.9],
    'fee_collection' => ['current' => 1850000, 'previous' => 1720000, 'change' => 7.6],
    'staff_satisfaction' => ['current' => 87, 'previous' => 84, 'change' => 3.6],
    'academic_performance' => ['current' => 78, 'previous' => 75, 'change' => 4.0]
];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Director Dashboard</h1>
        <p>Executive Overview - <?= htmlspecialchars($user['first_name'] ?? 'Director') ?></p>
        <div class="header-stats">
            <div class="header-stat">
                <span class="stat-value"><?= $stats['total_students'] ?></span>
                <span class="stat-label">Students</span>
            </div>
            <div class="header-stat">
                <span class="stat-value"><?= $stats['total_teachers'] ?></span>
                <span class="stat-label">Teachers</span>
            </div>
            <div class="header-stat">
                <span class="stat-value">KSh <?= number_format($stats['monthly_revenue']) ?></span>
                <span class="stat-label">Monthly Revenue</span>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-section">
        <h2>Key Performance Indicators</h2>
        <div class="kpi-grid">
            <div class="kpi-card revenue">
                <div class="kpi-header">
                    <h3>Financial Performance</h3>
                    <span class="kpi-trend positive">+7.6%</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main">
                        <span class="kpi-value">KSh <?= number_format($financial_data['total_fees_collected']) ?></span>
                        <span class="kpi-label">Fees Collected</span>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-detail">
                            <span class="detail-label">Outstanding:</span>
                            <span class="detail-value">KSh <?= number_format($financial_data['outstanding_fees']) ?></span>
                        </div>
                        <div class="kpi-detail">
                            <span class="detail-label">Collection Rate:</span>
                            <span class="detail-value"><?= $financial_data['collection_rate'] ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kpi-card academic">
                <div class="kpi-header">
                    <h3>Academic Excellence</h3>
                    <span class="kpi-trend positive">+4.0%</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main">
                        <span class="kpi-value"><?= $monthly_trends['academic_performance']['current'] ?>%</span>
                        <span class="kpi-label">Average Performance</span>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-detail">
                            <span class="detail-label">Active Courses:</span>
                            <span class="detail-value"><?= $stats['total_courses'] ?></span>
                        </div>
                        <div class="kpi-detail">
                            <span class="detail-label">Student-Teacher Ratio:</span>
                            <span class="detail-value"><?= round($stats['total_students'] / max($stats['total_teachers'], 1), 1) ?>:1</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="kpi-card operational">
                <div class="kpi-header">
                    <h3>Operational Efficiency</h3>
                    <span class="kpi-trend positive">+3.6%</span>
                </div>
                <div class="kpi-content">
                    <div class="kpi-main">
                        <span class="kpi-value"><?= $monthly_trends['staff_satisfaction']['current'] ?>%</span>
                        <span class="kpi-label">Staff Satisfaction</span>
                    </div>
                    <div class="kpi-details">
                        <div class="kpi-detail">
                            <span class="detail-label">Pending Approvals:</span>
                            <span class="detail-value"><?= $stats['pending_approvals'] ?></span>
                        </div>
                        <div class="kpi-detail">
                            <span class="detail-label">System Alerts:</span>
                            <span class="detail-value"><?= $stats['system_alerts'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Executive Actions -->
    <div class="dashboard-section">
        <h2>Executive Actions</h2>
        <div class="action-grid">
            <div class="action-card critical" onclick="showApprovals()">
                <div class="action-icon">⚡</div>
                <h3>Pending Approvals</h3>
                <p>Review and authorize critical decisions</p>
                <span class="action-badge"><?= $stats['pending_approvals'] ?></span>
            <a href="executive_reports.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Executive Reports</h3>
                <p>Comprehensive reporting and analytics</p>
            </a>
            
            <a href="analytics.php" class="action-card">
                <div class="action-icon">🔧</div>
                <h3>Advanced Analytics</h3>
                <p>Detailed analytics and performance metrics</p>
            </a>
            
            <div class="action-card" onclick="showStaffManagement()">
                <div class="action-icon">👥</div>
                <h3>Staff Management</h3>
                <p>Oversee staff performance and development</p>
            </div>
            
            <div class="action-card" onclick="showPolicyManagement()">
                <div class="action-icon">📋</div>
                <h3>Policy Management</h3>
                <p>Review and update institutional policies</p>
            </div>
            
            <a href="strategic_planning.php" class="action-card">
                <div class="action-icon">🎯</div>
                <h3>Strategic Planning</h3>
                <p>Long-term planning and goal setting</p>
            </a>
            
            <a href="user_management.php" class="action-card">
                <div class="action-icon">👤</div>
                <h3>User Management</h3>
                <p>Create users and assign roles</p>
            </a>
        </div>
    </div>

    <div class="dashboard-row">
        <!-- Department Performance -->
        <div class="dashboard-section half-width">
            <h2>Department Performance</h2>
            <div class="performance-list">
                <?php foreach ($department_performance as $dept): ?>
                <div class="performance-item">
                    <div class="performance-content">
                        <h4><?= htmlspecialchars($dept['department']) ?></h4>
                        <div class="performance-bar">
                            <div class="performance-fill <?= $dept['status'] ?>" style="width: <?= $dept['performance'] ?>%"></div>
                        </div>
                        <div class="performance-details">
                            <span class="performance-score"><?= $dept['performance'] ?>%</span>
                            <span class="performance-status <?= $dept['status'] ?>"><?= ucfirst($dept['status']) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Critical Activities -->
        <div class="dashboard-section half-width">
            <h2>Requires Attention</h2>
            <div class="activity-list">
                <?php foreach ($critical_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-content">
                        <p><?= htmlspecialchars($activity['activity']) ?></p>
                        <span class="activity-time"><?= htmlspecialchars($activity['time']) ?></span>
                    </div>
                    <div class="activity-priority">
                        <span class="priority-badge <?= $activity['priority'] ?>"><?= ucfirst($activity['priority']) ?></span>
                        <button class="btn btn-primary btn-sm">Review</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- System Alerts -->
    <div class="dashboard-section">
        <h2>System Alerts</h2>
        <div class="alert-list">
            <?php foreach ($system_alerts as $alert): ?>
            <div class="alert-item <?= $alert['severity'] ?>">
                <div class="alert-icon">
                    <?php if ($alert['type'] === 'security'): ?>🔒<?php endif; ?>
                    <?php if ($alert['type'] === 'financial'): ?>💰<?php endif; ?>
                    <?php if ($alert['type'] === 'system'): ?>⚙️<?php endif; ?>
                </div>
                <div class="alert-content">
                    <p><?= htmlspecialchars($alert['message']) ?></p>
                    <span class="alert-type"><?= ucfirst($alert['type']) ?> Alert</span>
                </div>
                <div class="alert-actions">
                    <button class="btn btn-secondary btn-sm">Dismiss</button>
                    <button class="btn btn-primary btn-sm">Details</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="dashboard-section">
        <h2>Monthly Trends</h2>
        <div class="trends-grid">
            <?php foreach ($monthly_trends as $key => $trend): ?>
            <div class="trend-card">
                <h4><?= ucwords(str_replace('_', ' ', $key)) ?></h4>
                <div class="trend-value">
                    <?php if (strpos($key, 'fee') !== false): ?>
                        KSh <?= number_format($trend['current']) ?>
                    <?php else: ?>
                        <?= $trend['current'] ?><?= strpos($key, 'enrollment') === false ? '%' : '' ?>
                    <?php endif; ?>
                </div>
                <div class="trend-change <?= $trend['change'] > 0 ? 'positive' : 'negative' ?>">
                    <?= $trend['change'] > 0 ? '↗' : '↘' ?> <?= abs($trend['change']) ?>%
                </div>
                <div class="trend-comparison">
                    vs last month: 
                    <?php if (strpos($key, 'fee') !== false): ?>
                        KSh <?= number_format($trend['previous']) ?>
                    <?php else: ?>
                        <?= $trend['previous'] ?><?= strpos($key, 'enrollment') === false ? '%' : '' ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
}

.dashboard-header h1 {
    margin: 0 0 10px 0;
    font-size: 2.5em;
}

.header-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 20px;
}

.header-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-value {
    font-size: 1.8em;
    font-weight: bold;
}

.stat-label {
    font-size: 0.9em;
    opacity: 0.9;
}

.kpi-section {
    margin-bottom: 40px;
}

.kpi-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.kpi-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-left: 5px solid #3498db;
}

.kpi-card.revenue {
    border-left-color: #27ae60;
}

.kpi-card.academic {
    border-left-color: #3498db;
}

.kpi-card.operational {
    border-left-color: #f39c12;
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.kpi-header h3 {
    margin: 0;
    color: #2c3e50;
}

.kpi-trend {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
}

.kpi-trend.positive {
    background-color: #d5f4e6;
    color: #27ae60;
}

.kpi-trend.negative {
    background-color: #fdeaea;
    color: #e74c3c;
}

.kpi-main {
    margin-bottom: 15px;
}

.kpi-value {
    display: block;
    font-size: 2.2em;
    font-weight: bold;
    color: #2c3e50;
}

.kpi-label {
    color: #7f8c8d;
    font-size: 0.9em;
}

.kpi-details {
    display: flex;
    justify-content: space-between;
}

.kpi-detail {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 0.8em;
    color: #7f8c8d;
}

.detail-value {
    font-weight: bold;
    color: #2c3e50;
}

.dashboard-section {
    margin-bottom: 40px;
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
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.action-card.critical {
    border-left: 4px solid #e74c3c;
}

.action-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.action-card h3 {
    color: #2c3e50;
    margin: 10px 0;
}

.action-card p {
    color: #7f8c8d;
    margin: 0;
}

.action-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8em;
    font-weight: bold;
}

.dashboard-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 40px;
}

.half-width {
    margin-bottom: 0;
}

.performance-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.performance-item {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
}

.performance-item:last-child {
    border-bottom: none;
}

.performance-content h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.performance-bar {
    width: 100%;
    height: 12px;
    background-color: #ecf0f1;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 10px;
}

.performance-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.performance-fill.excellent {
    background: linear-gradient(90deg, #27ae60, #2ecc71);
}

.performance-fill.good {
    background: linear-gradient(90deg, #3498db, #5dade2);
}

.performance-fill.satisfactory {
    background: linear-gradient(90deg, #f39c12, #f8c471);
}

.activity-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.activity-item {
    padding: 20px;
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

.activity-priority {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.priority-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.priority-badge.high {
    background-color: #fdeaea;
    color: #e74c3c;
}

.priority-badge.medium {
    background-color: #fdebd0;
    color: #f39c12;
}

.priority-badge.low {
    background-color: #eaeded;
    color: #95a5a6;
}

.alert-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    overflow: hidden;
}

.alert-item {
    padding: 20px;
    border-bottom: 1px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.alert-item:last-child {
    border-bottom: none;
}

.alert-item.high {
    border-left: 4px solid #e74c3c;
}

.alert-item.medium {
    border-left: 4px solid #f39c12;
}

.alert-item.low {
    border-left: 4px solid #95a5a6;
}

.alert-icon {
    font-size: 1.5em;
}

.alert-content {
    flex: 1;
}

.alert-content p {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.alert-type {
    color: #7f8c8d;
    font-size: 0.9em;
}

.alert-actions {
    display: flex;
    gap: 8px;
}

.trends-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.trend-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    text-align: center;
}

.trend-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.trend-value {
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.trend-change {
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 10px;
}

.trend-change.positive {
    color: #27ae60;
}

.trend-change.negative {
    color: #e74c3c;
}

.trend-comparison {
    color: #7f8c8d;
    font-size: 0.9em;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.summary-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.progress-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: conic-gradient(#3498db 0deg 245deg, #ecf0f1 245deg 360deg);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    position: relative;
}

.progress-circle::before {
    content: '';
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    position: absolute;
}

.progress-text {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c3e50;
    z-index: 1;
}

.budget-bars {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.budget-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.budget-bar {
    flex: 1;
    height: 12px;
    background-color: #ecf0f1;
    border-radius: 6px;
    overflow: hidden;
}

.budget-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.budget-fill.revenue {
    background: linear-gradient(90deg, #27ae60, #2ecc71);
}

.budget-fill.expenses {
    background: linear-gradient(90deg, #e74c3c, #ec7063);
}

.compliance-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.compliance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.compliance-status.compliant {
    color: #27ae60;
    font-weight: bold;
}

.compliance-status.pending {
    color: #f39c12;
    font-weight: bold;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.2s;
}
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>

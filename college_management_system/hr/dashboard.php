<?php
/**
 * HR Dashboard
 * Human Resources management and employee administration
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require HR role
Authentication::requireRole('hr');

$user = Authentication::getCurrentUser();

// Get dashboard statistics
try {
    $stats = [
        'total_employees' => 45, // Demo data
        'active_employees' => 42, // Demo data
        'pending_leave' => 8, // Demo data
        'payroll_pending' => 3 // Demo data
    ];
} catch (Exception $e) {
    $stats = ['total_employees' => 0, 'active_employees' => 0, 'pending_leave' => 0, 'payroll_pending' => 0];
}

// Get recent activities
$recent_activities = [
    ['action' => 'New employee onboarded - Sarah Johnson', 'time' => '2 hours ago', 'type' => 'addition'],
    ['action' => 'Leave request approved - John Smith', 'time' => '4 hours ago', 'type' => 'approval'],
    ['action' => 'Salary advance processed - Mary Davis', 'time' => '1 day ago', 'type' => 'payment'],
    ['action' => 'Performance review completed - Mike Wilson', 'time' => '2 days ago', 'type' => 'review']
];

// Get pending leave requests
$pending_leaves = [
    ['employee' => 'Alice Brown', 'type' => 'Annual Leave', 'dates' => 'Jan 20-25, 2024', 'days' => 5],
    ['employee' => 'Bob Davis', 'type' => 'Sick Leave', 'dates' => 'Jan 18-19, 2024', 'days' => 2],
    ['employee' => 'Carol White', 'type' => 'Maternity Leave', 'dates' => 'Feb 1 - May 1, 2024', 'days' => 90]
];

// Get payroll summary
$payroll_summary = [
    ['department' => 'Teaching Staff', 'employees' => 25, 'total_salary' => 1250000],
    ['department' => 'Administrative', 'employees' => 12, 'total_salary' => 480000],
    ['department' => 'Support Staff', 'employees' => 8, 'total_salary' => 240000]
];
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>HR Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'HR Manager') ?>!</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?= $stats['total_employees'] ?></h3>
                <p>Total Employees</p>
            </div>
        </div>
        
        <div class="stat-card active">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?= $stats['active_employees'] ?></h3>
                <p>Active Employees</p>
            </div>
        </div>
        
        <div class="stat-card pending">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
                <h3><?= $stats['pending_leave'] ?></h3>
                <p>Pending Leave Requests</p>
            </div>
        </div>
        
        <div class="stat-card urgent">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3><?= $stats['payroll_pending'] ?></h3>
                <p>Payroll Pending</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="create_employee.php" class="action-card">
                <div class="action-icon">➕</div>
                <h3>Add New Employee</h3>
                <p>Create new employee records and accounts</p>
            </a>
            
            <a href="manage_leave.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>Manage Leave</h3>
                <p>Process leave requests and approvals</p>
            </a>
            
            <a href="payroll.php" class="action-card">
                <div class="action-icon">💵</div>
                <h3>Payroll Management</h3>
                <p>Generate payslips and manage salaries</p>
            </a>
            
            <a href="staff_performance.php" class="action-card">
                <div class="action-icon">📊</div>
                <h3>Performance Reviews</h3>
                <p>Conduct and track employee performance</p>
            </a>
            
            <a href="salary_advance.php" class="action-card">
                <div class="action-icon">🏦</div>
                <h3>Salary Advances</h3>
                <p>Process employee salary advance requests</p>
            </a>
            
            <a href="update_employee.php" class="action-card">
                <div class="action-icon">✏️</div>
                <h3>Update Employee Info</h3>
                <p>Edit employee details and records</p>
            </a>
        </div>
    </div>

    <div class="dashboard-row">
        <!-- Pending Leave Requests -->
        <div class="dashboard-section half-width">
            <h2>Pending Leave Requests</h2>
            <div class="leave-list">
                <?php foreach ($pending_leaves as $leave): ?>
                <div class="leave-item">
                    <div class="leave-content">
                        <h4><?= htmlspecialchars($leave['employee']) ?></h4>
                        <p><strong>Type:</strong> <?= htmlspecialchars($leave['type']) ?></p>
                        <p><strong>Dates:</strong> <?= htmlspecialchars($leave['dates']) ?></p>
                        <p><strong>Duration:</strong> <?= $leave['days'] ?> days</p>
                    </div>
                    <div class="leave-actions">
                        <button class="btn btn-success btn-sm">Approve</button>
                        <button class="btn btn-danger btn-sm">Reject</button>
                        <button class="btn btn-secondary btn-sm">Details</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="dashboard-section half-width">
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
    </div>

    <!-- Payroll Summary -->
    <div class="dashboard-section">
        <h2>Payroll Summary</h2>
        <div class="payroll-summary">
            <div class="payroll-grid">
                <?php foreach ($payroll_summary as $dept): ?>
                <div class="payroll-card">
                    <h4><?= htmlspecialchars($dept['department']) ?></h4>
                    <div class="payroll-stats">
                        <div class="payroll-stat">
                            <span class="stat-label">Employees:</span>
                            <span class="stat-value"><?= $dept['employees'] ?></span>
                        </div>
                        <div class="payroll-stat">
                            <span class="stat-label">Total Salary:</span>
                            <span class="stat-value">KSh <?= number_format($dept['total_salary']) ?></span>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm">Generate Payroll</button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="payroll-total">
                <h3>Total Monthly Payroll: KSh <?= number_format(array_sum(array_column($payroll_summary, 'total_salary'))) ?></h3>
            </div>
        </div>
    </div>

    <!-- Employee Statistics -->
    <div class="dashboard-section">
        <h2>Employee Statistics</h2>
        <div class="employee-stats">
            <div class="stat-chart">
                <h4>Department Distribution</h4>
                <div class="chart-item">
                    <span class="chart-label">Teaching Staff</span>
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 56%"></div>
                    </div>
                    <span class="chart-value">25 (56%)</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Administrative</span>
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 27%"></div>
                    </div>
                    <span class="chart-value">12 (27%)</span>
                </div>
                <div class="chart-item">
                    <span class="chart-label">Support Staff</span>
                    <div class="chart-bar">
                        <div class="chart-fill" style="width: 18%"></div>
                    </div>
                    <span class="chart-value">8 (18%)</span>
                </div>
            </div>
            
            <div class="attendance-summary">
                <h4>Today's Attendance</h4>
                <div class="attendance-stats">
                    <div class="attendance-stat present">
                        <span class="attendance-number">38</span>
                        <span class="attendance-label">Present</span>
                    </div>
                    <div class="attendance-stat absent">
                        <span class="attendance-number">4</span>
                        <span class="attendance-label">Absent</span>
                    </div>
                    <div class="attendance-stat leave">
                        <span class="attendance-number">3</span>
                        <span class="attendance-label">On Leave</span>
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

.stat-card.active {
    border-left: 4px solid #27ae60;
}

.stat-card.pending {
    border-left: 4px solid #f39c12;
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

.leave-list, .activity-list {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.leave-item, .activity-item {
    padding: 15px 20px;
    border-bottom: 1px solid #ecf0f1;
}

.leave-item:last-child, .activity-item:last-child {
    border-bottom: none;
}

.leave-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.leave-content h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.leave-content p {
    margin: 2px 0;
    color: #7f8c8d;
    font-size: 0.9em;
}

.leave-actions {
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.activity-item.addition {
    border-left: 4px solid #27ae60;
}

.activity-item.approval {
    border-left: 4px solid #3498db;
}

.activity-item.payment {
    border-left: 4px solid #f39c12;
}

.activity-item.review {
    border-left: 4px solid #9b59b6;
}

.activity-content p {
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.activity-time {
    color: #7f8c8d;
    font-size: 0.9em;
}

.payroll-summary {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.payroll-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.payroll-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #3498db;
}

.payroll-card h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.payroll-stats {
    margin-bottom: 15px;
}

.payroll-stat {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
}

.stat-value {
    font-weight: bold;
    color: #2c3e50;
}

.payroll-total {
    text-align: center;
    padding: 15px;
    background: #3498db;
    color: white;
    border-radius: 8px;
}

.payroll-total h3 {
    margin: 0;
}

.employee-stats {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.stat-chart, .attendance-summary {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stat-chart h4, .attendance-summary h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.chart-item {
    display: grid;
    grid-template-columns: 1fr 2fr auto;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.chart-label {
    font-size: 0.9em;
    color: #7f8c8d;
}

.chart-bar {
    height: 20px;
    background-color: #ecf0f1;
    border-radius: 10px;
    overflow: hidden;
}

.chart-fill {
    height: 100%;
    background: linear-gradient(90deg, #3498db, #2980b9);
    transition: width 0.3s ease;
}

.chart-value {
    font-size: 0.9em;
    color: #2c3e50;
    font-weight: bold;
}

.attendance-stats {
    display: flex;
    justify-content: space-around;
    text-align: center;
}

.attendance-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.attendance-number {
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.attendance-stat.present .attendance-number {
    color: #27ae60;
}

.attendance-stat.absent .attendance-number {
    color: #e74c3c;
}

.attendance-stat.leave .attendance-number {
    color: #f39c12;
}

.attendance-label {
    font-size: 0.9em;
    color: #7f8c8d;
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
    
    .payroll-grid {
        grid-template-columns: 1fr;
    }
    
    .employee-stats {
        grid-template-columns: 1fr;
    }
    
    .leave-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .leave-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .chart-item {
        grid-template-columns: 1fr;
        gap: 5px;
    }
    
    .attendance-stats {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<?php require_once __DIR__ . '/../footer.php'; ?>

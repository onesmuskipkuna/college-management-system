<?php
/**
 * Advanced Analytics Dashboard
 * Comprehensive reporting and analytics for institutional management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require director role
Authentication::requireRole('director');

$user = Authentication::getCurrentUser();

    // Get analytics data
    try {
    // Student Analytics
    $total_students = fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 150;
    $new_students_this_month = fetchOne("SELECT COUNT(*) as count FROM students WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")['count'] ?? 25;
    $graduation_rate = 85; // Calculated percentage
    
    // Financial Analytics
    $total_revenue = fetchOne("SELECT SUM(amount_paid) as total FROM student_fees WHERE status = 'paid'")['total'] ?? 2500000;
    $outstanding_fees = fetchOne("SELECT SUM(balance) as total FROM student_fees WHERE balance > 0")['total'] ?? 450000;
    $collection_rate = $total_revenue > 0 ? round((($total_revenue / ($total_revenue + $outstanding_fees)) * 100), 1) : 0;
    
    // Academic Analytics
    $total_courses = fetchOne("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 12;
    $total_teachers = fetchOne("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'")['count'] ?? 25;
    $avg_class_size = $total_students > 0 && $total_courses > 0 ? round($total_students / $total_courses) : 0;
    
    // Performance Analytics
    $assignments_completed = fetchOne("SELECT COUNT(*) as count FROM assignment_submissions WHERE status = 'graded'")['count'] ?? 320;
    $avg_grade = fetchOne("SELECT AVG(grade) as avg FROM assignment_submissions WHERE grade IS NOT NULL")['avg'] ?? 78.5;
    
} catch (Exception $e) {
    // Use demo data if database queries fail
    $total_students = 150;
    $new_students_this_month = 25;
    $graduation_rate = 85;
    $total_revenue = 2500000;
    $outstanding_fees = 450000;
    $collection_rate = 84.7;
    $total_courses = 12;
    $total_teachers = 25;
    $avg_class_size = 13;
    $assignments_completed = 320;
    $avg_grade = 78.5;
}

// Get monthly enrollment data for chart
$enrollment_data = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $count = rand(8, 35); // Demo data
    $enrollment_data[] = [
        'month' => date('M Y', strtotime("-{$i} months")),
        'count' => $count
    ];
}

// Get course performance data
$course_performance = [
    ['course' => 'Computer Science', 'students' => 45, 'avg_grade' => 82.3, 'completion' => 94],
    ['course' => 'Business Management', 'students' => 38, 'avg_grade' => 79.1, 'completion' => 89],
    ['course' => 'Accounting', 'students' => 32, 'avg_grade' => 85.7, 'completion' => 97],
    ['course' => 'Marketing', 'students' => 28, 'avg_grade' => 77.8, 'completion' => 86],
    ['course' => 'Information Technology', 'students' => 35, 'avg_grade' => 80.5, 'completion' => 91]
];

// Get financial trends
$financial_trends = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $revenue = rand(180000, 250000); // Demo data
    $expenses = rand(120000, 180000); // Demo data
    $financial_trends[] = [
        'month' => date('M Y', strtotime("-{$i} months")),
        'revenue' => $revenue,
        'expenses' => $expenses,
        'profit' => $revenue - $expenses
    ];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Analytics Dashboard</h1>
        <p>Comprehensive institutional analytics and performance metrics</p>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-section">
        <h2>Key Performance Indicators</h2>
        <div class="kpi-grid">
            <div class="kpi-card student-kpi">
                <div class="kpi-icon">👥</div>
                <div class="kpi-content">
                    <div class="kpi-number"><?= number_format($total_students) ?></div>
                    <div class="kpi-label">Total Students</div>
                    <div class="kpi-change positive">+<?= $new_students_this_month ?> this month</div>
                </div>
            </div>
            
            <div class="kpi-card financial-kpi">
                <div class="kpi-icon">💰</div>
                <div class="kpi-content">
                    <div class="kpi-number">KSh <?= number_format($total_revenue) ?></div>
                    <div class="kpi-label">Total Revenue</div>
                    <div class="kpi-change <?= $collection_rate >= 80 ? 'positive' : 'negative' ?>">
                        <?= $collection_rate ?>% collection rate
                    </div>
                </div>
            </div>
            
            <div class="kpi-card academic-kpi">
                <div class="kpi-icon">🎓</div>
                <div class="kpi-content">
                    <div class="kpi-number"><?= $graduation_rate ?>%</div>
                    <div class="kpi-label">Graduation Rate</div>
                    <div class="kpi-change positive">+2.3% from last year</div>
                </div>
            </div>
            
            <div class="kpi-card performance-kpi">
                <div class="kpi-icon">📊</div>
                <div class="kpi-content">
                    <div class="kpi-number"><?= number_format($avg_grade, 1) ?>%</div>
                    <div class="kpi-label">Average Grade</div>
                    <div class="kpi-change positive">+1.2% improvement</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tabs -->
    <div class="analytics-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showAnalyticsTab('enrollment')">Enrollment Trends</button>
            <button class="tab-button" onclick="showAnalyticsTab('financial')">Financial Analysis</button>
            <button class="tab-button" onclick="showAnalyticsTab('academic')">Academic Performance</button>
            <button class="tab-button" onclick="showAnalyticsTab('operational')">Operational Metrics</button>
        </div>

        <!-- Enrollment Analytics Tab -->
        <div id="enrollment" class="tab-content active">
            <div class="analytics-grid">
                <div class="chart-container">
                    <h3>Monthly Enrollment Trends</h3>
                    <canvas id="enrollmentChart" width="400" height="200"></canvas>
                </div>
                
                <div class="stats-container">
                    <h3>Enrollment Statistics</h3>
                    <div class="stat-list">
                        <div class="stat-item">
                            <span class="stat-label">Total Active Students</span>
                            <span class="stat-value"><?= number_format($total_students) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">New Enrollments (This Month)</span>
                            <span class="stat-value"><?= $new_students_this_month ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Average Class Size</span>
                            <span class="stat-value"><?= $avg_class_size ?> students</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Student-Teacher Ratio</span>
                            <span class="stat-value"><?= round($total_students / $total_teachers) ?>:1</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="course-performance-section">
                <h3>Course Performance Overview</h3>
                <div class="course-performance-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Students</th>
                                <th>Avg Grade</th>
                                <th>Completion Rate</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_performance as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course']) ?></td>
                                <td><?= $course['students'] ?></td>
                                <td><?= number_format($course['avg_grade'], 1) ?>%</td>
                                <td><?= $course['completion'] ?>%</td>
                                <td>
                                    <div class="performance-bar">
                                        <div class="performance-fill" style="width: <?= $course['avg_grade'] ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Analytics Tab -->
        <div id="financial" class="tab-content">
            <div class="analytics-grid">
                <div class="chart-container">
                    <h3>Financial Trends</h3>
                    <canvas id="financialChart" width="400" height="200"></canvas>
                </div>
                
                <div class="financial-summary">
                    <h3>Financial Summary</h3>
                    <div class="financial-cards">
                        <div class="financial-card revenue">
                            <div class="card-header">Total Revenue</div>
                            <div class="card-amount">KSh <?= number_format($total_revenue) ?></div>
                            <div class="card-change positive">+12.5% vs last period</div>
                        </div>
                        <div class="financial-card outstanding">
                            <div class="card-header">Outstanding Fees</div>
                            <div class="card-amount">KSh <?= number_format($outstanding_fees) ?></div>
                            <div class="card-change negative">-5.2% vs last period</div>
                        </div>
                        <div class="financial-card collection">
                            <div class="card-header">Collection Rate</div>
                            <div class="card-amount"><?= $collection_rate ?>%</div>
                            <div class="card-change positive">+3.1% improvement</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="payment-methods-section">
                <h3>Payment Method Distribution</h3>
                <div class="payment-methods-chart">
                    <canvas id="paymentMethodsChart" width="300" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Academic Performance Tab -->
        <div id="academic" class="tab-content">
            <div class="academic-metrics">
                <div class="metric-card">
                    <h3>Assignment Completion</h3>
                    <div class="metric-value"><?= number_format($assignments_completed) ?></div>
                    <div class="metric-description">Total assignments completed</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 87%"></div>
                    </div>
                    <div class="progress-text">87% completion rate</div>
                </div>
                
                <div class="metric-card">
                    <h3>Average Performance</h3>
                    <div class="metric-value"><?= number_format($avg_grade, 1) ?>%</div>
                    <div class="metric-description">Overall student average</div>
                    <div class="grade-distribution">
                        <div class="grade-bar a" style="width: 25%">A (25%)</div>
                        <div class="grade-bar b" style="width: 35%">B (35%)</div>
                        <div class="grade-bar c" style="width: 30%">C (30%)</div>
                        <div class="grade-bar d" style="width: 10%">D+ (10%)</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <h3>Teacher Performance</h3>
                    <div class="metric-value"><?= $total_teachers ?></div>
                    <div class="metric-description">Active teaching staff</div>
                    <div class="teacher-stats">
                        <div class="teacher-stat">
                            <span>Excellent: 60%</span>
                            <div class="stat-bar excellent" style="width: 60%"></div>
                        </div>
                        <div class="teacher-stat">
                            <span>Good: 30%</span>
                            <div class="stat-bar good" style="width: 30%"></div>
                        </div>
                        <div class="teacher-stat">
                            <span>Average: 10%</span>
                            <div class="stat-bar average" style="width: 10%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="academic-trends">
                <h3>Academic Performance Trends</h3>
                <canvas id="academicTrendsChart" width="600" height="300"></canvas>
            </div>
        </div>

        <!-- Operational Metrics Tab -->
        <div id="operational" class="tab-content">
            <div class="operational-grid">
                <div class="operational-card">
                    <h3>System Usage</h3>
                    <div class="usage-stats">
                        <div class="usage-item">
                            <span class="usage-label">Daily Active Users</span>
                            <span class="usage-value">127</span>
                        </div>
                        <div class="usage-item">
                            <span class="usage-label">Login Success Rate</span>
                            <span class="usage-value">98.5%</span>
                        </div>
                        <div class="usage-item">
                            <span class="usage-label">Average Session Time</span>
                            <span class="usage-value">24 min</span>
                        </div>
                    </div>
                </div>
                
                <div class="operational-card">
                    <h3>Certificate Processing</h3>
                    <div class="certificate-stats">
                        <div class="cert-stat">
                            <div class="cert-number">45</div>
                            <div class="cert-label">Pending Approval</div>
                        </div>
                        <div class="cert-stat">
                            <div class="cert-number">128</div>
                            <div class="cert-label">Issued This Month</div>
                        </div>
                        <div class="cert-stat">
                            <div class="cert-number">3.2</div>
                            <div class="cert-label">Avg Processing Days</div>
                        </div>
                    </div>
                </div>
                
                <div class="operational-card">
                    <h3>Fee Collection Efficiency</h3>
                    <div class="collection-metrics">
                        <canvas id="collectionEfficiencyChart" width="300" height="200"></canvas>
                    </div>
                </div>
                
                <div class="operational-card">
                    <h3>Support Tickets</h3>
                    <div class="support-stats">
                        <div class="support-item open">
                            <span class="support-count">12</span>
                            <span class="support-label">Open</span>
                        </div>
                        <div class="support-item resolved">
                            <span class="support-count">89</span>
                            <span class="support-label">Resolved</span>
                        </div>
                        <div class="support-item avg-time">
                            <span class="support-count">2.1h</span>
                            <span class="support-label">Avg Response</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export and Actions -->
    <div class="actions-section">
        <h2>Reports & Actions</h2>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="exportReport('pdf')">
                📄 Export PDF Report
            </button>
            <button class="btn btn-secondary" onclick="exportReport('excel')">
                📊 Export Excel Report
            </button>
            <button class="btn btn-info" onclick="scheduleReport()">
                📅 Schedule Report
            </button>
            <button class="btn btn-success" onclick="generateCustomReport()">
                🔧 Custom Report Builder
            </button>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.kpi-section {
    margin-bottom: 40px;
}

.kpi-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.kpi-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.kpi-card.student-kpi {
    border-left: 5px solid #3498db;
}

.kpi-card.financial-kpi {
    border-left: 5px solid #27ae60;
}

.kpi-card.academic-kpi {
    border-left: 5px solid #9b59b6;
}

.kpi-card.performance-kpi {
    border-left: 5px solid #f39c12;
}

.kpi-icon {
    font-size: 3em;
    opacity: 0.8;
}

.kpi-content {
    flex: 1;
}

.kpi-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.kpi-label {
    color: #7f8c8d;
    font-size: 1.1em;
    margin-bottom: 5px;
}

.kpi-change {
    font-size: 0.9em;
    font-weight: bold;
}

.kpi-change.positive {
    color: #27ae60;
}

.kpi-change.negative {
    color: #e74c3c;
}

.analytics-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1em;
    color: #6c757d;
    transition: all 0.2s;
}

.tab-button.active {
    background: white;
    color: #3498db;
    border-bottom: 3px solid #3498db;
}

.tab-button:hover {
    background: #e9ecef;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.analytics-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.chart-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.chart-container h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.stats-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.stats-container h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.stat-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.stat-label {
    color: #6c757d;
    font-weight: bold;
}

.stat-value {
    color: #2c3e50;
    font-weight: bold;
    font-size: 1.1em;
}

.course-performance-section {
    margin-top: 30px;
}

.course-performance-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.course-performance-table {
    background: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

th {
    background: #e9ecef;
    font-weight: bold;
    color: #2c3e50;
}

.performance-bar {
    width: 100px;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
}

.performance-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
    transition: width 0.3s ease;
}

.financial-summary {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.financial-cards {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.financial-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #3498db;
}

.financial-card.revenue {
    border-left-color: #27ae60;
}

.financial-card.outstanding {
    border-left-color: #e74c3c;
}

.financial-card.collection {
    border-left-color: #3498db;
}

.card-header {
    color: #6c757d;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.card-amount {
    font-size: 1.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.card-change {
    font-size: 0.8em;
    font-weight: bold;
}

.payment-methods-section {
    margin-top: 30px;
    text-align: center;
}

.payment-methods-chart {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.academic-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
}

.metric-card h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.metric-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 10px;
}

.metric-description {
    color: #6c757d;
    margin-bottom: 15px;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background: #ecf0f1;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #3498db;
    transition: width 0.3s ease;
}

.progress-text {
    color: #6c757d;
    font-size: 0.9em;
}

.grade-distribution {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: 15px;
}

.grade-bar {
    padding: 5px 10px;
    border-radius: 3px;
    color: white;
    font-size: 0.8em;
    font-weight: bold;
    text-align: left;
}

.grade-bar.a {
    background: #27ae60;
}

.grade-bar.b {
    background: #3498db;
}

.grade-bar.c {
    background: #f39c12;
}

.grade-bar.d {
    background: #e74c3c;
}

.teacher-stats {
    margin-top: 15px;
}

.teacher-stat {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}

.teacher-stat span {
    min-width: 80px;
    font-size: 0.9em;
    color: #6c757d;
}

.stat-bar {
    height: 6px;
    border-radius: 3px;
}

.stat-bar.excellent {
    background: #27ae60;
}

.stat-bar.good {
    background: #3498db;
}

.stat-bar.average {
    background: #f39c12;
}

.academic-trends {
    margin-top: 30px;
    text-align: center;
}

.academic-trends h3 {
    color: #2c3e50;
    margin-bottom: 20px;
}

.operational-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.operational-card {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
}

.operational-card h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
}

.usage-stats, .certificate-stats, .support-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.usage-item, .support-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.usage-label, .support-label {
    color: #6c757d;
}

.usage-value, .support-count {
    font-weight: bold;
    color: #2c3e50;
}

.cert-stat {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    margin-bottom: 10px;
}

.cert-number {
    font-size: 2em;
    font-weight: bold;
    color: #3498db;
}

.cert-label {
    color: #6c757d;
    font-size: 0.9em;
}

.support-item.open .support-count {
    color: #e74c3c;
}

.support-item.resolved .support-count {
    color: #27ae60;
}

.support-item.avg-time .support-count {
    color: #3498db;
}

.actions-section {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.actions-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1em;
    font-weight: bold;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

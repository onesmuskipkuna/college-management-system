<?php
/**
 * Executive Reports Module
 * Comprehensive reporting system for executive decision making
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require director role
Authentication::requireRole('director');

$user = Authentication::getCurrentUser();

    // Report Templates
    $report_templates = [
    [
        'id' => 'financial_summary',
        'name' => 'Financial Summary Report',
        'description' => 'Comprehensive financial overview including revenue, expenses, and profitability',
        'frequency' => 'Monthly',
        'last_generated' => '2024-01-15',
        'category' => 'Financial'
    ],
    [
        'id' => 'enrollment_analysis',
        'name' => 'Enrollment Analysis Report',
        'description' => 'Student enrollment trends, demographics, and course popularity analysis',
        'frequency' => 'Weekly',
        'last_generated' => '2024-01-20',
        'category' => 'Academic'
    ],
    [
        'id' => 'performance_metrics',
        'name' => 'Performance Metrics Dashboard',
        'description' => 'Key performance indicators across all departments and functions',
        'frequency' => 'Daily',
        'last_generated' => '2024-01-22',
        'category' => 'Operations'
    ],
    [
        'id' => 'staff_analytics',
        'name' => 'Staff Analytics Report',
        'description' => 'Staff performance, satisfaction, and development metrics',
        'frequency' => 'Monthly',
        'last_generated' => '2024-01-10',
        'category' => 'HR'
    ],
    [
        'id' => 'compliance_audit',
        'name' => 'Compliance Audit Report',
        'description' => 'Regulatory compliance status and audit findings',
        'frequency' => 'Quarterly',
        'last_generated' => '2024-01-01',
        'category' => 'Compliance'
    ]
];

// Recent Reports
$recent_reports = [
    [
        'title' => 'Q1 2024 Financial Performance',
        'type' => 'Financial',
        'generated_date' => '2024-01-22 09:30:00',
        'generated_by' => 'System Auto-Generate',
        'status' => 'completed',
        'file_size' => '2.4 MB'
    ],
    [
        'title' => 'Weekly Enrollment Summary',
        'type' => 'Academic',
        'generated_date' => '2024-01-21 14:15:00',
        'generated_by' => 'Director',
        'status' => 'completed',
        'file_size' => '1.8 MB'
    ],
    [
        'title' => 'Staff Performance Review',
        'type' => 'HR',
        'generated_date' => '2024-01-20 11:45:00',
        'generated_by' => 'HR Manager',
        'status' => 'completed',
        'file_size' => '3.1 MB'
    ],
    [
        'title' => 'System Health Check',
        'type' => 'Operations',
        'generated_date' => '2024-01-19 16:20:00',
        'generated_by' => 'System Auto-Generate',
        'status' => 'processing',
        'file_size' => 'Processing...'
    ]
];

// Scheduled Reports
$scheduled_reports = [
    [
        'name' => 'Daily Operations Summary',
        'schedule' => 'Daily at 8:00 AM',
        'recipients' => ['director@college.edu', 'operations@college.edu'],
        'status' => 'active',
        'next_run' => '2024-01-23 08:00:00'
    ],
    [
        'name' => 'Weekly Financial Update',
        'schedule' => 'Every Monday at 9:00 AM',
        'recipients' => ['director@college.edu', 'finance@college.edu'],
        'status' => 'active',
        'next_run' => '2024-01-29 09:00:00'
    ],
    [
        'name' => 'Monthly Board Report',
        'schedule' => 'First Monday of each month',
        'recipients' => ['board@college.edu', 'director@college.edu'],
        'status' => 'active',
        'next_run' => '2024-02-05 10:00:00'
    ]
];

// Analytics Data for Charts
$financial_data = [
    'revenue' => [180000, 195000, 210000, 225000, 240000, 255000],
    'expenses' => [120000, 125000, 135000, 140000, 145000, 150000],
    'months' => ['Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan']
];

$enrollment_data = [
    'new_students' => [25, 30, 35, 28, 32, 38],
    'total_students' => [220, 245, 275, 298, 325, 358],
    'months' => ['Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan']
];
?>

<div class="reports-container">
    <div class="reports-header">
        <h1>Executive Reports Center</h1>
        <p>Comprehensive reporting and analytics for strategic decision making</p>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="createCustomReport()">+ Custom Report</button>
            <button class="btn btn-secondary" onclick="scheduleReport()">📅 Schedule Report</button>
            <button class="btn btn-info" onclick="exportData()">📊 Export Data</button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?= count($recent_reports) ?></h3>
                <p>Reports Generated</p>
                <span class="stat-period">This Week</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏰</div>
            <div class="stat-content">
                <h3><?= count($scheduled_reports) ?></h3>
                <p>Scheduled Reports</p>
                <span class="stat-period">Active</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <h3>98.5%</h3>
                <p>Report Accuracy</p>
                <span class="stat-period">System Reliability</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💾</div>
            <div class="stat-content">
                <h3>12.8 GB</h3>
                <p>Data Processed</p>
                <span class="stat-period">This Month</span>
            </div>
        </div>
    </div>

    <!-- Report Templates -->
    <div class="templates-section">
        <h2>Report Templates</h2>
        <div class="templates-grid">
            <?php foreach ($report_templates as $template): ?>
            <div class="template-card">
                <div class="template-header">
                    <h3><?= htmlspecialchars($template['name']) ?></h3>
                    <span class="category-badge <?= strtolower($template['category']) ?>">
                        <?= $template['category'] ?>
                    </span>
                </div>
                
                <div class="template-content">
                    <p><?= htmlspecialchars($template['description']) ?></p>
                    <div class="template-meta">
                        <div class="meta-item">
                            <span class="meta-label">Frequency:</span>
                            <span class="meta-value"><?= $template['frequency'] ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Last Generated:</span>
                            <span class="meta-value"><?= date('M j, Y', strtotime($template['last_generated'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="template-actions">
                    <button class="btn btn-primary" onclick="generateReport('<?= $template['id'] ?>')">
                        Generate Now
                    </button>
                    <button class="btn btn-secondary" onclick="customizeTemplate('<?= $template['id'] ?>')">
                        Customize
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="recent-reports-section">
        <h2>Recent Reports</h2>
        <div class="reports-table">
            <table>
                <thead>
                    <tr>
                        <th>Report Title</th>
                        <th>Type</th>
                        <th>Generated</th>
                        <th>Generated By</th>
                        <th>Status</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_reports as $report): ?>
                    <tr>
                        <td><?= htmlspecialchars($report['title']) ?></td>
                        <td>
                            <span class="type-badge <?= strtolower($report['type']) ?>">
                                <?= $report['type'] ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y H:i', strtotime($report['generated_date'])) ?></td>
                        <td><?= htmlspecialchars($report['generated_by']) ?></td>
                        <td>
                            <span class="status-badge <?= $report['status'] ?>">
                                <?= ucfirst($report['status']) ?>
                            </span>
                        </td>
                        <td><?= $report['file_size'] ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($report['status'] === 'completed'): ?>
                                <button class="btn btn-sm btn-primary" onclick="downloadReport('<?= $report['title'] ?>')">
                                    Download
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="viewReport('<?= $report['title'] ?>')">
                                    View
                                </button>
                                <?php else: ?>
                                <button class="btn btn-sm btn-warning" disabled>
                                    Processing...
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Analytics Dashboard -->
    <div class="analytics-section">
        <h2>Executive Analytics</h2>
        <div class="analytics-grid">
            <div class="chart-container">
                <h3>Financial Performance Trend</h3>
                <canvas id="financialChart" width="400" height="200"></canvas>
                <div class="chart-summary">
                    <div class="summary-item">
                        <span class="summary-label">Revenue Growth:</span>
                        <span class="summary-value positive">+18.3%</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Profit Margin:</span>
                        <span class="summary-value positive">41.2%</span>
                    </div>
                </div>
            </div>
            
            <div class="chart-container">
                <h3>Enrollment Growth Analysis</h3>
                <canvas id="enrollmentChart" width="400" height="200"></canvas>
                <div class="chart-summary">
                    <div class="summary-item">
                        <span class="summary-label">New Students:</span>
                        <span class="summary-value positive">+52%</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Retention Rate:</span>
                        <span class="summary-value positive">94.5%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scheduled Reports -->
    <div class="scheduled-section">
        <h2>Scheduled Reports</h2>
        <div class="scheduled-grid">
            <?php foreach ($scheduled_reports as $scheduled): ?>
            <div class="scheduled-card">
                <div class="scheduled-header">
                    <h4><?= htmlspecialchars($scheduled['name']) ?></h4>
                    <span class="status-indicator <?= $scheduled['status'] ?>"></span>
                </div>
                
                <div class="scheduled-content">
                    <div class="schedule-info">
                        <div class="info-item">
                            <span class="info-label">Schedule:</span>
                            <span class="info-value"><?= htmlspecialchars($scheduled['schedule']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Next Run:</span>
                            <span class="info-value"><?= date('M j, Y H:i', strtotime($scheduled['next_run'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Recipients:</span>
                            <span class="info-value"><?= count($scheduled['recipients']) ?> recipients</span>
                        </div>
                    </div>
                </div>
                
                <div class="scheduled-actions">
                    <button class="btn btn-sm btn-primary" onclick="editSchedule('<?= $scheduled['name'] ?>')">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="runNow('<?= $scheduled['name'] ?>')">
                        Run Now
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Report Builder -->
    <div class="builder-section">
        <h2>Custom Report Builder</h2>
        <div class="builder-container">
            <div class="builder-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Name</label>
                        <input type="text" class="form-control" placeholder="Enter report name">
                    </div>
                    <div class="form-group">
                        <label>Report Type</label>
                        <select class="form-control">
                            <option>Financial</option>
                            <option>Academic</option>
                            <option>Operations</option>
                            <option>HR</option>
                            <option>Compliance</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Range</label>
                        <select class="form-control">
                            <option>Last 7 days</option>
                            <option>Last 30 days</option>
                            <option>Last 3 months</option>
                            <option>Last 6 months</option>
                            <option>Last year</option>
                            <option>Custom range</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Output Format</label>
                        <select class="form-control">
                            <option>PDF</option>
                            <option>Excel</option>
                            <option>CSV</option>
                            <option>PowerPoint</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Data Sources</label>
                    <div class="checkbox-group">
                        <label><input type="checkbox" checked> Student Records</label>
                        <label><input type="checkbox" checked> Financial Data</label>
                        <label><input type="checkbox"> Staff Information</label>
                        <label><input type="checkbox"> Course Analytics</label>
                        <label><input type="checkbox"> System Logs</label>
                    </div>
                </div>
                
                <div class="builder-actions">
                    <button class="btn btn-primary">Generate Report</button>
                    <button class="btn btn-secondary">Save Template</button>
                    <button class="btn btn-info">Preview</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.reports-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.reports-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.reports-header h1 {
    margin: 0;
    color: white;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 3em;
    opacity: 0.8;
}

.stat-content h3 {
    font-size: 2.5em;
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.stat-content p {
    margin: 0 0 5px 0;
    color: #7f8c8d;
    font-weight: bold;
}

.stat-period {
    color: #95a5a6;
    font-size: 0.9em;
}

.templates-section, .recent-reports-section, .analytics-section, .scheduled-section, .builder-section {
    margin-bottom: 40px;
}

.templates-section h2, .recent-reports-section h2, .analytics-section h2, .scheduled-section h2, .builder-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.template-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.template-card:hover {
    transform: translateY(-3px);
}

.template-header {
    padding: 20px 20px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.template-header h3 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
}

.category-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.category-badge.financial {
    background: #d5f4e6;
    color: #27ae60;
}

.category-badge.academic {
    background: #d1ecf1;
    color: #17a2b8;
}

.category-badge.operations {
    background: #fdebd0;
    color: #f39c12;
}

.category-badge.hr {
    background: #e1bee7;
    color: #9c27b0;
}

.category-badge.compliance {
    background: #fdeaea;
    color: #e74c3c;
}

.template-content {
    padding: 15px 20px;
}

.template-content p {
    margin: 0 0 15px 0;
    color: #7f8c8d;
}

.template-meta {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.meta-item {
    display: flex;
    justify-content: space-between;
}

.meta-label {
    color: #95a5a6;
    font-size: 0.9em;
}

.meta-value {
    color: #2c3e50;
    font-weight: bold;
}

.template-actions {
    padding: 15px 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.reports-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.reports-table table {
    width: 100%;
    border-collapse: collapse;
}

.reports-table th,
.reports-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.reports-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.type-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.type-badge.financial {
    background: #d5f4e6;
    color: #27ae60;
}

.type-badge.academic {
    background: #d1ecf1;
    color: #17a2b8;
}

.type-badge.operations {
    background: #fdebd0;
    color: #f39c12;
}

.type-badge.hr {
    background: #e1bee7;
    color: #9c27b0;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.completed {
    background: #d5f4e6;
    color: #27ae60;
}

.status-badge.processing {
    background: #fdebd0;
    color: #f39c12;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 30px;
}

.chart-container {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.chart-container h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.chart-summary {
    margin-top: 20px;
    display: flex;
    justify-content: space-around;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.summary-item {
    text-align: center;
}

.summary-label {
    display: block;
    color: #7f8c8d;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.summary-value {
    font-size: 1.5em;
    font-weight: bold;
}

.summary-value.positive {
    color: #27ae60;
}

.summary-value.negative {
    color: #e74c3c;
}

.scheduled-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.scheduled-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.scheduled-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.scheduled-header h4 {
    margin: 0;
    color: #2c3e50;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-indicator.active {
    background: #27ae60;
}

.status-indicator.inactive {
    background: #e74c3c;
}

.schedule-info {
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.info-label {
    color: #7f8c8d;
    font-size: 0.9em;
}

.info-value {
    color: #2c3e50;
    font-weight: bold;
}

.scheduled-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.builder-container {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    color: #2c3e50;
    font-weight: bold;
}

.form-control {
    padding: 10px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1em;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0;
    font-weight: normal;
}

.builder-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .reports-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .scheduled-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .reports-table {
        overflow-x: auto;
    }
}
</style>

<script>
// Simple chart implementation
function drawChart(canvasId, data, type, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (type === 'line') {
        const datasets = Object.keys(data).filter(key => key !== 'months');
        const months = data.months;
        
        datasets.forEach((dataset, index) => {
            const values = data[dataset];
            const max = Math.max(...values);
            const min = Math.min(...values);
            const range = max - min || 1;
            
            ctx.strokeStyle = colors[index] || '#3498db';
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            values.forEach((value, i) => {
                const x = (i / (values.length - 1)) * (canvas.width - 40) + 20;
                const y = canvas.height - 20 - ((value - min) / range) * (canvas.height - 40);
                
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Draw points
            ctx.fillStyle = colors[index] || '#3498db';
            values.forEach((value, i) => {
                const x = (i / (values.length - 1)) * (canvas.width - 40) + 20;
                const y = canvas.height - 20 - ((value - min) / range) * (canvas.height - 40);
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, 2 * Math.PI);
                ctx.fill();
            });
        });
    }
}

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    const financialData = <?= json_encode($financial_data) ?>;
    const enrollmentData = <?= json_encode($enrollment_data) ?>;

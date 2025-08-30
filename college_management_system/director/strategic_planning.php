<?php
/**
 * Strategic Planning Module
 * Long-term planning and goal setting for institutional growth
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require director role
Authentication::requireRole('director');

$user = Authentication::getCurrentUser();

    // Strategic Goals Data
    $strategic_goals = [
    [
        'id' => 1,
        'title' => 'Increase Student Enrollment by 25%',
        'description' => 'Expand student base through marketing and new program offerings',
        'target_date' => '2024-12-31',
        'progress' => 68,
        'status' => 'on_track',
        'priority' => 'high',
        'department' => 'Marketing & Admissions'
    ],
    [
        'id' => 2,
        'title' => 'Achieve 95% Fee Collection Rate',
        'description' => 'Improve financial processes and payment systems',
        'target_date' => '2024-06-30',
        'progress' => 82,
        'status' => 'on_track',
        'priority' => 'high',
        'department' => 'Finance'
    ],
    [
        'id' => 3,
        'title' => 'Launch Online Learning Platform',
        'description' => 'Develop and implement comprehensive e-learning system',
        'target_date' => '2024-09-15',
        'progress' => 45,
        'status' => 'at_risk',
        'priority' => 'medium',
        'department' => 'IT & Academic'
    ],
    [
        'id' => 4,
        'title' => 'Obtain ISO 9001 Certification',
        'description' => 'Implement quality management system certification',
        'target_date' => '2025-03-31',
        'progress' => 25,
        'status' => 'planning',
        'priority' => 'medium',
        'department' => 'Quality Assurance'
    ]
];

// Budget Allocation Data
$budget_allocation = [
    'Academic Programs' => ['allocated' => 2500000, 'spent' => 1850000, 'percentage' => 35],
    'Infrastructure' => ['allocated' => 1800000, 'spent' => 1200000, 'percentage' => 25],
    'Staff Development' => ['allocated' => 800000, 'spent' => 650000, 'percentage' => 11],
    'Technology' => ['allocated' => 1200000, 'spent' => 900000, 'percentage' => 17],
    'Marketing' => ['allocated' => 600000, 'spent' => 480000, 'percentage' => 8],
    'Operations' => ['allocated' => 300000, 'spent' => 220000, 'percentage' => 4]
];

// Key Performance Indicators
$kpis = [
    'student_satisfaction' => ['current' => 87, 'target' => 90, 'trend' => 'up'],
    'staff_retention' => ['current' => 92, 'target' => 95, 'trend' => 'stable'],
    'course_completion' => ['current' => 89, 'target' => 92, 'trend' => 'up'],
    'employment_rate' => ['current' => 78, 'target' => 85, 'trend' => 'up'],
    'revenue_growth' => ['current' => 12.5, 'target' => 15, 'trend' => 'up']
];

// Risk Assessment
$risks = [
    [
        'risk' => 'Economic downturn affecting enrollment',
        'probability' => 'medium',
        'impact' => 'high',
        'mitigation' => 'Diversify program offerings and payment plans',
        'status' => 'monitoring'
    ],
    [
        'risk' => 'Competition from online education providers',
        'probability' => 'high',
        'impact' => 'medium',
        'mitigation' => 'Accelerate digital transformation initiatives',
        'status' => 'active'
    ],
    [
        'risk' => 'Staff turnover in key positions',
        'probability' => 'medium',
        'impact' => 'medium',
        'mitigation' => 'Improve compensation and development programs',
        'status' => 'monitoring'
    ]
];
?>

<div class="strategic-container">
    <div class="strategic-header">
        <h1>Strategic Planning Dashboard</h1>
        <p>Long-term planning and institutional goal management</p>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="createNewGoal()">+ New Strategic Goal</button>
            <button class="btn btn-secondary" onclick="generateStrategicReport()">📊 Generate Report</button>
        </div>
    </div>

    <!-- Strategic Overview -->
    <div class="overview-section">
        <div class="overview-cards">
            <div class="overview-card goals">
                <div class="card-icon">🎯</div>
                <div class="card-content">
                    <h3><?= count($strategic_goals) ?></h3>
                    <p>Active Goals</p>
                    <div class="card-progress">
                        <?php 
                        $on_track = count(array_filter($strategic_goals, fn($g) => $g['status'] === 'on_track'));
                        $progress_percentage = ($on_track / count($strategic_goals)) * 100;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $progress_percentage ?>%"></div>
                        </div>
                        <span><?= $on_track ?> on track</span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card budget">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3>KSh <?= number_format(array_sum(array_column($budget_allocation, 'allocated'))) ?></h3>
                    <p>Total Budget</p>
                    <div class="card-progress">
                        <?php 
                        $total_spent = array_sum(array_column($budget_allocation, 'spent'));
                        $total_allocated = array_sum(array_column($budget_allocation, 'allocated'));
                        $budget_utilization = ($total_spent / $total_allocated) * 100;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill budget-fill" style="width: <?= $budget_utilization ?>%"></div>
                        </div>
                        <span><?= round($budget_utilization) ?>% utilized</span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card performance">
                <div class="card-icon">📈</div>
                <div class="card-content">
                    <h3><?= array_sum(array_column($kpis, 'current')) / count($kpis) ?>%</h3>
                    <p>Avg KPI Score</p>
                    <div class="card-progress">
                        <?php 
                        $avg_target = array_sum(array_column($kpis, 'target')) / count($kpis);
                        $avg_current = array_sum(array_column($kpis, 'current')) / count($kpis);
                        $kpi_achievement = ($avg_current / $avg_target) * 100;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill performance-fill" style="width: <?= $kpi_achievement ?>%"></div>
                        </div>
                        <span><?= round($kpi_achievement) ?>% of target</span>
                    </div>
                </div>
            </div>
            
            <div class="overview-card risks">
                <div class="card-icon">⚠️</div>
                <div class="card-content">
                    <h3><?= count($risks) ?></h3>
                    <p>Risk Factors</p>
                    <div class="card-progress">
                        <?php 
                        $active_risks = count(array_filter($risks, fn($r) => $r['status'] === 'active'));
                        $risk_percentage = count($risks) > 0 ? ($active_risks / count($risks)) * 100 : 0;
                        ?>
                        <div class="progress-bar">
                            <div class="progress-fill risk-fill" style="width: <?= $risk_percentage ?>%"></div>
                        </div>
                        <span><?= $active_risks ?> active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Strategic Goals -->
    <div class="goals-section">
        <h2>Strategic Goals</h2>
        <div class="goals-grid">
            <?php foreach ($strategic_goals as $goal): ?>
            <div class="goal-card <?= $goal['status'] ?>">
                <div class="goal-header">
                    <h3><?= htmlspecialchars($goal['title']) ?></h3>
                    <div class="goal-meta">
                        <span class="priority-badge <?= $goal['priority'] ?>"><?= ucfirst($goal['priority']) ?></span>
                        <span class="status-badge <?= $goal['status'] ?>"><?= ucwords(str_replace('_', ' ', $goal['status'])) ?></span>
                    </div>
                </div>
                
                <div class="goal-content">
                    <p><?= htmlspecialchars($goal['description']) ?></p>
                    <div class="goal-details">
                        <div class="detail-item">
                            <span class="detail-label">Department:</span>
                            <span class="detail-value"><?= htmlspecialchars($goal['department']) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Target Date:</span>
                            <span class="detail-value"><?= date('M j, Y', strtotime($goal['target_date'])) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="goal-progress">
                    <div class="progress-header">
                        <span>Progress</span>
                        <span><?= $goal['progress'] ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $goal['progress'] ?>%"></div>
                    </div>
                </div>
                
                <div class="goal-actions">
                    <button class="btn btn-sm btn-primary" onclick="editGoal(<?= $goal['id'] ?>)">Edit</button>
                    <button class="btn btn-sm btn-secondary" onclick="viewGoalDetails(<?= $goal['id'] ?>)">Details</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Budget Allocation -->
    <div class="budget-section">
        <h2>Budget Allocation & Utilization</h2>
        <div class="budget-grid">
            <?php foreach ($budget_allocation as $category => $budget): ?>
            <div class="budget-card">
                <div class="budget-header">
                    <h4><?= $category ?></h4>
                    <span class="budget-percentage"><?= $budget['percentage'] ?>%</span>
                </div>
                <div class="budget-amounts">
                    <div class="amount-item">
                        <span class="amount-label">Allocated:</span>
                        <span class="amount-value">KSh <?= number_format($budget['allocated']) ?></span>
                    </div>
                    <div class="amount-item">
                        <span class="amount-label">Spent:</span>
                        <span class="amount-value">KSh <?= number_format($budget['spent']) ?></span>
                    </div>
                    <div class="amount-item">
                        <span class="amount-label">Remaining:</span>
                        <span class="amount-value">KSh <?= number_format($budget['allocated'] - $budget['spent']) ?></span>
                    </div>
                </div>
                <div class="budget-progress">
                    <?php $utilization = ($budget['spent'] / $budget['allocated']) * 100; ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $utilization ?>%"></div>
                    </div>
                    <span class="utilization-text"><?= round($utilization) ?>% utilized</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="kpi-section">
        <h2>Key Performance Indicators</h2>
        <div class="kpi-grid">
            <?php foreach ($kpis as $kpi_name => $kpi_data): ?>
            <div class="kpi-card">
                <div class="kpi-header">
                    <h4><?= ucwords(str_replace('_', ' ', $kpi_name)) ?></h4>
                    <div class="kpi-trend <?= $kpi_data['trend'] ?>">
                        <?php if ($kpi_data['trend'] === 'up'): ?>↗️<?php endif; ?>
                        <?php if ($kpi_data['trend'] === 'down'): ?>↘️<?php endif; ?>
                        <?php if ($kpi_data['trend'] === 'stable'): ?>➡️<?php endif; ?>
                    </div>
                </div>
                <div class="kpi-values">
                    <div class="kpi-current"><?= $kpi_data['current'] ?><?= strpos($kpi_name, 'rate') !== false || strpos($kpi_name, 'satisfaction') !== false || strpos($kpi_name, 'retention') !== false || strpos($kpi_name, 'completion') !== false || strpos($kpi_name, 'employment') !== false ? '%' : '' ?></div>
                    <div class="kpi-target">Target: <?= $kpi_data['target'] ?><?= strpos($kpi_name, 'rate') !== false || strpos($kpi_name, 'satisfaction') !== false || strpos($kpi_name, 'retention') !== false || strpos($kpi_name, 'completion') !== false || strpos($kpi_name, 'employment') !== false ? '%' : '' ?></div>
                </div>
                <div class="kpi-progress">
                    <?php $achievement = ($kpi_data['current'] / $kpi_data['target']) * 100; ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($achievement, 100) ?>%"></div>
                    </div>
                    <span><?= round($achievement) ?>% of target</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Risk Assessment -->
    <div class="risk-section">
        <h2>Risk Assessment & Mitigation</h2>
        <div class="risk-table">
            <table>
                <thead>
                    <tr>
                        <th>Risk Factor</th>
                        <th>Probability</th>
                        <th>Impact</th>
                        <th>Mitigation Strategy</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risks as $index => $risk): ?>
                    <tr>
                        <td><?= htmlspecialchars($risk['risk']) ?></td>
                        <td>
                            <span class="probability-badge <?= $risk['probability'] ?>">
                                <?= ucfirst($risk['probability']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="impact-badge <?= $risk['impact'] ?>">
                                <?= ucfirst($risk['impact']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($risk['mitigation']) ?></td>
                        <td>
                            <span class="status-badge <?= $risk['status'] ?>">
                                <?= ucfirst($risk['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="updateRisk(<?= $index ?>)">Update</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.strategic-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.strategic-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.strategic-header h1 {
    margin: 0;
    color: white;
}

.header-actions {
    display: flex;
    gap: 15px;
}

.overview-section {
    margin-bottom: 40px;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.overview-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
}

.card-icon {
    font-size: 3em;
    opacity: 0.8;
}

.card-content h3 {
    font-size: 2.5em;
    margin: 0 0 5px 0;
    color: #2c3e50;
}

.card-content p {
    margin: 0 0 15px 0;
    color: #7f8c8d;
}

.card-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3498db;
    transition: width 0.3s ease;
}

.progress-fill.budget-fill {
    background: #27ae60;
}

.progress-fill.performance-fill {
    background: #f39c12;
}

.progress-fill.risk-fill {
    background: #e74c3c;
}

.goals-section, .budget-section, .kpi-section, .risk-section {
    margin-bottom: 40px;
}

.goals-section h2, .budget-section h2, .kpi-section h2, .risk-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.goals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
}

.goal-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.goal-card:hover {
    transform: translateY(-3px);
}

.goal-card.on_track {
    border-left: 5px solid #27ae60;
}

.goal-card.at_risk {
    border-left: 5px solid #f39c12;
}

.goal-card.planning {
    border-left: 5px solid #3498db;
}

.goal-header {
    padding: 20px 20px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.goal-header h3 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
}

.goal-meta {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.priority-badge, .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    text-align: center;
}

.priority-badge.high {
    background: #fdeaea;
    color: #e74c3c;
}

.priority-badge.medium {
    background: #fdebd0;
    color: #f39c12;
}

.priority-badge.low {
    background: #eaeded;
    color: #95a5a6;
}

.status-badge.on_track {
    background: #d5f4e6;
    color: #27ae60;
}

.status-badge.at_risk {
    background: #fdebd0;
    color: #f39c12;
}

.status-badge.planning {
    background: #d1ecf1;
    color: #17a2b8;
}

.goal-content {
    padding: 15px 20px;
}

.goal-content p {
    margin: 0 0 15px 0;
    color: #7f8c8d;
}

.goal-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
}

.detail-label {
    color: #95a5a6;
    font-size: 0.9em;
}

.detail-value {
    color: #2c3e50;
    font-weight: bold;
}

.goal-progress {
    padding: 15px 20px;
    background: #f8f9fa;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: bold;
    color: #2c3e50;
}

.goal-actions {
    padding: 15px 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.budget-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.budget-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.budget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.budget-header h4 {
    margin: 0;
    color: #2c3e50;
}

.budget-percentage {
    background: #3498db;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}

.budget-amounts {
    margin-bottom: 20px;
}

.amount-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.amount-label {
    color: #7f8c8d;
}

.amount-value {
    color: #2c3e50;
    font-weight: bold;
}

.budget-progress {
    text-align: center;
}

.utilization-text {
    color: #7f8c8d;
    font-size: 0.9em;
    margin-top: 5px;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.kpi-card {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    text-align: center;
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.kpi-header h4 {
    margin: 0;
    color: #2c3e50;
}

.kpi-trend {
    font-size: 1.2em;
}

.kpi-current {
    font-size: 2.5em;
    font-weight: bold;
    color: #3498db;
    margin-bottom: 5px;
}

.kpi-target {
    color: #7f8c8d;
    margin-bottom: 15px;
}

.risk-table {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.risk-table table {
    width: 100%;
    border-collapse: collapse;
}

.risk-table th,
.risk-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.risk-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.probability-badge, .impact-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.probability-badge.high, .impact-badge.high {
    background: #fdeaea;
    color: #e74c3c;
}

.probability-badge.medium, .impact-badge.medium {
    background: #fdebd0;
    color: #f39c12;
}

.probability-badge.low, .impact-badge.low {
    background: #d5f4e6;
    color: #27ae60;
}

.status-badge.active {
    background: #fdeaea;
    color: #e74c3c;
}

.status-badge.monitoring {
    background: #fdebd0;
    color: #f39c12;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
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

.btn-sm {
    padding: 6px 12px;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .strategic-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .goals-grid {
        grid-template-columns: 1fr;
    }
    
    .budget-grid {
        grid-template-columns: 1fr;
    }
    
    .kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .risk-table {
        overflow-x: auto;
    }
}
</style>

<script>
function createNewGoal() {
    alert('Create New Goal functionality would be implemented here');
}

function generateStrategicReport() {
    alert('Generate Strategic Report functionality would be implemented here');
}

function editGoal(goalId) {
    alert('Edit Goal ' + goalId + ' functionality would be implemented here');
}

function viewGoalDetails(goalId) {
    alert('View Goal Details ' + goalId + ' functionality would be implemented here');
}

function updateRisk(riskIndex) {
    alert('Update Risk ' + riskIndex + ' functionality would be implemented here');
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

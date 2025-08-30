<?php
/**
 * Profit & Loss Report
 * Generate comprehensive profit and loss statements
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Get date range from request or default to current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_period = $_GET['period'] ?? 'monthly';

// Calculate previous period for comparison
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);
$period_days = ($end_timestamp - $start_timestamp) / (24 * 60 * 60);

$prev_end_date = date('Y-m-d', $start_timestamp - 1);
$prev_start_date = date('Y-m-d', $start_timestamp - ($period_days * 24 * 60 * 60));

// INCOME SECTION
$income_data = [
    'tuition_fees' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type LIKE '%tuition%'
    ", [$start_date, $end_date])['total'],
    
    'registration_fees' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type LIKE '%registration%'
    ", [$start_date, $end_date])['total'],
    
    'examination_fees' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type LIKE '%exam%'
    ", [$start_date, $end_date])['total'],
    
    'other_fees' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type NOT LIKE '%tuition%' 
        AND fs.fee_type NOT LIKE '%registration%'
        AND fs.fee_type NOT LIKE '%exam%'
    ", [$start_date, $end_date])['total'],
    
    'hostel_income' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type LIKE '%hostel%'
    ", [$start_date, $end_date])['total'],
    
    'transport_income' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments p
        JOIN student_fees sf ON p.student_fee_id = sf.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE p.status = 'approved' 
        AND p.payment_date BETWEEN ? AND ?
        AND fs.fee_type LIKE '%transport%'
    ", [$start_date, $end_date])['total']
];

// EXPENSES SECTION (Mock data - in real system, you'd have an expenses table)
$expense_data = [
    'staff_salaries' => fetchOne("
        SELECT COALESCE(SUM(net_salary), 0) as total 
        FROM payroll 
        WHERE payment_date BETWEEN ? AND ?
        AND status = 'paid'
    ", [$start_date, $end_date])['total'] ?? 0,
    
    'utilities' => 50000, // Mock data
    'maintenance' => 25000, // Mock data
    'supplies' => 15000, // Mock data
    'marketing' => 10000, // Mock data
    'insurance' => 8000, // Mock data
    'transport_expenses' => 12000, // Mock data
    'other_expenses' => 5000 // Mock data
];

// Calculate totals
$total_income = array_sum($income_data);
$total_expenses = array_sum($expense_data);
$gross_profit = $total_income - $total_expenses;
$profit_margin = $total_income > 0 ? ($gross_profit / $total_income) * 100 : 0;

// Get previous period data for comparison
$prev_income = fetchOne("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM payments 
    WHERE status = 'approved' 
    AND payment_date BETWEEN ? AND ?
", [$prev_start_date, $prev_end_date])['total'];

$prev_expenses = fetchOne("
    SELECT COALESCE(SUM(net_salary), 0) as total 
    FROM payroll 
    WHERE payment_date BETWEEN ? AND ?
    AND status = 'paid'
", [$prev_start_date, $prev_end_date])['total'] ?? 75000; // Mock previous expenses

$prev_profit = $prev_income - $prev_expenses;

// Calculate growth percentages
$income_growth = $prev_income > 0 ? (($total_income - $prev_income) / $prev_income) * 100 : 0;
$profit_growth = $prev_profit != 0 ? (($gross_profit - $prev_profit) / abs($prev_profit)) * 100 : 0;

// Monthly trend data (last 12 months)
$monthly_trends = fetchAll("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as income
    FROM payments 
    WHERE status = 'approved' 
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Profit & Loss Report</h1>
                    <p class="text-muted">Comprehensive financial performance analysis</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="exportReport()">Export PDF</button>
                    <button class="btn btn-outline-success" onclick="exportExcel()">Export Excel</button>
                    <button class="btn btn-outline-info" onclick="printReport()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="period" class="form-label">Report Period</label>
                            <select class="form-select" id="period" name="period">
                                <option value="monthly" <?= $report_period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                <option value="quarterly" <?= $report_period === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                                <option value="yearly" <?= $report_period === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                <option value="custom" <?= $report_period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                            <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="kpi-card income">
                <div class="kpi-icon">💰</div>
                <div class="kpi-content">
                    <h3>KSh <?= number_format($total_income) ?></h3>
                    <p>Total Income</p>
                    <small class="growth <?= $income_growth >= 0 ? 'positive' : 'negative' ?>">
                        <?= $income_growth >= 0 ? '↗️' : '↘️' ?> <?= number_format(abs($income_growth), 1) ?>% vs previous period
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card expenses">
                <div class="kpi-icon">📊</div>
                <div class="kpi-content">
                    <h3>KSh <?= number_format($total_expenses) ?></h3>
                    <p>Total Expenses</p>
                    <small class="text-muted"><?= number_format(($total_expenses / $total_income) * 100, 1) ?>% of income</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card profit">
                <div class="kpi-icon"><?= $gross_profit >= 0 ? '📈' : '📉' ?></div>
                <div class="kpi-content">
                    <h3 class="<?= $gross_profit >= 0 ? 'text-success' : 'text-danger' ?>">
                        KSh <?= number_format($gross_profit) ?>
                    </h3>
                    <p><?= $gross_profit >= 0 ? 'Net Profit' : 'Net Loss' ?></p>
                    <small class="growth <?= $profit_growth >= 0 ? 'positive' : 'negative' ?>">
                        <?= $profit_growth >= 0 ? '↗️' : '↘️' ?> <?= number_format(abs($profit_growth), 1) ?>% vs previous period
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card margin">
                <div class="kpi-icon">📋</div>
                <div class="kpi-content">
                    <h3 class="<?= $profit_margin >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($profit_margin, 1) ?>%
                    </h3>
                    <p>Profit Margin</p>
                    <small class="text-muted">
                        <?= $profit_margin >= 20 ? 'Excellent' : ($profit_margin >= 10 ? 'Good' : 'Needs Improvement') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Income Statement -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Income Statement</h5>
                    <small class="text-muted"><?= formatDate($start_date) ?> to <?= formatDate($end_date) ?></small>
                </div>
                <div class="card-body">
                    <!-- INCOME SECTION -->
                    <div class="statement-section">
                        <h6 class="section-title">INCOME</h6>
                        <div class="statement-item">
                            <span>Tuition Fees</span>
                            <span class="amount">KSh <?= number_format($income_data['tuition_fees']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Registration Fees</span>
                            <span class="amount">KSh <?= number_format($income_data['registration_fees']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Examination Fees</span>
                            <span class="amount">KSh <?= number_format($income_data['examination_fees']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Hostel Income</span>
                            <span class="amount">KSh <?= number_format($income_data['hostel_income']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Transport Income</span>
                            <span class="amount">KSh <?= number_format($income_data['transport_income']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Other Fees</span>
                            <span class="amount">KSh <?= number_format($income_data['other_fees']) ?></span>
                        </div>
                        <div class="statement-total">
                            <span><strong>Total Income</strong></span>
                            <span class="amount"><strong>KSh <?= number_format($total_income) ?></strong></span>
                        </div>
                    </div>

                    <!-- EXPENSES SECTION -->
                    <div class="statement-section">
                        <h6 class="section-title">EXPENSES</h6>
                        <div class="statement-item">
                            <span>Staff Salaries</span>
                            <span class="amount">KSh <?= number_format($expense_data['staff_salaries']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Utilities (Electricity, Water, Internet)</span>
                            <span class="amount">KSh <?= number_format($expense_data['utilities']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Maintenance & Repairs</span>
                            <span class="amount">KSh <?= number_format($expense_data['maintenance']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Supplies & Materials</span>
                            <span class="amount">KSh <?= number_format($expense_data['supplies']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Marketing & Advertising</span>
                            <span class="amount">KSh <?= number_format($expense_data['marketing']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Insurance</span>
                            <span class="amount">KSh <?= number_format($expense_data['insurance']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Transport Expenses</span>
                            <span class="amount">KSh <?= number_format($expense_data['transport_expenses']) ?></span>
                        </div>
                        <div class="statement-item">
                            <span>Other Expenses</span>
                            <span class="amount">KSh <?= number_format($expense_data['other_expenses']) ?></span>
                        </div>
                        <div class="statement-total">
                            <span><strong>Total Expenses</strong></span>
                            <span class="amount"><strong>KSh <?= number_format($total_expenses) ?></strong></span>
                        </div>
                    </div>

                    <!-- NET PROFIT/LOSS -->
                    <div class="statement-section">
                        <div class="statement-final <?= $gross_profit >= 0 ? 'profit' : 'loss' ?>">
                            <span><strong><?= $gross_profit >= 0 ? 'NET PROFIT' : 'NET LOSS' ?></strong></span>
                            <span class="amount"><strong>KSh <?= number_format(abs($gross_profit)) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analysis -->
        <div class="col-md-4">
            <!-- Income vs Expenses Chart -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Income vs Expenses</h6>
                </div>
                <div class="card-body">
                    <canvas id="incomeExpensesChart" width="400" height="300"></canvas>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6>12-Month Income Trend</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Expense Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h6>Expense Breakdown</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($expense_data as $category => $amount): ?>
                        <?php if ($amount > 0): ?>
                            <div class="expense-item">
                                <div class="d-flex justify-content-between">
                                    <span class="text-capitalize"><?= str_replace('_', ' ', $category) ?></span>
                                    <strong>KSh <?= number_format($amount) ?></strong>
                                </div>
                                <div class="progress mt-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= ($amount / $total_expenses) * 100 ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.kpi-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.kpi-card:hover {
    transform: translateY(-3px);
}

.kpi-card.income {
    border-left: 5px solid #28a745;
}

.kpi-card.expenses {
    border-left: 5px solid #dc3545;
}

.kpi-card.profit {
    border-left: 5px solid #007bff;
}

.kpi-card.margin {
    border-left: 5px solid #ffc107;
}

.kpi-icon {
    font-size: 3rem;
    opacity: 0.8;
}

.kpi-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.kpi-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-weight: 500;
}

.growth.positive {
    color: #28a745;
}

.growth.negative {
    color: #dc3545;
}

.statement-section {
    margin-bottom: 2rem;
}

.section-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.statement-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.statement-total {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-top: 2px solid #2c3e50;
    margin-top: 1rem;
}

.statement-final {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem;
    border-radius: 10px;
    margin-top: 1rem;
}

.statement-final.profit {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.statement-final.loss {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.expense-item {
    margin-bottom: 1rem;
}

.expense-item:last-child {
    margin-bottom: 0;
}

.amount {
    font-weight: 600;
}

@media (max-width: 768px) {
    .kpi-card {
        flex-direction: column;
        text-align: center;
    }
    
    .kpi-icon {
        font-size: 2rem;
    }
    
    .kpi-content h3 {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Income vs Expenses Pie Chart
function createIncomeExpensesChart() {
    const ctx = document.getElementById('incomeExpensesChart').getContext('2d');
    const total = <?= $total_income ?> + <?= $total_expenses ?>;
    
    if (total === 0) {
        ctx.fillStyle = '#999';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', ctx.canvas.width/2, ctx.canvas.height/2);
        return;
    }
    
    const incomeAngle = (<?= $total_income ?> / total) * 2 * Math.PI;
    const expenseAngle = (<?= $total_expenses ?> / total) * 2 * Math.PI;
    
    // Draw income slice
    ctx.beginPath();
    ctx.arc(200, 150, 100, 0, incomeAngle);
    ctx.lineTo(200, 150);
    ctx.fillStyle = '#28a745';
    ctx.fill();
    
    // Draw expense slice
    ctx.beginPath();
    ctx.arc(200, 150, 100, incomeAngle, incomeAngle + expenseAngle);
    ctx.lineTo(200, 150);
    ctx.fillStyle = '#dc3545';
    ctx.fill();
    
    // Add labels
    ctx.fillStyle = '#000';
    ctx.font = '12px Arial';
    ctx.textAlign = 'center';
    ctx.fillText('Income: KSh <?= number_format($total_income) ?>', 200, 280);
    ctx.fillText('Expenses: KSh <?= number_format($total_expenses) ?>', 200, 295);
}

// Monthly Trend Chart
function createMonthlyTrendChart() {
    const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
    const data = <?= json_encode(array_reverse($monthly_trends)) ?>;
    
    if (data.length === 0) {
        ctx.fillStyle = '#999';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', ctx.canvas.width/2, ctx.canvas.height/2);
        return;
    }
    
    const maxIncome = Math.max(...data.map(d => parseFloat(d.income)));
    const width = ctx.canvas.width - 60;
    const height = ctx.canvas.height - 60;
    
    ctx.strokeStyle = '#007bff';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    data.forEach((item, index) => {
        const x = 30 + (index / (data.length - 1)) * width;
        const y = 30 + height - (parseFloat(item.income) / maxIncome) * height;
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
        
        // Draw points
        ctx.fillStyle = '#007bff';
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
        ctx.beginPath();
        ctx.strokeStyle = '#007bff';
        ctx.lineWidth = 3;
    });
    
    ctx.stroke();
}

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('/college_management_system/accounts/export_pl_report.php?' + params.toString(), '_blank');
}

function exportExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/accounts/export_pl_report.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}

function resetFilters() {
    window.location.href = '/college_management_system/accounts/p_l_report.php';
}

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    createIncomeExpensesChart();
    createMonthlyTrendChart();
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Financial Reporting System
 * Generate comprehensive financial reports and analytics
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
$report_type = $_GET['report_type'] ?? 'summary';

// Financial Summary Data
$financial_summary = [
    'total_income' => fetchOne("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'approved' 
        AND payment_date BETWEEN ? AND ?
    ", [$start_date, $end_date])['total'],
    
    'total_outstanding' => fetchOne("
        SELECT COALESCE(SUM(balance), 0) as total 
        FROM student_fees 
        WHERE balance > 0
    ")['total'],
    
    'total_payments_count' => fetchOne("
        SELECT COUNT(*) as count 
        FROM payments 
        WHERE status = 'approved' 
        AND payment_date BETWEEN ? AND ?
    ", [$start_date, $end_date])['count'],
    
    'overdue_amount' => fetchOne("
        SELECT COALESCE(SUM(balance), 0) as total 
        FROM student_fees 
        WHERE balance > 0 AND due_date < CURDATE()
    ")['total']
];

// Payment Method Breakdown
$payment_methods = fetchAll("
    SELECT 
        payment_method,
        COUNT(*) as transaction_count,
        SUM(amount) as total_amount,
        AVG(amount) as average_amount
    FROM payments 
    WHERE status = 'approved' 
    AND payment_date BETWEEN ? AND ?
    GROUP BY payment_method
    ORDER BY total_amount DESC
", [$start_date, $end_date]);

// Daily Collections
$daily_collections = fetchAll("
    SELECT 
        DATE(payment_date) as payment_date,
        COUNT(*) as transaction_count,
        SUM(amount) as daily_total
    FROM payments 
    WHERE status = 'approved' 
    AND payment_date BETWEEN ? AND ?
    GROUP BY DATE(payment_date)
    ORDER BY payment_date DESC
", [$start_date, $end_date]);

// Course-wise Revenue
$course_revenue = fetchAll("
    SELECT 
        c.course_name,
        c.course_code,
        COUNT(DISTINCT p.student_id) as students_paid,
        SUM(p.amount) as total_revenue,
        AVG(p.amount) as average_payment
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE p.status = 'approved' 
    AND p.payment_date BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total_revenue DESC
", [$start_date, $end_date]);

// Outstanding Fees by Course
$outstanding_by_course = fetchAll("
    SELECT 
        c.course_name,
        c.course_code,
        COUNT(DISTINCT sf.student_id) as students_with_balance,
        SUM(sf.balance) as total_outstanding
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE sf.balance > 0
    GROUP BY c.id
    ORDER BY total_outstanding DESC
");

// Monthly Trends (Last 12 months)
$monthly_trends = fetchAll("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        COUNT(*) as transaction_count,
        SUM(amount) as monthly_total
    FROM payments 
    WHERE status = 'approved' 
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY month DESC
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Financial Reports</h1>
                    <p class="text-muted">Comprehensive financial analytics and reporting</p>
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
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type">
                                <option value="summary" <?= $report_type === 'summary' ? 'selected' : '' ?>>Summary</option>
                                <option value="detailed" <?= $report_type === 'detailed' ? 'selected' : '' ?>>Detailed</option>
                                <option value="course_wise" <?= $report_type === 'course_wise' ? 'selected' : '' ?>>Course-wise</option>
                                <option value="payment_methods" <?= $report_type === 'payment_methods' ? 'selected' : '' ?>>Payment Methods</option>
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

    <!-- Financial Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="summary-card income">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3>KSh <?= number_format($financial_summary['total_income']) ?></h3>
                    <p>Total Income</p>
                    <small class="text-muted"><?= $start_date ?> to <?= $end_date ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card outstanding">
                <div class="card-icon">📋</div>
                <div class="card-content">
                    <h3>KSh <?= number_format($financial_summary['total_outstanding']) ?></h3>
                    <p>Outstanding Fees</p>
                    <small class="text-muted">All pending payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card transactions">
                <div class="card-icon">📊</div>
                <div class="card-content">
                    <h3><?= number_format($financial_summary['total_payments_count']) ?></h3>
                    <p>Transactions</p>
                    <small class="text-muted">Completed payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card overdue">
                <div class="card-icon">⚠️</div>
                <div class="card-content">
                    <h3>KSh <?= number_format($financial_summary['overdue_amount']) ?></h3>
                    <p>Overdue Amount</p>
                    <small class="text-muted">Past due date</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Payment Methods Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Payment Methods Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentMethodsChart" width="400" height="300"></canvas>
                    <div class="mt-3">
                        <?php foreach ($payment_methods as $method): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-capitalize"><?= htmlspecialchars($method['payment_method']) ?></span>
                            <div class="text-end">
                                <strong>KSh <?= number_format($method['total_amount']) ?></strong>
                                <br><small class="text-muted"><?= $method['transaction_count'] ?> transactions</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trends Chart -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Monthly Collection Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyTrendsChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Course-wise Revenue -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Course-wise Revenue Analysis</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Students Paid</th>
                                    <th>Total Revenue</th>
                                    <th>Average Payment</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($course_revenue as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($course['course_name']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($course['course_code']) ?></small>
                                    </td>
                                    <td><?= $course['students_paid'] ?></td>
                                    <td><strong>KSh <?= number_format($course['total_revenue']) ?></strong></td>
                                    <td>KSh <?= number_format($course['average_payment']) ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php 
                                            $max_revenue = $course_revenue[0]['total_revenue'] ?? 1;
                                            $percentage = ($course['total_revenue'] / $max_revenue) * 100;
                                            ?>
                                            <div class="progress-bar bg-success" style="width: <?= $percentage ?>%">
                                                <?= round($percentage, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding Fees by Course -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Outstanding Fees by Course</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Students with Balance</th>
                                    <th>Total Outstanding</th>
                                    <th>Risk Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($outstanding_by_course as $course): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($course['course_name']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($course['course_code']) ?></small>
                                    </td>
                                    <td><?= $course['students_with_balance'] ?></td>
                                    <td><strong>KSh <?= number_format($course['total_outstanding']) ?></strong></td>
                                    <td>
                                        <?php 
                                        $risk_level = 'Low';
                                        $badge_class = 'success';
                                        if ($course['total_outstanding'] > 100000) {
                                            $risk_level = 'High';
                                            $badge_class = 'danger';
                                        } elseif ($course['total_outstanding'] > 50000) {
                                            $risk_level = 'Medium';
                                            $badge_class = 'warning';
                                        }
                                        ?>
                                        <span class="badge bg-<?= $badge_class ?>"><?= $risk_level ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Collections -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Daily Collections Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Transactions</th>
                                    <th>Total Amount</th>
                                    <th>Average per Transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($daily_collections as $day): ?>
                                <tr>
                                    <td><?= date('D, M j, Y', strtotime($day['payment_date'])) ?></td>
                                    <td><?= $day['transaction_count'] ?></td>
                                    <td><strong>KSh <?= number_format($day['daily_total']) ?></strong></td>
                                    <td>KSh <?= number_format($day['daily_total'] / $day['transaction_count']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
    position: relative;
    overflow: hidden;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.summary-card.income {
    border-left: 5px solid #28a745;
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
}

.summary-card.outstanding {
    border-left: 5px solid #dc3545;
    background: linear-gradient(135deg, #ffffff 0%, #fff8f8 100%);
}

.summary-card.transactions {
    border-left: 5px solid #007bff;
    background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
}

.summary-card.overdue {
    border-left: 5px solid #ffc107;
    background: linear-gradient(135deg, #ffffff 0%, #fffdf8 100%);
}

.card-icon {
    font-size: 3rem;
    opacity: 0.8;
    background: rgba(255,255,255,0.9);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.card-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
}

.card-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-weight: 500;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}

.card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-radius: 15px;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.12);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid #e9ecef;
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.btn-group .btn {
    border-radius: 8px;
    margin: 0 2px;
    transition: all 0.3s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    transform: translateY(-1px);
}

.badge {
    padding: 0.5em 0.75em;
    border-radius: 8px;
    font-weight: 500;
}

.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .summary-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .card-icon {
        font-size: 2rem;
        width: 60px;
        height: 60px;
    }
    
    .card-content h3 {
        font-size: 1.5rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        margin: 0;
    }
}

/* Loading animation for charts */
.chart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #6c757d;
}

.chart-loading::after {
    content: '';
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 10px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhanced table styling */
.table-responsive {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,0.02);
}
</style>

<script>
// Payment Methods Chart
function createPaymentMethodsChart() {
    const ctx = document.getElementById('paymentMethodsChart').getContext('2d');
    const data = <?= json_encode($payment_methods) ?>;
    
    if (data.length === 0) {
        ctx.fillStyle = '#999';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', ctx.canvas.width/2, ctx.canvas.height/2);
        return;
    }
    
    // Simple pie chart implementation
    const total = data.reduce((sum, item) => sum + parseFloat(item.total_amount), 0);
    let currentAngle = 0;
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'];
    
    data.forEach((item, index) => {
        const sliceAngle = (parseFloat(item.total_amount) / total) * 2 * Math.PI;
        
        ctx.beginPath();
        ctx.arc(200, 150, 100, currentAngle, currentAngle + sliceAngle);
        ctx.lineTo(200, 150);
        ctx.fillStyle = colors[index % colors.length];
        ctx.fill();
        
        currentAngle += sliceAngle;
    });
}

// Monthly Trends Chart
function createMonthlyTrendsChart() {
    const ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
    const data = <?= json_encode($monthly_trends) ?>;
    
    if (data.length === 0) {
        ctx.fillStyle = '#999';
        ctx.font = '16px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', ctx.canvas.width/2, ctx.canvas.height/2);
        return;
    }
    
    // Simple line chart implementation
    const maxAmount = Math.max(...data.map(d => parseFloat(d.monthly_total)));
    const width = ctx.canvas.width - 80;
    const height = ctx.canvas.height - 80;
    
    ctx.strokeStyle = '#007bff';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    data.reverse().forEach((item, index) => {
        const x = 40 + (index / (data.length - 1)) * width;
        const y = 40 + height - (parseFloat(item.monthly_total) / maxAmount) * height;
        
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
    window.open('/college_management_system/accounts/export_report.php?' + params.toString(), '_blank');
}

function exportExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/accounts/export_report.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}

function resetFilters() {
    window.location.href = '/college_management_system/accounts/financial_reports.php';
}

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    createPaymentMethodsChart();
    createMonthlyTrendsChart();
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

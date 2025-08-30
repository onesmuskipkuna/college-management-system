<?php
/**
 * Accounts Dashboard
 * Financial overview and fee management dashboard
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require accounts role
Authentication::requireRole('accounts');

// Page configuration
$page_title = 'Accounts Dashboard';
$show_page_header = true;
$page_subtitle = 'Financial overview and fee management';

// Get current user
$current_user = Authentication::getCurrentUser();

// Get financial statistics
$total_fees_due = fetchOne("SELECT SUM(balance) as total FROM student_fees WHERE balance > 0")['total'] ?? 0;
$total_fees_collected = fetchOne("SELECT SUM(amount) as total FROM payments WHERE status = 'approved'")['total'] ?? 0;
$pending_payments = fetchOne("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'] ?? 0;
$overdue_fees = fetchOne("SELECT COUNT(*) as count FROM student_fees WHERE balance > 0 AND due_date < CURDATE()")['count'] ?? 0;

// Get recent payments
$recent_payments = fetchAll(
    "SELECT p.*, s.first_name, s.last_name, s.student_id, fs.fee_type
     FROM payments p
     JOIN students s ON p.student_id = s.id
     JOIN student_fees sf ON p.student_fee_id = sf.id
     JOIN fee_structure fs ON sf.fee_structure_id = fs.id
     ORDER BY p.created_at DESC
     LIMIT 10"
);

// Get monthly collection data for chart
$monthly_collections = fetchAll(
    "SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as total_amount,
        COUNT(*) as payment_count
     FROM payments 
     WHERE status = 'approved' 
       AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
     ORDER BY month DESC"
);

// Get payment method statistics
$payment_methods = fetchAll(
    "SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total_amount
     FROM payments 
     WHERE status = 'approved'
       AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY payment_method
     ORDER BY total_amount DESC"
);

// Include header
include __DIR__ . '/../header.php'; 
?>

<style>
    .hero-banner {
        margin-bottom: 2rem;
        overflow: hidden;
        border-radius: 15px;
    }
    
    .hero-banner img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 15px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .payment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.3s ease;
    }
    
    .payment-item:hover {
        background-color: #f8f9fa;
    }
    
    .payment-item:last-child {
        border-bottom: none;
    }
    
    .payment-details h6 {
        margin: 0 0 0.25rem 0;
        color: #2c3e50;
    }
    
    .payment-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .payment-amount {
        font-weight: 600;
        color: #28a745;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .action-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
        text-decoration: none;
        color: #495057;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .action-card:hover {
        transform: translateY(-2px);
        color: #007bff;
        text-decoration: none;
        box-shadow: 0 5px 25px rgba(0,0,0,0.12);
    }
    
    .method-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .method-stat:last-child {
        border-bottom: none;
    }
    
    .method-name {
        font-weight: 500;
        text-transform: capitalize;
    }
    
    .method-amount {
        font-weight: 600;
        color: #007bff;
    }
</style>

<!-- Hero Banner -->
<div class="hero-banner mb-4">
    <img src="https://storage.googleapis.com/workspace-0f70711f-8b4e-4d94-86f1-2a93ccde5887/image/4d98e080-cd64-44df-bf18-12e4b79934d5.png" 
         alt="Modern accounts dashboard header with clean layout and responsive design" 
         class="img-fluid rounded shadow-sm"
         onerror="this.style.display='none'">
</div>

<!-- Financial Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">₹</div>
        <div class="stat-value text-danger"><?php echo formatCurrency($total_fees_due); ?></div>
        <div class="stat-label">Outstanding Fees</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #1e7e34);">💰</div>
        <div class="stat-value text-success"><?php echo formatCurrency($total_fees_collected); ?></div>
        <div class="stat-label">Total Collected</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">⏳</div>
        <div class="stat-value text-warning"><?php echo $pending_payments; ?></div>
        <div class="stat-label">Pending Payments</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #fd7e14, #e55a00);">⚠️</div>
        <div class="stat-value text-danger"><?php echo $overdue_fees; ?></div>
        <div class="stat-label">Overdue Accounts</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="/college_management_system/accounts/fee_receive.php" class="action-card">
        <h5>Receive Payment</h5>
        <p>Process student fee payments</p>
    </a>
    
    <a href="/college_management_system/accounts/fee_balance_report.php" class="action-card">
        <h5>Fee Reports</h5>
        <p>View detailed fee reports</p>
    </a>
    
    <a href="/college_management_system/accounts/course_fee_structure.php" class="action-card">
        <h5>Fee Structure</h5>
        <p>Manage course fee structures</p>
    </a>
    
    <a href="/college_management_system/accounts/p_l_report.php" class="action-card">
        <h5>P&L Report</h5>
        <p>Profit and loss statements</p>
    </a>
</div>

<!-- Main Content Grid -->
<div class="row">
    <!-- Recent Payments -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recent Payments</h5>
                <a href="/college_management_system/accounts/payment_history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_payments)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No recent payments found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="payment-item">
                            <div class="payment-details">
                                <h6><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></h6>
                                <div class="payment-meta">
                                    <?php echo htmlspecialchars($payment['student_id']); ?> | 
                                    <?php echo htmlspecialchars($payment['fee_type']); ?> | 
                                    <?php echo formatDate($payment['payment_date']); ?> |
                                    <span class="text-capitalize"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                                </div>
                            </div>
                            <div class="payment-amount">
                                <?php echo formatCurrency($payment['amount']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Stats -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Payment Methods (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($payment_methods)): ?>
                    <p class="text-muted text-center">No payment data available</p>
                <?php else: ?>
                    <?php foreach ($payment_methods as $method): ?>
                        <div class="method-stat">
                            <div class="method-name"><?php echo htmlspecialchars($method['payment_method']); ?></div>
                            <div>
                                <div class="method-amount"><?php echo formatCurrency($method['total_amount']); ?></div>
                                <small class="text-muted"><?php echo $method['count']; ?> payments</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Monthly Collection Chart -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title">Monthly Collections</h5>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Alerts and Notifications -->
<?php if ($overdue_fees > 0): ?>
<div class="alert alert-warning mt-4">
    <h6>⚠️ Attention Required</h6>
    <p class="mb-0">
        There are <strong><?php echo $overdue_fees; ?></strong> overdue fee accounts that need immediate attention.
        <a href="/college_management_system/accounts/overdue_report.php" class="alert-link">View overdue accounts</a>
    </p>
</div>
<?php endif; ?>

<?php if ($pending_payments > 0): ?>
<div class="alert alert-info mt-2">
    <h6>📋 Pending Approvals</h6>
    <p class="mb-0">
        There are <strong><?php echo $pending_payments; ?></strong> payments waiting for approval.
        <a href="/college_management_system/accounts/pending_payments.php" class="alert-link">Review pending payments</a>
    </p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly collections chart
    const ctx = document.getElementById('monthlyChart');
    if (ctx) {
        const monthlyData = <?php echo json_encode($monthly_collections); ?>;
        
        // Simple chart implementation (you can replace with Chart.js for more features)
        const canvas = ctx.getContext('2d');
        const width = ctx.width;
        const height = ctx.height;
        
        if (monthlyData.length > 0) {
            const maxAmount = Math.max(...monthlyData.map(d => parseFloat(d.total_amount)));
            const barWidth = width / monthlyData.length * 0.8;
            const barSpacing = width / monthlyData.length * 0.2;
            
            monthlyData.forEach((data, index) => {
                const barHeight = (parseFloat(data.total_amount) / maxAmount) * (height * 0.8);
                const x = index * (barWidth + barSpacing) + barSpacing / 2;
                const y = height - barHeight - 20;
                
                // Draw bar
                canvas.fillStyle = '#007bff';
                canvas.fillRect(x, y, barWidth, barHeight);
                
                // Draw month label
                canvas.fillStyle = '#666';
                canvas.font = '12px Arial';
                canvas.textAlign = 'center';
                canvas.fillText(data.month.substring(5), x + barWidth/2, height - 5);
            });
        } else {
            canvas.fillStyle = '#999';
            canvas.font = '14px Arial';
            canvas.textAlign = 'center';
            canvas.fillText('No data available', width/2, height/2);
        }
    }
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        fetch('/college_management_system/api/dashboard_stats.php?module=accounts')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update statistics without full page reload
                    updateDashboardStats(data.stats);
                }
            })
            .catch(error => {
                console.error('Error updating dashboard:', error);
            });
    }, 300000); // 5 minutes
    
    function updateDashboardStats(stats) {
        // Update stat values
        document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = 
            'KSh ' + parseFloat(stats.total_fees_due).toLocaleString();
        document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = 
            'KSh ' + parseFloat(stats.total_fees_collected).toLocaleString();
        document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = 
            stats.pending_payments;
        document.querySelector('.stat-card:nth-child(4) .stat-value').textContent = 
            stats.overdue_fees;
    }
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Pending Payments
 * Manage and approve pending payment transactions
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $payment_ids = $_POST['payment_ids'] ?? [];
    $action = $_POST['bulk_action'];
    $reason = $_POST['reason'] ?? '';
    
    if (!empty($payment_ids) && in_array($action, ['approve', 'reject'])) {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($payment_ids as $payment_id) {
            try {
                if ($action === 'approve') {
                    // Approve payment
                    $stmt = $pdo->prepare("
                        UPDATE payments 
                        SET status = 'approved', 
                            approved_by = ?, 
                            approved_at = NOW(),
                            notes = CONCAT(COALESCE(notes, ''), '\nBulk approved by: " . $user['first_name'] . " " . $user['last_name'] . "')
                        WHERE id = ? AND status = 'pending'
                    ");
                    
                    if ($stmt->execute([$user['id'], $payment_id])) {
                        // Update student fee balance
                        $payment = fetchOne("SELECT * FROM payments WHERE id = ?", [$payment_id]);
                        if ($payment) {
                            $stmt2 = $pdo->prepare("
                                UPDATE student_fees 
                                SET amount_paid = amount_paid + ?, 
                                    balance = amount_due - (amount_paid + ?),
                                    status = CASE 
                                        WHEN (amount_due - (amount_paid + ?)) <= 0 THEN 'paid'
                                        ELSE 'partial'
                                    END
                                WHERE id = ?
                            ");
                            $stmt2->execute([$payment['amount'], $payment['amount'], $payment['amount'], $payment['student_fee_id']]);
                        }
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } else {
                    // Reject payment
                    $stmt = $pdo->prepare("
                        UPDATE payments 
                        SET status = 'rejected', 
                            rejected_by = ?, 
                            rejected_at = NOW(),
                            rejection_reason = ?,
                            notes = CONCAT(COALESCE(notes, ''), '\nBulk rejected by: " . $user['first_name'] . " " . $user['last_name'] . "')
                        WHERE id = ? AND status = 'pending'
                    ");
                    
                    if ($stmt->execute([$user['id'], $reason, $payment_id])) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            } catch (Exception $e) {
                $error_count++;
                error_log("Error processing payment $payment_id: " . $e->getMessage());
            }
        }
        
        if ($success_count > 0) {
            $success_message = "Successfully {$action}d $success_count payments.";
        }
        if ($error_count > 0) {
            $error_message = "Failed to process $error_count payments.";
        }
    }
}

// Get filter parameters
$payment_method = $_GET['payment_method'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build query conditions
$conditions = ["p.status = 'pending'"];
$params = [];

if ($payment_method) {
    $conditions[] = "p.payment_method = ?";
    $params[] = $payment_method;
}

if ($date_from) {
    $conditions[] = "p.payment_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "p.payment_date <= ?";
    $params[] = $date_to;
}

if ($amount_min) {
    $conditions[] = "p.amount >= ?";
    $params[] = $amount_min;
}

if ($amount_max) {
    $conditions[] = "p.amount <= ?";
    $params[] = $amount_max;
}

$where_clause = implode(' AND ', $conditions);

// Validate sort parameters
$valid_sort_columns = ['created_at', 'payment_date', 'amount', 'student_name', 'payment_method'];
$valid_sort_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $valid_sort_columns)) $sort_by = 'created_at';
if (!in_array($sort_order, $valid_sort_orders)) $sort_order = 'DESC';

$order_clause = match($sort_by) {
    'student_name' => "s.last_name $sort_order, s.first_name $sort_order",
    default => "p.$sort_by $sort_order"
};

// Get pending payments
$pending_payments = fetchAll("
    SELECT 
        p.*,
        s.first_name,
        s.last_name,
        s.student_id,
        s.phone,
        s.email,
        c.course_name,
        c.course_code,
        fs.fee_type,
        sf.amount_due,
        sf.balance,
        TIMESTAMPDIFF(HOUR, p.created_at, NOW()) as hours_pending,
        CASE 
            WHEN TIMESTAMPDIFF(HOUR, p.created_at, NOW()) <= 24 THEN 'Recent'
            WHEN TIMESTAMPDIFF(HOUR, p.created_at, NOW()) <= 72 THEN 'Moderate'
            ELSE 'Urgent'
        END as urgency_level
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN student_fees sf ON p.student_fee_id = sf.id
    JOIN fee_structure fs ON sf.fee_structure_id = fs.id
    JOIN courses c ON s.course_id = c.id
    WHERE $where_clause
    ORDER BY $order_clause
", $params);

// Get summary statistics
$summary_stats = fetchOne("
    SELECT 
        COUNT(*) as total_pending,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount,
        COUNT(CASE WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) > 72 THEN 1 END) as urgent_count,
        COUNT(CASE WHEN payment_method = 'mpesa' THEN 1 END) as mpesa_count,
        COUNT(CASE WHEN payment_method = 'bank' THEN 1 END) as bank_count,
        COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_count
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN student_fees sf ON p.student_fee_id = sf.id
    JOIN fee_structure fs ON sf.fee_structure_id = fs.id
    WHERE $where_clause
", $params);

// Get payment methods for filter
$payment_methods = fetchAll("
    SELECT DISTINCT payment_method 
    FROM payments 
    WHERE payment_method IS NOT NULL AND status = 'pending'
    ORDER BY payment_method
");

// Group by urgency levels
$urgency_stats = [
    'Recent' => ['count' => 0, 'amount' => 0, 'color' => 'success'],
    'Moderate' => ['count' => 0, 'amount' => 0, 'color' => 'warning'],
    'Urgent' => ['count' => 0, 'amount' => 0, 'color' => 'danger']
];

foreach ($pending_payments as $payment) {
    $urgency = $payment['urgency_level'];
    $urgency_stats[$urgency]['count']++;
    $urgency_stats[$urgency]['amount'] += $payment['amount'];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Pending Payments</h1>
                    <p class="text-muted">Review and approve pending payment transactions</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-success" onclick="approveAllVisible()">Approve All Visible</button>
                    <button class="btn btn-outline-primary" onclick="exportPendingPayments()">Export Report</button>
                    <button class="btn btn-outline-info" onclick="refreshPayments()">Refresh</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">⏳</div>
                <div class="stat-content">
                    <h3><?= number_format($summary_stats['total_pending']) ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['total_amount']) ?></h3>
                    <p>Total Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">🚨</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['urgent_count'] ?></h3>
                    <p>Urgent (72+ hours)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-secondary">📊</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['avg_amount']) ?></h3>
                    <p>Average Amount</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Urgency Level Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Payment Urgency Levels</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($urgency_stats as $level => $data): ?>
                            <div class="col-md-4">
                                <div class="urgency-card border-<?= $data['color'] ?>">
                                    <div class="urgency-header bg-<?= $data['color'] ?> text-white">
                                        <h6><?= $level ?></h6>
                                        <small>
                                            <?php
                                            switch($level) {
                                                case 'Recent': echo '0-24 hours'; break;
                                                case 'Moderate': echo '24-72 hours'; break;
                                                case 'Urgent': echo '72+ hours'; break;
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div class="urgency-body">
                                        <div class="urgency-stat">
                                            <strong><?= $data['count'] ?></strong>
                                            <span>Payments</span>
                                        </div>
                                        <div class="urgency-stat">
                                            <strong>KSh <?= number_format($data['amount']) ?></strong>
                                            <span>Amount</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Filter Pending Payments</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <?php foreach ($payment_methods as $method): ?>
                                    <option value="<?= htmlspecialchars($method['payment_method']) ?>" 
                                            <?= $payment_method === $method['payment_method'] ? 'selected' : '' ?>>
                                        <?= ucfirst(htmlspecialchars($method['payment_method'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="amount_min" class="form-label">Min Amount</label>
                            <input type="number" class="form-control" id="amount_min" name="amount_min" 
                                   value="<?= $amount_min ?>" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Date Created</option>
                                <option value="payment_date" <?= $sort_by === 'payment_date' ? 'selected' : '' ?>>Payment Date</option>
                                <option value="amount" <?= $sort_by === 'amount' ? 'selected' : '' ?>>Amount</option>
                                <option value="student_name" <?= $sort_by === 'student_name' ? 'selected' : '' ?>>Student Name</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Pending Payment Transactions (<?= count($pending_payments) ?> items)</h5>
                    <div class="btn-group">
                        <button class="btn btn-success btn-sm" onclick="bulkApprove()">Bulk Approve</button>
                        <button class="btn btn-danger btn-sm" onclick="bulkReject()">Bulk Reject</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_payments)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h5 class="text-success">No Pending Payments!</h5>
                            <p class="text-muted">All payments have been processed or no payments match your criteria.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="bulkActionForm">
                            <input type="hidden" name="bulk_action" id="bulk_action">
                            <input type="hidden" name="reason" id="bulk_reason">
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                            </th>
                                            <th>Urgency</th>
                                            <th>Student</th>
                                            <th>Fee Type</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Payment Date</th>
                                            <th>Pending Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_payments as $payment): ?>
                                        <tr class="payment-row <?= $payment['urgency_level'] === 'Urgent' ? 'table-warning' : '' ?>">
                                            <td>
                                                <input type="checkbox" class="payment-checkbox" 
                                                       name="payment_ids[]" value="<?= $payment['id'] ?>">
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $urgency_stats[$payment['urgency_level']]['color'] ?>">
                                                    <?= $payment['urgency_level'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="student-info">
                                                    <strong><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($payment['student_id']) ?></small><br>
                                                    <small class="text-muted"><?= htmlspecialchars($payment['phone']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fee-type"><?= htmlspecialchars($payment['fee_type']) ?></span><br>
                                                <small class="text-muted"><?= htmlspecialchars($payment['course_code']) ?></small>
                                            </td>
                                            <td>
                                                <strong class="amount">KSh <?= number_format($payment['amount'], 2) ?></strong><br>
                                                <small class="text-muted">Balance: KSh <?= number_format($payment['balance'], 2) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary text-capitalize">
                                                    <?= htmlspecialchars($payment['payment_method']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['transaction_reference']): ?>
                                                    <code class="small"><?= htmlspecialchars($payment['transaction_reference']) ?></code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= formatDate($payment['payment_date']) ?></strong><br>
                                                <small class="text-muted"><?= formatTime($payment['created_at']) ?></small>
                                            </td>
                                            <td>
                                                <span class="pending-time <?= $payment['hours_pending'] > 72 ? 'text-danger' : ($payment['hours_pending'] > 24 ? 'text-warning' : 'text-success') ?>">
                                                    <?php
                                                    if ($payment['hours_pending'] < 24) {
                                                        echo $payment['hours_pending'] . ' hours';
                                                    } else {
                                                        echo floor($payment['hours_pending'] / 24) . ' days';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="approvePayment(<?= $payment['id'] ?>)" 
                                                            title="Approve Payment">✅</button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="rejectPayment(<?= $payment['id'] ?>)" 
                                                            title="Reject Payment">❌</button>
                                                    <button type="button" class="btn btn-outline-info" 
                                                            onclick="viewPaymentDetails(<?= $payment['id'] ?>)" 
                                                            title="View Details">👁️</button>
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="verifyPayment(<?= $payment['id'] ?>)" 
                                                            title="Verify Payment">🔍</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="modalApproveBtn">Approve</button>
                <button type="button" class="btn btn-danger" id="modalRejectBtn">Reject</button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label">Please provide a reason for rejection:</label>
                    <textarea class="form-control" id="rejectionReason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">Confirm Rejection</button>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-content h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.urgency-card {
    border: 2px solid;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
}

.urgency-header {
    padding: 10px 15px;
    text-align: center;
}

.urgency-header h6 {
    margin: 0;
    font-weight: 600;
}

.urgency-body {
    padding: 15px;
    background: white;
}

.urgency-stat {
    text-align: center;
    margin-bottom: 10px;
}

.urgency-stat strong {
    display: block;
    font-size: 1.2rem;
    color: #2c3e50;
}

.urgency-stat span {
    font-size: 0.85rem;
    color: #6c757d;
}

.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.student-info {
    min-width: 150px;
}

.fee-type {
    font-weight: 500;
}

.amount {
    color: #28a745;
    font-size: 1.1rem;
}

.pending-time {
    font-weight: 600;
}

.payment-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .urgency-card {
        margin-bottom: 10px;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
let selectedPayments = [];
let currentPaymentId = null;

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.payment-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedPayments();
}

function updateSelectedPayments() {
    selectedPayments = Array.from(document.querySelectorAll('.payment-checkbox:checked'))
        .map(checkbox => checkbox.value);
}

function approvePayment(paymentId) {
    if (confirm('Are you sure you want to approve this payment?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="bulk_action" value="approve">
            <input type="hidden" name="payment_ids[]" value="${paymentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectPayment(paymentId) {
    currentPaymentId = paymentId;
    new bootstrap.Modal(document.getElementById('rejectionModal')).show();
}

function bulkApprove() {
    updateSelectedPayments();
    if (selectedPayments.length === 0) {
        alert('Please select payments to approve');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${selectedPayments.length} selected payments?`)) {
        document.getElementById('bulk_action').value = 'approve';
        document.getElementById('bulkActionForm').submit();
    }
}

function bulkReject() {
    updateSelectedPayments();
    if (selectedPayments.length === 0) {
        alert('Please select payments to reject');
        return;
    }
    
    const reason = prompt('Please provide a reason for bulk rejection:');
    if (reason) {
        document.getElementById('bulk_action').value = 'reject';
        document.getElementById('bulk_reason').value = reason;
        document.getElementById('bulkActionForm').submit();
    }
}

function viewPaymentDetails(paymentId) {
    fetch(`/college_management_system/api/get_payment_details.php?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('paymentDetailsContent').innerHTML = data.html;
                currentPaymentId = paymentId;
                
                // Set up modal buttons
                document.getElementById('modalAp

<?php
/**
 * Payment History
 * Comprehensive payment history and transaction management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Get filter parameters
$student_search = $_GET['student'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($student_search) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR s.phone LIKE ?)";
    $search_term = "%$student_search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

if ($payment_method) {
    $conditions[] = "p.payment_method = ?";
    $params[] = $payment_method;
}

if ($status_filter) {
    $conditions[] = "p.status = ?";
    $params[] = $status_filter;
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

// Get total count for pagination
$total_count = fetchOne("
    SELECT COUNT(*) as count
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN student_fees sf ON p.student_fee_id = sf.id
    JOIN fee_structure fs ON sf.fee_structure_id = fs.id
    WHERE $where_clause
", $params)['count'];

$total_pages = ceil($total_count / $per_page);

// Get payment history data
$payments = fetchAll("
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
        sf.balance as remaining_balance,
        u.first_name as processed_by_fname,
        u.last_name as processed_by_lname
    FROM payments p
    JOIN students s ON p.student_id = s.id
    JOIN student_fees sf ON p.student_fee_id = sf.id
    JOIN fee_structure fs ON sf.fee_structure_id = fs.id
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN users u ON p.processed_by = u.id
    WHERE $where_clause
    ORDER BY p.payment_date DESC, p.created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Get summary statistics
$summary_stats = fetchOne("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected,
        AVG(CASE WHEN status = 'approved' THEN amount ELSE NULL END) as avg_payment
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
    WHERE payment_method IS NOT NULL 
    ORDER BY payment_method
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Payment History</h1>
                <p class="text-muted">Comprehensive payment transaction history and management</p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">📊</div>
                <div class="stat-content">
                    <h3><?= number_format($summary_stats['total_transactions']) ?></h3>
                    <p>Total Transactions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">✅</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['total_approved']) ?></h3>
                    <p>Approved Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">⏳</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['total_pending']) ?></h3>
                    <p>Pending Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['avg_payment']) ?></h3>
                    <p>Average Payment</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Search & Filter Payments</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="student" class="form-label">Student Search</label>
                            <input type="text" class="form-control" id="student" name="student" 
                                   placeholder="Name, ID, or Phone" value="<?= htmlspecialchars($student_search) ?>">
                        </div>
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
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
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
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">Search</button>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="amount_min" class="form-label">Min Amount</label>
                            <input type="number" class="form-control" id="amount_min" name="amount_min" 
                                   placeholder="0" value="<?= $amount_min ?>" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label for="amount_max" class="form-label">Max Amount</label>
                            <input type="number" class="form-control" id="amount_max" name="amount_max" 
                                   placeholder="999999" value="<?= $amount_max ?>" step="0.01">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-success" onclick="exportPayments()">Export</button>
                                <button type="button" class="btn btn-info" onclick="printPayments()">Print</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Payment Transactions (<?= number_format($total_count) ?> total)</h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm" onclick="bulkApprove()">Bulk Approve</button>
                        <button class="btn btn-outline-danger btn-sm" onclick="bulkReject()">Bulk Reject</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-search fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No payments found</h5>
                            <p class="text-muted">Try adjusting your search criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr class="payment-row" data-payment-id="<?= $payment['id'] ?>">
                                        <td>
                                            <input type="checkbox" class="payment-checkbox" value="<?= $payment['id'] ?>">
                                        </td>
                                        <td>
                                            <strong><?= formatDate($payment['payment_date']) ?></strong><br>
                                            <small class="text-muted"><?= formatTime($payment['created_at']) ?></small>
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
                                            <?php if ($payment['remaining_balance'] > 0): ?>
                                                <small class="text-warning">Balance: KSh <?= number_format($payment['remaining_balance'], 2) ?></small>
                                            <?php else: ?>
                                                <small class="text-success">Fully Paid</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-capitalize">
                                                <?= htmlspecialchars($payment['payment_method']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['transaction_reference']): ?>
                                                <code><?= htmlspecialchars($payment['transaction_reference']) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'approved' => 'success',
                                                'pending' => 'warning',
                                                'rejected' => 'danger'
                                            ][$payment['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $status_class ?>">
                                                <?= ucfirst($payment['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['processed_by_fname']): ?>
                                                <small><?= htmlspecialchars($payment['processed_by_fname'] . ' ' . $payment['processed_by_lname']) ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-info" onclick="viewPayment(<?= $payment['id'] ?>)" title="View Details">
                                                    👁️
                                                </button>
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <button class="btn btn-outline-success" onclick="approvePayment(<?= $payment['id'] ?>)" title="Approve">
                                                        ✅
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="rejectPayment(<?= $payment['id'] ?>)" title="Reject">
                                                        ❌
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline-primary" onclick="printReceipt(<?= $payment['id'] ?>)" title="Print Receipt">
                                                    🖨️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Payment history pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
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
                <button type="button" class="btn btn-primary" onclick="printCurrentPayment()">Print Receipt</button>
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

.payment-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
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
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
let selectedPayments = [];

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

function viewPayment(paymentId) {
    fetch(`/college_management_system/api/get_payment_details.php?id=${paymentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('paymentDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
            } else {
                alert('Error loading payment details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading payment details');
        });
}

function approvePayment(paymentId) {
    if (confirm('Are you sure you want to approve this payment?')) {
        updatePaymentStatus(paymentId, 'approved');
    }
}

function rejectPayment(paymentId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        updatePaymentStatus(paymentId, 'rejected', reason);
    }
}

function updatePaymentStatus(paymentId, status, reason = '') {
    fetch('/college_management_system/api/update_payment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_id: paymentId,
            status: status,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating payment status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating payment status');
    });
}

function bulkApprove() {
    updateSelectedPayments();
    if (selectedPayments.length === 0) {
        alert('Please select payments to approve');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${selectedPayments.length} selected payments?`)) {
        bulkUpdateStatus('approved');
    }
}

function bulkReject() {
    updateSelectedPayments();
    if (selectedPayments.length === 0) {
        alert('Please select payments to reject');
        return;
    }
    
    const reason = prompt('Please provide a reason for rejection:');
    if (reason) {
        bulkUpdateStatus('rejected', reason);
    }
}

function bulkUpdateStatus(status, reason = '') {
    fetch('/college_management_system/api/bulk_update_payments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_ids: selectedPayments,
            status: status,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully updated ${data.updated_count} payments`);
            location.reload();
        } else {
            alert('Error updating payments: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating payments');
    });
}

function printReceipt(paymentId) {
    window.open(`/college_management_system/accounts/print_receipt.php?id=${paymentId}`, '_blank');
}

function printCurrentPayment() {
    // This would print the currently viewed payment in the modal
    window.print();
}

function exportPayments() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/accounts/export_payments.php?' + params.toString(), '_blank');
}

function printPayments() {
    window.print();
}

// Add event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.payment-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedPayments);
    });
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

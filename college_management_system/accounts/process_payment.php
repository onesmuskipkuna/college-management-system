<?php
/**
 * Payment Processing System
 * Handle student fee payments and generate receipts
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'process_payment') {
        $student_id = $_POST['student_id'];
        $student_fee_id = $_POST['student_fee_id'];
        $amount = $_POST['amount'];
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? null;
        $mpesa_receipt = $_POST['mpesa_receipt'] ?? null;
        $payment_date = $_POST['payment_date'];
        $notes = $_POST['notes'] ?? null;
        
        // Generate receipt number
        $receipt_number = 'RCP' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        try {
            // Insert payment record
            $stmt = $pdo->prepare("
                INSERT INTO payments (student_id, student_fee_id, amount, payment_method, reference_number, 
                                    mpesa_receipt, payment_date, received_by, receipt_number, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
            ");
            
            if ($stmt->execute([$student_id, $student_fee_id, $amount, $payment_method, $reference_number, 
                              $mpesa_receipt, $payment_date, $user['id'], $receipt_number, $notes])) {
                
                // Update student fee balance
                $update_stmt = $pdo->prepare("
                    UPDATE student_fees 
                    SET amount_paid = amount_paid + ?, 
                        status = CASE 
                            WHEN (amount_paid + ?) >= amount_due THEN 'paid'
                            WHEN (amount_paid + ?) > 0 THEN 'partial'
                            ELSE 'pending'
                        END
                    WHERE id = ?
                ");
                $update_stmt->execute([$amount, $amount, $amount, $student_fee_id]);
                
                $success_message = "Payment processed successfully! Receipt Number: " . $receipt_number;
                $payment_id = $pdo->lastInsertId();
            } else {
                $error_message = "Error processing payment.";
            }
        } catch (Exception $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get students with outstanding fees
$students_with_fees = fetchAll("
    SELECT DISTINCT s.id, s.student_id, s.first_name, s.last_name, c.course_name,
           SUM(sf.balance) as total_balance
    FROM students s
    JOIN courses c ON s.course_id = c.id
    JOIN student_fees sf ON s.id = sf.student_id
    WHERE sf.balance > 0
    GROUP BY s.id
    ORDER BY s.first_name, s.last_name
");

// Payment statistics
$payment_stats = [
    'today_payments' => fetchOne("SELECT COUNT(*) as count FROM payments WHERE DATE(payment_date) = CURDATE()")['count'] ?? 0,
    'today_amount' => fetchOne("SELECT SUM(amount) as total FROM payments WHERE DATE(payment_date) = CURDATE() AND status = 'approved'")['total'] ?? 0,
    'pending_payments' => fetchOne("SELECT COUNT(*) as count FROM payments WHERE status = 'pending'")['count'] ?? 0,
    'this_month_amount' => fetchOne("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) AND status = 'approved'")['total'] ?? 0
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Payment Processing</h1>
                <p class="text-muted">Process student fee payments and generate receipts</p>
            </div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">💰</div>
                <div class="stat-content">
                    <h3><?= $payment_stats['today_payments'] ?></h3>
                    <p>Today's Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">📊</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($payment_stats['today_amount']) ?></h3>
                    <p>Today's Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">⏳</div>
                <div class="stat-content">
                    <h3><?= $payment_stats['pending_payments'] ?></h3>
                    <p>Pending Approvals</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">📈</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($payment_stats['this_month_amount']) ?></h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Payment Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Process New Payment</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?= $success_message ?>
                            <?php if (isset($payment_id)): ?>
                                <br><a href="print_receipt.php?id=<?= $payment_id ?>" class="btn btn-sm btn-outline-primary mt-2">Print Receipt</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="paymentForm" class="row g-3">
                        <input type="hidden" name="action" value="process_payment">
                        
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select" id="student_id" name="student_id" required onchange="loadStudentFees()">
                                <option value="">Select Student</option>
                                <?php foreach ($students_with_fees as $student): ?>
                                    <option value="<?= $student['id'] ?>" data-balance="<?= $student['total_balance'] ?>">
                                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> 
                                        (<?= htmlspecialchars($student['student_id']) ?>) - 
                                        Balance: KSh <?= number_format($student['total_balance']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="student_fee_id" class="form-label">Fee Type</label>
                            <select class="form-select" id="student_fee_id" name="student_fee_id" required onchange="updatePaymentAmount()">
                                <option value="">Select Fee Type</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Payment Amount (KSh)</label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required onchange="togglePaymentFields()">
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="card">Card</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="col-md-6" id="reference_field" style="display: none;">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Bank/Cheque reference">
                        </div>
                        
                        <div class="col-md-6" id="mpesa_field" style="display: none;">
                            <label for="mpesa_receipt" class="form-label">M-Pesa Receipt</label>
                            <input type="text" class="form-control" id="mpesa_receipt" name="mpesa_receipt" placeholder="M-Pesa transaction code">
                        </div>
                        
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional payment notes"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Process Payment</button>
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                            <button type="button" class="btn btn-info" onclick="calculateChange()">Calculate Change</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions & Recent Payments -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="openMpesaSTK()">M-Pesa STK Push</button>
                        <button class="btn btn-outline-success btn-sm" onclick="bulkPaymentMode()">Bulk Payment</button>
                        <button class="btn btn-outline-info btn-sm" onclick="viewPendingPayments()">Pending Payments</button>
                        <button class="btn btn-outline-warning btn-sm" onclick="generateDailyReport()">Daily Report</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6>Recent Payments</h6>
                </div>
                <div class="card-body p-0">
                    <div class="recent-payments" id="recentPayments">
                        <!-- Recent payments will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- M-Pesa STK Modal -->
<div class="modal fade" id="mpesaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">M-Pesa STK Push</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="mpesaForm">
                    <div class="mb-3">
                        <label for="mpesa_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="mpesa_phone" placeholder="254XXXXXXXXX" required>
                    </div>
                    <div class="mb-3">
                        <label for="mpesa_amount" class="form-label">Amount (KSh)</label>
                        <input type="number" class="form-control" id="mpesa_amount" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="mpesa_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="mpesa_description" placeholder="Fee payment" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="initiateMpesaPayment()">Send STK Push</button>
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

.recent-payments {
    max-height: 400px;
    overflow-y: auto;
}

.payment-item {
    padding: 10px 15px;
    border-bottom: 1px solid #f8f9fa;
    font-size: 0.875rem;
}

.payment-item:last-child {
    border-bottom: none;
}

.payment-amount {
    font-weight: 600;
    color: #28a745;
}

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
function loadStudentFees() {
    const studentId = document.getElementById('student_id').value;
    const feeSelect = document.getElementById('student_fee_id');
    
    if (!studentId) {
        feeSelect.innerHTML = '<option value="">Select Fee Type</option>';
        return;
    }
    
    fetch(`/college_management_system/api/get_student_fees.php?student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            feeSelect.innerHTML = '<option value="">Select Fee Type</option>';
            if (data.success && data.fees) {
                data.fees.forEach(fee => {
                    if (fee.balance > 0) {
                        const option = document.createElement('option');
                        option.value = fee.id;
                        option.textContent = `${fee.fee_type} - Balance: KSh ${parseFloat(fee.balance).toLocaleString()}`;
                        option.dataset.balance = fee.balance;
                        feeSelect.appendChild(option);
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error loading student fees:', error);
        });
}

function updatePaymentAmount() {
    const feeSelect = document.getElementById('student_fee_id');
    const amountInput = document.getElementById('amount');
    
    if (feeSelect.selectedIndex > 0) {
        const balance = feeSelect.options[feeSelect.selectedIndex].dataset.balance;
        amountInput.value = balance;
        amountInput.max = balance;
    }
}

function togglePaymentFields() {
    const method = document.getElementById('payment_method').value;
    const referenceField = document.getElementById('reference_field');
    const mpesaField = document.getElementById('mpesa_field');
    
    // Hide all fields first
    referenceField.style.display = 'none';
    mpesaField.style.display = 'none';
    
    // Show relevant fields
    if (method === 'bank' || method === 'cheque') {
        referenceField.style.display = 'block';
    } else if (method === 'mpesa') {
        mpesaField.style.display = 'block';
    }
}

function calculateChange() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const received = prompt('Enter amount received:');
    
    if (received && !isNaN(received)) {
        const change = parseFloat(received) - amount;
        if (change >= 0) {
            alert(`Change to give: KSh ${change.toFixed(2)}`);
        } else {
            alert(`Insufficient amount. Short by: KSh ${Math.abs(change).toFixed(2)}`);
        }
    }
}

function openMpesaSTK() {
    new bootstrap.Modal(document.getElementById('mpesaModal')).show();
}

function initiateMpesaPayment() {
    const phone = document.getElementById('mpesa_phone').value;
    const amount = document.getElementById('mpesa_amount').value;
    const description = document.getElementById('mpesa_description').value;
    
    if (!phone || !amount || !description) {
        alert('Please fill all fields');
        return;
    }
    
    // Call M-Pesa STK Push API
    fetch('/college_management_system/api/mpesa_stk.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            phone: phone,
            amount: amount,
            description: description
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('STK Push sent successfully! Check phone for payment prompt.');
            bootstrap.Modal.getInstance(document.getElementById('mpesaModal')).hide();
        } else {
            alert('Error sending STK Push: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing M-Pesa payment');
    });
}

function bulkPaymentMode() {
    window.location.href = '/college_management_system/accounts/bulk_payment.php';
}

function viewPendingPayments() {
    window.location.href = '/college_management_system/accounts/pending_payments.php';
}

function generateDailyReport() {
    window.open('/college_management_system/accounts/daily_report.php?date=' + new Date().toISOString().split('T')[0], '_blank');
}

function loadRecentPayments() {
    fetch('/college_management_system/api/recent_payments.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('recentPayments');
            if (data.success && data.payments) {
                container.innerHTML = data.payments.map(payment => `
                    <div class="payment-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${payment.student_name}</strong><br>
                                <small class="text-muted">${payment.fee_type}</small>
                            </div>
                            <div class="text-end">
                                <div class="payment-amount">KSh ${parseFloat(payment.amount).toLocaleString()}</div>
                                <small class="text-muted">${payment.payment_method}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="payment-item text-center text-muted">No recent payments</div>';
            }
        })
        .catch(error => {
            console.error('Error loading recent payments:', error);
        });
}

// Load recent payments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadRecentPayments();
    
    // Refresh recent payments every 30 seconds
    setInterval(loadRecentPayments, 30000);
});

// Form validation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount').value);
    const feeSelect = document.getElementById('student_fee_id');
    
    if (feeSelect.selectedIndex > 0) {
        const maxAmount = parseFloat(feeSelect.options[feeSelect.selectedIndex].dataset.balance);
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert(`Payment amount cannot exceed the outstanding balance of KSh ${maxAmount.toLocaleString()}`);
            return false;
        }
    }
    
    return confirm('Are you sure you want to process this payment?');
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

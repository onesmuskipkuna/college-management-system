<?php
/**
 * Fee Collection Module
 * Process student fee payments with multiple payment methods including M-Pesa
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require accounts role
Authentication::requireRole('accounts');

// Page configuration
$page_title = 'Receive Fee Payment';
$show_page_header = true;
$page_subtitle = 'Process student fee payments with multiple payment methods';

// Get current user
$current_user = Authentication::getCurrentUser();

// Initialize variables
$error_message = '';
$success_message = '';
$student_data = null;
$pending_fees = [];

// Handle student search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = sanitizeInput($_GET['search']);
    
    // Search for student by ID, name, or phone
    $student_data = fetchOne(
        "SELECT s.*, c.course_name, u.email
         FROM students s
         JOIN courses c ON s.course_id = c.id
         JOIN users u ON s.user_id = u.id
         WHERE s.student_id LIKE ? OR s.id_number LIKE ? OR s.phone LIKE ? 
            OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?
         LIMIT 1",
        ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"]
    );
    
    if ($student_data) {
        // Get pending fees for this student
        $pending_fees = fetchAll(
            "SELECT sf.*, fs.fee_type, fs.description
             FROM student_fees sf
             JOIN fee_structure fs ON sf.fee_structure_id = fs.id
             WHERE sf.student_id = ? AND sf.balance > 0
             ORDER BY sf.due_date ASC",
            [$student_data['id']]
        );
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $student_fee_id = intval($_POST['student_fee_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        $reference_number = sanitizeInput($_POST['reference_number'] ?? '');
        $mpesa_phone = sanitizeInput($_POST['mpesa_phone'] ?? '');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        $discount_code = sanitizeInput($_POST['discount_code'] ?? '');
        $scholarship_amount = floatval($_POST['scholarship_amount'] ?? 0);
        $discount_amount = 0;
        
        // Validation
        if (!$student_fee_id || !$amount || !$payment_method) {
            $error_message = 'Please fill in all required fields.';
        } elseif ($amount <= 0) {
            $error_message = 'Payment amount must be greater than zero.';
        } else {
            // Validate discount code if provided
            if (!empty($discount_code)) {
                $discount_info = fetchOne(
                    "SELECT * FROM discount_codes WHERE code = ? AND status = 'active' AND 
                     (expiry_date IS NULL OR expiry_date >= CURDATE())",
                    [$discount_code]
                );
                
                if (!$discount_info) {
                    $error_message = 'Invalid or expired discount code.';
                } else {
                    // Calculate discount amount
                    if ($discount_info['type'] === 'percentage') {
                        $discount_amount = ($amount * $discount_info['value']) / 100;
                    } else {
                        $discount_amount = min($discount_info['value'], $amount);
                    }
                }
            }
            
            // Validate scholarship amount
            if ($scholarship_amount > 0) {
                if ($scholarship_amount > $amount) {
                    $error_message = 'Scholarship amount cannot exceed the payment amount.';
                }
            }
        }
        
        if (!$error_message) {
            // Get student fee details
            $student_fee = fetchOne(
                "SELECT sf.*, s.id as student_id, s.first_name, s.last_name, s.student_id as student_number
                 FROM student_fees sf
                 JOIN students s ON sf.student_id = s.id
                 WHERE sf.id = ?",
                [$student_fee_id]
            );
            
            if (!$student_fee) {
                $error_message = 'Invalid fee record selected.';
            } elseif ($amount > $student_fee['balance']) {
                $error_message = 'Payment amount cannot exceed the outstanding balance.';
            } else {
                try {
                    beginTransaction();
                    
                    // Generate receipt number
                    $receipt_number = generateReceiptNumber();
                    
                    // Process M-Pesa payment if selected
                    $mpesa_receipt = null;
                    if ($payment_method === 'mpesa') {
                        if (empty($mpesa_phone)) {
                            throw new Exception('M-Pesa phone number is required');
                        }
                        
                        // Initiate M-Pesa payment (this would integrate with actual M-Pesa API)
                        $mpesa_result = initiateMpesaPayment($amount, $mpesa_phone, $receipt_number);
                        if (!$mpesa_result['success']) {
                            throw new Exception('M-Pesa payment failed: ' . $mpesa_result['message']);
                        }
                        $mpesa_receipt = $mpesa_result['receipt'] ?? null;
                    }
                    
                    // Calculate final payment amount after discounts and scholarships
                    $final_amount = $amount - $discount_amount - $scholarship_amount;
                    
                    // Insert payment record
                    $payment_data = [
                        'student_id' => $student_fee['student_id'],
                        'student_fee_id' => $student_fee_id,
                        'amount' => $final_amount,
                        'original_amount' => $amount,
                        'discount_amount' => $discount_amount,
                        'scholarship_amount' => $scholarship_amount,
                        'discount_code' => $discount_code,
                        'payment_method' => $payment_method,
                        'reference_number' => $reference_number,
                        'mpesa_receipt' => $mpesa_receipt,
                        'payment_date' => date('Y-m-d'),
                        'received_by' => $current_user['id'],
                        'receipt_number' => $receipt_number,
                        'status' => 'approved', // Auto-approve for now
                        'notes' => $notes
                    ];
                    
                    $payment_id = insertRecord('payments', $payment_data);
                    
                    // Update student fee balance
                    $new_amount_paid = $student_fee['amount_paid'] + $amount;
                    $new_balance = $student_fee['amount_due'] - $new_amount_paid;
                    $new_status = $new_balance <= 0 ? 'paid' : ($new_amount_paid > 0 ? 'partial' : 'pending');
                    
                    updateRecord('student_fees', [
                        'amount_paid' => $new_amount_paid,
                        'status' => $new_status
                    ], 'id', $student_fee_id);
                    
                    commitTransaction();
                    
                    // Log activity
                    logActivity($current_user['id'], 'payment_received', 
                        "Payment received: {$receipt_number} - " . formatCurrency($amount) . 
                        " from {$student_fee['first_name']} {$student_fee['last_name']} ({$student_fee['student_number']})");
                    
                    // Send receipt email (optional)
                    $student_email = fetchOne("SELECT u.email FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?", [$student_fee['student_id']])['email'];
                    if ($student_email) {
                        $email_subject = 'Payment Receipt - ' . $receipt_number;
                        $email_message = "
                            <h2>Payment Receipt</h2>
                            <p>Dear {$student_fee['first_name']} {$student_fee['last_name']},</p>
                            <p>We have received your payment of " . formatCurrency($amount) . ".</p>
                            <p><strong>Receipt Number:</strong> {$receipt_number}</p>
                            <p><strong>Payment Method:</strong> " . ucfirst($payment_method) . "</p>
                            <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                            <p>Thank you for your payment.</p>
                        ";
                        sendEmail($student_email, $email_subject, $email_message);
                    }
                    
                    $success_message = "Payment processed successfully! Receipt Number: {$receipt_number}";
                    
                    // Refresh student data
                    if (isset($_GET['search'])) {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?search=" . urlencode($_GET['search']) . "&success=1");
                        exit;
                    }
                    
                } catch (Exception $e) {
                    rollbackTransaction();
                    error_log("Payment processing error: " . $e->getMessage());
                    $error_message = 'Payment processing failed: ' . $e->getMessage();
                }
            }
        }
    }
}

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .search-section {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        border: 1px solid #e3f2fd;
    }
    
    .search-section h4 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    
    .student-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }
    
    .student-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 600;
        margin-right: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        position: relative;
    }
    
    .student-avatar::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border-radius: 50%;
        background: linear-gradient(45deg, #007bff, #28a745, #ffc107, #dc3545);
        z-index: -1;
        animation: rotate 3s linear infinite;
    }
    
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .fee-item {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-left: 4px solid #007bff;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .fee-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #007bff, #28a745);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .fee-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    }
    
    .fee-item:hover::before {
        transform: translateX(0);
    }
    
    .fee-item.overdue {
        border-left-color: #dc3545;
        background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
    }
    
    .fee-item.overdue::before {
        background: linear-gradient(90deg, #dc3545, #fd7e14);
    }
    
    .fee-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .fee-amount {
        font-size: 1.5rem;
        font-weight: 700;
        color: #dc3545;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .payment-form {
        background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: 1px solid #e8f5e8;
        position: sticky;
        top: 20px;
    }
    
    .payment-form h5 {
        color: #2c3e50;
        margin-bottom: 1.5rem;
        font-weight: 600;
    }
    
    .payment-method-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin: 1.5rem 0;
    }
    
    .payment-method {
        position: relative;
    }
    
    .payment-method input[type="radio"] {
        display: none;
    }
    
    .payment-method label {
        display: block;
        padding: 1.2rem 0.8rem;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        background: white;
        position: relative;
        overflow: hidden;
    }
    
    .payment-method label::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s ease;
    }
    
    .payment-method input[type="radio"]:checked + label {
        border-color: #007bff;
        background: linear-gradient(135deg, #e7f3ff 0%, #ffffff 100%);
        color: #007bff;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,123,255,0.2);
    }
    
    .payment-method label:hover::before {
        left: 100%;
    }
    
    .mpesa-fields {
        display: none;
        background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 1rem;
        border: 1px solid #c3e6cb;
        animation: slideDown 0.3s ease;
    }
    
    .mpesa-fields.show {
        display: block;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .amount-input {
        font-size: 1.5rem;
        font-weight: 700;
        text-align: center;
        border: 2px solid #007bff;
        border-radius: 12px;
        padding: 1.2rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        transition: all 0.3s ease;
    }
    
    .amount-input:focus {
        border-color: #0056b3;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        transform: scale(1.02);
    }
    
    .balance-info {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 1px solid #ffeaa7;
        border-radius: 10px;
        padding: 1rem;
        margin: 1rem 0;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s ease;
        padding: 0.75rem 1rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        transform: translateY(-1px);
    }
    
    .btn {
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 1px solid #e9ecef;
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.5rem;
    }
    
    .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        animation: slideIn 0.3s ease;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .badge {
        padding: 0.5em 0.75em;
        border-radius: 8px;
        font-weight: 500;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .student-info {
            text-align: center;
            padding: 1.5rem;
        }
        
        .student-avatar {
            margin: 0 auto 1rem auto;
            width: 70px;
            height: 70px;
            font-size: 2rem;
        }
        
        .payment-method-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
        }
        
        .payment-method label {
            padding: 1rem 0.5rem;
            font-size: 0.9rem;
        }
        
        .payment-form {
            position: static;
            margin-top: 1rem;
        }
        
        .amount-input {
            font-size: 1.25rem;
        }
        
        .fee-amount {
            font-size: 1.25rem;
        }
    }
</style>

<!-- Search Section -->
<div class="search-section">
    <h4>Search Student</h4>
    <form method="GET" class="row">
        <div class="col-md-8">
            <input type="text" class="form-control" name="search" 
                   placeholder="Enter Student ID, Name, ID Number, or Phone Number"
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary btn-block">Search Student</button>
        </div>
    </form>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Payment processed successfully! Receipt has been generated.
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($student_data): ?>
    <!-- Student Information -->
    <div class="student-info">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="student-avatar">
                    <?php echo strtoupper(substr($student_data['first_name'], 0, 1)); ?>
                </div>
            </div>
            <div class="col">
                <h4><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></h4>
                <p class="mb-1">
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($student_data['student_id']); ?> |
                    <strong>Course:</strong> <?php echo htmlspecialchars($student_data['course_name']); ?>
                </p>
                <p class="mb-0">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($student_data['phone']); ?> |
                    <strong>Email:</strong> <?php echo htmlspecialchars($student_data['email']); ?>
                </p>
            </div>
            <div class="col-auto">
                <?php
                $total_balance = array_sum(array_column($pending_fees, 'balance'));
                ?>
                <div class="text-center">
                    <div class="h3 text-danger mb-0"><?php echo formatCurrency($total_balance); ?></div>
                    <small class="text-muted">Total Outstanding</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($pending_fees)): ?>
        <div class="alert alert-success">
            <h5>✅ All Fees Cleared!</h5>
            <p class="mb-0">This student has no outstanding fee balances.</p>
        </div>
    <?php else: ?>
        <!-- Pending Fees -->
        <div class="row">
            <div class="col-md-6">
                <h5>Outstanding Fees</h5>
                <?php foreach ($pending_fees as $fee): ?>
                    <div class="fee-item <?php echo (strtotime($fee['due_date']) < time()) ? 'overdue' : ''; ?>">
                        <div class="fee-header">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($fee['fee_type']); ?></h6>
                                <small class="text-muted">
                                    Due: <?php echo formatDate($fee['due_date']); ?>
                                    <?php if (strtotime($fee['due_date']) < time()): ?>
                                        <span class="badge badge-danger">Overdue</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="fee-amount">
                                <?php echo formatCurrency($fee['balance']); ?>
                            </div>
                        </div>
                        <div class="fee-details">
                            <small class="text-muted">
                                Total: <?php echo formatCurrency($fee['amount_due']); ?> | 
                                Paid: <?php echo formatCurrency($fee['amount_paid']); ?> | 
                                Balance: <?php echo formatCurrency($fee['balance']); ?>
                            </small>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm mt-2" 
                                onclick="selectFee(<?php echo $fee['id']; ?>, '<?php echo htmlspecialchars($fee['fee_type']); ?>', <?php echo $fee['balance']; ?>)">
                            Pay This Fee
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Payment Form -->
            <div class="col-md-6">
                <div class="payment-form">
                    <h5>Process Payment</h5>
                    <form method="POST" id="paymentForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="process_payment" value="1">
                        <input type="hidden" name="student_fee_id" id="selected_fee_id">
                        
                        <div class="form-group">
                            <label class="form-label">Selected Fee</label>
                            <input type="text" class="form-control" id="selected_fee_name" readonly 
                                   placeholder="Click 'Pay This Fee' button above to select">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" class="form-control amount-input" name="amount" 
                                   id="payment_amount" step="0.01" min="0" required>
                            <div class="balance-info" id="balance_info" style="display: none;">
                                <small>Outstanding Balance: <span id="outstanding_balance"></span></small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <div class="payment-method-grid">
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="cash" id="cash">
                                    <label for="cash">💰 Cash</label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="mpesa" id="mpesa">
                                    <label for="mpesa">📱 M-Pesa</label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="bank" id="bank">
                                    <label for="bank">🏦 Bank</label>
                                </div>
                                <div class="payment-method">
                                    <input type="radio" name="payment_method" value="cheque" id="cheque">
                                    <label for="cheque">📄 Cheque</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- M-Pesa specific fields -->
                        <div class="mpesa-fields" id="mpesa_fields">
                            <div class="form-group">
                                <label class="form-label">M-Pesa Phone Number</label>
                                <input type="tel" class="form-control" name="mpesa_phone" 
                                       placeholder="254712345678" pattern="^254[17]\d{8}$">
                                <small class="form-text text-muted">Enter phone number in format: 254712345678</small>
                            </div>
                        <div class="form-group">
                            <label class="form-label">Reference Number (Optional)</label>
                            <input type="text" class="form-control" name="reference_number" 
                                   placeholder="Transaction reference or cheque number">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Additional notes about this payment"></textarea>
                        </div>
                        
                        <!-- Discount and Scholarship Section -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">💰 Discounts & Scholarships</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Discount Code</label>
                                            <input type="text" class="form-control" name="discount_code" 
                                                   placeholder="Enter discount code" id="discount_code">
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-1" 
                                                    onclick="validateDiscountCode()">Validate Code</button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label">Scholarship Amount</label>
                                            <input type="number" class="form-control" name="scholarship_amount" 
                                                   step="0.01" min="0" placeholder="0.00" id="scholarship_amount">
                                            <small class="form-text text-muted">Enter scholarship amount if applicable</small>
                                        </div>
                                    </div>
                                </div>
                                <div id="discount_info" class="alert alert-info" style="display: none;">
                                    <small id="discount_details"></small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg btn-block" id="process_btn" disabled>
                            Process Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php elseif (isset($_GET['search'])): ?>
    <div class="alert alert-warning">
        <h5>Student Not Found</h5>
        <p class="mb-0">No student found matching "<?php echo htmlspecialchars($_GET['search']); ?>". Please check the search term and try again.</p>
    </div>
<?php endif; ?>

<script>
let selectedFeeBalance = 0;

function selectFee(feeId, feeName, balance) {
    document.getElementById('selected_fee_id').value = feeId;
    document.getElementById('selected_fee_name').value = feeName;
    document.getElementById('payment_amount').value = balance.toFixed(2);
    document.getElementById('outstanding_balance').textContent = 'KSh ' + balance.toLocaleString();
    document.getElementById('balance_info').style.display = 'block';
    
    selectedFeeBalance = balance;
    
    // Enable process button
    document.getElementById('process_btn').disabled = false;
    
    // Scroll to payment form
    document.querySelector('.payment-form').scrollIntoView({ behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function() {
    // Payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const mpesaFields = document.getElementById('mpesa_fields');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'mpesa') {
                mpesaFields.classList.add('show');
            } else {
                mpesaFields.classList.remove('show');
            }
        });
    });
    
    // Amount validation
    const amountInput = document.getElementById('payment_amount');
    amountInput.addEventListener('input', function() {
        const amount = parseFloat(this.value);
        if (amount > selectedFeeBalance) {
            this.setCustomValidity('Amount cannot exceed outstanding balance');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Form validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const selectedFeeId = document.getElementById('selected_fee_id').value;
        const amount = parseFloat(document.getElementById('payment_amount').value);
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedFeeId) {
            e.preventDefault();
            alert('Please select a fee to pay');
            return;
        }
        
        if (!amount || amount <= 0) {
            e.preventDefault();
            alert('Please enter a valid payment amount');
            return;
        }
        
        if (amount > selectedFeeBalance) {
            e.preventDefault();
            alert('Payment amount cannot exceed the outstanding balance');
            return;
        }
        
        if (!paymentMethod) {
            e.preventDefault();
            alert('Please select a payment method');
            return;
        }
        
        if (paymentMethod.value === 'mpesa') {
            const mpesaPhone = document.querySelector('input[name="mpesa_phone"]').value;
            if (!mpesaPhone || !mpesaPhone.match(/^254[17]\d{8}$/)) {
                e.preventDefault();
                alert('Please enter a valid M-Pesa phone number (254XXXXXXXXX)');
                return;
            }
        }
        
        // Show loading state
        const submitBtn = document.getElementById('process_btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Processing Payment...';
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

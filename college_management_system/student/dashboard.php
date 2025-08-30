<?php
/**
 * Student Dashboard
 * Shows student information, payment history, and fee balances
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require student role
Authentication::requireRole('student');

// Page configuration
$page_title = 'Student Dashboard';
$show_page_header = true;

// Get current user and student information
$current_user = Authentication::getCurrentUser();
$student_id = $current_user['profile_id'];

// Get student details with course information
$student = fetchOne(
    "SELECT s.*, c.course_name, c.duration_months, c.course_code,
            DATEDIFF(NOW(), s.admission_date) as days_enrolled,
            DATE_ADD(s.admission_date, INTERVAL c.duration_months MONTH) as expected_completion
     FROM students s 
     JOIN courses c ON s.course_id = c.id 
     WHERE s.id = ?",
    [$student_id]
);

if (!$student) {
    die('Student record not found');
}

// Get fee balance information
$fee_balance = getStudentFeeBalance($student_id);

// Get recent payment history (last 10 payments)
$payment_history = getStudentPaymentHistory($student_id, 10);

// Get pending fees
$pending_fees = fetchAll(
    "SELECT sf.*, fs.fee_type, fs.description
     FROM student_fees sf
     JOIN fee_structure fs ON sf.fee_structure_id = fs.id
     WHERE sf.student_id = ? AND sf.balance > 0
     ORDER BY sf.due_date ASC",
    [$student_id]
);

// Get academic progress (if grades exist)
$academic_progress = calculateStudentGPA($student_id);

// Get recent assignments
$recent_assignments = fetchAll(
    "SELECT a.*, s.subject_name, t.first_name as teacher_name
     FROM assignments a
     JOIN subjects s ON a.subject_id = s.id
     JOIN teachers t ON a.teacher_id = t.id
     WHERE s.course_id = ? AND a.status = 'active'
     ORDER BY a.due_date ASC
     LIMIT 5",
    [$student['course_id']]
);

// Calculate course progress percentage
$course_progress = 0;
if ($student['duration_months'] > 0) {
    $months_enrolled = floor($student['days_enrolled'] / 30);
    $course_progress = min(100, ($months_enrolled / $student['duration_months']) * 100);
}

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .dashboard-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
    }
    
    .dashboard-card:hover {
        transform: translateY(-2px);
    }
    
    .card-header {
        display: flex;
        align-items: center;
        justify-content: between;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f8f9fa;
    }
    
    .card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: white;
        margin-right: 1rem;
    }
    
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #007bff;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .fee-status {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        text-align: center;
    }
    
    .fee-status.paid {
        background: #d4edda;
        color: #155724;
    }
    
    .fee-status.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .fee-status.overdue {
        background: #f8d7da;
        color: #721c24;
    }
    
    .progress-bar-container {
        background: #e9ecef;
        border-radius: 10px;
        height: 10px;
        overflow: hidden;
        margin: 1rem 0;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #007bff, #0056b3);
        border-radius: 10px;
        transition: width 0.6s ease;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .action-btn {
        padding: 1rem;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
        border-radius: 10px;
        text-decoration: none;
        color: #495057;
        text-align: center;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .action-btn:hover {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
    }
    
    .payment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .payment-item:last-child {
        border-bottom: none;
    }
    
    .payment-details {
        flex: 1;
    }
    
    .payment-amount {
        font-weight: 600;
        color: #28a745;
    }
    
    .payment-date {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .assignment-item {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        border-left: 4px solid #007bff;
    }
    
    .assignment-title {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.25rem;
    }
    
    .assignment-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .due-soon {
        border-left-color: #ffc107;
    }
    
    .overdue {
        border-left-color: #dc3545;
    }
    
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Welcome back, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>!</h2>
                    <p class="text-muted mb-2">
                        Student ID: <strong><?php echo htmlspecialchars($student['student_id']); ?></strong> | 
                        Course: <strong><?php echo htmlspecialchars($student['course_name']); ?></strong>
                    </p>
                    <p class="text-muted mb-0">
                        Enrolled: <?php echo formatDate($student['admission_date']); ?> | 
                        Expected Completion: <?php echo formatDate($student['expected_completion']); ?>
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="stat-value"><?php echo number_format($course_progress, 1); ?>%</div>
                    <div class="stat-label">Course Progress</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?php echo $course_progress; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Stats -->
<div class="dashboard-grid">
    <!-- Fee Balance Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">₹</div>
            <h5 class="card-title">Fee Balance</h5>
        </div>
        <div class="stat-value text-danger"><?php echo formatCurrency($fee_balance['total_balance']); ?></div>
        <div class="stat-label">Outstanding Amount</div>
        <div class="mt-2">
            <small class="text-muted">
                Total Due: <?php echo formatCurrency($fee_balance['total_due']); ?> | 
                Paid: <?php echo formatCurrency($fee_balance['total_paid']); ?>
            </small>
        </div>
        <div class="fee-status <?php echo $fee_balance['total_balance'] <= 0 ? 'paid' : ($fee_balance['total_balance'] > 0 ? 'pending' : 'overdue'); ?>">
            <?php echo $fee_balance['total_balance'] <= 0 ? 'Fees Cleared' : 'Payment Pending'; ?>
        </div>
    </div>
    
    <!-- Academic Progress Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #28a745, #1e7e34);">📊</div>
            <h5 class="card-title">Academic Progress</h5>
        </div>
        <div class="stat-value text-success"><?php echo $academic_progress['gpa']; ?>%</div>
        <div class="stat-label">Current GPA</div>
        <div class="mt-2">
            <small class="text-muted">
                Subjects Completed: <?php echo $academic_progress['total_subjects']; ?>
            </small>
        </div>
    </div>
    
    <!-- Attendance Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">📅</div>
            <h5 class="card-title">Attendance</h5>
        </div>
        <?php
        // Calculate attendance percentage
        $attendance_data = fetchOne(
            "SELECT 
                COUNT(*) as total_classes,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_classes
             FROM attendance 
             WHERE student_id = ?",
            [$student_id]
        );
        
        $attendance_percentage = 0;
        if ($attendance_data['total_classes'] > 0) {
            $attendance_percentage = ($attendance_data['present_classes'] / $attendance_data['total_classes']) * 100;
        }
        ?>
        <div class="stat-value text-info"><?php echo number_format($attendance_percentage, 1); ?>%</div>
        <div class="stat-label">Attendance Rate</div>
        <div class="mt-2">
            <small class="text-muted">
                Present: <?php echo $attendance_data['present_classes'] ?? 0; ?> / 
                Total: <?php echo $attendance_data['total_classes'] ?? 0; ?>
            </small>
        </div>
    </div>
    
    <!-- Assignments Card -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">📝</div>
            <h5 class="card-title">Assignments</h5>
        </div>
        <div class="stat-value text-warning"><?php echo count($recent_assignments); ?></div>
        <div class="stat-label">Active Assignments</div>
        <div class="mt-2">
            <small class="text-muted">
                <?php
                $due_soon = 0;
                foreach ($recent_assignments as $assignment) {
                    if (strtotime($assignment['due_date']) <= strtotime('+3 days')) {
                        $due_soon++;
                    }
                }
                echo $due_soon > 0 ? "{$due_soon} due soon" : "No urgent deadlines";
                ?>
            </small>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <h5 class="card-title mb-3">Quick Actions</h5>
            <div class="quick-actions">
                <a href="/college_management_system/student/fee_statement.php" class="action-btn">
                    View Fee Statement
                </a>
                <a href="/college_management_system/student/id_download.php" class="action-btn">
                    Download Student ID
                </a>
                <a href="/college_management_system/student/timetable.php" class="action-btn">
                    View Timetable
                </a>
                <a href="/college_management_system/student/assignments.php" class="action-btn">
                    View Assignments
                </a>
                <a href="/college_management_system/student/exam_results.php" class="action-btn">
                    Check Results
                </a>
                <a href="/college_management_system/student/hostel_request.php" class="action-btn">
                    Hostel Request
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Payment History -->
    <div class="col-md-6">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Recent Payments</h5>
                <a href="/college_management_system/student/payment_history.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            
            <?php if (empty($payment_history)): ?>
                <p class="text-muted text-center py-3">No payment history available</p>
            <?php else: ?>
                <?php foreach ($payment_history as $payment): ?>
                    <div class="payment-item">
                        <div class="payment-details">
                            <div class="payment-type"><?php echo htmlspecialchars($payment['fee_type']); ?></div>
                            <div class="payment-date"><?php echo formatDate($payment['payment_date']); ?></div>
                        </div>
                        <div class="payment-amount">
                            <?php echo formatCurrency($payment['amount']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pending Fees -->
    <div class="col-md-6">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Pending Fees</h5>
                <a href="/college_management_system/student/fee_statement.php" class="btn btn-sm btn-outline-danger">Pay Now</a>
            </div>
            
            <?php if (empty($pending_fees)): ?>
                <p class="text-success text-center py-3">✓ All fees cleared!</p>
            <?php else: ?>
                <?php foreach ($pending_fees as $fee): ?>
                    <div class="payment-item">
                        <div class="payment-details">
                            <div class="payment-type"><?php echo htmlspecialchars($fee['fee_type']); ?></div>
                            <div class="payment-date">
                                Due: <?php echo formatDate($fee['due_date']); ?>
                                <?php if (strtotime($fee['due_date']) < time()): ?>
                                    <span class="badge badge-danger">Overdue</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="payment-amount text-danger">
                            <?php echo formatCurrency($fee['balance']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Assignments -->
<?php if (!empty($recent_assignments)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">Recent Assignments</h5>
                <a href="/college_management_system/student/assignments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            
            <?php foreach ($recent_assignments as $assignment): ?>
                <?php
                $due_date = strtotime($assignment['due_date']);
                $now = time();
                $days_until_due = ceil(($due_date - $now) / (24 * 60 * 60));
                
                $class = '';
                if ($days_until_due < 0) {
                    $class = 'overdue';
                } elseif ($days_until_due <= 3) {
                    $class = 'due-soon';
                }
                ?>
                <div class="assignment-item <?php echo $class; ?>">
                    <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                    <div class="assignment-meta">
                        <?php echo htmlspecialchars($assignment['subject_name']); ?> | 
                        Teacher: <?php echo htmlspecialchars($assignment['teacher_name']); ?> | 
                        Due: <?php echo formatDate($assignment['due_date']); ?>
                        <?php if ($days_until_due < 0): ?>
                            <span class="text-danger">(<?php echo abs($days_until_due); ?> days overdue)</span>
                        <?php elseif ($days_until_due <= 3): ?>
                            <span class="text-warning">(Due in <?php echo $days_until_due; ?> days)</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        // Check for new notifications or updates
        fetch('/college_management_system/api/dashboard_updates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.updates) {
                    // Show notification for new updates
                    showNotification('New updates available. Refresh page to see changes.', 'info');
                }
            })
            .catch(error => {
                console.error('Error checking for updates:', error);
            });
    }, 300000); // 5 minutes
    
    // Add click tracking for quick actions
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.textContent.trim();
            // Track user interaction (could be sent to analytics)
            console.log('Quick action clicked:', action);
        });
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

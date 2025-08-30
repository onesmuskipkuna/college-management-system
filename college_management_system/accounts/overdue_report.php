<?php
/**
 * Overdue Report
 * Generate reports for overdue fee payments
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Get filter parameters
$course_filter = $_GET['course'] ?? '';
$overdue_days = $_GET['overdue_days'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'days_overdue';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build query conditions
$conditions = ["sf.balance > 0", "sf.due_date < CURDATE()"];
$params = [];

if ($course_filter) {
    $conditions[] = "c.id = ?";
    $params[] = $course_filter;
}

if ($overdue_days) {
    $conditions[] = "DATEDIFF(CURDATE(), sf.due_date) >= ?";
    $params[] = $overdue_days;
}

if ($amount_min) {
    $conditions[] = "sf.balance >= ?";
    $params[] = $amount_min;
}

$where_clause = implode(' AND ', $conditions);

// Validate sort parameters
$valid_sort_columns = ['days_overdue', 'balance', 'due_date', 'student_name', 'course_name'];
$valid_sort_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $valid_sort_columns)) $sort_by = 'days_overdue';
if (!in_array($sort_order, $valid_sort_orders)) $sort_order = 'DESC';

$order_clause = match($sort_by) {
    'student_name' => "s.last_name $sort_order, s.first_name $sort_order",
    'course_name' => "c.course_name $sort_order",
    'days_overdue' => "DATEDIFF(CURDATE(), sf.due_date) $sort_order",
    default => "sf.$sort_by $sort_order"
};

// Get overdue fees data
$overdue_fees = fetchAll("
    SELECT 
        s.id as student_id,
        s.student_id as student_number,
        s.first_name,
        s.last_name,
        s.phone,
        s.email,
        c.course_name,
        c.course_code,
        fs.fee_type,
        sf.amount_due,
        sf.amount_paid,
        sf.balance,
        sf.due_date,
        DATEDIFF(CURDATE(), sf.due_date) as days_overdue,
        CASE 
            WHEN DATEDIFF(CURDATE(), sf.due_date) <= 30 THEN 'Recent'
            WHEN DATEDIFF(CURDATE(), sf.due_date) <= 90 THEN 'Moderate'
            WHEN DATEDIFF(CURDATE(), sf.due_date) <= 180 THEN 'Serious'
            ELSE 'Critical'
        END as overdue_category,
        (SELECT COUNT(*) FROM payments p WHERE p.student_id = s.id AND p.status = 'approved') as payment_history_count,
        (SELECT MAX(payment_date) FROM payments p WHERE p.student_id = s.id AND p.status = 'approved') as last_payment_date
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN fee_structure fs ON sf.fee_structure_id = fs.id
    WHERE $where_clause
    ORDER BY $order_clause
", $params);

// Get courses for filter
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Calculate summary statistics
$total_overdue_amount = array_sum(array_column($overdue_fees, 'balance'));
$total_overdue_students = count(array_unique(array_column($overdue_fees, 'student_id')));

// Group by overdue categories
$overdue_categories = [
    'Recent' => ['count' => 0, 'amount' => 0, 'color' => 'warning'],
    'Moderate' => ['count' => 0, 'amount' => 0, 'color' => 'info'],
    'Serious' => ['count' => 0, 'amount' => 0, 'color' => 'danger'],
    'Critical' => ['count' => 0, 'amount' => 0, 'color' => 'dark']
];

foreach ($overdue_fees as $fee) {
    $category = $fee['overdue_category'];
    $overdue_categories[$category]['count']++;
    $overdue_categories[$category]['amount'] += $fee['balance'];
}

// Get top defaulters (students with highest overdue amounts)
$top_defaulters = fetchAll("
    SELECT 
        s.student_id,
        s.first_name,
        s.last_name,
        s.phone,
        c.course_name,
        SUM(sf.balance) as total_overdue,
        COUNT(sf.id) as overdue_fees_count,
        MIN(sf.due_date) as oldest_due_date,
        MAX(DATEDIFF(CURDATE(), sf.due_date)) as max_days_overdue
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE sf.balance > 0 AND sf.due_date < CURDATE()
    GROUP BY s.id
    ORDER BY total_overdue DESC
    LIMIT 10
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Overdue Fees Report</h1>
                    <p class="text-muted">Track and manage overdue fee payments</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" onclick="exportReport()">Export Report</button>
                    <button class="btn btn-outline-success" onclick="sendBulkReminders()">Send Reminders</button>
                    <button class="btn btn-outline-info" onclick="printReport()">Print Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">⚠️</div>
                <div class="stat-content">
                    <h3><?= $total_overdue_students ?></h3>
                    <p>Students with Overdue Fees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($total_overdue_amount) ?></h3>
                    <p>Total Overdue Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">📊</div>
                <div class="stat-content">
                    <h3><?= count($overdue_fees) ?></h3>
                    <p>Overdue Fee Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-secondary">📅</div>
                <div class="stat-content">
                    <h3><?= !empty($overdue_fees) ? max(array_column($overdue_fees, 'days_overdue')) : 0 ?></h3>
                    <p>Max Days Overdue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Categories -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Overdue Categories</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($overdue_categories as $category => $data): ?>
                            <div class="col-md-3">
                                <div class="category-card border-<?= $data['color'] ?>">
                                    <div class="category-header bg-<?= $data['color'] ?> text-white">
                                        <h6><?= $category ?></h6>
                                        <small>
                                            <?php
                                            switch($category) {
                                                case 'Recent': echo '1-30 days'; break;
                                                case 'Moderate': echo '31-90 days'; break;
                                                case 'Serious': echo '91-180 days'; break;
                                                case 'Critical': echo '180+ days'; break;
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div class="category-body">
                                        <div class="category-stat">
                                            <strong><?= $data['count'] ?></strong>
                                            <span>Students</span>
                                        </div>
                                        <div class="category-stat">
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

    <div class="row">
        <!-- Overdue Fees List -->
        <div class="col-md-8">
            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="course" class="form-label">Course</label>
                            <select class="form-select" id="course" name="course">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="overdue_days" class="form-label">Min Days Overdue</label>
                            <input type="number" class="form-control" id="overdue_days" name="overdue_days" 
                                   value="<?= $overdue_days ?>" min="1">
                        </div>
                        <div class="col-md-2">
                            <label for="amount_min" class="form-label">Min Amount</label>
                            <input type="number" class="form-control" id="amount_min" name="amount_min" 
                                   value="<?= $amount_min ?>" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_by" name="sort_by">
                                <option value="days_overdue" <?= $sort_by === 'days_overdue' ? 'selected' : '' ?>>Days Overdue</option>
                                <option value="balance" <?= $sort_by === 'balance' ? 'selected' : '' ?>>Amount</option>
                                <option value="due_date" <?= $sort_by === 'due_date' ? 'selected' : '' ?>>Due Date</option>
                                <option value="student_name" <?= $sort_by === 'student_name' ? 'selected' : '' ?>>Student Name</option>
                                <option value="course_name" <?= $sort_by === 'course_name' ? 'selected' : '' ?>>Course</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Overdue Fees Table -->
            <div class="card">
                <div class="card-header">
                    <h5>Overdue Fee Details (<?= count($overdue_fees) ?> items)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($overdue_fees)): ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-check-circle fa-3x text-success"></i>
                            </div>
                            <h5 class="text-success">No Overdue Fees Found!</h5>
                            <p class="text-muted">All fees are up to date or no fees match your criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Fee Type</th>
                                        <th>Amount Due</th>
                                        <th>Balance</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Category</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdue_fees as $fee): ?>
                                    <tr class="overdue-row" data-student-id="<?= $fee['student_id'] ?>">
                                        <td>
                                            <input type="checkbox" class="student-checkbox" value="<?= $fee['student_id'] ?>">
                                        </td>
                                        <td>
                                            <div class="student-info">
                                                <strong><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($fee['student_number']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($fee['phone']) ?></small>
                                                <?php if ($fee['last_payment_date']): ?>
                                                    <br><small class="text-info">Last payment: <?= formatDate($fee['last_payment_date']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($fee['course_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($fee['course_code']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($fee['fee_type']) ?></td>
                                        <td>KSh <?= number_format($fee['amount_due']) ?></td>
                                        <td><strong class="text-danger">KSh <?= number_format($fee['balance']) ?></strong></td>
                                        <td><?= formatDate($fee['due_date']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $overdue_categories[$fee['overdue_category']]['color'] ?>">
                                                <?= $fee['days_overdue'] ?> days
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $overdue_categories[$fee['overdue_category']]['color'] ?>">
                                                <?= $fee['overdue_category'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="collectPayment('<?= $fee['student_number'] ?>')" 
                                                        title="Collect Payment">💰</button>
                                                <button class="btn btn-outline-info" 
                                                        onclick="sendReminder(<?= $fee['student_id'] ?>)" 
                                                        title="Send Reminder">📧</button>
                                                <button class="btn btn-outline-warning" 
                                                        onclick="callStudent('<?= $fee['phone'] ?>')" 
                                                        title="Call Student">📞</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Defaulters -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6>Top Defaulters</h6>
                    <small class="text-muted">Students with highest overdue amounts</small>
                </div>
                <div class="card-body">
                    <?php if (empty($top_defaulters)): ?>
                        <p class="text-muted text-center">No defaulters found</p>
                    <?php else: ?>
                        <?php foreach ($top_defaulters as $index => $defaulter): ?>
                            <div class="defaulter-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="defaulter-info">
                                        <div class="rank-badge">#<?= $index + 1 ?></div>
                                        <div>
                                            <strong><?= htmlspecialchars($defaulter['first_name'] . ' ' . $defaulter['last_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($defaulter['student_id']) ?></small><br>
                                            <small class="text-muted"><?= htmlspecialchars($defaulter['course_name']) ?></small>
                                        </div>
                                    </div>
                                    <div class="defaulter-stats text-end">
                                        <div class="overdue-amount">KSh <?= number_format($defaulter['total_overdue']) ?></div>
                                        <small class="text-muted"><?= $defaulter['overdue_fees_count'] ?> overdue fees</small><br>
                                        <small class="text-danger"><?= $defaulter['max_days_overdue'] ?> days max</small>
                                    </div>
                                </div>
                                <div class="defaulter-actions mt-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="collectPayment('<?= $defaulter['student_id'] ?>')">Collect</button>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="sendReminder(<?= $defaulter['student_id'] ?>)">Remind</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="generateFollowUpList()">
                            📋 Generate Follow-up List
                        </button>
                        <button class="btn btn-outline-success" onclick="sendBulkReminders()">
                            📧 Send Bulk Reminders
                        </button>
                        <button class="btn btn-outline-warning" onclick="escalateToManagement()">
                            ⚠️ Escalate to Management
                        </button>
                        <button class="btn btn-outline-info" onclick="exportDefaultersList()">
                            📊 Export Defaulters List
                        </button>
                    </div>
                </div>
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

.category-card {
    border: 2px solid;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
}

.category-header {
    padding: 10px 15px;
    text-align: center;
}

.category-header h6 {
    margin: 0;
    font-weight: 600;
}

.category-body {
    padding: 15px;
    background: white;
}

.category-stat {
    text-align: center;
    margin-bottom: 10px;
}

.category-stat strong {
    display: block;
    font-size: 1.2rem;
    color: #2c3e50;
}

.category-stat span {
    font-size: 0.85rem;
    color: #6c757d;
}

.defaulter-item {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 15px;
    background: #f8f9fa;
}

.defaulter-item:last-child {
    margin-bottom: 0;
}

.rank-badge {
    display: inline-block;
    width: 25px;
    height: 25px;
    background: #007bff;
    color: white;
    border-radius: 50%;
    text-align: center;
    line-height: 25px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-right: 10px;
    float: left;
}

.overdue-amount {
    font-size: 1.1rem;
    font-weight: 600;
    color: #dc3545;
}

.defaulter-actions {
    border-top: 1px solid #dee2e6;
    padding-top: 10px;
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

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .category-card {
        margin-bottom: 10px;
    }
    
    .defaulter-item {
        padding: 10px;
    }
}
</style>

<script>
let selectedStudents = [];

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedStudents();
}

function updateSelectedStudents() {
    selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked'))
        .map(checkbox => checkbox.value);
}

function collectPayment(studentId) {
    window.location.href = `/college_management_system/accounts/fee_receive.php?search=${encodeURIComponent(studentId)}`;
}

function sendReminder(studentId) {
    if (confirm('Send payment reminder to this student?')) {
        fetch('/college_management_system/api/send_reminder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_id: studentId,
                type: 'overdue_reminder'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Reminder sent successfully!');
            } else {
                alert('Error sending reminder: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending reminder');
        });
    }
}

function callStudent(phone) {
    if (phone) {
        window.open(`tel:${phone}`, '_self');
    } else {
        alert('No phone number available for this student');
    }
}

function sendBulkReminders() {
    updateSelectedStudents();
    if (selectedStudents.length === 0) {
        alert('Please select students to send reminders to');
        return;
    }
    
    if (confirm(`Send overdue payment reminders to ${selectedStudents.length} selected students?`)) {
        fetch('/college_management_system/api/send_bulk_reminders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_ids: selectedStudents,
                type: 'overdue_reminder'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Reminders sent to ${data.sent_count} students successfully!`);
            } else {
                alert('Error sending reminders: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending bulk reminders');
        });
    }
}

function generateFollowUpList() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'followup');
    window.open('/college_management_system/accounts/export_overdue_report.php?' + params.toString(), '_blank');
}

function escalateToManagement() {
    updateSelectedStudents();
    if (selectedStudents.length === 0) {
        alert('Please select students to escalate');
        return;
    }
    
    const reason = prompt('Please provide escalation reason:');
    if (reason) {
        fetch('/college_management_system/api/escalate_overdue.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                student_ids: selectedStudents,
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cases escalated to management successfully!');
            } else {
                alert('Error escalating cases: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error escalating cases');
        });
    }
}

function exportDefaultersList() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'defaulters');
    window.open('/college_management_system/accounts/export_overdue_report.php?' + params.toString(), '_blank');
}

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/accounts/export_overdue_report.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}

// Add event listeners for checkboxes
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedStudents);
    });
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

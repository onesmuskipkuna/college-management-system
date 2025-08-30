<?php
/**
 * Fee Balance Report
 * Generate detailed fee balance reports for students
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Get filter parameters
$course_filter = $_GET['course'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query conditions
$conditions = ["sf.balance > 0"];
$params = [];

if ($course_filter) {
    $conditions[] = "c.id = ?";
    $params[] = $course_filter;
}

if ($status_filter) {
    $conditions[] = "sf.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $conditions[] = "sf.due_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $conditions[] = "sf.due_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $conditions);

// Get fee balance data
$fee_balances = []; // Initialize fee balances array
// Fetch fee balance data from the database
try {
    $sql = "
        SELECT 
            s.student_id,
            s.first_name,
            s.last_name,
            s.phone,
            c.course_name,
            c.course_code,
            fs.fee_type,
            sf.amount_due,
            sf.amount_paid,
            sf.balance,
            sf.due_date,
            sf.status,
            DATEDIFF(CURDATE(), sf.due_date) as days_overdue
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN fee_structure fs ON sf.fee_structure_id = fs.id
        WHERE $where_clause
        ORDER BY sf.due_date ASC, s.last_name ASC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $fee_balances = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $message = 'Error fetching fee balance data: ' . $e->getMessage();
}

// Get courses for filter
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Calculate summary statistics
$total_outstanding = array_sum(array_column($fee_balances, 'balance'));
$total_students = count(array_unique(array_column($fee_balances, 'student_id')));
$overdue_count = count(array_filter($fee_balances, function($fee) {
    return $fee['days_overdue'] > 0;
}));
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Fee Balance Report</h1>
                <p class="text-muted">Detailed fee balance analysis and outstanding payments</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Report Filters</h5>
                </div>
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
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="partial" <?= $status_filter === 'partial' ? 'selected' : '' ?>>Partial</option>
                                <option value="overdue" <?= $status_filter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">Due From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">Due To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                                <button type="button" class="btn btn-success" onclick="exportReport()">Export</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($total_outstanding) ?></h3>
                    <p>Total Outstanding</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">👥</div>
                <div class="stat-content">
                    <h3><?= $total_students ?></h3>
                    <p>Students with Balance</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">⚠️</div>
                <div class="stat-content">
                    <h3><?= $overdue_count ?></h3>
                    <p>Overdue Accounts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">📊</div>
                <div class="stat-content">
                    <h3><?= count($fee_balances) ?></h3>
                    <p>Total Records</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Balance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Fee Balance Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="feeBalanceTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Fee Type</th>
                                    <th>Amount Due</th>
                                    <th>Amount Paid</th>
                                    <th>Balance</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fee_balances as $fee): ?>
                                <tr class="<?= $fee['days_overdue'] > 0 ? 'table-warning' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($fee['student_id']) ?></small><br>
                                        <small class="text-muted"><?= htmlspecialchars($fee['phone']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($fee['course_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($fee['course_code']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($fee['fee_type']) ?></td>
                                    <td>KSh <?= number_format($fee['amount_due']) ?></td>
                                    <td>KSh <?= number_format($fee['amount_paid']) ?></td>
                                    <td><strong class="text-danger">KSh <?= number_format($fee['balance']) ?></strong></td>
                                    <td>
                                        <?= formatDate($fee['due_date']) ?>
                                        <?php if ($fee['days_overdue'] > 0): ?>
                                            <br><small class="text-danger"><?= $fee['days_overdue'] ?> days overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = 'secondary';
                                        if ($fee['status'] === 'partial') $status_class = 'warning';
                                        if ($fee['days_overdue'] > 0) $status_class = 'danger';
                                        ?>
                                        <span class="badge bg-<?= $status_class ?>">
                                            <?= $fee['days_overdue'] > 0 ? 'Overdue' : ucfirst($fee['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="fee_receive.php?search=<?= urlencode($fee['student_id']) ?>" 
                                               class="btn btn-outline-primary" title="Receive Payment">💰</a>
                                            <button class="btn btn-outline-info" onclick="sendReminder('<?= $fee['student_id'] ?>')" title="Send Reminder">📧</button>
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

.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}
</style>

<script>
function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/accounts/export_fee_balance.php?' + params.toString(), '_blank');
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
                type: 'fee_reminder'
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

// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTable !== 'undefined') {
        new DataTable('#feeBalanceTable', {
            pageLength: 25,
            order: [[6, 'asc']],
            responsive: true
        });
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Student Finance
 * View student financial information and fee status
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();

// Get filter parameters
$student_search = $_GET['student'] ?? '';
$course_filter = $_GET['course'] ?? '';
$fee_status = $_GET['fee_status'] ?? '';

// Get courses for filter
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Build query conditions
$conditions = ["1=1"];
$params = [];

if ($student_search) {
    $conditions[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $search_term = "%$student_search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

if ($course_filter) {
    $conditions[] = "c.id = ?";
    $params[] = $course_filter;
}

$where_clause = implode(' AND ', $conditions);

// Get student financial data
$student_finances = fetchAll("
    SELECT 
        s.id as student_id,
        s.student_id as student_number,
        s.first_name,
        s.last_name,
        s.phone,
        s.email,
        c.course_name,
        c.course_code,
        COALESCE(SUM(sf.amount_due), 0) as total_fees,
        COALESCE(SUM(sf.amount_paid), 0) as total_paid,
        COALESCE(SUM(sf.balance), 0) as total_balance,
        COUNT(sf.id) as fee_items_count
    FROM students s
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN student_fees sf ON s.id = sf.student_id
    WHERE $where_clause
    GROUP BY s.id
    ORDER BY s.last_name, s.first_name
", $params);

// Calculate summary statistics
$summary_stats = [
    'total_students' => count($student_finances),
    'students_with_balance' => count(array_filter($student_finances, function($sf) { return $sf['total_balance'] > 0; })),
    'total_outstanding' => array_sum(array_column($student_finances, 'total_balance')),
    'total_collected' => array_sum(array_column($student_finances, 'total_paid'))
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Student Finance Overview</h1>
                <p class="text-muted">Monitor student financial status and fee collections</p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">👥</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['total_students'] ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">⚠️</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['students_with_balance'] ?></h3>
                    <p>With Balance</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['total_outstanding']) ?></h3>
                    <p>Outstanding</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">✅</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($summary_stats['total_collected']) ?></h3>
                    <p>Collected</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Filter Students</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="student" class="form-label">Student Search</label>
                            <input type="text" class="form-control" id="student" name="student" 
                                   placeholder="Name or Student ID" value="<?= htmlspecialchars($student_search) ?>">
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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

    <!-- Student Finance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Student Financial Status (<?= count($student_finances) ?> students)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($student_finances)): ?>
                        <div class="text-center py-4">
                            <h5 class="text-muted">No Students Found</h5>
                            <p class="text-muted">No students match your search criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Total Fees</th>
                                        <th>Total Paid</th>
                                        <th>Balance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($student_finances as $finance): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <strong><?= htmlspecialchars($finance['first_name'] . ' ' . $finance['last_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($finance['student_number']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($finance['phone']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($finance['course_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($finance['course_code']) ?></small>
                                        </td>
                                        <td>KSh <?= number_format($finance['total_fees']) ?></td>
                                        <td>KSh <?= number_format($finance['total_paid']) ?></td>
                                        <td>
                                            <strong class="<?= $finance['total_balance'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                KSh <?= number_format($finance['total_balance']) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="viewDetails('<?= $finance['student_id'] ?>')" 
                                                        title="View Details">View</button>
                                                <button class="btn btn-outline-success" 
                                                        onclick="collectPayment('<?= $finance['student_number'] ?>')" 
                                                        title="Collect Payment">Pay</button>
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
</style>

<script>
function viewDetails(studentId) {
    // Redirect to student details page
    window.location.href = `/college_management_system/student/dashboard.php?student_id=${studentId}`;
}

function collectPayment(studentNumber) {
    // Redirect to fee collection page
    window.location.href = `/college_management_system/accounts/fee_receive.php?search=${encodeURIComponent(studentNumber)}`;
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

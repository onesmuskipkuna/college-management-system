<?php
/**
 * Academic Reports
 * Generate comprehensive academic reports
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();

// Get report parameters
$report_type = $_GET['type'] ?? 'enrollment';
$course_filter = $_GET['course'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');

// Get courses for filter
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Generate report data based on type
$report_data = [];
$report_title = '';

switch ($report_type) {
    case 'enrollment':
        $report_title = 'Student Enrollment Report';
        $report_data = fetchAll("
            SELECT 
                c.course_name,
                c.course_code,
                COUNT(s.id) as total_students,
                COUNT(CASE WHEN s.status = 'active' THEN 1 END) as active_students,
                COUNT(CASE WHEN s.status = 'graduated' THEN 1 END) as graduated_students,
                COUNT(CASE WHEN s.status = 'suspended' THEN 1 END) as suspended_students
            FROM courses c
            LEFT JOIN students s ON c.id = s.course_id
            " . ($course_filter ? "WHERE c.id = ?" : "") . "
            GROUP BY c.id
            ORDER BY c.course_name
        ", $course_filter ? [$course_filter] : []);
        break;
        
    case 'admissions':
        $report_title = 'Admissions Report';
        $report_data = fetchAll("
            SELECT 
                s.student_id,
                s.first_name,
                s.last_name,
                s.phone,
                s.email,
                c.course_name,
                s.admission_date,
                s.status
            FROM students s
            JOIN courses c ON s.course_id = c.id
            WHERE s.admission_date BETWEEN ? AND ?
            " . ($course_filter ? "AND c.id = ?" : "") . "
            ORDER BY s.admission_date DESC
        ", $course_filter ? [$date_from, $date_to, $course_filter] : [$date_from, $date_to]);
        break;
        
    case 'graduation':
        $report_title = 'Graduation Report';
        $report_data = fetchAll("
            SELECT 
                s.student_id,
                s.first_name,
                s.last_name,
                c.course_name,
                s.graduation_date,
                CASE WHEN sf.balance <= 0 THEN 'Cleared' ELSE 'Pending' END as fee_status
            FROM students s
            JOIN courses c ON s.course_id = c.id
            LEFT JOIN (
                SELECT student_id, SUM(balance) as balance
                FROM student_fees
                GROUP BY student_id
            ) sf ON s.id = sf.student_id
            WHERE s.status = 'graduated'
            AND s.graduation_date BETWEEN ? AND ?
            " . ($course_filter ? "AND c.id = ?" : "") . "
            ORDER BY s.graduation_date DESC
        ", $course_filter ? [$date_from, $date_to, $course_filter] : [$date_from, $date_to]);
        break;
        
    case 'performance':
        $report_title = 'Academic Performance Report';
        $report_data = fetchAll("
            SELECT 
                c.course_name,
                COUNT(s.id) as total_students,
                AVG(CASE WHEN g.grade_points IS NOT NULL THEN g.grade_points END) as avg_grade,
                COUNT(CASE WHEN g.grade_points >= 3.5 THEN 1 END) as excellent_students,
                COUNT(CASE WHEN g.grade_points >= 2.5 AND g.grade_points < 3.5 THEN 1 END) as good_students,
                COUNT(CASE WHEN g.grade_points < 2.5 THEN 1 END) as poor_students
            FROM courses c
            LEFT JOIN students s ON c.id = s.course_id AND s.status = 'active'
            LEFT JOIN (
                SELECT student_id, AVG(grade_points) as grade_points
                FROM grades
                GROUP BY student_id
            ) g ON s.id = g.student_id
            " . ($course_filter ? "WHERE c.id = ?" : "") . "
            GROUP BY c.id
            ORDER BY c.course_name
        ", $course_filter ? [$course_filter] : []);
        break;
}

// Calculate summary statistics
$summary_stats = [];
if ($report_type === 'enrollment') {
    $summary_stats = [
        'total_courses' => count($report_data),
        'total_students' => array_sum(array_column($report_data, 'total_students')),
        'active_students' => array_sum(array_column($report_data, 'active_students')),
        'graduated_students' => array_sum(array_column($report_data, 'graduated_students'))
    ];
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Academic Reports</h1>
                    <p class="text-muted">Generate comprehensive academic reports and analytics</p>
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
                <div class="card-header">
                    <h5>Report Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Report Type</label>
                            <select class="form-select" id="type" name="type" onchange="toggleDateFields()">
                                <option value="enrollment" <?= $report_type === 'enrollment' ? 'selected' : '' ?>>Enrollment Report</option>
                                <option value="admissions" <?= $report_type === 'admissions' ? 'selected' : '' ?>>Admissions Report</option>
                                <option value="graduation" <?= $report_type === 'graduation' ? 'selected' : '' ?>>Graduation Report</option>
                                <option value="performance" <?= $report_type === 'performance' ? 'selected' : '' ?>>Performance Report</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="course" class="form-label">Course Filter</label>
                            <select class="form-select" id="course" name="course">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 date-fields">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= $date_from ?>">
                        </div>
                        <div class="col-md-2 date-fields">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= $date_to ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <?php if (!empty($summary_stats)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">📚</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['total_courses'] ?></h3>
                    <p>Total Courses</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">👥</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['total_students'] ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">✅</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['active_students'] ?></h3>
                    <p>Active Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">🎓</div>
                <div class="stat-content">
                    <h3><?= $summary_stats['graduated_students'] ?></h3>
                    <p>Graduated Students</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Report Data -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><?= $report_title ?></h5>
                    <small class="text-muted">Generated on <?= date('F j, Y \a\t g:i A') ?></small>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-chart-bar fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No Data Available</h5>
                            <p class="text-muted">No data found for the selected criteria. Try adjusting your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="reportTable">
                                <thead>
                                    <tr>
                                        <?php if ($report_type === 'enrollment'): ?>
                                            <th>Course</th>
                                            <th>Course Code</th>
                                            <th>Total Students</th>
                                            <th>Active</th>
                                            <th>Graduated</th>
                                            <th>Suspended</th>
                                            <th>Completion Rate</th>
                                        <?php elseif ($report_type === 'admissions'): ?>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Course</th>
                                            <th>Admission Date</th>
                                            <th>Status</th>
                                        <?php elseif ($report_type === 'graduation'): ?>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Course</th>
                                            <th>Graduation Date</th>
                                            <th>Fee Status</th>
                                        <?php elseif ($report_type === 'performance'): ?>
                                            <th>Course</th>
                                            <th>Total Students</th>
                                            <th>Average Grade</th>
                                            <th>Excellent (3.5+)</th>
                                            <th>Good (2.5-3.4)</th>
                                            <th>Poor (<2.5)</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <?php if ($report_type === 'enrollment'): ?>
                                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                                            <td><?= htmlspecialchars($row['course_code']) ?></td>
                                            <td><?= $row['total_students'] ?></td>
                                            <td><?= $row['active_students'] ?></td>
                                            <td><?= $row['graduated_students'] ?></td>
                                            <td><?= $row['suspended_students'] ?></td>
                                            <td>
                                                <?php 
                                                $completion_rate = $row['total_students'] > 0 ? 
                                                    ($row['graduated_students'] / $row['total_students']) * 100 : 0;
                                                ?>
                                                <span class="badge bg-<?= $completion_rate >= 70 ? 'success' : ($completion_rate >= 50 ? 'warning' : 'danger') ?>">
                                                    <?= number_format($completion_rate, 1) ?>%
                                                </span>
                                            </td>
                                        <?php elseif ($report_type === 'admissions'): ?>
                                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td><?= htmlspecialchars($row['phone']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                                            <td><?= formatDate($row['admission_date']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                        <?php elseif ($report_type === 'graduation'): ?>
                                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                                            <td><?= formatDate($row['graduation_date']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $row['fee_status'] === 'Cleared' ? 'success' : 'warning' ?>">
                                                    <?= $row['fee_status'] ?>
                                                </span>
                                            </td>
                                        <?php elseif ($report_type === 'performance'): ?>
                                            <td><?= htmlspecialchars($row['course_name']) ?></td>
                                            <td><?= $row['total_students'] ?></td>
                                            <td><?= $row['avg_grade'] ? number_format($row['avg_grade'], 2) : 'N/A' ?></td>
                                            <td><?= $row['excellent_students'] ?></td>
                                            <td><?= $row['good_students'] ?></td>
                                            <td><?= $row['poor_students'] ?></td>
                                        <?php endif; ?>
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
function toggleDateFields() {
    const reportType = document.getElementById('type').value;
    const dateFields = document.querySelectorAll('.date-fields');
    
    if (reportType === 'enrollment' || reportType === 'performance') {
        dateFields.forEach(field => field.style.display = 'none');
    } else {
        dateFields.forEach(field => field.style.display = 'block');
    }
}

function exportReport() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('/college_management_system/registrar/export_report.php?' + params.toString(), '_blank');
}

function exportExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/registrar/export_report.php?' + params.toString(), '_blank');
}

function printReport() {
    window.print();
}

// Initialize date field visibility
document.addEventListener('DOMContentLoaded', function() {
    toggleDateFields();
    
    // Initialize DataTable if available
    if (typeof DataTable !== 'undefined') {
        new DataTable('#reportTable', {
            pageLength: 25,
            responsive: true,
            order: [[0, 'asc']]
        });
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

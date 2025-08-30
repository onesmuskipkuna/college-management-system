<?php
/**
 * Teacher Dashboard
 * Overview of classes, assignments, and student progress
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require teacher role
Authentication::requireRole('teacher');

// Page configuration
$page_title = 'Teacher Dashboard';
$show_page_header = true;
$page_subtitle = 'Manage your classes, assignments, and student progress';

// Get current user and teacher info
$current_user = Authentication::getCurrentUser();
$teacher_info = fetchOne(
    "SELECT t.*, u.email FROM teachers t 
     JOIN users u ON t.user_id = u.id 
     WHERE t.user_id = ?",
    [$current_user['id']]
);

if (!$teacher_info) {
    header('Location: /college_management_system/login.php?error=teacher_profile_not_found');
    exit;
}

// Get teacher's subjects and classes
$teacher_subjects = fetchAll(
    "SELECT s.*, c.course_name, 
            COUNT(DISTINCT st.id) as student_count
     FROM subjects s
     JOIN courses c ON s.course_id = c.id
     LEFT JOIN students st ON st.course_id = c.id AND st.status = 'active'
     WHERE s.status = 'active'
     GROUP BY s.id
     ORDER BY c.course_name, s.semester, s.subject_name"
);

// Get recent assignments
$recent_assignments = fetchAll(
    "SELECT a.*, s.subject_name, c.course_name,
            COUNT(DISTINCT st.id) as total_students,
            COUNT(DISTINCT g.id) as submitted_count
     FROM assignments a
     JOIN subjects s ON a.subject_id = s.id
     JOIN courses c ON s.course_id = c.id
     LEFT JOIN students st ON st.course_id = c.id AND st.status = 'active'
     LEFT JOIN grades g ON g.subject_id = s.id AND g.exam_type = 'assignment'
     WHERE a.teacher_id = ? AND a.status = 'active'
     GROUP BY a.id
     ORDER BY a.due_date DESC
     LIMIT 5",
    [$teacher_info['id']]
);

// Get pending grade approvals
$pending_grades = fetchAll(
    "SELECT g.*, s.subject_name, st.first_name, st.last_name, st.student_id
     FROM grades g
     JOIN subjects s ON g.subject_id = s.id
     JOIN students st ON g.student_id = st.id
     WHERE g.teacher_id = ? AND g.status = 'pending'
     ORDER BY g.created_at DESC
     LIMIT 10",
    [$teacher_info['id']]
);

// Get attendance statistics for today
$today_attendance = fetchAll(
    "SELECT s.subject_name, c.course_name,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
            COUNT(a.id) as total_marked
     FROM attendance a
     JOIN subjects s ON a.subject_id = s.id
     JOIN courses c ON s.course_id = c.id
     WHERE a.teacher_id = ? AND a.attendance_date = CURDATE()
     GROUP BY s.id",
    [$teacher_info['id']]
);

// Calculate statistics
$total_students = array_sum(array_column($teacher_subjects, 'student_count'));
$total_assignments = count($recent_assignments);
$pending_grade_count = count($pending_grades);
$classes_today = count($today_attendance);

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .teacher-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .teacher-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
        margin-right: 1.5rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
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
    
    .subject-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        border-left: 4px solid #007bff;
    }
    
    .subject-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .assignment-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.3s ease;
    }
    
    .assignment-item:hover {
        background-color: #f8f9fa;
    }
    
    .assignment-item:last-child {
        border-bottom: none;
    }
    
    .assignment-details h6 {
        margin: 0 0 0.25rem 0;
        color: #2c3e50;
    }
    
    .assignment-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .due-date {
        font-weight: 600;
    }
    
    .due-date.overdue {
        color: #dc3545;
    }
    
    .due-date.due-soon {
        color: #ffc107;
    }
    
    .grade-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .grade-item:last-child {
        border-bottom: none;
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
    
    .attendance-summary {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    .attendance-bar {
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin: 0.5rem 0;
    }
    
    .attendance-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
    }
</style>

<!-- Teacher Header -->
<div class="teacher-header">
    <div class="row align-items-center">
        <div class="col-auto">
            <div class="teacher-avatar">
                <?php echo strtoupper(substr($teacher_info['first_name'], 0, 1)); ?>
            </div>
        </div>
        <div class="col">
            <h2><?php echo htmlspecialchars($teacher_info['first_name'] . ' ' . $teacher_info['last_name']); ?></h2>
            <p class="mb-1">
                <strong>Teacher ID:</strong> <?php echo htmlspecialchars($teacher_info['teacher_id']); ?> |
                <strong>Specialization:</strong> <?php echo htmlspecialchars($teacher_info['specialization'] ?? 'General'); ?>
            </p>
            <p class="mb-0">
                <strong>Email:</strong> <?php echo htmlspecialchars($teacher_info['email']); ?> |
                <strong>Hire Date:</strong> <?php echo formatDate($teacher_info['hire_date']); ?>
            </p>
        </div>
        <div class="col-auto">
            <div class="text-center">
                <div class="h4 mb-0"><?php echo date('l'); ?></div>
                <small><?php echo date('F j, Y'); ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #007bff, #0056b3);">👥</div>
        <div class="stat-value text-primary"><?php echo $total_students; ?></div>
        <div class="stat-label">Total Students</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #1e7e34);">📚</div>
        <div class="stat-value text-success"><?php echo count($teacher_subjects); ?></div>
        <div class="stat-label">Subjects Teaching</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">📝</div>
        <div class="stat-value text-warning"><?php echo $total_assignments; ?></div>
        <div class="stat-label">Active Assignments</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">⏳</div>
        <div class="stat-value text-danger"><?php echo $pending_grade_count; ?></div>
        <div class="stat-label">Pending Grades</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="/college_management_system/teacher/attendance.php" class="action-card">
        <h5>📋 Mark Attendance</h5>
        <p>Record student attendance</p>
    </a>
    
    <a href="/college_management_system/teacher/add_results.php" class="action-card">
        <h5>📊 Enter Grades</h5>
        <p>Add student exam results</p>
    </a>
    
    <a href="/college_management_system/teacher/issue_assignment.php" class="action-card">
        <h5>📝 Create Assignment</h5>
        <p>Issue new assignments</p>
    </a>
    
    <a href="/college_management_system/teacher/upload_material.php" class="action-card">
        <h5>📁 Upload Materials</h5>
        <p>Share study materials</p>
    </a>
</div>

<!-- Main Content -->
<div class="row">
    <!-- My Subjects -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">My Subjects</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($teacher_subjects)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No subjects assigned</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($teacher_subjects as $subject): ?>
                        <div class="subject-card">
                            <div class="subject-header">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($subject['course_name']); ?> | 
                                        Semester <?php echo $subject['semester']; ?> | 
                                        <?php echo $subject['credits']; ?> Credits
                                    </small>
                                </div>
                                <div class="text-center">
                                    <div class="h6 text-primary mb-0"><?php echo $subject['student_count']; ?></div>
                                    <small class="text-muted">Students</small>
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <a href="/college_management_system/teacher/attendance.php?subject_id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-outline-primary">Attendance</a>
                                <a href="/college_management_system/teacher/add_results.php?subject_id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-outline-success">Grades</a>
                                <a href="/college_management_system/teacher/issue_assignment.php?subject_id=<?php echo $subject['id']; ?>" 
                                   class="btn btn-outline-info">Assignment</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-md-6">
        <!-- Today's Attendance -->
        <?php if (!empty($today_attendance)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">Today's Attendance</h5>
            </div>
            <div class="card-body">
                <?php foreach ($today_attendance as $attendance): ?>
                    <div class="attendance-summary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($attendance['subject_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($attendance['course_name']); ?></small>
                            </div>
                            <div class="text-center">
                                <small class="text-success"><?php echo $attendance['present_count']; ?> Present</small> |
                                <small class="text-danger"><?php echo $attendance['absent_count']; ?> Absent</small>
                            </div>
                        </div>
                        <?php 
                        $attendance_rate = $attendance['total_marked'] > 0 ? 
                            ($attendance['present_count'] / $attendance['total_marked']) * 100 : 0;
                        ?>
                        <div class="attendance-bar">
                            <div class="attendance-fill" style="width: <?php echo $attendance_rate; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo number_format($attendance_rate, 1); ?>% attendance rate</small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Recent Assignments -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title">Recent Assignments</h5>
                <a href="/college_management_system/teacher/assignments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_assignments)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No assignments found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_assignments as $assignment): ?>
                        <div class="assignment-item">
                            <div class="assignment-details">
                                <h6><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                <div class="assignment-meta">
                                    <?php echo htmlspecialchars($assignment['subject_name']); ?> | 
                                    <?php echo $assignment['submitted_count']; ?>/<?php echo $assignment['total_students']; ?> submitted
                                </div>
                            </div>
                            <div class="text-right">
                                <?php
                                $due_date = strtotime($assignment['due_date']);
                                $now = time();
                                $days_until_due = ceil(($due_date - $now) / (24 * 60 * 60));
                                
                                $class = '';
                                if ($days_until_due < 0) {
                                    $class = 'overdue';
                                } elseif ($days_until_due <= 2) {
                                    $class = 'due-soon';
                                }
                                ?>
                                <div class="due-date <?php echo $class; ?>">
                                    <?php echo formatDate($assignment['due_date']); ?>
                                </div>
                                <small class="text-muted">
                                    <?php 
                                    if ($days_until_due < 0) {
                                        echo abs($days_until_due) . ' days overdue';
                                    } elseif ($days_until_due == 0) {
                                        echo 'Due today';
                                    } else {
                                        echo $days_until_due . ' days left';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pending Grades -->
        <?php if (!empty($pending_grades)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Pending Grade Approvals</h5>
            </div>
            <div class="card-body p-0">
                <?php foreach (array_slice($pending_grades, 0, 5) as $grade): ?>
                    <div class="grade-item">
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></h6>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($grade['subject_name']); ?> | 
                                <?php echo ucfirst($grade['exam_type']); ?>
                            </small>
                        </div>
                        <div class="text-right">
                            <div class="h6 mb-0"><?php echo $grade['marks']; ?>/<?php echo $grade['max_marks']; ?></div>
                            <small class="text-muted"><?php echo formatDate($grade['exam_date']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($pending_grades) > 5): ?>
                    <div class="text-center py-2">
                        <a href="/college_management_system/teacher/pending_grades.php" class="btn btn-sm btn-outline-primary">
                            View All (<?php echo count($pending_grades); ?>)
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Alerts and Notifications -->
<?php if ($pending_grade_count > 0): ?>
<div class="alert alert-info mt-4">
    <h6>📋 Pending Actions</h6>
    <p class="mb-0">
        You have <strong><?php echo $pending_grade_count; ?></strong> grades waiting for approval.
        <a href="/college_management_system/teacher/pending_grades.php" class="alert-link">Review pending grades</a>
    </p>
</div>
<?php endif; ?>

<?php
// Check for assignments due soon
$assignments_due_soon = array_filter($recent_assignments, function($assignment) {
    $days_until_due = ceil((strtotime($assignment['due_date']) - time()) / (24 * 60 * 60));
    return $days_until_due <= 2 && $days_until_due >= 0;
});

if (!empty($assignments_due_soon)):
?>
<div class="alert alert-warning mt-2">
    <h6>⏰ Assignments Due Soon</h6>
    <p class="mb-0">
        You have <strong><?php echo count($assignments_due_soon); ?></strong> assignments due within 2 days.
        <a href="/college_management_system/teacher/assignments.php" class="alert-link">View assignments</a>
    </p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh dashboard data every 5 minutes
    setInterval(function() {
        fetch('/college_management_system/api/dashboard_stats.php?module=teacher')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboardStats(data.stats);
                }
            })
            .catch(error => {
                console.error('Error updating dashboard:', error);
            });
    }, 300000); // 5 minutes
    
    function updateDashboardStats(stats) {
        // Update stat values
        document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = stats.total_students;
        document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = stats.total_subjects;
        document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = stats.total_assignments;
        document.querySelector('.stat-card:nth-child(4) .stat-value').textContent = stats.pending_grades;
    }
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

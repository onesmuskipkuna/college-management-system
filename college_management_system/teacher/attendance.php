<?php
/**
 * Teacher Attendance Management
 * Mark and manage student attendance for classes
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require teacher role
Authentication::requireRole('teacher');

// Page configuration
$page_title = 'Student Attendance';
$show_page_header = true;
$page_subtitle = 'Mark and manage student attendance';

// Get current user and teacher info
$current_user = Authentication::getCurrentUser();
$teacher_info = fetchOne(
    "SELECT * FROM teachers WHERE user_id = ?",
    [$current_user['id']]
);

if (!$teacher_info) {
    header('Location: /college_management_system/login.php?error=teacher_profile_not_found');
    exit;
}

// Initialize variables
$error_message = '';
$success_message = '';
$selected_subject = null;
$selected_date = date('Y-m-d');
$students = [];
$existing_attendance = [];

// Get teacher's subjects
$teacher_subjects = fetchAll(
    "SELECT s.*, c.course_name 
     FROM subjects s
     JOIN courses c ON s.course_id = c.id
     WHERE s.status = 'active'
     ORDER BY c.course_name, s.semester, s.subject_name"
);

// Handle subject and date selection
if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {
    $subject_id = intval($_GET['subject_id']);
    $selected_subject = fetchOne(
        "SELECT s.*, c.course_name, c.id as course_id
         FROM subjects s
         JOIN courses c ON s.course_id = c.id
         WHERE s.id = ?",
        [$subject_id]
    );
    
    if ($selected_subject) {
        // Get date from URL or use today
        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $selected_date = sanitizeInput($_GET['date']);
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
                $selected_date = date('Y-m-d');
            }
        }
        
        // Get students enrolled in this course
        $students = fetchAll(
            "SELECT s.*, u.email
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE s.course_id = ? AND s.status = 'active'
             ORDER BY s.first_name, s.last_name",
            [$selected_subject['course_id']]
        );
        
        // Get existing attendance for this date and subject
        $existing_attendance_data = fetchAll(
            "SELECT student_id, status, notes
             FROM attendance
             WHERE subject_id = ? AND attendance_date = ? AND teacher_id = ?",
            [$subject_id, $selected_date, $teacher_info['id']]
        );
        
        // Convert to associative array for easy lookup
        foreach ($existing_attendance_data as $att) {
            $existing_attendance[$att['student_id']] = $att;
        }
    }
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $attendance_date = sanitizeInput($_POST['attendance_date'] ?? '');
        $attendance_data = $_POST['attendance'] ?? [];
        $notes_data = $_POST['notes'] ?? [];
        
        // Validation
        if (!$subject_id || !$attendance_date) {
            $error_message = 'Please select a subject and date.';
        } elseif (empty($attendance_data)) {
            $error_message = 'Please mark attendance for at least one student.';
        } else {
            try {
                beginTransaction();
                
                $processed_count = 0;
                $updated_count = 0;
                
                foreach ($attendance_data as $student_id => $status) {
                    $student_id = intval($student_id);
                    $status = sanitizeInput($status);
                    $notes = sanitizeInput($notes_data[$student_id] ?? '');
                    
                    // Validate status
                    if (!in_array($status, ['present', 'absent', 'late', 'excused'])) {
                        continue;
                    }
                    
                    // Check if attendance already exists
                    $existing = fetchOne(
                        "SELECT id FROM attendance 
                         WHERE student_id = ? AND subject_id = ? AND attendance_date = ? AND teacher_id = ?",
                        [$student_id, $subject_id, $attendance_date, $teacher_info['id']]
                    );
                    
                    if ($existing) {
                        // Update existing attendance
                        updateRecord('attendance', [
                            'status' => $status,
                            'notes' => $notes
                        ], 'id', $existing['id']);
                        $updated_count++;
                    } else {
                        // Insert new attendance record
                        insertRecord('attendance', [
                            'student_id' => $student_id,
                            'subject_id' => $subject_id,
                            'teacher_id' => $teacher_info['id'],
                            'attendance_date' => $attendance_date,
                            'status' => $status,
                            'notes' => $notes
                        ]);
                        $processed_count++;
                    }
                }
                
                commitTransaction();
                
                // Log activity
                logActivity($current_user['id'], 'attendance_marked', 
                    "Attendance marked for subject ID {$subject_id} on {$attendance_date}: {$processed_count} new, {$updated_count} updated");
                
                $success_message = "Attendance saved successfully! {$processed_count} new records added, {$updated_count} records updated.";
                
                // Refresh the page to show updated data
                header("Location: " . $_SERVER['PHP_SELF'] . "?subject_id={$subject_id}&date={$attendance_date}&success=1");
                exit;
                
            } catch (Exception $e) {
                rollbackTransaction();
                error_log("Attendance submission error: " . $e->getMessage());
                $error_message = 'Failed to save attendance: ' . $e->getMessage();
            }
        }
    }
}

// Get attendance statistics for selected subject
$attendance_stats = [];
if ($selected_subject) {
    $attendance_stats = fetchOne(
        "SELECT 
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
            COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as excused_count,
            COUNT(DISTINCT a.attendance_date) as total_days,
            COUNT(a.id) as total_records
         FROM attendance a
         WHERE a.subject_id = ? AND a.teacher_id = ?
           AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        [$selected_subject['id'], $teacher_info['id']]
    ) ?: [];
}

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .subject-selector {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .attendance-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .stat-item {
        text-align: center;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
    
    .attendance-form {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .student-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.3s ease;
    }
    
    .student-row:hover {
        background-color: #f8f9fa;
    }
    
    .student-row:last-child {
        border-bottom: none;
    }
    
    .student-info {
        display: flex;
        align-items: center;
    }
    
    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 1rem;
    }
    
    .student-details h6 {
        margin: 0 0 0.25rem 0;
        color: #2c3e50;
    }
    
    .student-meta {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .attendance-options {
        display: flex;
        gap: 0.5rem;
    }
    
    .attendance-option {
        position: relative;
    }
    
    .attendance-option input[type="radio"] {
        display: none;
    }
    
    .attendance-option label {
        display: block;
        padding: 0.5rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.875rem;
        font-weight: 500;
        text-align: center;
        min-width: 70px;
    }
    
    .attendance-option input[type="radio"]:checked + label.present {
        border-color: #28a745;
        background-color: #28a745;
        color: white;
    }
    
    .attendance-option input[type="radio"]:checked + label.absent {
        border-color: #dc3545;
        background-color: #dc3545;
        color: white;
    }
    
    .attendance-option input[type="radio"]:checked + label.late {
        border-color: #ffc107;
        background-color: #ffc107;
        color: #212529;
    }
    
    .attendance-option input[type="radio"]:checked + label.excused {
        border-color: #6c757d;
        background-color: #6c757d;
        color: white;
    }
    
    .notes-input {
        width: 200px;
        font-size: 0.875rem;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        padding: 0.375rem 0.75rem;
    }
    
    .bulk-actions {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .bulk-btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .attendance-summary {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        text-align: center;
    }
    
    .summary-item {
        padding: 1rem;
        border-radius: 8px;
    }
    
    .summary-item.present {
        background: #d4edda;
        color: #155724;
    }
    
    .summary-item.absent {
        background: #f8d7da;
        color: #721c24;
    }
    
    .summary-item.late {
        background: #fff3cd;
        color: #856404;
    }
    
    .summary-item.excused {
        background: #e2e3e5;
        color: #383d41;
    }
    
    @media (max-width: 768px) {
        .student-row {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        
        .attendance-options {
            justify-content: center;
            margin: 0.5rem 0;
        }
        
        .notes-input {
            width: 100%;
        }
    }
</style>

<!-- Subject Selector -->
<div class="subject-selector">
    <h4>Select Subject and Date</h4>
    <form method="GET" class="row align-items-end">
        <div class="col-md-5">
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-control" required>
                <option value="">Select a subject...</option>
                <?php foreach ($teacher_subjects as $subject): ?>
                    <option value="<?php echo $subject['id']; ?>" 
                            <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subject['course_name'] . ' - ' . $subject['subject_name']); ?>
                        (Semester <?php echo $subject['semester']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" 
                   value="<?php echo htmlspecialchars($selected_date); ?>" 
                   max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-block">Load Attendance</button>
        </div>
    </form>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Attendance has been saved successfully!
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

<?php if ($selected_subject): ?>
    <!-- Attendance Header -->
    <div class="attendance-header">
        <div class="row align-items-center">
            <div class="col">
                <h3><?php echo htmlspecialchars($selected_subject['subject_name']); ?></h3>
                <p class="mb-0">
                    <strong>Course:</strong> <?php echo htmlspecialchars($selected_subject['course_name']); ?> |
                    <strong>Date:</strong> <?php echo formatDate($selected_date); ?> |
                    <strong>Students:</strong> <?php echo count($students); ?>
                </p>
            </div>
            <div class="col-auto">
                <div class="text-center">
                    <div class="h4 mb-0"><?php echo count($existing_attendance); ?></div>
                    <small>Marked</small>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <?php if (!empty($attendance_stats) && $attendance_stats['total_records'] > 0): ?>
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendance_stats['present_count']; ?></div>
                <div class="stat-label">Present (30 days)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendance_stats['absent_count']; ?></div>
                <div class="stat-label">Absent (30 days)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendance_stats['late_count']; ?></div>
                <div class="stat-label">Late (30 days)</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $attendance_stats['total_days']; ?></div>
                <div class="stat-label">Class Days</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($students)): ?>
        <div class="alert alert-warning">
            <h5>No Students Found</h5>
            <p class="mb-0">No active students are enrolled in this course.</p>
        </div>
    <?php else: ?>
        <!-- Attendance Form -->
        <div class="attendance-form">
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="submit_attendance" value="1">
                <input type="hidden" name="subject_id" value="<?php echo $selected_subject['id']; ?>">
                <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                
                <!-- Bulk Actions -->
                <div class="bulk-actions">
                    <h6>Quick Actions:</h6>
                    <button type="button" class="btn btn-success btn-sm bulk-btn" onclick="markAll('present')">
                        Mark All Present
                    </button>
                    <button type="button" class="btn btn-danger btn-sm bulk-btn" onclick="markAll('absent')">
                        Mark All Absent
                    </button>
                    <button type="button" class="btn btn-warning btn-sm bulk-btn" onclick="markAll('late')">
                        Mark All Late
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm bulk-btn" onclick="clearAll()">
                        Clear All
                    </button>
                </div>
                
                <!-- Student List -->
                <div class="student-list">
                    <?php foreach ($students as $student): ?>
                        <?php 
                        $existing_status = $existing_attendance[$student['id']]['status'] ?? '';
                        $existing_notes = $existing_attendance[$student['id']]['notes'] ?? '';
                        ?>
                        <div class="student-row">
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h6><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                    <div class="student-meta">
                                        ID: <?php echo htmlspecialchars($student['student_id']); ?> | 
                                        <?php echo htmlspecialchars($student['phone']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="attendance-options">
                                <div class="attendance-option">
                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                           value="present" id="present_<?php echo $student['id']; ?>"
                                           <?php echo ($existing_status === 'present') ? 'checked' : ''; ?>>
                                    <label for="present_<?php echo $student['id']; ?>" class="present">Present</label>
                                </div>
                                <div class="attendance-option">
                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                           value="absent" id="absent_<?php echo $student['id']; ?>"
                                           <?php echo ($existing_status === 'absent') ? 'checked' : ''; ?>>
                                    <label for="absent_<?php echo $student['id']; ?>" class="absent">Absent</label>
                                </div>
                                <div class="attendance-option">
                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                           value="late" id="late_<?php echo $student['id']; ?>"
                                           <?php echo ($existing_status === 'late') ? 'checked' : ''; ?>>
                                    <label for="late_<?php echo $student['id']; ?>" class="late">Late</label>
                                </div>
                                <div class="attendance-option">
                                    <input type="radio" name="attendance[<?php echo $student['id']; ?>]" 
                                           value="excused" id="excused_<?php echo $student['id']; ?>"
                                           <?php echo ($existing_status === 'excused') ? 'checked' : ''; ?>>
                                    <label for="excused_<?php echo $student['id']; ?>" class="excused">Excused</label>
                                </div>
                            </div>
                            
                            <div>
                                <input type="text" name="notes[<?php echo $student['id']; ?>]" 
                                       class="notes-input" placeholder="Notes (optional)"
                                       value="<?php echo htmlspecialchars($existing_notes); ?>">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        Save Attendance
                    </button>
                    <a href="/college_management_system/teacher/dashboard.php" class="btn btn-secondary btn-lg ml-2">
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
function markAll(status) {
    const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
    radios.forEach(radio => {
        radio.checked = true;
    });
}

function clearAll() {
    const radios = document.querySelectorAll('input[type="radio"]');
    radios.forEach(radio => {
        radio.checked = false;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        const checkedRadios = document.querySelectorAll('input[type="radio"]:checked');
        
        if (checkedRadios.length === 0) {
            e.preventDefault();
            alert('Please mark attendance for at least one student.');
            return;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Saving Attendance...';
    });
    
    // Auto-save functionality (optional)
    let autoSaveTimeout;
    const form = document.getElementById('attendanceForm');
    
    form.addEventListener('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            // Could implement auto-save here
            console.log('Auto-save triggered');
        }, 5000); // 5 seconds after last change
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

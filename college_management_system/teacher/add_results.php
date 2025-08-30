<?php
/**
 * Teacher Grade Entry
 * Add and manage student exam results and grades
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require teacher role
Authentication::requireRole('teacher');

// Page configuration
$page_title = 'Enter Student Grades';
$show_page_header = true;
$page_subtitle = 'Add exam results and grades for students';

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
$selected_exam_type = '';
$selected_exam_date = date('Y-m-d');
$students = [];
$existing_grades = [];

// Get teacher's subjects
$teacher_subjects = fetchAll(
    "SELECT s.*, c.course_name 
     FROM subjects s
     JOIN courses c ON s.course_id = c.id
     WHERE s.status = 'active'
     ORDER BY c.course_name, s.semester, s.subject_name"
);

// Exam types
$exam_types = [
    'assignment' => 'Assignment',
    'quiz' => 'Quiz',
    'midterm' => 'Midterm Exam',
    'final' => 'Final Exam',
    'project' => 'Project'
];

// Grading scale
$grading_scale = [
    ['min' => 90, 'max' => 100, 'grade' => 'A+', 'gpa' => 4.0],
    ['min' => 85, 'max' => 89, 'grade' => 'A', 'gpa' => 4.0],
    ['min' => 80, 'max' => 84, 'grade' => 'A-', 'gpa' => 3.7],
    ['min' => 75, 'max' => 79, 'grade' => 'B+', 'gpa' => 3.3],
    ['min' => 70, 'max' => 74, 'grade' => 'B', 'gpa' => 3.0],
    ['min' => 65, 'max' => 69, 'grade' => 'B-', 'gpa' => 2.7],
    ['min' => 60, 'max' => 64, 'grade' => 'C+', 'gpa' => 2.3],
    ['min' => 55, 'max' => 59, 'grade' => 'C', 'gpa' => 2.0],
    ['min' => 50, 'max' => 54, 'grade' => 'C-', 'gpa' => 1.7],
    ['min' => 45, 'max' => 49, 'grade' => 'D+', 'gpa' => 1.3],
    ['min' => 40, 'max' => 44, 'grade' => 'D', 'gpa' => 1.0],
    ['min' => 0, 'max' => 39, 'grade' => 'F', 'gpa' => 0.0]
];

// Handle subject selection
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
        // Get parameters from URL
        $selected_exam_type = sanitizeInput($_GET['exam_type'] ?? '');
        $selected_exam_date = sanitizeInput($_GET['exam_date'] ?? date('Y-m-d'));
        
        // Get students enrolled in this course
        $students = fetchAll(
            "SELECT s.*, u.email
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE s.course_id = ? AND s.status = 'active'
             ORDER BY s.first_name, s.last_name",
            [$selected_subject['course_id']]
        );
        
        // Get existing grades if exam type and date are selected
        if ($selected_exam_type && $selected_exam_date) {
            $existing_grades_data = fetchAll(
                "SELECT student_id, marks, max_marks, grade, status
                 FROM grades
                 WHERE subject_id = ? AND teacher_id = ? AND exam_type = ? AND exam_date = ?",
                [$subject_id, $teacher_info['id'], $selected_exam_type, $selected_exam_date]
            );
            
            // Convert to associative array for easy lookup
            foreach ($existing_grades_data as $grade) {
                $existing_grades[$grade['student_id']] = $grade;
            }
        }
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $exam_type = sanitizeInput($_POST['exam_type'] ?? '');
        $exam_date = sanitizeInput($_POST['exam_date'] ?? '');
        $max_marks = floatval($_POST['max_marks'] ?? 100);
        $grades_data = $_POST['grades'] ?? [];
        
        // Validation
        if (!$subject_id || !$exam_type || !$exam_date) {
            $error_message = 'Please fill in all required fields.';
        } elseif ($max_marks <= 0) {
            $error_message = 'Maximum marks must be greater than zero.';
        } elseif (empty($grades_data)) {
            $error_message = 'Please enter grades for at least one student.';
        } else {
            try {
                beginTransaction();
                
                $processed_count = 0;
                $updated_count = 0;
                $errors = [];
                
                foreach ($grades_data as $student_id => $marks) {
                    $student_id = intval($student_id);
                    $marks = floatval($marks);
                    
                    // Skip if no marks entered
                    if ($marks === 0.0 && $_POST['grades'][$student_id] === '') {
                        continue;
                    }
                    
                    // Validate marks
                    if ($marks < 0 || $marks > $max_marks) {
                        $student_name = fetchOne("SELECT CONCAT(first_name, ' ', last_name) as name FROM students WHERE id = ?", [$student_id])['name'];
                        $errors[] = "Invalid marks for {$student_name}: {$marks} (must be between 0 and {$max_marks})";
                        continue;
                    }
                    
                    // Calculate percentage and grade
                    $percentage = ($marks / $max_marks) * 100;
                    $letter_grade = calculateGrade($percentage, $grading_scale);
                    
                    // Check if grade already exists
                    $existing = fetchOne(
                        "SELECT id FROM grades 
                         WHERE student_id = ? AND subject_id = ? AND teacher_id = ? AND exam_type = ? AND exam_date = ?",
                        [$student_id, $subject_id, $teacher_info['id'], $exam_type, $exam_date]
                    );
                    
                    if ($existing) {
                        // Update existing grade
                        updateRecord('grades', [
                            'marks' => $marks,
                            'max_marks' => $max_marks,
                            'grade' => $letter_grade,
                            'status' => 'pending' // Reset to pending for approval
                        ], 'id', $existing['id']);
                        $updated_count++;
                    } else {
                        // Insert new grade
                        insertRecord('grades', [
                            'student_id' => $student_id,
                            'subject_id' => $subject_id,
                            'teacher_id' => $teacher_info['id'],
                            'exam_type' => $exam_type,
                            'marks' => $marks,
                            'max_marks' => $max_marks,
                            'grade' => $letter_grade,
                            'exam_date' => $exam_date,
                            'status' => 'pending'
                        ]);
                        $processed_count++;
                    }
                }
                
                if (!empty($errors)) {
                    rollbackTransaction();
                    $error_message = 'Validation errors:<br>' . implode('<br>', $errors);
                } else {
                    commitTransaction();
                    
                    // Log activity
                    logActivity($current_user['id'], 'grades_entered', 
                        "Grades entered for subject ID {$subject_id}, {$exam_type} on {$exam_date}: {$processed_count} new, {$updated_count} updated");
                    
                    $success_message = "Grades saved successfully! {$processed_count} new grades added, {$updated_count} grades updated.";
                    
                    // Refresh the page to show updated data
                    header("Location: " . $_SERVER['PHP_SELF'] . "?subject_id={$subject_id}&exam_type={$exam_type}&exam_date={$exam_date}&success=1");
                    exit;
                }
                
            } catch (Exception $e) {
                rollbackTransaction();
                error_log("Grade submission error: " . $e->getMessage());
                $error_message = 'Failed to save grades: ' . $e->getMessage();
            }
        }
    }
}

// Function to calculate letter grade
function calculateGrade($percentage, $grading_scale) {
    foreach ($grading_scale as $scale) {
        if ($percentage >= $scale['min'] && $percentage <= $scale['max']) {
            return $scale['grade'];
        }
    }
    return 'F';
}

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .grade-selector {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .grade-header {
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    
    .grading-scale {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .scale-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .scale-item {
        text-align: center;
        padding: 0.5rem;
        border-radius: 5px;
        font-size: 0.875rem;
    }
    
    .scale-item.excellent {
        background: #d4edda;
        color: #155724;
    }
    
    .scale-item.good {
        background: #cce7ff;
        color: #004085;
    }
    
    .scale-item.average {
        background: #fff3cd;
        color: #856404;
    }
    
    .scale-item.poor {
        background: #f8d7da;
        color: #721c24;
    }
    
    .grade-form {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .student-grade-row {
        display: grid;
        grid-template-columns: 1fr auto auto auto;
        gap: 1rem;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #f8f9fa;
        transition: background-color 0.3s ease;
    }
    
    .student-grade-row:hover {
        background-color: #f8f9fa;
    }
    
    .student-grade-row:last-child {
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
        background: linear-gradient(135deg, #6f42c1, #e83e8c);
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
    
    .marks-input {
        width: 80px;
        text-align: center;
        font-weight: 600;
        border: 2px solid #e9ecef;
        border-radius: 5px;
        padding: 0.5rem;
    }
    
    .marks-input:focus {
        border-color: #6f42c1;
        box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
    }
    
    .grade-display {
        font-weight: 600;
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        text-align: center;
        min-width: 60px;
    }
    
    .grade-A { background: #d4edda; color: #155724; }
    .grade-B { background: #cce7ff; color: #004085; }
    .grade-C { background: #fff3cd; color: #856404; }
    .grade-D { background: #ffeaa7; color: #856404; }
    .grade-F { background: #f8d7da; color: #721c24; }
    
    .percentage-display {
        font-weight: 600;
        color: #6c757d;
        text-align: center;
        min-width: 60px;
    }
    
    .exam-config {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .quick-fill {
        background: #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .quick-fill-btn {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .statistics {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        text-align: center;
    }
    
    .stat-item {
        padding: 1rem;
        border-radius: 8px;
        background: #f8f9fa;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #6f42c1;
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    @media (max-width: 768px) {
        .student-grade-row {
            grid-template-columns: 1fr;
            gap: 0.5rem;
            text-align: center;
        }
        
        .marks-input {
            width: 100px;
        }
    }
</style>

<!-- Grade Entry Selector -->
<div class="grade-selector">
    <h4>Select Subject and Exam Details</h4>
    <form method="GET" class="row align-items-end">
        <div class="col-md-4">
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
        <div class="col-md-3">
            <label class="form-label">Exam Type</label>
            <select name="exam_type" class="form-control" required>
                <option value="">Select exam type...</option>
                <?php foreach ($exam_types as $value => $label): ?>
                    <option value="<?php echo $value; ?>" 
                            <?php echo ($selected_exam_type === $value) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Exam Date</label>
            <input type="date" name="exam_date" class="form-control" 
                   value="<?php echo htmlspecialchars($selected_exam_date); ?>" 
                   max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-block">Load Students</button>
        </div>
    </form>
</div>

<!-- Grading Scale Reference -->
<div class="grading-scale">
    <h5>Grading Scale Reference</h5>
    <div class="scale-grid">
        <?php foreach ($grading_scale as $scale): ?>
            <?php
            $class = '';
            if ($scale['grade'][0] === 'A') $class = 'excellent';
            elseif ($scale['grade'][0] === 'B') $class = 'good';
            elseif ($scale['grade'][0] === 'C') $class = 'average';
            else $class = 'poor';
            ?>
            <div class="scale-item <?php echo $class; ?>">
                <strong><?php echo $scale['grade']; ?></strong><br>
                <?php echo $scale['min']; ?>-<?php echo $scale['max']; ?>%
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Grades have been saved successfully and are pending approval!
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($selected_subject && $selected_exam_type): ?>
    <!-- Grade Header -->
    <div class="grade-header">
        <div class="row align-items-center">
            <div class="col">
                <h3><?php echo htmlspecialchars($selected_subject['subject_name']); ?></h3>
                <p class="mb-0">
                    <strong>Course:</strong> <?php echo htmlspecialchars($selected_subject['course_name']); ?> |
                    <strong>Exam:</strong> <?php echo htmlspecialchars($exam_types[$selected_exam_type]); ?> |
                    <strong>Date:</strong> <?php echo formatDate($selected_exam_date); ?>
                </p>
            </div>
            <div class="col-auto">
                <div class="text-center">
                    <div class="h4 mb-0"><?php echo count($existing_grades); ?></div>
                    <small>Grades Entered</small>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($students)): ?>
        <div class="alert alert-warning">
            <h5>No Students Found</h5>
            <p class="mb-0">No active students are enrolled in this course.</p>
        </div>
    <?php else: ?>
        <!-- Grade Entry Form -->
        <div class="grade-form">
            <form method="POST" id="gradeForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="submit_grades" value="1">
                <input type="hidden" name="subject_id" value="<?php echo $selected_subject['id']; ?>">
                <input type="hidden" name="exam_type" value="<?php echo htmlspecialchars($selected_exam_type); ?>">
                <input type="hidden" name="exam_date" value="<?php echo htmlspecialchars($selected_exam_date); ?>">
                
                <!-- Exam Configuration -->
                <div class="exam-config">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label">Maximum Marks</label>
                            <input type="number" name="max_marks" class="form-control" 
                                   value="100" min="1" max="1000" step="0.01" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Quick Fill Options</label>
                            <div class="quick-fill">
                                <button type="button" class="btn btn-success btn-sm quick-fill-btn" onclick="fillAllGrades(90)">
                                    Fill All A (90%)
                                </button>
                                <button type="button" class="btn btn-info btn-sm quick-fill-btn" onclick="fillAllGrades(75)">
                                    Fill All B (75%)
                                </button>
                                <button type="button" class="btn btn-warning btn-sm quick-fill-btn" onclick="fillAllGrades(60)">
                                    Fill All C (60%)
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm quick-fill-btn" onclick="clearAllGrades()">
                                    Clear All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Student Grades -->
                <div class="student-grades">
                    <div class="row mb-3">
                        <div class="col">
                            <h6>Student</h6>
                        </div>
                        <div class="col-auto">
                            <h6>Marks</h6>
                        </div>
                        <div class="col-auto">
                            <h6>Percentage</h6>
                        </div>
                        <div class="col-auto">
                            <h6>Grade</h6>
                        </div>
                    </div>
                    
                    <?php foreach ($students as $student): ?>
                        <?php 
                        $existing_marks = $existing_grades[$student['id']]['marks'] ?? '';
                        $existing_grade = $existing_grades[$student['id']]['grade'] ?? '';
                        $existing_status = $existing_grades[$student['id']]['status'] ?? '';
                        ?>
                        <div class="student-grade-row">
                            <div class="student-info">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
                                </div>
                                <div class="student-details">
                                    <h6><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                    <div class="student-meta">
                                        ID: <?php echo htmlspecialchars($student['student_id']); ?>
                                        <?php if ($existing_status): ?>
                                            | Status: <span class="badge badge-<?php echo ($existing_status === 'approved') ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($existing_status); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <input type="number" name="grades[<?php echo $student['id']; ?>]" 
                                       class="marks-input" placeholder="0" 
                                       min="0" step="0.01" 
                                       value="<?php echo htmlspecialchars($existing_marks); ?>"
                                       data-student-id="<?php echo $student['id']; ?>"
                                       onchange="calculateGrade(this)">
                            </div>
                            
                            <div class="percentage-display" id="percentage_<?php echo $student['id']; ?>">
                                <?php echo $existing_marks ? number_format(($existing_marks / 100) * 100, 1) . '%' : '-'; ?>
                            </div>
                            
                            <div class="grade-display grade-<?php echo substr($existing_grade, 0, 1); ?>" 
                                 id="grade_<?php echo $student['id']; ?>">
                                <?php echo $existing_grade ?: '-'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        Save Grades
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
// Grading scale (JavaScript version)
const gradingScale = <?php echo json_encode($grading_scale); ?>;

function calculateGrade(input) {
    const studentId = input.dataset.studentId;
    const marks = parseFloat(input.value) || 0;
    const maxMarks = parseFloat(document.querySelector('input[name="max_marks"]').value) || 100;
    
    const percentage = (marks / maxMarks) * 100;
    const grade = getLetterGrade(percentage);
    
    // Update percentage display
    const percentageElement = document.getElementById(`percentage_${studentId}`);
    percentageElement.textContent = marks > 0 ? `${percentage.toFixed(1)}%` : '-';
    
    // Update grade display
    const gradeElement = document.getElementById(`grade_${studentId}`);
    gradeElement.textContent = marks > 0 ? grade : '-';
    gradeElement.className = `grade-display grade-${grade.charAt(0)}`;
}

function getLetterGrade(percentage) {
    for (const scale of gradingScale) {
        if (percentage >= scale.min && percentage <= scale.max) {
            return scale.grade;
        }
    }
    return 'F';
}

function fillAllGrades(percentage) {
    const maxMarks = parseFloat(document.querySelector('input[name="max_marks"]').value) || 100;
    const marks = (percentage / 100) * maxMarks;
    
    const inputs = document.querySelectorAll('.marks-input');
    inputs.forEach(input => {
        input.value = marks.toFixed(1);
        calculateGrade(input);
    });
}

function clearAllGrades() {
    const inputs = document.querySelectorAll('.marks-input');
    inputs.forEach(input => {
        input.value = '';
        calculateGrade(input);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Update grades when max marks changes
    document.querySelector('input[name="max_marks"]').addEventListener('change', function() {
        const inputs = document.querySelectorAll('.marks-input');
        inputs.forEach(input => {
            if (input.value) {
                calculateGrade(input);
            }
        });
    });
    
    // Form validation
    document.getElementById('gradeForm').addEventListener('submit', function(e) {
        const maxMarks = parseFloat(document.querySelector('input[name="max_marks"]').value);
        const inputs = document.querySelectorAll('.marks-input');
        let hasGrades = false;
        let errors = [];
        
        inputs.forEach(input => {
            const marks = parseFloat(input.value);
            if (input.value && !isNaN(marks)) {
                hasGrades = true;
                if (marks < 0 || marks > maxMarks) {
                    const studentRow = input.closest('.student-grade-row');
                    const studentName = studentRow.querySelector('.student-details h6').textContent;
                    errors.push(`Invalid marks for ${studentName}: ${marks} (must be between 0 and ${maxMarks})`);
                }
            }
        });
        
        if (!hasGrades) {
            e.preventDefault();
            alert('Please enter grades for at least one student.');
            return;
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert('Validation errors:\n' + errors.join('\n'));
            return;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Saving Grades...';
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

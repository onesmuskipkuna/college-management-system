<?php
/**
 * Advanced Exam Management System
 * Create, schedule, and manage examinations with real-time monitoring
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require teacher or headteacher role
Authentication::requireRole(['teacher', 'headteacher']);

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Get teacher information
try {
    if ($user['role'] === 'teacher') {
        $teacher = fetchOne("SELECT * FROM teachers WHERE user_id = ?", [$user['id']]);
        if (!$teacher) {
            throw new Exception("Teacher profile not found");
        }
    } else {
        $teacher = ['id' => 1, 'teacher_id' => 'T001', 'first_name' => 'Head', 'last_name' => 'Teacher'];
    }
} catch (Exception $e) {
    $teacher = ['id' => 1, 'teacher_id' => 'T001', 'first_name' => 'Demo', 'last_name' => 'Teacher'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_exam':
                    $exam_data = [
                        'teacher_id' => $teacher['id'],
                        'subject_id' => (int)$_POST['subject_id'],
                        'exam_title' => sanitizeInput($_POST['exam_title']),
                        'exam_type' => sanitizeInput($_POST['exam_type']),
                        'exam_date' => sanitizeInput($_POST['exam_date']),
                        'start_time' => sanitizeInput($_POST['start_time']),
                        'duration_minutes' => (int)$_POST['duration_minutes'],
                        'total_marks' => (float)$_POST['total_marks'],
                        'instructions' => sanitizeInput($_POST['instructions']),
                        'venue' => sanitizeInput($_POST['venue']),
                        'status' => 'scheduled'
                    ];
                    
                    $exam_id = insertRecord('exams', $exam_data);
                    
                    // Create exam questions if provided
                    if (!empty($_POST['questions'])) {
                        foreach ($_POST['questions'] as $index => $question) {
                            if (!empty($question['question_text'])) {
                                $question_data = [
                                    'exam_id' => $exam_id,
                                    'question_number' => $index + 1,
                                    'question_text' => sanitizeInput($question['question_text']),
                                    'question_type' => sanitizeInput($question['question_type']),
                                    'marks' => (float)$question['marks'],
                                    'options' => isset($question['options']) ? json_encode($question['options']) : null,
                                    'correct_answer' => sanitizeInput($question['correct_answer'] ?? '')
                                ];
                                insertRecord('exam_questions', $question_data);
                            }
                        }
                    }
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'exam_created', "Exam '{$exam_data['exam_title']}' created");
                    }
                    
                    $message = "Exam '{$exam_data['exam_title']}' has been successfully created and scheduled.";
                    break;
                    
                case 'update_exam_status':
                    $exam_id = (int)$_POST['exam_id'];
                    $new_status = sanitizeInput($_POST['new_status']);
                    
                    updateRecord('exams', ['status' => $new_status], 'id', $exam_id);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'exam_status_updated', "Exam ID {$exam_id} status changed to {$new_status}");
                    }
                    
                    $message = "Exam status has been updated successfully.";
                    break;
                    
                case 'record_attendance':
                    $exam_id = (int)$_POST['exam_id'];
                    $attendance_data = $_POST['attendance'] ?? [];
                    
                    foreach ($attendance_data as $student_id => $status) {
                        $attendance_record = [
                            'exam_id' => $exam_id,
                            'student_id' => (int)$student_id,
                            'attendance_status' => sanitizeInput($status),
                            'recorded_by' => $teacher['id'],
                            'recorded_at' => date('Y-m-d H:i:s')
                        ];
                        
                        // Check if record exists
                        $existing = fetchOne("SELECT id FROM exam_attendance WHERE exam_id = ? AND student_id = ?", [$exam_id, $student_id]);
                        
                        if ($existing) {
                            updateRecord('exam_attendance', $attendance_record, 'id', $existing['id']);
                        } else {
                            insertRecord('exam_attendance', $attendance_record);
                        }
                    }
                    
                    $message = "Exam attendance has been recorded successfully.";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get subjects for the teacher
try {
    if ($user['role'] === 'headteacher') {
        $subjects = fetchAll("
            SELECT s.*, c.course_name 
            FROM subjects s 
            JOIN courses c ON s.course_id = c.id 
            WHERE s.status = 'active'
            ORDER BY c.course_name, s.subject_name
        ");
    } else {
        $subjects = fetchAll("
            SELECT s.*, c.course_name 
            FROM subjects s 
            JOIN courses c ON s.course_id = c.id 
            WHERE s.status = 'active'
            ORDER BY c.course_name, s.subject_name
        ");
    }
} catch (Exception $e) {
    $subjects = [
        ['id' => 1, 'subject_name' => 'Computer Programming', 'course_name' => 'Computer Science'],
        ['id' => 2, 'subject_name' => 'Database Systems', 'course_name' => 'Computer Science'],
        ['id' => 3, 'subject_name' => 'Business Management', 'course_name' => 'Business Management']
    ];
}

// Get exams
try {
    $exams = fetchAll("
        SELECT e.*, s.subject_name, c.course_name,
               COUNT(DISTINCT ea.student_id) as registered_students,
               COUNT(DISTINCT CASE WHEN ea.attendance_status = 'present' THEN ea.student_id END) as present_students
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN exam_attendance ea ON e.id = ea.exam_id
        WHERE e.teacher_id = ?
        GROUP BY e.id
        ORDER BY e.exam_date DESC, e.start_time DESC
    ", [$teacher['id']]);
} catch (Exception $e) {
    // Demo data
    $exams = [
        [
            'id' => 1,
            'exam_title' => 'Database Systems Final Exam',
            'exam_type' => 'Final',
            'subject_name' => 'Database Systems',
            'course_name' => 'Computer Science',
            'exam_date' => '2024-02-20',
            'start_time' => '09:00:00',
            'duration_minutes' => 180,
            'total_marks' => 100,
            'venue' => 'Computer Lab 1',
            'status' => 'scheduled',
            'registered_students' => 25,
            'present_students' => 0,
            'created_at' => '2024-01-15 10:00:00'
        ],
        [
            'id' => 2,
            'exam_title' => 'Programming Midterm',
            'exam_type' => 'Midterm',
            'subject_name' => 'Computer Programming',
            'course_name' => 'Computer Science',
            'exam_date' => '2024-02-15',
            'start_time' => '14:00:00',
            'duration_minutes' => 120,
            'total_marks' => 50,
            'venue' => 'Lecture Hall A',
            'status' => 'completed',
            'registered_students' => 28,
            'present_students' => 26,
            'created_at' => '2024-01-10 09:00:00'
        ]
    ];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Exam Management</h1>
        <p>Create, schedule, and manage examinations with real-time monitoring</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-success">
        <strong>Success!</strong> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Exam Management Tabs -->
    <div class="exam-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showExamTab('exam-list')">My Exams</button>
            <button class="tab-button" onclick="showExamTab('create-exam')">Create Exam</button>
            <button class="tab-button" onclick="showExamTab('attendance')">Attendance</button>
            <button class="tab-button" onclick="showExamTab('analytics')">Analytics</button>
        </div>

        <!-- Exam List Tab -->
        <div id="exam-list" class="tab-content active">
            <div class="exam-controls">
                <div class="controls-left">
                    <h2>Examination Schedule</h2>
                </div>
                <div class="controls-right">
                    <select id="exam-filter" onchange="filterExams()">
                        <option value="">All Exams</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="exams-grid">
                <?php if (empty($exams)): ?>
                <div class="no-data">No exams created yet. Create your first exam to get started.</div>
                <?php else: ?>
                <?php foreach ($exams as $exam): ?>
                <?php 
                $exam_datetime = strtotime($exam['exam_date'] . ' ' . $exam['start_time']);
                $current_time = time();
                $is_upcoming = $exam_datetime > $current_time;
                $is_ongoing = $exam_datetime <= $current_time && $exam_datetime + ($exam['duration_minutes'] * 60) > $current_time;
                $is_past = $exam_datetime + ($exam['duration_minutes'] * 60) <= $current_time;
                
                $status_class = $exam['status'];
                if ($is_ongoing && $exam['status'] === 'scheduled') {
                    $status_class = 'ongoing';
                }
                ?>
                <div class="exam-card <?= $status_class ?>" data-status="<?= $exam['status'] ?>">
                    <div class="exam-header">
                        <div class="exam-title">
                            <h3><?= htmlspecialchars($exam['exam_title']) ?></h3>
                            <span class="exam-type"><?= htmlspecialchars($exam['exam_type']) ?></span>
                        </div>
                        <div class="exam-status">
                            <span class="status-badge <?= $status_class ?>">
                                <?= $is_ongoing ? 'Ongoing' : ucfirst($exam['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="exam-details">
                        <div class="detail-row">
                            <span class="detail-label">Subject:</span>
                            <span class="detail-value"><?= htmlspecialchars($exam['subject_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Course:</span>
                            <span class="detail-value"><?= htmlspecialchars($exam['course_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Date & Time:</span>
                            <span class="detail-value">
                                <?= date('M j, Y', strtotime($exam['exam_date'])) ?> at 
                                <?= date('g:i A', strtotime($exam['start_time'])) ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Duration:</span>
                            <span class="detail-value"><?= $exam['duration_minutes'] ?> minutes</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Venue:</span>
                            <span class="detail-value"><?= htmlspecialchars($exam['venue']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Marks:</span>
                            <span class="detail-value"><?= $exam['total_marks'] ?> points</span>
                        </div>
                    </div>
                    
                    <div class="exam-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= $exam['registered_students'] ?></span>
                            <span class="stat-label">Registered</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $exam['present_students'] ?></span>
                            <span class="stat-label">Present</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?= $exam['registered_students'] > 0 ? round(($exam['present_students'] / $exam['registered_students']) * 100) : 0 ?>%
                            </span>
                            <span class="stat-label">Attendance</span>
                        </div>
                    </div>
                    
                    <div class="exam-actions">
                        <?php if ($exam['status'] === 'scheduled' && $is_upcoming): ?>
                        <button class="btn btn-primary btn-sm" onclick="editExam(<?= $exam['id'] ?>)">
                            Edit Exam
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="cancelExam(<?= $exam['id'] ?>)">
                            Cancel
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($is_ongoing || ($exam['status'] === 'scheduled' && !$is_upcoming)): ?>
                        <button class="btn btn-success btn-sm" onclick="recordAttendance(<?= $exam['id'] ?>)">
                            Record Attendance
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-info btn-sm" onclick="viewExamDetails(<?= $exam['id'] ?>)">
                            View Details
                        </button>
                        
                        <?php if ($exam['status'] === 'completed'): ?>
                        <button class="btn btn-secondary btn-sm" onclick="viewResults(<?= $exam['id'] ?>)">
                            View Results
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_ongoing): ?>
                    <div class="exam-timer">
                        <div class="timer-label">Time Remaining:</div>
                        <div class="timer-display" data-end-time="<?= $exam_datetime + ($exam['duration_minutes'] * 60) ?>">
                            <span class="hours">00</span>:<span class="minutes">00</span>:<span class="seconds">00</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create Exam Tab -->
        <div id="create-exam" class="tab-content">
            <h2>Create New Examination</h2>
            
            <form method="POST" class="exam-form" id="create-exam-form">
                <input type="hidden" name="action" value="create_exam">
                
                <div class="form-section">
                    <h3>Basic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_id">Subject:</label>
                            <select name="subject_id" id="subject_id" required>
                                <option value="">Select a subject...</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>">
                                    <?= htmlspecialchars($subject['subject_name']) ?> 
                                    (<?= htmlspecialchars($subject['course_name']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="exam_type">Exam Type:</label>
                            <select name="exam_type" id="exam_type" required>
                                <option value="">Select type...</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Midterm">Midterm</option>
                                <option value="Final">Final Exam</option>
                                <option value="Practical">Practical</option>
                                <option value="Assignment">Assignment Test</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_title">Exam Title:</label>
                        <input type="text" name="exam_title" id="exam_title" required 
                               placeholder="e.g., Database Systems Final Examination" maxlength="200">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exam_date">Exam Date:</label>
                            <input type="date" name="exam_date" id="exam_date" required 
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="start_time">Start Time:</label>
                            <input type="time" name="start_time" id="start_time" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_minutes">Duration (minutes):</label>
                            <input type="number" name="duration_minutes" id="duration_minutes" required 
                                   min="15" max="480" value="120">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="total_marks">Total Marks:</label>
                            <input type="number" name="total_marks" id="total_marks" required 
                                   min="1" max="1000" value="100" step="0.5">
                        </div>
                        
                        <div class="form-group">
                            <label for="venue">Venue:</label>
                            <input type="text" name="venue" id="venue" required 
                                   placeholder="e.g., Computer Lab 1, Lecture Hall A" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="instructions">Instructions:</label>
                        <textarea name="instructions" id="instructions" rows="4" 
                                  placeholder="Enter exam instructions, rules, and guidelines..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Questions (Optional)</h3>
                    <p class="section-description">Add questions for objective exams. Leave blank for subjective exams.</p>
                    
                    <div id="questions-container">
                        <div class="question-item" data-question="1">
                            <div class="question-header">
                                <h4>Question 1</h4>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(1)">Remove</button>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group flex-2">
                                    <label>Question Text:</label>
                                    <textarea name="questions[0][question_text]" rows="2" 
                                              placeholder="Enter the question..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Type:</label>
                                    <select name="questions[0][question_type]" onchange="toggleQuestionOptions(0)">
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="true_false">True/False</option>
                                        <option value="short_answer">Short Answer</option>
                                        <option value="essay">Essay</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Marks:</label>
                                    <input type="number" name="questions[0][marks]" min="0.5" max="100" step="0.5" value="2">
                                </div>
                            </div>
                            
                            <div class="question-options" id="options-0">
                                <label>Options:</label>
                                <div class="options-list">
                                    <input type="text" name="questions[0][options][]" placeholder="Option A">
                                    <input type="text" name="questions[0][options][]" placeholder="Option B">
                                    <input type="text" name="questions[0][options][]" placeholder="Option C">
                                    <input type="text" name="questions[0][options][]" placeholder="Option D">
                                </div>
                                <div class="form-group">
                                    <label>Correct Answer:</label>
                                    <input type="text" name="questions[0][correct_answer]" placeholder="Enter correct answer">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="addQuestion()">
                        Add Another Question
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Examination</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                </div>
            </form>
        </div>

        <!-- Attendance Tab -->
        <div id="attendance" class="tab-content">
            <h2>Exam Attendance Management</h2>
            
            <div class="attendance-controls">
                <select id="attendance-exam-select" onchange="loadExamAttendance()">
                    <option value="">Select an exam...</option>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?= $exam['id'] ?>">
                        <?= htmlspecialchars($exam['exam_title']) ?> - 
                        <?= date('M j, Y', strtotime($exam['exam_date'])) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="attendance-form-container" style="display: none;">
                <form method="POST" id="attendance-form">
                    <input type="hidden" name="action" value="record_attendance">
                    <input type="hidden" name="exam_id" id="attendance-exam-id">
                    
                    <div class="attendance-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-tbody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                        <button type="button" class="btn btn-secondary" onclick="markAllPresent()">Mark All Present</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <h2>Exam Analytics</h2>
            
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Exam Statistics</h3>
                    <div class="stat-list">
                        <div class="stat-item">
                            <span class="stat-label">Total Exams Created</span>
                            <span class="stat-value"><?= count($exams) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Scheduled Exams</span>
                            <span class="stat-value">
                                <?= count(array_filter($exams, function($e) { return $e['status'] === 'scheduled'; })) ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Completed Exams</span>
                            <span class="stat-value">
                                <?= count(array_filter($exams, function($e) { return $e['status'] === 'completed'; })) ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Average Attendance</span>
                            <span class="stat-value">
                                <?php 
                                $total_registered = array_sum(array_column($exams, 'registered_students'));
                                $total_present = array_sum(array_column($exams, 'present_students'));
                                echo $total_registered > 0 ? round(($total_present / $total_registered) * 100) : 0;
                                ?>%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Upcoming Exams</h3>
                    <div class="upcoming-exams">
                        <?php 
                        $upcoming = array_filter($exams, function($e) {
                            return $e['status'] === 'scheduled' && strtotime($e['exam_date']) >= time();
                        });
                        ?>
                        <?php if (empty($upcoming)): ?>
                        <p class="no-data">No upcoming exams scheduled.</p>
                        <?php else: ?>
                        <?php foreach (array_slice($upcoming, 0, 5) as $exam): ?>
                        <div class="upcoming-exam-item">
                            <div class="exam-info">
                                <strong><?= htmlspecialchars($exam['exam_title']) ?></strong>
                                <span><?= date('M j, Y g:i A', strtotime($exam['exam_date'] . ' ' . $exam['start_time'])) ?></span>
                            </div>
                            <div class="exam-countdown" data-exam-date="<?= $exam['exam_date'] ?>" data-exam-time="<?= $exam['start_time'] ?>">
                                <!-- Countdown will be populated by JavaScript -->
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>Performance Trends</h3>
                    <div class="performance-chart">
                        <canvas id="examPerformanceChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.alert-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.exam-tabs {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 1em;
    color: #6c757d;
    transition: all 0.2s;
}

.tab-button.active {
    background: white;
    color: #3498db;
    border-bottom: 3px solid #3498db;
}

.tab-button:hover {
    background: #e9ecef;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.exam-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.exam-controls h2 {
    color: #2c3e50;
    margin: 0;
}

.exam-controls select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
}

.exams-grid {
    display

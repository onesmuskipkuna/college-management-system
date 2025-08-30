<?php
/**
 * Student Assignment Portal
 * View assignments, submit work, and track progress
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Get student information
try {
    $student = fetchOne("SELECT s.*, c.course_name FROM students s JOIN courses c ON s.course_id = c.id WHERE s.user_id = ?", [$user['id']]);
    if (!$student) {
        throw new Exception("Student profile not found");
    }
} catch (Exception $e) {
    $student = ['id' => 1, 'student_id' => 'ST001', 'first_name' => 'Demo', 'last_name' => 'Student', 'course_name' => 'Computer Science'];
}

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment') {
    try {
        $assignment_id = (int)$_POST['assignment_id'];
        
        // Check if assignment exists and is active
        $assignment = fetchOne("SELECT * FROM assignments WHERE id = ? AND status = 'active'", [$assignment_id]);
        if (!$assignment) {
            throw new Exception("Assignment not found or no longer active");
        }
        
        // Check if due date has passed
        if (strtotime($assignment['due_date']) < time()) {
            throw new Exception("Assignment submission deadline has passed");
        }
        
        $submission_data = [
            'assignment_id' => $assignment_id,
            'student_id' => $student['id'],
            'submission_text' => sanitizeInput($_POST['submission_text']),
            'submitted_at' => date('Y-m-d H:i:s'),
            'status' => 'submitted'
        ];
        
        // Handle file upload
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/submissions/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION);
            $filename = 'submission_' . $assignment_id . '_' . $student['id'] . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $file_path)) {
                $submission_data['file_path'] = 'uploads/submissions/' . $filename;
            }
        }
        
        // Check if already submitted (update or insert)
        $existing = fetchOne("SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?", [$assignment_id, $student['id']]);
        
        if ($existing) {
            updateRecord('assignment_submissions', $submission_data, 'id', $existing['id']);
            $message = "Assignment has been successfully resubmitted.";
        } else {
            insertRecord('assignment_submissions', $submission_data);
            $message = "Assignment has been successfully submitted.";
        }
        
        if (function_exists('logActivity')) {
            logActivity($user['id'], 'assignment_submitted', "Assignment ID {$assignment_id} submitted");
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get student's assignments
try {
    $assignments = fetchAll("
        SELECT a.*, s.subject_name, c.course_name,
               sub.id as submission_id, sub.submission_text, sub.file_path as submission_file,
               sub.submitted_at, sub.grade, sub.feedback, sub.status as submission_status
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id AND sub.student_id = ?
        WHERE c.id = ? AND a.status = 'active'
        ORDER BY a.due_date ASC
    ", [$student['id'], $student['course_id'] ?? 1]);
} catch (Exception $e) {
    // Demo data for assignments
    $assignments = [
        [
            'id' => 1,
            'title' => 'Database Design Project',
            'description' => 'Design a complete database schema for a library management system. Include ER diagrams, normalization, and SQL scripts.',
            'subject_name' => 'Database Systems',
            'course_name' => 'Computer Science',
            'due_date' => '2024-02-15 23:59:00',
            'max_marks' => 100,
            'file_path' => 'uploads/assignments/assignment_1.pdf',
            'submission_id' => 1,
            'submitted_at' => '2024-01-14 22:30:00',
            'grade' => 85,
            'feedback' => 'Good work on the ER diagram. Consider adding more constraints.',
            'submission_status' => 'graded',
            'created_at' => '2024-01-15 10:00:00'
        ],
        [
            'id' => 2,
            'title' => 'Programming Assignment 1',
            'description' => 'Create a simple calculator application using object-oriented programming principles.',
            'subject_name' => 'Computer Programming',
            'course_name' => 'Computer Science',
            'due_date' => '2024-02-20 23:59:00',
            'max_marks' => 50,
            'file_path' => null,
            'submission_id' => null,
            'submitted_at' => null,
            'grade' => null,
            'feedback' => null,
            'submission_status' => null,
            'created_at' => '2024-01-18 09:00:00'
        ],
        [
            'id' => 3,
            'title' => 'Research Paper: AI in Education',
            'description' => 'Write a 2000-word research paper on the impact of artificial intelligence in modern education.',
            'subject_name' => 'Computer Science Research',
            'course_name' => 'Computer Science',
            'due_date' => '2024-02-25 23:59:00',
            'max_marks' => 75,
            'file_path' => null,
            'submission_id' => 2,
            'submitted_at' => '2024-01-20 15:45:00',
            'grade' => null,
            'feedback' => null,
            'submission_status' => 'submitted',
            'created_at' => '2024-01-16 14:00:00'
        ]
    ];
}

// Calculate assignment statistics
$total_assignments = count($assignments);
$submitted_assignments = count(array_filter($assignments, function($a) { return $a['submission_id']; }));
$graded_assignments = count(array_filter($assignments, function($a) { return $a['grade'] !== null; }));
$pending_assignments = $total_assignments - $submitted_assignments;
?>

<div class="container">
    <div class="page-header">
        <h1>My Assignments</h1>
        <p>View assignments, submit work, and track your progress</p>
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

    <!-- Assignment Statistics -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_assignments ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="stat-card submitted">
                <div class="stat-number"><?= $submitted_assignments ?></div>
                <div class="stat-label">Submitted</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?= $pending_assignments ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card graded">
                <div class="stat-number"><?= $graded_assignments ?></div>
                <div class="stat-label">Graded</div>
            </div>
        </div>
    </div>

    <!-- Assignment Filters -->
    <div class="filters-section">
        <div class="filter-buttons">
            <button class="filter-btn active" onclick="filterAssignments('all')">All</button>
            <button class="filter-btn" onclick="filterAssignments('pending')">Pending</button>
            <button class="filter-btn" onclick="filterAssignments('submitted')">Submitted</button>
            <button class="filter-btn" onclick="filterAssignments('graded')">Graded</button>
            <button class="filter-btn" onclick="filterAssignments('overdue')">Overdue</button>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="assignments-section">
        <?php if (empty($assignments)): ?>
        <div class="no-data">No assignments available at the moment.</div>
        <?php else: ?>
        <?php foreach ($assignments as $assignment): ?>
        <?php 
        $is_overdue = strtotime($assignment['due_date']) < time() && !$assignment['submission_id'];
        $is_submitted = $assignment['submission_id'] !== null;
        $is_graded = $assignment['grade'] !== null;
        $is_pending = !$is_submitted && !$is_overdue;
        
        $status_class = '';
        if ($is_graded) $status_class = 'graded';
        elseif ($is_submitted) $status_class = 'submitted';
        elseif ($is_overdue) $status_class = 'overdue';
        else $status_class = 'pending';
        ?>
        
        <div class="assignment-card <?= $status_class ?>" data-status="<?= $status_class ?>">
            <div class="assignment-header">
                <div class="assignment-title">
                    <h3><?= htmlspecialchars($assignment['title']) ?></h3>
                    <span class="subject-tag"><?= htmlspecialchars($assignment['subject_name']) ?></span>
                </div>
                <div class="assignment-status">
                    <?php if ($is_graded): ?>
                    <span class="status-badge graded">Graded</span>
                    <div class="grade-display"><?= $assignment['grade'] ?>/<?= $assignment['max_marks'] ?></div>
                    <?php elseif ($is_submitted): ?>
                    <span class="status-badge submitted">Submitted</span>
                    <?php elseif ($is_overdue): ?>
                    <span class="status-badge overdue">Overdue</span>
                    <?php else: ?>
                    <span class="status-badge pending">Pending</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="assignment-details">
                <p class="assignment-description"><?= htmlspecialchars($assignment['description']) ?></p>
                
                <div class="assignment-meta">
                    <div class="meta-item">
                        <span class="meta-label">Due Date:</span>
                        <span class="meta-value <?= $is_overdue ? 'overdue-text' : '' ?>">
                            <?= date('M j, Y g:i A', strtotime($assignment['due_date'])) ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Max Marks:</span>
                        <span class="meta-value"><?= $assignment['max_marks'] ?> points</span>
                    </div>
                    <?php if ($assignment['file_path']): ?>
                    <div class="meta-item">
                        <span class="meta-label">Resources:</span>
                        <a href="<?= htmlspecialchars($assignment['file_path']) ?>" class="download-link" target="_blank">
                            Download Assignment File
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_submitted): ?>
                <div class="submission-info">
                    <h4>Your Submission</h4>
                    <div class="submission-details">
                        <p><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($assignment['submitted_at'])) ?></p>
                        <?php if ($assignment['submission_text']): ?>
                        <p><strong>Text:</strong> <?= htmlspecialchars($assignment['submission_text']) ?></p>
                        <?php endif; ?>
                        <?php if ($assignment['submission_file']): ?>
                        <p><strong>File:</strong> 
                            <a href="<?= htmlspecialchars($assignment['submission_file']) ?>" target="_blank">
                                View Submitted File
                            </a>
                        </p>
                        <?php endif; ?>
                        <?php if ($assignment['feedback']): ?>
                        <div class="feedback-section">
                            <p><strong>Teacher Feedback:</strong></p>
                            <div class="feedback-text"><?= htmlspecialchars($assignment['feedback']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="assignment-actions">
                <?php if (!$is_submitted && !$is_overdue): ?>
                <button class="btn btn-primary" onclick="openSubmissionModal(<?= $assignment['id'] ?>)">
                    Submit Assignment
                </button>
                <?php elseif ($is_submitted && !$is_graded && strtotime($assignment['due_date']) > time()): ?>
                <button class="btn btn-warning" onclick="openSubmissionModal(<?= $assignment['id'] ?>)">
                    Resubmit Assignment
                </button>
                <?php endif; ?>
                
                <?php if ($assignment['file_path']): ?>
                <button class="btn btn-secondary" onclick="downloadAssignment(<?= $assignment['id'] ?>)">
                    Download Resources
                </button>
                <?php endif; ?>
                
                <?php if ($is_submitted): ?>
                <button class="btn btn-info" onclick="viewSubmission(<?= $assignment['id'] ?>)">
                    View Submission
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Assignment Submission Modal -->
<div id="submission-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Submit Assignment</h3>
            <span class="close" onclick="closeSubmissionModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="submission-form">
                <input type="hidden" name="action" value="submit_assignment">
                <input type="hidden" name="assignment_id" id="modal-assignment-id">
                
                <div class="form-group">
                    <label for="submission_text">Submission Text:</label>
                    <textarea name="submission_text" id="submission_text" rows="6" 
                              placeholder="Enter your submission text, notes, or comments here..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="submission_file">Upload File:</label>
                    <input type="file" name="submission_file" id="submission_file" 
                           accept=".pdf,.doc,.docx,.txt,.zip,.jpg,.png">
                    <small>Accepted formats: PDF, DOC, DOCX, TXT, ZIP, JPG, PNG (Max 10MB)</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Assignment</button>
                    <button type="button" class="btn btn-secondary" onclick="closeSubmissionModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.container {
    max-width: 1200px;
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

.stats-section {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card.submitted {
    border-left-color: #27ae60;
}

.stat-card.pending {
    border-left-color: #f39c12;
}

.stat-card.graded {
    border-left-color: #9b59b6;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
}

.filters-section {
    margin-bottom: 30px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 10px 20px;
    border: 2px solid #3498db;
    background: white;
    color: #3498db;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-btn.active, .filter-btn:hover {
    background: #3498db;
    color: white;
}

.assignments-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.assignment-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.assignment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.assignment-card.pending {
    border-left: 4px solid #f39c12;
}

.assignment-card.submitted {
    border-left: 4px solid #3498db;
}

.assignment-card.graded {
    border-left: 4px solid #27ae60;
}

.assignment-card.overdue {
    border-left: 4px solid #e74c3c;
}

.assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px 20px 0 20px;
}

.assignment-title h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.subject-tag {
    background: #ecf0f1;
    color: #2c3e50;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.assignment-status {
    text-align: right;
}

.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: inline-block;
}

.status-badge.pending {
    background-color: #fdebd0;
    color: #f39c12;
}

.status-badge.submitted {
    background-color: #d6eaf8;
    color: #3498db;
}

.status-badge.graded {
    background-color: #d5f4e6;
    color: #27ae60;
}

.status-badge.overdue {
    background-color: #fdeaea;
    color: #e74c3c;
}

.grade-display {
    font-size: 1.2em;
    font-weight: bold;
    color: #27ae60;
}

.assignment-details {
    padding: 0 20px 20px 20px;
}

.assignment-description {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 15px;
}

.assignment-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.meta-item {
    display: flex;
    flex-direction: column;
}

.meta-label {
    font-size: 0.9em;
    color: #7f8c8d;
    font-weight: bold;
}

.meta-value {
    color: #2c3e50;
    font-weight: bold;
}

.meta-value.overdue-text {
    color: #e74c3c;
}

.download-link {
    color: #3498db;
    text-decoration: none;
    font-weight: bold;
}

.download-link:hover {
    text-decoration: underline;
}

.submission-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}

.submission-info h4 {
    margin: 0 0 10px 0;
    color: #2c3e50;
}

.submission-details p {
    margin: 5px 0;
    color: #6c757d;
}

.feedback-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #dee2e6;
}

.feedback-text {
    background: white;
    padding: 10px;
    border-radius: 5px;
    border-left: 4px solid #3498db;
    color: #2c3e50;
}

.assignment-actions {
    padding: 0 20px 20px 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

.btn-warning {
    background-color: #f39c12;
    color: white;
}

.btn-warning:hover {
    background-color: #e67e22;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
}

.no-data {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #2c3e50;
    font-weight: bold;
}

.form-group input, .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
}

.form-group small {
    color: #6c757d;
    font-size: 0.9em;
    margin-top: 5px;
    display: block;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .assignment-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .assignment-meta {
        grid-template-columns: 1fr;
    }
    
    .assignment-actions {
        flex-direction: column;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function filterAssignments(status) {
    const cards = document.querySelectorAll('.assignment-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter cards
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function openSubmissionModal(assignmentId) {
    document.getElementById('modal-assignment-id').value = assignmentId;
    document.getElementById('submission-modal').style.display = 'block';
}

function closeSubmissionModal() {
    document.getElementById('submission-modal').style.display = 'none';
    document.getElementById('submission-form').reset();
}

function downloadAssignment(assignmentId) {
    // In a real implementation, this would download the assignment file
    alert('Download assignment functionality - Assignment ID: ' + assignmentId);
}

function viewSubmission(assignmentId) {
    // In a real implementation, this would show submission details
    alert('View submission functionality - Assignment ID: ' + assignmentId);
}

// File upload validation
document.getElementById('submission_file').addEventListener('change', function() {
    const file = this.files[0];
    if (file && file.size > 10 * 1024 * 1024) { // 10MB limit
        alert('File size must be less than 10MB');
        this.value = '';
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('submission-modal');
    if (event.target === modal) {
        closeSubmissionModal();
    }
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

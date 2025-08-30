<?php
/**
 * Teacher Assignment Management
 * Create, manage and track assignments for classes
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require teacher role
Authentication::requireRole('teacher');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Get teacher information
try {
    $teacher = fetchOne("SELECT * FROM teachers WHERE user_id = ?", [$user['id']]);
    if (!$teacher) {
        throw new Exception("Teacher profile not found");
    }
} catch (Exception $e) {
    $teacher = ['id' => 1, 'teacher_id' => 'T001', 'first_name' => 'Demo', 'last_name' => 'Teacher'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_assignment':
                    $assignment_data = [
                        'teacher_id' => $teacher['id'],
                        'subject_id' => (int)$_POST['subject_id'],
                        'title' => sanitizeInput($_POST['title']),
                        'description' => sanitizeInput($_POST['description']),
                        'due_date' => sanitizeInput($_POST['due_date']) . ' ' . sanitizeInput($_POST['due_time']),
                        'max_marks' => (float)$_POST['max_marks'],
                        'status' => 'active'
                    ];
                    
                    // Handle file upload
                    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = __DIR__ . '/../uploads/assignments/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
                        $filename = 'assignment_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $file_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $file_path)) {
                            $assignment_data['file_path'] = 'uploads/assignments/' . $filename;
                        }
                    }
                    
                    $assignment_id = insertRecord('assignments', $assignment_data);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'assignment_created', "Assignment '{$assignment_data['title']}' created");
                    }
                    
                    $message = "Assignment '{$assignment_data['title']}' has been successfully created.";
                    break;
                    
                case 'update_assignment':
                    $assignment_id = (int)$_POST['assignment_id'];
                    $assignment_data = [
                        'title' => sanitizeInput($_POST['title']),
                        'description' => sanitizeInput($_POST['description']),
                        'due_date' => sanitizeInput($_POST['due_date']) . ' ' . sanitizeInput($_POST['due_time']),
                        'max_marks' => (float)$_POST['max_marks'],
                        'status' => sanitizeInput($_POST['status'])
                    ];
                    
                    updateRecord('assignments', $assignment_data, 'id', $assignment_id);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'assignment_updated', "Assignment ID {$assignment_id} updated");
                    }
                    
                    $message = "Assignment has been successfully updated.";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get teacher's subjects
try {
    $subjects = fetchAll("
        SELECT s.*, c.course_name 
        FROM subjects s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.status = 'active'
        ORDER BY c.course_name, s.subject_name
    ");
} catch (Exception $e) {
    $subjects = [
        ['id' => 1, 'subject_name' => 'Computer Programming', 'course_name' => 'Computer Science'],
        ['id' => 2, 'subject_name' => 'Database Systems', 'course_name' => 'Computer Science'],
        ['id' => 3, 'subject_name' => 'Business Management', 'course_name' => 'Business Management']
    ];
}

// Get teacher's assignments
try {
    $assignments = fetchAll("
        SELECT a.*, s.subject_name, c.course_name,
               COUNT(DISTINCT st.id) as total_students,
               COUNT(DISTINCT sub.id) as submissions_count
        FROM assignments a
        JOIN subjects s ON a.subject_id = s.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN students st ON c.id = st.course_id AND st.status = 'active'
        LEFT JOIN assignment_submissions sub ON a.id = sub.assignment_id
        WHERE a.teacher_id = ?
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ", [$teacher['id']]);
} catch (Exception $e) {
    // Demo data for assignments
    $assignments = [
        [
            'id' => 1,
            'title' => 'Database Design Project',
            'subject_name' => 'Database Systems',
            'course_name' => 'Computer Science',
            'due_date' => '2024-02-15 23:59:00',
            'max_marks' => 100,
            'status' => 'active',
            'total_students' => 25,
            'submissions_count' => 18,
            'created_at' => '2024-01-15 10:00:00'
        ],
        [
            'id' => 2,
            'title' => 'Programming Assignment 1',
            'subject_name' => 'Computer Programming',
            'course_name' => 'Computer Science',
            'due_date' => '2024-02-10 23:59:00',
            'max_marks' => 50,
            'status' => 'active',
            'total_students' => 25,
            'submissions_count' => 22,
            'created_at' => '2024-01-10 09:00:00'
        ]
    ];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Assignment Management</h1>
        <p>Create and manage assignments for your classes</p>
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

    <!-- Assignment Management Tabs -->
    <div class="tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('assignments')">My Assignments</button>
            <button class="tab-button" onclick="showTab('create-assignment')">Create Assignment</button>
            <button class="tab-button" onclick="showTab('submissions')">Submissions</button>
        </div>

        <!-- Assignments List Tab -->
        <div id="assignments" class="tab-content active">
            <h2>My Assignments</h2>
            
            <div class="assignments-grid">
                <?php if (empty($assignments)): ?>
                <div class="no-data">No assignments created yet. Create your first assignment to get started.</div>
                <?php else: ?>
                <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-card">
                    <div class="assignment-header">
                        <h3><?= htmlspecialchars($assignment['title']) ?></h3>
                        <span class="status-badge <?= $assignment['status'] ?>">
                            <?= ucfirst($assignment['status']) ?>
                        </span>
                    </div>
                    
                    <div class="assignment-details">
                        <div class="detail-row">
                            <span class="label">Subject:</span>
                            <span class="value"><?= htmlspecialchars($assignment['subject_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Course:</span>
                            <span class="value"><?= htmlspecialchars($assignment['course_name']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Due Date:</span>
                            <span class="value"><?= date('M j, Y g:i A', strtotime($assignment['due_date'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Max Marks:</span>
                            <span class="value"><?= $assignment['max_marks'] ?> points</span>
                        </div>
                    </div>
                    
                    <div class="assignment-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?= $assignment['total_students'] ?></span>
                            <span class="stat-label">Students</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?= $assignment['submissions_count'] ?></span>
                            <span class="stat-label">Submissions</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">
                                <?= $assignment['total_students'] > 0 ? round(($assignment['submissions_count'] / $assignment['total_students']) * 100) : 0 ?>%
                            </span>
                            <span class="stat-label">Completion</span>
                        </div>
                    </div>
                    
                    <div class="assignment-actions">
                        <button class="btn btn-primary btn-sm" onclick="viewSubmissions(<?= $assignment['id'] ?>)">
                            View Submissions
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="editAssignment(<?= $assignment['id'] ?>)">
                            Edit
                        </button>
                        <button class="btn btn-info btn-sm" onclick="downloadAssignment(<?= $assignment['id'] ?>)">
                            Download
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create Assignment Tab -->
        <div id="create-assignment" class="tab-content">
            <h2>Create New Assignment</h2>
            
            <form method="POST" enctype="multipart/form-data" class="assignment-form">
                <input type="hidden" name="action" value="create_assignment">
                
                <div class="form-section">
                    <h3>Assignment Details</h3>
                    
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
                            <label for="max_marks">Maximum Marks:</label>
                            <input type="number" name="max_marks" id="max_marks" required 
                                   min="1" max="1000" value="100" step="0.5">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Assignment Title:</label>
                        <input type="text" name="title" id="title" required 
                               placeholder="e.g., Database Design Project" maxlength="200">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" rows="4" required
                                  placeholder="Provide detailed instructions for the assignment..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="due_date">Due Date:</label>
                            <input type="date" name="due_date" id="due_date" required 
                                   min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="due_time">Due Time:</label>
                            <input type="time" name="due_time" id="due_time" required value="23:59">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_file">Assignment File (Optional):</label>
                        <input type="file" name="assignment_file" id="assignment_file" 
                               accept=".pdf,.doc,.docx,.txt,.zip">
                        <small>Upload assignment instructions, templates, or resources (Max 10MB)</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                </div>
            </form>
        </div>

        <!-- Submissions Tab -->
        <div id="submissions" class="tab-content">
            <h2>Assignment Submissions</h2>
            
            <div class="submissions-filter">
                <select id="assignment-filter" onchange="filterSubmissions()">
                    <option value="">All Assignments</option>
                    <?php foreach ($assignments as $assignment): ?>
                    <option value="<?= $assignment['id'] ?>">
                        <?= htmlspecialchars($assignment['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="submissions-table">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Assignment</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="submissions-tbody">
                        <!-- Demo submissions data -->
                        <tr>
                            <td>
                                <div class="student-info">
                                    <strong>John Smith</strong>
                                    <small>ST001</small>
                                </div>
                            </td>
                            <td>Database Design Project</td>
                            <td>Jan 14, 2024 11:30 PM</td>
                            <td><span class="status-badge submitted">Submitted</span></td>
                            <td>
                                <input type="number" class="grade-input" placeholder="Grade" min="0" max="100">
                            </td>
                            <td class="actions">
                                <button class="btn btn-sm btn-primary">View</button>
                                <button class="btn btn-sm btn-success">Grade</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <strong>Mary Johnson</strong>
                                    <small>ST002</small>
                                </div>
                            </td>
                            <td>Programming Assignment 1</td>
                            <td>Jan 10, 2024 10:15 PM</td>
                            <td><span class="status-badge graded">Graded</span></td>
                            <td><strong>85/50</strong></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-primary">View</button>
                                <button class="btn btn-sm btn-secondary">Edit Grade</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assignment Statistics -->
    <div class="stats-section">
        <h2>Assignment Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($assignments) ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count(array_filter($assignments, function($a) { return $a['status'] === 'active'; })) ?>
                </div>
                <div class="stat-label">Active Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= array_sum(array_column($assignments, 'submissions_count')) ?>
                </div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $total_students = array_sum(array_column($assignments, 'total_students'));
                    $total_submissions = array_sum(array_column($assignments, 'submissions_count'));
                    echo $total_students > 0 ? round(($total_submissions / $total_students) * 100) : 0;
                    ?>%
                </div>
                <div class="stat-label">Average Completion</div>
            </div>
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

.tabs {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.tab-buttons {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 15px 20px;
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
    border-bottom: 2px solid #3498db;
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

.assignments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.assignment-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    border-left: 4px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.assignment-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.assignment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.assignment-header h3 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.active {
    background-color: #d5f4e6;
    color: #27ae60;
}

.status-badge.completed {
    background-color: #d6eaf8;
    color: #3498db;
}

.status-badge.submitted {
    background-color: #fdebd0;
    color: #f39c12;
}

.status-badge.graded {
    background-color: #d5f4e6;
    color: #27ae60;
}

.assignment-details {
    margin-bottom: 15px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
}

.detail-row .label {
    color: #6c757d;
    font-weight: bold;
}

.detail-row .value {
    color: #2c3e50;
}

.assignment-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 15px;
    padding: 15px;
    background: white;
    border-radius: 8px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
    color: #3498db;
}

.stat-label {
    font-size: 0.9em;
    color: #6c757d;
}

.assignment-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.assignment-form {
    max-width: 800px;
}

.form-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-section h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    font-weight: bold;
    color: #2c3e50;
}

.form-group input, .form-group textarea, .form-group select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
}

.form-group small {
    margin-top: 5px;
    color: #6c757d;
    font-size: 0.9em;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
    transition: background-color 0.2s;
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

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
}

.btn-success {
    background-color: #27ae60;
    color: white;
}

.btn-success:hover {
    background-color: #229954;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.9em;
}

.submissions-filter {
    margin-bottom: 20px;
}

.submissions-filter select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
    min-width: 200px;
}

.submissions-table {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
}

th {
    background-color: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.student-info strong {
    display: block;
    color: #2c3e50;
}

.student-info small {
    color: #7f8c8d;
}

.grade-input {
    width: 80px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.actions {
    display: flex;
    gap: 5px;
}

.no-data {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 10px;
}

.stats-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.stats-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card .stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
}

@media (max-width: 768px) {
    .assignments-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .assignment-stats {
        flex-direction: column;
        gap: 10px;
    }
    
    .assignment-actions {
        justify-content: center;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .tab-buttons {
        flex-direction: column;
    }
}
</style>

<script>
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    // Remove active class from all buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}

function viewSubmissions(assignmentId) {
    // Switch to submissions tab and filter by assignment
    showTab('submissions');
    document.getElementById('assignment-filter').value = assignmentId;
    filterSubmissions();
}

function editAssignment(assignmentId) {
    // In a real implementation, this would populate the edit form
    alert('Edit assignment functionality - Assignment ID: ' + assignmentId);
}

function downloadAssignment(assignmentId) {
    // In a real implementation, this would download the assignment file
    alert('Download assignment functionality - Assignment ID: ' + assignmentId);
}

function filterSubmissions() {
    const filter = document.getElementById('assignment-filter').value;
    const rows = document.querySelectorAll('#submissions-tbody tr');
    
    rows.forEach(row => {
        if (!filter || row.dataset.assignmentId === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Set minimum due date to today
document.getElementById('due_date').min = new Date().toISOString().split('T')[0];

// File upload validation
document.getElementById('assignment_file').addEventListener('change', function() {
    const file = this.files[0];
    if (file && file.size > 10 * 1024 * 1024) { // 10MB limit
        alert('File size must be less than 10MB');
        this.value = '';
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

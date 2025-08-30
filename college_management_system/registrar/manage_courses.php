<?php
/**
 * Course Management System
 * Add, edit, and manage academic programs and fee structures
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_course':
                    $course_data = [
                        'course_code' => strtoupper(sanitizeInput($_POST['course_code'])),
                        'course_name' => sanitizeInput($_POST['course_name']),
                        'description' => sanitizeInput($_POST['description']),
                        'duration_months' => (int)$_POST['duration_months'],
                        'status' => 'active'
                    ];
                    
                    // Check if course code already exists
                    if (recordExists('courses', 'course_code', $course_data['course_code'])) {
                        throw new Exception("Course code already exists");
                    }
                    
                    $course_id = insertRecord('courses', $course_data);
                    
                    // Add fee structures if provided
                    if (!empty($_POST['fee_types'])) {
                        foreach ($_POST['fee_types'] as $index => $fee_type) {
                            if (!empty($fee_type) && !empty($_POST['fee_amounts'][$index])) {
                                $fee_data = [
                                    'course_id' => $course_id,
                                    'fee_type' => sanitizeInput($fee_type),
                                    'amount' => (float)$_POST['fee_amounts'][$index],
                                    'is_mandatory' => isset($_POST['fee_mandatory'][$index]) ? 1 : 0,
                                    'description' => sanitizeInput($_POST['fee_descriptions'][$index] ?? ''),
                                    'status' => 'active'
                                ];
                                insertRecord('fee_structure', $fee_data);
                            }
                        }
                    }
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'course_added', "Course {$course_data['course_code']} - {$course_data['course_name']} added");
                    }
                    
                    $message = "Course '{$course_data['course_name']}' has been successfully added.";
                    break;
                    
                case 'update_course':
                    $course_id = (int)$_POST['course_id'];
                    $course_data = [
                        'course_name' => sanitizeInput($_POST['course_name']),
                        'description' => sanitizeInput($_POST['description']),
                        'duration_months' => (int)$_POST['duration_months'],
                        'status' => sanitizeInput($_POST['status'])
                    ];
                    
                    updateRecord('courses', $course_data, 'id', $course_id);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'course_updated', "Course ID {$course_id} updated");
                    }
                    
                    $message = "Course has been successfully updated.";
                    break;
                    
                case 'add_fee_structure':
                    $fee_data = [
                        'course_id' => (int)$_POST['course_id'],
                        'fee_type' => sanitizeInput($_POST['fee_type']),
                        'amount' => (float)$_POST['amount'],
                        'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
                        'description' => sanitizeInput($_POST['description']),
                        'status' => 'active'
                    ];
                    
                    insertRecord('fee_structure', $fee_data);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'fee_structure_added', "Fee structure added for course ID {$fee_data['course_id']}");
                    }
                    
                    $message = "Fee structure has been successfully added.";
                    break;
                    
                case 'update_fee_structure':
                    $fee_id = (int)$_POST['fee_id'];
                    $fee_data = [
                        'fee_type' => sanitizeInput($_POST['fee_type']),
                        'amount' => (float)$_POST['amount'],
                        'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
                        'description' => sanitizeInput($_POST['description']),
                        'status' => sanitizeInput($_POST['status'])
                    ];
                    
                    updateRecord('fee_structure', $fee_data, 'id', $fee_id);
                    
                    if (function_exists('logActivity')) {
                        logActivity($user['id'], 'fee_structure_updated', "Fee structure ID {$fee_id} updated");
                    }
                    
                    $message = "Fee structure has been successfully updated.";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all courses with fee structures
try {
    $courses = fetchAll("
        SELECT c.*, 
               COUNT(s.id) as student_count,
               COUNT(fs.id) as fee_structure_count,
               SUM(fs.amount) as total_fees
        FROM courses c 
        LEFT JOIN students s ON c.id = s.course_id AND s.status = 'active'
        LEFT JOIN fee_structure fs ON c.id = fs.course_id AND fs.status = 'active'
        GROUP BY c.id 
        ORDER BY c.course_name
    ");
} catch (Exception $e) {
    $courses = [];
}

// Get fee structures for display
try {
    $fee_structures = fetchAll("
        SELECT fs.*, c.course_name, c.course_code
        FROM fee_structure fs
        JOIN courses c ON fs.course_id = c.id
        WHERE fs.status = 'active'
        ORDER BY c.course_name, fs.fee_type
    ");
} catch (Exception $e) {
    $fee_structures = [];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Course Management</h1>
        <p>Manage academic programs and fee structures</p>
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

    <!-- Course Management Tabs -->
    <div class="tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('courses')">Courses</button>
            <button class="tab-button" onclick="showTab('add-course')">Add Course</button>
            <button class="tab-button" onclick="showTab('fee-structures')">Fee Structures</button>
        </div>

        <!-- Courses Tab -->
        <div id="courses" class="tab-content active">
            <h2>Existing Courses</h2>
            <div class="courses-grid">
                <?php if (empty($courses)): ?>
                <div class="no-data">No courses found. Add your first course to get started.</div>
                <?php else: ?>
                <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <div class="course-header">
                        <h3><?= htmlspecialchars($course['course_name']) ?></h3>
                        <span class="course-code"><?= htmlspecialchars($course['course_code']) ?></span>
                    </div>
                    
                    <div class="course-details">
                        <p class="course-description"><?= htmlspecialchars($course['description']) ?></p>
                        
                        <div class="course-stats">
                            <div class="stat-item">
                                <span class="stat-label">Duration:</span>
                                <span class="stat-value"><?= $course['duration_months'] ?> months</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Students:</span>
                                <span class="stat-value"><?= $course['student_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Fee Components:</span>
                                <span class="stat-value"><?= $course['fee_structure_count'] ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Fees:</span>
                                <span class="stat-value">KSh <?= number_format($course['total_fees'] ?? 0) ?></span>
                            </div>
                        </div>
                        
                        <div class="course-status">
                            <span class="status-badge <?= $course['status'] ?>">
                                <?= ucfirst($course['status']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="course-actions">
                        <button class="btn btn-sm btn-primary" onclick="editCourse(<?= $course['id'] ?>)">
                            Edit Course
                        </button>
                        <button class="btn btn-sm btn-secondary" onclick="manageFees(<?= $course['id'] ?>)">
                            Manage Fees
                        </button>
                        <button class="btn btn-sm btn-info" onclick="viewStudents(<?= $course['id'] ?>)">
                            View Students
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add Course Tab -->
        <div id="add-course" class="tab-content">
            <h2>Add New Course</h2>
            <form method="POST" class="course-form">
                <input type="hidden" name="action" value="add_course">
                
                <div class="form-section">
                    <h3>Course Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="course_code">Course Code:</label>
                            <input type="text" name="course_code" id="course_code" required 
                                   placeholder="e.g., CS101" maxlength="20">
                            <small>Unique identifier for the course</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_months">Duration (Months):</label>
                            <input type="number" name="duration_months" id="duration_months" required 
                                   min="1" max="60" value="12">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_name">Course Name:</label>
                        <input type="text" name="course_name" id="course_name" required 
                               placeholder="e.g., Computer Science Diploma" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" rows="3" 
                                  placeholder="Brief description of the course content and objectives"></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Fee Structure</h3>
                    <p>Define the fee components for this course:</p>
                    
                    <div id="fee-structures-container">
                        <div class="fee-structure-item">
                            <div class="fee-row">
                                <div class="form-group">
                                    <label>Fee Type:</label>
                                    <input type="text" name="fee_types[]" placeholder="e.g., Tuition Fee">
                                </div>
                                <div class="form-group">
                                    <label>Amount (KSh):</label>
                                    <input type="number" name="fee_amounts[]" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="form-group checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="fee_mandatory[]" value="1">
                                        Mandatory
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description:</label>
                                <input type="text" name="fee_descriptions[]" placeholder="Optional description">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="addFeeStructure()">
                        Add Another Fee Component
                    </button>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Course</button>
                    <button type="reset" class="btn btn-secondary">Reset Form</button>
                </div>
            </form>
        </div>

        <!-- Fee Structures Tab -->
        <div id="fee-structures" class="tab-content">
            <h2>Fee Structures Overview</h2>
            
            <div class="fee-structures-table">
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Mandatory</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fee_structures)): ?>
                        <tr>
                            <td colspan="6" class="no-data">No fee structures defined yet</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($fee_structures as $fee): ?>
                        <tr>
                            <td>
                                <div class="course-info">
                                    <strong><?= htmlspecialchars($fee['course_name']) ?></strong>
                                    <small><?= htmlspecialchars($fee['course_code']) ?></small>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($fee['fee_type']) ?></td>
                            <td class="amount">KSh <?= number_format($fee['amount'], 2) ?></td>
                            <td>
                                <span class="mandatory-badge <?= $fee['is_mandatory'] ? 'yes' : 'no' ?>">
                                    <?= $fee['is_mandatory'] ? 'Yes' : 'No' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($fee['description']) ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-primary" onclick="editFeeStructure(<?= $fee['id'] ?>)">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Course Statistics -->
    <div class="stats-section">
        <h2>Course Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($courses) ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count(array_filter($courses, function($c) { return $c['status'] === 'active'; })) ?>
                </div>
                <div class="stat-label">Active Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= array_sum(array_column($courses, 'student_count')) ?>
                </div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count($fee_structures) ?>
                </div>
                <div class="stat-label">Fee Components</div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="edit-course-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Course</h3>
            <span class="close" onclick="closeModal('edit-course-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="edit-course-form">
                <input type="hidden" name="action" value="update_course">
                <input type="hidden" name="course_id" id="edit-course-id">
                
                <div class="form-group">
                    <label for="edit-course-name">Course Name:</label>
                    <input type="text" name="course_name" id="edit-course-name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-description">Description:</label>
                    <textarea name="description" id="edit-description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-duration">Duration (Months):</label>
                        <input type="number" name="duration_months" id="edit-duration" required min="1" max="60">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-status">Status:</label>
                        <select name="status" id="edit-status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Course</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('edit-course-modal')">Cancel</button>
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

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.course-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    border-left: 4px solid #3498db;
    transition: transform 0.2s, box-shadow 0.2s;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.course-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.course-header h3 {
    margin: 0;
    color: #2c3e50;
    flex: 1;
}

.course-code {
    background: #3498db;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
}

.course-description {
    color: #6c757d;
    margin-bottom: 15px;
    line-height: 1.4;
}

.course-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 15px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9em;
}

.stat-value {
    font-weight: bold;
    color: #2c3e50;
}

.course-status {
    margin-bottom: 15px;
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

.status-badge.inactive {
    background-color: #fdeaea;
    color: #e74c3c;
}

.course-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.course-form {
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

.checkbox-group {
    justify-content: center;
    align-items: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #2c3e50;
    font-weight: bold;
}

.fee-structure-item {
    background: white;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 15px;
    border: 1px solid #dee2e6;
}

.fee-row {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 15px;
    margin-bottom: 10px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 24px;
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

.btn-sm {
    padding: 6px 12px;
    font-size: 0.9em;
}

.fee-structures-table {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
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

.course-info strong {
    display: block;
    color: #2c3e50;
}

.course-info small {
    color: #7f8c8d;
}

.amount {
    font-weight: bold;
    color: #27ae60;
}

.mandatory-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.mandatory-badge.yes {
    background-color: #d5f4e6;
    color: #27ae60;
}

.mandatory-badge.no {
    background-color: #fdebd0;
    color: #f39c12;
}

.actions {
    display: flex;
    gap: 5px;
}

.no-data {
    text-align: center;
    color: #7f8c8d;
    font-style: italic;
    padding: 30px;
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

@media (max-width: 768px) {
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row, .fee-row {
        grid-template-columns: 1fr;
    }
    
    .course-stats {
        grid-template-columns: 1fr;
    }
    
    .course-actions {
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

function

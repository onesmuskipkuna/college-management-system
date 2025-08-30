<?php
/**
 * Enhanced Student Management System
 * Comprehensive student information and records management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();

// Initialize variables
$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add_student':
                    $student_data = [
                        'student_id' => sanitizeInput($_POST['student_id']),
                        'first_name' => sanitizeInput($_POST['first_name']),
                        'last_name' => sanitizeInput($_POST['last_name']),
                        'email' => sanitizeInput($_POST['email']),
                        'phone' => sanitizeInput($_POST['phone']),
                        'id_number' => sanitizeInput($_POST['id_number']),
                        'id_type' => sanitizeInput($_POST['id_type']),
                        'date_of_birth' => sanitizeInput($_POST['date_of_birth']),
                        'gender' => sanitizeInput($_POST['gender']),
                        'course_id' => intval($_POST['course_id']),
                        'admission_date' => sanitizeInput($_POST['admission_date']),
                        'address' => sanitizeInput($_POST['address']),
                        'emergency_contact' => sanitizeInput($_POST['emergency_contact']),
                        'emergency_phone' => sanitizeInput($_POST['emergency_phone']),
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Check if student ID already exists
                    $existing = fetchOne("SELECT id FROM students WHERE student_id = ?", [$student_data['student_id']]);
                    if ($existing) {
                        throw new Exception('Student ID already exists');
                    }
                    
                    // Check if email already exists
                    $existing_email = fetchOne("SELECT id FROM students WHERE email = ?", [$student_data['email']]);
                    if ($existing_email) {
                        throw new Exception('Email already exists');
                    }
                    
                    beginTransaction();
                    
                    // Create user account
                    $user_data = [
                        'username' => $student_data['student_id'],
                        'email' => $student_data['email'],
                        'password' => password_hash($student_data['student_id'], PASSWORD_DEFAULT),
                        'role' => 'student',
                        'status' => 'active',
                        'first_name' => $student_data['first_name'],
                        'last_name' => $student_data['last_name'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    $user_id = insertRecord('users', $user_data);
                    $student_data['user_id'] = $user_id;
                    
                    // Insert student record
                    $student_id = insertRecord('students', $student_data);
                    
                    // Auto-assign course fees
                    $course_fees = fetchAll("SELECT * FROM fee_structure WHERE course_id = ? AND status = 'active'", [$student_data['course_id']]);
                    foreach ($course_fees as $fee) {
                        $student_fee_data = [
                            'student_id' => $student_id,
                            'fee_structure_id' => $fee['id'],
                            'amount_due' => $fee['amount'],
                            'amount_paid' => 0,
                            'balance' => $fee['amount'],
                            'due_date' => date('Y-m-d', strtotime('+30 days')),
                            'status' => 'pending',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        insertRecord('student_fees', $student_fee_data);
                    }
                    
                    commitTransaction();
                    $success_message = "Student added successfully! Student ID: " . $student_data['student_id'];
                    break;
                    
                case 'update_student':
                    $student_id = intval($_POST['student_id']);
                    $update_data = [
                        'first_name' => sanitizeInput($_POST['first_name']),
                        'last_name' => sanitizeInput($_POST['last_name']),
                        'email' => sanitizeInput($_POST['email']),
                        'phone' => sanitizeInput($_POST['phone']),
                        'address' => sanitizeInput($_POST['address']),
                        'emergency_contact' => sanitizeInput($_POST['emergency_contact']),
                        'emergency_phone' => sanitizeInput($_POST['emergency_phone']),
                        'status' => sanitizeInput($_POST['status']),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    updateRecord('students', $update_data, 'id', $student_id);
                    $success_message = "Student updated successfully!";
                    break;
                    
                case 'delete_student':
                    $student_id = intval($_POST['student_id']);
                    
                    // Soft delete - change status to inactive
                    updateRecord('students', ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')], 'id', $student_id);
                    $success_message = "Student deactivated successfully!";
                    break;
            }
        } catch (Exception $e) {
            rollbackTransaction();
            $error_message = $e->getMessage();
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$course_filter = $_GET['course_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? 'active';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if (!empty($course_filter)) {
    $where_conditions[] = "s.course_id = ?";
    $params[] = $course_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "s.status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fetch students with course information
$students = fetchAll("
    SELECT s.*, c.course_name, c.course_code,
           (SELECT COUNT(*) FROM student_fees sf WHERE sf.student_id = s.id AND sf.balance > 0) as outstanding_fees,
           (SELECT SUM(sf.balance) FROM student_fees sf WHERE sf.student_id = s.id AND sf.balance > 0) as total_balance
    FROM students s
    LEFT JOIN courses c ON s.course_id = c.id
    $where_clause
    ORDER BY s.created_at DESC
", $params);

// Get courses for dropdown
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Get student statistics
$student_stats = [
    'total_students' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0,
    'new_this_month' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")['count'] ?? 0,
    'with_outstanding_fees' => fetchOne("SELECT COUNT(DISTINCT s.id) as count FROM students s JOIN student_fees sf ON s.id = sf.student_id WHERE s.status = 'active' AND sf.balance > 0")['count'] ?? 0,
    'graduated' => fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'graduated'")['count'] ?? 0
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1>Student Management</h1>
                    <p class="text-muted">Comprehensive student information and records management</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        Add New Student
                    </button>
                    <button class="btn btn-outline-success" onclick="exportStudents()">Export</button>
                    <button class="btn btn-outline-info" onclick="printStudentList()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">👨‍🎓</div>
                <div class="stat-content">
                    <h3><?= $student_stats['total_students'] ?></h3>
                    <p>Active Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">📈</div>
                <div class="stat-content">
                    <h3><?= $student_stats['new_this_month'] ?></h3>
                    <p>New This Month</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">💰</div>
                <div class="stat-content">
                    <h3><?= $student_stats['with_outstanding_fees'] ?></h3>
                    <p>Outstanding Fees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">🎓</div>
                <div class="stat-content">
                    <h3><?= $student_stats['graduated'] ?></h3>
                    <p>Graduated</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Students</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Student ID, Name, Email, or Phone">
                        </div>
                        <div class="col-md-3">
                            <label for="course_filter" class="form-label">Course</label>
                            <select class="form-select" id="course_filter" name="course_filter">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['course_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_filter" class="form-label">Status</label>
                            <select class="form-select" id="status_filter" name="status_filter">
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="graduated" <?= $status_filter === 'graduated' ? 'selected' : '' ?>>Graduated</option>
                                <option value="" <?= $status_filter === '' ? 'selected' : '' ?>>All</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Students List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Students (<?= count($students) ?> found)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                    <th>Outstanding</th>
                                    <th>Admission Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($student['student_id']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="student-avatar me-2">
                                                <?= strtoupper(substr($student['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($student['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($student['phone']) ?></strong>
                                            <?php if ($student['emergency_contact']): ?>
                                                <br><small class="text-muted">Emergency: <?= htmlspecialchars($student['emergency_phone']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($student['course_name']): ?>
                                            <strong><?= htmlspecialchars($student['course_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($student['course_code']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">No course assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $student['status'] === 'active' ? 'success' : ($student['status'] === 'graduated' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($student['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['outstanding_fees'] > 0): ?>
                                            <span class="text-danger">
                                                <strong>KSh <?= number_format($student['total_balance']) ?></strong>
                                                <br><small><?= $student['outstanding_fees'] ?> fee(s)</small>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-success">Cleared</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : 'N/A' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewStudent(<?= $student['id'] ?>)" title="View Details">
                                                👁️
                                            </button>
                                            <button class="btn btn-outline-success" onclick="editStudent(<?= $student['id'] ?>)" title="Edit">
                                                ✏️
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewFees(<?= $student['id'] ?>)" title="View Fees">
                                                💰
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteStudent(<?= $student['id'] ?>)" title="Deactivate">
                                                🗑️
                                            </button>
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
.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.stat-content h3 {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-weight: 500;
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
    font-size: 1.1rem;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.card {
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border-radius: 15px;
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0,0,0,0.12);
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 1px solid #e9ecef;
    border-radius: 15px 15px 0 0 !important;
    padding: 1.25rem 1.5rem;
}

.btn-group .btn {
    border-radius: 8px;
    margin: 0 2px;
    transition: all 0.3s ease;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    transform: translateY(-1px);
}

.badge {
    padding: 0.5em 0.75em;
    border-radius: 8px;
    font-weight: 500;
}

.alert {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .stat-icon {
        font-size: 1.5rem;
        width: 60px;
        height: 60px;
    }
    
    .stat-content h3 {
        font-size: 1.8rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        margin: 0;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
function viewStudent(studentId) {
    window.location.href = `/college_management_system/registrar/student_details.php?id=${studentId}`;
}

function editStudent(studentId) {
    // Fetch student details and populate edit modal
    fetch(`/college_management_system/api/get_student_details.php?id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate edit form fields
                document.getElementById('edit_student_id').value = data.student.id;
                document.getElementById('edit_first_name').value = data.student.first_name;
                document.getElementById('edit_last_name').value = data.student.last_name;
                document.getElementById('edit_email').value = data.student.email;
                document.getElementById('edit_phone').value = data.student.phone;
                document.getElementById('edit_address').value = data.student.address || '';
                document.getElementById('edit_emergency_contact').value = data.student.emergency_contact || '';
                document.getElementById('edit_emergency_phone').value = data.student.emergency_phone || '';
                document.getElementById('edit_status').value = data.student.status;
                
                new bootstrap.Modal(document.getElementById('editStudentModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading student details');
        });
}

function viewFees(studentId) {
    window.location.href = `/college_management_system/registrar/student_fees.php?student_id=${studentId}`;
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to deactivate this student? This action can be reversed later.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_student">
            <input type="hidden" name="student_id" value="${studentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportStudents() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.open('/college_management_system/registrar/export_students.php?' + params.toString(), '_blank');
}

function printStudentList() {
    window.print();
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Add form validation for add student form
    const addForm = document.getElementById('addStudentForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const studentId = document.getElementById('student_id').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const dateOfBirth = document.getElementById('date_of_birth').value;
            
            // Validate student ID format
            if (!/^[A-Z0-9]{6,12}$/.test(studentId)) {
                e.preventDefault();
                alert('Student ID must be 6-12 characters long and contain only letters and numbers');
                return;
            }
            
            // Validate email format
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            
            // Validate phone format
            if (!/^[0-9+\-\s()]{10,15}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return;
            }
            
            // Validate date of birth (not in future)
            if (new Date(dateOfBirth) > new Date()) {
                e.preventDefault();
                alert('Date of birth cannot be in the future');
                return;
            }
        });
    }
    
    // Auto-generate student ID
    const courseSelect = document.getElementById('course_id');
    const studentIdInput = document.getElementById('student_id');
    
    if (courseSelect && studentIdInput) {
        courseSelect.addEventListener('change', function() {
            if (this.value && !studentIdInput.value) {
                // Generate student ID based on course and current year
                const year = new Date().getFullYear().toString().substr(-2);
                const courseCode = this.options[this.selectedIndex].text.match(/\(([^)]+)\)/);
                const prefix = courseCode ? courseCode[1] : 'STU';
                const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                studentIdInput.value = `${prefix}${year}${random}`;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

<?php
/**
 * Certificate Issue Management
 * Handle certificate generation and approval workflow
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require registrar role
Authentication::requireRole('registrar');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

// Handle certificate issuance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'issue_certificate') {
            $student_id = (int)$_POST['student_id'];
            $certificate_type = sanitizeInput($_POST['certificate_type']);
            
            // Check if student is eligible
            $student = fetchOne("SELECT s.*, c.course_name, u.email 
                                FROM students s 
                                JOIN courses c ON s.course_id = c.id 
                                JOIN users u ON s.user_id = u.id 
                                WHERE s.id = ?", [$student_id]);
            
            if (!$student) {
                throw new Exception("Student not found");
            }
            
            // Check fee clearance
            $fee_balance = fetchOne("SELECT SUM(balance) as total_balance 
                                   FROM student_fees 
                                   WHERE student_id = ?", [$student_id])['total_balance'] ?? 0;
            
            // Check academic completion
            $academic_complete = true; // Demo - in real system, check grades and course completion
            
            if ($fee_balance > 0 && !isset($_POST['special_authorization'])) {
                $error = "Student has outstanding fee balance of KSh " . number_format($fee_balance) . ". Special authorization required.";
            } elseif (!$academic_complete) {
                $error = "Student has not completed all academic requirements.";
            } else {
                // Generate certificate
                $certificate_number = 'CERT-' . date('Y') . '-' . str_pad($student_id, 4, '0', STR_PAD_LEFT);
                
                $certificate_data = [
                    'student_id' => $student_id,
                    'certificate_number' => $certificate_number,
                    'certificate_type' => $certificate_type,
                    'issue_date' => date('Y-m-d'),
                    'issued_by' => $user['id'],
                    'fee_cleared' => $fee_balance <= 0 ? 1 : 0,
                    'special_authorization' => isset($_POST['special_authorization']) ? 1 : 0,
                    'authorized_by' => isset($_POST['special_authorization']) ? $user['id'] : null,
                    'status' => 'issued'
                ];
                
                $certificate_id = insertRecord('certificates', $certificate_data);
                
                if (function_exists('logActivity')) {
                    logActivity($user['id'], 'certificate_issued', "Certificate {$certificate_number} issued to student ID {$student_id}");
                }
                
                $message = "Certificate {$certificate_number} has been successfully issued to {$student['first_name']} {$student['last_name']}.";
            }
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get eligible students
try {
    $eligible_students = fetchAll("
        SELECT s.*, c.course_name, u.email,
               COALESCE(SUM(sf.balance), 0) as fee_balance,
               COUNT(cert.id) as certificate_count
        FROM students s 
        JOIN courses c ON s.course_id = c.id 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN student_fees sf ON s.id = sf.student_id 
        LEFT JOIN certificates cert ON s.id = cert.student_id 
        WHERE s.status = 'active'
        GROUP BY s.id 
        ORDER BY s.first_name, s.last_name
    ");
} catch (Exception $e) {
    $eligible_students = [];
}

// Get recent certificates
try {
    $recent_certificates = fetchAll("
        SELECT cert.*, s.first_name, s.last_name, s.student_id as student_number, c.course_name,
               u1.username as issued_by_name, u2.username as authorized_by_name
        FROM certificates cert
        JOIN students s ON cert.student_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN users u1 ON cert.issued_by = u1.id
        LEFT JOIN users u2 ON cert.authorized_by = u2.id
        ORDER BY cert.created_at DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    $recent_certificates = [];
}
?>

<div class="container">
    <div class="page-header">
        <h1>Certificate Management</h1>
        <p>Issue and manage student certificates</p>
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

    <!-- Certificate Issue Form -->
    <div class="form-section">
        <h2>Issue New Certificate</h2>
        <form method="POST" class="certificate-form">
            <input type="hidden" name="action" value="issue_certificate">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Select Student:</label>
                    <select name="student_id" id="student_id" required onchange="updateStudentInfo()">
                        <option value="">Choose a student...</option>
                        <?php foreach ($eligible_students as $student): ?>
                        <option value="<?= $student['id'] ?>" 
                                data-course="<?= htmlspecialchars($student['course_name']) ?>"
                                data-balance="<?= $student['fee_balance'] ?>"
                                data-certificates="<?= $student['certificate_count'] ?>">
                            <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> 
                            (<?= htmlspecialchars($student['student_id']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="certificate_type">Certificate Type:</label>
                    <select name="certificate_type" id="certificate_type" required>
                        <option value="">Select type...</option>
                        <option value="completion">Course Completion Certificate</option>
                        <option value="graduation">Graduation Certificate</option>
                        <option value="transcript">Academic Transcript</option>
                    </select>
                </div>
            </div>

            <!-- Student Information Display -->
            <div id="student-info" class="student-info" style="display: none;">
                <h3>Student Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Course:</label>
                        <span id="info-course">-</span>
                    </div>
                    <div class="info-item">
                        <label>Fee Balance:</label>
                        <span id="info-balance" class="balance-amount">-</span>
                    </div>
                    <div class="info-item">
                        <label>Previous Certificates:</label>
                        <span id="info-certificates">-</span>
                    </div>
                </div>
                
                <div id="fee-warning" class="fee-warning" style="display: none;">
                    <p><strong>⚠️ Warning:</strong> This student has outstanding fees. Special authorization is required to issue certificate.</p>
                    <label class="checkbox-label">
                        <input type="checkbox" name="special_authorization" id="special_authorization">
                        I authorize certificate issuance despite outstanding fees (Director/Registrar approval)
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="issue-btn" disabled>
                    Issue Certificate
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    Reset Form
                </button>
            </div>
        </form>
    </div>

    <!-- Recent Certificates -->
    <div class="certificates-section">
        <h2>Recent Certificates</h2>
        <div class="certificates-table">
            <table>
                <thead>
                    <tr>
                        <th>Certificate #</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Type</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_certificates)): ?>
                    <tr>
                        <td colspan="7" class="no-data">No certificates issued yet</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recent_certificates as $cert): ?>
                    <tr>
                        <td class="cert-number"><?= htmlspecialchars($cert['certificate_number']) ?></td>
                        <td>
                            <div class="student-info">
                                <strong><?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></strong>
                                <small><?= htmlspecialchars($cert['student_number']) ?></small>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($cert['course_name']) ?></td>
                        <td>
                            <span class="cert-type <?= $cert['certificate_type'] ?>">
                                <?= ucfirst($cert['certificate_type']) ?>
                            </span>
                        </td>
                        <td><?= date('M j, Y', strtotime($cert['issue_date'])) ?></td>
                        <td>
                            <span class="status-badge <?= $cert['status'] ?>">
                                <?= ucfirst($cert['status']) ?>
                            </span>
                            <?php if ($cert['special_authorization']): ?>
                            <span class="auth-badge">Special Auth</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <button class="btn btn-sm btn-primary" onclick="viewCertificate(<?= $cert['id'] ?>)">
                                View
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="printCertificate(<?= $cert['id'] ?>)">
                                Print
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Certificate Statistics -->
    <div class="stats-section">
        <h2>Certificate Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($recent_certificates) ?></div>
                <div class="stat-label">Total Issued</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count(array_filter($recent_certificates, function($c) { return $c['certificate_type'] === 'completion'; })) ?>
                </div>
                <div class="stat-label">Completion Certificates</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count(array_filter($recent_certificates, function($c) { return $c['certificate_type'] === 'graduation'; })) ?>
                </div>
                <div class="stat-label">Graduation Certificates</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= count(array_filter($recent_certificates, function($c) { return $c['special_authorization']; })) ?>
                </div>
                <div class="stat-label">Special Authorizations</div>
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

.form-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.form-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.certificate-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
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

.form-group select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
}

.student-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #3498db;
}

.student-info h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item label {
    font-size: 0.9em;
    color: #7f8c8d;
    margin-bottom: 5px;
}

.info-item span {
    font-weight: bold;
    color: #2c3e50;
}

.balance-amount.positive {
    color: #e74c3c;
}

.balance-amount.zero {
    color: #27ae60;
}

.fee-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
}

.fee-warning p {
    margin: 0 0 10px 0;
    color: #856404;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #856404;
    font-weight: bold;
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

.btn-primary:hover:not(:disabled) {
    background-color: #2980b9;
}

.btn-primary:disabled {
    background-color: #bdc3c7;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background-color: #7f8c8d;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.9em;
}

.certificates-section {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.certificates-section h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.certificates-table {
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

.cert-number {
    font-family: monospace;
    font-weight: bold;
    color: #3498db;
}

.student-info strong {
    display: block;
    color: #2c3e50;
}

.student-info small {
    color: #7f8c8d;
}

.cert-type {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.cert-type.completion {
    background-color: #d5f4e6;
    color: #27ae60;
}

.cert-type.graduation {
    background-color: #d6eaf8;
    color: #3498db;
}

.cert-type.transcript {
    background-color: #fdebd0;
    color: #f39c12;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.issued {
    background-color: #d5f4e6;
    color: #27ae60;
}

.status-badge.pending {
    background-color: #fdebd0;
    color: #f39c12;
}

.auth-badge {
    display: inline-block;
    background-color: #e8f4fd;
    color: #3498db;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7em;
    font-weight: bold;
    margin-left: 5px;
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function updateStudentInfo() {
    const select = document.getElementById('student_id');
    const studentInfo = document.getElementById('student-info');
    const feeWarning = document.getElementById('fee-warning');
    const issueBtn = document.getElementById('issue-btn');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const course = option.dataset.course;
        const balance = parseFloat(option.dataset.balance);
        const certificates = option.dataset.certificates;
        
        // Update info display
        document.getElementById('info-course').textContent = course;
        document.getElementById('info-balance').textContent = 'KSh ' + balance.toLocaleString();
        document.getElementById('info-certificates').textContent = certificates;
        
        // Update balance color
        const balanceElement = document.getElementById('info-balance');
        balanceElement.className = 'balance-amount ' + (balance > 0 ? 'positive' : 'zero');
        
        // Show/hide fee warning
        if (balance > 0) {
            feeWarning.style.display = 'block';
            issueBtn.disabled = true;
        } else {
            feeWarning.style.display = 'none';
            issueBtn.disabled = false;
        }
        
        studentInfo.style.display = 'block';
    } else {
        studentInfo.style.display = 'none';
        issueBtn.disabled = true;
    }
}

// Enable issue button when special authorization is checked
document.getElementById('special_authorization').addEventListener('change', function() {
    const issueBtn = document.getElementById('issue-btn');
    const select = document.getElementById('student_id');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const balance = parseFloat(option.dataset.balance);
        
        if (balance > 0) {
            issueBtn.disabled = !this.checked;
        }
    }
});

function resetForm() {
    document.querySelector('.certificate-form').reset();
    document.getElementById('student-info').style.display = 'none';
    document.getElementById('issue-btn').disabled = true;
}

function viewCertificate(certificateId) {
    // In a real implementation, this would open a modal or new page
    alert('View certificate functionality - Certificate ID: ' + certificateId);
}

function printCertificate(certificateId) {
    // In a real implementation, this would generate and print the certificate
    alert('Print certificate functionality - Certificate ID: ' + certificateId);
}

// Enable certificate type selection when student is selected
document.getElementById('student_id').addEventListener('change', function() {
    const certType = document.getElementById('certificate_type');
    if (this.value) {
        certType.disabled = false;
    } else {
        certType.disabled = true;
        certType.value = '';
    }
});

// Enable issue button when both student and certificate type are selected
document.getElementById('certificate_type').addEventListener('change', function() {
    const select = document.getElementById('student_id');
    const issueBtn = document.getElementById('issue-btn');
    
    if (select.value && this.value) {
        const option = select.options[select.selectedIndex];
        const balance = parseFloat(option.dataset.balance);
        const authCheckbox = document.getElementById('special_authorization');
        
        if (balance > 0) {
            issueBtn.disabled = !authCheckbox.checked;
        } else {
            issueBtn.disabled = false;
        }
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

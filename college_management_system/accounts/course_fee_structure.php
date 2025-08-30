<?php
/**
 * Course Fee Structure Management
 * Manage fee structures for different courses
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require accounts role
Authentication::requireRole('accounts');

$user = Authentication::getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_fee_structure':
                $course_id = $_POST['course_id'];
                $fee_type = $_POST['fee_type'];
                $amount = $_POST['amount'];
                $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
                $description = $_POST['description'];
                $academic_year = $_POST['academic_year'];
                $due_date_offset = $_POST['due_date_offset'] ?? 30;
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO fee_structure 
                        (course_id, fee_type, amount, is_mandatory, description, academic_year, due_date_offset, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    
                    if ($stmt->execute([$course_id, $fee_type, $amount, $is_mandatory, $description, $academic_year, $due_date_offset])) {
                        $success_message = "Fee structure added successfully!";
                    } else {
                        $error_message = "Error adding fee structure.";
                    }
                } catch (Exception $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
                break;
                
            case 'update_fee_structure':
                $fee_id = $_POST['fee_id'];
                $amount = $_POST['amount'];
                $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
                $description = $_POST['description'];
                $due_date_offset = $_POST['due_date_offset'];
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE fee_structure 
                        SET amount = ?, is_mandatory = ?, description = ?, due_date_offset = ? 
                        WHERE id = ?
                    ");
                    
                    if ($stmt->execute([$amount, $is_mandatory, $description, $due_date_offset, $fee_id])) {
                        $success_message = "Fee structure updated successfully!";
                    } else {
                        $error_message = "Error updating fee structure.";
                    }
                } catch (Exception $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
                break;
                
            case 'toggle_status':
                $fee_id = $_POST['fee_id'];
                $new_status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                
                try {
                    $stmt = $pdo->prepare("UPDATE fee_structure SET status = ? WHERE id = ?");
                    if ($stmt->execute([$new_status, $fee_id])) {
                        $success_message = "Fee structure status updated successfully!";
                    } else {
                        $error_message = "Error updating status.";
                    }
                } catch (Exception $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all courses
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Get fee structures with course information
$fee_structures = fetchAll("
    SELECT fs.*, c.course_name, c.course_code,
           COUNT(sf.id) as students_assigned,
           SUM(sf.amount_due) as total_expected,
           SUM(sf.amount_paid) as total_collected
    FROM fee_structure fs 
    JOIN courses c ON fs.course_id = c.id 
    LEFT JOIN student_fees sf ON fs.id = sf.fee_structure_id
    GROUP BY fs.id
    ORDER BY c.course_name, fs.fee_type
");

// Get summary statistics
$fee_stats = [
    'total_structures' => count($fee_structures),
    'active_structures' => count(array_filter($fee_structures, function($fs) { return $fs['status'] === 'active'; })),
    'total_expected_revenue' => array_sum(array_column($fee_structures, 'total_expected')),
    'total_collected' => array_sum(array_column($fee_structures, 'total_collected'))
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Course Fee Structure Management</h1>
                <p class="text-muted">Configure and manage fee structures for different courses</p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">📋</div>
                <div class="stat-content">
                    <h3><?= $fee_stats['total_structures'] ?></h3>
                    <p>Total Fee Structures</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">✅</div>
                <div class="stat-content">
                    <h3><?= $fee_stats['active_structures'] ?></h3>
                    <p>Active Structures</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($fee_stats['total_expected_revenue']) ?></h3>
                    <p>Expected Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">📊</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($fee_stats['total_collected']) ?></h3>
                    <p>Total Collected</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Fee Structure -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Add New Fee Structure</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="add_fee_structure">
                        
                        <div class="col-md-4">
                            <label for="course_id" class="form-label">Course *</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>">
                                        <?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="fee_type" class="form-label">Fee Type *</label>
                            <select class="form-select" id="fee_type" name="fee_type" required>
                                <option value="">Select Fee Type</option>
                                <option value="Tuition Fee">Tuition Fee</option>
                                <option value="Registration Fee">Registration Fee</option>
                                <option value="Examination Fee">Examination Fee</option>
                                <option value="Laboratory Fee">Laboratory Fee</option>
                                <option value="Library Fee">Library Fee</option>
                                <option value="Sports Fee">Sports Fee</option>
                                <option value="Medical Fee">Medical Fee</option>
                                <option value="Hostel Fee">Hostel Fee</option>
                                <option value="Transport Fee">Transport Fee</option>
                                <option value="Caution Money">Caution Money</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount (KSh) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" 
                                   placeholder="e.g., 2024/2025" value="<?= date('Y') . '/' . (date('Y') + 1) ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="due_date_offset" class="form-label">Due Date (Days from Registration)</label>
                            <input type="number" class="form-control" id="due_date_offset" name="due_date_offset" 
                                   value="30" min="0" max="365">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" 
                                   placeholder="Brief description of the fee">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" checked>
                                <label class="form-check-label" for="is_mandatory">
                                    Mandatory Fee (Required for all students)
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Fee Structure</button>
                            <button type="reset" class="btn btn-secondary">Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Structures List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Fee Structures</h5>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm" onclick="exportFeeStructures()">Export</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="printFeeStructures()">Print</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="feeStructuresTable">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Students</th>
                                    <th>Expected</th>
                                    <th>Collected</th>
                                    <th>Collection %</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fee_structures as $fee): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($fee['course_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($fee['course_code']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($fee['fee_type']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($fee['academic_year'] ?? 'N/A') ?></small>
                                    </td>
                                    <td><strong>KSh <?= number_format($fee['amount'], 2) ?></strong></td>
                                    <td>
                                        <?php if ($fee['is_mandatory']): ?>
                                            <span class="badge bg-success">Mandatory</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $fee['students_assigned'] ?></td>
                                    <td>KSh <?= number_format($fee['total_expected']) ?></td>
                                    <td>KSh <?= number_format($fee['total_collected']) ?></td>
                                    <td>
                                        <?php 
                                        $collection_rate = $fee['total_expected'] > 0 ? 
                                            ($fee['total_collected'] / $fee['total_expected']) * 100 : 0;
                                        $rate_class = $collection_rate >= 80 ? 'success' : ($collection_rate >= 50 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $rate_class ?>"><?= number_format($collection_rate, 1) ?>%</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $fee['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($fee['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editFeeStructure(<?= $fee['id'] ?>)" title="Edit">
                                                ✏️
                                            </button>
                                            <button class="btn btn-outline-<?= $fee['status'] === 'active' ? 'warning' : 'success' ?>" 
                                                    onclick="toggleStatus(<?= $fee['id'] ?>, '<?= $fee['status'] ?>')" 
                                                    title="<?= $fee['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                <?= $fee['status'] === 'active' ? '⏸️' : '▶️' ?>
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewDetails(<?= $fee['id'] ?>)" title="View Details">
                                                👁️
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

<!-- Edit Fee Structure Modal -->
<div class="modal fade" id="editFeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Fee Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editFeeForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_fee_structure">
                    <input type="hidden" name="fee_id" id="edit_fee_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_amount" class="form-label">Amount (KSh)</label>
                            <input type="number" class="form-control" id="edit_amount" name="amount" required min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_due_date_offset" class="form-label">Due Date Offset (Days)</label>
                            <input type="number" class="form-control" id="edit_due_date_offset" name="due_date_offset" min="0" max="365">
                        </div>
                        
                        <div class="col-12">
                            <label for="edit_description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="edit_description" name="description">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_mandatory" name="is_mandatory">
                                <label class="form-check-label" for="edit_is_mandatory">
                                    Mandatory Fee
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Fee Structure</button>
                </div>
            </form>
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

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>

<script>
function editFeeStructure(feeId) {
    // Fetch fee structure details and populate modal
    fetch(`/college_management_system/api/get_fee_structure.php?id=${feeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_fee_id').value = data.fee.id;
                document.getElementById('edit_amount').value = data.fee.amount;
                document.getElementById('edit_description').value = data.fee.description;
                document.getElementById('edit_due_date_offset').value = data.fee.due_date_offset;
                document.getElementById('edit_is_mandatory').checked = data.fee.is_mandatory == 1;
                
                new bootstrap.Modal(document.getElementById('editFeeModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading fee structure details');
        });
}

function toggleStatus(feeId, currentStatus) {
    const action = currentStatus === 'active' ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} this fee structure?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="fee_id" value="${feeId}">
            <input type="hidden" name="status" value="${currentStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewDetails(feeId) {
    window.location.href = `/college_management_system/accounts/fee_structure_details.php?id=${feeId}`;
}

function exportFeeStructures() {
    window.location.href = '/college_management_system/accounts/export_fee_structures.php';
}

function printFeeStructures() {
    window.print();
}

// Initialize DataTable
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTable !== 'undefined') {
        new DataTable('#feeStructuresTable', {
            pageLength: 25,
            order: [[0, 'asc']],
            responsive: true
        });
    }
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

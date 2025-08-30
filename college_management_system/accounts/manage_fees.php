<?php
/**
 * Fee Management System
 * Manage fee structures, types, and configurations
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
            case 'add_fee':
                $course_id = $_POST['course_id'];
                $fee_type = $_POST['fee_type'];
                $amount = $_POST['amount'];
                $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
                $description = $_POST['description'];
                $academic_year = $_POST['academic_year'];
                
                $stmt = $pdo->prepare("INSERT INTO fee_structure (course_id, fee_type, amount, is_mandatory, description, academic_year) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$course_id, $fee_type, $amount, $is_mandatory, $description, $academic_year])) {
                    $success_message = "Fee structure added successfully!";
                } else {
                    $error_message = "Error adding fee structure.";
                }
                break;
                
            case 'update_fee':
                $fee_id = $_POST['fee_id'];
                $amount = $_POST['amount'];
                $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
                $description = $_POST['description'];
                
                $stmt = $pdo->prepare("UPDATE fee_structure SET amount = ?, is_mandatory = ?, description = ? WHERE id = ?");
                if ($stmt->execute([$amount, $is_mandatory, $description, $fee_id])) {
                    $success_message = "Fee structure updated successfully!";
                } else {
                    $error_message = "Error updating fee structure.";
                }
                break;
        }
    }
}

// Get all courses for dropdown
$courses = fetchAll("SELECT id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name");

// Get all fee structures with course information
$fee_structures = fetchAll("
    SELECT fs.*, c.course_name, c.course_code 
    FROM fee_structure fs 
    JOIN courses c ON fs.course_id = c.id 
    ORDER BY c.course_name, fs.fee_type
");

// Get fee statistics
$fee_stats = [
    'total_fee_types' => fetchOne("SELECT COUNT(*) as count FROM fee_structure")['count'] ?? 0,
    'mandatory_fees' => fetchOne("SELECT COUNT(*) as count FROM fee_structure WHERE is_mandatory = 1")['count'] ?? 0,
    'optional_fees' => fetchOne("SELECT COUNT(*) as count FROM fee_structure WHERE is_mandatory = 0")['count'] ?? 0,
    'total_fee_amount' => fetchOne("SELECT SUM(amount) as total FROM fee_structure")['total'] ?? 0
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1>Fee Management System</h1>
                <p class="text-muted">Manage course fee structures and configurations</p>
            </div>
        </div>
    </div>

    <!-- Fee Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">📋</div>
                <div class="stat-content">
                    <h3><?= $fee_stats['total_fee_types'] ?></h3>
                    <p>Total Fee Types</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">✅</div>
                <div class="stat-content">
                    <h3><?= $fee_stats['mandatory_fees'] ?></h3>
                    <p>Mandatory Fees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">📝</div>
                <div class="stat-content">
                    <h3><?= $fee_stats['optional_fees'] ?></h3>
                    <p>Optional Fees</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">💰</div>
                <div class="stat-content">
                    <h3>KSh <?= number_format($fee_stats['total_fee_amount']) ?></h3>
                    <p>Total Fee Amount</p>
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
                        <input type="hidden" name="action" value="add_fee">
                        
                        <div class="col-md-4">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="fee_type" class="form-label">Fee Type</label>
                            <input type="text" class="form-control" id="fee_type" name="fee_type" required placeholder="e.g., Tuition Fee, Lab Fee">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount (KSh)</label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" placeholder="e.g., 2024/2025">
                        </div>
                        
                        <div class="col-md-8">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="Fee description">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_mandatory" name="is_mandatory" checked>
                                <label class="form-check-label" for="is_mandatory">
                                    Mandatory Fee
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Add Fee Structure</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
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
                                    <th>Academic Year</th>
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
                                    <td><?= htmlspecialchars($fee['fee_type']) ?></td>
                                    <td><strong>KSh <?= number_format($fee['amount'], 2) ?></strong></td>
                                    <td>
                                        <?php if ($fee['is_mandatory']): ?>
                                            <span class="badge bg-success">Mandatory</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Optional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($fee['academic_year'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $fee['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($fee['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="editFee(<?= $fee['id'] ?>)" title="Edit">
                                                ✏️
                                            </button>
                                            <button class="btn btn-outline-info" onclick="viewFeeDetails(<?= $fee['id'] ?>)" title="View Details">
                                                👁️
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteFee(<?= $fee['id'] ?>)" title="Delete">
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

<!-- Edit Fee Modal -->
<div class="modal fade" id="editFeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Fee Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editFeeForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_fee">
                    <input type="hidden" name="fee_id" id="edit_fee_id">
                    
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label">Amount (KSh)</label>
                        <input type="number" class="form-control" id="edit_amount" name="amount" required min="0" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="edit_description" name="description">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_mandatory" name="is_mandatory">
                            <label class="form-check-label" for="edit_is_mandatory">
                                Mandatory Fee
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Fee</button>
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

@media (max-width: 768px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

<script>
function editFee(feeId) {
    // Fetch fee details and populate modal
    fetch(`/college_management_system/api/get_fee_details.php?id=${feeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('edit_fee_id').value = data.fee.id;
                document.getElementById('edit_amount').value = data.fee.amount;
                document.getElementById('edit_description').value = data.fee.description;
                document.getElementById('edit_is_mandatory').checked = data.fee.is_mandatory == 1;
                
                new bootstrap.Modal(document.getElementById('editFeeModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading fee details');
        });
}

function viewFeeDetails(feeId) {
    // Implement fee details view
    window.location.href = `/college_management_system/accounts/fee_details.php?id=${feeId}`;
}

function deleteFee(feeId) {
    if (confirm('Are you sure you want to delete this fee structure?')) {
        fetch(`/college_management_system/api/delete_fee.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({id: feeId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting fee structure');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting fee structure');
        });
    }
}

function exportFeeStructures() {
    window.location.href = '/college_management_system/accounts/export_fees.php';
}

function printFeeStructures() {
    window.print();
}

// Initialize DataTable for better table functionality
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

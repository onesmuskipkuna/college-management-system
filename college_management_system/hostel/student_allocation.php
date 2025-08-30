<?php
/**
 * Student Hostel Allocation Module
 * Advanced student room allocation and management system
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require hostel role
Authentication::requireRole('hostel');

$user = Authentication::getCurrentUser();

    // Sample data for student allocation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_room = $_POST['roomSelect'];
        $selected_meal_plan = $_POST['mealPlanSelect'];
        
        // Logic to allocate room and meal plan to the student
        if (allocateRoomToStudent($student_id, $selected_room, $selected_meal_plan)) {
            echo "<script>alert('Room and meal plan allocated successfully!');</script>";
        } else {
            echo "<script>alert('Failed to allocate room and meal plan. Please try again.');</script>";
        }
    }

    $pending_applications = [
    [
        'id' => 1,
        'student_id' => 'STU001',
        'student_name' => 'John Doe',
        'course' => 'Computer Science',
        'year' => '2nd Year',
        'gender' => 'Male',
        'phone' => '+254712345678',
        'email' => 'john.doe@student.college.edu',
        'room_preference' => 'Single',
        'special_needs' => 'None',
        'application_date' => '2024-01-15',
        'status' => 'pending',
        'priority_score' => 85
    ],
    [
        'id' => 2,
        'student_id' => 'STU002',
        'student_name' => 'Jane Smith',
        'course' => 'Business Management',
        'year' => '1st Year',
        'gender' => 'Female',
        'phone' => '+254723456789',
        'email' => 'jane.smith@student.college.edu',
        'room_preference' => 'Double',
        'special_needs' => 'Ground floor access',
        'application_date' => '2024-01-16',
        'status' => 'pending',
        'priority_score' => 92
    ],
    [
        'id' => 3,
        'student_id' => 'STU003',
        'student_name' => 'Michael Johnson',
        'course' => 'Accounting',
        'year' => '3rd Year',
        'gender' => 'Male',
        'phone' => '+254734567890',
        'email' => 'michael.johnson@student.college.edu',
        'room_preference' => 'Single',
        'special_needs' => 'Quiet environment',
        'application_date' => '2024-01-17',
        'status' => 'under_review',
        'priority_score' => 78
    ]
];

// Available rooms data
$available_rooms = [
    ['room_number' => 'A-101', 'type' => 'Single', 'floor' => '1st', 'block' => 'A', 'monthly_fee' => 8000, 'amenities' => ['WiFi', 'Study Desk', 'Wardrobe']],
    ['room_number' => 'A-102', 'type' => 'Double', 'floor' => '1st', 'block' => 'A', 'monthly_fee' => 6000, 'amenities' => ['WiFi', 'Study Desk', 'Shared Wardrobe']],
    ['room_number' => 'B-201', 'type' => 'Single', 'floor' => '2nd', 'block' => 'B', 'monthly_fee' => 8500, 'amenities' => ['WiFi', 'Study Desk', 'Wardrobe', 'Balcony']],
    ['room_number' => 'B-205', 'type' => 'Double', 'floor' => '2nd', 'block' => 'B', 'monthly_fee' => 6500, 'amenities' => ['WiFi', 'Study Desk', 'Shared Wardrobe']],
    ['room_number' => 'C-301', 'type' => 'Triple', 'floor' => '3rd', 'block' => 'C', 'monthly_fee' => 5000, 'amenities' => ['WiFi', 'Study Area', 'Shared Facilities']]
];

// Current allocations
$current_allocations = [
    [
        'student_id' => 'STU004',
        'student_name' => 'Sarah Wilson',
        'room_number' => 'A-103',
        'room_type' => 'Single',
        'allocation_date' => '2024-01-10',
        'monthly_fee' => 8000,
        'payment_status' => 'paid',
        'contract_end' => '2024-12-31'
    ],
    [
        'student_id' => 'STU005',
        'student_name' => 'David Brown',
        'room_number' => 'B-202',
        'room_type' => 'Double',
        'allocation_date' => '2024-01-12',
        'monthly_fee' => 6000,
        'payment_status' => 'pending',
        'contract_end' => '2024-12-31'
    ]
];

// Allocation statistics
$allocation_stats = [
    'total_applications' => count($pending_applications) + 45,
    'pending_applications' => count($pending_applications),
    'approved_today' => 8,
    'rejected_today' => 2,
    'total_allocated' => 156,
    'available_rooms' => count($available_rooms),
    'occupancy_rate' => 87.5
];
?>

<link rel="stylesheet" href="../css/hostel.css">

<div class="allocation-container">
    <div class="allocation-header">
        <h1>Student Hostel Allocation</h1>
        <p>Manage student room applications and allocations</p>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="bulkAllocate()">🏠 Bulk Allocate</button>
            <button class="btn btn-secondary" onclick="exportAllocations()">📊 Export Report</button>
            <button class="btn btn-info" onclick="roomOptimizer()">🎯 Room Optimizer</button>
        </div>
    </div>

    <!-- Allocation Statistics -->
    <div class="stats-overview">
        <div class="stat-card applications">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3><?= $allocation_stats['total_applications'] ?></h3>
                <p>Total Applications</p>
                <span class="stat-change">+<?= $allocation_stats['pending_applications'] ?> pending</span>
            </div>
        </div>
        
        <div class="stat-card approved">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <h3><?= $allocation_stats['approved_today'] ?></h3>
                <p>Approved Today</p>
                <span class="stat-change">+<?= $allocation_stats['rejected_today'] ?> rejected</span>
            </div>
        </div>
        
        <div class="stat-card allocated">
            <div class="stat-icon">🏠</div>
            <div class="stat-content">
                <h3><?= $allocation_stats['total_allocated'] ?></h3>
                <p>Total Allocated</p>
                <span class="stat-change"><?= $allocation_stats['occupancy_rate'] ?>% occupancy</span>
            </div>
        </div>
        
        <div class="stat-card available">
            <div class="stat-icon">🟢</div>
            <div class="stat-content">
                <h3><?= $allocation_stats['available_rooms'] ?></h3>
                <p>Available Rooms</p>
                <span class="stat-change">Ready for allocation</span>
            </div>
        </div>
    </div>

    <!-- Allocation Tabs -->
    <div class="allocation-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showTab('pending')">Pending Applications</button>
            <button class="tab-button" onclick="showTab('available')">Available Rooms</button>
            <button class="tab-button" onclick="showTab('current')">Current Allocations</button>
            <button class="tab-button" onclick="showTab('analytics')">Allocation Analytics</button>
        </div>

        <!-- Pending Applications Tab -->
        <div id="pending" class="tab-content active">
            <div class="applications-section">
                <div class="section-header">
                    <h2>Pending Applications</h2>
                    <div class="filter-controls">
                        <select class="filter-select" onchange="filterApplications(this.value)">
                            <option value="all">All Applications</option>
                            <option value="pending">Pending Review</option>
                            <option value="under_review">Under Review</option>
                            <option value="high_priority">High Priority</option>
                        </select>
                        <button class="btn btn-secondary" onclick="sortByPriority()">Sort by Priority</button>
                    </div>
                </div>
                
                <div class="applications-grid">
                    <?php foreach ($pending_applications as $app): ?>
                    <div class="application-card <?= $app['status'] ?>" data-priority="<?= $app['priority_score'] ?>">
                        <div class="application-header">
                            <div class="student-info">
                                <h3><?= htmlspecialchars($app['student_name']) ?></h3>
                                <span class="student-id"><?= htmlspecialchars($app['student_id']) ?></span>
                            </div>
                            <div class="priority-score">
                                <span class="score-label">Priority Score</span>
                                <span class="score-value <?= $app['priority_score'] >= 85 ? 'high' : ($app['priority_score'] >= 70 ? 'medium' : 'low') ?>">
                                    <?= $app['priority_score'] ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="application-details">
                            <div class="detail-row">
                                <span class="detail-label">Course:</span>
                                <span class="detail-value"><?= htmlspecialchars($app['course']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Year:</span>
                                <span class="detail-value"><?= htmlspecialchars($app['year']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Gender:</span>
                                <span class="detail-value"><?= htmlspecialchars($app['gender']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Room Preference:</span>
                                <span class="detail-value"><?= htmlspecialchars($app['room_preference']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Special Needs:</span>
                                <span class="detail-value"><?= htmlspecialchars($app['special_needs']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Applied:</span>
                                <span class="detail-value"><?= date('M j, Y', strtotime($app['application_date'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="application-actions">
                            <button class="btn btn-success" onclick="approveApplication(<?= $app['id'] ?>)">
                                ✅ Approve
                            </button>
                            <button class="btn btn-warning" onclick="reviewApplication(<?= $app['id'] ?>)">
                                👁️ Review
                            </button>
                            <button class="btn btn-danger" onclick="rejectApplication(<?= $app['id'] ?>)">
                                ❌ Reject
                            </button>
                            <button class="btn btn-info" onclick="allocateRoom(<?= $app['id'] ?>)">
                                🏠 Allocate Room
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Available Rooms Tab -->
        <div id="available" class="tab-content">
            <div class="rooms-section">
                <div class="section-header">
                    <h2>Available Rooms</h2>
                    <div class="filter-controls">
                        <select class="filter-select" onchange="filterRooms(this.value)">
                            <option value="all">All Room Types</option>
                            <option value="single">Single Rooms</option>
                            <option value="double">Double Rooms</option>
                            <option value="triple">Triple Rooms</option>
                        </select>
                        <select class="filter-select" onchange="filterByBlock(this.value)">
                            <option value="all">All Blocks</option>
                            <option value="A">Block A</option>
                            <option value="B">Block B</option>
                            <option value="C">Block C</option>
                        </select>
                    </div>
                </div>
                
                <div class="rooms-grid">
                    <?php foreach ($available_rooms as $room): ?>
                    <div class="room-card available" data-type="<?= strtolower($room['type']) ?>" data-block="<?= $room['block'] ?>">
                        <div class="room-header">
                            <h3>Room <?= htmlspecialchars($room['room_number']) ?></h3>
                            <span class="room-type-badge <?= strtolower($room['type']) ?>">
                                <?= $room['type'] ?>
                            </span>
                        </div>
                        
                        <div class="room-details">
                            <div class="detail-row">
                                <span class="detail-label">Block:</span>
                                <span class="detail-value"><?= $room['block'] ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Floor:</span>
                                <span class="detail-value"><?= $room['floor'] ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Monthly Fee:</span>
                                <span class="detail-value fee">KSh <?= number_format($room['monthly_fee']) ?></span>
                            </div>
                        </div>
                        
                        <div class="room-amenities">
                            <h4>Amenities:</h4>
                            <div class="amenities-list">
                                <?php foreach ($room['amenities'] as $amenity): ?>
                                <span class="amenity-tag"><?= htmlspecialchars($amenity) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="room-actions">
                            <button class="btn btn-primary" onclick="reserveRoom('<?= $room['room_number'] ?>')">
                                🔒 Reserve
                            </button>
                            <button class="btn btn-secondary" onclick="viewRoomDetails('<?= $room['room_number'] ?>')">
                                👁️ Details
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Current Allocations Tab -->
        <div id="current" class="tab-content">
            <div class="allocations-section">
                <div class="section-header">
                    <h2>Current Allocations</h2>
                    <div class="filter-controls">
                        <input type="text" class="search-input" placeholder="Search by student name or room..." onkeyup="searchAllocations(this.value)">
                        <select class="filter-select" onchange="filterByPaymentStatus(this.value)">
                            <option value="all">All Payment Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                
                <div class="allocations-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Room Number</th>
                                <th>Room Type</th>
                                <th>Monthly Fee</th>
                                <th>Payment Status</th>
                                <th>Contract End</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_allocations as $allocation): ?>
                            <tr class="allocation-row" data-payment="<?= $allocation['payment_status'] ?>">
                                <td><?= htmlspecialchars($allocation['student_id']) ?></td>
                                <td><?= htmlspecialchars($allocation['student_name']) ?></td>
                                <td><?= htmlspecialchars($allocation['room_number']) ?></td>
                                <td><?= htmlspecialchars($allocation['room_type']) ?></td>
                                <td>KSh <?= number_format($allocation['monthly_fee']) ?></td>
                                <td>
                                    <span class="payment-status <?= $allocation['payment_status'] ?>">
                                        <?= ucfirst($allocation['payment_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($allocation['contract_end'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" onclick="viewAllocation('<?= $allocation['student_id'] ?>')">
                                            View
                                        </button>
                                        <button class="btn btn-sm btn-warning" onclick="editAllocation('<?= $allocation['student_id'] ?>')">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="terminateAllocation('<?= $allocation['student_id'] ?>')">
                                            Terminate
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

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <div class="analytics-section">
                <h2>Allocation Analytics</h2>
                
                <div class="analytics-grid">
                    <div class="chart-container">
                        <h3>Room Type Distribution</h3>
                        <canvas id="roomTypeChart" width="300" height="200"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3>Monthly Allocation Trends</h3>
                        <canvas id="allocationTrendChart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="metrics-container">
                        <h3>Key Metrics</h3>
                        <div class="metrics-grid">
                            <div class="metric-card">
                                <div class="metric-value">87.5%</div>
                                <div class="metric-label">Occupancy Rate</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">4.2</div>
                                <div class="metric-label">Avg Days to Allocate</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">92%</div>
                                <div class="metric-label">Student Satisfaction</div>
                            </div>
                            <div class="metric-card">
                                <div class="metric-value">KSh 6,800</div>
                                <div class="metric-label">Avg Monthly Fee</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Allocation Modal -->
<div id="allocationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Allocate Room</h3>
            <span class="close" onclick="closeModal('allocationModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="allocation-form">
                <div class="form-group">
                    <label>Student:</label>
                    <input type="text" id="selectedStudent" readonly>
                </div>
                <div class="form-group">
                    <label>Select Room:</label>
                    <select id="roomSelect">
                        <option value="">Choose a room...</option>
                        <?php foreach ($available_rooms as $room): ?>
                        <option value="<?= $room['room_number'] ?>" data-fee="<?= $room['monthly_fee'] ?>">
                            <?= $room['room_number'] ?> - <?= $room['type'] ?> (KSh <?= number_format($room['monthly_fee']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Meal Plan:</label>
                    <select id="mealPlanSelect">
                        <option value="">Choose a meal plan...</option>
                        <?php foreach ($meal_plans as $plan): ?>
                        <option value="<?= $plan['id'] ?>" data-cost="<?= $plan['monthly_cost'] ?>">
                            <?= htmlspecialchars($plan['name']) ?> (KSh <?= number_format($plan['monthly_cost']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contract Start Date:</label>
                    <input type="date" id="contractStart" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Contract End Date:</label>
                    <input type="date" id="contractEnd" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                </div>
                <div class="form-group">
                    <label>Special Instructions:</label>
                    <textarea id="specialInstructions" rows="3" placeholder="Any special instructions or notes..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('allocationModal')">Cancel</button>
            <button class="btn btn-primary" onclick="confirmAllocation()">Allocate Room</button>
        </div>
    </div>
</div>

<script>
// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab and activate button
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

// Filter functions
function filterApplications(status) {
    const cards = document.querySelectorAll('.application-card');
    cards.forEach(card => {
        if (status === 'all' || card.classList.contains(status)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterRooms(type) {
    const cards = document.querySelectorAll('.room-card');
    cards.forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterByBlock(block) {
    const cards = document.querySelectorAll('.room-card');
    cards.forEach(card => {
        if (block === 'all' || card.dataset.block === block) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterByPaymentStatus(status) {
    const rows = document.querySelectorAll('.allocation-row');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.payment === status) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

// Search function
function searchAllocations(query) {
    const rows = document.querySelectorAll('.allocation-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query.toLowerCase())) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

// Sort function
function sortByPriority() {
    const container = document.querySelector('.applications-grid');
    const cards = Array.from(container.querySelectorAll('.application-card'));
    
    cards.sort((a, b) => {
        return parseInt(b.dataset.priority) - parseInt(a.dataset.priority);
    });
    
    cards.forEach(card => container.appendChild(card));
}

// Action functions
function approveApplication(id) {
    if (confirm('Are you sure you want to approve this application?')) {
        alert('Application approved successfully!');
        // Here you would make an AJAX call to update the database
    }
}

function reviewApplication(id) {
    alert('Opening application review form...');
}

function rejectApplication(id) {
    if (confirm('Are you sure you want to reject this application?')) {
        alert('Application rejected.');
        // Here you would make an AJAX call to update the database
    }
}

function allocateRoom(id) {
    document.getElementById('allocationModal').style.display = 'block';
    // Set the student info in the modal
    document.getElementById('selectedStudent').value = 'Student ID: ' + id;
}

function reserveRoom(roomNumber) {
    if (confirm('Reserve room ' + roomNumber + '?')) {
        alert('Room ' + roomNumber + ' has been reserved.');
    }
}

function viewRoomDetails(roomNumber) {
    alert('Viewing details for room ' + roomNumber);
}

function viewAllocation(studentId) {
    alert('Viewing allocation details for student ' + studentId);
}

function editAllocation(studentId) {
    alert('Editing allocation for student ' + studentId);
}

function terminateAllocation(studentId) {
    if (confirm('Are you sure you want to terminate the allocation for student ' + studentId + '?')) {
        alert('Allocation terminated.');
    }
}

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function confirmAllocation() {
    const room = document.getElementById('roomSelect').value;
    const startDate = document.getElementById('contractStart').value;
    
    if (!room || !startDate) {
        alert('Please fill in all required fields.');
        return;
    }
    
    alert('Room allocated successfully!');
    closeModal('allocationModal');
}

// Bulk operations
function bulkAllocate() {
    alert('Opening bulk allocation wizard...');
}

function exportAllocations() {
    alert('Exporting allocation report...');
}

function roomOptimizer() {
    alert('Opening room optimization tool...');
}

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Simple chart implementations would go here
    console.log('Student allocation page loaded');
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

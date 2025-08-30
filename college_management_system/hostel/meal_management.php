<?php
/**
 * Hostel Meal Management System
 * Comprehensive meal planning, tracking, and management
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require hostel role
Authentication::requireRole('hostel');

$user = Authentication::getCurrentUser();

// Sample meal data
$meal_plans = [
    [
        'id' => 1,
        'name' => 'Standard Meal Plan',
        'description' => 'Three meals per day with balanced nutrition',
        'meals_per_day' => 3,
        'monthly_cost' => 4500,
        'active_students' => 156,
        'status' => 'active'
    ],
    [
        'id' => 2,
        'name' => 'Premium Meal Plan',
        'description' => 'Enhanced meals with additional options and snacks',
        'meals_per_day' => 4,
        'monthly_cost' => 6200,
        'active_students' => 89,
        'status' => 'active'
    ],
    [
        'id' => 3,
        'name' => 'Basic Meal Plan',
        'description' => 'Two meals per day - lunch and dinner',
        'meals_per_day' => 2,
        'monthly_cost' => 3200,
        'active_students' => 45,
        'status' => 'active'
    ]
];

// Weekly menu
$weekly_menu = [
    'Monday' => [
        'breakfast' => 'Porridge, Bread, Tea/Coffee',
        'lunch' => 'Rice, Beef Stew, Vegetables',
        'dinner' => 'Ugali, Fish, Sukuma Wiki'
    ],
    'Tuesday' => [
        'breakfast' => 'Pancakes, Fruit, Milk',
        'lunch' => 'Chapati, Chicken Curry, Salad',
        'dinner' => 'Rice, Beans, Cabbage'
    ],
    'Wednesday' => [
        'breakfast' => 'Eggs, Toast, Juice',
        'lunch' => 'Pasta, Meat Sauce, Vegetables',
        'dinner' => 'Ugali, Beef, Spinach'
    ],
    'Thursday' => [
        'breakfast' => 'Cereal, Milk, Banana',
        'lunch' => 'Rice, Chicken, Mixed Vegetables',
        'dinner' => 'Chapati, Lentils, Kales'
    ],
    'Friday' => [
        'breakfast' => 'French Toast, Fruit Salad',
        'lunch' => 'Pilau, Beef, Coleslaw',
        'dinner' => 'Rice, Fish Curry, Vegetables'
    ],
    'Saturday' => [
        'breakfast' => 'Porridge, Mandazi, Tea',
        'lunch' => 'Ugali, Chicken, Vegetables',
        'dinner' => 'Rice, Beans, Sukuma Wiki'
    ],
    'Sunday' => [
        'breakfast' => 'Pancakes, Sausages, Juice',
        'lunch' => 'Rice, Beef Stew, Salad',
        'dinner' => 'Chapati, Fish, Spinach'
    ]
];

// Meal statistics
$meal_stats = [
    'total_students_enrolled' => 290,
    'meals_served_today' => 756,
    'meals_served_this_week' => 4832,
    'average_daily_meals' => 690,
    'food_waste_percentage' => 8.5,
    'student_satisfaction' => 87.3,
    'monthly_food_budget' => 1250000,
    'budget_utilized' => 78.5
];

// Recent meal feedback
$meal_feedback = [
    [
        'student_name' => 'John Doe',
        'meal_type' => 'Lunch',
        'date' => '2024-01-22',
        'rating' => 4,
        'comment' => 'Good portion size and taste. Could use more vegetables.',
        'meal_plan' => 'Standard'
    ],
    [
        'student_name' => 'Jane Smith',
        'meal_type' => 'Dinner',
        'date' => '2024-01-22',
        'rating' => 5,
        'comment' => 'Excellent meal! Very satisfied with the quality.',
        'meal_plan' => 'Premium'
    ],
    [
        'student_name' => 'Mike Johnson',
        'meal_type' => 'Breakfast',
        'date' => '2024-01-21',
        'rating' => 3,
        'comment' => 'Average breakfast. Would like more variety.',
        'meal_plan' => 'Basic'
    ]
];

// Dietary requirements
$dietary_requirements = [
    'vegetarian' => 45,
    'vegan' => 12,
    'gluten_free' => 8,
    'diabetic' => 15,
    'allergies' => 23,
    'halal' => 67,
    'no_restrictions' => 120
];

// Inventory data
$inventory_items = [
    ['item' => 'Rice (50kg bags)', 'current_stock' => 25, 'minimum_stock' => 15, 'unit_cost' => 4500, 'supplier' => 'ABC Suppliers'],
    ['item' => 'Cooking Oil (20L)', 'current_stock' => 8, 'minimum_stock' => 10, 'unit_cost' => 3200, 'supplier' => 'XYZ Distributors'],
    ['item' => 'Beef (kg)', 'current_stock' => 45, 'minimum_stock' => 30, 'unit_cost' => 650, 'supplier' => 'Local Butchery'],
    ['item' => 'Vegetables (Mixed)', 'current_stock' => 12, 'minimum_stock' => 20, 'unit_cost' => 150, 'supplier' => 'Fresh Farm'],
    ['item' => 'Bread (Loaves)', 'current_stock' => 35, 'minimum_stock' => 25, 'unit_cost' => 55, 'supplier' => 'City Bakery']
];
?>

<link rel="stylesheet" href="../css/hostel.css">

<div class="meal-container">
    <div class="meal-header">
        <h1>Hostel Meal Management</h1>
        <p>Comprehensive meal planning, tracking, and nutrition management</p>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="addMealPlan()">+ New Meal Plan</button>
            <button class="btn btn-secondary" onclick="updateMenu()">📋 Update Menu</button>
            <button class="btn btn-info" onclick="generateMealReport()">📊 Meal Report</button>
        </div>
    </div>

    <!-- Meal Statistics -->
    <div class="meal-stats-grid">
        <div class="stat-card meals-served">
            <div class="stat-icon">🍽️</div>
            <div class="stat-content">
                <h3><?= number_format($meal_stats['meals_served_today']) ?></h3>
                <p>Meals Served Today</p>
                <span class="stat-change">Avg: <?= number_format($meal_stats['average_daily_meals']) ?>/day</span>
            </div>
        </div>
        
        <div class="stat-card students-enrolled">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?= $meal_stats['total_students_enrolled'] ?></h3>
                <p>Students Enrolled</p>
                <span class="stat-change">All meal plans</span>
            </div>
        </div>
        
        <div class="stat-card satisfaction">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
                <h3><?= $meal_stats['student_satisfaction'] ?>%</h3>
                <p>Satisfaction Rate</p>
                <span class="stat-change">Based on feedback</span>
            </div>
        </div>
        
        <div class="stat-card budget">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3><?= $meal_stats['budget_utilized'] ?>%</h3>
                <p>Budget Utilized</p>
                <span class="stat-change">KSh <?= number_format($meal_stats['monthly_food_budget']) ?> total</span>
            </div>
        </div>
    </div>

    <!-- Meal Management Tabs -->
    <div class="meal-tabs">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="showMealTab('plans')">Meal Plans</button>
            <button class="tab-button" onclick="showMealTab('menu')">Weekly Menu</button>
            <button class="tab-button" onclick="showMealTab('feedback')">Student Feedback</button>
            <button class="tab-button" onclick="showMealTab('dietary')">Dietary Requirements</button>
            <button class="tab-button" onclick="showMealTab('inventory')">Inventory</button>
        </div>

        <!-- Meal Plans Tab -->
        <div id="plans" class="tab-content active">
            <div class="meal-plans-section">
                <h2>Available Meal Plans</h2>
                <div class="meal-plans-grid">
                    <?php foreach ($meal_plans as $plan): ?>
                    <div class="meal-plan-card <?= $plan['status'] ?>">
                        <div class="plan-header">
                            <h3><?= htmlspecialchars($plan['name']) ?></h3>
                            <span class="plan-status <?= $plan['status'] ?>"><?= ucfirst($plan['status']) ?></span>
                        </div>
                        
                        <div class="plan-content">
                            <p class="plan-description"><?= htmlspecialchars($plan['description']) ?></p>
                            
                            <div class="plan-details">
                                <div class="detail-item">
                                    <span class="detail-icon">🍽️</span>
                                    <span class="detail-text"><?= $plan['meals_per_day'] ?> meals per day</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">💰</span>
                                    <span class="detail-text">KSh <?= number_format($plan['monthly_cost']) ?>/month</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">👥</span>
                                    <span class="detail-text"><?= $plan['active_students'] ?> students enrolled</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="plan-actions">
                            <button class="btn btn-primary" onclick="editMealPlan(<?= $plan['id'] ?>)">Edit Plan</button>
                            <button class="btn btn-secondary" onclick="viewPlanDetails(<?= $plan['id'] ?>)">View Details</button>
                            <?php if ($plan['status'] === 'active'): ?>
                            <button class="btn btn-warning" onclick="deactivatePlan(<?= $plan['id'] ?>)">Deactivate</button>
                            <?php else: ?>
                            <button class="btn btn-success" onclick="activatePlan(<?= $plan['id'] ?>)">Activate</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Weekly Menu Tab -->
        <div id="menu" class="tab-content">
            <div class="menu-section">
                <div class="menu-header">
                    <h2>Weekly Menu</h2>
                    <div class="menu-controls">
                        <select class="menu-select">
                            <option>Current Week</option>
                            <option>Next Week</option>
                            <option>Previous Week</option>
                        </select>
                        <button class="btn btn-primary" onclick="editMenu()">Edit Menu</button>
                    </div>
                </div>
                
                <div class="weekly-menu-grid">
                    <?php foreach ($weekly_menu as $day => $meals): ?>
                    <div class="day-menu-card">
                        <div class="day-header">
                            <h3><?= $day ?></h3>
                            <span class="day-date"><?= date('M j', strtotime($day . ' this week')) ?></span>
                        </div>
                        
                        <div class="meals-list">
                            <div class="meal-item breakfast">
                                <div class="meal-type">🌅 Breakfast</div>
                                <div class="meal-description"><?= htmlspecialchars($meals['breakfast']) ?></div>
                            </div>
                            
                            <div class="meal-item lunch">
                                <div class="meal-type">☀️ Lunch</div>
                                <div class="meal-description"><?= htmlspecialchars($meals['lunch']) ?></div>
                            </div>
                            
                            <div class="meal-item dinner">
                                <div class="meal-type">🌙 Dinner</div>
                                <div class="meal-description"><?= htmlspecialchars($meals['dinner']) ?></div>
                            </div>
                        </div>
                        
                        <div class="day-actions">
                            <button class="btn btn-sm btn-secondary" onclick="editDayMenu('<?= $day ?>')">Edit Day</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Student Feedback Tab -->
        <div id="feedback" class="tab-content">
            <div class="feedback-section">
                <div class="feedback-header">
                    <h2>Student Meal Feedback</h2>
                    <div class="feedback-summary">
                        <div class="summary-item">
                            <span class="summary-label">Average Rating:</span>
                            <span class="summary-value">4.2/5 ⭐</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Reviews:</span>
                            <span class="summary-value">1,247</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">This Week:</span>
                            <span class="summary-value">89 reviews</span>
                        </div>
                    </div>
                </div>
                
                <div class="feedback-list">
                    <?php foreach ($meal_feedback as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header-info">
                            <div class="student-info">
                                <h4><?= htmlspecialchars($feedback['student_name']) ?></h4>
                                <span class="meal-plan-badge"><?= htmlspecialchars($feedback['meal_plan']) ?> Plan</span>
                            </div>
                            <div class="feedback-meta">
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $feedback['rating'] ? 'filled' : '' ?>">⭐</span>
                                    <?php endfor; ?>
                                </div>
                                <div class="feedback-date"><?= date('M j, Y', strtotime($feedback['date'])) ?></div>
                            </div>
                        </div>
                        
                        <div class="feedback-content">
                            <div class="meal-info">
                                <span class="meal-type-label"><?= htmlspecialchars($feedback['meal_type']) ?></span>
                            </div>
                            <p class="feedback-comment"><?= htmlspecialchars($feedback['comment']) ?></p>
                        </div>
                        
                        <div class="feedback-actions">
                            <button class="btn btn-sm btn-primary" onclick="respondToFeedback('<?= $feedback['student_name'] ?>')">Respond</button>
                            <button class="btn btn-sm btn-secondary" onclick="markAsAddressed('<?= $feedback['student_name'] ?>')">Mark Addressed</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Dietary Requirements Tab -->
        <div id="dietary" class="tab-content">
            <div class="dietary-section">
                <h2>Dietary Requirements & Restrictions</h2>
                
                <div class="dietary-overview">
                    <div class="dietary-chart">
                        <h3>Distribution of Dietary Needs</h3>
                        <canvas id="dietaryChart" width="400" height="300"></canvas>
                    </div>
                    
                    <div class="dietary-stats">
                        <h3>Dietary Statistics</h3>
                        <div class="dietary-list">
                            <?php foreach ($dietary_requirements as $type => $count): ?>
                            <div class="dietary-item">
                                <div class="dietary-type">
                                    <span class="dietary-icon">
                                        <?php
                                        $icons = [
                                            'vegetarian' => '🥬',
                                            'vegan' => '🌱',
                                            'gluten_free' => '🚫',
                                            'diabetic' => '🩺',
                                            'allergies' => '⚠️',
                                            'halal' => '☪️',
                                            'no_restrictions' => '✅'
                                        ];
                                        echo $icons[$type] ?? '📋';
                                        ?>
                                    </span>
                                    <span class="dietary-label"><?= ucwords(str_replace('_', ' ', $type)) ?></span>
                                </div>
                                <div class="dietary-count">
                                    <span class="count-number"><?= $count ?></span>
                                    <span class="count-percentage"><?= round(($count / $meal_stats['total_students_enrolled']) * 100, 1) ?>%</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="dietary-actions">
                    <button class="btn btn-primary" onclick="addDietaryRequirement()">Add New Requirement</button>
                    <button class="btn btn-secondary" onclick="updateDietaryMenu()">Update Special Menus</button>
                    <button class="btn btn-info" onclick="generateDietaryReport()">Generate Report</button>
                </div>
            </div>
        </div>

        <!-- Inventory Tab -->
        <div id="inventory" class="tab-content">
            <div class="inventory-section">
                <div class="inventory-header">
                    <h2>Food Inventory Management</h2>
                    <div class="inventory-controls">
                        <button class="btn btn-primary" onclick="addInventoryItem()">Add Item</button>
                        <button class="btn btn-secondary" onclick="updateStock()">Update Stock</button>
                        <button class="btn btn-warning" onclick="generatePurchaseOrder()">Purchase Order</button>
                    </div>
                </div>
                
                <div class="inventory-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                                <th>Status</th>
                                <th>Unit Cost</th>
                                <th>Supplier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_items as $item): ?>
                            <tr class="inventory-row">
                                <td><?= htmlspecialchars($item['item']) ?></td>
                                <td><?= $item['current_stock'] ?></td>
                                <td><?= $item['minimum_stock'] ?></td>
                                <td>
                                    <?php
                                    $status = $item['current_stock'] <= $item['minimum_stock'] ? 'low' : 'good';
                                    $statusText = $item['current_stock'] <= $item['minimum_stock'] ? 'Low Stock' : 'In Stock';
                                    ?>
                                    <span class="stock-status <?= $status ?>"><?= $statusText ?></span>
                                </td>
                                <td>KSh <?= number_format($item['unit_cost']) ?></td>
                                <td><?= htmlspecialchars($item['supplier']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-primary" onclick="updateItemStock('<?= $item['item'] ?>')">Update</button>
                                        <button class="btn btn-sm btn-secondary" onclick="viewItemHistory('<?= $item['item'] ?>')">History</button>
                                        <?php if ($status === 'low'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="orderItem('<?= $item['item'] ?>')">Order</button>
                                        <?php endif; ?>
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

<!-- Meal Plan Modal -->
<div id="mealPlanModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add/Edit Meal Plan</h3>
            <span class="close" onclick="closeModal('mealPlanModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="meal-plan-form">
                <div class="form-group">
                    <label>Plan Name:</label>
                    <input type="text" id="planName" placeholder="Enter plan name">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea id="planDescription" rows="3" placeholder="Plan description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Meals per Day:</label>
                        <select id="mealsPerDay">
                            <option value="2">2 Meals</option>
                            <option value="3">3 Meals</option>
                            <option value="4">4 Meals</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monthly Cost (KSh):</label>
                        <input type="number" id="monthlyCost" placeholder="0">
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('mealPlanModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveMealPlan()">Save Plan</button>
        </div>
    </div>
</div>

<script>
// Tab functionality
function showMealTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

// Meal plan functions
function addMealPlan() {
    document.getElementById('mealPlanModal').style.display = 'block';
}

function editMealPlan(planId) {
    document.getElementById('mealPlanModal').style.display = 'block';
    // Load plan data for editing
    console.log('Editing meal plan:', planId);
}

function viewPlanDetails(planId) {
    alert('Viewing details for meal plan: ' + planId);
}

function deactivatePlan(planId) {
    if (confirm('Are you sure you want to deactivate this meal plan?')) {
        alert('Meal plan deactivated');
    }
}

function activatePlan(planId) {
    if (confirm('Are you sure you want to activate this meal plan?')) {
        alert('Meal plan activated');
    }
}

function saveMealPlan() {
    const name = document.getElementById('planName').value;
    const description = document.getElementById('planDescription').value;
    
    if (!name || !description) {
        alert('Please fill in all required fields');
        return;
    }
    
    alert('Meal plan saved successfully!');
    closeModal('mealPlanModal');
}

// Menu functions
function editMenu() {
    alert('Opening menu editor...');
}

function editDayMenu(day) {
    alert('Editing menu for ' + day);
}

function updateMenu() {
    alert('Updating weekly menu...');
}

// Feedback functions
function respondToFeedback(studentName) {
    alert('Responding to feedback from ' + studentName);
}

function markAsAddressed(studentName) {
    if (confirm('Mark this feedback as addressed?')) {
        alert('Feedback marked as addressed');
    }
}

// Dietary functions
function addDietaryRequirement() {
    alert('Adding new dietary requirement...');
}

function updateDietaryMenu() {
    alert('Updating special dietary menus...');
}

function generateDietaryReport() {
    alert('Generating dietary requirements report...');
}

// Inventory functions
function addInventoryItem() {
    alert('Adding new inventory item...');
}

function updateStock() {
    alert('Updating stock levels...');
}

function generatePurchaseOrder() {
    alert('Generating purchase order...');
}

function updateItemStock(item) {
    const newStock = prompt('Enter new stock level for ' + item + ':');
    if (newStock !== null && newStock !== '') {
        alert('Stock updated for ' + item);
    }
}

function viewItemHistory(item) {
    alert('Viewing history for ' + item);
}

function orderItem(item) {
    if (confirm('Create purchase order for ' + item + '?')) {
        alert('Purchase order created for ' + item);
    }
}

// Modal functions
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Report functions
function generateMealReport() {
    alert('Generating comprehensive meal report...');
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Meal management page loaded');
});
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>

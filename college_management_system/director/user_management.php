<?php
/**
 * User Management System
 * Allows director to create users and assign roles
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../header.php';

// Require director role
Authentication::requireRole('director');

$user = Authentication::getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
        Security::logSecurityEvent('csrf_token_mismatch', 'User management form', 'WARNING');
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_user':
                $result = createNewUser($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update_user':
                $result = updateUserRole($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'deactivate_user':
                $result = deactivateUser($_POST['user_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'activate_user':
                $result = activateUser($_POST['user_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'reset_password':
                $result = resetUserPassword($_POST['user_id']);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

$users = fetchAll("
    SELECT u.*, 
           CASE 
               WHEN u.role = 'student' THEN s.first_name
               WHEN u.role = 'teacher' THEN t.first_name
               ELSE u.username
           END as first_name,
           CASE 
               WHEN u.role = 'student' THEN s.last_name
               WHEN u.role = 'teacher' THEN t.last_name
               ELSE ''
           END as last_name
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id AND u.role = 'student'
    LEFT JOIN teachers t ON u.id = t.user_id AND u.role = 'teacher'
    ORDER BY u.created_at DESC
");

$available_roles = [
    'student' => 'Student',
    'teacher' => 'Teacher',
    'headteacher' => 'Head Teacher',
    'registrar' => 'Registrar',
    'accounts' => 'Accounts Officer',
    'reception' => 'Reception',
    'hr' => 'Human Resources',
    'hostel' => 'Hostel Manager',
    'director' => 'Director'
];

/**
 * Create new user function
 */
function createNewUser($data) {
    try {
        // Sanitize input
        $username = Security::sanitizeInput($data['username']);
        $email = Security::sanitizeInput($data['email']);
        $role = Security::sanitizeInput($data['role']);
        $first_name = Security::sanitizeInput($data['first_name'] ?? '');
        $last_name = Security::sanitizeInput($data['last_name'] ?? '');
        $phone = Security::sanitizeInput($data['phone'] ?? '');
        $password = $data['password'];
        
        // Validate input
        if (empty($username) || empty($email) || empty($role) || empty($password)) {
            return ['success' => false, 'message' => 'All required fields must be filled.'];
        }
        
        // Validate email
        if (!Security::validateEmail($email)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        
        // Validate password
        $password_validation = Security::validatePassword($password);
        if ($password_validation !== true) {
            return ['success' => false, 'message' => 'Password requirements: ' . implode(', ', $password_validation)];
        }
        
        // Check if username or email already exists
        if (recordExists('users', 'username', $username)) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        if (recordExists('users', 'email', $email)) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Create user record
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user_id']
        ];
        
        $user_id = insertRecord('users', $user_data);
        
        if ($user_id) {
            // Create role-specific profile if needed
            createRoleProfile($user_id, $role, $first_name, $last_name, $phone, $email);
            
            // Log activity
            Security::logSecurityEvent('user_created', "New user created: {$username} with role: {$role}", 'INFO');
            
            return ['success' => true, 'message' => "User '{$username}' created successfully with role '{$role}'."];
        } else {
            return ['success' => false, 'message' => 'Failed to create user.'];
        }
        
    } catch (Exception $e) {
        error_log("User creation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while creating the user.'];
    }
}

/**
 * Create role-specific profile
 */
function createRoleProfile($user_id, $role, $first_name, $last_name, $phone, $email) {
    try {
        switch ($role) {
            case 'student':
                $student_data = [
                    'user_id' => $user_id,
                    'student_id' => 'STU' . str_pad($user_id, 6, '0', STR_PAD_LEFT),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                insertRecord('students', $student_data);
                break;
                
            case 'teacher':
                $teacher_data = [
                    'user_id' => $user_id,
                    'employee_id' => 'TEA' . str_pad($user_id, 6, '0', STR_PAD_LEFT),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                insertRecord('teachers', $teacher_data);
                break;
                
            default:
                // For other roles, create a general profile
                $profile_data = [
                    'user_id' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                insertRecord('user_profiles', $profile_data);
                break;
        }
    } catch (Exception $e) {
        error_log("Profile creation error: " . $e->getMessage());
    }
}

/**
 * Update user role
 */
function updateUserRole($data) {
    try {
        $user_id = (int)$data['user_id'];
        $new_role = Security::sanitizeInput($data['new_role']);
        
        if (empty($user_id) || empty($new_role)) {
            return ['success' => false, 'message' => 'Invalid user ID or role.'];
        }
        
        // Get current user data
        $current_user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$current_user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Prevent changing director role (security measure)
        if ($current_user['role'] === 'director' && $new_role !== 'director') {
            return ['success' => false, 'message' => 'Cannot change director role for security reasons.'];
        }
        
        // Update user role
        updateRecord('users', ['role' => $new_role], 'id', $user_id);
        
        // Log activity
        Security::logSecurityEvent('user_role_updated', "User ID {$user_id} role changed from {$current_user['role']} to {$new_role}", 'INFO');
        
        return ['success' => true, 'message' => "User role updated to '{$new_role}' successfully."];
        
    } catch (Exception $e) {
        error_log("Role update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating the role.'];
    }
}

/**
 * Deactivate user
 */
function deactivateUser($user_id) {
    try {
        $user_id = (int)$user_id;
        
        // Get user data
        $user_data = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user_data) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Prevent deactivating director (security measure)
        if ($user_data['role'] === 'director') {
            return ['success' => false, 'message' => 'Cannot deactivate director account for security reasons.'];
        }
        
        // Update user status
        updateRecord('users', ['status' => 'inactive'], 'id', $user_id);
        
        // Log activity
        Security::logSecurityEvent('user_deactivated', "User {$user_data['username']} deactivated", 'INFO');
        
        return ['success' => true, 'message' => "User '{$user_data['username']}' deactivated successfully."];
        
    } catch (Exception $e) {
        error_log("User deactivation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while deactivating the user.'];
    }
}

/**
 * Activate user
 */
function activateUser($user_id) {
    try {
        $user_id = (int)$user_id;
        
        // Get user data
        $user_data = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user_data) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Update user status
        updateRecord('users', ['status' => 'active'], 'id', $user_id);
        
        // Log activity
        Security::logSecurityEvent('user_activated', "User {$user_data['username']} activated", 'INFO');
        
        return ['success' => true, 'message' => "User '{$user_data['username']}' activated successfully."];
        
    } catch (Exception $e) {
        error_log("User activation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while activating the user.'];
    }
}

/**
 * Reset user password
 */
function resetUserPassword($user_id) {
    try {
        $user_id = (int)$user_id;
        
        // Get user data
        $user_data = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        if (!$user_data) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Generate temporary password
        $temp_password = 'Temp' . rand(1000, 9999) . '!';
        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
        
        // Update password
        updateRecord('users', ['password' => $hashed_password], 'id', $user_id);
        
        // Log activity
        Security::logSecurityEvent('password_reset', "Password reset for user {$user_data['username']}", 'INFO');
        
        return ['success' => true, 'message' => "Password reset successfully. Temporary password: {$temp_password}"];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while resetting the password.'];
    }
}

$csrf_token = Security::generateCSRFToken();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>User Management</h1>
        <p>Create and manage system users and their roles</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Create New User Section -->
    <div class="section-card">
        <div class="section-header">
            <h2>Create New User</h2>
            <button class="btn btn-primary" onclick="toggleSection('create-user-form')">
                <span id="create-toggle-text">Show Form</span>
            </button>
        </div>
        
        <div id="create-user-form" class="form-section" style="display: none;">
            <form method="POST" class="user-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create_user">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required 
                               pattern="[a-zA-Z0-9_]{3,20}" 
                               title="3-20 characters, letters, numbers, and underscores only">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s()]+">
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <?php foreach ($available_roles as $role_key => $role_name): ?>
                                <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" 
                           title="Minimum 8 characters with uppercase, lowercase, number, and special character">
                    <div class="password-requirements">
                        <small>Password must contain: 8+ characters, uppercase, lowercase, number, special character</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create User</button>
                    <button type="reset" class="btn btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List Section -->
    <div class="section-card">
        <div class="section-header">
            <h2>Existing Users (<?php echo count($users); ?>)</h2>
            <div class="search-box">
                <input type="text" id="user-search" placeholder="Search users..." onkeyup="filterUsers()">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="users-table" id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user_item): ?>
                    <tr class="user-row" data-user-id="<?php echo $user_item['id']; ?>">
                        <td><?php echo $user_item['id']; ?></td>
                        <td class="username"><?php echo htmlspecialchars($user_item['username']); ?></td>
                        <td class="name">
                            <?php 
                            $full_name = trim($user_item['first_name'] . ' ' . $user_item['last_name']);
                            echo htmlspecialchars($full_name ?: 'N/A'); 
                            ?>
                        </td>
                        <td class="email"><?php echo htmlspecialchars($user_item['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $user_item['role']; ?>">
                                <?php echo $available_roles[$user_item['role']] ?? $user_item['role']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $user_item['status']; ?>">
                                <?php echo ucfirst($user_item['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user_item['created_at'])); ?></td>
                        <td class="actions">
                            <div class="action-buttons">
                                <?php if ($user_item['role'] !== 'director'): ?>
                                    <button class="btn btn-sm btn-secondary" 
                                            onclick="showRoleModal(<?php echo $user_item['id']; ?>, '<?php echo $user_item['role']; ?>')">
                                        Change Role
                                    </button>
                                    
                                    <?php if ($user_item['status'] === 'active'): ?>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="confirmAction('deactivate', <?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['username']); ?>')">
                                            Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="confirmAction('activate', <?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['username']); ?>')">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="confirmAction('reset_password', <?php echo $user_item['id']; ?>, '<?php echo htmlspecialchars($user_item['username']); ?>')">
                                        Reset Password
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">Protected Account</span>
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

<!-- Role Change Modal -->
<div id="role-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change User Role</h3>
            <span class="close" onclick="closeModal('role-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="role-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="role-user-id">
                
                <div class="form-group">
                    <label for="new_role">New Role:</label>
                    <select name="new_role" id="new_role" required>
                        <?php foreach ($available_roles as $role_key => $role_name): ?>
                            <option value="<?php echo $role_key; ?>"><?php echo $role_name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Role</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('role-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirm-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="confirm-title">Confirm Action</h3>
            <span class="close" onclick="closeModal('confirm-modal')">&times;</span>
        </div>
        <div class="modal-body">
            <p id="confirm-message"></p>
            <form method="POST" id="confirm-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" id="confirm-action">
                <input type="hidden" name="user_id" id="confirm-user-id">
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-danger" id="confirm-button">Confirm</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('confirm-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 30px;
}

.dashboard-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.section-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    overflow: hidden;
}

.section-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h2 {
    margin: 0;
    color: #2c3e50;
}

.form-section {
    padding: 20px;
}

.user-form {
    max-width: 800px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #2c3e50;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.password-requirements {
    margin-top: 5px;
}

.password-requirements small {
    color: #7f8c8d;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
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

.btn-success {
    background-color: #27ae60;
    color: white;
}

.btn-success:hover {
    background-color: #229954;
}

.btn-warning {
    background-color: #f39c12;
    color: white;
}

.btn-warning:hover {
    background-color: #e67e22;
}

.btn-danger {
    background-color: #e74c3c;
    color: white;
}

.btn-danger:hover {
    background-color: #c0392b;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.search-box {
    display: flex;
    align-items: center;
}

.search-box input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 250px;
}

.table-responsive {
    overflow-x: auto;
    padding: 20px;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.users-table th,
.users-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.users-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.users-table tr:hover {
    background-color: #f8f9fa;
}

.role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.role-student { background-color: #e3f2fd; color: #1976d2; }
.role-teacher { background-color: #f3e5f5; color: #7b1fa2; }
.role-headteacher { background-color: #fff3e0; color: #f57c00; }
.role-registrar { background-color: #e8f5e8; color: #388e3c; }
.role-accounts { background-color: #fff8e1; color: #f9a825; }
.role-reception { background-color: #fce4ec; color: #c2185b; }
.role-hr { background-color: #e0f2f1; color: #00796b; }
.role-hostel { background-color: #f1f8e9; color: #689f38; }
.role-director { background-color: #ffebee; color: #d32f2f; }

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {

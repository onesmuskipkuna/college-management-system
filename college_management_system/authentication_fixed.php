<?php
/**
 * Authentication System
 * Handles user login, logout, session management, and role-based access control
 */

// Define access constant
if (!defined('CMS_ACCESS')) {
    define('CMS_ACCESS', true);
}

// Include required files
require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Authentication {
    
    /**
     * Generate OTP for the user
     */
    private static function generateOTP($user) {
        $otp = mt_rand(100000, 999999); // Generate a 6-digit OTP
        $_SESSION['otp'] = $otp; // Store OTP in session
        $_SESSION['otp_expiry'] = time() + 300; // Set expiry time (5 minutes)

        // Here you would send the OTP to the user's registered contact (email/SMS)
        // For example: sendOTP($user['email'], $otp);
    }

    /**
     * Verify the provided OTP
     */
    public static function verifyOTP($user, $otp) {
        if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
            return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
        }

        if ($_SESSION['otp'] == $otp) {
            // Clear OTP from session
            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            self::createSession($user); // Create session for the user
            return ['success' => true, 'message' => 'OTP verified successfully.'];
        } else {
            return ['success' => false, 'message' => 'Invalid OTP. Please try again.'];
        }
    }

    /**
     * Login user with username/email and password
     */
    public static function login($username, $password, $remember = false) {
        global $db;
        
        try {
            // Check for too many failed attempts
            if (self::isAccountLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Account temporarily locked due to too many failed attempts. Please try again later.'
                ];
            }
            
            // Find user by username or email
            $sql = "SELECT u.*, 
                           CASE 
                               WHEN u.role = 'student' THEN s.first_name
                               WHEN u.role = 'teacher' THEN t.first_name
                               ELSE u.username
                           END as first_name,
                           CASE 
                               WHEN u.role = 'student' THEN s.last_name
                               WHEN u.role = 'teacher' THEN t.last_name
                               ELSE ''
                           END as last_name,
                           CASE 
                               WHEN u.role = 'student' THEN s.id
                               WHEN u.role = 'teacher' THEN t.id
                               ELSE NULL
                           END as profile_id
                    FROM users u
                    LEFT JOIN students s ON u.id = s.user_id AND u.role = 'student'
                    LEFT JOIN teachers t ON u.id = t.user_id AND u.role = 'teacher'
                    WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'";
            
            $user = fetchOne($sql, [$username, $username]);
            
            if (!$user) {
                self::recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                self::recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            // Clear failed attempts
            self::clearFailedAttempts($username);
            
            // Check if 2FA is enabled
            if ($user['two_factor_enabled']) {
                // Generate OTP and send it to the user
                self::generateOTP($user);
                return [
                    'success' => false,
                    'otp_required' => true,
                    'message' => 'An OTP has been sent to your registered contact.'
                ];
            } else {
                // Create session
                self::createSession($user);
            
                // Assign super admin role if user is a director
                if ($user['role'] == 'director') {
                    $_SESSION['role'] = 'super_admin';
                }
                if ($remember) {
                    self::setRememberMeCookie($user['id']);
                }
                
                // Log successful login
                if (function_exists('logActivity')) {
                    logActivity($user['id'], 'login', 'User logged in successfully');
                }
                
                return [
                    'success' => true,
                    'message' => 'Login successful.',
                    'user' => $user,
                    'redirect' => self::getRedirectUrl($user['role'])
                ];
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ];
        }
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
            self::clearRememberToken($_SESSION['user_id'] ?? null);
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
            // Check session timeout
            if (defined('SESSION_TIMEOUT') && isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
                self::logout();
                return false;
            }
            
            // Update last activity
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Check remember me cookie
        return self::checkRememberMe();
    }
    
    /**
     * Get current user information
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'first_name' => $_SESSION['first_name'] ?? '',
            'last_name' => $_SESSION['last_name'] ?? '',
            'profile_id' => $_SESSION['profile_id'] ?? null
        ];
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        $user = self::getCurrentUser();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole($roles) {
        $user = self::getCurrentUser();
        return $user && in_array($user['role'], $roles);
    }
    
    /**
     * Require login - redirect to login if not authenticated
     */
    public static function requireLogin($redirect_url = null) {
        if (!self::isLoggedIn()) {
            $redirect = $redirect_url ?: $_SERVER['REQUEST_URI'];
            header("Location: /college_management_system/login.php?redirect=" . urlencode($redirect));
            exit;
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role, $redirect_url = null) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            if ($redirect_url) {
                header("Location: " . $redirect_url);
            } else {
                http_response_code(403);
                die("Access denied. Insufficient permissions.");
            }
            exit;
        }
    }
    
    /**
     * Require any of the specified roles
     */
    public static function requireAnyRole($roles, $redirect_url = null) {
        self::requireLogin();
        
        if (!self::hasAnyRole($roles)) {
            if ($redirect_url) {
                header("Location: " . $redirect_url);
            } else {
                http_response_code(403);
                die("Access denied. Insufficient permissions.");
            }
            exit;
        }
    }
    
    /**
     * Change user password
     */
    public static function changePassword($user_id, $current_password, $new_password) {
        try {
            // Get current password hash
            $user = fetchOne("SELECT password FROM users WHERE id = ?", [$user_id]);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found.'];
            }
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect.'];
            }
            
            // Validate new password
            $min_length = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8;
            if (strlen($new_password) < $min_length) {
                return ['success' => false, 'message' => 'New password must be at least ' . $min_length . ' characters long.'];
            }
            
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            updateRecord('users', ['password' => $new_hash], 'id', $user_id);
            
            // Log password change
            if (function_exists('logActivity')) {
                logActivity($user_id, 'password_change', 'Password changed successfully');
            }
            
            return ['success' => true, 'message' => 'Password changed successfully.'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while changing password.'];
        }
    }
    
    /**
     * Reset password (for admin use)
     */
    public static function resetPassword($user_id, $new_password) {
        try {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            updateRecord('users', ['password' => $new_hash], 'id', $user_id);
            
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'] ?? 0, 'password_reset', "Password reset for user ID: {$user_id}");
            }
            
            return ['success' => true, 'message' => 'Password reset successfully.'];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while resetting password.'];
        }
    }
    
    /**
     * Private helper methods
     */
    
    private static function createSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['profile_id'] = $user['profile_id'];
        $_SESSION['last_activity'] = time();
        
        if (function_exists('generateToken')) {
            $_SESSION['csrf_token'] = generateToken();
        }
    }
    
    private static function getRedirectUrl($role) {
        $redirects = [
            'student' => '/college_management_system/student/dashboard.php',
            'teacher' => '/college_management_system/teacher/dashboard.php',
            'headteacher' => '/college_management_system/headteacher/dashboard.php',
            'registrar' => '/college_management_system/registrar/dashboard.php',
            'accounts' => '/college_management_system/accounts/dashboard.php',
            'reception' => '/college_management_system/reception/dashboard.php',
            'hr' => '/college_management_system/hr/dashboard.php',
            'hostel' => '/college_management_system/hostel/dashboard.php',
            'director' => '/college_management_system/director/dashboard.php'
        ];
        
        return $redirects[$role] ?? '/college_management_system/dashboard.php';
    }
    
    private static function isAccountLocked($username) {
        if (!defined('false')) {
            return false; // Skip in demo mode
        }
        
        try {
            $lockout_duration = defined('LOCKOUT_DURATION') ? LOCKOUT_DURATION : 900;
            $max_attempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
            
            $sql = "SELECT COUNT(*) as attempts FROM login_attempts 
                    WHERE username = ? AND attempt_time > datetime('now', '-{$lockout_duration} seconds')";
            
            $result = fetchOne($sql, [$username]);
            return $result && $result['attempts'] >= $max_attempts;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private static function recordFailedAttempt($username) {
        try {
            if (defined('false')) {
                return; // Skip in demo mode
            }
            
            $sql = "INSERT INTO login_attempts (username, ip_address, attempt_time) VALUES (?, ?, datetime('now'))";
            executeQuery($sql, [$username, $_SERVER['REMOTE_ADDR'] ?? 'Unknown']);
        } catch (Exception $e) {
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
    }
    
    private static function clearFailedAttempts($username) {
        try {
            if (defined('false')) {
                return; // Skip in demo mode
            }
            
            executeQuery("DELETE FROM login_attempts WHERE username = ?", [$username]);
        } catch (Exception $e) {
            error_log("Failed to clear login attempts: " . $e->getMessage());
        }
    }
    
    private static function setRememberMeCookie($user_id) {
        if (defined('false')) {
            return; // Skip in demo mode
        }
        
        if (!function_exists('generateToken')) {
            return;
        }
        
        $token = generateToken(64);
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        try {
            // Store token in database
            $sql = "INSERT OR REPLACE INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, datetime(?, 'unixepoch'))";
            executeQuery($sql, [$user_id, hash('sha256', $token), $expires]);
            
            // Set cookie
            setcookie('remember_token', $token, $expires, '/', '', false, true);
            
        } catch (Exception $e) {
            error_log("Failed to set remember me cookie: " . $e->getMessage());
        }
    }
    
    private static function checkRememberMe() {
        if (defined('false') || !isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        try {
            $token_hash = hash('sha256', $_COOKIE['remember_token']);
            
            $sql = "SELECT u.* FROM users u 
                    JOIN remember_tokens rt ON u.id = rt.user_id 
                    WHERE rt.token = ? AND rt.expires_at > datetime('now') AND u.status = 'active'";
            
            $user = fetchOne($sql, [$token_hash]);
            
            if ($user) {
                self::createSession($user);
                return true;
            } else {
                // Invalid or expired token
                setcookie('remember_token', '', time() - 3600, '/');
            }
            
        } catch (Exception $e) {
            error_log("Remember me check error: " . $e->getMessage());
        }
        
        return false;
    }
    
    private static function clearRememberToken($user_id) {
        if ($user_id && !defined('false')) {
            try {
                executeQuery("DELETE FROM remember_tokens WHERE user_id = ?", [$user_id]);
            } catch (Exception $e) {
                error_log("Failed to clear remember token: " . $e->getMessage());
            }
        }
    }
}

// Create required tables if they don't exist (skip in demo mode)
if (!defined('false') && $db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            ip_address TEXT NOT NULL,
            attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
            user_id INTEGER PRIMARY KEY,
            token TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
    } catch (Exception $e) {
        error_log("Failed to create authentication tables: " . $e->getMessage());
    }
}
?>

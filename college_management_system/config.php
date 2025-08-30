<?php
/**
 * Configuration File
 * Contains database and security settings
 */

// Prevent direct access
if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

// Database Configuration - Update these for your MySQL setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_NAME', 'college_db');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

// Security Configuration
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-here');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
define('CHECK_IP_CONSISTENCY', false); // Set to true for stricter security

// Demo Mode (set to false in production)
define('DEMO_MODE', false);

// Admin Configuration
define('ADMIN_EMAIL', 'admin@college.edu');
define('SYSTEM_NAME', 'College Management System');

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Development Mode (set to false in production)
define('DEVELOPMENT_MODE', true);

// Google Analytics (optional)
define('GOOGLE_ANALYTICS_ID', '');

// Error Reporting
if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Africa/Nairobi');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Helper function to get current academic year
function getCurrentAcademicYear() {
    $current_month = date('n');
    $current_year = date('Y');
    
    if ($current_month >= 9) {
        return $current_year . '/' . ($current_year + 1);
    } else {
        return ($current_year - 1) . '/' . $current_year;
    }
}

// Helper function to generate secure tokens
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Helper function to log activities
function logActivity($user_id, $action, $details = '') {
    try {
        $log_data = [
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $log_entry = json_encode($log_data) . PHP_EOL;
        file_put_contents(__DIR__ . '/logs/activity.log', $log_entry, FILE_APPEND | LOCK_EX);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/logs';
if (!is_dir($logs_dir)) {
    mkdir($logs_dir, 0755, true);
}

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
    
    // Create subdirectories
    $subdirs = ['assignments', 'certificates', 'materials', 'receipts', 'profiles'];
    foreach ($subdirs as $subdir) {
        $path = UPLOAD_PATH . $subdir;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}

// Security headers function
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Set security headers
setSecurityHeaders();
?>

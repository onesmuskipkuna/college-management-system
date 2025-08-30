<?php
/**
 * Security Framework
 * Comprehensive security measures for the college management system
 */

if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

class Security {
    
    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        // Remove null bytes
        $data = str_replace(chr(0), '', $data);
        
        // Trim whitespace
        $data = trim($data);
        
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (digits only)
     */
    public static function validatePhone($phone) {
        return preg_match('/^[0-9+\-\s()]+$/', $phone);
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($action, $limit = 10, $window = 3600) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
        }
        
        $data = $_SESSION[$key];
        
        // Reset if window expired
        if (time() > $data['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $window];
            $data = $_SESSION[$key];
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = '', $severity = 'INFO') {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? 'anonymous',
            'event' => $event,
            'details' => $details,
            'severity' => $severity
        ];
        
        $log_line = json_encode($log_entry) . PHP_EOL;
        file_put_contents(__DIR__ . '/../logs/security.log', $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Check for suspicious activity
     */
    public static function detectSuspiciousActivity() {
        $suspicious_patterns = [
            'sql_injection' => '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)/i',
            'xss_attempt' => '/<script|javascript:|on\w+\s*=/i',
            'path_traversal' => '/\.\.[\/\\\\]/',
            'command_injection' => '/[;&|`$(){}]/i'
        ];
        
        $request_data = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($request_data as $key => $value) {
            if (is_string($value)) {
                foreach ($suspicious_patterns as $type => $pattern) {
                    if (preg_match($pattern, $value)) {
                        self::logSecurityEvent('suspicious_activity', "Type: {$type}, Key: {$key}, Value: " . substr($value, 0, 100), 'WARNING');
                        return $type;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Secure file upload
     */
    public static function validateFileUpload($file, $allowed_types = [], $max_size = 5242880) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'No file uploaded or invalid upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!empty($allowed_types) && !in_array($mime_type, $allowed_types)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check for malicious content
        $file_content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<script|javascript:/i', $file_content)) {
            $errors[] = 'File contains potentially malicious content';
        }
        
        return $errors;
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($original_filename) {
        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16));
        return $filename . '.' . strtolower($extension);
    }
    
    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_key_change_in_production';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($encrypted_data, $key = null) {
        if ($key === null) {
            $key = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : 'default_key_change_in_production';
        }
        
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Hash sensitive data (one-way)
     */
    public static function hashData($data, $salt = null) {
        if ($salt === null) {
            $salt = bin2hex(random_bytes(16));
        }
        return hash('sha256', $data . $salt) . ':' . $salt;
    }
    
    /**
     * Verify hashed data
     */
    public static function verifyHash($data, $hash) {
        list($stored_hash, $salt) = explode(':', $hash);
        return hash_equals($stored_hash, hash('sha256', $data . $salt));
    }
    
    /**
     * Check for brute force attacks
     */
    public static function checkBruteForce($identifier, $max_attempts = 5, $lockout_time = 900) {
        $key = "brute_force_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
        }
        
        $data = $_SESSION[$key];
        
        // Check if still locked
        if ($data['locked_until'] > time()) {
            return false;
        }
        
        // Reset if lockout period expired
        if ($data['locked_until'] > 0 && $data['locked_until'] <= time()) {
            $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
            return true;
        }
        
        // Check attempts
        if ($data['attempts'] >= $max_attempts) {
            $_SESSION[$key]['locked_until'] = time() + $lockout_time;
            self::logSecurityEvent('brute_force_lockout', "Identifier: {$identifier}", 'WARNING');
            return false;
        }
        
        return true;
    }
    
    /**
     * Record failed attempt
     */
    public static function recordFailedAttempt($identifier) {
        $key = "brute_force_{$identifier}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
        }
        
        $_SESSION[$key]['attempts']++;
        self::logSecurityEvent('failed_attempt', "Identifier: {$identifier}", 'INFO');
    }
    
    /**
     * Clear failed attempts
     */
    public static function clearFailedAttempts($identifier) {
        $key = "brute_force_{$identifier}";
        unset($_SESSION[$key]);
    }
    
    /**
     * Validate session integrity
     */
    public static function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 3600) {
            session_destroy();
            return false;
        }
        
        // Check IP consistency (optional, can cause issues with mobile users)
        if (defined('CHECK_IP_CONSISTENCY') && CHECK_IP_CONSISTENCY) {
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                self::logSecurityEvent('session_hijack_attempt', 'IP address mismatch', 'CRITICAL');
                session_destroy();
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Secure headers
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
        
        // HTTPS enforcement (if using HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Clean old log files
     */
    public static function cleanOldLogs($days = 30) {
        $log_files = [
            __DIR__ . '/../logs/security.log',
            __DIR__ . '/../logs/error.log'
        ];
        
        foreach ($log_files as $log_file) {
            if (file_exists($log_file)) {
                $lines = file($log_file);
                $cutoff_date = date('Y-m-d', strtotime("-{$days} days"));
                $new_lines = [];
                
                foreach ($lines as $line) {
                    $log_data = json_decode($line, true);
                    if ($log_data && isset($log_data['timestamp'])) {
                        if (substr($log_data['timestamp'], 0, 10) >= $cutoff_date) {
                            $new_lines[] = $line;
                        }
                    }
                }
                
                file_put_contents($log_file, implode('', $new_lines));
            }
        }
    }
}

// Helper functions for easy access
function generateCSRFToken() {
    return Security::generateCSRFToken();
}

function verifyCSRFToken($token) {
    return Security::verifyCSRFToken($token);
}

function sanitizeInput($data) {
    return Security::sanitizeInput($data);
}

function logSecurityEvent($event, $details = '', $severity = 'INFO') {
    Security::logSecurityEvent($event, $details, $severity);
}

// Set security headers on every request
Security::setSecurityHeaders();

// Check for suspicious activity
$suspicious = Security::detectSuspiciousActivity();
if ($suspicious) {
    http_response_code(403);
    die('Suspicious activity detected. Access denied.');
}

// Validate session integrity
if (isset($_SESSION['user_id']) && !Security::validateSession()) {
    header('Location: /college_management_system/login.php?session_expired=1');
    exit;
}
?>

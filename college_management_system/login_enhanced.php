<?php
/**
 * Ultra-Secure Enhanced Login Page
 * Advanced security measures and modern UI
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/includes/security.php';

// Start session with enhanced security
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Enhanced security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Redirect if already logged in
if (Authentication::isLoggedIn()) {
    $user = Authentication::getCurrentUser();
    header('Location: ' . Authentication::getRedirectUrl($user['role']));
    exit;
}

$error_message = '';
$success_message = '';
$show_otp_form = false;
$show_captcha = false;
$username_for_otp = '';
$login_attempts = 0;

// Check for suspicious activity
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Enhanced rate limiting with IP tracking
function checkAdvancedRateLimit($ip, $action = 'login') {
    $key = "rate_limit_{$action}_{$ip}";
    $attempts = $_SESSION[$key] ?? 0;
    $last_attempt = $_SESSION[$key . '_time'] ?? 0;
    
    // Reset counter if more than 15 minutes have passed
    if (time() - $last_attempt > 900) {
        $attempts = 0;
    }
    
    // Show CAPTCHA after 3 attempts, block after 5
    if ($attempts >= 5) {
        return ['allowed' => false, 'show_captcha' => true, 'message' => 'Too many failed attempts. Please try again in 15 minutes.'];
    } elseif ($attempts >= 3) {
        return ['allowed' => true, 'show_captcha' => true, 'message' => 'Please complete the security verification.'];
    }
    
    return ['allowed' => true, 'show_captcha' => false, 'message' => ''];
}

// Simple CAPTCHA generation
function generateCaptcha() {
    $captcha_code = '';
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    for ($i = 0; $i < 5; $i++) {
        $captcha_code .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha_code'] = $captcha_code;
    return $captcha_code;
}

// Check rate limiting
$rate_limit_check = checkAdvancedRateLimit($client_ip);
if (!$rate_limit_check['allowed']) {
    $error_message = $rate_limit_check['message'];
} else {
    $show_captcha = $rate_limit_check['show_captcha'];
    if ($show_captcha && empty($_SESSION['captcha_code'])) {
        generateCaptcha();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rate_limit_check['allowed']) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Invalid security token. Please refresh and try again.';
        Security::logSecurityEvent('csrf_token_mismatch', 'Login form', 'WARNING');
    } else {
        // Verify CAPTCHA if required
        if ($show_captcha) {
            $captcha_input = strtoupper(trim($_POST['captcha'] ?? ''));
            $captcha_session = $_SESSION['captcha_code'] ?? '';
            
            if (empty($captcha_input) || $captcha_input !== $captcha_session) {
                $error_message = 'Invalid security code. Please try again.';
                generateCaptcha(); // Generate new CAPTCHA
                
                // Increment failed attempts
                $key = "rate_limit_login_{$client_ip}";
                $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
                $_SESSION[$key . '_time'] = time();
                
                Security::logSecurityEvent('captcha_failed', "IP: {$client_ip}", 'WARNING');
            } else {
                // CAPTCHA verified, clear it
                unset($_SESSION['captcha_code']);
                $show_captcha = false;
            }
        }
        
        if (empty($error_message)) {
            // Sanitize and validate input
            $username = Security::sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // Enhanced input validation
            if (empty($username) || empty($password)) {
                $error_message = 'Please enter both username and password.';
            } elseif (strlen($username) < 3 || strlen($username) > 50) {
                $error_message = 'Username must be between 3 and 50 characters.';
            } elseif (strlen($password) < 6) {
                $error_message = 'Password must be at least 6 characters long.';
            } else {
                // Check for brute force attempts
                if (!Security::checkBruteForce($username)) {
                    $error_message = 'Account temporarily locked due to multiple failed attempts. Please try again later.';
                } else {
                    // Handle OTP verification if needed
                    if (isset($_POST['otp_code'])) {
                        $otp_code = Security::sanitizeInput($_POST['otp_code']);
                        $stored_username = $_SESSION['otp_username'] ?? '';
                        
                        if ($stored_username && $otp_code) {
                            // Get user for OTP verification
                            $user = fetchOne("SELECT * FROM users WHERE username = ? OR email = ?", [$stored_username, $stored_username]);
                            
                            if ($user) {
                                $otp_result = Authentication::verifyOTP($user, $otp_code);
                                
                                if ($otp_result['success']) {
                                    Security::clearFailedAttempts($username);
                                    Security::logSecurityEvent('successful_login', "User: {$username}, IP: {$client_ip}", 'INFO');
                                    
                                    // Clear rate limiting on successful login
                                    $key = "rate_limit_login_{$client_ip}";
                                    unset($_SESSION[$key], $_SESSION[$key . '_time']);
                                    
                                    $redirect_url = $_GET['redirect'] ?? Authentication::getRedirectUrl($user['role']);
                                    header('Location: ' . $redirect_url);
                                    exit;
                                } else {
                                    $error_message = $otp_result['message'];
                                    Security::recordFailedAttempt($username);
                                }
                            }
                        }
                    } else {
                        // Regular login attempt
                        $login_result = Authentication::login($username, $password, $remember);
                        
                        if ($login_result['success']) {
                            Security::clearFailedAttempts($username);
                            Security::logSecurityEvent('successful_login', "User: {$username}, IP: {$client_ip}", 'INFO');
                            
                            // Clear rate limiting on successful login
                            $key = "rate_limit_login_{$client_ip}";
                            unset($_SESSION[$key], $_SESSION[$key . '_time']);
                            
                            $redirect_url = $_GET['redirect'] ?? $login_result['redirect'];
                            header('Location: ' . $redirect_url);
                            exit;
                        } elseif (isset($login_result['otp_required']) && $login_result['otp_required']) {
                            $show_otp_form = true;
                            $username_for_otp = $username;
                            $_SESSION['otp_username'] = $username;
                            $success_message = $login_result['message'];
                        } else {
                            $error_message = $login_result['message'];
                            Security::recordFailedAttempt($username);
                            Security::logSecurityEvent('failed_login', "User: {$username}, IP: {$client_ip}", 'WARNING');
                            
                            // Increment failed attempts for rate limiting
                            $key = "rate_limit_login_{$client_ip}";
                            $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
                            $_SESSION[$key . '_time'] = time();
                            
                            // Check if we need to show CAPTCHA now
                            $new_check = checkAdvancedRateLimit($client_ip);
                            $show_captcha = $new_check['show_captcha'];
                            if ($show_captcha) {
                                generateCaptcha();
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle session expired message
if (isset($_GET['session_expired'])) {
    $error_message = 'Your session has expired. Please log in again.';
}

// Generate CSRF token
$csrf_token = Security::generateCSRFToken();

// Get current attempts for display
$key = "rate_limit_login_{$client_ip}";
$login_attempts = $_SESSION[$key] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - <?php echo SYSTEM_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <!-- Enhanced Security Headers -->
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #10b981;
            --error-color: #ef4444;
            --warning-color: #f59e0b;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --bg-light: #f9fafb;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background particles */
        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .particle:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { width: 60px; height: 60px; left: 20%; animation-delay: 2s; }
        .particle:nth-child(3) { width: 40px; height: 40px; left: 70%; animation-delay: 4s; }
        .particle:nth-child(4) { width: 100px; height: 100px; left: 80%; animation-delay: 1s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 1; }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            padding: 48px;
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--primary-gradient);
            border-radius: 24px 24px 0 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .login-header .logo {
            width: 64px;
            height: 64px;
            background: var(--primary-gradient);
            border-radius: 16px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .login-header h1 {
            color: var(--text-primary);
            margin: 0 0 8px 0;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .security-status {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 16px 0;
            padding: 12px;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--success-color);
        }
        
        .security-status::before {
            content: '🔒';
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .form-group input:invalid {
            border-color: var(--error-color);
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .strength-weak { background: var(--error-color); width: 25%; }
        .strength-fair { background: var(--warning-color); width: 50%; }
        .strength-good { background: #3b82f6; width: 75%; }
        .strength-strong { background: var(--success-color); width: 100%; }
        
        .captcha-container {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .captcha-image {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 4px;
            text-align: center;
            min-width: 120px;
            user-select: none;
        }
        
        .captcha-input {
            flex: 1;
            padding: 12px 16px !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
            gap: 12px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #667eea;
        }
        
        .checkbox-group label {
            margin: 0;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: none;
            letter-spacing: normal;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(-1px);
        }
        
        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert-error::before {
            content: '⚠️';
            font-size: 18px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-success::before {
            content: '✅';
            font-size: 18px;
        }
        
        .security-info {
            margin-top: 32px;
            padding: 24px;
            background: var(--bg-light);
            border-radius: 16px;
            border: 1px solid var(--border-color);
        }
        
        .security-info h3 {
            margin: 0 0 16px 0;
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-info h3::before {
            content: '🛡️';
            font-size: 20px;
        }
        
        .security-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        
        .security-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        
        .security-feature::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
        }
        
        .otp-form {
            text-align: center;
        }
        
        .otp-form h3 {
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: 700;
        }
        
        .otp-form p {
            color: var(--text-secondary);
            margin-bottom: 24px;
            font-size: 16px;
        }
        
        .otp-input {
            font-size: 28px !important;
            text-align: center;
            letter-spacing: 12px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
        
        .back-to-login {
            margin-top: 20px;
        }
        
        .back-to-login button {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-to-login button:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .attempts-warning {
            text-align: center;
            margin-bottom: 16px;
            padding: 12px;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 8px;
            color: var(--warning-color);
            font-size: 14px;
            font-weight: 600;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
                margin: 16px;
                border-radius: 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
            }
            
            .security-features {
                grid-template-columns: 1fr;
            }
        }
        
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced focus styles for accessibility */
        *:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }
        
        /* Smooth transitions for all interactive elements */
        * {
            transition: all 0.2s ease;
        }
    </style>
</head>
<body>
    <div class="bg-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo">CMS</div>
            <h1><?php echo SYSTEM_NAME; ?></h1>
            <p>Ultra-Secure Access Portal</p>
            <div class="security-status">
                SSL Encrypted Connection Active
            </div>
        </div>
        
        <?php if ($login_attempts > 0 && $login_attempts < 5): ?>
            <div class="attempts-warning">
                ⚠️ <?php echo $login_attempts; ?> failed attempt<?php echo $login_attempts > 1 ? 's' : ''; ?> detected. 
                <?php echo (5 - $login_attempts); ?> attempt<?php echo (5 - $login_attempts) > 1 ? 's' : ''; ?> remaining.
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_otp_form): ?>
            <!-- Enhanced OTP Verification Form -->
            <div class="otp-form">
                <h3>🔐 Two-Factor Authentication</h3>
                <p>Enter the 6-digit verification code sent to your registered device.</p>
                
                <form method="POST" id="otpForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username_for_otp); ?>">
                    
                    <div class="form-group">
                        <input type="text" 
                               name="otp_code" 
                               class="otp-input"
                               placeholder="000000"
                               maxlength="6"
                               pattern="[0-9]{6}"
                               required
                               autocomplete="off"
                               inputmode="numeric">
                    </div>
                    
                    <button type="submit" class="btn">
                        <span class="btn-text">Verify & Sign In</span>
                        <div class="loading">
                            <div class="spinner"></div>
                        </div>
                    </button>
                </form>
                
                <div class="back-to-login">
                    <button onclick="location.reload()">← Back to Login</button>
                </div>
            </div>
        <?php else: ?>
            <!-- Enhanced Login Form -->
            <form method="POST" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           autocomplete="username"
                           spellcheck="false"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                </div>
                
                <?php if ($show_captcha): ?>
                    <div class="form-group">
                        <label for="captcha">Security Verification</label>
                        <div class="captcha-container">
                            <div class="captcha-image"><?php echo $_SESSION['captcha_code']; ?></div>

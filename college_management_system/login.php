<?php
/**
 * Secure Login Page
 * Enhanced with comprehensive security measures
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/includes/security.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
$username_for_otp = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    if (!Security::checkRateLimit('login', 10, 300)) {
        $error_message = 'Too many login attempts. Please try again in 5 minutes.';
    } else {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !Security::verifyCSRFToken($_POST['csrf_token'])) {
            $error_message = 'Invalid security token. Please try again.';
            Security::logSecurityEvent('csrf_token_mismatch', 'Login form', 'WARNING');
        } else {
            // Sanitize input
            $username = Security::sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // Validate input
            if (empty($username) || empty($password)) {
                $error_message = 'Please enter both username and password.';
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
                                    Security::logSecurityEvent('successful_login', "User: {$username}", 'INFO');
                                    
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
                            Security::logSecurityEvent('successful_login', "User: {$username}", 'INFO');
                            
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
                            Security::logSecurityEvent('failed_login', "User: {$username}, IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'WARNING');
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .login-header p {
            color: #7f8c8d;
            margin: 0;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:invalid {
            border-color: #e74c3c;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        .checkbox-group label {
            margin: 0;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        
        .security-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .security-info h3 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .security-info ul {
            margin: 0;
            padding-left: 20px;
            color: #7f8c8d;
            font-size: 13px;
        }
        
        .security-info li {
            margin-bottom: 5px;
        }
        
        .otp-form {
            text-align: center;
        }
        
        .otp-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
        }
        
        .back-to-login {
            margin-top: 15px;
        }
        
        .back-to-login button {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .back-to-login button:hover {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .login-header h1 {
                font-size: 24px;
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
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo SYSTEM_NAME; ?></h1>
            <p>Secure Access Portal</p>
        </div>
        
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
            <!-- OTP Verification Form -->
            <div class="otp-form">
                <h3>Enter Verification Code</h3>
                <p>Please enter the 6-digit code sent to your registered contact.</p>
                
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
                               autocomplete="off">
                    </div>
                    
                    <button type="submit" class="btn">
                        <span class="btn-text">Verify Code</span>
                        <div class="loading">
                            <div class="spinner"></div>
                        </div>
                    </button>
                </form>
                
                <div class="back-to-login">
                    <button onclick="location.reload()">Back to Login</button>
                </div>
            </div>
        <?php else: ?>
            <!-- Login Form -->
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="btn">
                    <span class="btn-text">Sign In</span>
                    <div class="loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
        <?php endif; ?>
        
        <div class="security-info">
            <h3>Security Features</h3>
            <ul>
                <li>End-to-end encryption</li>
                <li>Multi-factor authentication</li>
                <li>Brute force protection</li>
                <li>Session security</li>
                <li>Activity monitoring</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Form submission handling
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            // Validate form before submission
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            btn.disabled = true;
            btnText.style.opacity = '0';
            loading.style.display = 'block';
        });
        
        document.getElementById('otpForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn');
            const btnText = btn.querySelector('.btn-text');
            const loading = btn.querySelector('.loading');
            
            const otpCode = this.querySelector('input[name="otp_code"]').value;
            
            if (!otpCode || otpCode.length !== 6) {
                e.preventDefault();
                alert('Please enter a valid 6-digit verification code.');
                return;
            }
            
            btn.disabled = true;
            btnText.style.opacity = '0';
            loading.style.display = 'block';
        });
        
        // OTP input formatting
        const otpInput = document.querySelector('.otp-input');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                this.value = paste.replace(/[^0-9]/g, '').substring(0, 6);
            });
        }
        
        // Auto-focus first input
        document.querySelector('input[type="text"]')?.focus();
        
        // Clear error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            });
        }, 5000);
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Enhanced keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const activeElement = document.activeElement;
                if (activeElement.tagName === 'INPUT') {
                    const form = activeElement.closest('form');
                    if (form) {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn && !submitBtn.disabled) {
                            submitBtn.click();
                        }
                    }
                }
            }
        });
        
        // Security monitoring: Log suspicious activity
        let suspiciousActivity = 0;
        
        document.addEventListener('keydown', function(e) {
            // Detect potential automated tools
            if (e.isTrusted === false) {
                suspiciousActivity++;
                if (suspiciousActivity > 5) {
                    console.warn('Suspicious automated activity detected');
                }
            }
        });
        
        // Performance: Preload critical resources
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = 'css/styles.css';
        link.as = 'style';
        document.head.appendChild(link);
    </script>
</body>
</html>

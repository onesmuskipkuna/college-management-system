<?php
/**
 * Logout Page
 * Handles user logout and session cleanup
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/authentication.php';

// Handle logout
if (Authentication::isLoggedIn()) {
    Authentication::logout();
    
    // Set success message
    session_start();
    $_SESSION['flash_message'] = 'You have been successfully logged out.';
    $_SESSION['flash_type'] = 'success';
}

// Redirect to login page
header("Location: /college_management_system/login.php");
exit;
?>

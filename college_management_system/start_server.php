<?php
/**
 * Development Server Starter
 * Simple script to start PHP built-in server for testing
 */

echo "College Management System - Development Server\n";
echo "==============================================\n\n";

// Check if PHP version is compatible
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "❌ Error: PHP 8.2 or higher is required. Current version: " . PHP_VERSION . "\n";
    echo "Please upgrade to PHP 8.2+ for optimal performance and security.\n";
    exit(1);
}

if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
    echo "✅ PHP Version: " . PHP_VERSION . " (Optimal - PHP 8.3+)\n";
} elseif (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "✅ PHP Version: " . PHP_VERSION . " (Compatible - PHP 8.2+)\n";
    echo "💡 Consider upgrading to PHP 8.3+ for latest features\n";
}

// Check if required extensions are loaded
$required_extensions = ['pdo', 'curl', 'json', 'mbstring', 'openssl', 'fileinfo'];
$missing_extensions = [];
$missing_optional = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

foreach ($optional_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_optional[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    echo "❌ Error: Missing required PHP extensions:\n";
    foreach ($missing_extensions as $ext) {
        echo "   - $ext\n";
    }
    echo "\nPlease install the missing extensions and try again.\n";
    exit(1);
}

echo "✅ Required extensions loaded\n";

if (!empty($missing_optional)) {
    echo "⚠️  Optional extensions missing (database functionality may be limited):\n";
    foreach ($missing_optional as $ext) {
        echo "   - $ext\n";
    }
    echo "\n";
}

// Check database connectivity (optional)
if (extension_loaded('pdo_mysql')) {
    try {
        $pdo = new PDO('mysql:host=localhost', '', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "✅ MySQL Version: " . $version . "\n";
        
        if (version_compare($version, '8.0.0', '>=')) {
            echo "✅ MySQL 8.0+ detected (Optimal)\n";
        } elseif (version_compare($version, '5.7.0', '>=')) {
            echo "⚠️  MySQL 5.7 detected (Compatible but upgrade to 8.0+ recommended)\n";
        }
    } catch (Exception $e) {
        echo "⚠️  MySQL connection failed (database may not be configured yet)\n";
    }
} else {
    echo "⚠️  MySQL extension not available - using demo mode\n";
}

echo "\n";

// Set server configuration
$host = '127.0.0.1';
$port = 8000;
$docroot = __DIR__;

echo "Starting development server...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Document Root: $docroot\n\n";

echo "🌐 Server will be available at: http://$host:$port\n";
echo "📋 Test setup page: http://$host:$port/test_setup.php\n";
echo "🏠 Home page: http://$host:$port/index.php\n";
echo "🔐 Login page: http://$host:$port/login.php\n\n";

echo "Press Ctrl+C to stop the server\n";
echo "==============================================\n\n";

// Start the built-in PHP server
$command = "php -S $host:$port -t $docroot";
passthru($command);
?>

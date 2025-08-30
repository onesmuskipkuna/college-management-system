<?php
/**
 * Database Setup and Connection Test Script
 * This script sets up the MySQL database and tests connectivity
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/config.php';

// Set error reporting for setup
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>College Management System - Database Setup</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Test MySQL connection
echo "<h2>1. Testing MySQL Connection</h2>";
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "<p style='color: green;'>✓ MySQL connection successful</p>";
    echo "<p>Server info: " . $mysqli->server_info . "</p>";
    
    // Create database if it doesn't exist
    echo "<h2>2. Creating Database</h2>";
    $db_name = DB_NAME;
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS `{$db_name}` 
                      DEFAULT CHARACTER SET utf8mb4 
                      COLLATE utf8mb4_0900_ai_ci";
    
    if ($mysqli->query($create_db_sql)) {
        echo "<p style='color: green;'>✓ Database '{$db_name}' created/verified successfully</p>";
    } else {
        throw new Exception("Error creating database: " . $mysqli->error);
    }
    
    // Select the database
    $mysqli->select_db($db_name);
    echo "<p style='color: green;'>✓ Database '{$db_name}' selected</p>";
    
    // Read and execute SQL schema
    echo "<h2>3. Setting up Database Schema</h2>";
    $sql_file = __DIR__ . '/database/college_db.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL schema file not found: {$sql_file}");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^(--|\/\*|\s*$)/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                if ($mysqli->query($statement)) {
                    $success_count++;
                } else {
                    // Skip certain expected errors (like table already exists)
                    if (strpos($mysqli->error, 'already exists') === false) {
                        echo "<p style='color: orange;'>Warning: " . $mysqli->error . "</p>";
                        $error_count++;
                    }
                }
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p style='color: orange;'>Warning: " . $e->getMessage() . "</p>";
                    $error_count++;
                }
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Schema setup completed: {$success_count} statements executed successfully</p>";
    if ($error_count > 0) {
        echo "<p style='color: orange;'>⚠ {$error_count} warnings (likely non-critical)</p>";
    }
    
    // Test table creation
    echo "<h2>4. Verifying Tables</h2>";
    $tables_query = "SHOW TABLES";
    $result = $mysqli->query($tables_query);
    
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "<p style='color: green;'>✓ Found " . count($tables) . " tables:</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    }
    
    // Test with db.php functions
    echo "<h2>5. Testing Database Functions</h2>";
    require_once __DIR__ . '/db.php';
    
    // Test connection validation
    if (validateConnection()) {
        echo "<p style='color: green;'>✓ Database connection validation successful</p>";
    } else {
        echo "<p style='color: red;'>✗ Database connection validation failed</p>";
    }
    
    // Test basic query
    try {
        $user_count = fetchOne("SELECT COUNT(*) as count FROM users");
        echo "<p style='color: green;'>✓ Query test successful - Found {$user_count['count']} users</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Query test failed: " . $e->getMessage() . "</p>";
    }
    
    // Test record operations
    echo "<h2>6. Testing CRUD Operations</h2>";
    
    // Test if admin user exists
    $admin_exists = recordExists('users', 'username', 'admin');
    if ($admin_exists) {
        echo "<p style='color: green;'>✓ Admin user exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Admin user not found - creating default admin</p>";
        
        // Create default admin user
        try {
            $admin_data = [
                'username' => 'admin',
                'email' => 'admin@college.edu',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'director',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $admin_id = insertRecord('users', $admin_data);
            echo "<p style='color: green;'>✓ Default admin user created (ID: {$admin_id})</p>";
            echo "<p style='color: blue;'>Default login: admin / admin123</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Failed to create admin user: " . $e->getMessage() . "</p>";
        }
    }
    
    // Test courses
    $course_count = fetchOne("SELECT COUNT(*) as count FROM courses");
    echo "<p style='color: green;'>✓ Found {$course_count['count']} courses</p>";
    
    if ($course_count['count'] == 0) {
        echo "<p style='color: orange;'>⚠ No courses found - sample data may not have been inserted</p>";
    }
    
    echo "<h2>7. Configuration Summary</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th style='padding: 8px; background: #f0f0f0;'>Setting</th><th style='padding: 8px; background: #f0f0f0;'>Value</th></tr>";
    echo "<tr><td style='padding: 8px;'>Database Host</td><td style='padding: 8px;'>" . DB_HOST . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>Database Name</td><td style='padding: 8px;'>" . DB_NAME . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>Database User</td><td style='padding: 8px;'>" . DB_USER . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>Demo Mode</td><td style='padding: 8px;'>" . (false ? 'Enabled' : 'Disabled') . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>Development Mode</td><td style='padding: 8px;'>" . (DEVELOPMENT_MODE ? 'Enabled' : 'Disabled') . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>PHP Version</td><td style='padding: 8px;'>" . PHP_VERSION . "</td></tr>";
    echo "<tr><td style='padding: 8px;'>MySQL Version</td><td style='padding: 8px;'>" . $mysqli->server_info . "</td></tr>";
    echo "</table>";
    
    echo "<h2>8. Next Steps</h2>";
    echo "<ol>";
    echo "<li>Access the system at: <a href='index.php'>index.php</a></li>";
    echo "<li>Login with: admin / admin123 (change password immediately)</li>";
    echo "<li>Configure system settings in config.php</li>";
    echo "<li>Set false to false for production</li>";
    echo "<li>Set DEVELOPMENT_MODE to false for production</li>";
    echo "</ol>";
    
    echo "<p style='color: green; font-weight: bold; font-size: 18px;'>✓ Database setup completed successfully!</p>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>✗ Setup failed: " . $e->getMessage() . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Verify MySQL server is running</li>";
    echo "<li>Check database credentials in config.php</li>";
    echo "<li>Ensure database user has CREATE, INSERT, UPDATE, DELETE privileges</li>";
    echo "<li>Check MySQL error logs</li>";
    echo "</ul>";
}

echo "</div>";
?>

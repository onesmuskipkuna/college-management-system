<?php
/**
 * Database Connection Handler
 * MySQL-only database connection
 */

// Define access constant
if (!defined('CMS_ACCESS')) {
    define('CMS_ACCESS', true);
}

// Include configuration
require_once __DIR__ . '/config.php';

// MySQL connection only
try {
    if (!class_exists('mysqli')) {
        throw new Exception("MySQLi extension is not available. Please install php-mysqli extension.");
    }
    
    // MySQL connection
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($mysqli->connect_error) {
        throw new Exception("MySQL connection failed: " . $mysqli->connect_error);
    }
    
    // Set charset
    $mysqli->set_charset(DB_CHARSET);
    
    // Set SQL mode for better compatibility
    $mysqli->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
    $db_connection_type = 'mysql';
    
    // Define MySQL functions
    /**
     * Execute a prepared statement with parameters
     */
    function executeQuery($sql, $params = []) {
        global $mysqli;

        if (!$mysqli) {
            throw new Exception("Database not available");
        }

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        
        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    function fetchOne($sql, $params = []) {
        global $mysqli;

        if (!$mysqli) {
            return null;
        }

        try {
            $stmt = executeQuery($sql, $params);
            $result = $stmt->get_result();
            return $result ? $result->fetch_assoc() : null;
        } catch (Exception $e) {
            error_log("fetchOne error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch all rows
     */
    function fetchAll($sql, $params = []) {
        global $mysqli;

        if (!$mysqli) {
            return [];
        }

        try {
            $stmt = executeQuery($sql, $params);
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Exception $e) {
            error_log("fetchAll error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the last inserted ID
     */
    function getLastInsertId() {
        global $mysqli;
        return $mysqli->insert_id;
    }

    /**
     * Begin transaction
     */
    function beginTransaction() {
        global $mysqli;
        return $mysqli->begin_transaction();
    }

    /**
     * Commit transaction
     */
    function commitTransaction() {
        global $mysqli;
        return $mysqli->commit();
    }

    /**
     * Rollback transaction
     */
    function rollbackTransaction() {
        global $mysqli;
        return $mysqli->rollback();
    }

    /**
     * Check if a record exists
     */
    function recordExists($table, $field, $value) {
        global $mysqli;

        if (!$mysqli) {
            return false;
        }

        try {
            $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$field}` = ?";
            $stmt = executeQuery($sql, [$value]);
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("recordExists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert a record and return the ID
     */
    function insertRecord($table, $data) {
        global $mysqli;

        if (!$mysqli) {
            throw new Exception("Database not available");
        }

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";

        executeQuery($sql, array_values($data));
        return getLastInsertId();
    }

    /**
     * Update a record
     */
    function updateRecord($table, $data, $where_field, $where_value) {
        global $mysqli;

        if (!$mysqli) {
            throw new Exception("Database not available");
        }

        $fields = array_keys($data);
        $set_clause = implode(' = ?, ', $fields) . ' = ?';
        $sql = "UPDATE `{$table}` SET {$set_clause} WHERE `{$where_field}` = ?";

        $params = array_values($data);
        $params[] = $where_value;

        return executeQuery($sql, $params);
    }

    /**
     * Delete a record
     */
    function deleteRecord($table, $field, $value) {
        global $mysqli;

        if (!$mysqli) {
            throw new Exception("Database not available");
        }

        $sql = "DELETE FROM `{$table}` WHERE `{$field}` = ?";
        return executeQuery($sql, [$value]);
    }

    /**
     * Validate database connection
     */
    function validateConnection() {
        global $mysqli;
        if (!$mysqli) return false;

        try {
            $result = $mysqli->query('SELECT 1');
            return $result !== false;
        } catch (Exception $e) {
            error_log("Database connection validation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database statistics
     */
    function getDatabaseStats() {
        global $mysqli;
        
        if (!$mysqli) {
            return null;
        }
        
        try {
            $stats = [];
            
            // Get table count
            $result = $mysqli->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
            $stats['tables'] = $result ? $result->fetch_assoc()['table_count'] : 0;
            
            // Get database size
            $result = $mysqli->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
            $stats['size_mb'] = $result ? $result->fetch_assoc()['size_mb'] : 0;
            
            return $stats;
        } catch (Exception $e) {
            error_log("getDatabaseStats error: " . $e->getMessage());
            return null;
        }
    }

    // Test connection on load
    if (!validateConnection()) {
        error_log("Database connection validation failed during initialization");
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection failed. Please check your MySQL configuration and ensure the database server is running.");
}
?>

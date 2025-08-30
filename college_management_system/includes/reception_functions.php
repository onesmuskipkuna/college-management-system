<?php
/**
 * Reception Management Functions
 * Specific functions for reception operations
 */

// Prevent direct access
if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Get today's appointments for reception
 */
function getTodaysAppointments() {
    try {
        // In a real implementation, this would query the database
        // For now, return demo data with proper error handling
        $appointments = [
            ['time' => '2:00 PM', 'visitor' => 'Mr. James Parker', 'purpose' => 'Parent meeting with registrar'],
            ['time' => '3:30 PM', 'visitor' => 'Ms. Linda Green', 'purpose' => 'Course consultation'],
            ['time' => '4:00 PM', 'visitor' => 'Dr. Michael Brown', 'purpose' => 'Partnership discussion']
        ];
        
        return $appointments;
    } catch (Exception $e) {
        error_log("Error fetching today's appointments: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

/**
 * Get recent inquiries for reception
 */
function getRecentInquiries($limit = 10) {
    try {
        // In a real implementation, this would query the database
        // For now, return demo data with proper error handling
        $inquiries = [
            ['name' => 'John Smith', 'type' => 'Course Information', 'time' => '10:30 AM', 'status' => 'pending'],
            ['name' => 'Mary Johnson', 'type' => 'Fee Payment', 'time' => '11:15 AM', 'status' => 'resolved'],
            ['name' => 'David Wilson', 'type' => 'Admission Process', 'time' => '12:00 PM', 'status' => 'in_progress'],
            ['name' => 'Sarah Brown', 'type' => 'Certificate Collection', 'time' => '1:30 PM', 'status' => 'pending']
        ];
        
        return array_slice($inquiries, 0, $limit);
    } catch (Exception $e) {
        error_log("Error fetching recent inquiries: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

/**
 * Get recent complaints for reception
 */
function getRecentComplaints($limit = 10) {
    try {
        // In a real implementation, this would query the database
        // For now, return demo data with proper error handling
        $complaints = [
            ['student' => 'Alice Davis', 'issue' => 'Delayed certificate processing', 'priority' => 'high', 'time' => '9:00 AM'],
            ['student' => 'Bob Wilson', 'issue' => 'Fee payment not reflected', 'priority' => 'medium', 'time' => '10:45 AM'],
            ['student' => 'Carol White', 'issue' => 'Timetable conflict', 'priority' => 'low', 'time' => '2:15 PM']
        ];
        
        return array_slice($complaints, 0, $limit);
    } catch (Exception $e) {
        error_log("Error fetching recent complaints: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

/**
 * Get visitor log entries
 */
function getVisitorLog($date = null, $limit = 50) {
    try {
        $date = $date ?: date('Y-m-d');
        // In a real implementation, this would query the database
        // For now, return demo data with proper error handling
        $visitors = [
            [
                'id' => 1,
                'visitor_name' => 'John Doe',
                'purpose' => 'Course inquiry',
                'contact_number' => '0712345678',
                'check_in_time' => '09:00:00',
                'check_out_time' => null,
                'status' => 'checked_in'
            ],
            [
                'id' => 2,
                'visitor_name' => 'Jane Smith',
                'purpose' => 'Fee payment',
                'contact_number' => '0723456789',
                'check_in_time' => '10:30:00',
                'check_out_time' => '11:00:00',
                'status' => 'checked_out'
            ]
        ];
        
        return array_slice($visitors, 0, $limit);
    } catch (Exception $e) {
        error_log("Error fetching visitor log: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

/**
 * Register a new visitor
 */
function registerVisitor($visitor_data) {
    try {
        $required_fields = ['visitor_name', 'purpose'];
        foreach ($required_fields as $field) {
            if (empty($visitor_data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // In a real implementation, this would insert into database
        // For now, simulate successful registration
        $visitor_id = rand(1000, 9999);
        
        return [
            'success' => true,
            'visitor_id' => $visitor_id,
            'message' => 'Visitor registered successfully'
        ];
    } catch (Exception $e) {
        error_log("Error registering visitor: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [
            'success' => false,
            'message' => 'Failed to register visitor: ' . $e->getMessage()
        ];
    }
}

/**
 * Get reception dashboard statistics
 */
function getReceptionStatistics() {
    try {
        // In a real implementation, these would be database queries
        // For now, return demo data with proper error handling
        return [
            'daily_visitors' => 28,
            'pending_inquiries' => 12,
            'complaints_today' => 5,
            'fee_inquiries' => 15
        ];
    } catch (Exception $e) {
        error_log("Error fetching reception statistics: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [
            'daily_visitors' => 0,
            'pending_inquiries' => 0,
            'complaints_today' => 0,
            'fee_inquiries' => 0
        ];
    }
}

/**
 * Search students for reception
 */
function searchStudents($search_term, $limit = 10) {
    try {
        // In a real implementation, this would query the database
        // For now, return demo data based on search term
        if (empty(trim($search_term))) {
            return [];
        }
        
        $students = [
            [
                'id' => 1,
                'student_id' => 'ST001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@email.com',
                'phone' => '0712345678',
                'status' => 'active',
                'course_name' => 'Computer Science Diploma'
            ]
        ];
        
        return array_slice($students, 0, $limit);
    } catch (Exception $e) {
        error_log("Error searching students: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

/**
 * Create a new inquiry
 */
function createInquiry($inquiry_data) {
    try {
        $required_fields = ['visitor_name', 'inquiry_type'];
        foreach ($required_fields as $field) {
            if (empty($inquiry_data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // In a real implementation, this would insert into database
        // For now, simulate successful creation
        $inquiry_id = rand(1000, 9999);
        
        return [
            'success' => true,
            'inquiry_id' => $inquiry_id,
            'message' => 'Inquiry created successfully'
        ];
    } catch (Exception $e) {
        error_log("Error creating inquiry: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [
            'success' => false,
            'message' => 'Failed to create inquiry: ' . $e->getMessage()
        ];
    }
}

/**
 * Create a new complaint
 */
function createComplaint($complaint_data) {
    try {
        $required_fields = ['student_name', 'complaint_type', 'description'];
        foreach ($required_fields as $field) {
            if (empty($complaint_data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // In a real implementation, this would insert into database
        // For now, simulate successful creation
        $complaint_id = rand(1000, 9999);
        
        return [
            'success' => true,
            'complaint_id' => $complaint_id,
            'message' => 'Complaint registered successfully'
        ];
    } catch (Exception $e) {
        error_log("Error creating complaint: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [
            'success' => false,
            'message' => 'Failed to register complaint: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

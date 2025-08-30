<?php
/**
 * Common Functions
 * Utility functions used throughout the system
 */

// Prevent direct access
if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

// Prevent direct access
if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Student Management Functions
 */

function isSuperAdmin() {
    $user = Authentication::getCurrentUser();
    return $user && $user['role'] === ROLE_SUPER_ADMIN;
}

function generateStudentId() {
    $year = date('Y');
    $prefix = 'STU' . $year;
    
    // Get the last student ID for this year
    $sql = "SELECT student_id FROM students WHERE student_id LIKE ? ORDER BY student_id DESC LIMIT 1";
    $last_student = fetchOne($sql, [$prefix . '%']);
    
    if ($last_student) {
        $last_number = intval(substr($last_student['student_id'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate unique teacher ID
 */
function generateTeacherId() {
    $year = date('Y');
    $prefix = 'TCH' . $year;
    
    $sql = "SELECT teacher_id FROM teachers WHERE teacher_id LIKE ? ORDER BY teacher_id DESC LIMIT 1";
    $last_teacher = fetchOne($sql, [$prefix . '%']);
    
    if ($last_teacher) {
        $last_number = intval(substr($last_teacher['teacher_id'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate unique receipt number
 */
function generateReceiptNumber() {
    $year = date('Y');
    $month = date('m');
    $prefix = 'RCP' . $year . $month;
    
    $sql = "SELECT receipt_number FROM payments WHERE receipt_number LIKE ? ORDER BY receipt_number DESC LIMIT 1";
    $last_receipt = fetchOne($sql, [$prefix . '%']);
    
    if ($last_receipt) {
        $last_number = intval(substr($last_receipt['receipt_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate unique certificate number
 */
function generateCertificateNumber($type = 'completion') {
    $year = date('Y');
    $prefix = strtoupper(substr($type, 0, 3)) . $year;
    
    $sql = "SELECT certificate_number FROM certificates WHERE certificate_number LIKE ? ORDER BY certificate_number DESC LIMIT 1";
    $last_cert = fetchOne($sql, [$prefix . '%']);
    
    if ($last_cert) {
        $last_number = intval(substr($last_cert['certificate_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Fee Management Functions
 */

/**
 * Auto-generate invoice for student based on course
 */
function generateStudentInvoice($student_id, $course_id) {
    try {
        beginTransaction();
        
        // Get fee structure for the course
        $fee_structures = fetchAll(
            "SELECT * FROM fee_structure WHERE course_id = ? AND status = 'active'",
            [$course_id]
        );
        
        if (empty($fee_structures)) {
            throw new Exception("No fee structure found for this course");
        }
        
        $total_amount = 0;
        
        foreach ($fee_structures as $fee) {
            // Calculate due date (30 days from now for most fees, immediate for registration)
            $due_date = ($fee['fee_type'] === 'Registration Fee') 
                ? date('Y-m-d') 
                : date('Y-m-d', strtotime('+30 days'));
            
            // Insert student fee record
            $student_fee_data = [
                'student_id' => $student_id,
                'fee_structure_id' => $fee['id'],
                'amount_due' => $fee['amount'],
                'due_date' => $due_date,
                'status' => 'pending'
            ];
            
            insertRecord('student_fees', $student_fee_data);
            $total_amount += $fee['amount'];
        }
        
        commitTransaction();
        
        return [
            'success' => true,
            'message' => 'Invoice generated successfully',
            'total_amount' => $total_amount
        ];
        
    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Invoice generation error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to generate invoice: ' . $e->getMessage()
        ];
    }
}

/**
 * Get student fee balance
 */
function getStudentFeeBalance($student_id) {
    $sql = "SELECT 
                SUM(sf.amount_due) as total_due,
                SUM(sf.amount_paid) as total_paid,
                SUM(sf.balance) as total_balance
            FROM student_fees sf 
            WHERE sf.student_id = ?";
    
    $result = fetchOne($sql, [$student_id]);
    
    return [
        'total_due' => $result['total_due'] ?? 0,
        'total_paid' => $result['total_paid'] ?? 0,
        'total_balance' => $result['total_balance'] ?? 0
    ];
}

/**
 * Check if student has cleared all fees
 */
function hasStudentClearedFees($student_id) {
    $balance = getStudentFeeBalance($student_id);
    return $balance['total_balance'] <= 0;
}

/**
 * Get student payment history
 */
function getStudentPaymentHistory($student_id, $limit = null) {
    $sql = "SELECT p.*, sf.fee_structure_id, fs.fee_type, u.username as received_by_name
            FROM payments p
            JOIN student_fees sf ON p.student_fee_id = sf.id
            JOIN fee_structure fs ON sf.fee_structure_id = fs.id
            JOIN users u ON p.received_by = u.id
            WHERE p.student_id = ?
            ORDER BY p.payment_date DESC, p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    return fetchAll($sql, [$student_id]);
}

/**
 * Hostel Management Functions
 */

/**
 * Get room statistics
 */
function getRoomStatistics() {
    $sql = "SELECT 
                COUNT(*) AS total_rooms,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available_rooms,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_rooms
            FROM hostel_rooms";
    
    return fetchOne($sql);
}

/**
 * Allocate room to a student
 */
function allocateRoom($student_id, $room_id) {
    $query = "INSERT INTO hostel_allocations (student_id, room_id, allocation_date, status) VALUES (?, ?, NOW(), 'active')";
    return executeQuery($query, [$student_id, $room_id]);
}

/**
 * Allocate room and meal plan to a student
 */
function allocateRoomToStudent($student_id, $room_number, $meal_plan_id) {
    try {
        // In demo mode, just return success
        if (defined('false') && false) {
            return true;
        }
        
        // Insert room allocation with meal plan
        $query = "INSERT INTO hostel_allocations (student_id, room_number, meal_plan_id, allocation_date, status) VALUES (?, ?, ?, NOW(), 'active')";
        return executeQuery($query, [$student_id, $room_number, $meal_plan_id]);
    } catch (Exception $e) {
        error_log("Room allocation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update room status
 */
function updateRoomStatus($room_id, $status) {
    $query = "UPDATE hostel_rooms SET status = ? WHERE id = ?";
    return executeQuery($query, [$status, $room_id]);
}

/**
 * Academic Functions
 */

/**
 * Calculate GPA for a student
 */
function calculateStudentGPA($student_id, $semester = null) {
    $where_clause = "WHERE g.student_id = ? AND g.status = 'approved'";
    $params = [$student_id];
    
    if ($semester) {
        $where_clause .= " AND s.semester = ?";
        $params[] = $semester;
    }
    
    $sql = "SELECT 
                AVG((g.marks / g.max_marks) * 100) as gpa,
                COUNT(*) as total_subjects
            FROM grades g
            JOIN subjects s ON g.subject_id = s.id
            {$where_clause}";
    
    $result = fetchOne($sql, $params);
    
    return [
        'gpa' => round($result['gpa'] ?? 0, 2),
        'total_subjects' => $result['total_subjects'] ?? 0
    ];
}

/**
 * Get student transcript data
 */
function getStudentTranscript($student_id) {
    $sql = "SELECT 
                s.subject_name,
                s.subject_code,
                s.credits,
                s.semester,
                AVG(g.marks) as average_marks,
                AVG(g.marks / g.max_marks * 100) as percentage,
                CASE 
                    WHEN AVG(g.marks / g.max_marks * 100) >= 80 THEN 'A'
                    WHEN AVG(g.marks / g.max_marks * 100) >= 70 THEN 'B'
                    WHEN AVG(g.marks / g.max_marks * 100) >= 60 THEN 'C'
                    WHEN AVG(g.marks / g.max_marks * 100) >= 50 THEN 'D'
                    ELSE 'F'
                END as grade
            FROM grades g
            JOIN subjects s ON g.subject_id = s.id
            WHERE g.student_id = ? AND g.status = 'approved'
            GROUP BY s.id, s.subject_name, s.subject_code, s.credits, s.semester
            ORDER BY s.semester, s.subject_name";
    
    return fetchAll($sql, [$student_id]);
}

/**
 * Check if student is eligible for certificate
 */
function isStudentEligibleForCertificate($student_id) {
    // Check if student has completed all subjects
    $student = fetchOne("SELECT * FROM students WHERE id = ?", [$student_id]);
    if (!$student) {
        return ['eligible' => false, 'reason' => 'Student not found'];
    }
    
    // Check if fees are cleared
    if (!hasStudentClearedFees($student_id)) {
        return ['eligible' => false, 'reason' => 'Outstanding fees must be cleared'];
    }
    
    // Check if all subjects are passed
    $transcript = getStudentTranscript($student_id);
    foreach ($transcript as $subject) {
        if ($subject['grade'] === 'F') {
            return ['eligible' => false, 'reason' => 'Failed subjects must be retaken'];
        }
    }
    
    // Check if course duration is completed
    $course_info = fetchOne(
        "SELECT c.duration_months, s.admission_date 
         FROM students s 
         JOIN courses c ON s.course_id = c.id 
         WHERE s.id = ?", 
        [$student_id]
    );
    
    $admission_date = new DateTime($course_info['admission_date']);
    $completion_date = $admission_date->add(new DateInterval('P' . $course_info['duration_months'] . 'M'));
    $now = new DateTime();
    
    if ($now < $completion_date) {
        return ['eligible' => false, 'reason' => 'Course duration not yet completed'];
    }
    
    return ['eligible' => true, 'reason' => 'Student is eligible for certificate'];
}

/**
 * Utility Functions
 */

/**
 * Upload file with validation
 */
function uploadFile($file, $destination_folder, $allowed_types = null) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $allowed_types = $allowed_types ?: ALLOWED_FILE_TYPES;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $destination = UPLOAD_PATH . $destination_folder . '/' . $filename;
    
    // Create directory if it doesn't exist
    $dir = dirname($destination);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination,
            'url' => '/college_management_system/uploads/' . $destination_folder . '/' . $filename
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message, $from = null) {
    $from = $from ?: ADMIN_EMAIL;
    
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $password;
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Get academic year
 */
function getCurrentAcademicYear() {
    $current_month = date('n');
    $current_year = date('Y');
    
    // Academic year starts in September (month 9)
    if ($current_month >= 9) {
        return $current_year . '/' . ($current_year + 1);
    } else {
        return ($current_year - 1) . '/' . $current_year;
    }
}

/**
 * Validate Kenyan ID number
 */
function validateKenyanId($id_number) {
    return preg_match('/^\d{8}$/', $id_number);
}

/**
 * Validate passport number
 */
function validatePassport($passport_number) {
    return preg_match('/^[A-Z0-9]{6,9}$/', strtoupper($passport_number));
}

/**
 * Get age from date of birth
 */
function calculateAge($date_of_birth) {
    $birth_date = new DateTime($date_of_birth);
    $today = new DateTime();
    return $birth_date->diff($today)->y;
}

/**
 * Check if date is in the future
 */
function isFutureDate($date) {
    return strtotime($date) > time();
}
?>

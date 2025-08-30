<?php
/**
 * API Endpoint - Get Fee Structure for Course
 * Returns fee structure data for a specific course
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!Authentication::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get course ID from request
$course_id = intval($_GET['course_id'] ?? 0);

if (!$course_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

try {
    // Get fee structure for the course
    $fee_structure = fetchAll(
        "SELECT fs.*, c.course_name 
         FROM fee_structure fs 
         JOIN courses c ON fs.course_id = c.id 
         WHERE fs.course_id = ? AND fs.status = 'active' 
         ORDER BY fs.is_mandatory DESC, fs.fee_type",
        [$course_id]
    );
    
    if (empty($fee_structure)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No fee structure found for this course'
        ]);
        exit;
    }
    
    // Calculate total amount
    $total_amount = 0;
    foreach ($fee_structure as &$fee) {
        $total_amount += $fee['amount'];
        $fee['formatted_amount'] = formatCurrency($fee['amount']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $fee_structure,
        'total_amount' => $total_amount,
        'formatted_total' => formatCurrency($total_amount),
        'course_name' => $fee_structure[0]['course_name'] ?? ''
    ]);
    
} catch (Exception $e) {
    error_log("Fee structure API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to retrieve fee structure'
    ]);
}
?>

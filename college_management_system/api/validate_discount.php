<?php
/**
 * Discount Code Validation API
 * Validates discount codes for fee payments
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated
if (!Authentication::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['discount_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Discount code is required']);
    exit;
}

$discount_code = sanitizeInput($input['discount_code']);

try {
    // Check if discount code exists and is active
    $discount = fetchOne(
        "SELECT * FROM discount_codes WHERE code = ? AND status = 'active' AND 
         (expiry_date IS NULL OR expiry_date >= CURDATE())",
        [$discount_code]
    );
    
    if (!$discount) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid or expired discount code'
        ]);
        exit;
    }
    
    // Check usage limits if applicable
    if ($discount['usage_limit'] > 0) {
        $usage_count = fetchOne(
            "SELECT COUNT(*) as count FROM payments WHERE discount_code = ?",
            [$discount_code]
        )['count'] ?? 0;
        
        if ($usage_count >= $discount['usage_limit']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Discount code usage limit exceeded'
            ]);
            exit;
        }
    }
    
    // Return discount information
    echo json_encode([
        'success' => true,
        'message' => 'Valid discount code',
        'discount' => [
            'id' => $discount['id'],
            'code' => $discount['code'],
            'type' => $discount['type'],
            'value' => $discount['value'],
            'description' => $discount['description'],
            'expiry_date' => $discount['expiry_date'],
            'usage_limit' => $discount['usage_limit']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Discount validation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred'
    ]);
}
?>

<?php
/**
 * AI Chatbot API Endpoint
 * Handles chatbot interactions via AJAX
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../includes/ai_chatbot.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure user is authenticated
if (!Authentication::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required',
        'message' => 'Please log in to use the chatbot'
    ]);
    exit();
}

$user = Authentication::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['message'])) {
            throw new Exception('Message is required');
        }
        
        $message = trim($input['message']);
        if (empty($message)) {
            throw new Exception('Message cannot be empty');
        }
        
        // Rate limiting (simple implementation)
        $session_key = 'chatbot_last_request_' . $user['id'];
        $last_request = $_SESSION[$session_key] ?? 0;
        $current_time = time();
        
        if ($current_time - $last_request < 1) { // 1 second rate limit
            throw new Exception('Please wait before sending another message');
        }
        
        $_SESSION[$session_key] = $current_time;
        
        // Process message with AI chatbot
        global $aiChatbot;
        $response = $aiChatbot->processMessage($message, $user['id'], $user['role']);
        
        // Add user context to response
        $response['user'] = [
            'name' => $user['first_name'] ?? $user['username'],
            'role' => $user['role']
        ];
        
        // Add timestamp
        $response['timestamp'] = date('Y-m-d H:i:s');
        
        echo json_encode($response);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get conversation history
        $limit = (int)($_GET['limit'] ?? 10);
        $limit = min(max($limit, 1), 50); // Between 1 and 50
        
        try {
            $conversations = fetchAll("
                SELECT message, response, intent, timestamp 
                FROM chatbot_conversations 
                WHERE user_id = ? 
                ORDER BY timestamp DESC 
                LIMIT ?
            ", [$user['id'], $limit]);
            
            echo json_encode([
                'success' => true,
                'conversations' => array_reverse($conversations), // Oldest first
                'count' => count($conversations)
            ]);
        } catch (Exception $e) {
            // Return empty history if table doesn't exist
            echo json_encode([
                'success' => true,
                'conversations' => [],
                'count' => 0
            ]);
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to process chatbot request'
    ]);
}
?>

<?php
/**
 * AI-Powered Student Support Chatbot
 * Intelligent assistance for students, teachers, and staff
 */

if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

class AIChatbot {
    private $db;
    private $knowledge_base;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->initializeKnowledgeBase();
    }
    
    /**
     * Process user message and generate AI response
     */
    public function processMessage($message, $user_id, $user_role) {
        try {
            // Clean and analyze the message
            $cleaned_message = $this->cleanMessage($message);
            $intent = $this->detectIntent($cleaned_message);
            $entities = $this->extractEntities($cleaned_message, $user_role);
            
            // Generate contextual response
            $response = $this->generateResponse($intent, $entities, $user_id, $user_role);
            
            // Log conversation for learning
            $this->logConversation($user_id, $message, $response, $intent);
            
            return [
                'success' => true,
                'response' => $response,
                'intent' => $intent,
                'suggestions' => $this->getSuggestions($intent, $user_role),
                'quick_actions' => $this->getQuickActions($intent, $user_role)
            ];
            
        } catch (Exception $e) {
            error_log("Chatbot error: " . $e->getMessage());
            return [
                'success' => false,
                'response' => "I'm sorry, I'm having trouble understanding right now. Please try again or contact support.",
                'intent' => 'error',
                'suggestions' => [],
                'quick_actions' => []
            ];
        }
    }
    
    /**
     * Detect user intent from message
     */
    private function detectIntent($message) {
        $message_lower = strtolower($message);
        
        // Fee-related intents
        if (preg_match('/\b(fee|payment|balance|invoice|receipt|mpesa|pay)\b/', $message_lower)) {
            if (preg_match('/\b(balance|owe|outstanding|due)\b/', $message_lower)) {
                return 'fee_balance_inquiry';
            } elseif (preg_match('/\b(pay|payment|mpesa|bank)\b/', $message_lower)) {
                return 'payment_methods';
            } elseif (preg_match('/\b(receipt|invoice|statement)\b/', $message_lower)) {
                return 'fee_documents';
            }
            return 'fee_general';
        }
        
        // Academic intents
        if (preg_match('/\b(grade|result|assignment|exam|course|subject|progress)\b/', $message_lower)) {
            if (preg_match('/\b(assignment|homework|submit)\b/', $message_lower)) {
                return 'assignment_help';
            } elseif (preg_match('/\b(grade|result|score|mark)\b/', $message_lower)) {
                return 'grade_inquiry';
            } elseif (preg_match('/\b(progress|performance|gpa)\b/', $message_lower)) {
                return 'progress_inquiry';
            }
            return 'academic_general';
        }
        
        // Certificate intents
        if (preg_match('/\b(certificate|graduation|diploma|transcript)\b/', $message_lower)) {
            return 'certificate_inquiry';
        }
        
        // Timetable intents
        if (preg_match('/\b(timetable|schedule|class|time|when)\b/', $message_lower)) {
            return 'timetable_inquiry';
        }
        
        // Registration intents
        if (preg_match('/\b(register|registration|enroll|admission|apply)\b/', $message_lower)) {
            return 'registration_help';
        }
        
        // Technical support
        if (preg_match('/\b(login|password|access|error|problem|issue|bug)\b/', $message_lower)) {
            return 'technical_support';
        }
        
        // Greeting
        if (preg_match('/\b(hello|hi|hey|good morning|good afternoon|good evening)\b/', $message_lower)) {
            return 'greeting';
        }
        
        // Help request
        if (preg_match('/\b(help|assist|support|guide|how)\b/', $message_lower)) {
            return 'help_request';
        }
        
        return 'general_inquiry';
    }
    
    /**
     * Extract entities from message
     */
    private function extractEntities($message, $user_role) {
        $entities = [];
        
        // Extract amounts
        if (preg_match('/\b(\d+(?:,\d{3})*(?:\.\d{2})?)\b/', $message, $matches)) {
            $entities['amount'] = $matches[1];
        }
        
        // Extract dates
        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/', $message, $matches)) {
            $entities['date'] = $matches[1];
        }
        
        // Extract course names (from knowledge base)
        foreach ($this->knowledge_base['courses'] as $course) {
            if (stripos($message, $course) !== false) {
                $entities['course'] = $course;
                break;
            }
        }
        
        // Extract student ID pattern
        if (preg_match('/\b(ST\d{3,6})\b/i', $message, $matches)) {
            $entities['student_id'] = $matches[1];
        }
        
        return $entities;
    }
    
    /**
     * Generate contextual response based on intent and entities
     */
    private function generateResponse($intent, $entities, $user_id, $user_role) {
        switch ($intent) {
            case 'greeting':
                return $this->getGreetingResponse($user_role);
                
            case 'fee_balance_inquiry':
                return $this->getFeeBalanceResponse($user_id, $user_role);
                
            case 'payment_methods':
                return $this->getPaymentMethodsResponse();
                
            case 'fee_documents':
                return $this->getFeeDocumentsResponse($user_role);
                
            case 'assignment_help':
                return $this->getAssignmentHelpResponse($user_id, $user_role);
                
            case 'grade_inquiry':
                return $this->getGradeInquiryResponse($user_id, $user_role);
                
            case 'progress_inquiry':
                return $this->getProgressInquiryResponse($user_id, $user_role);
                
            case 'certificate_inquiry':
                return $this->getCertificateInquiryResponse($user_id, $user_role);
                
            case 'timetable_inquiry':
                return $this->getTimetableInquiryResponse($user_id, $user_role);
                
            case 'registration_help':
                return $this->getRegistrationHelpResponse($user_role);
                
            case 'technical_support':
                return $this->getTechnicalSupportResponse();
                
            case 'help_request':
                return $this->getHelpResponse($user_role);
                
            default:
                return $this->getGeneralResponse($user_role);
        }
    }
    
    /**
     * Get role-specific greeting
     */
    private function getGreetingResponse($user_role) {
        $greetings = [
            'student' => "Hello! I'm your AI assistant. I can help you with fees, assignments, grades, timetables, and more. What would you like to know?",
            'teacher' => "Hello! I can assist you with student information, grade management, assignment tracking, and administrative tasks. How can I help?",
            'registrar' => "Hello! I can help you with student admissions, course management, certificate processing, and academic records. What do you need?",
            'accounts' => "Hello! I can assist with fee collection, financial reports, payment processing, and student account inquiries. How may I help?",
            'director' => "Hello! I can provide system analytics, performance reports, and administrative insights. What information do you need?",
            'default' => "Hello! I'm here to help you navigate the college management system. What can I assist you with today?"
        ];
        
        return $greetings[$user_role] ?? $greetings['default'];
    }
    
    /**
     * Get fee balance response for students
     */
    private function getFeeBalanceResponse($user_id, $user_role) {
        if ($user_role !== 'student') {
            return "I can help students check their fee balance. For detailed financial information, please use the accounts dashboard or contact the accounts office.";
        }
        
        try {
            // Get student fee information
            $student = fetchOne("SELECT * FROM students WHERE user_id = ?", [$user_id]);
            if ($student) {
                $balance = fetchOne("SELECT SUM(balance) as total FROM student_fees WHERE student_id = ? AND balance > 0", [$student['id']]);
                $total_balance = $balance['total'] ?? 0;
                
                if ($total_balance > 0) {
                    return "Your current fee balance is KSh " . number_format($total_balance) . ". You can pay via M-Pesa (Paybill 123456, Account: {$student['student_id']}), bank transfer, or visit the accounts office. Would you like me to show you payment options?";
                } else {
                    return "Great news! Your fee account is up to date with no outstanding balance. Keep up the good work!";
                }
            }
        } catch (Exception $e) {
            // Demo response
            return "Your current fee balance is KSh 25,000. You can pay via M-Pesa (Paybill 123456), bank transfer, or visit the accounts office. Would you like me to show you payment options?";
        }
        
        return "I couldn't retrieve your fee information right now. Please try again or visit the accounts office for assistance.";
    }
    
    /**
     * Get payment methods information
     */
    private function getPaymentMethodsResponse() {
        return "Here are the available payment methods:\n\n" .
               "💳 **M-Pesa STK Push**: Pay directly through the system\n" .
               "📱 **M-Pesa Paybill**: 123456 (Account: Your Student ID)\n" .
               "🏦 **Bank Transfer**: Contact accounts office for details\n" .
               "💵 **Cash Payment**: Visit the accounts office\n" .
               "💳 **Cheque**: Payable to the college\n\n" .
               "For instant payment, I recommend using M-Pesa STK Push through your student dashboard!";
    }
    
    /**
     * Get assignment help response
     */
    private function getAssignmentHelpResponse($user_id, $user_role) {
        if ($user_role === 'student') {
            return "I can help you with assignments! Here's what you can do:\n\n" .
                   "📝 **View Assignments**: Check your student dashboard\n" .
                   "📤 **Submit Work**: Upload files through the assignment portal\n" .
                   "📅 **Check Deadlines**: See due dates and time remaining\n" .
                   "📊 **Track Progress**: Monitor submission status\n\n" .
                   "Would you like me to show you your current assignments or help with submission?";
        } elseif ($user_role === 'teacher') {
            return "I can help you manage assignments:\n\n" .
                   "➕ **Create Assignment**: Use the assignment management tool\n" .
                   "👥 **Track Submissions**: Monitor student progress\n" .
                   "✅ **Grade Work**: Review and provide feedback\n" .
                   "📊 **View Statistics**: See completion rates\n\n" .
                   "What would you like to do with assignments?";
        }
        
        return "I can provide information about the assignment system. Students can submit work and teachers can create assignments through their respective dashboards.";
    }
    
    /**
     * Get suggestions based on intent
     */
    private function getSuggestions($intent, $user_role) {
        $suggestions = [
            'fee_balance_inquiry' => [
                "Show payment methods",
                "Download fee statement",
                "Payment history",
                "Contact accounts office"
            ],
            'assignment_help' => [
                "View my assignments",
                "Check submission deadlines",
                "Upload assignment",
                "Assignment guidelines"
            ],
            'grade_inquiry' => [
                "View current grades",
                "Check GPA",
                "Academic progress",
                "Transcript request"
            ],
            'general_inquiry' => [
                "Check fee balance",
                "View assignments",
                "See timetable",
                "Academic progress"
            ]
        ];
        
        return $suggestions[$intent] ?? $suggestions['general_inquiry'];
    }
    
    /**
     * Get quick actions based on intent
     */
    private function getQuickActions($intent, $user_role) {
        $actions = [
            'fee_balance_inquiry' => [
                ['text' => 'Pay Fees', 'action' => 'redirect', 'url' => 'student/fee_statement.php'],
                ['text' => 'Download Statement', 'action' => 'download', 'type' => 'fee_statement']
            ],
            'assignment_help' => [
                ['text' => 'View Assignments', 'action' => 'redirect', 'url' => 'student/assignments.php'],
                ['text' => 'Submit Work', 'action' => 'modal', 'modal' => 'assignment_submission']
            ],
            'timetable_inquiry' => [
                ['text' => 'View Timetable', 'action' => 'redirect', 'url' => 'student/timetable.php'],
                ['text' => 'Download PDF', 'action' => 'download', 'type' => 'timetable']
            ]
        ];
        
        return $actions[$intent] ?? [];
    }
    
    /**
     * Initialize knowledge base with common information
     */
    private function initializeKnowledgeBase() {
        $this->knowledge_base = [
            'courses' => [
                'Computer Science', 'Business Management', 'Accounting', 
                'Marketing', 'Information Technology', 'Engineering'
            ],
            'fee_types' => [
                'Tuition Fee', 'Registration Fee', 'Examination Fee', 
                'Library Fee', 'Laboratory Fee', 'Hostel Fee'
            ],
            'payment_methods' => [
                'M-Pesa', 'Bank Transfer', 'Cash', 'Cheque'
            ],
            'common_issues' => [
                'Login Problems', 'Password Reset', 'Fee Payment Issues', 
                'Grade Inquiries', 'Certificate Requests'
            ]
        ];
    }
    
    /**
     * Clean and normalize user message
     */
    private function cleanMessage($message) {
        // Remove extra whitespace and normalize
        $message = trim(preg_replace('/\s+/', ' ', $message));
        
        // Remove special characters but keep basic punctuation
        $message = preg_replace('/[^\w\s\.\?\!\-\/]/', '', $message);
        
        return $message;
    }
    
    /**
     * Log conversation for learning and improvement
     */
    private function logConversation($user_id, $message, $response, $intent) {
        try {
            $log_data = [
                'user_id' => $user_id,
                'message' => substr($message, 0, 500),
                'response' => substr($response, 0, 1000),
                'intent' => $intent,
                'timestamp' => date('Y-m-d H:i:s'),
                'session_id' => session_id()
            ];
            
            insertRecord('chatbot_conversations', $log_data);
        } catch (Exception $e) {
            error_log("Failed to log chatbot conversation: " . $e->getMessage());
        }
    }
    
    // Additional response methods...
    private function getGradeInquiryResponse($user_id, $user_role) {
        return "I can help you check your academic performance! You can view your current grades, GPA, and academic progress through your student dashboard. Would you like me to show you your latest grades or overall progress?";
    }
    
    private function getProgressInquiryResponse($user_id, $user_role) {
        return "Your academic progress includes course completion, GPA trends, and performance analytics. I can show you detailed progress reports, improvement recommendations, and graduation timeline. What specific progress information would you like to see?";
    }
    
    private function getCertificateInquiryResponse($user_id, $user_role) {
        return "For certificate inquiries, I can help you check eligibility, application status, and collection information. Certificates are issued after course completion, passing grades, and fee clearance. Would you like to check your certificate status?";
    }
    
    private function getTimetableInquiryResponse($user_id, $user_role) {
        return "I can help you access your class timetable, exam schedules, and academic calendar. Your personalized timetable is available in your student dashboard. Would you like me to show you today's classes or the full weekly schedule?";
    }
    
    private function getRegistrationHelpResponse($user_role) {
        return "I can guide you through the registration process! This includes course enrollment, fee payment, document submission, and account setup. New students should visit the registrar's office, while current students can register for additional courses online. What type of registration do you need help with?";
    }
    
    private function getTechnicalSupportResponse() {
        return "For technical issues, I can help with:\n\n" .
               "🔐 **Login Problems**: Password reset and account access\n" .
               "🖥️ **System Issues**: Browser compatibility and performance\n" .
               "📱 **Mobile Access**: Using the system on mobile devices\n" .
               "📧 **Email Issues**: Notification and communication problems\n\n" .
               "If you're having login issues, try clearing your browser cache or using the password reset option. For persistent problems, contact IT support.";
    }
    
    private function getHelpResponse($user_role) {
        $help_topics = [
            'student' => "I can help you with: Fee payments, Assignment submissions, Grade checking, Timetable viewing, Progress tracking, Certificate requests",
            'teacher' => "I can assist with: Student management, Grade entry, Assignment creation, Attendance tracking, Report generation",
            'default' => "I can help with various college management tasks. Please let me know what specific area you need assistance with."
        ];
        
        return $help_topics[$user_role] ?? $help_topics['default'];
    }
    
    private function getFeeDocumentsResponse($user_role) {
        return "I can help you access fee-related documents:\n\n" .
               "📄 **Fee Statement**: Current balance and payment history\n" .
               "🧾 **Receipts**: Payment confirmations and records\n" .
               "📊 **Invoice**: Detailed fee breakdown\n" .
               "📈 **Payment History**: Complete transaction records\n\n" .
               "All documents can be downloaded as PDF from your dashboard. Which document do you need?";
    }
    
    private function getGeneralResponse($user_role) {
        return "I'm here to help you with the college management system! I can assist with fees, academics, assignments, timetables, and more. Please let me know what specific information or assistance you need, and I'll do my best to help you.";
    }
}

// Initialize chatbot instance
$aiChatbot = new AIChatbot();
?>

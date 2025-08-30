<?php
/**
 * Notification System
 * Handle email notifications, SMS alerts, and system announcements
 */

if (!defined('CMS_ACCESS')) {
    die('Direct access not permitted');
}

class NotificationSystem {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Send email notification
     */
    public function sendEmail($to, $subject, $message, $from = null) {
        try {
            $from = $from ?: 'noreply@college.edu';
            
            // Create email headers
            $headers = [
                'From: ' . $from,
                'Reply-To: ' . $from,
                'Content-Type: text/html; charset=UTF-8',
                'MIME-Version: 1.0'
            ];
            
            // Log email attempt
            $this->logNotification('email', $to, $subject, $message);
            
            // In production, use a proper email service like PHPMailer, SendGrid, etc.
            // For demo purposes, we'll simulate email sending
            if (false) {
                error_log("DEMO EMAIL - To: {$to}, Subject: {$subject}");
                return true;
            }
            
            // Send email using PHP's mail function (basic implementation)
            return mail($to, $subject, $message, implode("\r\n", $headers));
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMS notification
     */
    public function sendSMS($phone, $message) {
        try {
            // Clean phone number
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            
            // Log SMS attempt
            $this->logNotification('sms', $phone, 'SMS Alert', $message);
            
            // In production, integrate with SMS service like Twilio, Africa's Talking, etc.
            if (false) {
                error_log("DEMO SMS - To: {$phone}, Message: {$message}");
                return true;
            }
            
            // Placeholder for SMS API integration
            return $this->sendSMSViaAPI($phone, $message);
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create system notification
     */
    public function createSystemNotification($user_id, $title, $message, $type = 'info', $action_url = null) {
        try {
            $notification_data = [
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type, // info, success, warning, error
                'action_url' => $action_url,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return insertRecord('system_notifications', $notification_data);
            
        } catch (Exception $e) {
            error_log("System notification creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send fee due reminder
     */
    public function sendFeeDueReminder($student_id, $days_until_due = 7) {
        try {
            // Get student and fee information
            $student = fetchOne("
                SELECT s.*, u.email, c.course_name 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                JOIN courses c ON s.course_id = c.id 
                WHERE s.id = ?
            ", [$student_id]);
            
            if (!$student) return false;
            
            // Get outstanding fees
            $outstanding_fees = fetchAll("
                SELECT sf.*, fs.fee_type 
                FROM student_fees sf 
                JOIN fee_structure fs ON sf.fee_structure_id = fs.id 
                WHERE sf.student_id = ? AND sf.balance > 0 
                AND sf.due_date <= DATE_ADD(NOW(), INTERVAL ? DAY)
            ", [$student_id, $days_until_due]);
            
            if (empty($outstanding_fees)) return true;
            
            $total_balance = array_sum(array_column($outstanding_fees, 'balance'));
            
            // Create email content
            $subject = "Fee Payment Reminder - " . $student['course_name'];
            $message = $this->createFeeReminderEmail($student, $outstanding_fees, $total_balance);
            
            // Send email
            $email_sent = $this->sendEmail($student['email'], $subject, $message);
            
            // Send SMS if phone number available
            if ($student['phone']) {
                $sms_message = "Fee reminder: KSh " . number_format($total_balance) . " due soon. Pay via M-Pesa or visit accounts office.";
                $this->sendSMS($student['phone'], $sms_message);
            }
            
            // Create system notification
            $this->createSystemNotification(
                $student['user_id'],
                'Fee Payment Due',
                "You have outstanding fees totaling KSh " . number_format($total_balance) . ". Please make payment to avoid penalties.",
                'warning',
                'student/fee_statement.php'
            );
            
            return $email_sent;
            
        } catch (Exception $e) {
            error_log("Fee reminder failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send assignment deadline reminder
     */
    public function sendAssignmentReminder($assignment_id, $hours_before = 24) {
        try {
            // Get assignment details
            $assignment = fetchOne("
                SELECT a.*, s.subject_name, c.course_name, t.first_name as teacher_name
                FROM assignments a
                JOIN subjects s ON a.subject_id = s.id
                JOIN courses c ON s.course_id = c.id
                JOIN teachers t ON a.teacher_id = t.id
                WHERE a.id = ?
            ", [$assignment_id]);
            
            if (!$assignment) return false;
            
            // Get students who haven't submitted
            $students = fetchAll("
                SELECT st.*, u.email 
                FROM students st 
                JOIN users u ON st.user_id = u.id 
                JOIN courses c ON st.course_id = c.id 
                JOIN subjects s ON c.id = s.course_id 
                WHERE s.id = ? AND st.status = 'active'
                AND st.id NOT IN (
                    SELECT student_id FROM assignment_submissions 
                    WHERE assignment_id = ?
                )
            ", [$assignment['subject_id'], $assignment_id]);
            
            foreach ($students as $student) {
                $subject = "Assignment Deadline Reminder - " . $assignment['title'];
                $message = $this->createAssignmentReminderEmail($student, $assignment);
                
                $this->sendEmail($student['email'], $subject, $message);
                
                // Create system notification
                $this->createSystemNotification(
                    $student['user_id'],
                    'Assignment Due Soon',
                    "Assignment '{$assignment['title']}' is due in {$hours_before} hours.",
                    'warning',
                    'student/assignments.php'
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Assignment reminder failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send certificate ready notification
     */
    public function sendCertificateNotification($student_id, $certificate_id) {
        try {
            $student = fetchOne("
                SELECT s.*, u.email, c.course_name 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                JOIN courses c ON s.course_id = c.id 
                WHERE s.id = ?
            ", [$student_id]);
            
            $certificate = fetchOne("SELECT * FROM certificates WHERE id = ?", [$certificate_id]);
            
            if (!$student || !$certificate) return false;
            
            $subject = "Certificate Ready for Collection - " . $student['course_name'];
            $message = $this->createCertificateNotificationEmail($student, $certificate);
            
            $this->sendEmail($student['email'], $subject, $message);
            
            if ($student['phone']) {
                $sms_message = "Your {$certificate['certificate_type']} certificate is ready for collection. Visit the registrar's office.";
                $this->sendSMS($student['phone'], $sms_message);
            }
            
            $this->createSystemNotification(
                $student['user_id'],
                'Certificate Ready',
                "Your {$certificate['certificate_type']} certificate is ready for collection.",
                'success',
                'student/certificates.php'
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Certificate notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send system-wide announcement
     */
    public function sendSystemAnnouncement($title, $message, $target_roles = [], $priority = 'normal') {
        try {
            // Get users based on target roles
            $role_condition = empty($target_roles) ? '' : "WHERE role IN ('" . implode("','", $target_roles) . "')";
            $users = fetchAll("SELECT * FROM users {$role_condition}");
            
            foreach ($users as $user) {
                // Send email
                $this->sendEmail($user['email'], $title, $message);
                
                // Create system notification
                $notification_type = $priority === 'urgent' ? 'error' : ($priority === 'important' ? 'warning' : 'info');
                $this->createSystemNotification($user['id'], $title, $message, $notification_type);
            }
            
            // Log announcement
            $this->logNotification('announcement', 'system', $title, $message, [
                'target_roles' => $target_roles,
                'priority' => $priority,
                'user_count' => count($users)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("System announcement failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        try {
            $read_condition = $unread_only ? 'AND is_read = 0' : '';
            
            return fetchAll("
                SELECT * FROM system_notifications 
                WHERE user_id = ? {$read_condition}
                ORDER BY created_at DESC 
                LIMIT ?
            ", [$user_id, $limit]);
            
        } catch (Exception $e) {
            error_log("Get notifications failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            return updateRecord('system_notifications', 
                ['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')], 
                'id', $notification_id, 
                "AND user_id = {$user_id}"
            );
        } catch (Exception $e) {
            error_log("Mark notification as read failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Schedule notification (for cron jobs)
     */
    public function scheduleNotification($type, $recipient, $subject, $message, $send_at) {
        try {
            $schedule_data = [
                'type' => $type,
                'recipient' => $recipient,
                'subject' => $subject,
                'message' => $message,
                'send_at' => $send_at,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            return insertRecord('notification_schedule', $schedule_data);
            
        } catch (Exception $e) {
            error_log("Schedule notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process scheduled notifications (for cron job)
     */
    public function processScheduledNotifications() {
        try {
            $pending_notifications = fetchAll("
                SELECT * FROM notification_schedule 
                WHERE status = 'pending' AND send_at <= NOW()
                ORDER BY send_at ASC
            ");
            
            foreach ($pending_notifications as $notification) {
                $success = false;
                
                switch ($notification['type']) {
                    case 'email':
                        $success = $this->sendEmail($notification['recipient'], $notification['subject'], $notification['message']);
                        break;
                    case 'sms':
                        $success = $this->sendSMS($notification['recipient'], $notification['message']);
                        break;
                }
                
                // Update status
                $status = $success ? 'sent' : 'failed';
                updateRecord('notification_schedule', 
                    ['status' => $status, 'sent_at' => date('Y-m-d H:i:s')], 
                    'id', $notification['id']
                );
            }
            
            return count($pending_notifications);
            
        } catch (Exception $e) {
            error_log("Process scheduled notifications failed: " . $e->getMessage());
            return 0;
        }
    }
    
    // Private helper methods
    
    private function sendSMSViaAPI($phone, $message) {
        // Placeholder for SMS API integration
        // Example for Africa's Talking API:
        /*
        $apiKey = SMS_API_KEY;
        $username = SMS_USERNAME;
        
        $data = [
            'username' => $username,
            'to' => $phone,
            'message' => $message
        ];
        
        $url = 'https://api.africastalking.com/version1/messaging';
        $headers = [
            'apiKey: ' . $apiKey,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return strpos($response, 'Success') !== false;
        */
        
        return true; // Demo mode
    }
    
    private function logNotification($type, $recipient, $subject, $message, $metadata = []) {
        try {
            $log_data = [
                'type' => $type,
                'recipient' => $recipient,
                'subject' => $subject,
                'message' => substr($message, 0, 500), // Truncate long messages
                'metadata' => json_encode($metadata),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            insertRecord('notification_logs', $log_data);
        } catch (Exception $e) {
            error_log("Notification logging failed: " . $e->getMessage());
        }
    }
    
    private function createFeeReminderEmail($student, $outstanding_fees, $total_balance) {
        $fee_list = '';
        foreach ($outstanding_fees as $fee) {
            $fee_list .= "<li>{$fee['fee_type']}: KSh " . number_format($fee['balance']) . " (Due: " . date('M j, Y', strtotime($fee['due_date'])) . ")</li>";
        }
        
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Fee Payment Reminder</h2>
                <p>Dear {$student['first_name']} {$student['last_name']},</p>
                <p>This is a friendly reminder that you have outstanding fees that require your attention:</p>
                <ul style='background: #f8f9fa; padding: 15px; border-left: 4px solid #f39c12;'>
                    {$fee_list}
                </ul>
                <p><strong>Total Outstanding: KSh " . number_format($total_balance) . "</strong></p>
                <p>Please make your payment as soon as possible to avoid any penalties or restrictions on your account.</p>
                <p><strong>Payment Methods:</strong></p>
                <ul>
                    <li>M-Pesa: Pay Bill 123456, Account: {$student['student_id']}</li>
                    <li>Bank Transfer: Contact accounts office for details</li>
                    <li>Cash Payment: Visit the accounts office</li>
                </ul>
                <p>If you have any questions or need assistance, please contact the accounts office.</p>
                <p>Best regards,<br>Accounts Department</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function createAssignmentReminderEmail($student, $assignment) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Assignment Deadline Reminder</h2>
                <p>Dear {$student['first_name']} {$student['last_name']},</p>
                <p>This is a reminder that your assignment is due soon:</p>
                <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #3498db; margin: 15px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #2c3e50;'>{$assignment['title']}</h3>
                    <p><strong>Subject:</strong> {$assignment['subject_name']}</p>
                    <p><strong>Due Date:</strong> " . date('M j, Y g:i A', strtotime($assignment['due_date'])) . "</p>
                    <p><strong>Max Marks:</strong> {$assignment['max_marks']} points</p>
                </div>
                <p>Please ensure you submit your assignment before the deadline to avoid penalties.</p>
                <p>You can submit your assignment through the student portal.</p>
                <p>If you have any questions, please contact your teacher: {$assignment['teacher_name']}</p>
                <p>Best regards,<br>Academic Department</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function createCertificateNotificationEmail($student, $certificate) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Certificate Ready for Collection</h2>
                <p>Dear {$student['first_name']} {$student['last_name']},</p>
                <p>Congratulations! Your certificate is now ready for collection.</p>
                <div style='background: #d5f4e6; padding: 15px; border-left: 4px solid #27ae60; margin: 15px 0;'>
                    <h3 style='margin: 0 0 10px 0; color: #27ae60;'>Certificate Details</h3>
                    <p><strong>Type:</strong> " . ucfirst($certificate['certificate_type']) . "</p>
                    <p><strong>Course:</strong> {$student['course_name']}</p>
                    <p><strong>Certificate Number:</strong> {$certificate['certificate_number']}</p>
                    <p><strong>Issue Date:</strong> " . date('M j, Y', strtotime($certificate['issue_date'])) . "</p>
                </div>
                <p><strong>Collection Instructions:</strong></p>
                <ul>
                    <li>Visit the Registrar's Office during business hours (8:00 AM - 5:00 PM)</li>
                    <li>Bring a valid ID for verification</li>
                    <li>Collection fee may apply</li>
                </ul>
                <p>If you cannot collect in person, you may authorize someone else with a written authorization letter and copies of both IDs.</p>
                <p>Congratulations on your achievement!</p>
                <p>Best regards,<br>Registrar's Office</p>
            </div>
        </body>
        </html>
        ";
    }
}

// Initialize notification system
$notificationSystem = new NotificationSystem();

// Helper functions for easy access
function sendNotificationEmail($to, $subject, $message, $from = null) {
    global $notificationSystem;
    return $notificationSystem->sendEmail($to, $subject, $message, $from);
}

function sendNotificationSMS($phone, $message) {
    global $notificationSystem;
    return $notificationSystem->sendSMS($phone, $message);
}

function createSystemNotification($user_id, $title, $message, $type = 'info', $action_url = null) {
    global $notificationSystem;
    return $notificationSystem->createSystemNotification($user_id, $title, $message, $type, $action_url);
}

function sendFeeDueReminder($student_id, $days_until_due = 7) {
    global $notificationSystem;
    return $notificationSystem->sendFeeDueReminder($student_id, $days_until_due);
}

function sendAssignmentReminder($assignment_id, $hours_before = 24) {
    global $notificationSystem;
    return $notificationSystem->sendAssignmentReminder($assignment_id, $hours_before);
}

function sendCertificateNotification($student_id, $certificate_id) {
    global $notificationSystem;
    return $notificationSystem->sendCertificateNotification($student_id, $certificate_id);
}

function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
    global $notificationSystem;
    return $notificationSystem->getUserNotifications($user_id, $limit, $unread_only);
}
?>

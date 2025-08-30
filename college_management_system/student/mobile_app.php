<?php
/**
 * Mobile-First Student Portal
 * Progressive Web App with offline capabilities and push notifications
 */

define('CMS_ACCESS', true);
require_once __DIR__ . '/../authentication.php';
require_once __DIR__ . '/../header.php';

// Require student role
Authentication::requireRole('student');

$user = Authentication::getCurrentUser();

// Get student information
try {
    $student = fetchOne("
        SELECT s.*, c.course_name, c.duration_months
        FROM students s 
        JOIN courses c ON s.course_id = c.id 
        WHERE s.user_id = ?
    ", [$user['id']]);
    
    if (!$student) {
        throw new Exception("Student profile not found");
    }
} catch (Exception $e) {
    $student = [
        'id' => 1,
        'student_id' => 'ST001',
        'first_name' => 'Demo',
        'last_name' => 'Student',
        'course_name' => 'Computer Science',
        'phone' => '+254700123456',
        'email' => 'demo@student.com'
    ];
}

// Get quick stats
try {
    $fee_balance = fetchOne("SELECT SUM(balance) as total FROM student_fees WHERE student_id = ? AND balance > 0", [$student['id']])['total'] ?? 0;
    $pending_assignments = fetchOne("SELECT COUNT(*) as count FROM assignments a JOIN subjects s ON a.subject_id = s.id JOIN courses c ON s.course_id = c.id WHERE c.id = ? AND a.due_date >= CURDATE() AND a.id NOT IN (SELECT assignment_id FROM assignment_submissions WHERE student_id = ?)", [$student['course_id'] ?? 1, $student['id']])['count'] ?? 3;
    $current_gpa = 78.5; // Demo value
    $attendance_rate = 92; // Demo value
} catch (Exception $e) {
    $fee_balance = 25000;
    $pending_assignments = 3;
    $current_gpa = 78.5;
    $attendance_rate = 92;
}

// Get recent notifications
$recent_notifications = [
    [
        'id' => 1,
        'title' => 'Assignment Due Tomorrow',
        'message' => 'Database Systems assignment is due tomorrow at 11:59 PM',
        'type' => 'warning',
        'time' => '2 hours ago',
        'read' => false
    ],
    [
        'id' => 2,
        'title' => 'Fee Payment Reminder',
        'message' => 'Your fee balance of KSh 25,000 is due soon',
        'type' => 'info',
        'time' => '1 day ago',
        'read' => false
    ],
    [
        'id' => 3,
        'title' => 'New Grade Posted',
        'message' => 'Your grade for Programming Assignment 2 has been posted',
        'type' => 'success',
        'time' => '2 days ago',
        'read' => true
    ]
];

// Get today's schedule
$todays_schedule = [
    [
        'time' => '08:00 - 10:00',
        'subject' => 'Database Systems',
        'type' => 'Lecture',
        'venue' => 'Computer Lab 1',
        'teacher' => 'Dr. Smith'
    ],
    [
        'time' => '10:30 - 12:30',
        'subject' => 'Web Development',
        'type' => 'Practical',
        'venue' => 'Computer Lab 2',
        'teacher' => 'Prof. Johnson'
    ],
    [
        'time' => '14:00 - 16:00',
        'subject' => 'Software Engineering',
        'type' => 'Lecture',
        'venue' => 'Lecture Hall A',
        'teacher' => 'Dr. Brown'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Mobile Portal</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3498db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Student Portal">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="../manifest.json">
    
    <!-- Icons -->
    <link rel="apple-touch-icon" href="../icons/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="../icons/icon-192x192.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }
        
        .mobile-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }
        
        .mobile-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-info h2 {
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        
        .user-info p {
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 1.2em;
            transition: background 0.2s;
        }
        
        .header-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .quick-stat {
            background: rgba(255,255,255,0.15);
            padding: 12px;
            border-radius: 10px;
            text-align: center;
        }
        
        .quick-stat-value {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .quick-stat-label {
            font-size: 0.8em;
            opacity: 0.9;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .section-action {
            color: #3498db;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .notification-item.warning {
            border-left-color: #f39c12;
        }
        
        .notification-item.success {
            border-left-color: #27ae60;
        }
        
        .notification-item.unread {
            background: #e3f2fd;
        }
        
        .notification-icon {
            font-size: 1.2em;
            margin-top: 2px;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.9em;
        }
        
        .notification-message {
            color: #6c757d;
            font-size: 0.8em;
            margin-bottom: 4px;
        }
        
        .notification-time {
            color: #95a5a6;
            font-size: 0.7em;
        }
        
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .schedule-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #3498db;
        }
        
        .schedule-time {
            background: #3498db;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8em;
            font-weight: bold;
            text-align: center;
            min-width: 80px;
        }
        
        .schedule-details {
            flex: 1;
        }
        
        .schedule-subject {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .schedule-meta {
            color: #6c757d;
            font-size: 0.8em;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .action-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .action-card.fees {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .action-card.assignments {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .action-card.grades {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .action-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .action-subtitle {
            font-size: 0.8em;
            opacity: 0.9;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #7f8c8d;
            padding: 8px;
            border-radius: 10px;
            transition: all 0.2s;
            min-width: 60px;
        }
        
        .nav-item.active {
            color: #3498db;
            background: #e3f2fd;
        }
        
        .nav-icon {
            font-size: 1.2em;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 0.7em;
            font-weight: 500;
        }
        
        .offline-indicator {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #e74c3c;
            color: white;
            text-align: center;
            padding: 10px;
            font-size: 0.9em;
            transform: translateY(-100%);
            transition: transform 0.3s;
            z-index: 1000;
        }
        
        .offline-indicator.show {
            transform: translateY(0);
        }
        
        .fab {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5em;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
            transition: all 0.2s;
            z-index: 99;
        }
        
        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.6);
        }
        
        .chatbot-widget {
            position: fixed;
            bottom: 150px;
            right: 20px;
            width: 300px;
            max-width: calc(100vw - 40px);
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: none;
            z-index: 98;
        }
        
        .chatbot-header {
            background: #3498db;
            color: white;
            padding: 15px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chatbot-messages {
            height: 300px;
            overflow-y: auto;
            padding: 15px;
        }
        
        .chatbot-input {
            padding: 15px;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 10px;
        }
        
        .chatbot-input input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }
        
        .chatbot-input button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                padding-bottom: 80px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .quick-stat {
                padding: 8px;
            }
            
            .quick-stat-value {
                font-size: 1.2em;
            }
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pull-to-refresh {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Offline Indicator -->
        <div class="offline-indicator" id="offline-indicator">
            📡 You're offline. Some features may be limited.
        </div>
        
        <!-- Mobile Header -->
        <div class="mobile-header">
            <div class="header-top">
                <div class="user-info">
                    <h2>Hello, <?= htmlspecialchars($student['first_name']) ?>! 👋</h2>
                    <p><?= htmlspecialchars($student['student_id']) ?> • <?= htmlspecialchars($student['course_name']) ?></p>
                </div>
                <div class="header-actions">
                    <button class="header-btn" onclick="toggleNotifications()" id="notifications-btn">
                        🔔
                        <?php if (count(array_filter($recent_notifications, function($n) { return !$n['read']; })) > 0): ?>
                        <span class="notification-badge"><?= count(array_filter($recent_notifications, function($n) { return !$n['read']; })) ?></span>
                        <?php endif; ?>
                    </button>
                    <button class="header-btn" onclick="refreshData()">🔄</button>
                </div>
            </div>
            
            <div class="quick-stats">
                <div class="quick-stat">
                    <div class="quick-stat-value">KSh <?= number_format($fee_balance) ?></div>
                    <div class="quick-stat-label">Fee Balance</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value"><?= $pending_assignments ?></div>
                    <div class="quick-stat-label">Pending Tasks</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value"><?= number_format($current_gpa, 1) ?>%</div>
                    <div class="quick-stat-label">Current GPA</div>
                </div>
                <div class="quick-stat">
                    <div class="quick-stat-value"><?= $attendance_rate ?>%</div>
                    <div class="quick-stat-label">Attendance</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Pull to Refresh -->
            <div class="pull-to-refresh" id="pull-to-refresh" style="display: none;">
                <div class="loading"></div>
                <p>Refreshing...</p>
            </div>
            
            <!-- Quick Actions -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Quick Actions</div>
                </div>
                <div class="quick-actions">
                    <a href="fee_statement.php" class="action-card fees">
                        <div class="action-icon">💳</div>
                        <div class="action-title">Pay Fees</div>
                        <div class="action-subtitle">KSh <?= number_format($fee_balance) ?> due</div>
                    </a>
                    <a href="assignments.php" class="action-card assignments">
                        <div class="action-icon">📝</div>
                        <div class="action-title">Assignments</div>
                        <div class="action-subtitle"><?= $pending_assignments ?> pending</div>
                    </a>
                    <a href="progress.php" class="action-card grades">
                        <div class="action-icon">📊</div>
                        <div class="action-title">My Grades</div>
                        <div class="action-subtitle">GPA: <?= number_format($current_gpa, 1) ?>%</div>
                    </a>
                    <a href="../library/dashboard.php" class="action-card">
                        <div class="action-icon">📚</div>
                        <div class="action-title">Library</div>
                        <div class="action-subtitle">Browse books</div>
                    </a>
                </div>
            </div>
            
            <!-- Today's Schedule -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Today's Schedule</div>
                    <a href="timetable.php" class="section-action">View All</a>
                </div>
                <div class="schedule-list">
                    <?php foreach ($todays_schedule as $class): ?>
                    <div class="schedule-item">
                        <div class="schedule-time"><?= htmlspecialchars($class['time']) ?></div>
                        <div class="schedule-details">
                            <div class="schedule-subject"><?= htmlspecialchars($class['subject']) ?></div>
                            <div class="schedule-meta">
                                <?= htmlspecialchars($class['type']) ?> • <?= htmlspecialchars($class['venue']) ?> • <?= htmlspecialchars($class['teacher']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Notifications -->
            <div class="section">
                <div class="section-header">
                    <div class="section-title">Recent Updates</div>
                    <a href="#" class="section-action">Mark All Read</a>
                </div>
                <div class="notifications-list">
                    <?php foreach (array_slice($recent_notifications, 0, 3) as $notification): ?>
                    <div class="notification-item <?= $notification['type'] ?> <?= !$notification['read'] ? 'unread' : '' ?>">
                        <div class="notification-icon">
                            <?php if ($notification['type'] === 'warning'): ?>⚠️
                            <?php elseif ($notification['type'] === 'success'): ?>✅
                            <?php else: ?>ℹ️<?php endif; ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title"><?= htmlspecialchars($notification['title']) ?></div>
                            <div class="notification-message"><?= htmlspecialchars($notification['message']) ?></div>
                            <div class="notification-time"><?= htmlspecialchars($notification['time']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <a href="mobile_app.php" class="nav-item active">
                <div class="nav-icon">🏠</div>
                <div class="nav-label">Home</div>
            </a>
            <a href="assignments.php" class="nav-item">
                <div class="nav-icon">📝</div>
                <div class="nav-label">Tasks</div>
            </a>
            <a href="progress.php" class="nav-item">
                <div class="nav-icon">📊</div>
                <div class="nav-label">Progress</div>
            </a>
            <a href="../library/dashboard.php" class="nav-item">
                <div class="nav-icon">📚</div>
                <div class="nav-label">Library</div>
            </a>
            <a href="dashboard.php" class="nav-item">
                <div class="nav-icon">⚙️</div>
                <div class="nav-label">More</div>
            </a>
        </div>
        
        <!-- Floating Action Button -->
        <button class="fab" onclick="toggleChatbot()">💬</button>
        
        <!-- Chatbot Widget -->
        <div class="chatbot-widget" id="chatbot-widget">
            <div class="chatbot-header">
                <div>AI Assistant</div>
                <button onclick="toggleChatbot()" style="background: none; border: none; color: white; font-size: 1.2em;">✕</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div style="text-align: center; color: #7f8c8d; padding: 20px;">
                    👋 Hi! I'm your AI assistant. How can I help you today?
                </div>
            </div>
            <div class="chatbot-input">
                <input type="text" id="chatbot-input" placeholder="Type your message..." onkeypress="handleChatbotEnter(event)">
                <button onclick="sendChatbotMessage()">Send</button>
            </div>
        </div>
    </div>
    
    <script>
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
        
        // Online/Offline Detection
        function updateOnlineStatus() {
            const offlineIndicator = document.getElementById('offline-indicator');
            if (navigator.onLine) {
                offlineIndicator.classList.remove('show');
            } else {
                offlineIndicator.classList.add('show');
            }
        }
        
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();
        
        // Pull to Refresh
        let startY = 0;
        let currentY = 0;
        let pullDistance = 0;
        let isPulling = false;
        
        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });
        
        document.addEventListener('touchmove', (e) => {
            if (isPulling && window.scrollY === 0) {
                currentY = e.touches[0].clientY;
                pullDistance = currentY - startY;
                
                if (pullDistance > 0) {
                    e.preventDefault();
                    const pullToRefresh = document.getElementById('pull-to-refresh');
                    if (pullDistance > 100) {
                        pullToRefresh.style.display = 'block';
                    }
                }
            }
        });
        
        document.addEventListener('touchend', () => {
            if (isPulling && pullDistance > 100) {
                refreshData();
            }
            isPulling = false;
            pullDistance = 0;
            document.getElementById('pull-to-refresh').style.display = 'none';
        });
        
        // Refresh Data
        function refreshData() {
            const pullToRefresh = document.getElementById('pull-to-refresh');
            pullToRefresh.style.display = 'block';
            
            // Simulate data refresh
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
        
        // Chatbot Functions
        function toggleChatbot() {
            const widget = document.getElementById('chatbot-widget');
            widget.style.display = widget.style.display === 'block' ? 'none' : 'block';
        }
        
        function handleChatbotEnter(event) {
            if (event.key === 'Enter') {
                sendChatbotMessage();
            }
        }
        
        async function sendChatbotMessage() {
            const input = document.getElementById('chatbot-input');
            const messages = document.getElementById('chatbot-messages');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Add user message
            const userMessage = document.createElement('div');
            userMessage.style.cssText = 'background: #3498db; color: white; padding: 10px; border-radius: 15px; margin: 10px 0; margin-left: 50px; text-align: right;';
            userMessage.textContent = message;
            messages.appendChild(userMessage);
            
            input.value = '';
            messages.scrollTop = messages.scrollHeight;
            
            // Show typing indicator
            const typingIndicator = document.createElement('div');
            typingIndicator.style.cssText = 'background: #f1f1f1; padding: 10px; border-radius: 15px; margin: 10px 0; margin-right: 50px;';
            typingIndicator.innerHTML = '<div class="loading"></div> AI is typing...';
            messages.appendChild(typingIndicator);
            messages.scrollTop = messages.scrollHeight;
            
            try {
                // Send to chatbot API
                const response = await fetch('../api/chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message })
                });
                
                const data = await response.json();
                
                // Remove typing indicator
                messages.removeChild(typingIndicator);
                
                // Add bot response
                const botMessage = document.createElement('div');
                botMessage.style.cssText = 'background: #f1f1f1; padding: 10px; border-radius: 15px; margin: 10px 0; margin-right: 50px;';
                botMessage.textContent = data.response || 'Sorry, I couldn\'t process that request.';
                messages.appendChild(botMessage);
                
                // Add quick actions if available
                if (data.quick_actions && data.quick_actions.length > 0) {
                    const actionsDiv = document.createElement('

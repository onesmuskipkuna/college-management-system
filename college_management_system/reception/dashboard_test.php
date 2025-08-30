<?php
/**
 * Reception Dashboard - Test Version (No Authentication)
 * Front desk operations and student services
 */

// Mock user data for testing
$user = [
    'first_name' => 'Test',
    'last_name' => 'Receptionist',
    'role' => 'reception'
];

// Get dashboard statistics with proper error handling
try {
    // In a real implementation, these would be database queries
    $stats = [
        'daily_visitors' => 28, // Demo data
        'pending_inquiries' => 12, // Demo data
        'complaints_today' => 5, // Demo data
        'fee_inquiries' => 15 // Demo data
    ];
} catch (Exception $e) {
    error_log("Error fetching dashboard statistics: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $stats = ['daily_visitors' => 0, 'pending_inquiries' => 0, 'complaints_today' => 0, 'fee_inquiries' => 0];
}

// Get recent inquiries with error handling
try {
    $recent_inquiries = [
        ['name' => 'John Smith', 'type' => 'Course Information', 'time' => '10:30 AM', 'status' => 'pending'],
        ['name' => 'Mary Johnson', 'type' => 'Fee Payment', 'time' => '11:15 AM', 'status' => 'resolved'],
        ['name' => 'David Wilson', 'type' => 'Admission Process', 'time' => '12:00 PM', 'status' => 'in_progress'],
        ['name' => 'Sarah Brown', 'type' => 'Certificate Collection', 'time' => '1:30 PM', 'status' => 'pending']
    ];
} catch (Exception $e) {
    error_log("Error fetching recent inquiries: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $recent_inquiries = [];
}

// Get recent complaints with error handling
try {
    $recent_complaints = [
        ['student' => 'Alice Davis', 'issue' => 'Delayed certificate processing', 'priority' => 'high', 'time' => '9:00 AM'],
        ['student' => 'Bob Wilson', 'issue' => 'Fee payment not reflected', 'priority' => 'medium', 'time' => '10:45 AM'],
        ['student' => 'Carol White', 'issue' => 'Timetable conflict', 'priority' => 'low', 'time' => '2:15 PM']
    ];
} catch (Exception $e) {
    error_log("Error fetching recent complaints: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $recent_complaints = [];
}

// Get today's appointments with error handling
try {
    $appointments = [
        ['time' => '2:00 PM', 'visitor' => 'Mr. James Parker', 'purpose' => 'Parent meeting with registrar'],
        ['time' => '3:30 PM', 'visitor' => 'Ms. Linda Green', 'purpose' => 'Course consultation'],
        ['time' => '4:00 PM', 'visitor' => 'Dr. Michael Brown', 'purpose' => 'Partnership discussion']
    ];
} catch (Exception $e) {
    error_log("Error fetching appointments: " . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $appointments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reception Dashboard - College Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .dashboard-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .current-time {
            font-size: 1.2em;
            color: #3498db;
            font-weight: bold;
            margin-top: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .stat-card.pending {
            border-left: 4px solid #f39c12;
        }

        .stat-card.urgent {
            border-left: 4px solid #e74c3c;
        }

        .stat-icon {
            font-size: 2.5em;
            opacity: 0.8;
        }

        .stat-content h3 {
            font-size: 2em;
            margin: 0;
            color: #2c3e50;
        }

        .stat-content p {
            margin: 5px 0 0 0;
            color: #7f8c8d;
        }

        .dashboard-section {
            margin-bottom: 30px;
        }

        .dashboard-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            font-size: 1.5em;
        }

        .dashboard-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .half-width {
            margin-bottom: 0;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .action-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .action-card h3 {
            color: #2c3e50;
            margin: 10px 0;
        }

        .action-card p {
            color: #7f8c8d;
            margin: 0;
        }

        .inquiry-list, .complaint-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .inquiry-item, .complaint-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inquiry-item:last-child, .complaint-item:last-child {
            border-bottom: none;
        }

        .inquiry-content h4, .complaint-content h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .inquiry-content p, .complaint-content p {
            margin: 2px 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .status-badge, .priority-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background-color: #f39c12;
            color: white;
        }

        .status-badge.resolved {
            background-color: #27ae60;
            color: white;
        }

        .status-badge.in_progress {
            background-color: #3498db;
            color: white;
        }

        .priority-badge.high {
            background-color: #e74c3c;
            color: white;
        }

        .priority-badge.medium {
            background-color: #f39c12;
            color: white;
        }

        .priority-badge.low {
            background-color: #95a5a6;
            color: white;
        }

        .inquiry-actions, .complaint-actions {
            display: flex;
            gap: 5px;
            flex-direction: column;
        }

        .appointment-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .appointment-item {
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-time {
            text-align: center;
        }

        .appointment-time .time {
            font-size: 1.2em;
            font-weight: bold;
            color: #3498db;
        }

        .appointment-details h4 {
            margin: 0 0 5px 0;
            color: #2c3e50;
        }

        .appointment-details p {
            margin: 0;
            color: #7f8c8d;
        }

        .appointment-actions {
            display: flex;
            gap: 5px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.2s;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75em;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background-color: #229954;
        }

        .btn-warning {
            background-color: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background-color: #e67e22;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-row {
                grid-template-columns: 1fr;
            }
            
            .inquiry-item, .complaint-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .inquiry-actions, .complaint-actions {
                flex-direction: row;
                width: 100%;
            }
            
            .appointment-item {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Reception Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($user['first_name'] ?? 'Receptionist') ?>!</p>
            <div class="current-time">
                <span id="current-time"></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?= $stats['daily_visitors'] ?></h3>
                    <p>Daily Visitors</p>
                </div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">❓</div>
                <div class="stat-content">
                    <h3><?= $stats['pending_inquiries'] ?></h3>
                    <p>Pending Inquiries</p>
                </div>
            </div>
            
            <div class="stat-card urgent">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3><?= $stats['complaints_today'] ?></h3>
                    <p>Complaints Today</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?= $stats['fee_inquiries'] ?></h3>
                    <p>Fee Inquiries</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-section">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <div class="action-card">
                    <div class="action-icon">📞</div>
                    <h3>Handle Inquiries</h3>
                    <p>Respond to visitor and student inquiries</p>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">💳</div>
                    <h3>Fee Statements</h3>
                    <p>View and print student fee statements</p>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">📊</div>
                    <h3>Student Progress</h3>
                    <p>Check student academic progress</p>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">📝</div>
                    <h3>Manage Complaints</h3>
                    <p>Record and track student complaints</p>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">📋</div>
                    <h3>Visitor Log</h3>
                    <p>Register new visitors and appointments</p>
                </div>
                
                <div class="action-card">
                    <div class="action-icon">🔍</div>
                    <h3>Quick Search</h3>
                    <p>Find student information quickly</p>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <!-- Recent Inquiries -->
            <div class="dashboard-section half-width">
                <h2>Recent Inquiries</h2>
                <div class="inquiry-list">
                    <?php if (empty($recent_inquiries)): ?>
                        <p style="padding: 20px; text-align: center; color: #7f8c8d;">No recent inquiries found.</p>
                    <?php else: ?>
                        <?php foreach ($recent_inquiries as $inquiry): ?>
                        <div class="inquiry-item">
                            <div class="inquiry-content">
                                <h4><?= htmlspecialchars($inquiry['name']) ?></h4>
                                <p><strong>Type:</strong> <?= htmlspecialchars($inquiry['type']) ?></p>
                                <p><strong>Time:</strong> <?= htmlspecialchars($inquiry['time']) ?></p>
                                <span class="status-badge <?= $inquiry['status'] ?>"><?= ucfirst(str_replace('_', ' ', $inquiry['status'])) ?></span>
                            </div>
                            <div class="inquiry-actions">
                                <?php if ($inquiry['status'] === 'pending'): ?>
                                <button class="btn btn-primary btn-sm">Respond</button>
                                <?php endif; ?>
                                <button class="btn btn-secondary btn-sm">Details</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Complaints -->
            <div class="dashboard-section half-width">
                <h2>Recent Complaints</h2>
                <div class="complaint-list">
                    <?php if (empty($recent_complaints)): ?>
                        <p style="padding: 20px; text-align: center; color: #7f8c8d;">No recent complaints found.</p>
                    <?php else: ?>
                        <?php foreach ($recent_complaints as $complaint): ?>
                        <div class="complaint-item">
                            <div class="complaint-content">
                                <h4><?= htmlspecialchars($complaint['student']) ?></h4>
                                <p><?= htmlspecialchars($complaint['issue']) ?></p>
                                <p><strong>Time:</strong> <?= htmlspecialchars($complaint['time']) ?></p>
                                <span class="priority-badge <?= $complaint['priority'] ?>"><?= ucfirst($complaint['priority']) ?> Priority</span>
                            </div>
                            <div class="complaint-actions">
                                <button class="btn btn-success btn-sm">Resolve</button>
                                <button class="btn btn-warning btn-sm">Escalate</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="dashboard-section">
            <h2>Today's Appointments</h2>
            <div class="appointment-list">
                <?php if (empty($appointments)): ?>
                    <p style="padding: 20px; text-align: center; color: #7f8c8d;">No appointments scheduled for today.</p>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-item">
                        <div class="appointment-time">
                            <span class="time"><?= htmlspecialchars($appointment['time']) ?></span>
                        </div>
                        <div class="appointment-details">
                            <h4><?= htmlspecialchars($appointment['visitor']) ?></h4>
                            <p><?= htmlspecialchars($appointment['purpose']) ?></p>
                        </div>
                        <div class="appointment-actions">
                            <button class="btn btn-success btn-sm">Check In</button>
                            <button class="btn btn-secondary btn-sm">Reschedule</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('current-time').innerHTML = `${dateString}<br>${timeString}`;
        }

        // Update time every second
        setInterval(updateTime, 1000);
        updateTime(); // Initial call

        // Add click handlers for action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function() {
                const title = this.querySelector('h3').textContent;
                alert(`${title} functionality would be implemented here.`);
            });
        });

        // Add click handlers for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                alert(`${this.textContent} functionality would be implemented here.`);
            });
        });
    </script>
</body>
</html>

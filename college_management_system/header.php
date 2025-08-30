<?php
/**
 * Common Header File
 * Includes navigation and user information
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/authentication.php';

// Get current user
$current_user = Authentication::getCurrentUser();
$page_title = $page_title ?? 'College Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="College Management System - Comprehensive educational institution management">
    <meta name="author" content="College Management System">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/college_management_system/favicon.ico">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/college_management_system/css/styles.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>
    
    <!-- Additional CSS for specific pages -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="bg-gray-800 text-white">
        <div class="container mx-auto flex justify-between items-center p-4">
            <div class="logo">
                <a href="/college_management_system/" class="text-2xl font-bold">
                    CMS
                </a>
            </div>
                
                <!-- Navigation Menu -->
                <?php if ($current_user): ?>
                    <nav class="nav-menu">
                        <ul class="nav-menu">
                            <?php
                            $role = $current_user['role'];
                            $menu_items = [];
                            
                            // Define menu items based on user role
                            switch ($role) {
                                case 'student':
                                    $menu_items = [
                                        ['url' => '/college_management_system/student/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/student/profile.php', 'label' => 'Profile'],
                                        ['url' => '/college_management_system/student/timetable.php', 'label' => 'Timetable'],
                                        ['url' => '/college_management_system/student/assignments.php', 'label' => 'Assignments'],
                                        ['url' => '/college_management_system/student/exam_results.php', 'label' => 'Results'],
                                        ['url' => '/college_management_system/student/fee_statement.php', 'label' => 'Fees'],
                                    ];
                                    break;
                                    
                                case 'teacher':
                                    $menu_items = [
                                        ['url' => '/college_management_system/teacher/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/teacher/attendance.php', 'label' => 'Attendance'],
                                        ['url' => '/college_management_system/teacher/add_results.php', 'label' => 'Add Results'],
                                        ['url' => '/college_management_system/teacher/issue_assignment.php', 'label' => 'Assignments'],
                                        ['url' => '/college_management_system/teacher/upload_material.php', 'label' => 'Materials'],
                                    ];
                                    break;
                                    
                                case 'headteacher':
                                    $menu_items = [
                                        ['url' => '/college_management_system/headteacher/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/headteacher/manage_teachers.php', 'label' => 'Teachers'],
                                        ['url' => '/college_management_system/headteacher/manage_exams.php', 'label' => 'Exams'],
                                        ['url' => '/college_management_system/headteacher/approve_requests.php', 'label' => 'Approvals'],
                                        ['url' => '/college_management_system/headteacher/student_finance.php', 'label' => 'Finance'],
                                    ];
                                    break;
                                    
                                case 'registrar':
                                    $menu_items = [
                                        ['url' => '/college_management_system/registrar/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/registrar/admission.php', 'label' => 'Admissions'],
                                        ['url' => '/college_management_system/registrar/student_management.php', 'label' => 'Students'],
                                        ['url' => '/college_management_system/registrar/manage_courses.php', 'label' => 'Courses'],
                                        ['url' => '/college_management_system/registrar/certificate_issue.php', 'label' => 'Certificates'],
                                        ['url' => '/college_management_system/registrar/reports.php', 'label' => 'Reports'],
                                    ];
                                    break;
                                    
                                case 'accounts':
                                    $menu_items = [
                                        ['url' => '/college_management_system/accounts/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/accounts/fee_receive.php', 'label' => 'Receive Fees'],
                                        ['url' => '/college_management_system/accounts/invoice.php', 'label' => 'Invoices'],
                                        ['url' => '/college_management_system/accounts/fee_balance_report.php', 'label' => 'Fee Reports'],
                                        ['url' => '/college_management_system/accounts/course_fee_structure.php', 'label' => 'Fee Structure'],
                                        ['url' => '/college_management_system/accounts/p_l_report.php', 'label' => 'P&L Report'],
                                    ];
                                    break;
                                    
                                case 'reception':
                                    $menu_items = [
                                        ['url' => '/college_management_system/reception/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/reception/fee_statement_view.php', 'label' => 'Fee Statements'],
                                        ['url' => '/college_management_system/reception/student_progress.php', 'label' => 'Student Progress'],
                                        ['url' => '/college_management_system/reception/complaints.php', 'label' => 'Complaints'],
                                        ['url' => '/college_management_system/reception/enquiries.php', 'label' => 'Enquiries'],
                                    ];
                                    break;
                                    
                                case 'hr':
                                    $menu_items = [
                                        ['url' => '/college_management_system/hr/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/hr/create_employee.php', 'label' => 'Add Employee'],
                                        ['url' => '/college_management_system/hr/payroll.php', 'label' => 'Payroll'],
                                        ['url' => '/college_management_system/hr/manage_leave.php', 'label' => 'Leave Management'],
                                        ['url' => '/college_management_system/hr/staff_performance.php', 'label' => 'Performance'],
                                    ];
                                    break;
                                    
                                case 'hostel':
                                    $menu_items = [
                                        ['url' => '/college_management_system/hostel/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/hostel/admit_student.php', 'label' => 'Admit Students'],
                                        ['url' => '/college_management_system/hostel/manage_rooms.php', 'label' => 'Manage Rooms'],
                                        ['url' => '/college_management_system/hostel/feeding_register.php', 'label' => 'Feeding Register'],
                                    ];
                                    break;
                                    
                                case 'director':
                                    $menu_items = [
                                        ['url' => '/college_management_system/director/dashboard.php', 'label' => 'Dashboard'],
                                        ['url' => '/college_management_system/director/system_overview.php', 'label' => 'System Overview'],
                                        ['url' => '/college_management_system/director/financial_overview.php', 'label' => 'Financial Overview'],
                                        ['url' => '/college_management_system/director/user_management.php', 'label' => 'User Management'],
                                        ['url' => '/college_management_system/director/system_settings.php', 'label' => 'Settings'],
                                    ];
                                    break;
                            }
                            
                            // Display menu items
                            foreach ($menu_items as $item):
                                $is_active = (strpos($_SERVER['REQUEST_URI'], $item['url']) !== false) ? 'active' : '';
                            ?>
                                <li><a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $is_active; ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <!-- User Information -->
                <?php if ($current_user): ?>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($current_user['first_name'] ?: $current_user['username'], 0, 1)); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name">
                                <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                            </div>
                            <div class="user-role">
                                <?php echo htmlspecialchars(ucfirst($current_user['role'])); ?>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="/college_management_system/profile.php" class="btn btn-sm btn-outline-light">Profile</a>
                            <a href="/college_management_system/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="/college_management_system/login.php" class="btn btn-outline-light">Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Main Content Area -->
    <main>
        <div class="container">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show">
                    <?php 
                    echo htmlspecialchars($_SESSION['flash_message']);
                    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Page Header -->
            <?php if (isset($show_page_header) && $show_page_header): ?>
                <div class="page-header">
                    <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <?php if (isset($page_subtitle)): ?>
                        <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

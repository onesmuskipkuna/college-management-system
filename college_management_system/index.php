<?php
/**
 * College Management System - Main Landing Page
 * Entry point for the application
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/authentication.php';

// Check if user is already logged in
if (Authentication::isLoggedIn()) {
    $user = Authentication::getCurrentUser();
    $redirect_url = Authentication::getRedirectUrl($user['role']);
    header("Location: " . $redirect_url);
    exit;
}

// Page configuration
$page_title = 'Welcome to College Management System';
$show_page_header = false;

// Include header
include __DIR__ . '/header.php';
?>

<style>
    /* Landing page specific styles */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4rem 0;
        text-align: center;
        margin: -2rem -20px 3rem -20px;
    }
    
    .hero-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }
    
    .hero-subtitle {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
    }
    
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
    }
    
    .feature-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .feature-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
        color: white;
    }
    
    .login-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 3rem;
        margin: 3rem 0;
        text-align: center;
    }
    
    .role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }
    
    .role-card {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        text-decoration: none;
        color: #495057;
        transition: all 0.3s ease;
    }
    
    .role-card:hover {
        border-color: #007bff;
        color: #007bff;
        text-decoration: none;
        transform: translateY(-2px);
    }
    
    .stats-section {
        background: white;
        border-radius: 15px;
        padding: 3rem;
        margin: 3rem 0;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        text-align: center;
    }
    
    .stat-item {
        padding: 1rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #007bff;
        display: block;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 1rem;
        margin-top: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }
        
        .hero-section {
            padding: 2rem 0;
        }
        
        .feature-grid,
        .role-grid,
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">College Management System</h1>
        <p class="hero-subtitle">
            Comprehensive educational institution management solution for the digital age
        </p>
        <div class="hero-actions">
            <a href="#login" class="btn btn-light btn-lg">Get Started</a>
            <a href="#features" class="btn btn-outline-light btn-lg">Learn More</a>
        </div>
    </div>
</div>

<!-- Features Section -->
<section id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2>Powerful Features for Modern Education</h2>
            <p class="text-muted">Everything you need to manage your educational institution efficiently</p>
        </div>
        
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                    S
                </div>
                <h4>Student Management</h4>
                <p>Complete student lifecycle management from admission to graduation with automated fee invoicing and progress tracking.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    A
                </div>
                <h4>Academic Excellence</h4>
                <p>Comprehensive grading system, transcript generation, and certificate management with approval workflows.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    F
                </div>
                <h4>Financial Management</h4>
                <p>Advanced fee management with M-Pesa integration, automated invoicing, and comprehensive financial reporting.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                    T
                </div>
                <h4>Teacher Portal</h4>
                <p>Streamlined tools for attendance tracking, grade entry, assignment management, and student progress monitoring.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                    R
                </div>
                <h4>Reports & Analytics</h4>
                <p>Comprehensive reporting system with real-time analytics for informed decision making and institutional growth.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                    H
                </div>
                <h4>Hostel Management</h4>
                <p>Complete hostel administration including room allocation, feeding register, and accommodation fee management.</p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<?php
// Get system statistics
try {
    $total_students = fetchOne("SELECT COUNT(*) as count FROM students WHERE status = 'active'")['count'] ?? 0;
    $total_teachers = fetchOne("SELECT COUNT(*) as count FROM teachers WHERE status = 'active'")['count'] ?? 0;
    $total_courses = fetchOne("SELECT COUNT(*) as count FROM courses WHERE status = 'active'")['count'] ?? 0;
    $current_academic_year = getCurrentAcademicYear();
} catch (Exception $e) {
    $total_students = 0;
    $total_teachers = 0;
    $total_courses = 0;
    $current_academic_year = getCurrentAcademicYear();
}
?>

<div class="stats-section">
    <div class="container">
        <div class="text-center mb-4">
            <h3>Our Institution at a Glance</h3>
            <p class="text-muted">Academic Year <?php echo htmlspecialchars($current_academic_year); ?></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_students); ?></span>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_teachers); ?></span>
                <div class="stat-label">Teaching Staff</div>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($total_courses); ?></span>
                <div class="stat-label">Active Courses</div>
            </div>
            <div class="stat-item">
                <span class="stat-number">24/7</span>
                <div class="stat-label">System Availability</div>
            </div>
        </div>
    </div>
</div>

<!-- Login Section -->
<section id="login" class="login-section">
    <div class="container">
        <h3>Access Your Portal</h3>
        <p class="text-muted mb-4">Select your role to access the appropriate dashboard</p>
        
        <div class="role-grid">
            <a href="/college_management_system/student/login.php" class="role-card">
                <h5>Student Portal</h5>
                <p>Access your academic records, fees, and assignments</p>
            </a>
            
            <a href="/college_management_system/teacher/login.php" class="role-card">
                <h5>Teacher Portal</h5>
                <p>Manage classes, grades, and student progress</p>
            </a>
            
            <a href="/college_management_system/registrar/login.php" class="role-card">
                <h5>Registrar Office</h5>
                <p>Student admissions and academic administration</p>
            </a>
            
            <a href="/college_management_system/accounts/login.php" class="role-card">
                <h5>Accounts Department</h5>
                <p>Fee management and financial operations</p>
            </a>
            
            <a href="/college_management_system/hr/login.php" class="role-card">
                <h5>Human Resources</h5>
                <p>Staff management and payroll administration</p>
            </a>
            
            <a href="/college_management_system/login.php" class="role-card">
                <h5>General Login</h5>
                <p>All other staff and administrative access</p>
            </a>
        </div>
        
        <div class="mt-4">
            <a href="/college_management_system/login.php" class="btn btn-primary btn-lg">
                Universal Login Portal
            </a>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto text-center">
                <h3>Why Choose Our System?</h3>
                <p class="lead text-muted">
                    Built with modern technology and designed for educational institutions of all sizes. 
                    Our system provides a comprehensive solution that grows with your institution.
                </p>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <h5>Secure & Reliable</h5>
                        <p>Enterprise-grade security with role-based access control and data encryption.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>Mobile Friendly</h5>
                        <p>Responsive design that works perfectly on all devices and screen sizes.</p>
                    </div>
                    <div class="col-md-4">
                        <h5>24/7 Support</h5>
                        <p>Dedicated support team to ensure smooth operation of your institution.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Animate statistics on scroll
function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const finalValue = target.textContent;
                
                if (!isNaN(finalValue.replace(/,/g, ''))) {
                    const numValue = parseInt(finalValue.replace(/,/g, ''));
                    let currentValue = 0;
                    const increment = numValue / 50;
                    
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= numValue) {
                            target.textContent = finalValue;
                            clearInterval(timer);
                        } else {
                            target.textContent = Math.floor(currentValue).toLocaleString();
                        }
                    }, 30);
                }
                
                observer.unobserve(target);
            }
        });
    });
    
    statNumbers.forEach(stat => observer.observe(stat));
}

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if ('IntersectionObserver' in window) {
        animateStats();
    }
});
</script>

<?php include __DIR__ . '/footer.php'; ?>

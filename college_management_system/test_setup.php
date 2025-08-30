<?php
/**
 * Test Setup Script
 * Creates sample data for testing the college management system
 */

// Define access constant
define('CMS_ACCESS', true);
define('DEVELOPMENT_MODE', true);

// Include required files
require_once __DIR__ . '/db.php';

echo "<h1>College Management System - Test Setup</h1>";
echo "<p>Setting up test data...</p>";

try {
    // Start transaction
    beginTransaction();
    
    echo "<h3>1. Creating Sample Courses</h3>";
    
    // Check if courses already exist
    $existing_courses = fetchAll("SELECT COUNT(*) as count FROM courses");
    if ($existing_courses[0]['count'] == 0) {
        // Insert sample courses
        $courses = [
            ['CS101', 'Computer Science Diploma', 'Comprehensive computer science program covering programming, databases, and web development', 24],
            ['BM101', 'Business Management Certificate', 'Business management fundamentals including marketing, finance, and operations', 12],
            ['AC101', 'Accounting Certificate', 'Basic accounting principles and practices for small businesses', 18],
            ['IT201', 'Information Technology Diploma', 'Advanced IT skills including networking, cybersecurity, and system administration', 30],
            ['DM301', 'Digital Marketing Certificate', 'Modern digital marketing strategies and social media management', 6]
        ];
        
        foreach ($courses as $course) {
            insertRecord('courses', [
                'course_code' => $course[0],
                'course_name' => $course[1],
                'description' => $course[2],
                'duration_months' => $course[3],
                'status' => 'active'
            ]);
            echo "✓ Created course: {$course[1]}<br>";
        }
    } else {
        echo "✓ Courses already exist<br>";
    }
    
    echo "<h3>2. Creating Fee Structures</h3>";
    
    // Get course IDs
    $courses = fetchAll("SELECT id, course_name FROM courses");
    
    foreach ($courses as $course) {
        // Check if fee structure exists
        $existing_fees = fetchOne("SELECT COUNT(*) as count FROM fee_structure WHERE course_id = ?", [$course['id']]);
        
        if ($existing_fees['count'] == 0) {
            // Create fee structure based on course
            $base_fee = 30000; // Base tuition fee
            $registration_fee = 5000;
            
            // Adjust fees based on course duration
            if (strpos($course['course_name'], 'Diploma') !== false) {
                $base_fee = 50000;
                $lab_fee = 10000;
            } elseif (strpos($course['course_name'], 'Certificate') !== false) {
                $base_fee = 30000;
                $lab_fee = 5000;
            }
            
            // Insert fee structure
            insertRecord('fee_structure', [
                'course_id' => $course['id'],
                'fee_type' => 'Tuition Fee',
                'amount' => $base_fee,
                'is_mandatory' => 1,
                'description' => 'Semester tuition fee',
                'status' => 'active'
            ]);
            
            insertRecord('fee_structure', [
                'course_id' => $course['id'],
                'fee_type' => 'Registration Fee',
                'amount' => $registration_fee,
                'is_mandatory' => 1,
                'description' => 'One-time registration fee',
                'status' => 'active'
            ]);
            
            if (isset($lab_fee)) {
                insertRecord('fee_structure', [
                    'course_id' => $course['id'],
                    'fee_type' => 'Lab Fee',
                    'amount' => $lab_fee,
                    'is_mandatory' => 1,
                    'description' => 'Laboratory and practical sessions fee',
                    'status' => 'active'
                ]);
            }
            
            echo "✓ Created fee structure for: {$course['course_name']}<br>";
        }
    }
    
    echo "<h3>3. Creating Sample Users</h3>";
    
    // Create admin user if not exists
    if (!recordExists('users', 'username', 'admin')) {
        insertRecord('users', [
            'username' => 'admin',
            'email' => 'admin@college.edu',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'director',
            'status' => 'active'
        ]);
        echo "✓ Created admin user (username: admin, password: admin123)<br>";
    }
    
    // Create registrar user
    if (!recordExists('users', 'username', 'registrar')) {
        insertRecord('users', [
            'username' => 'registrar',
            'email' => 'registrar@college.edu',
            'password' => password_hash('reg123', PASSWORD_DEFAULT),
            'role' => 'registrar',
            'status' => 'active'
        ]);
        echo "✓ Created registrar user (username: registrar, password: reg123)<br>";
    }
    
    // Create accounts user
    if (!recordExists('users', 'username', 'accounts')) {
        insertRecord('users', [
            'username' => 'accounts',
            'email' => 'accounts@college.edu',
            'password' => password_hash('acc123', PASSWORD_DEFAULT),
            'role' => 'accounts',
            'status' => 'active'
        ]);
        echo "✓ Created accounts user (username: accounts, password: acc123)<br>";
    }
    
    // Create sample teacher
    if (!recordExists('users', 'username', 'teacher1')) {
        $teacher_user_id = insertRecord('users', [
            'username' => 'teacher1',
            'email' => 'teacher1@college.edu',
            'password' => password_hash('teach123', PASSWORD_DEFAULT),
            'role' => 'teacher',
            'status' => 'active'
        ]);
        
        // Create teacher profile
        insertRecord('teachers', [
            'user_id' => $teacher_user_id,
            'teacher_id' => 'TCH' . date('Y') . '0001',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'phone' => '0712345678',
            'specialization' => 'Computer Science',
            'hire_date' => date('Y-m-d'),
            'status' => 'active'
        ]);
        
        echo "✓ Created teacher user (username: teacher1, password: teach123)<br>";
    }
    
    // Create sample student
    if (!recordExists('users', 'username', 'student1')) {
        $student_user_id = insertRecord('users', [
            'username' => 'student1',
            'email' => 'student1@example.com',
            'password' => password_hash('stud123', PASSWORD_DEFAULT),
            'role' => 'student',
            'status' => 'active'
        ]);
        
        // Get first course ID
        $first_course = fetchOne("SELECT id FROM courses LIMIT 1");
        
        // Create student profile
        $student_id = insertRecord('students', [
            'user_id' => $student_user_id,
            'student_id' => 'STU' . date('Y') . '0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'id_type' => 'ID',
            'id_number' => '12345678',
            'phone' => '0723456789',
            'date_of_birth' => '2000-01-15',
            'gender' => 'Female',
            'address' => '123 Main Street, Nairobi',
            'course_id' => $first_course['id'],
            'admission_date' => date('Y-m-d'),
            'status' => 'active'
        ]);
        
        // Generate invoice for student
        generateStudentInvoice($student_id, $first_course['id']);
        
        echo "✓ Created student user (username: student1, password: stud123)<br>";
    }
    
    echo "<h3>4. Creating Sample Subjects</h3>";
    
    // Create subjects for courses
    $cs_course = fetchOne("SELECT id FROM courses WHERE course_code = 'CS101'");
    if ($cs_course) {
        $subjects = [
            ['CS101-01', 'Introduction to Programming', 3, 1],
            ['CS101-02', 'Database Management', 4, 1],
            ['CS101-03', 'Web Development', 4, 2],
            ['CS101-04', 'Software Engineering', 3, 2]
        ];
        
        foreach ($subjects as $subject) {
            if (!recordExists('subjects', 'subject_code', $subject[0])) {
                insertRecord('subjects', [
                    'course_id' => $cs_course['id'],
                    'subject_code' => $subject[0],
                    'subject_name' => $subject[1],
                    'credits' => $subject[2],
                    'semester' => $subject[3],
                    'status' => 'active'
                ]);
                echo "✓ Created subject: {$subject[1]}<br>";
            }
        }
    }
    
    echo "<h3>5. Creating Sample Hostel Rooms</h3>";
    
    // Create hostel rooms
    for ($i = 1; $i <= 20; $i++) {
        $room_number = 'R' . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        if (!recordExists('hostel_rooms', 'room_number', $room_number)) {
            insertRecord('hostel_rooms', [
                'room_number' => $room_number,
                'capacity' => ($i <= 10) ? 2 : 4, // First 10 rooms are double, rest are quad
                'current_occupancy' => 0,
                'room_type' => ($i <= 10) ? 'double' : 'dormitory',
                'monthly_fee' => ($i <= 10) ? 8000 : 6000,
                'status' => 'available'
            ]);
        }
    }
    echo "✓ Created 20 hostel rooms<br>";
    
    // Commit transaction
    commitTransaction();
    
    echo "<h3>✅ Test Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Test Login Credentials:</h4>";
    echo "<ul>";
    echo "<li><strong>Admin/Director:</strong> username: admin, password: admin123</li>";
    echo "<li><strong>Registrar:</strong> username: registrar, password: reg123</li>";
    echo "<li><strong>Accounts:</strong> username: accounts, password: acc123</li>";
    echo "<li><strong>Teacher:</strong> username: teacher1, password: teach123</li>";
    echo "<li><strong>Student:</strong> username: student1, password: stud123</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #cce5ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><a href='/college_management_system/'>Go to Home Page</a></li>";
    echo "<li><a href='/college_management_system/login.php'>Login with any of the test accounts</a></li>";
    echo "<li><a href='/college_management_system/registrar/admission.php'>Test Student Registration (login as registrar first)</a></li>";
    echo "<li><a href='/college_management_system/student/dashboard.php'>View Student Dashboard (login as student first)</a></li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    rollbackTransaction();
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Setup Failed!</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
    error_log("Test setup error: " . $e->getMessage());
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1 {
    color: #2c3e50;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
}

h3 {
    color: #34495e;
    margin-top: 30px;
}

ul, ol {
    margin: 10px 0;
    padding-left: 30px;
}

a {
    color: #3498db;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>

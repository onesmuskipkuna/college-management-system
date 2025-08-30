-- College Management System Database Schema
-- Created: 2024
-- Database: college_db
-- Compatible with MySQL 8.0+ and MariaDB 10.6+
-- Optimized for PHP 8.3+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Database creation with MySQL 8.0+ optimizations
CREATE DATABASE IF NOT EXISTS `college_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_0900_ai_ci;
USE `college_db`;

-- Create training_programs table
CREATE TABLE IF NOT EXISTS training_programs (
  id INT NOT NULL AUTO_INCREMENT,
  title VARCHAR(100),
  description TEXT,
  start_date DATE,
  end_date DATE,
  status VARCHAR(20),
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','headteacher','registrar','accounts','reception','hr','hostel','director') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` tinyint DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role_status` (`role`, `status`),
  KEY `idx_last_login` (`last_login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `courses`
-- --------------------------------------------------------

CREATE TABLE `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `description` text,
  `duration_months` int NOT NULL,
  `max_students` int DEFAULT NULL,
  `current_students` int DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_status` (`status`),
  FULLTEXT KEY `ft_course_search` (`course_name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------

CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `id_type` enum('ID','Passport') NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `address` text,
  `course_id` int NOT NULL,
  `admission_date` date NOT NULL,
  `graduation_date` date NULL,
  `status` enum('active','graduated','suspended','dropped') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `user_id` (`user_id`),
  KEY `course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_admission_date` (`admission_date`),
  FULLTEXT KEY `ft_student_search` (`first_name`, `last_name`, `student_id`),
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `teachers`
-- --------------------------------------------------------

CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `teacher_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `specialization` varchar(100),
  `hire_date` date NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_id` (`teacher_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  FULLTEXT KEY `ft_teacher_search` (`first_name`, `last_name`, `specialization`),
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `fee_structure`
-- --------------------------------------------------------

CREATE TABLE `fee_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_mandatory` boolean DEFAULT true,
  `description` text,
  `academic_year` varchar(9) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `idx_status` (`status`),
  KEY `idx_academic_year` (`academic_year`),
  CONSTRAINT `fk_fee_structure_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `student_fees`
-- --------------------------------------------------------

CREATE TABLE `student_fees` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`amount_due` - `amount_paid`) STORED,
  `due_date` date NOT NULL,
  `status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `fee_structure_id` (`fee_structure_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_balance` (`balance`),
  CONSTRAINT `fk_student_fees_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_student_fees_structure` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structure` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `student_fee_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','mpesa','bank','cheque','card') NOT NULL,
  `reference_number` varchar(50),
  `mpesa_receipt` varchar(50) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `received_by` int NOT NULL,
  `approved_by` int NULL,
  `receipt_number` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `receipt_number` (`receipt_number`),
  KEY `student_id` (`student_id`),
  KEY `student_fee_id` (`student_fee_id`),
  KEY `received_by` (`received_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_payments_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_student_fee` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_payments_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `subjects`
-- --------------------------------------------------------

CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `credits` int NOT NULL DEFAULT 1,
  `semester` int NOT NULL DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_code` (`subject_code`),
  KEY `course_id` (`course_id`),
  KEY `idx_semester` (`semester`),
  KEY `idx_status` (`status`),
  FULLTEXT KEY `ft_subject_search` (`subject_name`, `subject_code`),
  CONSTRAINT `fk_subjects_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `grades`
-- --------------------------------------------------------

CREATE TABLE `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `exam_type` enum('assignment','quiz','midterm','final','project') NOT NULL,
  `marks` decimal(5,2) NOT NULL,
  `max_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `percentage` decimal(5,2) GENERATED ALWAYS AS ((`marks` / `max_marks`) * 100) STORED,
  `grade` varchar(5),
  `exam_date` date NOT NULL,
  `approved_by` int NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_exam_date` (`exam_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_grades_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `attendance`
-- --------------------------------------------------------

CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`student_id`, `subject_id`, `attendance_date`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `idx_attendance_date` (`attendance_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `assignments`
-- --------------------------------------------------------

CREATE TABLE `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `due_date` datetime NOT NULL,
  `max_marks` decimal(5,2) NOT NULL DEFAULT 100.00,
  `file_path` varchar(255),
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  FULLTEXT KEY `ft_assignment_search` (`title`, `description`),
  CONSTRAINT `fk_assignments_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignments_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `certificates`

-- --------------------------------------------------------
-- Table structure for table `hostel_rooms`
CREATE TABLE `hostel_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `capacity` int NOT NULL DEFAULT 1,
  `current_occupancy` int DEFAULT 0,
  `room_type` enum('single','double','dormitory') NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `idx_status` (`status`),
  KEY `idx_room_type` (`room_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `hostel_allocations`
CREATE TABLE `hostel_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `room_id` int NOT NULL,
  `allocation_date` date NOT NULL,
  `checkout_date` date NULL,
  `status` enum('active','checked_out','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_allocation_date` (`allocation_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_hostel_allocations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hostel_allocations_room` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- --------------------------------------------------------

CREATE TABLE `certificates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `certificate_type` enum('completion','graduation','transcript') NOT NULL,
  `issue_date` date NOT NULL,
  `issued_by` int NOT NULL,
  `approved_by` int NULL,
  `fee_cleared` boolean DEFAULT false,
  `special_authorization` boolean DEFAULT false,
  `authorized_by` int NULL,
  `file_path` varchar(255),
  `status` enum('pending','issued','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `student_id` (`student_id`),
  KEY `issued_by` (`issued_by`),
  KEY `approved_by` (`approved_by`),
  KEY `authorized_by` (`authorized_by`),
  KEY `idx_issue_date` (`issue_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_certificates_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_certificates_issued_by` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_certificates_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_certificates_authorized_by` FOREIGN KEY (`authorized_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `hostel_rooms`
-- --------------------------------------------------------

CREATE TABLE `hostel_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `capacity` int NOT NULL DEFAULT 1,
  `current_occupancy` int DEFAULT 0,
  `room_type` enum('single','double','dormitory') NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL,
  `status` enum('available','occupied','maintenance','closed') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `idx_status` (`status`),
  KEY `idx_room_type` (`room_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Table structure for table `hostel_allocations`
-- --------------------------------------------------------

CREATE TABLE `hostel_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `room_id` int NOT NULL,
  `allocation_date` date NOT NULL,
  `checkout_date` date NULL,
  `status` enum('active','checked_out','terminated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `room_id` (`room_id`),
  KEY `idx_allocation_date` (`allocation_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_hostel_allocations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hostel_allocations_room` FOREIGN KEY (`room_id`) REFERENCES `hostel_rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Additional tables for enhanced functionality
-- --------------------------------------------------------

-- System logs table
CREATE TABLE `system_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NULL,
  `record_id` int NULL,
  `old_values` json NULL,
  `new_values` json NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_system_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Session management table
CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `payload` longtext NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Insert default data
-- --------------------------------------------------------

-- Insert default admin user
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'director');

-- Insert sample courses
INSERT INTO `courses` (`course_code`, `course_name`, `description`, `duration_months`, `max_students`) VALUES
('CS101', 'Computer Science Diploma', 'Comprehensive computer science program covering programming, databases, web development, and software engineering', 24, 50),
('BM101', 'Business Management Certificate', 'Business management fundamentals including marketing, finance, operations, and leadership', 12, 40),
('AC101', 'Accounting Certificate', 'Basic accounting principles and practices for small businesses and financial management', 18, 35),
('IT201', 'Information Technology Diploma', 'Advanced IT skills including networking, cybersecurity, system administration, and cloud computing', 30, 30),
('DM301', 'Digital Marketing Certificate', 'Modern digital marketing strategies, social media management, and online advertising', 6, 25);

-- Insert sample fee structures
INSERT INTO `fee_structure` (`course_id`, `fee_type`, `amount`, `is_mandatory`, `description`, `academic_year`) VALUES
(1, 'Tuition Fee', 50000.00, true, 'Semester tuition fee for Computer Science Diploma', '2024/2025'),
(1, 'Registration Fee', 5000.00, true, 'One-time registration fee', '2024/2025'),
(1, 'Lab Fee', 10000.00, true, 'Computer lab usage and equipment fee', '2024/2025'),
(1, 'Exam Fee', 3000.00, true, 'Examination and assessment fee', '2024/2025'),
(2, 'Tuition Fee', 30000.00, true, 'Semester tuition fee for Business Management', '2024/2025'),
(2, 'Registration Fee', 3000.00, true, 'One-time registration fee', '2024/2025'),
(2, 'Materials Fee', 2000.00, false, 'Course materials and resources', '2024/2025'),
(3, 'Tuition Fee', 40000.00, true, 'Semester tuition fee for Accounting Certificate', '2024/2025'),
(3, 'Registration Fee', 4000.00, true, 'One-time registration fee', '2024/2025'),
(3, 'Software Fee', 5000.00, true, 'Accounting software license fee', '2024/2025'),
(4, 'Tuition Fee', 60000.00, true, 'Semester tuition fee for IT Diploma', '2024/2025'),
(4, 'Registration Fee', 6000.00, true, 'One-time registration fee', '2024/2025'),
(4, 'Lab Fee', 15000.00, true, 'IT lab and equipment usage fee', '2024/2025'),
(5, 'Tuition Fee', 25000.00, true, 'Course fee for Digital Marketing Certificate', '2024/2025'),
(5, 'Registration Fee', 2500.00, true, 'One-time registration fee', '2024/2025');

COMMIT;

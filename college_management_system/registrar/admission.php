<?php
/**
 * Student Admission/Registration Page
 * Enhanced registration system with validation and auto-invoice generation
 */

// Define access constant
define('CMS_ACCESS', true);

// Include required files
require_once __DIR__ . '/../authentication.php';

// Require registrar role
Authentication::requireRole('registrar');

// Page configuration
$page_title = 'Student Admission';
$show_page_header = true;
$page_subtitle = 'Register new students with enhanced validation and auto-invoice generation';

// Get current user
$current_user = Authentication::getCurrentUser();

// Initialize variables
$error_message = '';
$success_message = '';
$form_data = [];

// Get available courses
$courses = fetchAll("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate input data
        $form_data = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'id_type' => sanitizeInput($_POST['id_type'] ?? ''),
            'id_number' => sanitizeInput($_POST['id_number'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'date_of_birth' => sanitizeInput($_POST['date_of_birth'] ?? ''),
            'gender' => sanitizeInput($_POST['gender'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'course_id' => intval($_POST['course_id'] ?? 0),
            'admission_date' => sanitizeInput($_POST['admission_date'] ?? date('Y-m-d'))
        ];
        
        // Validation
        $validation_errors = [];
        
        // Required fields validation
        if (empty($form_data['first_name'])) {
            $validation_errors['first_name'] = 'First name is required';
        }
        
        if (empty($form_data['last_name'])) {
            $validation_errors['last_name'] = 'Last name is required';
        }
        
        if (empty($form_data['id_type'])) {
            $validation_errors['id_type'] = 'ID type is required';
        }
        
        if (empty($form_data['id_number'])) {
            $validation_errors['id_number'] = 'ID number is required';
        } else {
            // Validate ID number based on type
            if ($form_data['id_type'] === 'ID' && !validateKenyanId($form_data['id_number'])) {
                $validation_errors['id_number'] = 'Please enter a valid 8-digit Kenyan ID number';
            } elseif ($form_data['id_type'] === 'Passport' && !validatePassport($form_data['id_number'])) {
                $validation_errors['id_number'] = 'Please enter a valid passport number';
            }
            
            // Check if ID number already exists
            if (recordExists('students', 'id_number', $form_data['id_number'])) {
                $validation_errors['id_number'] = 'This ID number is already registered';
            }
        }
        
        if (empty($form_data['phone'])) {
            $validation_errors['phone'] = 'Phone number is required';
        } elseif (!isValidPhone($form_data['phone'])) {
            $validation_errors['phone'] = 'Please enter a valid phone number (e.g., +254712345678 or 0712345678)';
        }
        
        if (empty($form_data['email'])) {
            $validation_errors['email'] = 'Email address is required';
        } elseif (!isValidEmail($form_data['email'])) {
            $validation_errors['email'] = 'Please enter a valid email address';
        } elseif (recordExists('users', 'email', $form_data['email'])) {
            $validation_errors['email'] = 'This email address is already registered';
        }
        
        if (empty($form_data['date_of_birth'])) {
            $validation_errors['date_of_birth'] = 'Date of birth is required';
        } elseif (isFutureDate($form_data['date_of_birth'])) {
            $validation_errors['date_of_birth'] = 'Date of birth cannot be in the future';
        } elseif (calculateAge($form_data['date_of_birth']) < 16) {
            $validation_errors['date_of_birth'] = 'Student must be at least 16 years old';
        }
        
        if (empty($form_data['gender'])) {
            $validation_errors['gender'] = 'Gender is required';
        }
        
        if (empty($form_data['course_id'])) {
            $validation_errors['course_id'] = 'Course selection is required';
        }
        
        // If no validation errors, proceed with registration
        if (empty($validation_errors)) {
            try {
                beginTransaction();
                
                // Generate student ID
                $student_id = generateStudentId();
                
                // Generate username (first name + last 4 digits of ID/passport)
                $username = strtolower($form_data['first_name']) . substr($form_data['id_number'], -4);
                
                // Check if username exists, if so, add random number
                $original_username = $username;
                $counter = 1;
                while (recordExists('users', 'username', $username)) {
                    $username = $original_username . $counter;
                    $counter++;
                }
                
                // Generate temporary password
                $temp_password = generateRandomPassword(8);
                $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                
                // Create user account
                $user_data = [
                    'username' => $username,
                    'email' => $form_data['email'],
                    'password' => $hashed_password,
                    'role' => 'student',
                    'status' => 'active'
                ];
                
                $user_id = insertRecord('users', $user_data);
                
                // Create student record
                $student_data = [
                    'user_id' => $user_id,
                    'student_id' => $student_id,
                    'first_name' => $form_data['first_name'],
                    'last_name' => $form_data['last_name'],
                    'id_type' => $form_data['id_type'],
                    'id_number' => $form_data['id_number'],
                    'phone' => $form_data['phone'],
                    'date_of_birth' => $form_data['date_of_birth'],
                    'gender' => $form_data['gender'],
                    'address' => $form_data['address'],
                    'course_id' => $form_data['course_id'],
                    'admission_date' => $form_data['admission_date'],
                    'status' => 'active'
                ];
                
                $new_student_id = insertRecord('students', $student_data);
                
                // Generate invoice automatically
                $invoice_result = generateStudentInvoice($new_student_id, $form_data['course_id']);
                
                if (!$invoice_result['success']) {
                    throw new Exception('Failed to generate student invoice: ' . $invoice_result['message']);
                }
                
                commitTransaction();
                
                // Log activity
                logActivity($current_user['id'], 'student_admission', "Admitted new student: {$form_data['first_name']} {$form_data['last_name']} (ID: {$student_id})");
                
                // Send welcome email with credentials
                $email_subject = 'Welcome to ' . SITE_NAME . ' - Your Account Details';
                $email_message = "
                    <h2>Welcome to " . SITE_NAME . "</h2>
                    <p>Dear {$form_data['first_name']} {$form_data['last_name']},</p>
                    <p>Your student account has been successfully created. Here are your login details:</p>
                    <ul>
                        <li><strong>Student ID:</strong> {$student_id}</li>
                        <li><strong>Username:</strong> {$username}</li>
                        <li><strong>Temporary Password:</strong> {$temp_password}</li>
                    </ul>
                    <p>Please log in to the student portal and change your password immediately.</p>
                    <p>Your total fee amount is: " . formatCurrency($invoice_result['total_amount']) . "</p>
                    <p>Best regards,<br>Registrar Office</p>
                ";
                
                sendEmail($form_data['email'], $email_subject, $email_message);
                
                $success_message = "Student successfully registered! Student ID: {$student_id}. Login credentials have been sent to the student's email address. Total fee amount: " . formatCurrency($invoice_result['total_amount']);
                
                // Clear form data
                $form_data = [];
                
            } catch (Exception $e) {
                rollbackTransaction();
                error_log("Student admission error: " . $e->getMessage());
                $error_message = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

// Include header
include __DIR__ . '/../header.php';
?>

<style>
    .admission-form {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .section-title {
        color: #495057;
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .fee-preview {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .fee-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .fee-item:last-child {
        border-bottom: none;
        font-weight: 600;
        font-size: 1.1rem;
        color: #007bff;
    }
    
    .required-field::after {
        content: " *";
        color: #dc3545;
    }
    
    @media (max-width: 768px) {
        .admission-form {
            padding: 1rem;
        }
        
        .row {
            margin: 0;
        }
        
        .col-md-6 {
            padding: 0;
            margin-bottom: 1rem;
        }
    }
</style>

<div class="row">
    <div class="col-12">
        <!-- Success/Error Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Validation Errors -->
        <?php if (!empty($validation_errors)): ?>
            <div class="alert alert-danger">
                <h6>Please correct the following errors:</h6>
                <ul class="mb-0">
                    <?php foreach ($validation_errors as $field => $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Admission Form -->
        <div class="admission-form">
            <form method="POST" id="registrationForm" data-autosave novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <span class="section-icon">1</span>
                        Personal Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label required-field">First Name</label>
                                <input type="text" class="form-control <?php echo isset($validation_errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                       id="first_name" name="first_name" required
                                       value="<?php echo htmlspecialchars($form_data['first_name'] ?? ''); ?>">
                                <?php if (isset($validation_errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['first_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label required-field">Last Name</label>
                                <input type="text" class="form-control <?php echo isset($validation_errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                       id="last_name" name="last_name" required
                                       value="<?php echo htmlspecialchars($form_data['last_name'] ?? ''); ?>">
                                <?php if (isset($validation_errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['last_name']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_type" class="form-label required-field">Identification Type</label>
                                <select class="form-control <?php echo isset($validation_errors['id_type']) ? 'is-invalid' : ''; ?>" 
                                        id="id_type" name="id_type" required>
                                    <option value="">Select ID Type</option>
                                    <option value="ID" <?php echo ($form_data['id_type'] ?? '') === 'ID' ? 'selected' : ''; ?>>Kenyan National ID</option>
                                    <option value="Passport" <?php echo ($form_data['id_type'] ?? '') === 'Passport' ? 'selected' : ''; ?>>Passport</option>
                                </select>
                                <?php if (isset($validation_errors['id_type'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['id_type']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_number" class="form-label required-field">ID/Passport Number</label>
                                <input type="text" class="form-control <?php echo isset($validation_errors['id_number']) ? 'is-invalid' : ''; ?>" 
                                       id="id_number" name="id_number" required
                                       value="<?php echo htmlspecialchars($form_data['id_number'] ?? ''); ?>">
                                <small class="form-text text-muted" id="id_help">Enter 8-digit ID number or passport number</small>
                                <?php if (isset($validation_errors['id_number'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['id_number']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                                <input type="date" class="form-control <?php echo isset($validation_errors['date_of_birth']) ? 'is-invalid' : ''; ?>" 
                                       id="date_of_birth" name="date_of_birth" required
                                       max="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>">
                                <?php if (isset($validation_errors['date_of_birth'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['date_of_birth']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="gender" class="form-label required-field">Gender</label>
                                <select class="form-control <?php echo isset($validation_errors['gender']) ? 'is-invalid' : ''; ?>" 
                                        id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($form_data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($form_data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($form_data['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <?php if (isset($validation_errors['gender'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['gender']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <span class="section-icon">2</span>
                        Contact Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label required-field">Phone Number</label>
                                <input type="tel" class="form-control <?php echo isset($validation_errors['phone']) ? 'is-invalid' : ''; ?>" 
                                       id="phone" name="phone" required
                                       pattern="^(\+254|0)[17]\d{8}$"
                                       title="Enter a valid Kenyan phone number"
                                       value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                                <small class="form-text text-muted">Format: +254712345678 or 0712345678</small>
                                <?php if (isset($validation_errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['phone']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label required-field">Email Address</label>
                                <input type="email" class="form-control <?php echo isset($validation_errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" required
                                       value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                                <?php if (isset($validation_errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['email']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Academic Information Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <span class="section-icon">3</span>
                        Academic Information
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="course_id" class="form-label required-field">Course/Program</label>
                                <select class="form-control <?php echo isset($validation_errors['course_id']) ? 'is-invalid' : ''; ?>" 
                                        id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($form_data['course_id'] ?? 0) == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_name']); ?> 
                                            (<?php echo $course['duration_months']; ?> months)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($validation_errors['course_id'])): ?>
                                    <div class="invalid-feedback"><?php echo htmlspecialchars($validation_errors['course_id']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="admission_date" class="form-label">Admission Date</label>
                                <input type="date" class="form-control" id="admission_date" name="admission_date"
                                       value="<?php echo htmlspecialchars($form_data['admission_date'] ?? date('Y-m-d')); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fee Structure Preview -->
                    <div id="fee-structure-display" class="fee-preview" style="display: none;">
                        <h6>Fee Structure Preview</h6>
                        <div id="fee-items"></div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Register Student & Generate Invoice
                    </button>
                    <a href="/college_management_system/registrar/student_management.php" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const idTypeSelect = document.getElementById('id_type');
    const idNumberField = document.getElementById('id_number');
    const idHelpText = document.getElementById('id_help');
    const courseSelect = document.getElementById('course_id');
    const feeDisplay = document.getElementById('fee-structure-display');
    const phoneField = document.getElementById('phone');
    const dobField = document.getElementById('date_of_birth');
    
    // ID type change handler
    idTypeSelect.addEventListener('change', function() {
        const idType = this.value;
        
        if (idType === 'ID') {
            idNumberField.setAttribute('pattern', '\\d{8}');
            idNumberField.setAttribute('maxlength', '8');
            idNumberField.setAttribute('title', 'Enter 8-digit Kenyan ID number');
            idHelpText.textContent = 'Enter 8-digit Kenyan ID number';
        } else if (idType === 'Passport') {
            idNumberField.setAttribute('pattern', '[A-Z0-9]{6,9}');
            idNumberField.setAttribute('maxlength', '9');
            idNumberField.setAttribute('title', 'Enter valid passport number');
            idHelpText.textContent = 'Enter passport number (6-9 characters)';
        }
        
        idNumberField.value = '';
        CMS_Validation.clearFieldError(idNumberField);
    });
    
    // Phone number validation
    phoneField.addEventListener('input', function() {
        // Remove any non-digit characters except + and spaces
        let value = this.value.replace(/[^\d+\s]/g, '');
        this.value = value;
        
        // Validate phone number format
        if (value && !CMS_Validation.isValidPhone(value)) {
            CMS_Validation.showFieldError(this, 'Please enter a valid phone number (e.g., +254712345678 or 0712345678)');
        } else {
            CMS_Validation.clearFieldError(this);
        }
    });
    
    // Date of birth validation
    dobField.addEventListener('change', function() {
        if (CMS_Validation.isFutureDate(this.value)) {
            CMS_Validation.showFieldError(this, 'Date of birth cannot be in the future');
            this.value = '';
        } else {
            const age = calculateAge(this.value);
            if (age < 16) {
                CMS_Validation.showFieldError(this, 'Student must be at least 16 years old');
            } else {
                CMS_Validation.clearFieldError(this);
            }
        }
    });
    
    // Course selection change handler
    courseSelect.addEventListener('change', function() {
        const courseId = this.value;
        
        if (courseId) {
            // Fetch and display fee structure
            fetch(`/college_management_system/api/get_fee_structure.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFeeStructure(data.data);
                    } else {
                        console.error('Failed to fetch fee structure:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching fee structure:', error);
                });
        } else {
            feeDisplay.style.display = 'none';
        }
    });
    
    function displayFeeStructure(feeStructure) {
        const feeItems = document.getElementById('fee-items');
        let html = '';
        let total = 0;
        
        feeStructure.forEach(fee => {
            html += `
                <div class="fee-item">
                    <span>${fee.fee_type}</span>
                    <span>${CMS_Validation.formatCurrency(fee.amount)}</span>
                </div>
            `;
            total += parseFloat(fee.amount);
        });
        
        html += `
            <div class="fee-item">
                <span>Total Amount</span>
                <span>${CMS_Validation.formatCurrency(total)}</span>
            </div>
        `;
        
        feeItems.innerHTML = html;
        feeDisplay.style.display = 'block';
    }
    
    function calculateAge(dateOfBirth) {
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    }
    
    // Form submission validation
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        // Validate required fields
        const requiredFields = this.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                CMS_Validation.showFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        // Additional validations
        const idType = document.getElementById('id_type').value;
        const idNumber = document.getElementById('id_number').value;
        
        if (idType === 'ID' && !CMS_Validation.isValidKenyanId(idNumber)) {
            CMS_Validation.showFieldError(document.getElementById('id_number'), 'Please enter a valid 8-digit Kenyan ID number');
            isValid = false;
        } else if (idType === 'Passport' && !CMS_Validation.isValidPassport(idNumber)) {
            CMS_Validation.showFieldError(document.getElementById('id_number'), 'Please enter a valid passport number');
            isValid = false;
        }
        
        const phone = document.getElementById('phone').value;
        if (!CMS_Validation.isValidPhone(phone)) {
            CMS_Validation.showFieldError(document.getElementById('phone'), 'Please enter a valid phone number');
            isValid = false;
        }
        
        const dob = document.getElementById('date_of_birth').value;
        if (CMS_Validation.isFutureDate(dob)) {
            CMS_Validation.showFieldError(document.getElementById('date_of_birth'), 'Date of birth cannot be in the future');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            CMS_Validation.showAlert('Please correct the errors in the form', 'danger');
            
            // Scroll to first error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
    
    // Auto-format phone number
    phoneField.addEventListener('blur', function() {
        let phone = this.value.replace(/\s/g, '');
        if (phone.startsWith('0') && phone.length === 10) {
            this.value = phone.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
        } else if (phone.startsWith('+254') && phone.length === 13) {
            this.value = phone.replace(/(\+254)(\d{3})(\d{3})(\d{3})/, '$1 $2 $3 $4');
        }
    });
    
    // Real-time ID number validation
    idNumberField.addEventListener('input', function() {
        const idType = idTypeSelect.value;
        const value = this.value.toUpperCase();
        
        if (idType === 'ID') {
            // Only allow digits for Kenyan ID
            this.value = value.replace(/[^\d]/g, '');
        } else if (idType === 'Passport') {
            // Allow alphanumeric for passport
            this.value = value.replace(/[^A-Z0-9]/g, '');
        }
    });
    
    // Show loading state on form submission
    document.getElementById('registrationForm').addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Processing Registration...';
    });
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>

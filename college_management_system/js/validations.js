/**
 * College Management System - Client-side Validations
 * Handles form validations and user interactions
 */

// Global validation object
const CMS_Validation = {
    
    // Initialize all validations
    init: function() {
        this.setupFormValidations();
        this.setupDateValidations();
        this.setupPhoneValidations();
        this.setupPasswordValidations();
        this.setupFileValidations();
        this.setupCSRFProtection();
    },
    
    // Setup form validations
    setupFormValidations: function() {
        // Registration form validation
        const registrationForm = document.getElementById('registrationForm');
        if (registrationForm) {
            registrationForm.addEventListener('submit', this.validateRegistrationForm);
        }
        
        // Login form validation
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.validateLoginForm);
        }
        
        // Fee payment form validation
        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', this.validatePaymentForm);
        }
        
        // Grade entry form validation
        const gradeForm = document.getElementById('gradeForm');
        if (gradeForm) {
            gradeForm.addEventListener('submit', this.validateGradeForm);
        }
    },
    
    // Setup date validations
    setupDateValidations: function() {
        // Date of birth validation - no future dates
        const dobFields = document.querySelectorAll('input[name="date_of_birth"], input[name="dob"]');
        dobFields.forEach(field => {
            // Set max date to today
            const today = new Date().toISOString().split('T')[0];
            field.setAttribute('max', today);
            
            field.addEventListener('change', function() {
                if (CMS_Validation.isFutureDate(this.value)) {
                    CMS_Validation.showFieldError(this, 'Date of birth cannot be in the future');
                    this.value = '';
                } else {
                    CMS_Validation.clearFieldError(this);
                }
            });
        });
        
        // Due date validation
        const dueDateFields = document.querySelectorAll('input[name="due_date"]');
        dueDateFields.forEach(field => {
            field.addEventListener('change', function() {
                if (!CMS_Validation.isFutureDate(this.value) && this.value !== '') {
                    CMS_Validation.showFieldError(this, 'Due date must be in the future');
                } else {
                    CMS_Validation.clearFieldError(this);
                }
            });
        });
    },
    
    // Setup phone number validations
    setupPhoneValidations: function() {
        const phoneFields = document.querySelectorAll('input[name="phone"], input[type="tel"]');
        phoneFields.forEach(field => {
            // Only allow digits, +, and spaces
            field.addEventListener('input', function() {
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
            
            // Set pattern for HTML5 validation
            field.setAttribute('pattern', '^(\\+254|0)[17]\\d{8}$');
            field.setAttribute('title', 'Enter a valid Kenyan phone number');
        });
    },
    
    // Setup password validations
    setupPasswordValidations: function() {
        const passwordFields = document.querySelectorAll('input[type="password"]');
        passwordFields.forEach(field => {
            if (field.name === 'password' || field.name === 'new_password') {
                field.addEventListener('input', function() {
                    const strength = CMS_Validation.checkPasswordStrength(this.value);
                    CMS_Validation.showPasswordStrength(this, strength);
                });
            }
        });
        
        // Confirm password validation
        const confirmPasswordFields = document.querySelectorAll('input[name="confirm_password"]');
        confirmPasswordFields.forEach(field => {
            field.addEventListener('input', function() {
                const passwordField = document.querySelector('input[name="password"], input[name="new_password"]');
                if (passwordField && this.value !== passwordField.value) {
                    CMS_Validation.showFieldError(this, 'Passwords do not match');
                } else {
                    CMS_Validation.clearFieldError(this);
                }
            });
        });
    },
    
    // Setup file upload validations
    setupFileValidations: function() {
        const fileFields = document.querySelectorAll('input[type="file"]');
        fileFields.forEach(field => {
            field.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    // Check file size (5MB limit)
                    if (file.size > 5 * 1024 * 1024) {
                        CMS_Validation.showFieldError(this, 'File size must be less than 5MB');
                        this.value = '';
                        return;
                    }
                    
                    // Check file type
                    const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    if (!allowedTypes.includes(fileExtension)) {
                        CMS_Validation.showFieldError(this, 'File type not allowed. Allowed types: ' + allowedTypes.join(', '));
                        this.value = '';
                        return;
                    }
                    
                    CMS_Validation.clearFieldError(this);
                }
            });
        });
    },
    
    // Setup CSRF protection
    setupCSRFProtection: function() {
        // Add CSRF token to all forms
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = csrfToken.getAttribute('content');
                    form.appendChild(input);
                }
            }
        });
    },
    
    // Form validation functions
    validateRegistrationForm: function(e) {
        const form = e.target;
        let isValid = true;
        
        // Required fields validation
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                CMS_Validation.showFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        // ID number validation
        const idType = form.querySelector('select[name="id_type"]').value;
        const idNumber = form.querySelector('input[name="id_number"]').value;
        
        if (idType === 'ID' && !CMS_Validation.isValidKenyanId(idNumber)) {
            CMS_Validation.showFieldError(form.querySelector('input[name="id_number"]'), 'Please enter a valid 8-digit Kenyan ID number');
            isValid = false;
        } else if (idType === 'Passport' && !CMS_Validation.isValidPassport(idNumber)) {
            CMS_Validation.showFieldError(form.querySelector('input[name="id_number"]'), 'Please enter a valid passport number');
            isValid = false;
        }
        
        // Phone validation
        const phone = form.querySelector('input[name="phone"]').value;
        if (!CMS_Validation.isValidPhone(phone)) {
            CMS_Validation.showFieldError(form.querySelector('input[name="phone"]'), 'Please enter a valid phone number');
            isValid = false;
        }
        
        // Date of birth validation
        const dob = form.querySelector('input[name="date_of_birth"]').value;
        if (CMS_Validation.isFutureDate(dob)) {
            CMS_Validation.showFieldError(form.querySelector('input[name="date_of_birth"]'), 'Date of birth cannot be in the future');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            CMS_Validation.showAlert('Please correct the errors in the form', 'danger');
        }
    },
    
    validateLoginForm: function(e) {
        const form = e.target;
        const username = form.querySelector('input[name="username"]').value;
        const password = form.querySelector('input[name="password"]').value;
        
        if (!username.trim()) {
            CMS_Validation.showFieldError(form.querySelector('input[name="username"]'), 'Username is required');
            e.preventDefault();
            return;
        }
        
        if (!password.trim()) {
            CMS_Validation.showFieldError(form.querySelector('input[name="password"]'), 'Password is required');
            e.preventDefault();
            return;
        }
    },
    
    validatePaymentForm: function(e) {
        const form = e.target;
        const amount = parseFloat(form.querySelector('input[name="amount"]').value);
        const paymentMethod = form.querySelector('select[name="payment_method"]').value;
        
        if (amount <= 0) {
            CMS_Validation.showFieldError(form.querySelector('input[name="amount"]'), 'Amount must be greater than 0');
            e.preventDefault();
            return;
        }
        
        if (!paymentMethod) {
            CMS_Validation.showFieldError(form.querySelector('select[name="payment_method"]'), 'Please select a payment method');
            e.preventDefault();
            return;
        }
        
        // M-Pesa specific validation
        if (paymentMethod === 'mpesa') {
            const phone = form.querySelector('input[name="mpesa_phone"]');
            if (phone && !CMS_Validation.isValidPhone(phone.value)) {
                CMS_Validation.showFieldError(phone, 'Please enter a valid M-Pesa phone number');
                e.preventDefault();
                return;
            }
        }
    },
    
    validateGradeForm: function(e) {
        const form = e.target;
        const marks = parseFloat(form.querySelector('input[name="marks"]').value);
        const maxMarks = parseFloat(form.querySelector('input[name="max_marks"]').value);
        
        if (marks < 0) {
            CMS_Validation.showFieldError(form.querySelector('input[name="marks"]'), 'Marks cannot be negative');
            e.preventDefault();
            return;
        }
        
        if (marks > maxMarks) {
            CMS_Validation.showFieldError(form.querySelector('input[name="marks"]'), 'Marks cannot exceed maximum marks');
            e.preventDefault();
            return;
        }
    },
    
    // Validation helper functions
    isValidPhone: function(phone) {
        // Kenyan phone number validation
        const phoneRegex = /^(\+254|0)[17]\d{8}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    },
    
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    isValidKenyanId: function(id) {
        return /^\d{8}$/.test(id);
    },
    
    isValidPassport: function(passport) {
        return /^[A-Z0-9]{6,9}$/i.test(passport);
    },
    
    isFutureDate: function(date) {
        return new Date(date) > new Date();
    },
    
    checkPasswordStrength: function(password) {
        let strength = 0;
        let feedback = [];
        
        if (password.length >= 8) strength++;
        else feedback.push('At least 8 characters');
        
        if (/[a-z]/.test(password)) strength++;
        else feedback.push('Lowercase letter');
        
        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('Uppercase letter');
        
        if (/\d/.test(password)) strength++;
        else feedback.push('Number');
        
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        else feedback.push('Special character');
        
        return {
            score: strength,
            feedback: feedback,
            level: strength < 2 ? 'weak' : strength < 4 ? 'medium' : 'strong'
        };
    },
    
    // UI helper functions
    showFieldError: function(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    },
    
    clearFieldError: function(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    },
    
    showPasswordStrength: function(field, strength) {
        let strengthDiv = field.parentNode.querySelector('.password-strength');
        
        if (!strengthDiv) {
            strengthDiv = document.createElement('div');
            strengthDiv.className = 'password-strength mt-2';
            field.parentNode.appendChild(strengthDiv);
        }
        
        const colors = {
            weak: '#dc3545',
            medium: '#ffc107',
            strong: '#28a745'
        };
        
        strengthDiv.innerHTML = `
            <div class="progress" style="height: 5px;">
                <div class="progress-bar" style="width: ${(strength.score / 5) * 100}%; background-color: ${colors[strength.level]}"></div>
            </div>
            <small class="text-${strength.level === 'weak' ? 'danger' : strength.level === 'medium' ? 'warning' : 'success'}">
                Password strength: ${strength.level.toUpperCase()}
                ${strength.feedback.length > 0 ? ' - Missing: ' + strength.feedback.join(', ') : ''}
            </small>
        `;
    },
    
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of the main content
        const main = document.querySelector('main') || document.body;
        main.insertBefore(alertDiv, main.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },
    
    // Utility functions
    formatCurrency: function(amount) {
        return 'KSh ' + parseFloat(amount).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('en-KE');
    },
    
    showLoading: function(element) {
        const spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinner.innerHTML = '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';
        
        element.appendChild(spinner);
    },
    
    hideLoading: function(element) {
        const spinner = element.querySelector('.spinner');
        if (spinner) {
            spinner.remove();
        }
    },
    
    // AJAX helper functions
    makeRequest: function(url, method = 'GET', data = null) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                xhr.setRequestHeader('X-CSRF-Token', csrfToken.getAttribute('content'));
            }
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        resolve(xhr.responseText);
                    }
                } else {
                    reject(new Error('Request failed with status: ' + xhr.status));
                }
            };
            
            xhr.onerror = function() {
                reject(new Error('Network error'));
            };
            
            xhr.send(data ? JSON.stringify(data) : null);
        });
    }
};

// Auto-complete and search functionality
const CMS_Search = {
    init: function() {
        this.setupSearchFields();
        this.setupAutoComplete();
    },
    
    setupSearchFields: function() {
        const searchFields = document.querySelectorAll('.search-field');
        searchFields.forEach(field => {
            let timeout;
            field.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    CMS_Search.performSearch(this);
                }, 300);
            });
        });
    },
    
    setupAutoComplete: function() {
        const autoCompleteFields = document.querySelectorAll('.autocomplete');
        autoCompleteFields.forEach(field => {
            field.addEventListener('input', function() {
                CMS_Search.showAutoComplete(this);
            });
        });
    },
    
    performSearch: function(field) {
        const query = field.value.trim();
        const target = field.getAttribute('data-target');
        
        if (query.length < 2) return;
        
        // Implement search logic based on target
        console.log('Searching for:', query, 'in', target);
    },
    
    showAutoComplete: function(field) {
        const query = field.value.trim();
        const source = field.getAttribute('data-source');
        
        if (query.length < 2) return;
        
        // Implement autocomplete logic
        console.log('Autocomplete for:', query, 'from', source);
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    CMS_Validation.init();
    CMS_Search.init();
    
    // Setup dynamic form interactions
    setupDynamicForms();
    
    // Setup data tables
    setupDataTables();
    
    // Setup modals
    setupModals();
});

// Dynamic form functions
function setupDynamicForms() {
    // Course selection changes fee display
    const courseSelects = document.querySelectorAll('select[name="course_id"]');
    courseSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateFeeStructure(this.value);
        });
    });
    
    // ID type changes validation
    const idTypeSelects = document.querySelectorAll('select[name="id_type"]');
    idTypeSelects.forEach(select => {
        select.addEventListener('change', function() {
            updateIdValidation(this.value);
        });
    });
}

function updateFeeStructure(courseId) {
    if (!courseId) return;
    
    CMS_Validation.makeRequest(`/college_management_system/api/get_fee_structure.php?course_id=${courseId}`)
        .then(response => {
            if (response.success) {
                displayFeeStructure(response.data);
            }
        })
        .catch(error => {
            console.error('Error fetching fee structure:', error);
        });
}

function displayFeeStructure(feeStructure) {
    const container = document.getElementById('fee-structure-display');
    if (!container) return;
    
    let html = '<h5>Fee Structure</h5><ul class="list-group">';
    let total = 0;
    
    feeStructure.forEach(fee => {
        html += `<li class="list-group-item d-flex justify-content-between">
            <span>${fee.fee_type}</span>
            <span>${CMS_Validation.formatCurrency(fee.amount)}</span>
        </li>`;
        total += parseFloat(fee.amount);
    });
    
    html += `<li class="list-group-item d-flex justify-content-between font-weight-bold">
        <span>Total</span>
        <span>${CMS_Validation.formatCurrency(total)}</span>
    </li></ul>`;
    
    container.innerHTML = html;
}

function updateIdValidation(idType) {
    const idField = document.querySelector('input[name="id_number"]');
    if (!idField) return;
    
    if (idType === 'ID') {
        idField.setAttribute('pattern', '\\d{8}');
        idField.setAttribute('title', 'Enter 8-digit Kenyan ID number');
        idField.setAttribute('maxlength', '8');
    } else if (idType === 'Passport') {
        idField.setAttribute('pattern', '[A-Z0-9]{6,9}');
        idField.setAttribute('title', 'Enter valid passport number');
        idField.setAttribute('maxlength', '9');
    }
}

function setupDataTables() {
    // Add sorting and filtering to tables
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        // Add basic sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(table, this.getAttribute('data-sort'));
            });
        });
    });
}

function sortTable(table, column) {
    // Basic table sorting implementation
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.cells[column].textContent.trim();
        const bVal = b.cells[column].textContent.trim();
        
        if (!isNaN(aVal) && !isNaN(bVal)) {
            return parseFloat(aVal) - parseFloat(bVal);
        }
        
        return aVal.localeCompare(bVal);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

function setupModals() {
    // Setup modal functionality
    const modalTriggers = document.querySelectorAll('[data-modal]');
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    // Close modal functionality
    const closeButtons = document.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
}

function showModal(modal) {
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Export for global use
window.CMS_Validation = CMS_Validation;
window.CMS_Search = CMS_Search;

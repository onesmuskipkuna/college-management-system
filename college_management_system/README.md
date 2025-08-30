# College Management System

A comprehensive educational institution management solution built with PHP 8.3+, featuring modern UI design and advanced functionality for managing students, teachers, fees, academics, and more.

## 🚀 Features

### Core Functionality
- **Student Management**: Complete lifecycle from admission to graduation
- **Enhanced Registration**: Phone validation (digits only), ID/Passport dropdown, DOB validation
- **Auto-Invoice Generation**: Automatic fee invoicing based on course selection
- **Payment History & Balance Tracking**: Real-time fee management with M-Pesa integration
- **Academic Management**: Grading, transcripts, certificates with approval workflows
- **Multi-Role Access**: Student, Teacher, Registrar, Accounts, HR, Hostel, Director roles

### Enhanced Features
- **Modern UI**: Clean, responsive design with Tailwind-inspired styling
- **Security**: Role-based access control, CSRF protection, secure authentication
- **Validation**: Client-side and server-side form validation
- **Mobile Responsive**: Works perfectly on all devices
- **Real-time Updates**: Dynamic fee structure display and form interactions
- **Advanced Database**: MySQL 8.0+ features including JSON columns, generated columns, and full-text search

## 📋 Requirements

- **PHP 8.3 or higher** (Required for optimal performance and security)
- **MySQL 8.0+ or MariaDB 10.6+** (Required for advanced features)
- Web server (Apache/Nginx) or PHP built-in server
- Required PHP extensions:
  - PDO
  - PDO MySQL
  - cURL
  - JSON
  - mbstring
  - OpenSSL
  - fileinfo

### Why PHP 8.3+?
- Enhanced type declarations and performance
- Improved security features
- Better error handling and debugging
- Latest language features and optimizations
- Long-term support and security updates

### Why MySQL 8.0+?
- Advanced indexing and query optimization
- JSON data type support for flexible data storage
- Generated columns for computed values
- Full-text search capabilities
- Enhanced security and performance

## 🛠️ Installation

### 1. System Requirements Check
Before installation, ensure your system meets the requirements:

```bash
# Check PHP version
php --version
# Should show PHP 8.3.x or higher

# Check MySQL version
mysql --version
# Should show MySQL 8.0.x or MariaDB 10.6.x or higher
```

### 2. Clone/Download the Project
```bash
# If using git
git clone <repository-url>
cd college_management_system

# Or download and extract the ZIP file
```

### 3. Database Setup
```bash
# Create a MySQL database
mysql -u root -p
CREATE DATABASE college_db CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
exit

# Import the database schema (optimized for MySQL 8.0+)
mysql -u root -p college_db < database/college_db.sql
```

### 4. Configuration
Edit `config.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
```

### 5. Set Permissions
```bash
# Make sure directories are writable
chmod 755 uploads/
chmod 755 temp/
chmod 755 logs/
```

### 6. Start the Server

#### Option A: PHP Built-in Server (Development)
```bash
php start_server.php
```
The script will automatically check for PHP 8.3+ and MySQL compatibility.
Then visit: http://127.0.0.1:8000

#### Option B: Apache/Nginx
Configure your web server to point to the project directory.

### 7. Initialize Test Data
Visit: http://your-domain/test_setup.php

This will create sample courses, users, and fee structures for testing.

## 🔐 Default Login Credentials

After running the test setup, you can use these credentials:

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| Director | admin | admin123 | Full system access |
| Registrar | registrar | reg123 | Student admissions & management |
| Accounts | accounts | acc123 | Fee management & financial reports |
| Teacher | teacher1 | teach123 | Grade entry & class management |
| Student | student1 | stud123 | Student portal access |

## 📚 Usage Guide

### Student Registration Process
1. Login as **Registrar** (registrar/reg123)
2. Go to **Admissions** → **Student Registration**
3. Fill the enhanced registration form:
   - **ID Type**: Dropdown selection (ID/Passport)
   - **Phone**: Accepts digits only with format validation
   - **Date of Birth**: Prevents future dates
   - **Course Selection**: Auto-displays fee structure
4. Submit to auto-generate student account and invoice

### Student Dashboard Features
1. Login as **Student** (student1/stud123)
2. View comprehensive dashboard with:
   - **Fee Balance**: Real-time outstanding amounts
   - **Payment History**: Complete transaction records
   - **Academic Progress**: GPA and course completion
   - **Quick Actions**: Access to all student services

### Fee Management
1. Login as **Accounts** (accounts/acc123)
2. Features include:
   - **Fee Collection**: M-Pesa, Bank, Cash integration
   - **Receipt Generation**: System and duplicate receipts
   - **Financial Reports**: P&L, Income/Expenditure
   - **Multi-level Authorization**: Director approval workflows

## 🏗️ System Architecture

### File Structure
```
college_management_system/
├── config.php              # System configuration
├── db.php                  # Database connection & helpers
├── authentication.php      # User authentication & sessions
├── header.php             # Common header template
├── footer.php             # Common footer template
├── index.php              # Landing page
├── login.php              # Universal login system
├── css/styles.css         # Modern responsive styling
├── js/validations.js      # Client-side validations
├── includes/functions.php # Common utility functions
├── api/                   # API endpoints
├── student/               # Student module
├── teacher/               # Teacher module
├── registrar/             # Registrar module
├── accounts/              # Accounts module
├── hr/                    # HR module
├── hostel/                # Hostel management
├── uploads/               # File storage
└── database/              # Database schema
```

### Database Schema (MySQL 8.0+ Optimized)
- **users**: System users with enhanced security features
- **students**: Student profiles with full-text search
- **teachers**: Teacher profiles and specializations
- **courses**: Academic programs with enrollment limits
- **fee_structure**: Course-specific fee configurations with academic year tracking
- **student_fees**: Individual student fee records with generated balance columns
- **payments**: Payment transactions with M-Pesa integration
- **grades**: Academic performance with auto-calculated percentages
- **attendance**: Class and exam attendance tracking
- **certificates**: Academic certificates with approval workflows
- **system_logs**: Comprehensive audit trail with JSON data storage
- **user_sessions**: Enhanced session management

## 🔧 Advanced Configuration

### PHP 8.3+ Specific Features
The system leverages PHP 8.3+ features including:
- Enhanced type declarations
- Improved performance optimizations
- Better error handling
- Modern syntax and language features

### MySQL 8.0+ Features Used
- **utf8mb4_0900_ai_ci** collation for better Unicode support
- **Generated columns** for automatic calculations
- **JSON data types** for flexible data storage
- **Full-text indexes** for advanced search capabilities
- **Enhanced indexing** for better query performance

### M-Pesa Integration
Update M-Pesa credentials in `config.php`:
```php
define('MPESA_CONSUMER_KEY', 'your_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_consumer_secret');
define('MPESA_SHORTCODE', 'your_shortcode');
define('MPESA_PASSKEY', 'your_passkey');
```

### Email Configuration
Configure SMTP settings for email notifications:
```php
define('SMTP_HOST', 'your_smtp_host');
define('SMTP_USERNAME', 'your_email');
define('SMTP_PASSWORD', 'your_password');
```

### Security Settings
```php
define('SESSION_TIMEOUT', 3600);        # 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);        # Login attempt limit
define('LOCKOUT_DURATION', 900);        # 15 minutes lockout
```

## 🧪 Testing

### System Requirements Testing
The `start_server.php` script automatically checks:
- PHP version compatibility (8.3+)
- Required PHP extensions
- MySQL version compatibility
- Database connectivity

### Manual Testing
1. **Registration Flow**: Test student admission with validations
2. **Authentication**: Verify role-based access control
3. **Fee Management**: Test invoice generation and payments
4. **Academic Features**: Grade entry and progress tracking

### API Testing
```bash
# Test fee structure API
curl -X GET "http://localhost:8000/api/get_fee_structure.php?course_id=1"

# Test with authentication
curl -X POST "http://localhost:8000/api/endpoint.php" \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

## 🚀 Deployment

### Production Checklist
- [ ] Verify PHP 8.3+ is installed
- [ ] Confirm MySQL 8.0+ is running
- [ ] Update database credentials
- [ ] Set `DEVELOPMENT_MODE` to `false`
- [ ] Configure HTTPS
- [ ] Set up proper file permissions
- [ ] Configure backup system
- [ ] Set up monitoring and logging
- [ ] Enable PHP OPcache
- [ ] Configure MySQL query cache

### Performance Optimization
- **PHP 8.3+ JIT Compiler**: Automatically enabled for better performance
- **MySQL 8.0+ Query Optimizer**: Enhanced query execution plans
- **Generated Columns**: Automatic calculation of derived values
- **Full-text Indexes**: Fast search capabilities
- **Optimized Indexes**: Strategic indexing for common queries

## 🔄 Migration from Older Versions

### From PHP 7.x to 8.3+
1. Update PHP to version 8.3+
2. Test all functionality
3. Update deprecated functions if any
4. Enable JIT compiler for performance

### From MySQL 5.7 to 8.0+
1. Backup existing database
2. Upgrade MySQL to 8.0+
3. Run the new schema migration
4. Update collation to utf8mb4_0900_ai_ci
5. Test all database operations

## 🤝 Contributing

1. Fork the repository
2. Ensure PHP 8.3+ and MySQL 8.0+ compatibility
3. Create a feature branch
4. Make your changes
5. Test thoroughly
6. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Email: admin@college.edu
- Documentation: Check the `/docs` folder
- Issues: Report bugs via the issue tracker

## 🔄 Updates & Roadmap

### Completed Features ✅
- Core system infrastructure with PHP 8.3+ and MySQL 8.0+
- Enhanced student registration with validations
- Auto-invoice generation system
- Student dashboard with payment history
- Fee structure API endpoint
- Modern responsive UI design
- Advanced database schema with generated columns
- Full-text search capabilities

### Upcoming Features 🚧
- Academic grading system with course-specific units
- Teacher attendance management
- Assignment filtering by class
- Certificate auto-generation workflow
- Enhanced fee management with M-Pesa integration
- Advanced reporting and analytics
- Real-time notifications system
- Mobile app API endpoints

---

**Built with ❤️ for educational institutions using modern PHP 8.3+ and MySQL 8.0+ technologies**

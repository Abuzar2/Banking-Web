🏦 Banking Management System
A comprehensive web-based banking management system built with PHP, MySQL, and Bootstrap. This system provides secure banking operations for customers, staff, and administrators with role-based access control.

🌟 Features
👥 Customer Portal
Account Management - View balances, transaction history

Fund Transfers - Secure internal and external transfers

Profile Management - Update personal information

Transaction History - Complete financial records

👨‍💼 Staff Portal
Customer Management - Add, view, and manage customers

Account Operations - Open new accounts, process transactions

Loan Management - Process loan applications

Basic Reporting - Transaction summaries

🛡️ Admin Portal
Full System Oversight - Complete administrative control

Advanced Reporting - Branch performance, staff analytics

User Management - Customer and staff management

Audit Logs - Comprehensive activity tracking

🚀 Quick Start
Prerequisites
XAMPP/WAMP/LAMP Stack

PHP 7.4+

MySQL 5.7+

Web Browser

Installation
Clone the Repository

bash
git clone https://github.com/Abuzar2/Banking-Web.git
cd Banking-Web
Database Setup

Import banking_sys.sql to your MySQL database

Update database credentials in config files

Configure Database Connection

php
// Update in respective PHP files
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;
Access the Application

Customers: http://localhost/Banking-Web/customer/

Staff: http://localhost/Banking-Web/staff/

Admin: http://localhost/Banking-Web/admin/

📁 Project Structure
text
Banking-Web/
├── admin/                 # Administrator Portal
│   ├── admin_customer_details.php
│   ├── admin_loans.php
│   ├── staff_reports.php
│   └── ...
├── customer/              # Customer Portal  
│   ├── customer_dashboard.php
│   ├── customer_transfer.php
│   ├── transaction_history.php
│   └── ...
├── staff/                 # Staff Portal
│   ├── staff_dashboard.php
│   └── ...
├── authentication/        # Login System
│   ├── admin_login.php
│   ├── customer_login.php
│   └── staff_login.php
└── assets/               # CSS, JS, Images
    ├── css/
    ├── js/
    └── images/
🔐 Default Login Credentials
Administrator
Username: admin

Password: admin123

Staff
Username: Varies by branch

Password: Check database

Customers
Self-registration with admin approval

🗄️ Database Schema
Key Tables:

customer - Customer personal information

account - Bank account details

transaction - Financial transactions

loan - Loan records

staff - Staff information

branch - Branch details

audit_log - Security audit trail

🛡️ Security Features
Password Hashing - bcrypt password encryption

SQL Injection Protection - Prepared statements

XSS Prevention - Input sanitization

Session Management - Secure session handling

Role-Based Access Control - Permission levels

Audit Logging - Complete activity tracking

💻 Technology Stack
Backend: PHP 7.4+

Frontend: HTML5, CSS3, JavaScript, Bootstrap 5

Database: MySQL

Server: Apache

Security: Prepared Statements, Password Hashing

🔧 Configuration
Database Configuration
Update database settings in individual PHP files:

php
$servername = "localhost";
$username = "root";   
$password = ""; 
$dbname = "banking_sys";
$port = 3307;
Session Configuration
Session timeout: 30 minutes
Automatic logout on inactivity

📊 Features Overview
Customer Features
✅ Account balance checking

✅ Transaction history

✅ Fund transfers

✅ Profile management

✅ Secure authentication

Staff Features
✅ Customer management

✅ Account operations

✅ Loan processing

✅ Basic reporting

Admin Features
✅ System oversight

✅ Advanced analytics

✅ User management

✅ Audit controls

🐛 Troubleshooting
Common Issues
Database Connection Error

Verify MySQL service is running

Check database credentials

Ensure database exists

Session Issues

Clear browser cache

Check PHP session configuration

File Permission Errors

Ensure proper read/write permissions

Check file paths

Support
For issues and questions:

Check the troubleshooting guide

Review database configuration

Verify file permissions

🤝 Contributing
Fork the repository

Create a feature branch

Commit your changes

Push to the branch

Create a Pull Request

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

👨‍💻 Developer
Abuzar

GitHub: @Abuzar2

Project: Banking Management System

Note: This is a educational project for banking system management. Always follow security best practices in production environments.

<div align="center">
⭐ Don't forget to star this repository if you find it helpful!

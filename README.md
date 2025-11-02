🏦 My Bank Management System

Project Overview

The Nexus Bank Management System is a comprehensive, role-based web application designed to handle essential daily banking operations, staff management, and financial reporting. It provides specialized dashboards for different staff roles (Teller, Manager, Loan Officer) and a secure portal for customers.

The system is built on a traditional LAMP/XAMPP stack, leveraging PHP for business logic and MySQL for data persistence.

✨ Key Features

Multi-Role Access

The system supports distinct, authenticated user roles, each with tailored access permissions:

Administrator: Full system control (logins via admin_login.php).

Staff/Teller: Handles daily customer transactions and onboarding (logins via staff_login.php).

Manager: Oversees operations and compliance.

Customer: Manages personal accounts (logins via customer_login.php).

Manager Tools (Completed)

System Audit Log (staff_audit_log.php): Tracks and filters all critical actions taken by staff members for compliance and security review.

Branch & Staff Reports (staff_branch_reports.php): Provides aggregated financial summaries, including branch assets, outstanding loans, and staff transaction performance over a specified period.

Core Operations (Planned/Under Development)

Customer Account Management (Open, Close, Update).

Deposit and Withdrawal processing.

Loan Application Review and Approval (for Loan Officers).

Real-time Customer Dashboard.

💻 Technology Stack

Backend: PHP (Native / Procedural with MySQLi)

Database: MySQL

Frontend: HTML5, CSS3, Tailwind CSS (for modern, responsive UI)

Dependencies: XAMPP / WAMP / LAMP environment

🚀 Setup and Installation

Follow these steps to get the project running locally.

1. Prerequisites

You must have a local web server environment installed (e.g., XAMPP, WAMP, or MAMP) with PHP and MySQL running.

2. Clone the Repository

git clone [https://github.com/YourUsername/banking-system.git](https://github.com/YourUsername/banking-system.git)
cd banking-system


3. Database Configuration

Create Database: Access your MySQL server (via phpMyAdmin, MySQL Workbench, etc.) and create a new database named banking_sys.

Update Connection: Ensure the database connection details in all PHP files (e.g., staff_audit_log.php, staff_branch_reports.php) are correct for your local setup:

$servername = "localhost";
$username = "root";   
$password = ""; // Change this if your root user has a password
$dbname = "banking_sys";
$port = 3307; // Change this if your MySQL port is different


Schema Setup (Critical)
For the Manager Reports to function correctly, your transaction table must include the following columns. If your existing table is missing them, run these SQL commands:

-- 1. Add the transaction_id column (Primary Key)
ALTER TABLE transaction 
ADD COLUMN transaction_id INT AUTO_INCREMENT PRIMARY KEY FIRST;

-- 2. Add the staff_id column (Foreign Key for performance tracking)
ALTER TABLE transaction 
ADD COLUMN staff_id INT;

-- Optional: Add the foreign key constraint for data integrity
-- ALTER TABLE transaction ADD CONSTRAINT fk_transaction_staff 
-- FOREIGN KEY (staff_id) REFERENCES staff(staff_id);


You will need to create and populate other essential tables (estaff, customer, account, branch, loan, audit_log) as well.

4. Access the Application

Once the files are placed in your web server's root directory (htdocs or www folder):

Start your Apache and MySQL services.

Open your web browser and navigate to the entry point:

http://localhost/banking-system/access_portal.html


🔐 Default Access Credentials (For Testing)

Note: Replace these with your own seeded test data.

Role

Access File

Example ID

Example Password

Manager

staff_login.php

1

password123

Teller

staff_login.php

4

password123

Customer

customer_login.php

qaari

qaari12

🤝 Contribution

Feel free to open issues or submit pull requests to improve the system's security, features, and user interface!

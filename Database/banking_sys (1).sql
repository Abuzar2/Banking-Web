-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 02, 2025 at 06:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `banking_sys`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_number` bigint(20) NOT NULL,
  `account_type` enum('Savings','Checking') NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_opened` date NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_number`, `account_type`, `balance`, `date_opened`, `branch_id`) VALUES
(1000000001, 'Checking', 1500.50, '2020-01-10', 1),
(1000000002, 'Savings', 8378.40, '2019-05-20', 1),
(2000000001, 'Checking', 500.00, '2021-03-01', 2),
(3000000001, 'Savings', 12000.00, '2018-08-15', 3),
(7160289195, 'Savings', 9999000.00, '2025-11-01', 1),
(8408104771, 'Checking', 100000.00, '2025-11-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `user_role` varchar(50) NOT NULL,
  `user_id` varchar(100) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `details` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `timestamp`, `user_role`, `user_id`, `action_type`, `details`, `ip_address`) VALUES
(1, '2025-10-30 14:00:00', 'Admin', 'admin_1', 'LOGIN_SUCCESS', 'Admin User logged in to dashboard.', NULL),
(2, '2025-10-30 14:05:30', 'Admin', 'admin_1', 'BRANCH_ADD', 'Added new branch: Northwest Branch, Assets $500,000.', NULL),
(3, '2025-10-30 14:15:45', 'Staff', 'staff_3', 'CUST_UPDATE', 'Updated email for Customer ID 1005.', NULL),
(4, '2025-10-31 09:00:15', 'System', 'SYSTEM', 'DAILY_INTEREST_RUN', 'Applied daily interest to all savings accounts.', NULL),
(5, '2025-11-01 00:10:02', 'Manager', '1', 'STAFF_LOGIN', 'Staff user 1 (Manager) logged in successfully.', '::1'),
(6, '2025-11-01 00:11:59', 'Manager', '1', 'STAFF_LOGIN', 'Staff user 1 (Manager) logged in successfully.', '::1'),
(7, '2025-11-01 00:16:35', 'Teller', '4', 'STAFF_LOGIN', 'Staff user 4 (Teller) logged in successfully.', '::1'),
(8, '2025-11-01 00:19:13', 'Teller', '4', 'ACCOUNT_OPEN', 'Opened new Savings account (No: 7160289195) for Customer ID 1 with initial deposit of 10000000 at Branch 1.', '::1'),
(9, '2025-11-01 00:20:11', 'Manager', '1', 'STAFF_LOGIN', 'Staff user 1 (Manager) logged in successfully.', '::1'),
(10, '2025-11-01 12:32:24', 'Manager', '1', 'STAFF_LOGIN', 'Staff user 1 (Manager) logged in successfully.', '::1'),
(11, '2025-11-01 12:33:11', 'Teller', '4', 'STAFF_LOGIN', 'Staff user 4 (Teller) logged in successfully.', '::1'),
(12, '2025-11-01 12:33:44', 'Teller', '4', 'ACCOUNT_OPEN', 'Opened new Checking account (No: 8408104771) for Customer ID 1 with initial deposit of 100000 at Branch 1.', '::1'),
(13, '2025-11-01 13:50:12', 'Manager', '1', 'STAFF_LOGIN', 'Staff user 1 (Manager) logged in successfully.', '::1'),
(14, '2025-11-01 13:50:45', 'Teller', '4', 'STAFF_LOGIN', 'Staff user 4 (Teller) logged in successfully.', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `branch_city` varchar(50) NOT NULL,
  `assets` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`branch_id`, `branch_name`, `branch_city`, `assets`) VALUES
(1, 'Bank', 'Karachi', 50000000.00),
(2, 'Market Branch', 'Lahore', 35000000.00),
(3, 'Main Branch', 'Islamabad', 20000000.00);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `cust_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `ssn` char(9) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `city` varchar(50) NOT NULL,
  `mobile_no` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`cust_id`, `first_name`, `last_name`, `ssn`, `address`, `phone_number`, `date_of_birth`, `city`, `mobile_no`) VALUES
(1, 'Alia', 'Khan', '123456789', 'qw', '555-1234', '1985-05-15', 'Karachi', '03132310163'),
(2, 'Babar', 'Jahangir', '987654321', '202 Oak Ave, NY', '555-5678', '1992-11-20', 'Lahore', '04129812'),
(3, 'Charlie', 'Brown', '111223344', '303 Pine Ln, BO', '555-9012', '1976-02-29', 'Karachi', '132546'),
(4, 'Qwsa', 'gas', NULL, NULL, NULL, '0000-00-00', 'Islamabad', '78453221'),
(5, 'Abuzar', 'Magsi', NULL, NULL, NULL, '0000-00-00', 'karachi', '03132310163'),
(7, 'Caz', 'aqw', '012893', 'vfgsadsg', '345345', '2000-04-02', 'Larkana', '6546547');

-- --------------------------------------------------------

--
-- Table structure for table `customer_account`
--

CREATE TABLE `customer_account` (
  `cust_id` int(11) NOT NULL,
  `account_number` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_account`
--

INSERT INTO `customer_account` (`cust_id`, `account_number`) VALUES
(1, 1000000001),
(1, 1000000002),
(1, 7160289195),
(1, 8408104771),
(2, 2000000001),
(3, 3000000001);

-- --------------------------------------------------------

--
-- Table structure for table `customer_loan`
--

CREATE TABLE `customer_loan` (
  `cust_id` int(11) NOT NULL,
  `loan_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_loan`
--

INSERT INTO `customer_loan` (`cust_id`, `loan_number`) VALUES
(1, 1),
(1, 3),
(2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `cust_login`
--

CREATE TABLE `cust_login` (
  `cust_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cust_login`
--

INSERT INTO `cust_login` (`cust_id`, `username`, `password_hash`) VALUES
(1, 'qaari', '$2y$10$HrzOq2jh2JY41Zb0PRnobulaus7U4ANQkA4NENSXWwY2hasreOItO');

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `loan_number` int(11) NOT NULL,
  `loan_type` enum('Home','Car','Personal') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `interest_rate` decimal(5,4) NOT NULL,
  `start_date` date NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan`
--

INSERT INTO `loan` (`loan_number`, `loan_type`, `amount`, `interest_rate`, `start_date`, `branch_id`) VALUES
(1, 'Home', 350000.00, 0.0450, '2022-06-01', 1),
(2, 'Car', 25000.00, 0.0600, '2023-01-25', 2),
(3, 'Personal', 5000.00, 0.0800, '2023-11-10', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `position` enum('Teller','Manager','Loan Officer') NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `hire_date` date NOT NULL,
  `branch_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `first_name`, `last_name`, `position`, `salary`, `hire_date`, `branch_id`) VALUES
(1, 'Abuzar', 'Magsi', 'Manager', 950000.00, '2015-08-10', 1),
(4, 'Grace', 'Hall', 'Teller', 48000.00, '2023-01-20', 2),
(5, 'Haq', 'qwe', 'Manager', 100000.00, '2025-10-31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `trans_id` bigint(20) NOT NULL,
  `account_number` bigint(20) NOT NULL,
  `trans_type` enum('Deposit','Withdrawal','Transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL CHECK (`amount` > 0),
  `trans_date` datetime NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`trans_id`, `account_number`, `trans_type`, `amount`, `trans_date`, `description`, `staff_id`) VALUES
(1, 1000000001, 'Deposit', 200.00, '2025-10-31 00:15:26', 'ATM Deposit', NULL),
(2, 1000000001, 'Withdrawal', 50.00, '2025-10-30 00:15:26', 'Grocery Store Debit', NULL),
(3, 2000000001, 'Deposit', 1000.00, '2025-10-31 00:15:26', 'Payroll Deposit', NULL),
(4, 1000000002, 'Withdrawal', 0.35, '2025-10-31 23:57:21', 'Bill Payment to Power & Light Co. - Ref: as', NULL),
(5, 7160289195, 'Withdrawal', 1000.00, '2025-11-01 12:35:04', 'Bill Payment to Internet & Cable Provider - Ref: 1009', NULL),
(6, 1000000002, 'Withdrawal', 122.00, '2025-11-01 13:52:04', 'Bill Payment to City Water Services - Ref: qw', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_number`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`cust_id`),
  ADD UNIQUE KEY `ssn` (`ssn`);

--
-- Indexes for table `customer_account`
--
ALTER TABLE `customer_account`
  ADD PRIMARY KEY (`cust_id`,`account_number`),
  ADD KEY `account_number` (`account_number`);

--
-- Indexes for table `customer_loan`
--
ALTER TABLE `customer_loan`
  ADD PRIMARY KEY (`cust_id`,`loan_number`),
  ADD KEY `loan_number` (`loan_number`);

--
-- Indexes for table `cust_login`
--
ALTER TABLE `cust_login`
  ADD PRIMARY KEY (`cust_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`loan_number`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`trans_id`),
  ADD KEY `account_number` (`account_number`),
  ADD KEY `fk_transaction_staff` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `cust_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan`
--
ALTER TABLE `loan`
  MODIFY `loan_number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `trans_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`);

--
-- Constraints for table `customer_account`
--
ALTER TABLE `customer_account`
  ADD CONSTRAINT `customer_account_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `customer` (`cust_id`),
  ADD CONSTRAINT `customer_account_ibfk_2` FOREIGN KEY (`account_number`) REFERENCES `account` (`account_number`);

--
-- Constraints for table `customer_loan`
--
ALTER TABLE `customer_loan`
  ADD CONSTRAINT `customer_loan_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `customer` (`cust_id`),
  ADD CONSTRAINT `customer_loan_ibfk_2` FOREIGN KEY (`loan_number`) REFERENCES `loan` (`loan_number`);

--
-- Constraints for table `cust_login`
--
ALTER TABLE `cust_login`
  ADD CONSTRAINT `cust_login_ibfk_1` FOREIGN KEY (`cust_id`) REFERENCES `customer` (`cust_id`);

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `fk_transaction_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`account_number`) REFERENCES `account` (`account_number`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

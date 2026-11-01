-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2026 at 06:55 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spis_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `driver_code` varchar(20) DEFAULT NULL,
  `full_name` varchar(120) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` varchar(255) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hauling`
--

CREATE TABLE `hauling` (
  `hauling_id` int(11) NOT NULL,
  `transaction_id` varchar(30) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `tractor_id` int(11) DEFAULT NULL,
  `primary_driver_id` int(11) DEFAULT NULL,
  `secondary_tractor_id` int(11) DEFAULT NULL,
  `secondary_driver_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `affiliate` enum('Member','Not Member') NOT NULL,
  `operator` varchar(100) NOT NULL,
  `secondary_operator` varchar(150) DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `hauling_count` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Missed','Ongoing','Done') DEFAULT 'Pending',
  `remarks` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `tractor_no` varchar(50) NOT NULL,
  `secondary_tractor_no` varchar(80) DEFAULT NULL,
  `rental_date` date NOT NULL,
  `actual_started_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `reschedule_count` int(11) NOT NULL DEFAULT 0,
  `last_rescheduled_from` date DEFAULT NULL,
  `last_rescheduled_at` datetime DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `actual_started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_historical_entry` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hauling_driver_helpers`
--

CREATE TABLE `hauling_driver_helpers` (
  `helper_assignment_id` int(11) NOT NULL,
  `hauling_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `driver_name` varchar(120) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `added_at` datetime NOT NULL DEFAULT current_timestamp(),
  `removed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hauling_payments`
--

CREATE TABLE `hauling_payments` (
  `payment_id` int(11) NOT NULL,
  `hauling_id` int(11) NOT NULL,
  `payment_type` enum('Downpayment','Installment','Full Payment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` varchar(255) DEFAULT NULL,
  `recorded_by_id` int(11) DEFAULT NULL,
  `recorded_by_name` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hauling_reschedules`
--

CREATE TABLE `hauling_reschedules` (
  `reschedule_id` int(11) NOT NULL,
  `hauling_id` int(11) NOT NULL,
  `previous_date` date NOT NULL,
  `previous_time` time DEFAULT NULL,
  `new_date` date NOT NULL,
  `new_time` time NOT NULL,
  `reason` varchar(255) NOT NULL,
  `rescheduled_by_id` int(11) DEFAULT NULL,
  `rescheduled_by_name` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `history_logs`
--

CREATE TABLE `history_logs` (
  `history_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(150) NOT NULL,
  `user_role` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `record_id` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `acquired_price` decimal(10,2) NOT NULL,
  `markup_percent` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` bigint(20) UNSIGNED NOT NULL,
  `email_hash` char(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `member_code` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `birth_date` date NOT NULL,
  `membership_date` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `address` text NOT NULL,
  `tin_no` varchar(20) NOT NULL,
  `rsbsa_no` varchar(30) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `social_media_name` varchar(150) DEFAULT NULL,
  `farm_field_hectares` decimal(10,4) DEFAULT NULL,
  `cbu_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cbu_completed_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `classification` varchar(50) NOT NULL,
  `archived` tinyint(1) DEFAULT 0,
  `archived_by` varchar(100) DEFAULT NULL,
  `date_registered` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_attachments`
--

CREATE TABLE `member_attachments` (
  `attachment_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_cbu_transactions`
--

CREATE TABLE `member_cbu_transactions` (
  `cbu_transaction_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `transaction_type` enum('ADD','DEDUCT') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `previous_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `new_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_demo_dates_backup`
--

CREATE TABLE `monetary_demo_dates_backup` (
  `monetary_loan_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `release_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_demo_dates_backup_20261022`
--

CREATE TABLE `monetary_demo_dates_backup_20261022` (
  `monetary_loan_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `release_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_loans`
--

CREATE TABLE `monetary_loans` (
  `monetary_loan_id` int(10) UNSIGNED NOT NULL,
  `member_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `loan_purpose` varchar(500) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `term_days` smallint(5) UNSIGNED NOT NULL,
  `loan_term_years` smallint(5) UNSIGNED DEFAULT NULL,
  `principal_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `scheduled_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `scheduled_total_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `outstanding_principal_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `net_payments_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Accepted','Rejected') NOT NULL DEFAULT 'Pending',
  `release_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL,
  `fully_paid_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_loan_attachments`
--

CREATE TABLE `monetary_loan_attachments` (
  `monetary_loan_attachment_id` int(10) UNSIGNED NOT NULL,
  `monetary_loan_id` int(10) UNSIGNED NOT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(150) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `removed_by` int(11) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `removal_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_loan_payments`
--

CREATE TABLE `monetary_loan_payments` (
  `monetary_loan_payment_id` int(10) UNSIGNED NOT NULL,
  `monetary_loan_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('Payment','Reversal') NOT NULL DEFAULT 'Payment',
  `reversed_payment_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(15,2) UNSIGNED NOT NULL,
  `calculation_from_date` date DEFAULT NULL,
  `calculation_to_date` date DEFAULT NULL,
  `elapsed_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `principal_before_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_before_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `newly_accrued_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `total_interest_due_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `interest_paid_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `principal_paid_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `principal_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `balance_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_by` int(11) DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `reversal_reason` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monetary_loan_status_history`
--

CREATE TABLE `monetary_loan_status_history` (
  `monetary_loan_status_history_id` int(10) UNSIGNED NOT NULL,
  `monetary_loan_id` int(10) UNSIGNED NOT NULL,
  `previous_status` enum('Pending','Accepted','Rejected') DEFAULT NULL,
  `new_status` enum('Pending','Accepted','Rejected') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loans`
--

CREATE TABLE `product_loans` (
  `product_loan_id` int(10) UNSIGNED NOT NULL,
  `member_id` int(11) NOT NULL,
  `application_date` date NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `term_days` smallint(5) UNSIGNED NOT NULL,
  `principal_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `scheduled_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `scheduled_total_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `outstanding_principal_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `net_payments_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Delivered','Rejected') NOT NULL DEFAULT 'Pending',
  `delivered_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL,
  `fully_paid_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loan_attachments`
--

CREATE TABLE `product_loan_attachments` (
  `product_loan_attachment_id` int(10) UNSIGNED NOT NULL,
  `product_loan_id` int(10) UNSIGNED NOT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `removed_by` int(11) DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `removal_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loan_items`
--

CREATE TABLE `product_loan_items` (
  `product_loan_item_id` int(10) UNSIGNED NOT NULL,
  `product_loan_id` int(10) UNSIGNED NOT NULL,
  `loan_product_id` int(11) DEFAULT NULL,
  `product_name` varchar(180) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(15,2) UNSIGNED NOT NULL,
  `line_total` decimal(15,2) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loan_payments`
--

CREATE TABLE `product_loan_payments` (
  `product_loan_payment_id` int(10) UNSIGNED NOT NULL,
  `product_loan_id` int(10) UNSIGNED NOT NULL,
  `transaction_type` enum('Payment','Reversal') NOT NULL DEFAULT 'Payment',
  `reversed_payment_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_amount` decimal(15,2) UNSIGNED NOT NULL,
  `calculation_from_date` date DEFAULT NULL,
  `calculation_to_date` date DEFAULT NULL,
  `elapsed_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `principal_before_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_before_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `newly_accrued_interest_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `total_interest_due_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `interest_paid_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `principal_paid_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `principal_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `unpaid_interest_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `balance_after_amount` decimal(15,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `is_reversed` tinyint(1) NOT NULL DEFAULT 0,
  `reversed_by` int(11) DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loan_products`
--

CREATE TABLE `product_loan_products` (
  `loan_product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `acquired_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `markup_percent` decimal(7,2) NOT NULL DEFAULT 0.00,
  `loan_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_loan_status_history`
--

CREATE TABLE `product_loan_status_history` (
  `product_loan_status_history_id` int(10) UNSIGNED NOT NULL,
  `product_loan_id` int(10) UNSIGNED NOT NULL,
  `previous_status` enum('Pending','Delivered','Rejected') DEFAULT NULL,
  `new_status` enum('Pending','Delivered','Rejected') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reaper`
--

CREATE TABLE `reaper` (
  `reaper_id` int(11) NOT NULL,
  `transaction_id` varchar(30) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `tractor_id` int(11) DEFAULT NULL,
  `primary_driver_id` int(11) DEFAULT NULL,
  `secondary_tractor_id` int(11) DEFAULT NULL,
  `secondary_driver_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `affiliate` enum('Member','Not Member') NOT NULL,
  `operator` varchar(150) NOT NULL,
  `secondary_operator` varchar(150) DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `rental_date` date NOT NULL,
  `is_historical_entry` tinyint(1) NOT NULL DEFAULT 0,
  `actual_started_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `tractor_no` varchar(50) NOT NULL,
  `secondary_tractor_no` varchar(80) DEFAULT NULL,
  `commodity` varchar(150) DEFAULT NULL,
  `no_of_harvest` int(11) DEFAULT NULL,
  `status` enum('Pending','Ongoing','Done') DEFAULT 'Pending',
  `remarks` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reschedule_count` int(11) NOT NULL DEFAULT 0,
  `last_rescheduled_from` date DEFAULT NULL,
  `last_rescheduled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rotor`
--

CREATE TABLE `rotor` (
  `rotor_id` int(11) NOT NULL,
  `transaction_id` varchar(30) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `tractor_id` int(11) DEFAULT NULL,
  `primary_driver_id` int(11) DEFAULT NULL,
  `secondary_tractor_id` int(11) DEFAULT NULL,
  `secondary_driver_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `affiliate` enum('Member','Not Member') NOT NULL,
  `operator` varchar(100) NOT NULL,
  `secondary_operator` varchar(150) DEFAULT NULL,
  `location` varchar(150) NOT NULL,
  `hectare` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Ongoing','Done') DEFAULT 'Pending',
  `remarks` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `tractor_no` varchar(50) NOT NULL,
  `secondary_tractor_no` varchar(80) DEFAULT NULL,
  `rental_date` date NOT NULL,
  `is_historical_entry` tinyint(1) NOT NULL DEFAULT 0,
  `actual_started_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reschedule_count` int(11) NOT NULL DEFAULT 0,
  `last_rescheduled_from` date DEFAULT NULL,
  `last_rescheduled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rotor_payments`
--

CREATE TABLE `rotor_payments` (
  `payment_id` int(11) NOT NULL,
  `rotor_id` int(11) NOT NULL,
  `payment_type` enum('Downpayment','Installment','Full Payment') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `notes` varchar(255) DEFAULT NULL,
  `recorded_by_id` int(11) DEFAULT NULL,
  `recorded_by_name` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rotor_price`
--

CREATE TABLE `rotor_price` (
  `id` int(11) NOT NULL,
  `price_per_hectare` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `transaction_id` varchar(30) NOT NULL,
  `buyer_name` varchar(255) DEFAULT NULL,
  `affiliate` varchar(30) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `sale_date` datetime NOT NULL,
  `is_historical` tinyint(1) NOT NULL DEFAULT 0,
  `recorded_at` datetime DEFAULT NULL,
  `grand_total` decimal(10,2) NOT NULL,
  `cash_received` decimal(15,2) DEFAULT NULL,
  `change_amount` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_items`
--

CREATE TABLE `sales_items` (
  `sales_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tractors`
--

CREATE TABLE `tractors` (
  `tractor_id` int(11) NOT NULL,
  `tractor_no` varchar(50) NOT NULL,
  `tractor_name` varchar(100) NOT NULL,
  `tractor_type` enum('Hauling','Rotor','Reaper') NOT NULL,
  `status` enum('Available','Reserved','In Use','Maintenance') NOT NULL DEFAULT 'Available',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` varchar(150) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `inventory_access` tinyint(1) NOT NULL DEFAULT 0,
  `sales_access` tinyint(1) NOT NULL DEFAULT 0,
  `members_access` tinyint(1) NOT NULL DEFAULT 0,
  `lending_access` tinyint(1) NOT NULL DEFAULT 0,
  `rental_access` tinyint(1) NOT NULL DEFAULT 0,
  `archives_access` tinyint(1) NOT NULL DEFAULT 0,
  `account_access` tinyint(1) NOT NULL DEFAULT 0,
  `lending_permission` enum('Limited','Full') NOT NULL DEFAULT 'Limited',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` varchar(100) DEFAULT NULL,
  `password_reset_access` tinyint(1) NOT NULL DEFAULT 0,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`, `inventory_access`, `sales_access`, `members_access`, `lending_access`, `rental_access`, `archives_access`, `account_access`, `lending_permission`, `status`, `archived`, `archived_by`, `password_reset_access`, `must_change_password`) VALUES
(1, 'Demo User', 'demo@spis.com', '$2y$10$qLdMAbj9jlhj8B9g9BXPNe9Yr.1wu2fvfnDpIvi40BIsGNhk52b6e', 'Manager', '2026-08-03 10:05:00', 1, 1, 1, 1, 1, 1, 1, 'Full', 'Active', 0, NULL, 1, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `unique_driver_code` (`driver_code`),
  ADD UNIQUE KEY `uq_drivers_member_id` (`member_id`);

--
-- Indexes for table `hauling`
--
ALTER TABLE `hauling`
  ADD PRIMARY KEY (`hauling_id`),
  ADD KEY `idx_hauling_primary_driver` (`primary_driver_id`);

--
-- Indexes for table `hauling_driver_helpers`
--
ALTER TABLE `hauling_driver_helpers`
  ADD PRIMARY KEY (`helper_assignment_id`),
  ADD KEY `idx_helper_hauling` (`hauling_id`),
  ADD KEY `idx_helper_driver` (`driver_id`);

--
-- Indexes for table `hauling_payments`
--
ALTER TABLE `hauling_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_hauling_payment` (`hauling_id`),
  ADD KEY `idx_payment_recorded_by` (`recorded_by_id`);

--
-- Indexes for table `hauling_reschedules`
--
ALTER TABLE `hauling_reschedules`
  ADD PRIMARY KEY (`reschedule_id`),
  ADD KEY `idx_reschedule_hauling` (`hauling_id`),
  ADD KEY `idx_rescheduled_by` (`rescheduled_by_id`);

--
-- Indexes for table `history_logs`
--
ALTER TABLE `history_logs`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `idx_history_module_created` (`module`,`created_at`),
  ADD KEY `idx_history_created` (`created_at`),
  ADD KEY `idx_history_user` (`user_id`),
  ADD KEY `idx_history_action` (`action`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_login_attempt_lookup` (`email_hash`,`ip_address`,`attempted_at`),
  ADD KEY `idx_login_attempt_time` (`attempted_at`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`);

--
-- Indexes for table `member_attachments`
--
ALTER TABLE `member_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member_cbu_transactions`
--
ALTER TABLE `member_cbu_transactions`
  ADD PRIMARY KEY (`cbu_transaction_id`),
  ADD KEY `idx_cbu_member` (`member_id`),
  ADD KEY `idx_cbu_date` (`transaction_date`);

--
-- Indexes for table `monetary_loans`
--
ALTER TABLE `monetary_loans`
  ADD PRIMARY KEY (`monetary_loan_id`),
  ADD KEY `idx_monetary_member` (`member_id`),
  ADD KEY `idx_monetary_status` (`status`,`is_archived`),
  ADD KEY `idx_monetary_application_date` (`application_date`),
  ADD KEY `idx_monetary_release_date` (`release_date`),
  ADD KEY `idx_monetary_due_date` (`due_date`),
  ADD KEY `fk_monetary_created_by` (`created_by`),
  ADD KEY `fk_monetary_updated_by` (`updated_by`),
  ADD KEY `fk_monetary_approved_by` (`approved_by`),
  ADD KEY `fk_monetary_rejected_by` (`rejected_by`),
  ADD KEY `fk_monetary_archived_by` (`archived_by`);

--
-- Indexes for table `monetary_loan_attachments`
--
ALTER TABLE `monetary_loan_attachments`
  ADD PRIMARY KEY (`monetary_loan_attachment_id`),
  ADD KEY `idx_monetary_attachment_loan` (`monetary_loan_id`,`is_removed`),
  ADD KEY `idx_monetary_attachment_hash` (`file_hash`),
  ADD KEY `fk_monetary_attachment_uploaded` (`uploaded_by`),
  ADD KEY `fk_monetary_attachment_removed` (`removed_by`);

--
-- Indexes for table `monetary_loan_payments`
--
ALTER TABLE `monetary_loan_payments`
  ADD PRIMARY KEY (`monetary_loan_payment_id`),
  ADD KEY `idx_monetary_payment_loan` (`monetary_loan_id`,`payment_date`),
  ADD KEY `idx_monetary_payment_type` (`transaction_type`),
  ADD KEY `idx_monetary_payment_reversed` (`reversed_payment_id`),
  ADD KEY `fk_monetary_payment_user` (`recorded_by`),
  ADD KEY `fk_monetary_payment_reversed_by` (`reversed_by`);

--
-- Indexes for table `monetary_loan_status_history`
--
ALTER TABLE `monetary_loan_status_history`
  ADD PRIMARY KEY (`monetary_loan_status_history_id`),
  ADD KEY `idx_monetary_status_history` (`monetary_loan_id`,`changed_at`),
  ADD KEY `fk_monetary_status_user` (`changed_by`);

--
-- Indexes for table `product_loans`
--
ALTER TABLE `product_loans`
  ADD PRIMARY KEY (`product_loan_id`),
  ADD KEY `idx_product_loan_member` (`member_id`),
  ADD KEY `idx_product_loan_status` (`status`),
  ADD KEY `idx_product_loan_member_status` (`member_id`,`status`,`is_archived`),
  ADD KEY `idx_product_loan_application_date` (`application_date`),
  ADD KEY `idx_product_loan_due_date` (`due_date`),
  ADD KEY `idx_product_loan_archived` (`is_archived`);

--
-- Indexes for table `product_loan_attachments`
--
ALTER TABLE `product_loan_attachments`
  ADD PRIMARY KEY (`product_loan_attachment_id`),
  ADD KEY `idx_product_loan_attachment_loan` (`product_loan_id`,`is_removed`);

--
-- Indexes for table `product_loan_items`
--
ALTER TABLE `product_loan_items`
  ADD PRIMARY KEY (`product_loan_item_id`),
  ADD KEY `idx_product_loan_item_loan` (`product_loan_id`),
  ADD KEY `idx_product_loan_items_loan_product_id` (`loan_product_id`);

--
-- Indexes for table `product_loan_payments`
--
ALTER TABLE `product_loan_payments`
  ADD PRIMARY KEY (`product_loan_payment_id`),
  ADD KEY `idx_product_loan_payment_loan` (`product_loan_id`,`payment_date`),
  ADD KEY `idx_product_loan_payment_type` (`transaction_type`),
  ADD KEY `idx_product_loan_payment_reversed` (`is_reversed`),
  ADD KEY `fk_product_loan_payment_reversal` (`reversed_payment_id`);

--
-- Indexes for table `product_loan_products`
--
ALTER TABLE `product_loan_products`
  ADD PRIMARY KEY (`loan_product_id`),
  ADD KEY `idx_loan_product_name` (`product_name`),
  ADD KEY `idx_loan_product_archived` (`archived`);

--
-- Indexes for table `product_loan_status_history`
--
ALTER TABLE `product_loan_status_history`
  ADD PRIMARY KEY (`product_loan_status_history_id`),
  ADD KEY `idx_product_loan_status_history_loan` (`product_loan_id`,`changed_at`);

--
-- Indexes for table `reaper`
--
ALTER TABLE `reaper`
  ADD PRIMARY KEY (`reaper_id`);

--
-- Indexes for table `rotor`
--
ALTER TABLE `rotor`
  ADD PRIMARY KEY (`rotor_id`);

--
-- Indexes for table `rotor_payments`
--
ALTER TABLE `rotor_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_rotor_payments_rotor` (`rotor_id`),
  ADD KEY `idx_rotor_payments_recorded_by` (`recorded_by_id`);

--
-- Indexes for table `rotor_price`
--
ALTER TABLE `rotor_price`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`);

--
-- Indexes for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`sales_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tractors`
--
ALTER TABLE `tractors`
  ADD PRIMARY KEY (`tractor_id`),
  ADD UNIQUE KEY `tractor_no` (`tractor_no`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hauling`
--
ALTER TABLE `hauling`
  MODIFY `hauling_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hauling_driver_helpers`
--
ALTER TABLE `hauling_driver_helpers`
  MODIFY `helper_assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hauling_payments`
--
ALTER TABLE `hauling_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hauling_reschedules`
--
ALTER TABLE `hauling_reschedules`
  MODIFY `reschedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `history_logs`
--
ALTER TABLE `history_logs`
  MODIFY `history_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_attachments`
--
ALTER TABLE `member_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_cbu_transactions`
--
ALTER TABLE `member_cbu_transactions`
  MODIFY `cbu_transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monetary_loans`
--
ALTER TABLE `monetary_loans`
  MODIFY `monetary_loan_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monetary_loan_attachments`
--
ALTER TABLE `monetary_loan_attachments`
  MODIFY `monetary_loan_attachment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monetary_loan_payments`
--
ALTER TABLE `monetary_loan_payments`
  MODIFY `monetary_loan_payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `monetary_loan_status_history`
--
ALTER TABLE `monetary_loan_status_history`
  MODIFY `monetary_loan_status_history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loans`
--
ALTER TABLE `product_loans`
  MODIFY `product_loan_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loan_attachments`
--
ALTER TABLE `product_loan_attachments`
  MODIFY `product_loan_attachment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loan_items`
--
ALTER TABLE `product_loan_items`
  MODIFY `product_loan_item_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loan_payments`
--
ALTER TABLE `product_loan_payments`
  MODIFY `product_loan_payment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loan_products`
--
ALTER TABLE `product_loan_products`
  MODIFY `loan_product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_loan_status_history`
--
ALTER TABLE `product_loan_status_history`
  MODIFY `product_loan_status_history_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reaper`
--
ALTER TABLE `reaper`
  MODIFY `reaper_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rotor`
--
ALTER TABLE `rotor`
  MODIFY `rotor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rotor_payments`
--
ALTER TABLE `rotor_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rotor_price`
--
ALTER TABLE `rotor_price`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `sales_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tractors`
--
ALTER TABLE `tractors`
  MODIFY `tractor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `member_attachments`
--
ALTER TABLE `member_attachments`
  ADD CONSTRAINT `member_attachments_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `monetary_loans`
--
ALTER TABLE `monetary_loans`
  ADD CONSTRAINT `fk_monetary_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_archived_by` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `monetary_loan_attachments`
--
ALTER TABLE `monetary_loan_attachments`
  ADD CONSTRAINT `fk_monetary_attachment_loan` FOREIGN KEY (`monetary_loan_id`) REFERENCES `monetary_loans` (`monetary_loan_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_attachment_removed` FOREIGN KEY (`removed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_attachment_uploaded` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `monetary_loan_payments`
--
ALTER TABLE `monetary_loan_payments`
  ADD CONSTRAINT `fk_monetary_payment_loan` FOREIGN KEY (`monetary_loan_id`) REFERENCES `monetary_loans` (`monetary_loan_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_payment_original` FOREIGN KEY (`reversed_payment_id`) REFERENCES `monetary_loan_payments` (`monetary_loan_payment_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_payment_reversed_by` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_payment_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `monetary_loan_status_history`
--
ALTER TABLE `monetary_loan_status_history`
  ADD CONSTRAINT `fk_monetary_status_loan` FOREIGN KEY (`monetary_loan_id`) REFERENCES `monetary_loans` (`monetary_loan_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monetary_status_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_loan_attachments`
--
ALTER TABLE `product_loan_attachments`
  ADD CONSTRAINT `fk_product_loan_attachment_loan` FOREIGN KEY (`product_loan_id`) REFERENCES `product_loans` (`product_loan_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_loan_items`
--
ALTER TABLE `product_loan_items`
  ADD CONSTRAINT `fk_product_loan_item_loan` FOREIGN KEY (`product_loan_id`) REFERENCES `product_loans` (`product_loan_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_loan_payments`
--
ALTER TABLE `product_loan_payments`
  ADD CONSTRAINT `fk_product_loan_payment_loan` FOREIGN KEY (`product_loan_id`) REFERENCES `product_loans` (`product_loan_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_loan_payment_reversal` FOREIGN KEY (`reversed_payment_id`) REFERENCES `product_loan_payments` (`product_loan_payment_id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_loan_status_history`
--
ALTER TABLE `product_loan_status_history`
  ADD CONSTRAINT `fk_product_loan_status_history_loan` FOREIGN KEY (`product_loan_id`) REFERENCES `product_loans` (`product_loan_id`) ON UPDATE CASCADE;

--
-- Constraints for table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

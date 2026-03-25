-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table platinum.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.pvt_freelancer_categories
CREATE TABLE IF NOT EXISTS `pvt_freelancer_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pvt_freelancer_categories_user_id_category_id_unique` (`user_id`,`category_id`),
  KEY `pvt_freelancer_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `pvt_freelancer_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pvt_freelancer_categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.pvt_studio_categories
CREATE TABLE IF NOT EXISTS `pvt_studio_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pvt_studio_categories_studio_id_category_id_unique` (`studio_id`,`category_id`),
  KEY `pvt_studio_categories_user_id_foreign` (`user_id`),
  KEY `pvt_studio_categories_category_id_foreign` (`category_id`),
  CONSTRAINT `pvt_studio_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pvt_studio_categories_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pvt_studio_categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_bookings
CREATE TABLE IF NOT EXISTS `tbl_bookings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `booking_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `location_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `venue_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barangay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Cavite',
  `multiple_locations` json DEFAULT NULL,
  `special_requests` text COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(10,2) NOT NULL,
  `down_payment` decimal(10,2) NOT NULL,
  `remaining_balance` decimal(10,2) NOT NULL,
  `deposit_policy` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '30%',
  `payment_type` enum('downpayment','full_payment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'downpayment',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_bookings_booking_reference_unique` (`booking_reference`),
  KEY `tbl_bookings_client_id_foreign` (`client_id`),
  KEY `tbl_bookings_category_id_foreign` (`category_id`),
  CONSTRAINT `tbl_bookings_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`),
  CONSTRAINT `tbl_bookings_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_booking_assigned_photographers
CREATE TABLE IF NOT EXISTS `tbl_booking_assigned_photographers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `photographer_id` bigint unsigned NOT NULL,
  `assigned_by` bigint unsigned NOT NULL,
  `status` enum('assigned','confirmed','on_site','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `assignment_notes` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `on_site_at` timestamp NULL DEFAULT NULL,
  `client_confirmed_at` timestamp NULL DEFAULT NULL,
  `client_confirmation_notes` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_booking_assigned_photographers_studio_id_foreign` (`studio_id`),
  KEY `tbl_booking_assigned_photographers_booking_id_studio_id_index` (`booking_id`,`studio_id`),
  KEY `tbl_booking_assigned_photographers_photographer_id_status_index` (`photographer_id`,`status`),
  KEY `tbl_booking_assigned_photographers_assigned_by_index` (`assigned_by`),
  CONSTRAINT `tbl_booking_assigned_photographers_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_booking_assigned_photographers_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_booking_assigned_photographers_photographer_id_foreign` FOREIGN KEY (`photographer_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_booking_assigned_photographers_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='on_site_at: When photographer marked on-site, client_confirmed_at: When client verified presence';

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_booking_packages
CREATE TABLE IF NOT EXISTS `tbl_booking_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `package_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_price` decimal(10,2) NOT NULL,
  `package_inclusions` text COLLATE utf8mb4_unicode_ci,
  `duration` int DEFAULT NULL,
  `maximum_edited_photos` int DEFAULT NULL,
  `coverage_scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_booking_packages_booking_id_foreign` (`booking_id`),
  CONSTRAINT `tbl_booking_packages_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_categories
CREATE TABLE IF NOT EXISTS `tbl_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_categories_category_name_unique` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_chatbot_configs
CREATE TABLE IF NOT EXISTS `tbl_chatbot_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `config_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `welcome_message` text COLLATE utf8mb4_unicode_ci,
  `fallback_message` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `bot_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Support Assistant',
  `bot_avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_chatbot_configs_owner_id_index` (`owner_id`),
  KEY `tbl_chatbot_configs_is_active_index` (`is_active`),
  CONSTRAINT `tbl_chatbot_configs_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_chatbot_conversations
CREATE TABLE IF NOT EXISTS `tbl_chatbot_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `config_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','ended','timeout') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` timestamp NULL DEFAULT NULL,
  `message_count` int NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_chatbot_conversations_session_id_unique` (`session_id`),
  KEY `tbl_chatbot_conversations_session_id_index` (`session_id`),
  KEY `tbl_chatbot_conversations_user_id_index` (`user_id`),
  KEY `tbl_chatbot_conversations_owner_id_index` (`owner_id`),
  KEY `tbl_chatbot_conversations_status_index` (`status`),
  KEY `tbl_chatbot_conversations_started_at_index` (`started_at`),
  KEY `tbl_chatbot_conversations_config_id_foreign` (`config_id`),
  CONSTRAINT `tbl_chatbot_conversations_config_id_foreign` FOREIGN KEY (`config_id`) REFERENCES `tbl_chatbot_configs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_chatbot_conversations_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_chatbot_conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_chatbot_intents
CREATE TABLE IF NOT EXISTS `tbl_chatbot_intents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `config_id` bigint unsigned NOT NULL,
  `intent_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_keywords` json NOT NULL,
  `response_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_type` enum('text','quick_reply','image') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `match_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_chatbot_intents_config_id_index` (`config_id`),
  KEY `tbl_chatbot_intents_is_active_index` (`is_active`),
  KEY `tbl_chatbot_intents_priority_index` (`priority`),
  CONSTRAINT `tbl_chatbot_intents_config_id_foreign` FOREIGN KEY (`config_id`) REFERENCES `tbl_chatbot_configs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_chatbot_messages
CREATE TABLE IF NOT EXISTS `tbl_chatbot_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `sender_type` enum('user','bot') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent_id` bigint unsigned DEFAULT NULL,
  `was_helpful` tinyint(1) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_chatbot_messages_conversation_id_index` (`conversation_id`),
  KEY `tbl_chatbot_messages_sender_type_index` (`sender_type`),
  KEY `tbl_chatbot_messages_created_at_index` (`created_at`),
  KEY `tbl_chatbot_messages_intent_id_foreign` (`intent_id`),
  CONSTRAINT `tbl_chatbot_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `tbl_chatbot_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_chatbot_messages_intent_id_foreign` FOREIGN KEY (`intent_id`) REFERENCES `tbl_chatbot_intents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_chatbot_quick_replies
CREATE TABLE IF NOT EXISTS `tbl_chatbot_quick_replies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `intent_id` bigint unsigned NOT NULL,
  `reply_text` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_value` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` enum('trigger_intent','open_url','none') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trigger_intent',
  `position` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_chatbot_quick_replies_intent_id_index` (`intent_id`),
  KEY `tbl_chatbot_quick_replies_is_active_index` (`is_active`),
  KEY `tbl_chatbot_quick_replies_position_index` (`position`),
  CONSTRAINT `tbl_chatbot_quick_replies_intent_id_foreign` FOREIGN KEY (`intent_id`) REFERENCES `tbl_chatbot_intents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_client_budget
CREATE TABLE IF NOT EXISTS `tbl_client_budget` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned NOT NULL,
  `budget_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Name/Title for this budget (e.g., Wedding Budget, Event Budget)',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description of the budget purpose',
  `minimum_budget` decimal(10,2) DEFAULT NULL COMMENT 'Minimum budget amount',
  `maximum_budget` decimal(10,2) DEFAULT NULL COMMENT 'Maximum budget amount',
  `preferred_budget` decimal(10,2) DEFAULT NULL COMMENT 'Preferred/ideal budget amount',
  `category_id` bigint unsigned DEFAULT NULL COMMENT 'Associated service category',
  `budget_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type: service, package, equipment, etc.',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'Budget status',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete support',
  PRIMARY KEY (`id`),
  KEY `tbl_client_budget_client_id_index` (`client_id`),
  KEY `tbl_client_budget_category_id_index` (`category_id`),
  KEY `tbl_client_budget_status_index` (`status`),
  KEY `tbl_client_budget_client_id_status_index` (`client_id`,`status`),
  KEY `tbl_client_budget_minimum_budget_maximum_budget_index` (`minimum_budget`,`maximum_budget`),
  CONSTRAINT `tbl_client_budget_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_client_budget_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_employee_attendance
CREATE TABLE IF NOT EXISTS `tbl_employee_attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `schedule_id` bigint unsigned DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `scheduled_start_time` time DEFAULT NULL,
  `scheduled_end_time` time DEFAULT NULL,
  `check_in_time` datetime DEFAULT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `check_in_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_status` enum('ON_TIME','LATE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_status` enum('ON_TIME','UNDERTIME') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `late_minutes` int NOT NULL DEFAULT '0',
  `undertime_minutes` int NOT NULL DEFAULT '0',
  `check_in_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_out_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in_user_agent` text COLLATE utf8mb4_unicode_ci,
  `check_out_user_agent` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_employee_attendance_user_id_index` (`user_id`),
  KEY `tbl_employee_attendance_studio_id_index` (`studio_id`),
  KEY `tbl_employee_attendance_attendance_date_index` (`attendance_date`),
  KEY `tbl_employee_attendance_check_in_status_index` (`check_in_status`),
  KEY `tbl_employee_attendance_schedule_id_index` (`schedule_id`),
  KEY `tbl_employee_attendance_user_id_attendance_date_index` (`user_id`,`attendance_date`),
  CONSTRAINT `tbl_employee_attendance_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `tbl_studio_employee_schedule` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_employee_attendance_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_employee_attendance_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_employee_payroll
CREATE TABLE IF NOT EXISTS `tbl_employee_payroll` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT 'Reference to tbl_users (employee)',
  `studio_id` bigint unsigned NOT NULL COMMENT 'Reference to tbl_studios',
  `created_by` bigint unsigned NOT NULL COMMENT 'Studio owner who created this payroll setting',
  `payroll_basis` enum('attendance_only','booking_and_attendance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'attendance_only' COMMENT 'attendance_only for HR/Finance, booking_and_attendance for photographers',
  `daily_rate` decimal(10,2) DEFAULT NULL COMMENT 'Daily rate for attendance-based calculation',
  `monthly_salary` decimal(10,2) DEFAULT NULL COMMENT 'Fixed monthly salary if applicable',
  `hourly_rate` decimal(10,2) DEFAULT NULL COMMENT 'Computed or manual hourly rate',
  `per_booking_rate` decimal(10,2) DEFAULT NULL COMMENT 'Rate per booking for photographers',
  `booking_commission_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Commission percentage from bookings',
  `sss_deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'SSS monthly contribution',
  `phic_deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'PhilHealth monthly contribution',
  `hdmf_deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Pag-IBIG monthly contribution',
  `tax_withholding` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Withholding tax amount',
  `sss_loan_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hdmf_loan_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `other_deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `is_taxable` tinyint(1) NOT NULL DEFAULT '1',
  `tax_type` enum('withholding','graduated','exempt') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'withholding',
  `tax_percentage` decimal(5,2) DEFAULT NULL COMMENT 'For percentage-based tax',
  `tax_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tax code/classification',
  `subject_to_vat` tinyint(1) NOT NULL DEFAULT '0',
  `vat_percentage` decimal(5,2) NOT NULL DEFAULT '12.00' COMMENT 'VAT rate (default 12%)',
  `vat_type` enum('inclusive','exclusive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'exclusive',
  `absence_deduction_per_day` decimal(10,2) DEFAULT NULL COMMENT 'Amount deducted per day of absence',
  `undertime_deduction_per_hour` decimal(10,2) DEFAULT NULL COMMENT 'Amount deducted per hour of undertime',
  `late_grace_period_minutes` int NOT NULL DEFAULT '15' COMMENT 'Grace period in minutes before deducting',
  `late_deduction_per_minute` decimal(10,2) DEFAULT NULL COMMENT 'Amount deducted per minute late after grace period',
  `absent_deduction_method` enum('deduct_daily_rate','deduct_fixed_amount','deduct_percentage') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'deduct_daily_rate',
  `absent_fixed_deduction` decimal(10,2) DEFAULT NULL COMMENT 'Fixed amount deduction for absence',
  `absent_percentage_deduction` decimal(5,2) DEFAULT NULL COMMENT 'Percentage deduction for absence',
  `paid_holidays` tinyint(1) NOT NULL DEFAULT '1',
  `payment_schedule` enum('weekly','bi_weekly','semi_monthly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'semi_monthly',
  `payday_1` int DEFAULT NULL COMMENT 'First payday of month (1-31)',
  `payday_2` int DEFAULT NULL COMMENT 'Second payday of month (1-31) for semi-monthly',
  `payday_weekly` enum('monday','tuesday','wednesday','thursday','friday') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_method` enum('bank_transfer','cash','check') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_date` date DEFAULT NULL COMMENT 'When these settings take effect',
  `expiry_date` date DEFAULT NULL COMMENT 'When these settings expire (if applicable)',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_payroll` (`user_id`,`studio_id`),
  KEY `tbl_employee_payroll_user_id_index` (`user_id`),
  KEY `tbl_employee_payroll_studio_id_index` (`studio_id`),
  KEY `tbl_employee_payroll_created_by_index` (`created_by`),
  KEY `tbl_employee_payroll_is_active_index` (`is_active`),
  KEY `tbl_employee_payroll_payroll_basis_index` (`payroll_basis`),
  KEY `tbl_employee_payroll_studio_id_is_active_index` (`studio_id`,`is_active`),
  KEY `tbl_employee_payroll_user_id_studio_id_index` (`user_id`,`studio_id`),
  CONSTRAINT `tbl_employee_payroll_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_employee_payroll_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_employee_payroll_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancers
CREATE TABLE IF NOT EXISTS `tbl_freelancers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tagline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `years_experience` int DEFAULT NULL,
  `brand_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barangay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starting_price` decimal(10,2) DEFAULT NULL,
  `deposit_policy` enum('required','not_required') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_type` enum('fixed','percentage') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'fixed = fixed amount, percentage = percentage of total',
  `deposit_amount` decimal(10,2) DEFAULT NULL COMMENT 'Amount or percentage value based on deposit_type',
  `portfolio_works` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `facebook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_freelancers_user_id_foreign` (`user_id`),
  KEY `tbl_freelancers_location_id_foreign` (`location_id`),
  CONSTRAINT `tbl_freelancers_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `tbl_locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_freelancers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancers_chk_1` CHECK (json_valid(`portfolio_works`))
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_online_gallery
CREATE TABLE IF NOT EXISTS `tbl_freelancer_online_gallery` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `freelancer_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `gallery_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gallery_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `total_photos` int NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_freelancer_online_gallery_gallery_reference_unique` (`gallery_reference`),
  KEY `tbl_freelancer_online_gallery_booking_id_index` (`booking_id`),
  KEY `tbl_freelancer_online_gallery_freelancer_id_index` (`freelancer_id`),
  KEY `tbl_freelancer_online_gallery_client_id_index` (`client_id`),
  KEY `tbl_freelancer_online_gallery_gallery_reference_index` (`gallery_reference`),
  CONSTRAINT `tbl_freelancer_online_gallery_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_online_gallery_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_online_gallery_freelancer_id_foreign` FOREIGN KEY (`freelancer_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_packages
CREATE TABLE IF NOT EXISTS `tbl_freelancer_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `package_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `allow_time_customization` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = fixed duration, 1 = clients can customize duration',
  `duration` int DEFAULT NULL,
  `maximum_edited_photos` int NOT NULL,
  `coverage_scope` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_price` decimal(10,2) NOT NULL,
  `online_gallery` tinyint(1) NOT NULL DEFAULT '0',
  `allow_multiple_locations` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Determines if package allows multiple shooting locations',
  `max_locations` int DEFAULT '1' COMMENT 'Maximum number of locations allowed (1-10)',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_freelancer_packages_user_id_index` (`user_id`),
  KEY `tbl_freelancer_packages_category_id_index` (`category_id`),
  KEY `tbl_freelancer_packages_status_index` (`status`),
  CONSTRAINT `tbl_freelancer_packages_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_packages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_packages_chk_1` CHECK (json_valid(`package_inclusions`))
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_plans
CREATE TABLE IF NOT EXISTS `tbl_freelancer_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `freelancer_id` bigint unsigned NOT NULL COMMENT 'FK to tbl_users.id (freelancer)',
  `plan_id` bigint unsigned NOT NULL COMMENT 'FK to tbl_subscription_plans.id',
  `subscription_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique reference for the subscription',
  `start_date` date NOT NULL COMMENT 'Subscription start date',
  `end_date` date NOT NULL COMMENT 'Subscription end date',
  `next_billing_date` date NOT NULL COMMENT 'Next billing date',
  `amount_paid` decimal(10,2) NOT NULL COMMENT 'Amount paid for current period',
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status` enum('active','expired','cancelled','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `plan_snapshot` json DEFAULT NULL COMMENT 'Snapshot of plan details at subscription time',
  `usage_metrics` json DEFAULT NULL COMMENT 'Current usage metrics (bookings)',
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_freelancer_plans_subscription_reference_unique` (`subscription_reference`),
  KEY `tbl_freelancer_plans_plan_id_foreign` (`plan_id`),
  KEY `tbl_freelancer_plans_freelancer_id_status_index` (`freelancer_id`,`status`),
  KEY `tbl_freelancer_plans_next_billing_date_index` (`next_billing_date`),
  KEY `tbl_freelancer_plans_subscription_reference_index` (`subscription_reference`),
  CONSTRAINT `tbl_freelancer_plans_freelancer_id_foreign` FOREIGN KEY (`freelancer_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_plans_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `tbl_subscription_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_ratings
CREATE TABLE IF NOT EXISTS `tbl_freelancer_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `freelancer_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL COMMENT '1-5 stars',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_type` enum('positive','neutral','negative') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preset_used` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The preset review template used',
  `is_recommend` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_freelancer_ratings_booking_id_foreign` (`booking_id`),
  KEY `tbl_freelancer_ratings_client_id_foreign` (`client_id`),
  CONSTRAINT `tbl_freelancer_ratings_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_ratings_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_schedules
CREATE TABLE IF NOT EXISTS `tbl_freelancer_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `operating_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `booking_limit` int DEFAULT NULL,
  `advance_booking` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_freelancer_schedules_user_id_foreign` (`user_id`),
  CONSTRAINT `tbl_freelancer_schedules_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_schedules_chk_1` CHECK (json_valid(`operating_days`))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_freelancer_services
CREATE TABLE IF NOT EXISTS `tbl_freelancer_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `services_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_category` (`user_id`,`category_id`),
  KEY `tbl_freelancer_services_category_id_foreign` (`category_id`),
  CONSTRAINT `tbl_freelancer_services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_services_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_freelancer_services_chk_1` CHECK (json_valid(`services_name`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_generated_payrolls
CREATE TABLE IF NOT EXISTS `tbl_generated_payrolls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payroll_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `payroll_setting_id` bigint unsigned NOT NULL,
  `generated_by` bigint unsigned NOT NULL,
  `employee_type` enum('regular_employee','studio_photographer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payroll_basis` enum('attendance_only','booking_and_attendance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `employee_role` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `attendance_days_present` int unsigned NOT NULL DEFAULT '0',
  `attendance_days_absent` int unsigned NOT NULL DEFAULT '0',
  `attendance_minutes_late` int unsigned NOT NULL DEFAULT '0',
  `attendance_minutes_undertime` int unsigned NOT NULL DEFAULT '0',
  `booking_count` int unsigned NOT NULL DEFAULT '0',
  `attendance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `booking_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gross_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deduction_breakdown` json DEFAULT NULL,
  `computation_summary` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `generated_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `generated_payroll_period_unique` (`user_id`,`studio_id`,`period_start`,`period_end`),
  UNIQUE KEY `tbl_generated_payrolls_payroll_reference_unique` (`payroll_reference`),
  KEY `tbl_generated_payrolls_user_id_index` (`user_id`),
  KEY `tbl_generated_payrolls_studio_id_index` (`studio_id`),
  KEY `tbl_generated_payrolls_payroll_setting_id_index` (`payroll_setting_id`),
  KEY `tbl_generated_payrolls_generated_by_index` (`generated_by`),
  KEY `tbl_generated_payrolls_studio_id_period_start_period_end_index` (`studio_id`,`period_start`,`period_end`),
  CONSTRAINT `tbl_generated_payrolls_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_generated_payrolls_payroll_setting_id_foreign` FOREIGN KEY (`payroll_setting_id`) REFERENCES `tbl_employee_payroll` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_generated_payrolls_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_generated_payrolls_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_locations
CREATE TABLE IF NOT EXISTS `tbl_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cavite',
  `municipality` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barangay` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `zip_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `tbl_locations_chk_1` CHECK (json_valid(`barangay`))
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_notifications
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bell',
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_notifications_uuid_unique` (`uuid`),
  KEY `tbl_notifications_user_id_read_at_index` (`user_id`,`read_at`),
  KEY `tbl_notifications_created_at_index` (`created_at`),
  CONSTRAINT `tbl_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_packages
CREATE TABLE IF NOT EXISTS `tbl_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `package_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `package_inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `duration` int DEFAULT NULL,
  `maximum_edited_photos` int NOT NULL,
  `coverage_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `package_location` json DEFAULT NULL,
  `allow_multiple_locations` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Determines if package allows multiple shooting locations',
  `max_locations` int DEFAULT '1' COMMENT 'Maximum number of locations allowed (1-10)',
  `allow_time_customization` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = Fixed duration only, 1 = Clients can customize time',
  `package_price` decimal(10,2) NOT NULL,
  `online_gallery` tinyint(1) NOT NULL DEFAULT '0',
  `photographer_count` int NOT NULL DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_packages_studio_id_index` (`studio_id`),
  KEY `tbl_packages_category_id_index` (`category_id`),
  KEY `tbl_packages_status_index` (`status`),
  CONSTRAINT `tbl_packages_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_packages_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_packages_chk_1` CHECK (json_valid(`package_inclusions`)),
  CONSTRAINT `tbl_packages_chk_2` CHECK (json_valid(`coverage_scope`))
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_payments
CREATE TABLE IF NOT EXISTS `tbl_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'card',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_details` json DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_payments_payment_reference_unique` (`payment_reference`),
  KEY `tbl_payments_booking_id_foreign` (`booking_id`),
  CONSTRAINT `tbl_payments_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_permissions
CREATE TABLE IF NOT EXISTS `tbl_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_permissions_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_rbac
CREATE TABLE IF NOT EXISTS `tbl_rbac` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `can_create` tinyint(1) NOT NULL DEFAULT '0',
  `can_read` tinyint(1) NOT NULL DEFAULT '0',
  `can_update` tinyint(1) NOT NULL DEFAULT '0',
  `can_delete` tinyint(1) NOT NULL DEFAULT '0',
  `module_permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_rbac_user_id_unique` (`user_id`),
  KEY `tbl_rbac_user_id_index` (`user_id`),
  KEY `tbl_rbac_studio_id_index` (`studio_id`),
  KEY `tbl_rbac_role_index` (`role`),
  CONSTRAINT `tbl_rbac_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_rbac_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_roles
CREATE TABLE IF NOT EXISTS `tbl_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_role_permissions
CREATE TABLE IF NOT EXISTS `tbl_role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_role_permissions_role_id_permission_id_unique` (`role_id`,`permission_id`),
  KEY `tbl_role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `tbl_role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `tbl_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_services
CREATE TABLE IF NOT EXISTS `tbl_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `service_name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_services_category_id_foreign` (`category_id`),
  KEY `tbl_services_studio_id_category_id_index` (`studio_id`,`category_id`),
  CONSTRAINT `tbl_services_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_services_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studios
CREATE TABLE IF NOT EXISTS `tbl_studios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `street` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `barangay` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `studio_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `studio_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `studio_type` enum('photography_studio','video_production','mixed_media') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'photography_studio',
  `year_established` int NOT NULL,
  `studio_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `studio_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `starting_price` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `downpayment_percentage` decimal(5,2) NOT NULL DEFAULT '30.00' COMMENT 'Required downpayment percentage for bookings',
  `operating_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `max_clients_per_day` int NOT NULL DEFAULT '1',
  `advance_booking_days` int NOT NULL DEFAULT '1',
  `business_permit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_id_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','verified','rejected','active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rejection_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_studios_user_id_index` (`user_id`),
  KEY `tbl_studios_status_index` (`status`),
  KEY `tbl_studios_category_id_foreign` (`category_id`),
  KEY `fk_studios_location_id` (`location_id`),
  CONSTRAINT `fk_studios_location_id` FOREIGN KEY (`location_id`) REFERENCES `tbl_locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tbl_studios_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `tbl_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_studios_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studios_chk_1` CHECK (json_valid(`operating_days`))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_employee_schedule
CREATE TABLE IF NOT EXISTS `tbl_studio_employee_schedule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `operating_days` json NOT NULL,
  `start_time` time NOT NULL DEFAULT '09:00:00',
  `end_time` time NOT NULL DEFAULT '18:00:00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_studio_employee_schedule_user_id_index` (`user_id`),
  KEY `tbl_studio_employee_schedule_studio_id_index` (`studio_id`),
  KEY `tbl_studio_employee_schedule_is_active_index` (`is_active`),
  CONSTRAINT `tbl_studio_employee_schedule_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_employee_schedule_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_members
CREATE TABLE IF NOT EXISTS `tbl_studio_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `freelancer_id` bigint unsigned NOT NULL,
  `invited_by` bigint unsigned NOT NULL,
  `invitation_message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `response_message` text COLLATE utf8mb4_unicode_ci,
  `invited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_studio_freelancer` (`studio_id`,`freelancer_id`),
  KEY `tbl_studio_members_invited_by_foreign` (`invited_by`),
  KEY `tbl_studio_members_studio_id_status_index` (`studio_id`,`status`),
  KEY `tbl_studio_members_freelancer_id_status_index` (`freelancer_id`,`status`),
  CONSTRAINT `tbl_studio_members_freelancer_id_foreign` FOREIGN KEY (`freelancer_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_members_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_members_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_online_gallery
CREATE TABLE IF NOT EXISTS `tbl_studio_online_gallery` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `gallery_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gallery_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `images` json DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `total_photos` int NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_studio_online_gallery_gallery_reference_unique` (`gallery_reference`),
  KEY `tbl_studio_online_gallery_booking_id_index` (`booking_id`),
  KEY `tbl_studio_online_gallery_studio_id_index` (`studio_id`),
  KEY `tbl_studio_online_gallery_client_id_index` (`client_id`),
  KEY `tbl_studio_online_gallery_status_index` (`status`),
  CONSTRAINT `tbl_studio_online_gallery_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_online_gallery_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_online_gallery_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_photographers
CREATE TABLE IF NOT EXISTS `tbl_studio_photographers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `photographer_id` bigint unsigned NOT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` bigint unsigned DEFAULT NULL,
  `years_of_experience` int DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_studio_photographers_studio_id_photographer_id_unique` (`studio_id`,`photographer_id`),
  KEY `tbl_studio_photographers_studio_id_index` (`studio_id`),
  KEY `tbl_studio_photographers_owner_id_index` (`owner_id`),
  KEY `tbl_studio_photographers_photographer_id_index` (`photographer_id`),
  KEY `tbl_studio_photographers_specialization_index` (`specialization`),
  KEY `tbl_studio_photographers_status_index` (`status`),
  CONSTRAINT `tbl_studio_photographers_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_photographers_photographer_id_foreign` FOREIGN KEY (`photographer_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_photographers_specialization_foreign` FOREIGN KEY (`specialization`) REFERENCES `tbl_services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_photographers_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_plans
CREATE TABLE IF NOT EXISTS `tbl_studio_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned NOT NULL,
  `subscription_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_response` json DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `next_billing_date` date DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status` enum('active','expired','cancelled','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `plan_snapshot` json DEFAULT NULL,
  `usage_metrics` json DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_studio_plans_subscription_reference_unique` (`subscription_reference`),
  KEY `tbl_studio_plans_plan_id_foreign` (`plan_id`),
  KEY `tbl_studio_plans_subscription_reference_index` (`subscription_reference`),
  KEY `tbl_studio_plans_status_index` (`status`),
  KEY `tbl_studio_plans_studio_id_status_index` (`studio_id`,`status`),
  CONSTRAINT `tbl_studio_plans_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `tbl_subscription_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_plans_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_ratings
CREATE TABLE IF NOT EXISTS `tbl_studio_ratings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `studio_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL COMMENT '1-5 stars',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_type` enum('positive','neutral','negative') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preset_used` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'The preset review template used',
  `is_recommend` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_booking_review` (`booking_id`),
  KEY `tbl_studio_ratings_client_id_foreign` (`client_id`),
  KEY `tbl_studio_ratings_studio_id_foreign` (`studio_id`),
  CONSTRAINT `tbl_studio_ratings_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_ratings_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_ratings_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_studio_schedules
CREATE TABLE IF NOT EXISTS `tbl_studio_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `studio_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `operating_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `booking_limit` int NOT NULL,
  `advance_booking` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_studio_schedules_studio_id_index` (`studio_id`),
  KEY `tbl_studio_schedules_location_id_index` (`location_id`),
  CONSTRAINT `tbl_studio_schedules_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `tbl_locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_schedules_studio_id_foreign` FOREIGN KEY (`studio_id`) REFERENCES `tbl_studios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_studio_schedules_chk_1` CHECK (json_valid(`operating_days`))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_subscription_plans
CREATE TABLE IF NOT EXISTS `tbl_subscription_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` enum('studio','freelancer') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Target user type: studio or freelancer',
  `plan_type` enum('basic','premium','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Plan tier',
  `billing_cycle` enum('monthly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Monthly or yearly billing',
  `plan_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique plan identifier (e.g., STUDIO_BASIC_MONTHLY)',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Display name of the plan',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Plan description',
  `price` decimal(10,2) NOT NULL COMMENT 'Subscription price',
  `commission_rate` decimal(5,2) NOT NULL COMMENT 'Platform commission percentage',
  `max_booking` int DEFAULT NULL COMMENT 'Maximum bookings allowed (null = unlimited)',
  `max_studio_photographers` int DEFAULT NULL COMMENT 'Maximum photographers for studios (null = unlimited)',
  `max_studios` int DEFAULT NULL COMMENT 'Maximum number of studios a studio owner can register (null = unlimited)',
  `staff_limit` int DEFAULT NULL COMMENT 'Maximum number of staff/employees for studio (null = unlimited)',
  `priority_level` int NOT NULL DEFAULT '0' COMMENT 'Priority level for display (higher = shows first)',
  `features` json DEFAULT NULL COMMENT 'JSON array of plan features',
  `support_level` enum('basic','priority','dedicated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'basic' COMMENT 'Support level',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_subscription_plans_plan_code_unique` (`plan_code`),
  KEY `tbl_subscription_plans_user_type_status_index` (`user_type`,`status`),
  KEY `tbl_subscription_plans_plan_code_index` (`plan_code`),
  KEY `tbl_subscription_plans_priority_level_index` (`priority_level`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_system_revenue
CREATE TABLE IF NOT EXISTS `tbl_system_revenue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_reference` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `platform_fee_percentage` decimal(5,2) NOT NULL DEFAULT '10.00',
  `platform_fee_amount` decimal(12,2) NOT NULL,
  `provider_amount` decimal(12,2) NOT NULL,
  `provider_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revenue_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booking' COMMENT 'booking or subscription',
  `provider_id` bigint unsigned NOT NULL,
  `client_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `breakdown` json DEFAULT NULL,
  `settled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_system_revenue_transaction_reference_unique` (`transaction_reference`),
  KEY `tbl_system_revenue_transaction_reference_index` (`transaction_reference`),
  KEY `tbl_system_revenue_booking_id_index` (`booking_id`),
  KEY `tbl_system_revenue_payment_id_index` (`payment_id`),
  KEY `tbl_system_revenue_provider_id_index` (`provider_id`),
  KEY `tbl_system_revenue_client_id_index` (`client_id`),
  KEY `tbl_system_revenue_status_index` (`status`),
  KEY `tbl_system_revenue_subscription_id_foreign` (`subscription_id`),
  CONSTRAINT `tbl_system_revenue_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_system_revenue_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_system_revenue_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `tbl_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_system_revenue_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `tbl_studio_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_users
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','owner','freelancer','client','studio-photographer','studio-staff','studio-hr','studio-finance') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `middle_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('Photographer','Customer','Admin','Staff','Manager') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Customer',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verification_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expiry` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_users_uuid_unique` (`uuid`),
  UNIQUE KEY `tbl_users_email_unique` (`email`),
  KEY `tbl_users_email_index` (`email`),
  KEY `tbl_users_role_index` (`role`),
  KEY `tbl_users_status_index` (`status`),
  KEY `tbl_users_location_id_index` (`location_id`),
  CONSTRAINT `tbl_users_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `tbl_locations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table platinum.tbl_user_roles
CREATE TABLE IF NOT EXISTS `tbl_user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_user_roles_user_id_role_id_unique` (`user_id`,`role_id`),
  KEY `tbl_user_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `tbl_user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

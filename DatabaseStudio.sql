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

-- Dumping data for table platinum.migrations: ~46 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(2, '2026_01_23_025132_add_user_type_to_tbl_users_table', 1),
	(4, '2026_01_24_172015_create_tbl_studios_table', 2),
	(5, '2026_01_24_172022_create_tbl_location_table', 2),
	(8, '2026_01_22_093354_create_tbl_users_table', 3),
	(9, '2026_01_23_085704_create_tbl_categories_table', 3),
	(10, '2026_01_24_175854_create_tbl_studios_table', 4),
	(11, '2026_01_24_175920_create_tbl_location_table', 4),
	(12, '2026_01_24_180340_create_tbl_studio_categories_table', 5),
	(13, '2026_01_24_182149_add_service_coverage_area_to_tbl_studios_table', 6),
	(14, '2026_01_25_140023_drop_tbl_studio_categories_table', 7),
	(15, '2026_01_25_140045_add_category_id_to_tbl_studios_table', 7),
	(16, '2026_01_25_154427_create_tbl_services_table', 8),
	(17, '2026_01_25_161945_create_tbl_services_table', 9),
	(18, '2026_01_26_034254_create_packages_table', 10),
	(19, '2026_01_26_055451_create_tbl_packages_table', 11),
	(20, '2026_01_26_131528_remove_service_description_and_status_from_tbl_services_table', 12),
	(21, '2026_01_26_132323_change_service_name_to_json_in_tbl_services', 13),
	(22, '2026_01_27_085739_create_tbl_locations_table', 14),
	(23, '2026_01_28_041639_create_tbl_studio_schedules_table', 15),
	(24, '2026_01_28_151726_create_pvt_studio_categories_table', 16),
	(25, '2026_01_28_151746_remove_service_coverage_area_from_tbl_studios', 16),
	(26, '2026_01_28_151803_add_contact_info_to_tbl_studios', 16),
	(27, '2026_01_29_152148_create_freelancer_schedules_table', 17),
	(28, '2026_01_29_152148_create_freelancers_table', 17),
	(30, '2026_01_29_152148_create_pvt_freelancer_categories_table', 18),
	(31, '2026_01_31_161024_create_tbl_freelancer_services_table_fixed', 18),
	(32, '2026_01_31_162439_add_category_id_to_tbl_freelancer_services', 19),
	(33, '2026_02_01_071852_create_freelancer_packages_table', 20),
	(34, '2026_02_01_083143_add_unique_constraint_to_tbl_freelancer_services', 21),
	(35, '2026_01_31_161024_create_tbl_freelancer_services_table', 22),
	(36, '2026_02_02_004222_add_location_id_to_tbl_users_table', 23),
	(37, '2026_02_03_090801_create_tbl_studio_members_table', 24),
	(38, '2026_02_04_145521_create_tbl_studio_photographers_table', 25),
	(39, '2026_02_04_145527_create_pvt_studio_photographers_table', 25),
	(40, '2026_02_04_155822_simple_fix_specialization_column', 26),
	(41, '2026_02_04_160047_add_foreign_key_to_specialization', 27),
	(42, '2026_02_04_160453_create_tbl_studio_photographers_table_v2', 28),
	(43, '2026_02_05_023818_change_specialization_fk_to_services_in_studio_photographers', 29),
	(44, '2026_02_06_090123_drop_pvt_studio_photographers_table', 30),
	(45, '2026_02_08_093206_create_tbl_bookings_table', 31),
	(46, '2026_02_08_093213_create_tbl_payments_table', 31),
	(47, '2026_02_08_101236_create_bookings_and_payments_tables', 32),
	(48, '2026_02_08_102822_create_tbl_bookings_table', 33),
	(49, '2026_02_08_102828_create_tbl_payments_table', 33),
	(50, '2026_02_08_102833_create_tbl_booking_packages_table', 33),
	(51, '2026_02_08_150413_add_payment_type_to_bookings_table', 34),
	(52, '2026_02_09_132930_create_studio_photographer_assignments_table', 35),
	(53, '2026_02_09_134711_create_tbl_booking_photographers_table', 36),
	(54, '2026_02_09_151150_create_booking_assigned_photographers_table', 37),
	(57, '2026_02_11_035017_create_tbl_system_revenue_table', 38),
	(58, '2026_02_12_060445_add_online_gallery_and_photographer_count_to_tbl_packages_table', 39),
	(61, '2026_02_14_073655_create_tbl_studio_ratings_table', 40),
	(62, '2026_02_14_074433_create_tbl_studio_ratings_table', 41),
	(63, '2026_02_14_081201_create_tbl_studio_ratings_table', 42),
	(64, '2026_02_14_085714_create_tbl_freelancer_ratings_table', 43),
	(65, '2026_02_16_072329_create_tbl_online_gallery_table', 44),
	(66, '2026_02_16_091620_create_tbl_studio_online_gallery_table', 45),
	(67, '2026_02_16_093634_create_tbl_freelancer_online_gallery_table', 46),
	(68, '2026_02_16_135556_add_online_gallery_to_freelancer_packages_table', 47),
	(69, '2026_02_19_084643_create_tbl_subscription_plans', 48),
	(70, '2026_02_19_084644_create_tbl_studio_plans', 48),
	(71, '2026_02_19_084700_create_tbl_freelancer_plans', 48),
	(72, '2026_02_19_094538_add_additional_fields_to_tbl_subscription_plans', 49),
	(73, '2026_02_21_091800_add_started_at_to_tbl_booking_assigned_photographers', 50),
	(74, '2026_02_21_093953_add_started_at_to_tbl_booking_assigned_photographers_table', 51),
	(75, '2026_02_22_145351_create_tbl_notifications_table', 52),
	(77, '2026_02_24_093052_create_tbl_studio_plans_table', 53),
	(78, '2026_02_24_095336_add_stripe_session_id_to_tbl_studio_plans', 54),
	(79, '2026_02_24_130338_add_studio_plan_payment_fields_to_tbl_studio_plans', 55),
	(80, '2026_02_25_002803_add_subscription_fields_to_system_revenue_table', 56),
	(81, '2026_02_25_035512_add_downpayment_percentage_to_tbl_studios_table', 57),
	(82, '2026_02_26_030955_add_package_location_to_tbl_packages_table', 58),
	(85, '2026_02_26_134817_add_allow_time_customization_to_tbl_packages_table', 59),
	(86, '2026_02_26_135623_make_duration_nullable_in_tbl_packages_table', 60),
	(87, '2026_02_27_072011_add_deposit_configuration_to_freelancers_table', 61),
	(88, '2026_02_27_081236_add_allow_time_customization_to_freelancer_packages_table', 62),
	(89, '2026_02_27_082531_modify_duration_nullable_in_freelancer_packages', 63),
	(90, '2026_03_01_132318_add_cover_photo_to_tbl_users_table', 64),
	(91, '2026_03_02_022114_modify_package_location_to_allow_multiple_in_tbl_packages', 65),
	(92, '2026_03_02_095858_add_multiple_locations_to_tbl_packages_table', 66),
	(93, '2026_03_02_135545_add_multiple_locations_to_tbl_freelancer_packages_table', 67),
	(98, '2026_03_03_090434_add_multiple_locations_to_tbl_bookings_table', 68),
	(99, '2026_03_03_090606_add_multiple_locations_to_tbl_bookings_table', 69),
	(100, '2026_03_04_060017_create_tbl_rbac_table', 70),
	(101, '2026_03_04_060048_create_tbl_studio_employee_schedule_table', 70),
	(102, '2026_03_04_061555_update_users_role_enum_for_employees', 71),
	(103, '2026_03_04_062314_update_users_user_type_enum', 72),
	(104, '2026_03_05_063346_create_tbl_employee_payroll_table', 73),
	(105, '2026_03_10_090654_remove_unused_payroll_columns_from_tbl_employee_payroll', 74),
	(106, '2026_03_11_051427_create_tbl_client_budget_table', 75),
	(107, '2026_03_16_072122_create_tbl_employee_attendance_table', 76),
	(108, '2026_03_18_203430_add_on_site_fields_to_booking_assigned_photographers_table', 77),
	(109, '2026_03_18_205308_add_on_site_to_booking_assigned_photographers_status', 78),
	(110, '2026_03_19_150022_create_chatbot_configs_table', 79),
	(111, '2026_03_19_150023_create_chatbot_conversations_table', 79),
	(112, '2026_03_19_150023_create_chatbot_intents_table', 79),
	(113, '2026_03_19_150023_create_chatbot_messages_table', 79),
	(114, '2026_03_19_150024_create_chatbot_quick_replies_table', 79),
	(115, '2026_03_20_174218_create_tbl_roles_table', 80),
	(116, '2026_03_20_174219_create_tbl_permissions_table', 80),
	(117, '2026_03_20_174219_create_tbl_role_permissions_table', 80),
	(118, '2026_03_20_174229_create_tbl_user_roles_table', 80),
	(119, '2026_03_25_162147_create_tbl_generated_payrolls_table', 81);

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

-- Dumping data for table platinum.pvt_freelancer_categories: ~2 rows (approximately)
INSERT INTO `pvt_freelancer_categories` (`id`, `user_id`, `category_id`, `created_at`, `updated_at`) VALUES
	(1, 98, 2, '2026-02-26 23:26:55', '2026-02-26 23:26:55'),
	(2, 98, 5, '2026-02-26 23:26:55', '2026-02-26 23:26:55'),
	(3, 98, 10, '2026-02-26 23:26:55', '2026-02-26 23:26:55');

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

-- Dumping data for table platinum.pvt_studio_categories: ~8 rows (approximately)
INSERT INTO `pvt_studio_categories` (`id`, `user_id`, `studio_id`, `category_id`, `created_at`, `updated_at`) VALUES
	(7, 97, 3, 1, '2026-02-26 21:05:16', '2026-02-26 21:05:16'),
	(8, 97, 3, 2, '2026-02-26 21:05:16', '2026-02-26 21:05:16'),
	(9, 97, 3, 3, '2026-02-26 21:05:16', '2026-02-26 21:05:16'),
	(10, 97, 3, 4, '2026-02-26 21:05:16', '2026-02-26 21:05:16'),
	(11, 97, 3, 6, '2026-02-26 21:05:16', '2026-02-26 21:05:16'),
	(12, 96, 1, 3, '2026-03-08 05:08:45', '2026-03-08 05:08:45'),
	(13, 96, 1, 4, '2026-03-08 05:08:45', '2026-03-08 05:08:45'),
	(14, 96, 1, 10, '2026-03-08 05:08:45', '2026-03-08 05:08:45');

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

-- Dumping data for table platinum.tbl_bookings: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_booking_assigned_photographers: ~3 rows (approximately)

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

-- Dumping data for table platinum.tbl_booking_packages: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_categories: ~10 rows (approximately)
INSERT INTO `tbl_categories` (`id`, `category_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Wedding Photography', 'Records moments and emotions from a couple’s wedding day.', 'active', '2026-01-24 09:51:50', '2026-01-24 09:51:50'),
	(2, 'Event Photography', 'Documents occasions like weddings, concerts, and corporate gatherings.', 'active', '2026-01-24 09:52:10', '2026-01-24 09:52:10'),
	(3, 'Family Portrait', 'Family and group portrait sessions', 'active', '2026-01-26 04:13:33', '2026-01-26 04:13:33'),
	(4, 'Product Photography', 'Photos for online selling and ads', 'active', '2026-01-26 04:13:55', '2026-01-26 04:13:55'),
	(5, 'Street Photography', 'Captures candid moments of everyday life in public places.', 'active', '2026-01-26 04:14:40', '2026-01-26 04:14:40'),
	(6, 'Fashion Photography', 'Displays clothing, accessories, and style, often for magazines or advertising.', 'active', '2026-01-26 04:14:51', '2026-01-26 04:14:51'),
	(7, 'Documentary Photography', 'Tells real-life stories through images, often with social or historical focus.', 'active', '2026-01-26 04:15:03', '2026-01-26 04:15:03'),
	(8, 'Food Photography', 'Makes dishes and drinks look appealing for menus, ads, or social media.', 'active', '2026-01-26 04:15:59', '2026-01-26 04:15:59'),
	(9, 'Real Estate Photography', 'Highlights properties and interiors for listings and marketing.', 'active', '2026-01-26 04:16:08', '2026-01-26 04:16:08'),
	(10, 'Pet Photography', 'Focuses on animals in domestic or stylized environments.', 'active', '2026-01-26 04:16:20', '2026-01-26 04:16:20');

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

-- Dumping data for table platinum.tbl_chatbot_configs: ~2 rows (approximately)
INSERT INTO `tbl_chatbot_configs` (`id`, `owner_id`, `config_name`, `welcome_message`, `fallback_message`, `is_active`, `bot_name`, `bot_avatar`, `settings`, `created_at`, `updated_at`) VALUES
	(1, 96, 'Photography Studio Support Bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Soo-Bot, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', 1, 'Soo-Bot', NULL, NULL, '2026-03-19 07:51:51', '2026-03-19 13:10:58'),
	(2, 97, 'Default Configuration', 'Hello! How can I assist you today?', 'I apologize, but I don\'t understand. Please contact our support team for assistance.', 1, 'Support Assistant', NULL, NULL, '2026-03-19 09:04:25', '2026-03-19 09:04:25');

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

-- Dumping data for table platinum.tbl_chatbot_conversations: ~12 rows (approximately)
INSERT INTO `tbl_chatbot_conversations` (`id`, `session_id`, `user_id`, `owner_id`, `config_id`, `status`, `started_at`, `ended_at`, `message_count`, `metadata`, `created_at`, `updated_at`) VALUES
	(1, 'CHAT-69BBB8EDDE0F4-1099', 82, 96, 1, 'active', '2026-03-19 08:50:53', NULL, 11, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T16:52:53+08:00", "total_messages": 11}', '2026-03-19 08:50:53', '2026-03-19 08:52:53'),
	(2, 'CHAT-69BBB97530943-1223', 82, 96, 1, 'active', '2026-03-19 08:53:09', NULL, 7, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T16:53:30+08:00", "total_messages": 7}', '2026-03-19 08:53:09', '2026-03-19 08:53:30'),
	(3, 'CHAT-69BBB9971C77B-8388', 82, 96, 1, 'active', '2026-03-19 08:53:43', NULL, 1, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start"}', '2026-03-19 08:53:43', '2026-03-19 08:53:43'),
	(4, 'CHAT-69BBBC1CA0E63-7061', 82, 97, 2, 'ended', '2026-03-19 09:04:28', '2026-03-19 09:04:42', 3, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T17:04:35+08:00", "total_messages": 3}', '2026-03-19 09:04:28', '2026-03-19 09:04:42'),
	(5, 'CHAT-69BBBC4F03D14-5082', 82, 96, 1, 'ended', '2026-03-19 09:05:19', '2026-03-19 09:05:40', 1, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start"}', '2026-03-19 09:05:19', '2026-03-19 09:05:40'),
	(6, 'CHAT-69BBBC65A15C9-8454', 82, 96, 1, 'ended', '2026-03-19 09:05:41', '2026-03-19 09:05:53', 1, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start"}', '2026-03-19 09:05:41', '2026-03-19 09:05:53'),
	(7, 'CHAT-69BBBC936EC38-9650', 82, 96, 1, 'ended', '2026-03-19 09:06:27', '2026-03-19 09:08:35', 7, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T17:08:32+08:00", "total_messages": 7}', '2026-03-19 09:06:27', '2026-03-19 09:08:35'),
	(8, 'CHAT-69BBBD3011DF6-3812', 82, 96, 1, 'ended', '2026-03-19 09:09:04', '2026-03-19 09:10:13', 3, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T17:09:16+08:00", "total_messages": 3}', '2026-03-19 09:09:04', '2026-03-19 09:10:13'),
	(9, 'CHAT-69BBBD76B4C2A-5818', 82, 96, 1, 'active', '2026-03-19 09:10:14', NULL, 5, '{"bot_name": "Support Assistant", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T17:10:23+08:00", "total_messages": 5}', '2026-03-19 09:10:14', '2026-03-19 09:10:23'),
	(10, 'CHAT-69BBF808A6A79-4804', 79, 96, 1, 'ended', '2026-03-19 13:20:08', '2026-03-19 13:20:55', 13, '{"bot_name": "Soo-Bot", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T21:20:51+08:00", "total_messages": 13}', '2026-03-19 13:20:08', '2026-03-19 13:20:55'),
	(11, 'CHAT-69BBF83E0848D-4465', 79, 96, 1, 'ended', '2026-03-19 13:21:02', '2026-03-19 13:35:40', 17, '{"bot_name": "Soo-Bot", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T21:22:48+08:00", "total_messages": 17}', '2026-03-19 13:21:02', '2026-03-19 13:35:40'),
	(12, 'CHAT-69BBFBAF55069-5705', 79, 96, 1, 'ended', '2026-03-19 13:35:43', '2026-03-19 13:35:57', 9, '{"bot_name": "Soo-Bot", "started_from": "client/chatbot/start", "last_activity": "2026-03-19T21:35:54+08:00", "total_messages": 9}', '2026-03-19 13:35:43', '2026-03-19 13:35:57');

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

-- Dumping data for table platinum.tbl_chatbot_intents: ~2 rows (approximately)
INSERT INTO `tbl_chatbot_intents` (`id`, `config_id`, `intent_name`, `trigger_keywords`, `response_text`, `response_type`, `image_url`, `priority`, `is_active`, `match_count`, `created_at`, `updated_at`) VALUES
	(2, 1, 'Service Inquiries', '["services", "offer", "provide", "what services", "photography services", "videography", "coverage", "types of services", "what do you offer", "offerings", "service list"]', 'We offer a comprehensive range of photography and videography services:\n\n📸 WEDDING PHOTOGRAPHY\n- Full day coverage (preparation to reception)\n- Pre-nuptial photo sessions\n- Same Day Edit (SDE) videos\n- Printed photo albums\n\n👶 MATERNITY & NEWBORN\n- Studio sessions with professional lighting\n- On-location outdoor shoots\n- Props and outfits included\n- Digital copies with printing rights\n\n🎉 EVENTS & PARTIES\n- Birthday celebrations\n- Debuts and anniversaries\n- Corporate events and conferences\n- Team building documentation\n\n👔 PORTRAITS & HEADSHOTS\n- Professional corporate headshots\n- Family portraits\n- Personal branding sessions\n- Model portfolios\n\n🏠 REAL ESTATE\n- Property photography for listings\n- Virtual tours\n- Drone aerial shots\n- 360-degree room views\n\n📹 VIDEOGRAPHY SERVICES\n- Event highlights (3-5 minutes)\n- Full ceremony coverage\n- Interview/documentary style\n- Social media ready clips\n\nWhich service are you interested in learning more about? I can provide detailed package information, pricing, and sample portfolios!', 'quick_reply', NULL, 75, 1, 4, '2026-03-19 13:14:56', '2026-03-19 13:22:15'),
	(3, 1, 'Wedding Packages Detailed', '["wedding packages", "wedding rates", "kasal package", "wedding price", "prenup package", "wedding coverage cost", "same day edit price", "wedding photo package", "kasal rates", "wedding inclusions"]', 'Our wedding photography packages are designed to capture every precious moment of your special day:\n\n💒 **ESSENTIAL WEDDING - ₱25,000**\n✓ 4 hours coverage (ceremony + reception basics)\n✓ 1 professional photographer\n✓ 100 edited high-resolution photos\n✓ Online private gallery for 3 months\n✓ Free pre-wedding consultation\n✓ 50 prints (4R size)\n✓ Soft copy on USB drive\n\n💍 **PREMIUM WEDDING - ₱40,000**\n✓ 8 hours full day coverage (preparation to reception)\n✓ 2 photographers (lead + assistant)\n✓ 200+ edited high-resolution photos\n✓ Online gallery for 6 months with download rights\n✓ Pre-nuptial photo session (2 hours, 1 location)\n✓ Same Day Edit (SDE) 3-5 minute video\n✓ 100 prints (assorted sizes)\n✓ Soft copy on custom USB box\n✓ Free album layout design\n\n👰 **LUXURY WEDDING - ₱65,000**\n✓ 10+ hours complete coverage\n✓ 3 photographers (team coverage)\n✓ 400+ edited premium photos\n✓ Lifetime online gallery with printing rights\n✓ Pre-nuptial video (2-3 minutes cinematic)\n✓ Full ceremony video (30-45 minutes)\n✓ Same Day Edit (SDE) video shown at reception\n✓ Premium 20-page photo album (10x10)\n✓ 150 premium prints with box\n✓ Drone aerial coverage (weather permitting)\n✓ 2nd shooter free for pre-nup\n✓ Engagement session inclusion\n\n📸 **A LA CARTE ADD-ONS:**\n• Additional hour: ₱3,000\n• Extra photographer: ₱5,000\n• Photo album upgrade: ₱8,000\n• Raw footage DVD: ₱4,000\n• Extended pre-nup session: ₱6,000\n\nAll packages include professional editing, online proofing gallery, and full printing rights to downloaded photos. We require 30% downpayment to reserve your date, with balance due on or before the event day.\n\nWould you like to schedule a free consultation to discuss your wedding vision?', 'quick_reply', NULL, 90, 1, 2, '2026-03-19 13:17:09', '2026-03-19 13:21:56');

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

-- Dumping data for table platinum.tbl_chatbot_messages: ~78 rows (approximately)
INSERT INTO `tbl_chatbot_messages` (`id`, `conversation_id`, `sender_type`, `message`, `intent_id`, `was_helpful`, `metadata`, `created_at`, `updated_at`) VALUES
	(1, 1, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 08:50:53', '2026-03-19 08:50:53'),
	(2, 1, 'user', 'Hi', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:50:58', '2026-03-19 08:50:58'),
	(3, 1, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 08:50:58', '2026-03-19 08:50:58'),
	(4, 1, 'user', 'price', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:51:07', '2026-03-19 08:51:07'),
	(5, 1, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 08:51:07', '2026-03-19 08:51:07'),
	(6, 1, 'user', 'Hello', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:51:21', '2026-03-19 08:51:21'),
	(7, 1, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 08:51:21', '2026-03-19 08:51:21'),
	(8, 1, 'user', 'magkano', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:52:35', '2026-03-19 08:52:35'),
	(9, 1, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 08:52:35', '2026-03-19 08:52:35'),
	(10, 1, 'user', 'Send me brochure', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:52:53', '2026-03-19 08:52:53'),
	(11, 1, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 08:52:53', '2026-03-19 08:52:53'),
	(12, 2, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 08:53:09', '2026-03-19 08:53:09'),
	(13, 2, 'user', 'Hi', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:53:18', '2026-03-19 08:53:18'),
	(14, 2, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 08:53:18', '2026-03-19 08:53:18'),
	(15, 2, 'user', 'how much?', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:53:27', '2026-03-19 08:53:27'),
	(16, 2, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 08:53:27', '2026-03-19 08:53:27'),
	(17, 2, 'user', 'Talk to human agent', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 08:53:30', '2026-03-19 08:53:30'),
	(18, 2, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 08:53:30', '2026-03-19 08:53:30'),
	(19, 3, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 08:53:43', '2026-03-19 08:53:43'),
	(20, 4, 'bot', 'Hello! How can I assist you today?', NULL, NULL, NULL, '2026-03-19 09:04:28', '2026-03-19 09:04:28'),
	(21, 4, 'user', 'Hello?', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:04:35', '2026-03-19 09:04:35'),
	(22, 4, 'bot', 'I apologize, but I don\'t understand. Please contact our support team for assistance.', NULL, NULL, NULL, '2026-03-19 09:04:35', '2026-03-19 09:04:35'),
	(23, 5, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 09:05:19', '2026-03-19 09:05:19'),
	(24, 6, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 09:05:41', '2026-03-19 09:05:41'),
	(25, 7, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 09:06:27', '2026-03-19 09:06:27'),
	(26, 7, 'user', 'Magkano?', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:07:38', '2026-03-19 09:07:38'),
	(27, 7, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 09:07:38', '2026-03-19 09:07:38'),
	(28, 7, 'user', 'Send me brochure', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:07:40', '2026-03-19 09:07:40'),
	(29, 7, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 09:07:40', '2026-03-19 09:07:40'),
	(30, 7, 'user', 'sorry', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:08:32', '2026-03-19 09:08:32'),
	(31, 7, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 09:08:32', '2026-03-19 09:08:32'),
	(32, 8, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 09:09:04', '2026-03-19 09:09:04'),
	(33, 8, 'user', 'Price', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:09:16', '2026-03-19 09:09:16'),
	(34, 8, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 09:09:16', '2026-03-19 09:09:16'),
	(35, 9, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Alexa, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 09:10:14', '2026-03-19 09:10:14'),
	(36, 9, 'user', 'lugsakdgsahgds', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:10:17', '2026-03-19 09:10:17'),
	(37, 9, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team? Please call us at (02) 8123-4567 or email support@capturedmoments.com', NULL, NULL, NULL, '2026-03-19 09:10:17', '2026-03-19 09:10:17'),
	(38, 9, 'user', 'magkano????', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 09:10:23', '2026-03-19 09:10:23'),
	(39, 9, 'bot', 'Our photography packages start at ₱15,000 for basic coverage and go up to ₱50,000 for premium packages. We offer:\n    - Basic Package: ₱15,000 – 2 hours coverage, 50 edited photos\n    - Standard Package: ₱25,000 – 4 hours coverage, 100 edited photos, online gallery\n    - Premium Package: ₱40,000 – 8 hours coverage, 200 edited photos, online gallery, print rights\nWould you like me to send you a detailed brochure with all our packages?', NULL, NULL, NULL, '2026-03-19 09:10:23', '2026-03-19 09:10:23'),
	(40, 10, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Soo-Bot, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 13:20:08', '2026-03-19 13:20:08'),
	(41, 10, 'user', 'hi', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:14', '2026-03-19 13:20:14'),
	(42, 10, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:20:14', '2026-03-19 13:20:14'),
	(43, 10, 'user', 'package', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:18', '2026-03-19 13:20:18'),
	(44, 10, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:20:18', '2026-03-19 13:20:18'),
	(45, 10, 'user', 'wedding', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:22', '2026-03-19 13:20:22'),
	(46, 10, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:20:22', '2026-03-19 13:20:22'),
	(47, 10, 'user', 'provide', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:38', '2026-03-19 13:20:38'),
	(48, 10, 'bot', 'We offer a comprehensive range of photography and videography services:\n\n📸 WEDDING PHOTOGRAPHY\n- Full day coverage (preparation to reception)\n- Pre-nuptial photo sessions\n- Same Day Edit (SDE) videos\n- Printed photo albums\n\n👶 MATERNITY & NEWBORN\n- Studio sessions with professional lighting\n- On-location outdoor shoots\n- Props and outfits included\n- Digital copies with printing rights\n\n🎉 EVENTS & PARTIES\n- Birthday celebrations\n- Debuts and anniversaries\n- Corporate events and conferences\n- Team building documentation\n\n👔 PORTRAITS & HEADSHOTS\n- Professional corporate headshots\n- Family portraits\n- Personal branding sessions\n- Model portfolios\n\n🏠 REAL ESTATE\n- Property photography for listings\n- Virtual tours\n- Drone aerial shots\n- 360-degree room views\n\n📹 VIDEOGRAPHY SERVICES\n- Event highlights (3-5 minutes)\n- Full ceremony coverage\n- Interview/documentary style\n- Social media ready clips\n\nWhich service are you interested in learning more about? I can provide detailed package information, pricing, and sample portfolios!', 2, NULL, NULL, '2026-03-19 13:20:38', '2026-03-19 13:20:38'),
	(49, 10, 'user', 'Events/Parties', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:46', '2026-03-19 13:20:46'),
	(50, 10, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:20:46', '2026-03-19 13:20:46'),
	(51, 10, 'user', 'services', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:20:51', '2026-03-19 13:20:51'),
	(52, 10, 'bot', 'We offer a comprehensive range of photography and videography services:\n\n📸 WEDDING PHOTOGRAPHY\n- Full day coverage (preparation to reception)\n- Pre-nuptial photo sessions\n- Same Day Edit (SDE) videos\n- Printed photo albums\n\n👶 MATERNITY & NEWBORN\n- Studio sessions with professional lighting\n- On-location outdoor shoots\n- Props and outfits included\n- Digital copies with printing rights\n\n🎉 EVENTS & PARTIES\n- Birthday celebrations\n- Debuts and anniversaries\n- Corporate events and conferences\n- Team building documentation\n\n👔 PORTRAITS & HEADSHOTS\n- Professional corporate headshots\n- Family portraits\n- Personal branding sessions\n- Model portfolios\n\n🏠 REAL ESTATE\n- Property photography for listings\n- Virtual tours\n- Drone aerial shots\n- 360-degree room views\n\n📹 VIDEOGRAPHY SERVICES\n- Event highlights (3-5 minutes)\n- Full ceremony coverage\n- Interview/documentary style\n- Social media ready clips\n\nWhich service are you interested in learning more about? I can provide detailed package information, pricing, and sample portfolios!', 2, NULL, NULL, '2026-03-19 13:20:51', '2026-03-19 13:20:51'),
	(53, 11, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Soo-Bot, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 13:21:02', '2026-03-19 13:21:02'),
	(54, 11, 'user', '"kasal', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:21:04', '2026-03-19 13:21:04'),
	(55, 11, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:21:04', '2026-03-19 13:21:04'),
	(56, 11, 'user', 'kasal', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:21:08', '2026-03-19 13:21:08'),
	(57, 11, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:21:09', '2026-03-19 13:21:09'),
	(58, 11, 'user', 'kasal rates', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:21:13', '2026-03-19 13:21:13'),
	(59, 11, 'bot', 'Our wedding photography packages are designed to capture every precious moment of your special day:\n\n💒 **ESSENTIAL WEDDING - ₱25,000**\n✓ 4 hours coverage (ceremony + reception basics)\n✓ 1 professional photographer\n✓ 100 edited high-resolution photos\n✓ Online private gallery for 3 months\n✓ Free pre-wedding consultation\n✓ 50 prints (4R size)\n✓ Soft copy on USB drive\n\n💍 **PREMIUM WEDDING - ₱40,000**\n✓ 8 hours full day coverage (preparation to reception)\n✓ 2 photographers (lead + assistant)\n✓ 200+ edited high-resolution photos\n✓ Online gallery for 6 months with download rights\n✓ Pre-nuptial photo session (2 hours, 1 location)\n✓ Same Day Edit (SDE) 3-5 minute video\n✓ 100 prints (assorted sizes)\n✓ Soft copy on custom USB box\n✓ Free album layout design\n\n👰 **LUXURY WEDDING - ₱65,000**\n✓ 10+ hours complete coverage\n✓ 3 photographers (team coverage)\n✓ 400+ edited premium photos\n✓ Lifetime online gallery with printing rights\n✓ Pre-nuptial video (2-3 minutes cinematic)\n✓ Full ceremony video (30-45 minutes)\n✓ Same Day Edit (SDE) video shown at reception\n✓ Premium 20-page photo album (10x10)\n✓ 150 premium prints with box\n✓ Drone aerial coverage (weather permitting)\n✓ 2nd shooter free for pre-nup\n✓ Engagement session inclusion\n\n📸 **A LA CARTE ADD-ONS:**\n• Additional hour: ₱3,000\n• Extra photographer: ₱5,000\n• Photo album upgrade: ₱8,000\n• Raw footage DVD: ₱4,000\n• Extended pre-nup session: ₱6,000\n\nAll packages include professional editing, online proofing gallery, and full printing rights to downloaded photos. We require 30% downpayment to reserve your date, with balance due on or before the event day.\n\nWould you like to schedule a free consultation to discuss your wedding vision?', 3, NULL, NULL, '2026-03-19 13:21:13', '2026-03-19 13:21:13'),
	(60, 11, 'user', 'same day edit price', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:21:56', '2026-03-19 13:21:56'),
	(61, 11, 'bot', 'Our wedding photography packages are designed to capture every precious moment of your special day:\n\n💒 **ESSENTIAL WEDDING - ₱25,000**\n✓ 4 hours coverage (ceremony + reception basics)\n✓ 1 professional photographer\n✓ 100 edited high-resolution photos\n✓ Online private gallery for 3 months\n✓ Free pre-wedding consultation\n✓ 50 prints (4R size)\n✓ Soft copy on USB drive\n\n💍 **PREMIUM WEDDING - ₱40,000**\n✓ 8 hours full day coverage (preparation to reception)\n✓ 2 photographers (lead + assistant)\n✓ 200+ edited high-resolution photos\n✓ Online gallery for 6 months with download rights\n✓ Pre-nuptial photo session (2 hours, 1 location)\n✓ Same Day Edit (SDE) 3-5 minute video\n✓ 100 prints (assorted sizes)\n✓ Soft copy on custom USB box\n✓ Free album layout design\n\n👰 **LUXURY WEDDING - ₱65,000**\n✓ 10+ hours complete coverage\n✓ 3 photographers (team coverage)\n✓ 400+ edited premium photos\n✓ Lifetime online gallery with printing rights\n✓ Pre-nuptial video (2-3 minutes cinematic)\n✓ Full ceremony video (30-45 minutes)\n✓ Same Day Edit (SDE) video shown at reception\n✓ Premium 20-page photo album (10x10)\n✓ 150 premium prints with box\n✓ Drone aerial coverage (weather permitting)\n✓ 2nd shooter free for pre-nup\n✓ Engagement session inclusion\n\n📸 **A LA CARTE ADD-ONS:**\n• Additional hour: ₱3,000\n• Extra photographer: ₱5,000\n• Photo album upgrade: ₱8,000\n• Raw footage DVD: ₱4,000\n• Extended pre-nup session: ₱6,000\n\nAll packages include professional editing, online proofing gallery, and full printing rights to downloaded photos. We require 30% downpayment to reserve your date, with balance due on or before the event day.\n\nWould you like to schedule a free consultation to discuss your wedding vision?', 3, NULL, NULL, '2026-03-19 13:21:56', '2026-03-19 13:21:56'),
	(62, 11, 'user', 'coverage', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:22:09', '2026-03-19 13:22:09'),
	(63, 11, 'bot', 'We offer a comprehensive range of photography and videography services:\n\n📸 WEDDING PHOTOGRAPHY\n- Full day coverage (preparation to reception)\n- Pre-nuptial photo sessions\n- Same Day Edit (SDE) videos\n- Printed photo albums\n\n👶 MATERNITY & NEWBORN\n- Studio sessions with professional lighting\n- On-location outdoor shoots\n- Props and outfits included\n- Digital copies with printing rights\n\n🎉 EVENTS & PARTIES\n- Birthday celebrations\n- Debuts and anniversaries\n- Corporate events and conferences\n- Team building documentation\n\n👔 PORTRAITS & HEADSHOTS\n- Professional corporate headshots\n- Family portraits\n- Personal branding sessions\n- Model portfolios\n\n🏠 REAL ESTATE\n- Property photography for listings\n- Virtual tours\n- Drone aerial shots\n- 360-degree room views\n\n📹 VIDEOGRAPHY SERVICES\n- Event highlights (3-5 minutes)\n- Full ceremony coverage\n- Interview/documentary style\n- Social media ready clips\n\nWhich service are you interested in learning more about? I can provide detailed package information, pricing, and sample portfolios!', 2, NULL, NULL, '2026-03-19 13:22:09', '2026-03-19 13:22:09'),
	(64, 11, 'user', 'Talk to Human', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:22:12', '2026-03-19 13:22:12'),
	(65, 11, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:22:12', '2026-03-19 13:22:12'),
	(66, 11, 'user', 'coverage', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:22:15', '2026-03-19 13:22:15'),
	(67, 11, 'bot', 'We offer a comprehensive range of photography and videography services:\n\n📸 WEDDING PHOTOGRAPHY\n- Full day coverage (preparation to reception)\n- Pre-nuptial photo sessions\n- Same Day Edit (SDE) videos\n- Printed photo albums\n\n👶 MATERNITY & NEWBORN\n- Studio sessions with professional lighting\n- On-location outdoor shoots\n- Props and outfits included\n- Digital copies with printing rights\n\n🎉 EVENTS & PARTIES\n- Birthday celebrations\n- Debuts and anniversaries\n- Corporate events and conferences\n- Team building documentation\n\n👔 PORTRAITS & HEADSHOTS\n- Professional corporate headshots\n- Family portraits\n- Personal branding sessions\n- Model portfolios\n\n🏠 REAL ESTATE\n- Property photography for listings\n- Virtual tours\n- Drone aerial shots\n- 360-degree room views\n\n📹 VIDEOGRAPHY SERVICES\n- Event highlights (3-5 minutes)\n- Full ceremony coverage\n- Interview/documentary style\n- Social media ready clips\n\nWhich service are you interested in learning more about? I can provide detailed package information, pricing, and sample portfolios!', 2, NULL, NULL, '2026-03-19 13:22:15', '2026-03-19 13:22:15'),
	(68, 11, 'user', 'Portraits/Headshots', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:22:48', '2026-03-19 13:22:48'),
	(69, 11, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:22:48', '2026-03-19 13:22:48'),
	(70, 12, 'bot', 'Hi there! 👋 Welcome to Captured Moments Photography Studio. I\'m Soo-Bot, your virtual assistant. How can I help you today? You can ask me about our photography packages, pricing, booking availability, or studio services.', NULL, NULL, NULL, '2026-03-19 13:35:43', '2026-03-19 13:35:43'),
	(71, 12, 'user', 'Hello', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:35:47', '2026-03-19 13:35:47'),
	(72, 12, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:35:47', '2026-03-19 13:35:47'),
	(73, 12, 'user', 'afas', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:35:52', '2026-03-19 13:35:52'),
	(74, 12, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:35:52', '2026-03-19 13:35:52'),
	(75, 12, 'user', 'asdds', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:35:53', '2026-03-19 13:35:53'),
	(76, 12, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:35:53', '2026-03-19 13:35:53'),
	(77, 12, 'user', 'as21\\das', NULL, NULL, '{"ip": "127.0.0.1", "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0"}', '2026-03-19 13:35:54', '2026-03-19 13:35:54'),
	(78, 12, 'bot', 'I apologize, but I don\'t have an answer for that question. Would you like me to connect you with our human support team?', NULL, NULL, NULL, '2026-03-19 13:35:54', '2026-03-19 13:35:54');

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

-- Dumping data for table platinum.tbl_chatbot_quick_replies: ~5 rows (approximately)
INSERT INTO `tbl_chatbot_quick_replies` (`id`, `intent_id`, `reply_text`, `action_value`, `action_type`, `position`, `is_active`, `created_at`, `updated_at`) VALUES
	(3, 2, 'Wedding Packages', 'wedding_packages', 'trigger_intent', 0, 1, '2026-03-19 13:14:56', '2026-03-19 13:14:56'),
	(4, 2, 'Events/Parties', 'event_packages', 'trigger_intent', 1, 1, '2026-03-19 13:14:56', '2026-03-19 13:14:56'),
	(5, 2, 'Portraits/Headshots', 'real_estate_packages', 'trigger_intent', 2, 1, '2026-03-19 13:14:56', '2026-03-19 13:14:56'),
	(6, 2, 'View Portfolio', 'https://capturedmoments.com/portfolio', 'open_url', 3, 1, '2026-03-19 13:14:56', '2026-03-19 13:14:56'),
	(7, 2, 'Talk to Human', 'human_support', 'trigger_intent', 4, 1, '2026-03-19 13:14:56', '2026-03-19 13:14:56');

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

-- Dumping data for table platinum.tbl_client_budget: ~5 rows (approximately)
INSERT INTO `tbl_client_budget` (`id`, `client_id`, `budget_name`, `description`, `minimum_budget`, `maximum_budget`, `preferred_budget`, `category_id`, `budget_type`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
	(3, 54, 'My Daughters Budget', 'Lorem ipsum dolor sit amet consectetur adipiscing elit. Quisque faucibus ex sapien vitae pellentesque sem placerat. In id cursus mi pretium tellus duis convallis. Tempus leo eu aenean sed diam urna tempor. Pulvinar vivamus fringilla lacus nec metus bibendum egestas. Iaculis massa nisl malesuada lacinia integer nunc posuere. Ut hendrerit semper vel class aptent taciti sociosqu. Ad litora torquent per conubia nostra inceptos himenaeos.', 6000.00, 125000.00, 50000.00, 2, 'package', 'active', '2026-03-11 05:13:52', '2026-03-11 05:13:52', NULL),
	(4, 54, 'Test', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 10000.00, 80000.00, 50000.00, 7, 'package', 'active', '2026-03-11 07:23:45', '2026-03-11 07:23:45', NULL),
	(5, 53, 'Budget ko para sa kasal ng anak ko', 'Kayo na bahala mag lagay since optional naman siya', 10000.00, 30000.00, 15000.00, 1, 'package', 'active', '2026-03-16 14:03:09', '2026-03-16 14:08:07', '2026-03-16 14:08:07'),
	(6, 53, 'Docs Budget', 'Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries for previewing layouts and visual mockups.', 10000.00, 30000.00, 15000.00, 7, 'package', 'active', '2026-03-16 14:05:02', '2026-03-16 14:05:02', NULL),
	(7, 53, 'Eveeeeeent Budjet', 'asdasdsadasdas', 1500.00, 3000.00, 5000.00, 2, 'package', 'active', '2026-03-16 14:07:58', '2026-03-16 14:13:45', NULL);

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

-- Dumping data for table platinum.tbl_employee_attendance: ~1 rows (approximately)
INSERT INTO `tbl_employee_attendance` (`id`, `user_id`, `studio_id`, `schedule_id`, `attendance_date`, `scheduled_start_time`, `scheduled_end_time`, `check_in_time`, `check_out_time`, `check_in_image`, `check_out_image`, `check_in_status`, `check_out_status`, `late_minutes`, `undertime_minutes`, `check_in_ip`, `check_out_ip`, `check_in_user_agent`, `check_out_user_agent`, `notes`, `created_at`, `updated_at`) VALUES
	(3, 135, 1, 23, '2026-03-25', '08:00:00', '17:00:00', '2026-03-25 14:30:54', '2026-03-25 14:31:25', 'employee-attendance/2026/03/25/27sznWJe3TOKjbZuv5vsWAbCGcH7Ec6BtAMiUdmi.jpg', 'employee-attendance/2026/03/25/bXUbH0PtKAUPhqLUzWz9WyZvbFG8HgyAkpVJgtWb.jpg', 'LATE', 'UNDERTIME', 390, 149, '127.0.0.1', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-25 06:30:55', '2026-03-25 06:31:25');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table platinum.tbl_employee_payroll: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_freelancers: ~1 rows (approximately)
INSERT INTO `tbl_freelancers` (`id`, `user_id`, `location_id`, `brand_name`, `tagline`, `bio`, `years_experience`, `brand_logo`, `street`, `barangay`, `service_area`, `starting_price`, `deposit_policy`, `deposit_type`, `deposit_amount`, `portfolio_works`, `facebook_url`, `instagram_url`, `website_url`, `valid_id`, `created_at`, `updated_at`) VALUES
	(2, 98, 2, 'MacroGraphy', 'Capturing Authenticity, One Frame at a Time', 'I\'m Marco Villanueva, a passionate documentary and street photographer based in Manila. With over 8 years of experience, I specialize in capturing candid, authentic moments that tell compelling visual stories. My work has been featured in local and international publications, and I\'ve collaborated with NGOs, brands, and individuals who value honest, emotion-driven photography. I believe every moment has a story worth telling, and I\'m here to help you preserve yours through timeless imagery. Whether it\'s documenting your family\'s milestones, covering your events, or creating content for your brand, I bring a journalistic eye and artistic sensibility to every project.', 8, 'brand-logos/ukfI6mzPr1jZd5wXSIOBvtJHj0lHbUTxeCJHoMf3.png', 'Unit 12B, Urban Residences, 789 Roxas Boulevard', 'Tejero', 'Within my city only', 3500.00, 'not_required', 'fixed', 0.00, '"[\\"portfolio-works\\\\\\/TGMaEw723DQdWLQ9VPvpxSoIITN8D7lGgi7lelfN.png\\",\\"portfolio-works\\\\\\/aEzmtcMRcqHcbYXuzlsGEzLxAWYQztxFx3dGVpxT.png\\",\\"portfolio-works\\\\\\/HKE7UC9Egp8R92ACucSOvlu6BdTa51Teu03kVAHZ.png\\",\\"portfolio-works\\\\\\/p3FmAZhXAAssCXdMF71UgmXkbMjNsgS9qDsMMwGy.png\\",\\"portfolio-works\\\\\\/XjdAJWmce7Df1nQBjW21Tn8MpoK94FkTU09iWTGu.png\\",\\"portfolio-works\\\\\\/kXcPqTSpzKaPfYlHq9TdGOGr45MS2GzTOeH2NPkZ.png\\"]"', 'https://www.facebook.com/marcovphotography', 'https://www.instagram.com/marco.v.photo', 'https://www.marcovphotography.com/', 'valid-ids/DKbpiUIVbhwZdBvsfUMZ2gcMG89Jo2SbrgOnT1DD.jpg', '2026-02-26 23:26:55', '2026-02-26 23:26:55');

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

-- Dumping data for table platinum.tbl_freelancer_online_gallery: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_freelancer_packages: ~3 rows (approximately)
INSERT INTO `tbl_freelancer_packages` (`id`, `user_id`, `category_id`, `package_name`, `package_description`, `package_inclusions`, `allow_time_customization`, `duration`, `maximum_edited_photos`, `coverage_scope`, `package_price`, `online_gallery`, `allow_multiple_locations`, `max_locations`, `status`, `created_at`, `updated_at`) VALUES
	(1, 98, 2, 'Professional Solo Essentials', 'A quick and efficient session designed for high-quality portraits. Ideal for LinkedIn profiles, graduation photos, or creative social media content.', '["Studio or Outdoor Session","1 Photographer","Basic Color Grading","High-Resolution Digital Downloads"]', 0, 3, 15, 'Event Place', 3500.00, 1, 0, 1, 'active', '2026-02-27 18:28:24', '2026-02-27 18:28:24'),
	(2, 98, 2, 'Intimate Celebration Package', 'Comprehensive coverage for your family’s most precious milestones. We focus on candid moments and the little details that make your event special.', '["Continuous Coverage","1 Lead Photographer","Professional Photo Enhancement","Print-Ready Digital Files","USB Flash Drive Delivery"]', 1, NULL, 100, 'Birthday Party / Baptism / Small Event', 8500.00, 1, 0, 1, 'active', '2026-02-27 18:29:20', '2026-02-27 18:29:20'),
	(3, 98, 2, 'Premium Brand & Product Suite', 'Elevate your business with professional imagery. This package covers team headshots, interior shots of your space, and high-end product photography.', '["5 Hours On-site Coverage","Full Lighting Equipment Setup","Advanced Retouching & Compositing","Commercial Usage Rights","Fast 72-Hour Turnaround"]', 1, NULL, 50, 'Business Interior, Staff, and Product Catalog', 25000.00, 1, 1, 3, 'active', '2026-02-27 18:30:02', '2026-02-27 18:30:02');

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

-- Dumping data for table platinum.tbl_freelancer_plans: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_freelancer_ratings: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_freelancer_schedules: ~2 rows (approximately)
INSERT INTO `tbl_freelancer_schedules` (`id`, `user_id`, `operating_days`, `start_time`, `end_time`, `booking_limit`, `advance_booking`, `created_at`, `updated_at`) VALUES
	(1, 98, '"[\\"monday\\",\\"wednesday\\",\\"friday\\",\\"sunday\\"]"', '08:00:00', '17:00:00', 2, 5, '2026-02-26 21:54:28', '2026-02-26 21:54:28'),
	(3, 98, '"[\\"monday\\",\\"wednesday\\",\\"friday\\",\\"sunday\\"]"', '08:00:00', '18:00:00', 2, 5, '2026-02-26 23:26:55', '2026-02-26 23:26:55');

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

-- Dumping data for table platinum.tbl_freelancer_services: ~0 rows (approximately)

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table platinum.tbl_generated_payrolls: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_locations: ~8 rows (approximately)
INSERT INTO `tbl_locations` (`id`, `province`, `municipality`, `barangay`, `zip_code`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Cavite', 'Dasmariñas', '"[\\"Burol\\",\\"Burol I\\",\\"Burol II\\",\\"Burol III\\",\\"Fatima I\\",\\"Fatima II\\",\\"Fatima III\\",\\"Langkaan I\\",\\"Langkaan II\\",\\"Paliparan I\\",\\"Paliparan II\\",\\"Paliparan III\\",\\"Salitran I\\",\\"Salitran II\\",\\"Salitran III\\",\\"Salitran IV\\",\\"Zone I\\",\\"Zone I-B\\",\\"Zone II\\",\\"Zone III\\",\\"Zone IV\\"]"', '4115', 'active', '2026-01-27 01:04:27', '2026-01-27 01:04:27'),
	(2, 'Cavite', 'General Trias', '"[\\"Alingaro\\",\\"Arnaldo (Barangay 7)\\",\\"Bacao I\\",\\"Bacao II\\",\\"Bagumbayan (Barangay 5)\\",\\"Biclatan\\",\\"Buenavista I\\",\\"Buenavista II\\",\\"Buenavista III\\",\\"Corregidor (Barangay 10)\\",\\"Dulong Bayan (Barangay 3)\\",\\"Gov. Ferrer (Barangay 1)\\",\\"Javalera\\",\\"Manggahan\\",\\"Navarro\\",\\"Ninety Sixth (Barangay 8)\\",\\"Panungyanan\\",\\"Pasong Camachile I\\",\\"Pasong Camachile II\\",\\"Pasong Kawayan I\\",\\"Pasong Kawayan II\\",\\"Pinagtipunan\\",\\"Prinza (Barangay 9)\\",\\"Sampalucan (Barangay 2)\\",\\"San Francisco\\",\\"San Gabriel (Barangay 4)\\",\\"San Juan I\\",\\"San Juan II\\",\\"Santa Clara\\",\\"Santiago\\",\\"Tapia\\",\\"Tejero\\",\\"Vibora (Barangay 6)\\"]"', '4107', 'active', '2026-01-27 01:42:45', '2026-01-27 01:42:45'),
	(6, 'Cavite', 'Imus', '"[\\"Alapan I-A\\",\\"Alapan I-B\\",\\"Alapan I-C\\",\\"Alapan II-A\\",\\"Alapan II-B\\",\\"Anabu I-A\\",\\"Anabu I-B\\",\\"Anabu I-C\\",\\"Anabu I-D\\",\\"Anabu I-E\\",\\"Anabu I-F\\",\\"Anabu I-G\\",\\"Anabu II-A\\",\\"Anabu II-B\\",\\"Anabu II-C\\",\\"Anabu II-D\\",\\"Anabu II-E\\",\\"Anabu II-F\\",\\"Bagong Silang (Bahayang Pag-Asa)\\",\\"Bayan Luma I\\",\\"Bayan Luma II\\",\\"Bayan Luma III\\",\\"Bayan Luma IV\\",\\"Bayan Luma V\\",\\"Bayan Luma VI\\",\\"Bayan Luma VII\\",\\"Bayan Luma VIII\\",\\"Bayan Luma IX\\",\\"Bucandala I\\",\\"Bucandala II\\",\\"Bucandala III\\",\\"Bucandala IV\\",\\"Bucandala V\\",\\"Buhay na Tubig\\",\\"Carsadang Bago I\\",\\"Carsadang Bago II\\",\\"Magdalo\\",\\"Maharlika\\",\\"Malagasang I-A\\",\\"Malagasang I-B\\",\\"Malagasang I-C\\",\\"Malagasang I-D\\",\\"Malagasang I-E\\",\\"Malagasang I-F\\",\\"Malagasang I-G\\",\\"Malagasang II-A\\",\\"Malagasang II-B\\",\\"Malagasang II-C\\",\\"Malagasang II-D\\",\\"Malagasang II-E\\",\\"Malagasang II-F\\",\\"Malagasang II-G\\",\\"Mariano Espeleta I\\",\\"Mariano Espeleta II\\",\\"Mariano Espeleta III\\",\\"Medicion I-A\\",\\"Medicion I-B\\",\\"Medicion I-C\\",\\"Pag-Asa I\\",\\"Pag-Asa II\\",\\"Pag-Asa III\\",\\"Palico I\\",\\"Palico II\\",\\"Palico III\\",\\"Poblacion I-A\\",\\"Poblacion I-B\\",\\"Poblacion I-C\\",\\"Tanzang Luma I\\",\\"Tanzang Luma II\\",\\"Tanzang Luma III\\",\\"Toclong I-A\\",\\"Toclong I-B\\",\\"Toclong I-C\\"]"', '4103', 'active', '2026-01-28 06:05:50', '2026-01-28 06:05:50'),
	(7, 'Cavite', 'Silang', '"[\\"Adlas\\",\\"Balite I\\",\\"Balite II\\",\\"Balubad\\",\\"Batas\\",\\"Biga I\\",\\"Biluso\\",\\"Buho\\",\\"Bucal\\",\\"Bulihan\\",\\"Cabangaan\\",\\"Carmen\\",\\"Hukay\\",\\"Iba\\",\\"Inchican\\",\\"Kalubkob\\",\\"Kaong\\",\\"Lalaan I\\",\\"Lalaan II\\",\\"Litlit\\",\\"Lucsuhin\\",\\"Lumil\\",\\"Maguyam\\",\\"Malabag\\",\\"Mataas Na Burol\\",\\"Munting Ilog\\",\\"Paligawan\\",\\"Pasong Langka\\",\\"Pook I\\",\\"Pulong Bunga\\",\\"Pulong Saging\\",\\"Puting Kahoy\\",\\"Sabutan\\"]"', '4118', 'active', '2026-01-28 06:11:32', '2026-01-28 06:11:32'),
	(18, 'Cavite', 'Carmona', '"[\\"Barangay 1 (Poblacion) - San Pablo\\",\\"Barangay 2 (Poblacion) - San Jose\\",\\"Barangay 3 (Poblacion) - San Jose\\",\\"Barangay 4 (Poblacion) - J.M. Loyola\\",\\"Barangay 5 (Poblacion) - J.M. Loyola\\",\\"Barangay 6 (Poblacion) - Magallanes\\",\\"Barangay 7 (Poblacion) - Magallanes\\",\\"Barangay 8 (Poblacion) - Rosario\\",\\"Bancal\\",\\"Cabilang Baybay\\",\\"Lantic\\",\\"Mabuhay\\",\\"Maduya\\",\\"Milagrosa\\"]"', '4116', 'active', '2026-02-01 15:38:41', '2026-02-01 15:38:41'),
	(19, 'Cavite', 'General Mariano Alvarez', '"[\\"Aldiano Olaes\\",\\"Barangay 1 Poblacion\\",\\"Barangay 2 Poblacion\\",\\"Barangay 3 Poblacion\\",\\"Barangay 4 Poblacion\\",\\"Barangay 5 Poblacion\\",\\"Benjamin Tirona\\",\\"Bernardo Pulido\\",\\"Epifanio Malia\\",\\"Fiorello Calimag\\",\\"Francisco de Castro\\",\\"Francisco Reyes\\",\\"Gavino Maderan\\",\\"Gregoria de Jesus\\",\\"Inocencio Salud\\",\\"Jacinto Lumbreras\\",\\"Kapitan Kua\\",\\"Koronel Jose P. Elises\\",\\"Macario Dacon\\",\\"Marcelino Memije\\",\\"Nicolasa Virata\\",\\"Pantaleon Granados\\",\\"Ramon Cruz\\",\\"San Gabriel\\",\\"San Jose\\",\\"Severino de Las Alas\\",\\"Tiniente Tiago\\"]"', '4117', 'active', '2026-02-01 15:41:13', '2026-02-01 15:41:13'),
	(20, 'Cavite', 'Indang', '"[\\"Agus-us\\",\\"Alulod\\",\\"Banaba Cerca\\",\\"Banaba Lejos\\",\\"Bancod\\",\\"Barangay 1\\",\\"Barangay 2\\",\\"Barangay 3\\",\\"Barangay 4\\",\\"Buna Cerca\\",\\"Buna Lejos I\\",\\"Buna Lejos II\\",\\"Calumpang Cerca\\",\\"Calumpang Lejos I\\",\\"Carasuchi\\",\\"Daine I\\",\\"Daine II\\",\\"Guyam Malaki\\",\\"Guyam Munti\\",\\"Harasan\\",\\"Kayquit I\\",\\"Kayquit II\\",\\"Kayquit III\\",\\"Kaytambog\\",\\"Kaytapos\\",\\"Limbon\\",\\"Lumampong Balagbag\\",\\"Lumampong Halayhay\\",\\"Mahabangkahoy Cerca\\",\\"Mahabangkahoy Lejos\\",\\"Mataas na Lupa\\",\\"Pulo\\",\\"Tambo Balagbag\\",\\"Tambo Ilaya\\",\\"Tambo Kulit\\",\\"Tambo Malaki\\"]"', '4122', 'active', '2026-02-01 15:43:34', '2026-02-01 15:43:34'),
	(21, 'Cavite', 'Kawit', '"[\\"Balsahan-Bisita\\",\\"Batong Dalig\\",\\"Binakayan-Aplaya\\",\\"Binakayan-Kanluran\\",\\"Congbalay-Legaspi\\",\\"Gahak\\",\\"Kaingen\\",\\"Magdalo\\",\\"Manggahan-Lawin\\",\\"Marulas\\",\\"Panamitan\\",\\"Poblacion\\",\\"Pulvorista\\",\\"Samala-Marquez\\",\\"San Sebastian\\",\\"Santa Isabel\\",\\"Tabon I\\",\\"Tabon II\\",\\"Tabon III\\",\\"Toclong\\",\\"Tramo-Bantayan\\",\\"Wakas I\\",\\"Wakas II\\"]"', '4104', 'active', '2026-02-01 15:46:00', '2026-02-01 15:46:00');

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

-- Dumping data for table platinum.tbl_notifications: ~10 rows (approximately)
INSERT INTO `tbl_notifications` (`id`, `uuid`, `user_id`, `type`, `title`, `message`, `data`, `icon`, `color`, `read_at`, `created_at`, `updated_at`) VALUES
	(1, '88f637ad-33e0-4dae-a2fb-fd334b4e2bd1', 96, 'studio_approved', 'Studio Registration Approved', 'Your studio \'Prism & Pine Creative Spaces\' has been approved and is now verified.', '"{\\"studio_id\\":1,\\"studio_name\\":\\"Prism & Pine Creative Spaces\\",\\"route\\":\\"\\\\/owner\\\\/view\\\\/studio\\"}"', 'check-circle', 'success', '2026-02-25 22:48:48', '2026-02-24 21:00:22', '2026-02-25 22:48:48'),
	(2, '4a6cd883-a661-4f48-954d-4faedc4908ea', 97, 'studio_approved', 'Studio Registration Approved', 'Your studio \'Lumière & Co. Photography Studio\' has been approved and is now verified.', '"{\\"studio_id\\":3,\\"studio_name\\":\\"Lumi\\\\u00e8re & Co. Photography Studio\\",\\"route\\":\\"\\\\/owner\\\\/view\\\\/studio\\"}"', 'check-circle', 'success', '2026-02-26 21:07:11', '2026-02-26 21:06:33', '2026-02-26 21:07:11'),
	(3, '6aa92c53-b64b-4eb3-a872-1b8993102d3c', 69, 'photographer_on_site', 'Photographer On-Site Confirmation', 'Photographer Rowan Ware has arrived on-site. Please confirm their presence.', '"{\\"booking_id\\":5,\\"booking_reference\\":\\"BK-69B894D8C41C9\\",\\"assignment_id\\":6,\\"photographer_name\\":\\"Rowan Ware\\",\\"route\\":\\"\\\\/client\\\\/view\\\\/my-bookings\\"}"', 'map-pin', 'info', NULL, '2026-03-18 12:46:40', '2026-03-18 12:46:40'),
	(4, '2631265f-7ac2-41d6-9e9a-5896c7f0f0b8', 69, 'photographer_on_site', 'Photographer On-Site Confirmation', 'Photographer Rowan Ware has arrived on-site. Please confirm their presence.', '"{\\"booking_id\\":5,\\"booking_reference\\":\\"BK-69B894D8C41C9\\",\\"assignment_id\\":6,\\"photographer_name\\":\\"Rowan Ware\\",\\"route\\":\\"\\\\/client\\\\/view\\\\/my-bookings\\"}"', 'map-pin', 'info', NULL, '2026-03-18 12:46:48', '2026-03-18 12:46:48'),
	(5, '9a3d8c14-4417-464d-afc5-5ea9db5e5787', 69, 'photographer_on_site', 'Photographer On-Site Confirmation', 'Photographer Rowan Ware has arrived on-site. Please confirm their presence.', '"{\\"booking_id\\":5,\\"booking_reference\\":\\"BK-69B894D8C41C9\\",\\"assignment_id\\":6,\\"photographer_name\\":\\"Rowan Ware\\",\\"route\\":\\"\\\\/client\\\\/view\\\\/my-bookings\\"}"', 'map-pin', 'info', NULL, '2026-03-18 12:47:29', '2026-03-18 12:47:29'),
	(6, '630e38a3-2c16-4474-92e2-809a088773f0', 69, 'photographer_on_site', 'Photographer On-Site Confirmation', 'Photographer Rowan Ware has arrived on-site. Please confirm their presence.', '"{\\"booking_id\\":5,\\"booking_reference\\":\\"BK-69B894D8C41C9\\",\\"assignment_id\\":6,\\"photographer_name\\":\\"Rowan Ware\\",\\"route\\":\\"\\\\/client\\\\/view\\\\/my-bookings\\"}"', 'map-pin', 'info', NULL, '2026-03-18 12:54:24', '2026-03-18 12:54:24'),
	(8, '5cca66aa-e06b-4c98-9fde-828036b40deb', 69, 'photographer_on_site', 'Photographer On-Site Confirmation', 'Photographer Rowan Ware has arrived on-site. Please confirm their presence.', '"{\\"booking_id\\":5,\\"booking_reference\\":\\"BK-69B894D8C41C9\\",\\"assignment_id\\":6,\\"photographer_name\\":\\"Rowan Ware\\",\\"route\\":\\"\\\\/client\\\\/view\\\\/my-bookings\\"}"', 'map-pin', 'info', NULL, '2026-03-18 13:19:44', '2026-03-18 13:19:44'),
	(10, '2569d298-efee-453f-8c73-3a473952f8af', 96, 'new_booking', 'New Booking Received!', 'A new booking has been made by Mark Thomas for Standard Celebration Package on March 27, 2026.', '"{\\"booking_id\\":6,\\"booking_reference\\":\\"BK-69BB81F03ACE6\\",\\"client_name\\":\\"Mark Thomas\\",\\"client_id\\":82,\\"event_date\\":\\"2026-03-27\\",\\"event_time\\":\\"10:00 - 17:00\\",\\"package_name\\":\\"Standard Celebration Package\\",\\"package_price\\":\\"12000.00\\",\\"down_payment\\":8040,\\"total_amount\\":\\"12000.00\\",\\"location_type\\":\\"on-location\\",\\"category_id\\":\\"2\\",\\"route\\":\\"\\\\/owner\\\\/view\\\\/bookings\\",\\"city\\":\\"Carmona\\",\\"barangay\\":\\"Cabilang Baybay\\",\\"venue_name\\":\\"Venue Name\\"}"', 'calendar-check', 'primary', '2026-03-19 04:57:11', '2026-03-19 04:56:19', '2026-03-19 04:57:11'),
	(15, '9b25a800-b2bd-4b7a-8ed7-b4a6cd5a5e38', 96, 'photographer_assigned', 'Photographer Assignment Confirmed', 'All 2 required photographers have been successfully assigned to booking BK-69BB81F03ACE6.', '"{\\"booking_id\\":6,\\"booking_reference\\":\\"BK-69BB81F03ACE6\\",\\"studio_id\\":1,\\"studio_name\\":\\"Prism & Pine Creative Spaces\\",\\"client_name\\":\\"Mark Thomas\\",\\"event_date\\":\\"2026-03-26T16:00:00.000000Z\\",\\"formatted_date\\":\\"March 27, 2026\\",\\"event_time\\":\\"10:00:00 - 17:00:00\\",\\"package_name\\":\\"Standard Celebration Package\\",\\"assigned_photographers\\":[\\"Rowan Ware\\",\\"Karina Church\\"],\\"assigned_photographer_ids\\":[\\"128\\",\\"132\\"],\\"assigned_count\\":2,\\"assignment_notes\\":null,\\"route\\":\\"\\\\/owner\\\\/bookings\\\\/6\\\\/details\\",\\"is_batch_assignment\\":true,\\"notification_type\\":\\"owner_notification\\",\\"current_assigned_count\\":2,\\"required_photographers\\":2,\\"is_complete\\":true}"', 'check-circle', 'success', '2026-03-19 05:34:21', '2026-03-19 05:30:49', '2026-03-19 05:34:21');

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

-- Dumping data for table platinum.tbl_packages: ~6 rows (approximately)
INSERT INTO `tbl_packages` (`id`, `studio_id`, `category_id`, `package_name`, `package_description`, `package_inclusions`, `duration`, `maximum_edited_photos`, `coverage_scope`, `package_location`, `allow_multiple_locations`, `max_locations`, `allow_time_customization`, `package_price`, `online_gallery`, `photographer_count`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 2, 'Essential Event Coverage', 'High-quality professional coverage for intimate gatherings, ensuring your key moments are preserved without the fluff.', '["2 Hours of continuous professional photography.","High-resolution digital images delivered via Online Gallery.","Basic color correction and image enhancement."]', 2, 50, '"TEST COVERAGE SCOPE LOCATIONS"', '["In-Studio"]', 0, 0, 0, 5000.00, 1, 1, 'active', '2026-03-03 01:21:49', '2026-03-03 01:21:49'),
	(2, 1, 2, 'Standard Celebration Package', 'Comprehensive coverage for medium-sized events, providing more variety and a faster turnaround for social media sharing.', '["4 Hours of continuous professional photography.","Full digital library with professional color grading.","10 \\"Sneak Peek\\" photos delivered within 24 hours.","Personalized Online Gallery with 3-month hosting.","On-site backup equipment to ensure zero data loss."]', NULL, 100, '"TEST COVERAGE SCOPE LOCATIONS"', '["On-Location"]', 0, 0, 1, 12000.00, 1, 2, 'active', '2026-03-03 01:23:26', '2026-03-03 01:23:26'),
	(3, 1, 2, 'The Grand Premiere', 'The ultimate storytelling package featuring multi-angle coverage and physical keepsakes to commemorate your milestone.', '["8 Hours of full-day event coverage.","Lead Photographer + 1 Assistant\\/Second Shooter.","Custom-themed Online Gallery with high-speed download.","1x Premium 8x10 Hardbound Photo Album (20 pages).","50 High-end retouched \\"Signature\\" portraits.","Same-day slideshow highlight (for display during the event).","Perpetual cloud storage for digital files.","Print release for all high-resolution images."]', NULL, 250, '"TEST COVERAGE SCOPE LOCATIONS"', '["On-Location", "In-Studio"]', 1, 4, 1, 35000.00, 1, 3, 'active', '2026-03-03 01:24:40', '2026-03-03 01:24:40'),
	(4, 1, 7, 'The Premium', 'The ultimate storytelling package featuring multi-angle coverage and physical keepsakes to commemorate your milestone.', '["8 Hours of full-day event coverage.","Lead Photographer + 1 Assistant\\/Second Shooter.","Custom-themed Online Gallery with high-speed download.","1x Premium 8x10 Hardbound Photo Album (20 pages).","50 High-end retouched \\"Signature\\" portraits.","Same-day slideshow highlight (for display during the event).","Perpetual cloud storage for digital files.","Print release for all high-resolution images."]', NULL, 250, '"TEST COVERAGE SCOPE LOCATIONS"', '["On-Location", "In-Studio"]', 1, 4, 1, 35000.00, 1, 3, 'active', '2026-03-03 01:24:40', '2026-03-03 01:24:40'),
	(5, 1, 7, 'Standard Package', 'Comprehensive coverage for medium-sized events, providing more variety and a faster turnaround for social media sharing.', '["4 Hours of continuous professional photography.","Full digital library with professional color grading.","10 \\"Sneak Peek\\" photos delivered within 24 hours.","Personalized Online Gallery with 3-month hosting.","On-site backup equipment to ensure zero data loss."]', NULL, 100, '"TEST COVERAGE SCOPE LOCATIONS"', '["On-Location"]', 0, 0, 1, 12000.00, 1, 2, 'active', '2026-03-03 01:23:26', '2026-03-03 01:23:26'),
	(6, 1, 7, 'Essential Coverage', 'High-quality professional coverage for intimate gatherings, ensuring your key moments are preserved without the fluff.', '["2 Hours of continuous professional photography.","High-resolution digital images delivered via Online Gallery.","Basic color correction and image enhancement."]', 2, 50, '"TEST COVERAGE SCOPE LOCATIONS"', '["In-Studio"]', 0, 0, 0, 5000.00, 1, 1, 'active', '2026-03-03 01:21:49', '2026-03-03 01:21:49');

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

-- Dumping data for table platinum.tbl_payments: ~6 rows (approximately)
INSERT INTO `tbl_payments` (`id`, `booking_id`, `payment_reference`, `stripe_payment_intent_id`, `stripe_session_id`, `amount`, `payment_method`, `status`, `payment_details`, `paid_at`, `created_at`, `updated_at`) VALUES
	(1, 1, 'PAY-69A6ABD9530D4', NULL, 'cs_test_a1vnEAIV6eppajDQ5f1Cvdd2BgRty09JoLztIzzPSTc13a7SJXHVTdbjEd', 7200.00, 'card', 'succeeded', '{"mode": "test", "amount": "7200.00", "created_at": "2026-03-03 09:37:32", "session_id": "cs_test_a1vnEAIV6eppajDQ5f1Cvdd2BgRty09JoLztIzzPSTc13a7SJXHVTdbjEd", "verified_at": "2026-03-03 09:37:49", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1vnEAIV6eppajDQ5f1Cvdd2BgRty09JoLztIzzPSTc13a7SJXHVTdbjEd#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": false, "checkout_session_created": true}', '2026-03-03 01:37:49', '2026-03-03 01:37:29', '2026-03-03 01:37:49'),
	(2, 2, 'PAY-69A6D4C71C421', NULL, 'cs_test_a1lWIinYvQUHNpfcUXKnZnwToQbL24lY6croPl5pc8ceb2hqfaeyc5LRHt', 5750.00, 'card', 'succeeded', '{"mode": "test", "amount": "5750.00", "created_at": "2026-03-03 12:32:31", "session_id": "cs_test_a1lWIinYvQUHNpfcUXKnZnwToQbL24lY6croPl5pc8ceb2hqfaeyc5LRHt", "verified_at": "2026-03-03 12:32:53", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1lWIinYvQUHNpfcUXKnZnwToQbL24lY6croPl5pc8ceb2hqfaeyc5LRHt#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": false, "checkout_session_created": true}', '2026-03-03 04:32:53', '2026-03-03 04:32:07', '2026-03-03 04:32:53'),
	(3, 3, 'PAY-69A6DE2EEAB90', NULL, 'cs_test_a1VgQi9UkxVkT9prKgBIUBN93HOfLaSaWafUk1ky3ymMFBlW13tvRpEZft', 8500.00, 'card', 'succeeded', '{"mode": "test", "amount": "8500.00", "created_at": "2026-03-03 13:12:28", "session_id": "cs_test_a1VgQi9UkxVkT9prKgBIUBN93HOfLaSaWafUk1ky3ymMFBlW13tvRpEZft", "verified_at": "2026-03-03 13:12:47", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1VgQi9UkxVkT9prKgBIUBN93HOfLaSaWafUk1ky3ymMFBlW13tvRpEZft#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": false, "checkout_session_created": true}', '2026-03-03 05:12:47', '2026-03-03 05:12:14', '2026-03-03 05:12:47'),
	(5, 5, 'PAY-69B894D8E1EB8', NULL, 'cs_test_a1kbSOn1EsMtYS423RKabrSCK7Kmcxv06eOU7NUggF54fURxQSP2rR1bDv', 3350.00, 'card', 'succeeded', '{"mode": "test", "amount": "3350.00", "created_at": "2026-03-17 07:40:27", "session_id": "cs_test_a1kbSOn1EsMtYS423RKabrSCK7Kmcxv06eOU7NUggF54fURxQSP2rR1bDv", "verified_at": "2026-03-17 07:40:56", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1kbSOn1EsMtYS423RKabrSCK7Kmcxv06eOU7NUggF54fURxQSP2rR1bDv#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": false, "checkout_session_created": true}', '2026-03-16 23:40:56', '2026-03-16 23:40:08', '2026-03-16 23:40:56'),
	(6, 5, 'PAY-69BB7BF0DF024', NULL, 'cs_test_a1TUDjRey5tGyS0TyeKcmvq9Ojm8Ioe5g8DyeFP9wEWG2RBtAuZ4U6HjAF', 1650.00, 'card', 'succeeded', '{"mode": "test", "amount": "1650.00", "created_at": "2026-03-19 12:30:58", "session_id": "cs_test_a1TUDjRey5tGyS0TyeKcmvq9Ojm8Ioe5g8DyeFP9wEWG2RBtAuZ4U6HjAF", "verified_at": "2026-03-19 12:31:14", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a1TUDjRey5tGyS0TyeKcmvq9Ojm8Ioe5g8DyeFP9wEWG2RBtAuZ4U6HjAF#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": true, "checkout_session_created": true}', '2026-03-19 04:31:14', '2026-03-19 04:30:40', '2026-03-19 04:31:14'),
	(7, 6, 'PAY-69BB81F03FA1F', NULL, 'cs_test_a18ti7qh7TlrB7mwBeKGChUARumSm7CzAbCK3ngNqzrcyMjN8K6b7n6gvD', 8040.00, 'card', 'succeeded', '{"mode": "test", "amount": "8040.00", "created_at": "2026-03-19 12:56:27", "session_id": "cs_test_a18ti7qh7TlrB7mwBeKGChUARumSm7CzAbCK3ngNqzrcyMjN8K6b7n6gvD", "verified_at": "2026-03-19 12:56:44", "checkout_url": "https://checkout.stripe.com/c/pay/cs_test_a18ti7qh7TlrB7mwBeKGChUARumSm7CzAbCK3ngNqzrcyMjN8K6b7n6gvD#fidnandhYHdWcXxpYCc%2FJ2FgY2RwaXEnKSdkdWxOYHwnPyd1blpxYHZxWjA0VnxQMDRGR1RzbjRHfEB0PV82dTFiSWNGcGEyZHdCY0BASTIxQWhNc3A0QkJmY31vNUZDSmhfUkpOdzdkd3dGZ1FpN1RhPHBnY0hTZF9jdGB3VXRnPFxpNTVjR25ddjVDTScpJ2N3amhWYHdzYHcnP3F3cGApJ2dkZm5id2pwa2FGamlqdyc%2FJyZjY2NjY2MnKSdpZHxqcHFRfHVgJz8ndmxrYmlgWmxxYGgnKSdga2RnaWBVaWRmYG1qaWFgd3YnP3F3cGB4JSUl", "stripe_status": "paid", "is_balance_payment": false, "checkout_session_created": true}', '2026-03-19 04:56:44', '2026-03-19 04:56:16', '2026-03-19 04:56:44');

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

-- Dumping data for table platinum.tbl_permissions: ~14 rows (approximately)
INSERT INTO `tbl_permissions` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'view_employees', 'Can view the list of employees and their basic information', 'active', '2026-03-20 09:52:31', '2026-03-20 09:52:31'),
	(2, 'create_employee', 'Can create and add new employees to the studio', 'active', '2026-03-20 09:52:36', '2026-03-20 09:52:36'),
	(3, 'edit_employee', 'Can edit existing employee details and information', 'active', '2026-03-20 09:52:42', '2026-03-20 09:52:42'),
	(4, 'delete_employee', 'Can permanently remove employees from the system', 'active', '2026-03-20 09:52:47', '2026-03-20 09:52:47'),
	(5, 'view_attendance', 'Can view attendance records of all employees', 'active', '2026-03-20 09:52:52', '2026-03-20 09:52:52'),
	(6, 'manage_attendance', 'Can mark attendance, edit time logs, and override attendance records', 'active', '2026-03-20 09:52:57', '2026-03-20 09:52:57'),
	(7, 'view_payroll', 'Can view payroll calculations and payment history', 'active', '2026-03-20 09:53:02', '2026-03-20 09:53:02'),
	(8, 'manage_payroll', 'Can configure payroll settings, deductions, and employee rates', 'active', '2026-03-20 09:53:07', '2026-03-20 09:53:07'),
	(9, 'process_payroll', 'Can initiate and process payroll payments', 'active', '2026-03-20 09:53:12', '2026-03-20 09:53:12'),
	(10, 'view_schedules', 'Can view employee work schedules and shifts', 'active', '2026-03-20 09:53:17', '2026-03-20 09:53:17'),
	(11, 'manage_schedules', 'Can create, edit, and assign employee work schedules', 'active', '2026-03-20 09:53:22', '2026-03-20 09:53:22'),
	(12, 'view_reports', 'Can view system reports and analytics', 'active', '2026-03-20 09:53:29', '2026-03-20 09:53:29'),
	(13, 'export_reports', 'Can export reports in PDF, Excel, or CSV formats', 'active', '2026-03-20 09:53:33', '2026-03-20 09:53:33'),
	(14, 'manage_permissions', 'Can assign and modify role permissions', 'active', '2026-03-20 09:53:38', '2026-03-20 09:53:38'),
	(15, 'manage_roles', 'Can create, edit, and delete system roles', 'active', '2026-03-20 09:53:44', '2026-03-20 09:53:44'),
	(16, 'manage_archives', 'Allows the user to access, organize, and maintain archived records or data, including viewing, restoring, and deleting archived items as permitted by their role.', 'active', '2026-03-20 10:04:25', '2026-03-20 10:04:25');

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

-- Dumping data for table platinum.tbl_rbac: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_roles: ~5 rows (approximately)
INSERT INTO `tbl_roles` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'studio-hr-manager', 'Human Resource Manager - Full access to employee management, attendance, and HR reports', 'active', '2026-03-20 09:51:34', '2026-03-20 09:51:34'),
	(2, 'studio-hr-staff', 'Human Resource Staff - Can view employee records, manage attendance, and update employee information', 'active', '2026-03-20 09:51:42', '2026-03-20 09:51:42'),
	(3, 'studio-finance-manager', 'Finance Manager - Full access to payroll processing, financial reports, and payment management', 'active', '2026-03-20 09:51:49', '2026-03-20 09:51:49'),
	(4, 'studio-finance-staff', 'Finance Staff - Can view payroll records and generate financial reports', 'active', '2026-03-20 09:52:00', '2026-03-20 09:52:00'),
	(5, 'studio-photographer', 'Responsible for managing assigned bookings, updating work status, and uploading photos to client galleries.', 'active', '2026-03-20 11:04:17', '2026-03-20 11:07:09');

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

-- Dumping data for table platinum.tbl_role_permissions: ~16 rows (approximately)
INSERT INTO `tbl_role_permissions` (`id`, `role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
	(11, 2, 3, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(12, 2, 1, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(13, 2, 5, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(14, 2, 7, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(15, 2, 10, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(16, 2, 12, '2026-03-20 13:47:49', '2026-03-20 13:47:49'),
	(17, 2, 2, '2026-03-25 06:28:38', '2026-03-25 06:28:38'),
	(19, 2, 6, '2026-03-25 06:28:38', '2026-03-25 06:28:38'),
	(20, 2, 8, '2026-03-25 06:28:38', '2026-03-25 06:28:38'),
	(21, 2, 9, '2026-03-25 06:28:38', '2026-03-25 06:28:38'),
	(22, 2, 13, '2026-03-25 06:28:38', '2026-03-25 06:28:38'),
	(24, 1, 2, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(25, 1, 3, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(26, 1, 1, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(27, 1, 6, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(28, 1, 5, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(29, 1, 8, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(30, 1, 9, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(31, 1, 7, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(32, 1, 11, '2026-03-25 07:48:43', '2026-03-25 07:48:43'),
	(33, 1, 10, '2026-03-25 07:48:43', '2026-03-25 07:48:43');

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

-- Dumping data for table platinum.tbl_services: ~2 rows (approximately)
INSERT INTO `tbl_services` (`id`, `studio_id`, `category_id`, `service_name`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, '"[\\"Full-Day Wedding Coverage\\",\\"Engagement Photo Session\\",\\"Bridal Portrait Shoot\\"]"', '2026-03-02 00:18:06', '2026-03-02 00:18:06'),
	(2, 1, 2, '"[\\"Corporate Event Coverage\\",\\"Birthday Party Photography\\",\\"Conference & Seminar Documentation\\"]"', '2026-03-02 00:18:32', '2026-03-02 00:18:32'),
	(4, 3, 2, '"[\\"Corporate Event Coverage\\",\\"Birthday Party Photography\\",\\"Conference & Seminar Documentation\\"]"', '2026-03-02 00:18:32', '2026-03-02 00:18:32');

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

-- Dumping data for table platinum.tbl_studios: ~2 rows (approximately)
INSERT INTO `tbl_studios` (`id`, `user_id`, `category_id`, `location_id`, `street`, `barangay`, `contact_number`, `studio_email`, `facebook_url`, `instagram_url`, `website_url`, `studio_name`, `studio_type`, `year_established`, `studio_description`, `studio_logo`, `starting_price`, `downpayment_percentage`, `operating_days`, `start_time`, `end_time`, `max_clients_per_day`, `advance_booking_days`, `business_permit`, `owner_id_document`, `status`, `rejection_note`, `created_at`, `updated_at`) VALUES
	(1, 96, 3, 1, 'Unit 402, 4th Floor, Valero Tower, 122 Valero St.', 'Salitran III', '09171234567', 'hello@prismandpine.com', 'https://facebook.com/prismandpinestudio', 'https://insgram.com/prismandpine', 'https://www.prismandpine.com', 'Prism & Pine Creative Spaces', 'photography_studio', 2022, 'A 120sqm minimalist, Scandinavian-inspired daylight studio located in the heart of the city. We specialize in editorial fashion, high-end portraits, and commercial product shoots. Our space features floor-to-ceiling windows, a motorized cyclorama wall, and a dedicated makeup and dressing area.', 'studio_logo/0ebd87e6-4dd2-4d74-a7aa-8ed3675fe311.png', '3500', 67.00, '"[\\"monday\\",\\"wednesday\\",\\"friday\\",\\"saturday\\"]"', '08:00:00', '18:00:00', 4, 3, 'studio_documents/943b5df4-4eda-4b65-9ca0-504ce5e79fa8.pdf', 'studio_documents/016bf19e-a03b-41db-bbea-bd122b1baa7c.jpg', 'verified', NULL, '2026-02-24 20:10:55', '2026-03-08 05:08:45'),
	(3, 97, 1, 7, '5th Floor, Heritage Lofts, 456 Dela Rosa Street', 'Pasong Langka', '09175558842', 'hello@lumiereco.ph', 'https://www.facebook.com/lumierecostudio', 'https://www.instagram.com/lumiereco.studio', 'https://www.lumiereco.ph', 'Lumière & Co. Photography Studio', 'photography_studio', 2021, 'Lumière & Co. Photography Studio is a boutique creative space nestled in the vibrant cultural district of Makati City. Founded by award-winning photographer Sofia Reyes, our studio combines contemporary aesthetics with timeless artistry. We pride ourselves on creating authentic, emotion-driven imagery that tells your unique story. Our 3,000 square foot industrial-chic loft features floor-to-ceiling windows, natural lighting, customizable backdrops, and cutting-edge photography equipment. We specialize in weddings, events, portraits, commercial photography, and creative content production.', 'studio_logo/9c5b0664-f8f2-4f83-9fb1-93d3b93a29c7.png', '6000', 45.00, '"[\\"monday\\",\\"tuesday\\",\\"thursday\\",\\"friday\\"]"', '10:00:00', '19:00:00', 5, 7, 'studio_documents/0c09e198-04b0-4fae-9886-ddbfb9e119a5.pdf', 'studio_documents/cde314ca-910a-42a7-a5f9-a99d82ad7b33.jpg', 'verified', NULL, '2026-02-26 21:05:16', '2026-02-26 21:06:25');

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

-- Dumping data for table platinum.tbl_studio_employee_schedule: ~6 rows (approximately)
INSERT INTO `tbl_studio_employee_schedule` (`id`, `user_id`, `studio_id`, `operating_days`, `start_time`, `end_time`, `is_active`, `notes`, `created_at`, `updated_at`) VALUES
	(23, 135, 1, '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]', '08:00:00', '17:00:00', 1, NULL, '2026-03-20 10:50:17', '2026-03-20 10:50:17'),
	(25, 138, 1, '["monday", "tuesday", "wednesday", "thursday", "friday"]', '07:57:00', '19:12:00', 1, NULL, '2026-03-20 13:50:33', '2026-03-20 13:50:33'),
	(26, 139, 1, '["monday", "tuesday", "wednesday", "thursday", "friday"]', '06:00:00', '17:00:00', 1, NULL, '2026-03-20 13:51:41', '2026-03-20 13:51:41'),
	(27, 140, 1, '["monday", "tuesday", "wednesday", "thursday", "friday", "saturday"]', '08:00:00', '18:00:00', 1, NULL, '2026-03-20 13:57:57', '2026-03-20 13:57:57'),
	(28, 141, 1, '["monday", "tuesday", "wednesday", "thursday", "friday"]', '08:00:00', '18:00:00', 1, NULL, '2026-03-20 13:59:39', '2026-03-20 13:59:39');

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

-- Dumping data for table platinum.tbl_studio_members: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_studio_online_gallery: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_studio_photographers: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_studio_plans: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_studio_ratings: ~0 rows (approximately)

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

-- Dumping data for table platinum.tbl_studio_schedules: ~1 rows (approximately)
INSERT INTO `tbl_studio_schedules` (`id`, `studio_id`, `location_id`, `operating_days`, `opening_time`, `closing_time`, `booking_limit`, `advance_booking`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, '"[\\"monday\\",\\"wednesday\\",\\"friday\\",\\"saturday\\"]"', '08:00:00', '18:00:00', 4, 3, '2026-02-24 20:10:55', '2026-02-24 20:10:55'),
	(3, 3, 7, '"[\\"monday\\",\\"tuesday\\",\\"thursday\\",\\"friday\\"]"', '10:00:00', '19:00:00', 5, 7, '2026-02-26 21:05:16', '2026-02-26 21:05:16');

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

-- Dumping data for table platinum.tbl_subscription_plans: ~3 rows (approximately)
INSERT INTO `tbl_subscription_plans` (`id`, `user_type`, `plan_type`, `billing_cycle`, `plan_code`, `name`, `description`, `price`, `commission_rate`, `max_booking`, `max_studio_photographers`, `max_studios`, `staff_limit`, `priority_level`, `features`, `support_level`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'studio', 'basic', 'monthly', 'STU_BASIC_MON', 'Studio Basic (Monthly)', NULL, 599.00, 5.00, 50, 3, 2, 3, 0, '["Manage up to 2 Studios (Locations/Brands)", "3 Photographer Seats", "Consolidated Calendar View (See both studios)", "Automated Invoice Reminders", "Client Portal Access", "100 GB Storage"]', 'basic', 'active', '2026-02-24 00:30:41', '2026-02-24 00:30:41'),
	(2, 'studio', 'premium', 'yearly', 'STU_PREMIUM_YEA', 'Studio Premium (Yearly)', NULL, 1490.00, 2.00, 100, 10, 5, 10, 2, '["Manage up to 5 Studios", "10 Photographer Seats", "Team Permissions & Roles", "Advanced Analytics (Revenue per Studio)", "Automated SMS/Email Reminders", "API Access for Website Integration", "500 GB Storage + Automated Backup"]', 'priority', 'active', '2026-02-24 00:32:25', '2026-02-24 00:32:25'),
	(3, 'studio', 'enterprise', 'yearly', 'STU_ENTERPRISE_YEA', 'Studio Enterprise (Yearly)', NULL, 7500.00, 1.00, 500, 50, 10, 25, 5, '["Unlimited Studios & Photographers", "Unlimited Staff Accounts", "White-Label Solution (Remove our branding)", "Custom Contract Templates", "Advanced API Access & Webhooks", "2 TB Cloud Storage", "Priority Feature Requests"]', 'dedicated', 'active', '2026-02-24 00:34:00', '2026-02-24 00:34:00');

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

-- Dumping data for table platinum.tbl_system_revenue: ~3 rows (approximately)
INSERT INTO `tbl_system_revenue` (`id`, `transaction_reference`, `booking_id`, `payment_id`, `subscription_id`, `total_amount`, `platform_fee_percentage`, `platform_fee_amount`, `provider_amount`, `provider_type`, `revenue_type`, `provider_id`, `client_id`, `status`, `breakdown`, `settled_at`, `created_at`, `updated_at`) VALUES
	(1, 'REV-69A6ABEDAAA81', 1, 1, NULL, 7200.00, 10.00, 720.00, 6480.00, 'studio', 'booking', 1, 64, 'completed', '{"calculation": {"platform_fee": 720, "total_payment": "7200.00", "provider_earnings": 6480}, "payment_type": "downpayment", "booking_summary": {"down_payment": "7200.00", "total_amount": "12000.00", "remaining_balance": 4800}, "booking_reference": "BK-69A6ABD94E732", "payment_reference": "PAY-69A6ABD9530D4", "platform_fee_percentage": "10%"}', '2026-03-03 01:37:49', '2026-03-03 01:37:49', '2026-03-03 01:37:49'),
	(2, 'REV-69A6D4F5ADF0C', 2, 2, NULL, 5750.00, 10.00, 575.00, 5175.00, 'freelancer', 'booking', 98, 64, 'completed', '{"calculation": {"platform_fee": 575, "total_payment": "5750.00", "provider_earnings": 5175}, "payment_type": "downpayment", "booking_summary": {"down_payment": "5750.00", "total_amount": "25000.00", "remaining_balance": 19250}, "booking_reference": "BK-69A6D4C702ACD", "payment_reference": "PAY-69A6D4C71C421", "platform_fee_percentage": "10%"}', '2026-03-03 04:32:53', '2026-03-03 04:32:53', '2026-03-03 04:32:53'),
	(3, 'REV-69A6DE4F2A5C9', 3, 3, NULL, 8500.00, 10.00, 850.00, 7650.00, 'freelancer', 'booking', 98, 64, 'completed', '{"calculation": {"platform_fee": 850, "total_payment": "8500.00", "provider_earnings": 7650}, "payment_type": "full_payment", "booking_summary": {"down_payment": "8500.00", "total_amount": "8500.00", "remaining_balance": 0}, "booking_reference": "BK-69A6DE2ED1A49", "payment_reference": "PAY-69A6DE2EEAB90", "platform_fee_percentage": "10%"}', '2026-03-03 05:12:47', '2026-03-03 05:12:47', '2026-03-03 05:12:47'),
	(6, 'REV-69B89508B59AD', 5, 5, NULL, 3350.00, 10.00, 335.00, 3015.00, 'studio', 'booking', 1, 69, 'completed', '{"calculation": {"platform_fee": 335, "total_payment": "3350.00", "provider_earnings": 3015}, "payment_type": "downpayment", "booking_summary": {"down_payment": "3350.00", "total_amount": "5000.00", "remaining_balance": 1650}, "booking_reference": "BK-69B894D8C41C9", "payment_reference": "PAY-69B894D8E1EB8", "platform_fee_percentage": "10%"}', '2026-03-16 23:40:56', '2026-03-16 23:40:56', '2026-03-16 23:40:56'),
	(7, 'REV-69BB7C1242DCD', 5, 6, NULL, 1650.00, 10.00, 165.00, 1485.00, 'studio', 'booking', 1, 69, 'completed', '{"calculation": {"platform_fee": 165, "total_payment": "1650.00", "provider_earnings": 1485}, "payment_type": "downpayment", "booking_summary": {"down_payment": "3350.00", "total_amount": "5000.00", "remaining_balance": 0}, "booking_reference": "BK-69B894D8C41C9", "payment_reference": "PAY-69BB7BF0DF024", "platform_fee_percentage": "10%"}', '2026-03-19 04:31:14', '2026-03-19 04:31:14', '2026-03-19 04:31:14'),
	(8, 'REV-69BB820CD91F6', 6, 7, NULL, 8040.00, 10.00, 804.00, 7236.00, 'studio', 'booking', 1, 82, 'completed', '{"calculation": {"platform_fee": 804, "total_payment": "8040.00", "provider_earnings": 7236}, "payment_type": "downpayment", "booking_summary": {"down_payment": "8040.00", "total_amount": "12000.00", "remaining_balance": 3960}, "booking_reference": "BK-69BB81F03ACE6", "payment_reference": "PAY-69BB81F03FA1F", "platform_fee_percentage": "10%"}', '2026-03-19 04:56:44', '2026-03-19 04:56:44', '2026-03-19 04:56:44');

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

-- Dumping data for table platinum.tbl_users: ~39 rows (approximately)
INSERT INTO `tbl_users` (`id`, `uuid`, `role`, `first_name`, `middle_name`, `last_name`, `user_type`, `email`, `mobile_number`, `password`, `profile_photo`, `cover_photo`, `location_id`, `status`, `email_verified`, `verification_token`, `token_expiry`, `created_at`, `updated_at`) VALUES
	(1, 'fe0cd758-dbd6-47f5-a71f-de0ef3dd6079', 'admin', 'Studio', 'System', 'Administrator', 'Admin', 'lumora_admin@gmail.com', '+633109293132', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', 'profile-photos/aChzeEPXOAX2MjHLzzQwK69mbic7wgBWtQrLdetU.png', 'cover-photos/Ifa0FHx27fnNcXcOCP1fC4mLm0oiVORx61Naftef.png', 2, 'active', 1, NULL, NULL, '2026-01-24 09:49:41', '2026-03-01 06:59:51'),
	(53, '65031538-1347-4c1e-aec1-12fd77a0e476', 'client', 'David', 'O.', 'Miller', 'Customer', 'david.miller1@example.com', '+15349735002', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', 'profile-photos/g0EJweaSUKYDZFd2crO9l0PUGcZRHWSA4nBc3RM6.png', 'cover-photos/Q5UuzQIxAfRa33pLyRKLXS4ruQg9pxsTHe1PDGwT.png', 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-03-01 06:22:00'),
	(54, '09827a02-a1b2-48c9-8811-8dbc1764bbde', 'client', 'Mark', 'G.', 'Robinson', 'Customer', 'mark.robinson2@example.com', '+18423819131', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(55, '691e653e-480c-4e14-861c-a8e96d2f7512', 'client', 'Sarah', 'J.', 'Clark', 'Customer', 'sarah.clark3@example.com', '+18627843220', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(56, 'bbe3a03b-9e83-49dc-a08c-10e88060919c', 'client', 'Maria', 'Y.', 'Brown', 'Customer', 'maria.brown4@example.com', '+18947671590', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 20, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(57, 'd5eda1b7-b0e4-4761-ad96-a50bbb5ba344', 'client', 'James', 'E.', 'Clark', 'Customer', 'james.clark5@example.com', '+14626537902', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 21, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(58, '93ca180e-cbe2-4b32-902d-821e8e9ff86c', 'client', 'Daniel', 'V.', 'Brown', 'Customer', 'daniel.brown6@example.com', '+19175157707', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(59, 'af990d67-73ec-438c-b194-10172c54a346', 'client', 'Robert', NULL, 'Clark', 'Customer', 'robert.clark7@example.com', '+14813087510', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 7, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(60, 'f84b4275-1fd7-4b29-be54-47252b9f1016', 'client', 'Michael', NULL, 'Martinez', 'Customer', 'michael.martinez8@example.com', '+13105114736', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(61, 'fecd1053-87ae-4955-bf94-5aa279773f37', 'client', 'Sarah', NULL, 'Brown', 'Customer', 'sarah.brown9@example.com', '+16281363729', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(62, 'b204adc7-8fe9-4626-8697-efa11b8a4ab3', 'client', 'Anthony', NULL, 'Martin', 'Customer', 'anthony.martin10@example.com', '+19832946428', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 1, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(63, '53f21a0e-474f-4f0d-b863-fdda1dfe8a12', 'client', 'Joseph', NULL, 'Lewis', 'Customer', 'joseph.lewis11@example.com', '+18395948374', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 20, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(64, 'dd5f3e3e-2468-4b97-b7e6-cd110c52fd1b', 'client', 'Emily', NULL, 'Johnson', 'Customer', 'emily.johnson12@example.com', '+14213434459', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 7, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(65, 'b157042a-97a2-4a45-8aa6-3e2dd8b9c20a', 'client', 'John', NULL, 'Gonzalez', 'Customer', 'john.gonzalez13@example.com', '+15535239662', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 19, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(66, '027a92d8-8c52-43c6-ba25-cf2e8c0b1adf', 'client', 'Sarah', NULL, 'Taylor', 'Customer', 'sarah.taylor14@example.com', '+15337632509', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 7, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(67, 'f392f106-1cd4-4151-9d33-222b3a6d4d2f', 'client', 'Jennifer', 'O.', 'Robinson', 'Customer', 'jennifer.robinson15@example.com', '+15378129628', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(68, '748bfd07-0618-412c-8c71-f38aac96c2df', 'client', 'Daniel', NULL, 'Hernandez', 'Customer', 'daniel.hernandez16@example.com', '+19872282772', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(69, 'ffaa058c-db7b-4ed8-81d8-a2f6d8949ec2', 'client', 'Nancy', NULL, 'Clark', 'Customer', 'nancy.clark17@example.com', '+17196323998', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(70, '06a1a95a-cc44-41fc-ad50-cacfffc7396e', 'client', 'Richard', 'W.', 'Brown', 'Customer', 'richard.brown18@example.com', '+18402319037', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(71, '3e649cda-7c08-4999-b6c6-dfede9cf46dd', 'client', 'Susan', 'Z.', 'Clark', 'Customer', 'susan.clark19@example.com', '+19955268580', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 6, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(72, '670f859c-d217-4e84-92bb-893f980323d3', 'client', 'Emily', NULL, 'Moore', 'Customer', 'emily.moore20@example.com', '+18921822828', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(73, '21bdcb15-bfec-4a96-aa94-5dc5b7af56dd', 'client', 'Joseph', NULL, 'Rodriguez', 'Customer', 'joseph.rodriguez21@example.com', '+13915917023', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 21, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(74, '0ff7b4b9-9ace-4dd2-a775-b86265396bde', 'client', 'Maria', NULL, 'Martin', 'Customer', 'maria.martin22@example.com', '+14851716846', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 6, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(75, '0211b56e-c69f-4eba-b25d-a5d2dfffe683', 'client', 'Karen', NULL, 'Jackson', 'Customer', 'karen.jackson23@example.com', '+18027527718', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 7, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(76, '4fb36382-cd4a-4ff7-8a30-d97791173268', 'client', 'Sarah', 'P.', 'Rodriguez', 'Customer', 'sarah.rodriguez24@example.com', '+18941852604', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 2, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(77, '85e92cc6-3c24-449b-9ce9-dc8f858b10af', 'client', 'Richard', 'E.', 'Sanchez', 'Customer', 'richard.sanchez25@example.com', '+13535151667', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 1, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(78, '44c0784a-6a11-44fa-922e-27970ef814e8', 'client', 'Matthew', NULL, 'Thompson', 'Customer', 'matthew.thompson26@example.com', '+12858053882', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(79, 'fa96b929-a0d3-462b-8028-c01f66ae5ef6', 'client', 'Anthony', NULL, 'Harris', 'Customer', 'anthony.harris27@example.com', '+15186578444', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 21, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(80, '9d326391-1e37-4392-b5d1-5a3a017af1e8', 'client', 'Betty', 'L.', 'Clark', 'Customer', 'betty.clark28@example.com', '+12146441690', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 18, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(81, '374401b1-8eaa-4d5a-9656-745858d5bf38', 'client', 'Maria', 'W.', 'Perez', 'Customer', 'maria.perez29@example.com', '+19011435631', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 1, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(82, '5da0b71e-0cf8-4e86-9838-95dcecbfa83e', 'client', 'Mark', 'W.', 'Thomas', 'Customer', 'mark.thomas30@example.com', '+12655981727', '$2y$12$1q3FNPIgmFlGo6tcIn/FT.2rvUxNAJW5.JlWJ/9Xq0bwTt8lMOxZS', NULL, NULL, 6, 'active', 1, NULL, NULL, '2026-02-06 20:37:25', '2026-02-06 20:37:25'),
	(96, 'eb625247-c75c-411b-8ad7-f50137d4617c', 'owner', 'Daphne', 'Karina Marsh', 'Russo', 'Photographer', 'gorocoke@fxzig.com', '+631111111111', '$2y$12$KSqKICxKT5mjSyB52sKCh.eutgq1.aJr9lKegVDeyK3sLemXvXYyi', 'profile_photos/154ecda4-0230-425d-bdf8-2ef5eccce372.png', NULL, 18, 'active', 1, NULL, NULL, '2026-02-22 06:32:13', '2026-02-24 20:14:26'),
	(97, '44ede02b-e3ba-4ae0-9671-265a70a05f0b', 'owner', 'Samantha', 'Kevin Cook', 'Bass', 'Photographer', 'huficiworu@mailinator.com', '+632222222222', '$2y$12$rhAPiLMGNjp1FrVlZDzGnu05m38NpIp4/Rg/zqO2swxpMoSpquvf.', 'profile_photos/9cac8e64-b8aa-4e6b-9f66-0870c2e7ae32.png', NULL, 21, 'active', 1, NULL, NULL, '2026-02-22 21:22:24', '2026-02-26 21:05:16'),
	(98, '0c687fb3-a3b4-48fd-a359-b08adc11cfe0', 'freelancer', 'Ivor', 'Katell Hart', 'Mueller', 'Photographer', 'pixe@mailinator.com', '+633333333333', '$2y$12$1nXgt7UpNVM8Q/H9rvxOeOJzi/jEetFtNn3ejqkXuJqsiguA/LPqO', 'profile-photos/RHGZQg39HrcO0RSd0r3aQsOBOTO4soJQScJNBn3X.png', 'cover-photos/RsSsEIWlHwOxZIsv5FNel5ci8tU1LkjH5DfklQEC.png', 2, 'active', 1, NULL, NULL, '2026-02-26 21:46:34', '2026-03-01 07:03:50'),
	(135, 'a303dbba-11dd-4744-88fc-1b25b2f915fc', 'studio-hr', 'Kameko', 'Christine Ross', 'Calderon', 'Manager', 'nevosobyb@mailinator.com', '+(63)661 312 3123', '$2y$12$lPfJOcQdQT9jrFqq2CclGORmazuNvPSR3SFhyK/lSMfXraVihZhIC', 'employee_1774003817_69bd2669d490f.png', NULL, NULL, 'active', 1, NULL, NULL, '2026-03-20 10:50:17', '2026-03-20 10:50:17'),
	(138, 'b2399f8f-a289-49b9-af40-f86e3a28b82b', 'studio-hr', 'Melinda', 'Portia Sullivan', 'Chan', 'Staff', 'fixifebaf@mailinator.com', '+(63)513 324 2342', '$2y$12$G2kL0IiRT3ylGRUZpEMekOUAwqdqCu6XFVCeloas2mrdud3aRfWaa', 'employee_1774014631_69bd50a788fed.png', NULL, NULL, 'active', 1, NULL, NULL, '2026-03-20 13:50:32', '2026-03-20 13:50:32'),
	(139, 'a309b478-f74c-4fa1-be16-de539fcfe90e', 'studio-hr', 'Orson', 'Owen Jensen', 'Sawyer', 'Staff', 'qbiklash@pdf-cutter.com', '+(63)691 123 1231', '$2y$12$oFhMLssKaU474tTkXO/LP.iG7Tw6TAjas14KWsGSXyXY8wULpk3RG', 'employee_1774014701_69bd50ed7cdcc.png', NULL, NULL, 'active', 1, NULL, NULL, '2026-03-20 13:51:41', '2026-03-20 13:51:41'),
	(140, '41e618c6-33f4-4034-9baf-7f212906cae3', 'studio-hr', 'Cally', 'Yvonne Dyer', 'Snyder', 'Staff', 'sapodo@mailinator.com', '+(63)239 018 4721', '$2y$12$Lndc37ljV6H9.ugwix4ZN.aMePsTQtkZgTRGJ9vwYjWpcEcGNHXpW', 'employee_1774015077_69bd52659cabb.png', NULL, NULL, 'active', 1, NULL, NULL, '2026-03-20 13:57:57', '2026-03-20 13:57:57'),
	(141, '0d504ce2-d3d9-470b-b6d4-ba83f60a0d3d', 'studio-hr', 'Chaney', 'Aurelia Webster', 'Stone', 'Staff', 'loseta4930@qvmao.com', '+(63)471 542 3423', '$2y$12$Pd6tMA2tL6cYUeFELy59.e9mmzDTK9bGHop8e.yvAqehJv9mBtEWK', 'employee_1774015179_69bd52cb2cbed.png', NULL, NULL, 'active', 1, NULL, NULL, '2026-03-20 13:59:39', '2026-03-20 13:59:39');

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

-- Dumping data for table platinum.tbl_user_roles: ~6 rows (approximately)
INSERT INTO `tbl_user_roles` (`id`, `user_id`, `role_id`, `created_at`, `updated_at`) VALUES
	(3, 135, 1, '2026-03-20 10:50:17', '2026-03-20 10:50:17'),
	(5, 138, 2, '2026-03-20 13:50:32', '2026-03-20 13:50:32'),
	(6, 139, 2, '2026-03-20 13:51:41', '2026-03-20 13:51:41'),
	(7, 140, 2, '2026-03-20 13:57:57', '2026-03-20 13:57:57'),
	(8, 141, 2, '2026-03-20 13:59:39', '2026-03-20 13:59:39');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

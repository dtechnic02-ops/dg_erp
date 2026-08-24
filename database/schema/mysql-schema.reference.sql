/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `voucher_no` varchar(255) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `journal_item_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `at_company_journal_unique` (`company_id`,`journal_item_id`),
  UNIQUE KEY `at_reversal_unique` (`reversed_transaction_id`),
  KEY `account_transactions_journal_item_id_index` (`journal_item_id`),
  KEY `account_transactions_reversed_transaction_id_index` (`reversed_transaction_id`),
  CONSTRAINT `at_journal_item_fk` FOREIGN KEY (`journal_item_id`) REFERENCES `journal_items` (`id`),
  CONSTRAINT `at_reversal_fk` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `account_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounting_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `entry_number` varchar(255) NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(255) DEFAULT NULL,
  `source_module` varchar(255) NOT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_event` varchar(255) DEFAULT NULL,
  `source_key` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_entries_company_id_entry_number_unique` (`company_id`,`entry_number`),
  UNIQUE KEY `accounting_entries_company_id_source_key_unique` (`company_id`,`source_key`),
  KEY `accounting_entries_reversal_of_id_foreign` (`reversal_of_id`),
  KEY `accounting_entries_posted_by_foreign` (`posted_by`),
  KEY `accounting_entries_created_by_foreign` (`created_by`),
  KEY `accounting_entries_updated_by_foreign` (`updated_by`),
  KEY `accounting_entries_entry_date_index` (`entry_date`),
  KEY `accounting_entries_status_index` (`status`),
  KEY `accounting_entries_source_module_index` (`source_module`),
  KEY `accounting_entries_source_type_source_id_index` (`source_type`,`source_id`),
  KEY `accounting_entries_company_id_entry_date_index` (`company_id`,`entry_date`),
  KEY `accounting_entries_company_id_status_index` (`company_id`,`status`),
  KEY `ae_company_fy_date_idx` (`company_id`,`financial_year_id`,`entry_date`),
  KEY `accounting_entries_financial_year_fk` (`financial_year_id`),
  CONSTRAINT `accounting_entries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_financial_year_fk` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `accounting_entries_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `accounting_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_entries_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounting_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_entry_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `accounting_entry_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `operational_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subledger_type` varchar(50) DEFAULT NULL,
  `subledger_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_entry_lines_accounting_entry_id_line_number_unique` (`accounting_entry_id`,`line_number`),
  KEY `accounting_entry_lines_operational_account_id_foreign` (`operational_account_id`),
  KEY `accounting_entry_lines_subledger_type_subledger_id_index` (`subledger_type`,`subledger_id`),
  KEY `ael_chart_entry_idx` (`chart_account_id`,`accounting_entry_id`),
  CONSTRAINT `accounting_entry_lines_accounting_entry_id_foreign` FOREIGN KEY (`accounting_entry_id`) REFERENCES `accounting_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `accounting_entry_lines_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `accounting_entry_lines_operational_account_id_foreign` FOREIGN KEY (`operational_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounting_period_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounting_period_locks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 1,
  `reason` text NOT NULL,
  `locked_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounting_period_locks_financial_year_id_foreign` (`financial_year_id`),
  KEY `accounting_period_locks_locked_by_foreign` (`locked_by`),
  KEY `period_lock_company_fy_idx` (`company_id`,`financial_year_id`,`is_locked`),
  KEY `period_lock_company_dates_idx` (`company_id`,`date_from`,`date_to`),
  CONSTRAINT `accounting_period_locks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `accounting_period_locks_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `accounting_period_locks_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_group` varchar(20) DEFAULT NULL,
  `account_type` varchar(255) NOT NULL DEFAULT 'bank',
  `sub_ledger_type` varchar(20) DEFAULT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `branch` varchar(255) DEFAULT NULL,
  `account_no` varchar(255) DEFAULT NULL,
  `iban` varchar(255) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'AED',
  `swift_code` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `image_path` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing_cycles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `duration_days` int(10) unsigned DEFAULT NULL,
  `is_lifetime` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `billing_cycles_code_unique` (`code`),
  KEY `billing_cycles_created_by_foreign` (`created_by`),
  KEY `billing_cycles_updated_by_foreign` (`updated_by`),
  KEY `billing_cycles_cancelled_by_foreign` (`cancelled_by`),
  KEY `idx_billing_cycles_active_sort` (`is_active`,`sort_order`),
  CONSTRAINT `billing_cycles_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cycles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_cycles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_company_id_name_unique` (`company_id`,`name`),
  KEY `brands_created_by_foreign` (`created_by`),
  KEY `brands_updated_by_foreign` (`updated_by`),
  CONSTRAINT `brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brands_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `brands_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chart_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chart_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_class` enum('asset','liability','equity','income','expense') NOT NULL,
  `account_category` varchar(255) DEFAULT NULL,
  `normal_balance` enum('debit','credit') NOT NULL,
  `system_code` varchar(255) DEFAULT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `is_control` tinyint(1) NOT NULL DEFAULT 0,
  `allow_manual_entry` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chart_accounts_company_id_code_unique` (`company_id`,`code`),
  KEY `chart_accounts_parent_id_foreign` (`parent_id`),
  KEY `chart_accounts_created_by_foreign` (`created_by`),
  KEY `chart_accounts_updated_by_foreign` (`updated_by`),
  KEY `chart_accounts_account_class_index` (`account_class`),
  KEY `chart_accounts_system_code_index` (`system_code`),
  KEY `chart_accounts_status_index` (`status`),
  KEY `chart_accounts_company_id_account_class_index` (`company_id`,`account_class`),
  KEY `chart_accounts_company_id_parent_id_index` (`company_id`,`parent_id`),
  CONSTRAINT `chart_accounts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chart_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chart_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `chart_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chart_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax_no` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `financial_year_type` varchar(255) NOT NULL DEFAULT 'calendar',
  `language` varchar(255) NOT NULL DEFAULT 'English',
  `pan_number` varchar(255) DEFAULT NULL,
  `vat_number` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `selected_user_limit` int(10) unsigned NOT NULL DEFAULT 1,
  `selected_customer_limit` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','blocked','expired') NOT NULL DEFAULT 'active',
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_mobile_unique` (`mobile`),
  UNIQUE KEY `companies_email_unique` (`email`),
  KEY `companies_country_id_foreign` (`country_id`),
  CONSTRAINT `companies_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_permission` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_permission_company_id_permission_id_unique` (`company_id`,`permission_id`),
  KEY `company_permission_permission_id_foreign` (`permission_id`),
  CONSTRAINT `company_permission_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `mobile_no` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `selected_user_limit` int(11) NOT NULL DEFAULT 5,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_registrations_email_unique` (`email`),
  UNIQUE KEY `company_registrations_username_unique` (`username`),
  KEY `company_registrations_country_id_foreign` (`country_id`),
  CONSTRAINT `company_registrations_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subscription_type` enum('register_trial','free_trial','paid') NOT NULL DEFAULT 'paid',
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `staff_limit` int(10) unsigned NOT NULL DEFAULT 1,
  `hidden_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules`)),
  `is_all_modules_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `previous_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `expired_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subscriptions_company_id_foreign` (`company_id`),
  KEY `company_subscriptions_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `company_subscriptions_billing_cycle_id_foreign` (`billing_cycle_id`),
  KEY `company_subscriptions_previous_subscription_id_foreign` (`previous_subscription_id`),
  KEY `company_subscriptions_cancelled_by_foreign` (`cancelled_by`),
  KEY `company_subscriptions_approved_by_foreign` (`approved_by`),
  KEY `company_subscriptions_created_by_foreign` (`created_by`),
  KEY `company_subscriptions_updated_by_foreign` (`updated_by`),
  CONSTRAINT `company_subscriptions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `company_subscriptions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_previous_subscription_id_foreign` FOREIGN KEY (`previous_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_subscriptions_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `company_subscriptions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscriptions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contras` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `contra_no` varchar(255) NOT NULL,
  `contra_date` date NOT NULL,
  `from_account_id` bigint(20) unsigned NOT NULL,
  `to_account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `transfer_type` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `iso_code` char(2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countries_iso_code_unique` (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `document_type` varchar(50) NOT NULL DEFAULT 'attachment',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_attachments_entity_index` (`company_id`,`entity_type`,`entity_id`),
  KEY `crm_attachments_created_by_foreign` (`created_by`),
  KEY `crm_attachments_archived_by_foreign` (`archived_by`),
  CONSTRAINT `crm_attachments_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_attachments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_attachments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_configurations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `config_type` varchar(50) NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_label` varchar(100) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_config_company_type_key_unique` (`company_id`,`config_type`,`config_key`),
  KEY `crm_config_company_type_active_index` (`company_id`,`config_type`,`is_active`),
  KEY `crm_configurations_created_by_foreign` (`created_by`),
  KEY `crm_configurations_updated_by_foreign` (`updated_by`),
  CONSTRAINT `crm_configurations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_configurations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_configurations_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `contact_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `contact_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_reason` text DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_contacts_company_no_unique` (`company_id`,`contact_no`),
  KEY `crm_contacts_company_date_index` (`company_id`,`contact_date`),
  KEY `crm_contacts_company_status_index` (`company_id`,`status`),
  KEY `crm_contacts_company_employee_index` (`company_id`,`assigned_employee_id`),
  KEY `crm_contacts_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_contacts_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_contacts_crm_lead_id_foreign` (`crm_lead_id`),
  KEY `crm_contacts_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_contacts_created_by_foreign` (`created_by`),
  KEY `crm_contacts_updated_by_foreign` (`updated_by`),
  KEY `crm_contacts_closed_by_foreign` (`closed_by`),
  KEY `crm_contacts_archived_by_foreign` (`archived_by`),
  KEY `crm_contacts_cancelled_by_foreign` (`cancelled_by`),
  KEY `crm_contacts_customer_id_foreign` (`customer_id`),
  CONSTRAINT `crm_contacts_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_contacts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contacts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `crm_contacts_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_contacts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_follow_ups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_follow_ups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `activity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `crm_opportunity_id` bigint(20) unsigned DEFAULT NULL,
  `follow_up_date` date NOT NULL,
  `next_follow_up_date` date DEFAULT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_follow_ups_company_no_unique` (`company_id`,`activity_no`),
  KEY `crm_follow_ups_company_date_index` (`company_id`,`follow_up_date`),
  KEY `crm_follow_ups_company_next_date_index` (`company_id`,`next_follow_up_date`),
  KEY `crm_follow_ups_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_follow_ups_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_follow_ups_crm_lead_id_foreign` (`crm_lead_id`),
  KEY `crm_follow_ups_crm_opportunity_id_foreign` (`crm_opportunity_id`),
  KEY `crm_follow_ups_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_follow_ups_created_by_foreign` (`created_by`),
  KEY `crm_follow_ups_updated_by_foreign` (`updated_by`),
  KEY `crm_follow_ups_archived_by_foreign` (`archived_by`),
  KEY `crm_follow_ups_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `crm_follow_ups_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_follow_ups_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_follow_ups_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_crm_opportunity_id_foreign` FOREIGN KEY (`crm_opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_follow_ups_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_leads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `lead_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `lead_source` varchar(50) DEFAULT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'new',
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `expected_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lead_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `converted_by` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `conversion_remarks` text DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_reason` text DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_leads_company_no_unique` (`company_id`,`lead_no`),
  KEY `crm_leads_company_date_index` (`company_id`,`lead_date`),
  KEY `crm_leads_company_status_index` (`company_id`,`status`),
  KEY `crm_leads_company_employee_index` (`company_id`,`assigned_employee_id`),
  KEY `crm_leads_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_leads_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_leads_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_leads_created_by_foreign` (`created_by`),
  KEY `crm_leads_updated_by_foreign` (`updated_by`),
  KEY `crm_leads_closed_by_foreign` (`closed_by`),
  KEY `crm_leads_archived_by_foreign` (`archived_by`),
  KEY `crm_leads_cancelled_by_foreign` (`cancelled_by`),
  KEY `crm_leads_converted_by_foreign` (`converted_by`),
  KEY `crm_leads_company_customer_index` (`company_id`,`customer_id`),
  KEY `crm_leads_customer_id_foreign` (`customer_id`),
  CONSTRAINT `crm_leads_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_leads_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_leads_converted_by_foreign` FOREIGN KEY (`converted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `crm_leads_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_leads_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_meetings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `activity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `crm_opportunity_id` bigint(20) unsigned DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time DEFAULT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'scheduled',
  `remarks` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_meetings_company_no_unique` (`company_id`,`activity_no`),
  KEY `crm_meetings_company_date_index` (`company_id`,`meeting_date`),
  KEY `crm_meetings_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_meetings_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_meetings_crm_lead_id_foreign` (`crm_lead_id`),
  KEY `crm_meetings_crm_opportunity_id_foreign` (`crm_opportunity_id`),
  KEY `crm_meetings_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_meetings_created_by_foreign` (`created_by`),
  KEY `crm_meetings_updated_by_foreign` (`updated_by`),
  KEY `crm_meetings_archived_by_foreign` (`archived_by`),
  KEY `crm_meetings_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `crm_meetings_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_meetings_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_meetings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_crm_opportunity_id_foreign` FOREIGN KEY (`crm_opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_meetings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `note` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_notes_entity_index` (`company_id`,`entity_type`,`entity_id`),
  KEY `crm_notes_created_by_foreign` (`created_by`),
  KEY `crm_notes_updated_by_foreign` (`updated_by`),
  KEY `crm_notes_archived_by_foreign` (`archived_by`),
  CONSTRAINT `crm_notes_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_notes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_notes_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_opportunities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `opportunity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `potential_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `expected_closing_date` date DEFAULT NULL,
  `probability` decimal(5,2) NOT NULL DEFAULT 0.00,
  `stage` varchar(50) NOT NULL DEFAULT 'discovery',
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `remarks` text DEFAULT NULL,
  `closed_by` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `close_reason` text DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_opportunities_company_no_unique` (`company_id`,`opportunity_no`),
  KEY `crm_opportunities_company_stage_index` (`company_id`,`stage`),
  KEY `crm_opportunities_company_status_index` (`company_id`,`status`),
  KEY `crm_opportunities_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_opportunities_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_opportunities_created_by_foreign` (`created_by`),
  KEY `crm_opportunities_updated_by_foreign` (`updated_by`),
  KEY `crm_opportunities_closed_by_foreign` (`closed_by`),
  KEY `crm_opportunities_archived_by_foreign` (`archived_by`),
  KEY `crm_opportunities_cancelled_by_foreign` (`cancelled_by`),
  KEY `crm_opportunities_company_customer_index` (`company_id`,`customer_id`),
  KEY `crm_opportunities_customer_id_foreign` (`customer_id`),
  KEY `crm_opportunities_crm_lead_id_foreign` (`crm_lead_id`),
  CONSTRAINT `crm_opportunities_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_opportunities_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_closed_by_foreign` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_opportunities_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`),
  CONSTRAINT `crm_opportunities_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `crm_opportunities_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_opportunities_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `event` varchar(100) NOT NULL,
  `previous_value` varchar(255) DEFAULT NULL,
  `current_value` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `crm_status_histories_entity_index` (`company_id`,`entity_type`,`entity_id`),
  KEY `crm_status_histories_changed_by_foreign` (`changed_by`),
  CONSTRAINT `crm_status_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_status_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `activity_no` varchar(50) NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `crm_opportunity_id` bigint(20) unsigned DEFAULT NULL,
  `task_type` varchar(50) NOT NULL DEFAULT 'call',
  `task_status` varchar(50) NOT NULL DEFAULT 'pending',
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `due_date` date NOT NULL,
  `assigned_employee_id` bigint(20) unsigned NOT NULL,
  `remarks` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `archived_by` bigint(20) unsigned DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `archive_reason` text DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_tasks_company_no_unique` (`company_id`,`activity_no`),
  KEY `crm_tasks_company_due_index` (`company_id`,`due_date`),
  KEY `crm_tasks_company_status_index` (`company_id`,`task_status`),
  KEY `crm_tasks_company_fy_index` (`company_id`,`financial_year_id`),
  KEY `crm_tasks_financial_year_id_foreign` (`financial_year_id`),
  KEY `crm_tasks_crm_lead_id_foreign` (`crm_lead_id`),
  KEY `crm_tasks_crm_opportunity_id_foreign` (`crm_opportunity_id`),
  KEY `crm_tasks_assigned_employee_id_foreign` (`assigned_employee_id`),
  KEY `crm_tasks_created_by_foreign` (`created_by`),
  KEY `crm_tasks_updated_by_foreign` (`updated_by`),
  KEY `crm_tasks_archived_by_foreign` (`archived_by`),
  KEY `crm_tasks_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `crm_tasks_archived_by_foreign` FOREIGN KEY (`archived_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_assigned_employee_id_foreign` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employee_accounts` (`id`),
  CONSTRAINT `crm_tasks_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_crm_lead_id_foreign` FOREIGN KEY (`crm_lead_id`) REFERENCES `crm_leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_crm_opportunity_id_foreign` FOREIGN KEY (`crm_opportunity_id`) REFERENCES `crm_opportunities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crm_tasks_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `voucher_no` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `journal_item_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ct_company_journal_unique` (`company_id`,`journal_item_id`),
  UNIQUE KEY `ct_reversal_unique` (`reversed_transaction_id`),
  KEY `customer_transactions_company_id_customer_id_index` (`company_id`,`customer_id`),
  KEY `customer_transactions_financial_year_id_index` (`financial_year_id`),
  KEY `customer_transactions_transaction_date_index` (`transaction_date`),
  KEY `customer_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `customer_transactions_voucher_no_index` (`voucher_no`),
  KEY `customer_transactions_journal_item_id_index` (`journal_item_id`),
  KEY `customer_transactions_reversed_transaction_id_index` (`reversed_transaction_id`),
  CONSTRAINT `ct_journal_item_fk` FOREIGN KEY (`journal_item_id`) REFERENCES `journal_items` (`id`),
  CONSTRAINT `ct_reversal_fk` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `customer_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `crm_lead_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `authority_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_no` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_company_crm_lead_index` (`company_id`,`crm_lead_id`),
  CONSTRAINT `customers_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `document_type` enum('photo','additional_photo','attachment','pdf') NOT NULL DEFAULT 'photo',
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_attachments_note_type_index` (`delivery_note_id`,`document_type`),
  KEY `delivery_attachments_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_note_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_note_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `item_type` enum('product','service') NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `planned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delivered_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_note_items_note_index` (`delivery_note_id`),
  KEY `delivery_note_items_company_item_index` (`company_id`,`sales_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `delivery_no` varchar(50) NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('draft','ready','delivered','partial','rejected','cancelled') NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_notes_company_no_unique` (`company_id`,`delivery_no`),
  KEY `delivery_notes_company_date_index` (`company_id`,`delivery_date`),
  KEY `delivery_notes_company_status_index` (`company_id`,`status`),
  KEY `delivery_notes_company_customer_index` (`company_id`,`customer_id`),
  KEY `delivery_notes_company_invoice_index` (`company_id`,`sales_invoice_id`),
  KEY `delivery_notes_company_fy_index` (`company_id`,`financial_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_signatures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `receiver_name` varchar(255) DEFAULT NULL,
  `receiver_mobile` varchar(30) DEFAULT NULL,
  `signature_path` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `delivery_signatures_note_unique` (`delivery_note_id`),
  KEY `delivery_signatures_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery_status_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `delivery_note_id` bigint(20) unsigned NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `current_status` varchar(20) NOT NULL,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `delivery_status_histories_note_index` (`delivery_note_id`),
  KEY `delivery_status_histories_company_note_index` (`company_id`,`delivery_note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `employee_code` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `joining_date` date NOT NULL,
  `designation` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `post` varchar(255) DEFAULT NULL,
  `employment_type` enum('permanent','contract','temporary','intern') NOT NULL DEFAULT 'permanent',
  `basic_salary` decimal(18,2) NOT NULL DEFAULT 0.00,
  `salary_type` enum('monthly','daily') NOT NULL DEFAULT 'monthly',
  `opening_due_salary` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `cit_no` varchar(255) DEFAULT NULL,
  `pan_no` varchar(255) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `emergency_phone` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `cv_attachment` varchar(255) DEFAULT NULL,
  `id_document` varchar(255) DEFAULT NULL,
  `contract_document` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_accounts_company_code_unique` (`company_id`,`employee_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `employee_account_id` bigint(20) unsigned NOT NULL,
  `salary_sheet_id` bigint(20) unsigned NOT NULL,
  `voucher_no` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `salary_year` int(11) NOT NULL,
  `salary_month` tinyint(4) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_payments_company_voucher_unique` (`company_id`,`voucher_no`),
  KEY `employee_payments_salary_sheet_id_index` (`salary_sheet_id`),
  CONSTRAINT `employee_payments_salary_sheet_id_foreign` FOREIGN KEY (`salary_sheet_id`) REFERENCES `salary_sheets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expense_categories_chart_account_id_foreign` (`chart_account_id`),
  CONSTRAINT `expense_categories_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `expense_no` varchar(255) NOT NULL,
  `expense_category_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_years` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_years_company_id_foreign` (`company_id`),
  CONSTRAINT `financial_years_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `income_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `income_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `income_categories_chart_account_id_foreign` (`chart_account_id`),
  CONSTRAINT `income_categories_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `incomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `income_category_id` bigint(20) unsigned DEFAULT NULL,
  `income_no` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `income_date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventory_valuations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_valuations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned NOT NULL,
  `valuation_sequence` bigint(20) unsigned NOT NULL,
  `movement_type` varchar(255) NOT NULL,
  `source_module` varchar(255) NOT NULL,
  `source_type` varchar(255) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `source_event` varchar(255) NOT NULL,
  `quantity_before` decimal(20,6) NOT NULL,
  `quantity_change` decimal(20,6) NOT NULL,
  `quantity_after` decimal(20,6) NOT NULL,
  `inventory_value_before` decimal(20,4) NOT NULL,
  `inventory_value_change` decimal(20,4) NOT NULL,
  `inventory_value_after` decimal(20,4) NOT NULL,
  `average_cost_before` decimal(20,8) NOT NULL,
  `movement_unit_cost` decimal(20,8) NOT NULL,
  `average_cost_after` decimal(20,8) NOT NULL,
  `reversal_of_id` bigint(20) unsigned DEFAULT NULL,
  `valued_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inventory_valuations_stock_movement_id_unique` (`stock_movement_id`),
  UNIQUE KEY `inv_val_company_product_seq_uq` (`company_id`,`product_id`,`valuation_sequence`),
  KEY `inventory_valuations_product_id_foreign` (`product_id`),
  KEY `inventory_valuations_reversal_of_id_foreign` (`reversal_of_id`),
  KEY `inventory_valuations_company_id_product_id_index` (`company_id`,`product_id`),
  KEY `inventory_valuations_company_id_product_id_valued_at_index` (`company_id`,`product_id`,`valued_at`),
  KEY `inv_val_source_lookup_idx` (`company_id`,`source_type`,`source_id`,`source_event`),
  CONSTRAINT `inventory_valuations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `inventory_valuations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_valuations_reversal_of_id_foreign` FOREIGN KEY (`reversal_of_id`) REFERENCES `inventory_valuations` (`id`),
  CONSTRAINT `inventory_valuations_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_audit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `journal_id` bigint(20) unsigned NOT NULL,
  `event` varchar(40) NOT NULL,
  `previous_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `actor_id` bigint(20) unsigned NOT NULL,
  `event_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  KEY `journal_audit_events_financial_year_id_foreign` (`financial_year_id`),
  KEY `journal_audit_events_journal_id_foreign` (`journal_id`),
  KEY `journal_audit_events_actor_id_foreign` (`actor_id`),
  KEY `journal_audit_company_fy_date_idx` (`company_id`,`financial_year_id`,`event_at`),
  CONSTRAINT `journal_audit_events_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`),
  CONSTRAINT `journal_audit_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `journal_audit_events_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `journal_audit_events_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `journal_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `chart_account_id` bigint(20) unsigned DEFAULT NULL,
  `sub_ledger_type` varchar(20) DEFAULT NULL,
  `sub_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('debit','credit') NOT NULL,
  `amount` decimal(20,4) NOT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `description` text DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `line_number` int(10) unsigned DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `journal_items_chart_account_id_index` (`chart_account_id`),
  KEY `journal_items_account_id_foreign` (`account_id`),
  KEY `journal_items_journal_line_idx` (`journal_id`,`line_number`),
  CONSTRAINT `journal_items_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `journal_items_chart_account_fk` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `journal_items_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journal_number_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_number_sequences` (
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `next_number` bigint(20) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`company_id`,`financial_year_id`),
  KEY `journal_number_sequences_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `journal_number_sequences_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `journal_number_sequences_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `journal_no` varchar(255) NOT NULL,
  `journal_date` date NOT NULL,
  `journal_type` varchar(30) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `source_module` varchar(255) DEFAULT NULL,
  `source_type` varchar(255) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `source_key` varchar(255) DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `total_amount` decimal(20,4) NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` longtext DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `reversal_of_journal_id` bigint(20) unsigned DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `lock_reason` text DEFAULT NULL,
  `unlocked_by` bigint(20) unsigned DEFAULT NULL,
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `unlock_reason` text DEFAULT NULL,
  `legacy_classification` varchar(20) DEFAULT NULL,
  `legacy_classification_reason` text DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `journals_company_fy_number_unique` (`company_id`,`financial_year_id`,`journal_no`),
  UNIQUE KEY `journals_company_source_unique` (`company_id`,`source_key`),
  UNIQUE KEY `journals_company_request_unique` (`company_id`,`request_key`),
  UNIQUE KEY `journals_one_reversal_unique` (`reversal_of_journal_id`),
  KEY `journals_reversal_of_journal_id_index` (`reversal_of_journal_id`),
  KEY `journals_financial_year_id_foreign` (`financial_year_id`),
  KEY `journals_submitted_by_foreign` (`submitted_by`),
  KEY `journals_approved_by_foreign` (`approved_by`),
  KEY `journals_rejected_by_foreign` (`rejected_by`),
  KEY `journals_locked_by_foreign` (`locked_by`),
  KEY `journals_unlocked_by_foreign` (`unlocked_by`),
  KEY `journals_company_fy_date_status_idx` (`company_id`,`financial_year_id`,`journal_date`,`status`),
  CONSTRAINT `journals_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `journals_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `journals_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `journals_unlocked_by_foreign` FOREIGN KEY (`unlocked_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_no` varchar(255) NOT NULL,
  `request_key` char(36) DEFAULT NULL,
  `loan_name` varchar(255) NOT NULL,
  `loan_type` enum('taken','given') NOT NULL,
  `party_account_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `principal_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remaining_principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_payment_date` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_accounts_company_id_loan_no_unique` (`company_id`,`loan_no`),
  UNIQUE KEY `loan_accounts_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_accounts_company_id_index` (`company_id`),
  KEY `loan_accounts_financial_year_id_index` (`financial_year_id`),
  KEY `loan_accounts_status_index` (`status`),
  KEY `loan_accounts_party_account_id_index` (`party_account_id`),
  KEY `loan_accounts_account_id_index` (`account_id`),
  KEY `loan_accounts_updated_by_foreign` (`updated_by`),
  KEY `loan_accounts_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `loan_accounts_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_accounts_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_accounts_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_accounts_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_accounts_party_fk` FOREIGN KEY (`party_account_id`) REFERENCES `party_accounts` (`id`),
  CONSTRAINT `loan_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_integrity_seeded_chart_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_integrity_seeded_chart_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `system_code` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_integrity_seeded_chart_accounts_chart_account_id_unique` (`chart_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_account_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `next_payment_date` date DEFAULT NULL,
  `principal_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `interest_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `fine_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `saving_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remaining_principal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_payments_company_reference_unique` (`company_id`,`reference_no`),
  UNIQUE KEY `loan_payments_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_payments_financial_year_id_index` (`financial_year_id`),
  KEY `loan_payments_updated_by_foreign` (`updated_by`),
  KEY `loan_payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `loan_payments_loan_fk` (`loan_account_id`),
  KEY `loan_payments_account_fk` (`account_id`),
  CONSTRAINT `loan_payments_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_payments_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_payments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_payments_loan_fk` FOREIGN KEY (`loan_account_id`) REFERENCES `loan_accounts` (`id`),
  CONSTRAINT `loan_payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `loan_saving_ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_saving_ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `loan_account_id` bigint(20) unsigned NOT NULL,
  `loan_payment_id` bigint(20) unsigned DEFAULT NULL,
  `request_key` char(36) DEFAULT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `type` enum('deposit','withdraw','loan_settlement','reversal') NOT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(18,2) NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_date` date DEFAULT NULL,
  `cancel_reason` varchar(500) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_saving_company_request_unique` (`company_id`,`request_key`),
  KEY `loan_saving_ledgers_financial_year_id_index` (`financial_year_id`),
  KEY `loan_saving_ledgers_updated_by_foreign` (`updated_by`),
  KEY `loan_saving_ledgers_cancelled_by_foreign` (`cancelled_by`),
  KEY `loan_saving_loan_fk` (`loan_account_id`),
  KEY `loan_saving_payment_fk` (`loan_payment_id`),
  KEY `loan_saving_account_fk` (`account_id`),
  CONSTRAINT `loan_saving_account_fk` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  CONSTRAINT `loan_saving_company_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `loan_saving_ledgers_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_saving_ledgers_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `loan_saving_ledgers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `loan_saving_loan_fk` FOREIGN KEY (`loan_account_id`) REFERENCES `loan_accounts` (`id`),
  CONSTRAINT `loan_saving_payment_fk` FOREIGN KEY (`loan_payment_id`) REFERENCES `loan_payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opening_balance_audit_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opening_balance_audit_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `opening_balance_id` bigint(20) unsigned NOT NULL,
  `event` varchar(40) NOT NULL,
  `previous_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `occurred_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opening_balance_audit_events_financial_year_id_foreign` (`financial_year_id`),
  KEY `opening_balance_audit_events_opening_balance_id_foreign` (`opening_balance_id`),
  KEY `opening_balance_audit_events_user_id_foreign` (`user_id`),
  KEY `ob_audit_company_fy_idx` (`company_id`,`financial_year_id`,`occurred_at`),
  CONSTRAINT `opening_balance_audit_events_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `opening_balance_audit_events_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `opening_balance_audit_events_opening_balance_id_foreign` FOREIGN KEY (`opening_balance_id`) REFERENCES `opening_balances` (`id`),
  CONSTRAINT `opening_balance_audit_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opening_balance_legacy_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opening_balance_legacy_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(255) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `stored_amount` decimal(20,4) NOT NULL,
  `classification` varchar(30) NOT NULL,
  `evidence` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_legacy_source_unique` (`source_table`,`source_id`),
  KEY `ob_legacy_company_class_idx` (`company_id`,`classification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opening_balance_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opening_balance_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `opening_balance_id` bigint(20) unsigned NOT NULL,
  `chart_account_id` bigint(20) unsigned NOT NULL,
  `operational_account_id` bigint(20) unsigned DEFAULT NULL,
  `line_number` int(10) unsigned NOT NULL,
  `debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subledger_type` varchar(30) DEFAULT NULL,
  `subledger_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `line_reference` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `exchange_rate` decimal(20,8) DEFAULT NULL,
  `base_debit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `base_credit` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_line_number_unique` (`opening_balance_id`,`line_number`),
  KEY `opening_balance_lines_chart_account_id_foreign` (`chart_account_id`),
  KEY `opening_balance_lines_operational_account_id_foreign` (`operational_account_id`),
  KEY `ob_line_subledger_idx` (`subledger_type`,`subledger_id`),
  CONSTRAINT `opening_balance_lines_chart_account_id_foreign` FOREIGN KEY (`chart_account_id`) REFERENCES `chart_accounts` (`id`),
  CONSTRAINT `opening_balance_lines_opening_balance_id_foreign` FOREIGN KEY (`opening_balance_id`) REFERENCES `opening_balances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opening_balance_lines_operational_account_id_foreign` FOREIGN KEY (`operational_account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opening_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opening_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `type` varchar(30) NOT NULL,
  `reference_number` varchar(255) NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'draft',
  `active_key` varchar(20) DEFAULT 'active',
  `request_key` char(36) NOT NULL,
  `journal_id` bigint(20) unsigned DEFAULT NULL,
  `accounting_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `submitted_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `posted_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `reversed_by` bigint(20) unsigned DEFAULT NULL,
  `locked_by` bigint(20) unsigned DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reversed_at` timestamp NULL DEFAULT NULL,
  `locked_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `lock_reason` text DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ob_company_request_unique` (`company_id`,`request_key`),
  UNIQUE KEY `ob_company_fy_reference_unique` (`company_id`,`financial_year_id`,`reference_number`),
  UNIQUE KEY `ob_company_fy_active_unique` (`company_id`,`financial_year_id`,`active_key`),
  UNIQUE KEY `ob_journal_unique` (`journal_id`),
  UNIQUE KEY `ob_accounting_entry_unique` (`accounting_entry_id`),
  KEY `opening_balances_financial_year_id_foreign` (`financial_year_id`),
  KEY `opening_balances_created_by_foreign` (`created_by`),
  KEY `opening_balances_submitted_by_foreign` (`submitted_by`),
  KEY `opening_balances_approved_by_foreign` (`approved_by`),
  KEY `opening_balances_posted_by_foreign` (`posted_by`),
  KEY `opening_balances_cancelled_by_foreign` (`cancelled_by`),
  KEY `opening_balances_reversed_by_foreign` (`reversed_by`),
  KEY `opening_balances_locked_by_foreign` (`locked_by`),
  KEY `ob_company_fy_status_idx` (`company_id`,`financial_year_id`,`status`),
  KEY `ob_company_date_idx` (`company_id`,`business_date`),
  CONSTRAINT `opening_balances_accounting_entry_id_foreign` FOREIGN KEY (`accounting_entry_id`) REFERENCES `accounting_entries` (`id`),
  CONSTRAINT `opening_balances_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `opening_balances_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `opening_balances_journal_id_foreign` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`),
  CONSTRAINT `opening_balances_locked_by_foreign` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_posted_by_foreign` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_reversed_by_foreign` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `opening_balances_submitted_by_foreign` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `party_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `party_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `account_no` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `type` enum('bank','person','customer','supplier','company','other') NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  KEY `permission_role_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `scope` varchar(20) NOT NULL DEFAULT 'company',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_payment_gateways` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `gateway` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `environment` varchar(20) NOT NULL DEFAULT 'sandbox',
  `public_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL,
  `merchant_id` varchar(255) DEFAULT NULL,
  `webhook_secret` text DEFAULT NULL,
  `additional_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_config`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_gateway_setting_gateway_unique` (`platform_setting_id`,`gateway`),
  CONSTRAINT `platform_payment_gateways_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_name` varchar(150) NOT NULL,
  `legal_company_name` varchar(200) DEFAULT NULL,
  `owner_name` varchar(150) NOT NULL,
  `primary_email` varchar(190) NOT NULL,
  `primary_mobile` varchar(30) NOT NULL,
  `alternate_mobile` varchar(30) DEFAULT NULL,
  `support_email` varchar(190) DEFAULT NULL,
  `support_mobile` varchar(30) DEFAULT NULL,
  `whatsapp_number` varchar(30) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `state_province` varchar(100) DEFAULT NULL,
  `district_city` varchar(100) DEFAULT NULL,
  `municipality` varchar(100) DEFAULT NULL,
  `ward_number` varchar(30) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `full_address` text DEFAULT NULL,
  `tax_number` varchar(100) DEFAULT NULL,
  `vat_number` varchar(100) DEFAULT NULL,
  `company_registration_number` varchar(100) DEFAULT NULL,
  `business_license_number` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `profile_photo_path` varchar(255) DEFAULT NULL,
  `timezone` varchar(100) NOT NULL DEFAULT 'Asia/Kathmandu',
  `currency_code` varchar(3) NOT NULL DEFAULT 'NPR',
  `language_code` varchar(10) NOT NULL DEFAULT 'en',
  `date_format` varchar(30) NOT NULL DEFAULT 'Y-m-d',
  `time_format` varchar(30) NOT NULL DEFAULT 'H:i',
  `default_trial_days` int(10) unsigned NOT NULL DEFAULT 0,
  `default_staff_limit` int(10) unsigned DEFAULT NULL,
  `default_customer_limit` int(10) unsigned DEFAULT NULL,
  `default_product_limit` int(10) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `platform_settings_created_by_foreign` (`created_by`),
  KEY `platform_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `platform_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `platform_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_smtp_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_smtp_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `mailer` varchar(50) NOT NULL DEFAULT 'smtp',
  `host` varchar(255) NOT NULL,
  `port` smallint(5) unsigned NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` text DEFAULT NULL,
  `encryption` varchar(20) DEFAULT NULL,
  `from_address` varchar(190) NOT NULL,
  `from_name` varchar(150) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `last_tested_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_smtp_settings_platform_setting_id_unique` (`platform_setting_id`),
  CONSTRAINT `platform_smtp_settings_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `platform_social_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `platform_setting_id` bigint(20) unsigned NOT NULL,
  `provider` varchar(60) NOT NULL,
  `url` varchar(500) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform_social_setting_provider_unique` (`platform_setting_id`,`provider`),
  CONSTRAINT `platform_social_links_platform_setting_id_foreign` FOREIGN KEY (`platform_setting_id`) REFERENCES `platform_settings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_categories_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `brand_id` bigint(20) unsigned DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_no` varchar(255) DEFAULT NULL,
  `allow_online` tinyint(1) NOT NULL DEFAULT 0,
  `unit_id` bigint(20) unsigned NOT NULL,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `retail_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wholesale_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `current_stock` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_alert` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_company_id_foreign` (`company_id`),
  KEY `products_unit_id_foreign` (`unit_id`),
  KEY `products_vat_id_foreign` (`vat_id`),
  KEY `products_brand_id_foreign` (`brand_id`),
  CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`),
  CONSTRAINT `products_vat_id_foreign` FOREIGN KEY (`vat_id`) REFERENCES `vats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_invoices_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `purchase_invoices_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `item_type` enum('product','service') NOT NULL DEFAULT 'product',
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL,
  `returned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `price` decimal(10,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_vat_id_foreign` (`vat_id`),
  CONSTRAINT `purchase_items_vat_id_foreign` FOREIGN KEY (`vat_id`) REFERENCES `vats` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `payment_no` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `purchase_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `vat_rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_return_items_purchase_return_id_purchase_item_id_index` (`purchase_return_id`,`purchase_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_return_refund_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_refund_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `purchase_return_refund_id` bigint(20) unsigned NOT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `adjust_amount` decimal(18,2) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prra_cmp_idx` (`company_id`),
  KEY `prra_refund_idx` (`purchase_return_refund_id`),
  KEY `prra_invoice_idx` (`purchase_invoice_id`),
  CONSTRAINT `prra_cmp_fk` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prra_invoice_fk` FOREIGN KEY (`purchase_invoice_id`) REFERENCES `purchase_invoices` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prra_refund_fk` FOREIGN KEY (`purchase_return_refund_id`) REFERENCES `purchase_return_refunds` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_return_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_return_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_return_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` char(36) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `refund_date` date NOT NULL,
  `refund_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `refund_no` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_return_refunds_company_idempotency_unique` (`company_id`,`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `purchase_invoice_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `return_no` varchar(255) NOT NULL,
  `request_key` char(36) DEFAULT NULL,
  `return_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `damage_photo` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_returns_company_request_unique` (`company_id`,`request_key`),
  KEY `purchase_returns_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `purchase_returns_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `quotation_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `item_type` varchar(20) NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `quantity` decimal(20,4) NOT NULL,
  `unit_price` decimal(20,4) NOT NULL,
  `vat_rate` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `vat_amount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_price` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotation_items_company_id_foreign` (`company_id`),
  KEY `quotation_items_product_id_foreign` (`product_id`),
  KEY `quotation_items_service_id_foreign` (`service_id`),
  KEY `quotation_items_quotation_id_item_type_index` (`quotation_id`,`item_type`),
  CONSTRAINT `quotation_items_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `quotation_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `quotation_items_quotation_id_foreign` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotation_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `quotation_no` varchar(50) NOT NULL,
  `quotation_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `subtotal` decimal(20,4) NOT NULL,
  `discount` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_vat` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `grand_total` decimal(20,4) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `converted_by` bigint(20) unsigned DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotations_company_id_financial_year_id_quotation_no_unique` (`company_id`,`financial_year_id`,`quotation_no`),
  UNIQUE KEY `quotations_sales_invoice_id_unique` (`sales_invoice_id`),
  KEY `quotations_financial_year_id_foreign` (`financial_year_id`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  KEY `quotations_created_by_foreign` (`created_by`),
  KEY `quotations_approved_by_foreign` (`approved_by`),
  KEY `quotations_converted_by_foreign` (`converted_by`),
  KEY `quotations_company_id_status_quotation_date_index` (`company_id`,`status`,`quotation_date`),
  CONSTRAINT `quotations_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `quotations_converted_by_foreign` FOREIGN KEY (`converted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `quotations_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`),
  CONSTRAINT `quotations_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `employee_id` bigint(20) unsigned NOT NULL,
  `salary_month` varchar(20) NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `working_days` int(11) NOT NULL DEFAULT 30,
  `present_days` int(11) NOT NULL DEFAULT 30,
  `absent_days` int(11) NOT NULL DEFAULT 0,
  `allowance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overtime_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_cost_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_cost_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `stock_movement_id` bigint(20) unsigned NOT NULL,
  `inventory_valuation_id` bigint(20) unsigned NOT NULL,
  `average_cost_used` decimal(20,8) NOT NULL,
  `movement_unit_cost` decimal(20,8) NOT NULL,
  `movement_value` decimal(20,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_cost_snapshots_sales_item_id_unique` (`sales_item_id`),
  UNIQUE KEY `sales_cost_snapshots_stock_movement_id_unique` (`stock_movement_id`),
  KEY `sales_cost_snapshots_sales_invoice_id_foreign` (`sales_invoice_id`),
  KEY `sales_cost_snapshots_product_id_foreign` (`product_id`),
  KEY `sales_cost_snapshots_inventory_valuation_id_foreign` (`inventory_valuation_id`),
  KEY `sales_cost_snapshots_company_id_sales_invoice_id_index` (`company_id`,`sales_invoice_id`),
  KEY `sales_cost_snapshots_company_id_product_id_index` (`company_id`,`product_id`),
  CONSTRAINT `sales_cost_snapshots_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `sales_cost_snapshots_inventory_valuation_id_foreign` FOREIGN KEY (`inventory_valuation_id`) REFERENCES `inventory_valuations` (`id`),
  CONSTRAINT `sales_cost_snapshots_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sales_cost_snapshots_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`),
  CONSTRAINT `sales_cost_snapshots_sales_item_id_foreign` FOREIGN KEY (`sales_item_id`) REFERENCES `sales_items` (`id`),
  CONSTRAINT `sales_cost_snapshots_stock_movement_id_foreign` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `invoice_no` varchar(255) NOT NULL,
  `sale_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','partial','unpaid') NOT NULL DEFAULT 'unpaid',
  `note` text DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoices_company_fy_invoice_no_unique` (`company_id`,`financial_year_id`,`invoice_no`),
  KEY `sales_invoices_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_invoices_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `item_type` enum('product','service') NOT NULL DEFAULT 'product',
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `returned_qty` decimal(15,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(15,2) NOT NULL,
  `vat_rate` decimal(8,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned NOT NULL,
  `payment_no` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_payments_company_fy_payment_no_unique` (`company_id`,`financial_year_id`,`payment_no`),
  KEY `sales_payments_sales_invoice_id_foreign` (`sales_invoice_id`),
  KEY `sales_payments_customer_id_foreign` (`customer_id`),
  KEY `sales_payments_account_id_foreign` (`account_id`),
  KEY `sales_payments_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_payments_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_payments_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `sales_item_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `service_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vat_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `vat_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sales_return_items_sales_return_id_sales_item_id_index` (`sales_return_id`,`sales_item_id`),
  KEY `sales_return_items_financial_year_id_index` (`financial_year_id`),
  KEY `sales_return_items_service_id_foreign` (`service_id`),
  CONSTRAINT `sales_return_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_return_refund_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_refund_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `sales_return_refund_id` bigint(20) unsigned NOT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `adjust_amount` decimal(18,2) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_return_refund_adjustments_company_id_index` (`company_id`),
  KEY `sales_return_refund_adjustments_sales_return_refund_id_index` (`sales_return_refund_id`),
  KEY `sales_return_refund_adjustments_sales_invoice_id_index` (`sales_invoice_id`),
  CONSTRAINT `sales_return_refund_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `sales_return_refund_adjustments_sales_invoice_id_foreign` FOREIGN KEY (`sales_invoice_id`) REFERENCES `sales_invoices` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `sales_return_refund_adjustments_sales_return_refund_id_foreign` FOREIGN KEY (`sales_return_refund_id`) REFERENCES `sales_return_refunds` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_return_refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `idempotency_key` char(36) DEFAULT NULL,
  `refund_no` varchar(255) NOT NULL,
  `refund_date` date NOT NULL,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `cash_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `deleted_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_return_refunds_company_fy_refund_no_unique` (`company_id`,`financial_year_id`,`refund_no`),
  UNIQUE KEY `sales_return_refunds_company_idempotency_key_unique` (`company_id`,`idempotency_key`),
  KEY `sales_return_refunds_sales_return_id_foreign` (`sales_return_id`),
  KEY `sales_return_refunds_customer_id_foreign` (`customer_id`),
  KEY `sales_return_refunds_account_id_foreign` (`account_id`),
  KEY `sales_return_refunds_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_return_refunds_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_refunds_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_refunds_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_return_refunds_sales_return_id_foreign` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `sales_invoice_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `return_no` varchar(255) NOT NULL,
  `return_date` date NOT NULL,
  `subtotal` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_vat` decimal(18,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `adjust_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `damage_photo` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_returns_company_fy_return_no_unique` (`company_id`,`financial_year_id`,`return_no`),
  KEY `sales_returns_financial_year_id_foreign` (`financial_year_id`),
  CONSTRAINT `sales_returns_financial_year_id_foreign` FOREIGN KEY (`financial_year_id`) REFERENCES `financial_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `upload_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_categories_company_id_index` (`company_id`),
  KEY `service_categories_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `service_category_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `service_code` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `vat_id` bigint(20) unsigned DEFAULT NULL,
  `upload_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_company_id_index` (`company_id`),
  KEY `services_service_category_id_index` (`service_category_id`),
  KEY `services_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `before_stock` int(11) NOT NULL DEFAULT 0,
  `after_stock` int(11) NOT NULL DEFAULT 0,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_transactions_product_id_foreign` (`product_id`),
  KEY `stock_transactions_company_id_foreign` (`company_id`),
  CONSTRAINT `stock_transactions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `company_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `subscription_payment_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` enum('register_trial_started','free_trial_assigned','plan_assigned','renewed','upgraded','downgraded','activated','expired','cancelled','payment_submitted','payment_approved','payment_rejected') NOT NULL,
  `subscription_type_before` enum('register_trial','free_trial','paid') DEFAULT NULL,
  `subscription_type_after` enum('register_trial','free_trial','paid') DEFAULT NULL,
  `subscription_plan_id_before` bigint(20) unsigned DEFAULT NULL,
  `subscription_plan_id_after` bigint(20) unsigned DEFAULT NULL,
  `billing_cycle_id_before` bigint(20) unsigned DEFAULT NULL,
  `billing_cycle_id_after` bigint(20) unsigned DEFAULT NULL,
  `status_before` enum('active','expired','cancelled') DEFAULT NULL,
  `status_after` enum('active','expired','cancelled') DEFAULT NULL,
  `start_date_before` date DEFAULT NULL,
  `start_date_after` date DEFAULT NULL,
  `expiry_date_before` date DEFAULT NULL,
  `expiry_date_after` date DEFAULT NULL,
  `staff_limit_before` int(10) unsigned DEFAULT NULL,
  `staff_limit_after` int(10) unsigned DEFAULT NULL,
  `hidden_modules_before` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules_before`)),
  `hidden_modules_after` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules_after`)),
  `performed_by` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `event_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_histories_performed_by_foreign` (`performed_by`),
  KEY `idx_sh_company_event_at` (`company_id`,`event_at`),
  KEY `idx_sh_subscription_event_at` (`company_subscription_id`,`event_at`),
  KEY `idx_sh_event_type_event_at` (`event_type`,`event_at`),
  KEY `idx_sh_payment_id` (`subscription_payment_id`),
  KEY `idx_sh_event_at` (`event_at`),
  CONSTRAINT `subscription_histories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `subscription_histories_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`),
  CONSTRAINT `subscription_histories_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_histories_subscription_payment_id_foreign` FOREIGN KEY (`subscription_payment_id`) REFERENCES `subscription_payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned DEFAULT NULL,
  `action_type` enum('assign','renew','upgrade','downgrade') NOT NULL DEFAULT 'assign',
  `amount` int(11) NOT NULL,
  `currency_code` char(3) NOT NULL DEFAULT 'NPR',
  `payment_method` varchar(255) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `company_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `target_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` bigint(20) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_company_id_foreign` (`company_id`),
  KEY `subscription_payments_subscription_plan_id_foreign` (`subscription_plan_id`),
  KEY `subscription_payments_billing_cycle_id_foreign` (`billing_cycle_id`),
  KEY `subscription_payments_company_subscription_id_foreign` (`company_subscription_id`),
  KEY `subscription_payments_target_subscription_id_foreign` (`target_subscription_id`),
  KEY `subscription_payments_verified_by_foreign` (`verified_by`),
  KEY `subscription_payments_approved_by_foreign` (`approved_by`),
  KEY `subscription_payments_rejected_by_foreign` (`rejected_by`),
  KEY `subscription_payments_cancelled_by_foreign` (`cancelled_by`),
  KEY `subscription_payments_created_by_foreign` (`created_by`),
  KEY `subscription_payments_updated_by_foreign` (`updated_by`),
  CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subscription_payments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `subscription_payments_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_company_subscription_id_foreign` FOREIGN KEY (`company_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `subscription_payments_target_subscription_id_foreign` FOREIGN KEY (`target_subscription_id`) REFERENCES `company_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plan_billing_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plan_billing_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_plan_id` bigint(20) unsigned NOT NULL,
  `billing_cycle_id` bigint(20) unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency_code` char(3) NOT NULL DEFAULT 'NPR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plan_billing_option` (`subscription_plan_id`,`billing_cycle_id`),
  KEY `subscription_plan_billing_options_created_by_foreign` (`created_by`),
  KEY `subscription_plan_billing_options_updated_by_foreign` (`updated_by`),
  KEY `idx_spbo_plan_active` (`subscription_plan_id`,`is_active`),
  KEY `idx_spbo_cycle_active` (`billing_cycle_id`,`is_active`),
  CONSTRAINT `subscription_plan_billing_options_billing_cycle_id_foreign` FOREIGN KEY (`billing_cycle_id`) REFERENCES `billing_cycles` (`id`),
  CONSTRAINT `subscription_plan_billing_options_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plan_billing_options_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `subscription_plan_billing_options_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `staff_limit` int(11) NOT NULL,
  `hidden_modules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hidden_modules`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` bigint(20) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscription_plans_code` (`code`),
  KEY `subscription_plans_created_by_foreign` (`created_by`),
  KEY `subscription_plans_updated_by_foreign` (`updated_by`),
  KEY `subscription_plans_cancelled_by_foreign` (`cancelled_by`),
  CONSTRAINT `subscription_plans_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `subscription_plans_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `financial_year_id` bigint(20) unsigned NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `transaction_date` date NOT NULL,
  `voucher_no` varchar(255) DEFAULT NULL,
  `reference_type` varchar(50) NOT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `journal_item_id` bigint(20) unsigned DEFAULT NULL,
  `reversed_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(18,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(255) DEFAULT NULL,
  `reference_no` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `st_company_journal_unique` (`company_id`,`journal_item_id`),
  UNIQUE KEY `st_reversal_unique` (`reversed_transaction_id`),
  KEY `supplier_transactions_company_id_supplier_id_index` (`company_id`,`supplier_id`),
  KEY `supplier_transactions_financial_year_id_index` (`financial_year_id`),
  KEY `supplier_transactions_transaction_date_index` (`transaction_date`),
  KEY `supplier_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `supplier_transactions_voucher_no_index` (`voucher_no`),
  KEY `supplier_transactions_journal_item_id_index` (`journal_item_id`),
  KEY `supplier_transactions_reversed_transaction_id_index` (`reversed_transaction_id`),
  CONSTRAINT `st_journal_item_fk` FOREIGN KEY (`journal_item_id`) REFERENCES `journal_items` (`id`),
  CONSTRAINT `st_reversal_fk` FOREIGN KEY (`reversed_transaction_id`) REFERENCES `supplier_transactions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `authority_name` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `fax_no` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_no` varchar(255) DEFAULT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(10) unsigned NOT NULL DEFAULT 0,
  `current_balance` decimal(18,2) NOT NULL DEFAULT 0.00,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_no` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `short_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `units_company_id_foreign` (`company_id`),
  CONSTRAINT `units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  `is_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_permissions_user_id_permission_id_unique` (`user_id`,`permission_id`),
  KEY `user_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `user_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `job_role` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `account_status` varchar(255) NOT NULL DEFAULT 'active',
  `online_status` varchar(255) NOT NULL DEFAULT 'offline',
  `last_seen` timestamp NULL DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vats` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0000_01_00_000000_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_04_18_210002_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_04_18_213856_add_company_id_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_19_192156_create_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_21_193133_create_company_registrations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_21_195922_create_permission_role_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_04_23_113559_create_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_24_102707_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_25_102745_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_26_145123_add_login_tracking_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_04_29_180400_add_customer_limit_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_04_29_191440_add_customer_limit_to_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_04_30_155615_create_units_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_04_30_163735_add_job_role_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_04_30_192120_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_30_192619_create_stock_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_30_193112_create_purchase_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_30_193316_create_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_30_201211_create_vats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_05_01_181631_create_product_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_05_01_200417_change_status_column_in_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_05_03_080948_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_05_03_082020_create_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_05_04_194310_add_last_seen_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_05_08_172230_create_banks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_05_08_192337_create_cash_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_05_08_202223_add_vat_id_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_05_08_202340_update_purchase_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_05_08_202459_update_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_05_08_213939_add_status_to_vats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_05_08_230711_make_vat_id_nullable_in_purchase_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_05_08_230935_add_current_stock_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_05_09_224736_create_sales_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_05_09_224748_create_sales_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_05_10_221903_make_product_id_nullable_in_sales_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_05_11_110402_create_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_05_11_165729_create_sales_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_05_11_165823_create_sales_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_05_11_182734_add_vat_columns_to_sales_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_05_11_184543_change_type_column_in_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_05_11_184930_add_damage_photo_to_sales_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_05_12_200108_create_service_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_05_12_201134_create_services_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_05_16_210325_add_service_fields_to_sales_invoice_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_05_18_172622_add_return_qty_to_sales_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_05_18_181037_add_account_type_to_banks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_05_18_1818013_rename_banks_to_accounts_and_add_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_05_18_191046_add_missing_fields_to_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_05_18_191730_make_account_no_nullable_in_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_05_18_192558_create_invoice_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_05_19_170922_create_purchase_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_05_19_171040_create_purchase_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_05_19_184555_create_purchase_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_05_19_204435_add_account_id_to_purchase_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_05_19_221810_add_current_balance_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_05_21_194142_create_purchase_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_05_22_163057_add_current_balance_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_05_22_212326_update_sales_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_05_23_073758_create_sales_returnrefund_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_05_23_101004_create_sales_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_06_04_215243_add_refund_no_to_purchase_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_06_05_071924_create_expense_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_06_05_071926_create_expenses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_06_05_115139_create_loan_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_06_05_115143_create_loan_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_06_05_171701_create_party_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_06_05_201944_create_loan_saving_ledgers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_06_06_124251_create_employee_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_06_06_175635_create_incomes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_06_06_195111_create_income_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_06_06_203315_create_journals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_06_06_203826_create_journal_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_06_07_102935_create_financial_years_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_06_07_103038_add_financial_year_type_to_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_06_07_113903_add_financial_year_id_to_journals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_06_07_152632_add_financial_year_id_to_incomes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_06_08_120226_add_financial_year_id_to_expenses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_06_08_192433_add_financial_year_id_to_purchases_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_06_09_153623_add_financial_year_id_to_purchase_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_06_09_171658_add_financial_year_id_to_purchase_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_06_09_174405_add_financial_year_id_to_purchase_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_06_10_081606_add_financial_year_id_to_sales_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_06_10_112145_add_financial_year_id_to_sales_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_06_10_132037_add_financial_year_id_to_sales_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_06_10_145104_add_financial_year_id_to_sales_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_06_12_084435_create_contras_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_06_15_152110_create_salary_sheets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_06_16_181743_create_employee_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_06_16_201814_create_account_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_06_19_155736_add_status_to_purchase_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_06_23_164000_add_financial_year_and_status_to_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_06_23_164443_add_financial_year_and_status_to_purchase_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_06_24_171529_add_financial_year_id_to_sales_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_06_24_204634_add_financial_year_id_to_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_06_25_172944_add_refund_and_adjust_amount_to_purchase_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_06_28_202233_add_transaction_date_to_stock_movements_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_06_29_192830_create_supplier_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_06_30_082920_add_payment_method_and_attachment_to_purchase_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_07_01_094256_create_customer_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_07_01_141445_add_sales_item_id_to_sales_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_07_01_154246_add_purchase_item_id_to_purchase_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_07_01_163827_add_missing_fields_to_sales_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_07_01_171406_add_adjust_and_refund_amount_to_sales_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_07_04_185333_add_created_by_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_07_11_224141_create_brands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_07_11_224906_add_product_online_fields_to_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_07_12_152127_add_extra_fields_to_sales_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_07_13_084841_alter_sales_return_refunds_table_add_adjustment_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_07_13_090657_remove_payment_method_from_sales_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_07_13_094204_create_sales_return_refund_adjustments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_07_13_211122_alter_sales_return_refunds_make_account_nullable',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_07_14_194334_alter_sales_return_items_for_service_return',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_07_17_210000_fix_sales_module_uniques_and_returned_qty',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_07_18_181500_fix_sales_invoice_return_uniques_and_status',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_07_19_110000_add_credit_days_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_07_19_110100_add_due_date_to_sales_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_07_20_110000_add_due_date_to_purchase_invoices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_07_20_110100_add_returned_qty_to_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_07_20_120000_add_service_fields_to_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_07_20_120101_make_product_id_nullable_in_purchase_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_07_20_120150_add_service_id_to_purchase_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_07_20_120200_add_damage_photo_to_purchase_returns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_07_20_120300_add_credit_days_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_07_20_120400_add_audit_fields_to_purchase_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_07_20_120500_alter_purchase_return_refunds_foundation_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_07_20_120600_create_purchase_return_refund_adjustments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_07_20_120710_backfill_purchase_invoice_due_dates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_07_21_140000_add_refund_amount_to_purchase_return_refunds_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_07_21_160000_add_updated_by_to_transaction_documentation_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_07_21_170000_add_created_by_to_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_07_21_180000_make_product_id_nullable_on_purchase_return_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_07_21_200000_upgrade_incomes_table_for_income_module_standard',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_07_22_200000_upgrade_expenses_table_for_expense_module_standard',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_07_22_210000_upgrade_journals_table_for_journal_module_standard',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_07_22_220000_add_sub_ledger_support_to_journal_module',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_07_22_230000_upgrade_party_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_07_22_235000_upgrade_loan_accounts_table_for_foundation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_07_22_240000_make_account_id_nullable_on_loan_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_07_22_250000_add_financial_year_and_next_payment_date_to_loan_payment_modules',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_07_22_260000_add_audit_fields_to_loan_payment_modules',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_07_22_270000_add_soft_deletes_to_loan_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_07_23_080000_refactor_loan_accounts_cancel_instead_of_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_07_23_140000_harden_employee_accounts_for_production',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_07_23_150000_harden_salary_sheets_for_production',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_07_23_160000_salary_payment_foundation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_07_23_170000_harden_employee_payments_constitution_compliance',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_07_24_100000_create_delivery_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_07_24_100100_create_delivery_note_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_07_24_100200_create_delivery_status_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_07_24_110000_add_planned_qty_and_completion_fields_to_delivery_module',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_07_24_110100_create_delivery_signatures_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_07_24_110200_create_delivery_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_07_24_120000_add_financial_year_and_remove_processing_from_delivery_notes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_07_24_130000_create_crm_configurations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_07_24_130100_create_crm_leads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_07_24_130200_create_crm_opportunities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_07_24_130300_create_crm_follow_ups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_07_24_130400_create_crm_meetings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_07_24_130500_create_crm_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_07_24_130600_create_crm_notes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_07_24_130700_create_crm_attachments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_07_24_130800_create_crm_status_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_07_24_130900_add_crm_lead_id_to_customers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_07_24_140000_create_crm_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_07_24_140100_add_workflow_and_fy_fields_to_crm_activity_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_07_24_140200_add_foreign_keys_to_crm_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_07_24_150000_refactor_crm_leads_use_customer_reference',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_07_24_160000_harden_crm_opportunity_customer_and_contact_person_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_07_24_170000_harden_crm_lead_customer_and_opportunity_relationship_integrity',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_07_25_180000_create_billing_cycles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_07_25_180100_rename_and_upgrade_subscription_plans_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_07_25_180200_create_subscription_plan_billing_options_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_07_25_180300_rename_and_upgrade_company_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_07_25_180400_rename_and_upgrade_subscription_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_07_25_180500_create_subscription_histories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_07_25_180600_add_expired_status_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_07_25_180700_finalize_subscription_plans_drop_legacy_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_07_25_182648_create_user_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_07_25_200000_add_scope_to_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_07_25_200100_create_company_permission_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_07_26_000000_remove_super_staff_role_permissions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_07_26_010000_create_platform_settings_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_07_27_020000_add_journal_posting_traceability',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_07_28_000000_add_account_group_to_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_07_28_000100_create_chart_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_07_28_000200_create_accounting_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_07_28_000201_create_accounting_entry_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_07_28_000202_add_chart_account_id_to_expense_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_07_28_000203_add_chart_account_id_to_income_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_07_29_000204_create_inventory_valuations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_07_29_000205_create_sales_cost_snapshots_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_07_29_000206_add_idempotency_key_to_sales_return_refunds_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_08_01_000100_add_financial_year_and_loan_integrity',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_08_01_000200_finalize_loan_financial_integrity',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_08_02_000000_create_opening_balance_module',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_08_03_000000_create_journal_phase_one_foundation',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_08_09_000000_provision_supplier_return_receivable_account',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_08_14_000000_create_country_master_and_company_relations',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_08_14_010000_create_quotations_module',9);

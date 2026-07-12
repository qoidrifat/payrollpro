-- ================================================================
-- migrate.sql — MySQL-compatible schema for Project KP
-- Generated: 2026-07-12 07:55:41
-- Target: InfinityFree MySQL (phpMyAdmin)
-- ================================================================

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = '+07:00';

-- --------------------------------------------------------
-- Table structure for `activity_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_logs`;

CREATE TABLE `activity_logs` (
  `user_id` INT NULL,
  `action` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `subject_type` VARCHAR(255) NULL,
  `subject_id` INT NULL,
  `properties` TEXT NULL,
  `ip_address` VARCHAR(255) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_activity_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `approvals`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `approvals`;

CREATE TABLE `approvals` (
  `approvable_type` VARCHAR(255) NOT NULL,
  `approvable_id` INT NOT NULL,
  `level` VARCHAR(255) NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approver_id` INT NULL,
  `comments` TEXT NULL,
  `approved_at` DATETIME NULL,
  `rejected_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_approvals_approver_id` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `attendance_selfies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `attendance_selfies`;

CREATE TABLE `attendance_selfies` (
  `attendance_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `verified_at` DATETIME NULL,
  `verification_score` DECIMAL NULL,
  `device_info` VARCHAR(255) NULL,
  `gps_latitude` DECIMAL NULL,
  `gps_longitude` DECIMAL NULL,
  `gps_accuracy` DECIMAL NULL,
  `captured_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_attendance_selfies_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_attendance_selfies_attendance_id` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `attendances`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `attendances`;

CREATE TABLE `attendances` (
  `employee_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `clock_in` TIME NULL,
  `clock_out` TIME NULL,
  `status` ENUM('present','absent','late','half_day','sick','leave') NOT NULL DEFAULT 'absent',
  `type` ENUM('wfo','wfh','remote') NOT NULL DEFAULT 'wfo',
  `notes` TEXT NULL,
  `latitude` DECIMAL NULL,
  `longitude` DECIMAL NULL,
  `created_by` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `company_id` INT NULL,
  `source` VARCHAR(255) NOT NULL DEFAULT 'qr',
  `approved_by` INT NULL,
  `approved_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_employee_id_date_unique` (`employee_id`, `date`),
  CONSTRAINT `fk_attendances_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_attendances_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_attendances_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `bpjs_configs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `bpjs_configs`;

CREATE TABLE `bpjs_configs` (
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `payer` VARCHAR(255) NOT NULL,
  `rate_percentage` DECIMAL NOT NULL,
  `salary_cap` DECIMAL NULL,
  `applicable_year` INT NOT NULL,
  `description` TEXT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cache`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cache`;

CREATE TABLE `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `cache_locks`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `cache_locks`;

CREATE TABLE `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `companies`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `companies`;

CREATE TABLE `companies` (
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `domain` VARCHAR(255) NULL,
  `database` VARCHAR(255) NULL,
  `address` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `npwp` VARCHAR(255) NULL,
  `tax_config` TEXT NULL,
  `settings` TEXT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `subscription_plan` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `employees`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `user_id` INT NULL,
  `nik` VARCHAR(255) NOT NULL,
  `npwp` VARCHAR(255) NULL,
  `bpjs_kesehatan` VARCHAR(255) NULL,
  `bpjs_ketenagakerjaan` VARCHAR(255) NULL,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NULL,
  `gender` ENUM('male','female') NOT NULL,
  `position` VARCHAR(255) NOT NULL,
  `department` VARCHAR(255) NULL,
  `join_date` DATE NOT NULL,
  `resign_date` DATE NULL,
  `employment_status` ENUM('permanent','contract','probation','intern') NOT NULL,
  `base_salary` DECIMAL NOT NULL DEFAULT '0',
  `bank_name` VARCHAR(255) NULL,
  `bank_account_number` VARCHAR(255) NULL,
  `bank_account_name` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(255) NULL,
  `province` VARCHAR(255) NULL,
  `postal_code` VARCHAR(255) NULL,
  `emergency_contact_name` VARCHAR(255) NULL,
  `emergency_contact_phone` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  `company_id` INT NULL,
  `marital_status` VARCHAR(255) NULL,
  `dependents_count` INT NOT NULL DEFAULT '0',
  `nik_hash` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employees_nik_hash_unique` (`nik_hash`),
  CONSTRAINT `fk_employees_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_employees_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `failed_jobs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` TEXT NOT NULL,
  `exception` TEXT NOT NULL,
  `failed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `holidays`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `holidays`;

CREATE TABLE `holidays` (
  `company_id` INT NULL,
  `name` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  `is_recurring` TINYINT NOT NULL DEFAULT '0',
  `is_national` TINYINT NOT NULL DEFAULT '0',
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_holidays_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `incident_service`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `incident_service`;

CREATE TABLE `incident_service` (
  `incident_id` INT NOT NULL,
  `system_service_id` INT NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`incident_id`, `system_service_id`),
  CONSTRAINT `fk_incident_service_system_service_id` FOREIGN KEY (`system_service_id`) REFERENCES `system_services` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_incident_service_incident_id` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `incident_updates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `incident_updates`;

CREATE TABLE `incident_updates` (
  `incident_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_incident_updates_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_incident_updates_incident_id` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `incidents`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `incidents`;

CREATE TABLE `incidents` (
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `severity` VARCHAR(255) NOT NULL DEFAULT 'minor',
  `status` VARCHAR(255) NOT NULL DEFAULT 'investigating',
  `affected_services` TEXT NULL,
  `started_at` DATETIME NOT NULL,
  `resolved_at` DATETIME NULL,
  `resolution_notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `incidents_slug_unique` (`slug`),
  CONSTRAINT `fk_incidents_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `job_batches`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `job_batches`;

CREATE TABLE `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` TEXT NOT NULL,
  `options` TEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `jobs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `jobs`;

CREATE TABLE `jobs` (
  `queue` VARCHAR(255) NOT NULL,
  `payload` TEXT NOT NULL,
  `attempts` INT NOT NULL,
  `reserved_at` INT NULL,
  `available_at` INT NOT NULL,
  `created_at` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `leave_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `leave_requests`;

CREATE TABLE `leave_requests` (
  `company_id` INT NULL,
  `employee_id` INT NOT NULL,
  `leave_type` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_days` INT NOT NULL,
  `reason` TEXT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `approved_at` DATETIME NULL,
  `rejection_reason` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_leave_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_leave_requests_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_leave_requests_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `maintenance_schedules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `maintenance_schedules`;

CREATE TABLE `maintenance_schedules` (
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `affected_services` TEXT NULL,
  `scheduled_start` DATETIME NOT NULL,
  `scheduled_end` DATETIME NOT NULL,
  `started_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'scheduled',
  `created_by` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_maintenance_schedules_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `manual_attendance_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `manual_attendance_requests`;

CREATE TABLE `manual_attendance_requests` (
  `company_id` INT NULL,
  `employee_id` INT NOT NULL,
  `attendance_id` INT NULL,
  `request_type` VARCHAR(255) NOT NULL,
  `requested_date` DATE NOT NULL,
  `requested_time` TIME NOT NULL,
  `reason` TEXT NOT NULL,
  `evidence_path` VARCHAR(255) NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `reviewed_by` INT NULL,
  `reviewed_at` DATETIME NULL,
  `rejection_reason` TEXT NULL,
  `source` VARCHAR(255) NOT NULL DEFAULT 'manual',
  `metadata` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_manual_attendance_requests_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_manual_attendance_requests_attendance_id` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_manual_attendance_requests_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_manual_attendance_requests_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `migrations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `migration` VARCHAR(255) NOT NULL,
  `batch` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `model_has_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `model_has_permissions`;

CREATE TABLE `model_has_permissions` (
  `permission_id` INT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` INT NOT NULL,
  PRIMARY KEY (`permission_id`, `model_type`, `model_id`),
  CONSTRAINT `fk_model_has_permissions_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `model_has_roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `model_has_roles`;

CREATE TABLE `model_has_roles` (
  `role_id` INT NOT NULL,
  `model_type` VARCHAR(255) NOT NULL,
  `model_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `model_type`, `model_id`),
  CONSTRAINT `fk_model_has_roles_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` INT NOT NULL,
  `data` TEXT NOT NULL,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `office_locations`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `office_locations`;

CREATE TABLE `office_locations` (
  `company_id` INT NULL,
  `name` VARCHAR(255) NOT NULL,
  `address` TEXT NULL,
  `latitude` DECIMAL NOT NULL,
  `longitude` DECIMAL NOT NULL,
  `radius_meters` INT NOT NULL DEFAULT '100',
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `is_primary` TINYINT NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_office_locations_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `overtime_requests`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `overtime_requests`;

CREATE TABLE `overtime_requests` (
  `company_id` INT NULL,
  `employee_id` INT NOT NULL,
  `overtime_type` VARCHAR(255) NOT NULL,
  `date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `total_hours` DECIMAL NOT NULL,
  `calculated_pay` DECIMAL NOT NULL DEFAULT '0',
  `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `approved_at` DATETIME NULL,
  `rejection_reason` TEXT NULL,
  `reason` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_overtime_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_overtime_requests_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_overtime_requests_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `overtime_rules`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `overtime_rules`;

CREATE TABLE `overtime_rules` (
  `company_id` INT NULL,
  `overtime_type` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `multiplier_first_hour` DECIMAL NOT NULL DEFAULT '1.5',
  `multiplier_subsequent_hours` DECIMAL NOT NULL DEFAULT '1.5',
  `max_hours_per_day` INT NOT NULL DEFAULT '4',
  `max_hours_per_week` INT NOT NULL DEFAULT '14',
  `requires_approval` TINYINT NOT NULL DEFAULT '1',
  `applicable_year` INT NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_overtime_rules_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `password_reset_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `payroll_items`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payroll_items`;

CREATE TABLE `payroll_items` (
  `payroll_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `gross_salary` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_kesehatan_company` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_kesehatan_employee` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jht_company` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jht_employee` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jp_company` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jp_employee` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jkk` DECIMAL NOT NULL DEFAULT '0',
  `bpjs_tk_jkm` DECIMAL NOT NULL DEFAULT '0',
  `pph21` DECIMAL NOT NULL DEFAULT '0',
  `allowances_total` DECIMAL NOT NULL DEFAULT '0',
  `deductions_total` DECIMAL NOT NULL DEFAULT '0',
  `bonuses_total` DECIMAL NOT NULL DEFAULT '0',
  `overtime_pay` DECIMAL NOT NULL DEFAULT '0',
  `net_salary` DECIMAL NOT NULL DEFAULT '0',
  `calculation_details` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payroll_items_payroll_id_employee_id_unique` (`payroll_id`, `employee_id`),
  CONSTRAINT `fk_payroll_items_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_payroll_items_payroll_id` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `payrolls`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payrolls`;

CREATE TABLE `payrolls` (
  `name` VARCHAR(255) NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `status` VARCHAR(255) NOT NULL DEFAULT 'draft',
  `processed_by` INT NULL,
  `approved_by` INT NULL,
  `total_gross` DECIMAL NOT NULL DEFAULT '0',
  `total_deductions` DECIMAL NOT NULL DEFAULT '0',
  `total_net` DECIMAL NOT NULL DEFAULT '0',
  `total_employees` INT NOT NULL DEFAULT '0',
  `notes` TEXT NULL,
  `processed_at` DATETIME NULL,
  `approved_at` DATETIME NULL,
  `paid_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `company_id` INT NULL,
  `progress_percentage` INT NOT NULL DEFAULT '0',
  `current_batch` INT NOT NULL DEFAULT '0',
  `total_batches` INT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_payrolls_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION,
  CONSTRAINT `fk_payrolls_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_payrolls_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `payslips`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `payslips`;

CREATE TABLE `payslips` (
  `payroll_item_id` INT NOT NULL,
  `payslip_number` VARCHAR(255) NOT NULL,
  `pdf_path` VARCHAR(255) NULL,
  `generated_at` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslips_payslip_number_unique` (`payslip_number`),
  CONSTRAINT `fk_payslips_payroll_item_id` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE `permissions` (
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `personal_access_tokens`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `abilities` TEXT NULL,
  `last_used_at` DATETIME NULL,
  `expires_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `pph21_configs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pph21_configs`;

CREATE TABLE `pph21_configs` (
  `income_bracket_start` DECIMAL NOT NULL,
  `income_bracket_end` DECIMAL NULL,
  `rate_percentage` DECIMAL NOT NULL,
  `applicable_year` INT NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `ptkp_configs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `ptkp_configs`;

CREATE TABLE `ptkp_configs` (
  `category` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `annual_amount` DECIMAL NOT NULL,
  `applicable_year` INT NOT NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ptkp_configs_category_applicable_year_unique` (`category`, `applicable_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `pulse_aggregates`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pulse_aggregates`;

CREATE TABLE `pulse_aggregates` (
  `bucket` INT NOT NULL,
  `period` INT NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `key` TEXT NOT NULL,
  `key_hash` CHAR(16) CHARACTER SET binary NOT NULL,
  `aggregate` VARCHAR(255) NOT NULL,
  `value` DECIMAL NOT NULL,
  `count` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`, `period`, `type`, `aggregate`, `key_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `pulse_entries`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pulse_entries`;

CREATE TABLE `pulse_entries` (
  `timestamp` INT NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `key` TEXT NOT NULL,
  `key_hash` CHAR(16) CHARACTER SET binary NOT NULL,
  `value` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `pulse_values`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pulse_values`;

CREATE TABLE `pulse_values` (
  `timestamp` INT NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `key` TEXT NOT NULL,
  `key_hash` CHAR(16) CHARACTER SET binary NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`, `key_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `realtime_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `realtime_notifications`;

CREATE TABLE `realtime_notifications` (
  `company_id` INT NULL,
  `topic` VARCHAR(255) NOT NULL,
  `table_name` VARCHAR(255) NOT NULL,
  `event` VARCHAR(255) NOT NULL,
  `record_id` INT NULL,
  `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_realtime_notifications_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `role_has_permissions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `role_has_permissions`;

CREATE TABLE `role_has_permissions` (
  `permission_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  CONSTRAINT `fk_role_has_permissions_role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_permissions_permission_id` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `roles`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `name` VARCHAR(255) NOT NULL,
  `guard_name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `salary_components`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `salary_components`;

CREATE TABLE `salary_components` (
  `employee_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('allowance','deduction','bonus','overtime') NOT NULL,
  `amount` DECIMAL NOT NULL DEFAULT '0',
  `is_taxable` TINYINT NOT NULL DEFAULT '0',
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `effective_from` DATE NULL,
  `effective_until` DATE NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_salary_components_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `service_metrics`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `service_metrics`;

CREATE TABLE `service_metrics` (
  `system_service_id` INT NOT NULL,
  `metric_type` VARCHAR(255) NOT NULL,
  `value` DECIMAL NOT NULL,
  `recorded_at` DATETIME NOT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_service_metrics_system_service_id` FOREIGN KEY (`system_service_id`) REFERENCES `system_services` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `sessions`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` INT NULL,
  `ip_address` VARCHAR(255) NULL,
  `user_agent` TEXT NULL,
  `payload` TEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `settings`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT NULL,
  `group` VARCHAR(255) NOT NULL DEFAULT 'general',
  `type` VARCHAR(255) NOT NULL DEFAULT 'text',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `shift_assignments`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `shift_assignments`;

CREATE TABLE `shift_assignments` (
  `company_id` INT NULL,
  `employee_id` INT NOT NULL,
  `shift_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `is_override` TINYINT NOT NULL DEFAULT '0',
  `override_reason` TEXT NULL,
  `actual_clock_in` TIME NULL,
  `actual_clock_out` TIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shift_assignments_employee_id_date_unique` (`employee_id`, `date`),
  CONSTRAINT `fk_shift_assignments_shift_id` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_shift_assignments_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_shift_assignments_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `shifts`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `shifts`;

CREATE TABLE `shifts` (
  `company_id` INT NULL,
  `name` VARCHAR(255) NOT NULL,
  `shift_type` VARCHAR(255) NOT NULL DEFAULT 'fixed',
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `grace_period_minutes` INT NOT NULL DEFAULT '15',
  `late_threshold_minutes` INT NOT NULL DEFAULT '120',
  `max_clock_in_time` TIME NULL,
  `rotation_days` INT NOT NULL DEFAULT '7',
  `color` VARCHAR(255) NULL,
  `is_active` TINYINT NOT NULL DEFAULT '1',
  `description` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_shifts_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `system_services`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_services`;

CREATE TABLE `system_services` (
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(255) NOT NULL DEFAULT 'Infrastructure',
  `status` VARCHAR(255) NOT NULL DEFAULT 'operational',
  `response_time_ms` INT NULL,
  `uptime_percentage` DECIMAL NOT NULL DEFAULT '100',
  `is_public` TINYINT NOT NULL DEFAULT '1',
  `last_checked_at` DATETIME NULL,
  `sort_order` INT NOT NULL DEFAULT '0',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_services_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `uptime_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uptime_logs`;

CREATE TABLE `uptime_logs` (
  `system_service_id` INT NOT NULL,
  `status` VARCHAR(255) NOT NULL,
  `checked_at` DATETIME NOT NULL,
  `response_time_ms` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_uptime_logs_system_service_id` FOREIGN KEY (`system_service_id`) REFERENCES `system_services` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `user_notifications`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `user_notifications`;

CREATE TABLE `user_notifications` (
  `user_id` INT NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NULL,
  `data` TEXT NULL,
  `read_at` DATETIME NULL,
  `channel` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_user_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` DATETIME NULL,
  `password` VARCHAR(255) NOT NULL,
  `remember_token` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `company_id` INT NULL,
  `account_status` VARCHAR(255) NOT NULL DEFAULT 'pending',
  `approved_at` DATETIME NULL,
  `approved_by` INT NULL,
  `suspended_at` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  CONSTRAINT `fk_users_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `fk_users_company_id` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Indexes for tables
-- --------------------------------------------------------

CREATE INDEX `idx_activity_logs_action_date` ON `activity_logs` (`action`, `created_at`);
CREATE INDEX `activity_logs_created_at_index` ON `activity_logs` (`created_at`);
CREATE INDEX `activity_logs_action_index` ON `activity_logs` (`action`);
CREATE INDEX `activity_logs_subject_type_subject_id_index` ON `activity_logs` (`subject_type`, `subject_id`);
CREATE INDEX `approvals_approvable_type_approvable_id_status_index` ON `approvals` (`approvable_type`, `approvable_id`, `status`);
CREATE INDEX `approvals_approvable_type_approvable_id_index` ON `approvals` (`approvable_type`, `approvable_id`);
CREATE INDEX `attendance_selfies_verified_at_index` ON `attendance_selfies` (`verified_at`);
CREATE INDEX `idx_attendances_company_date_status` ON `attendances` (`company_id`, `date`, `status`);
CREATE INDEX `idx_attendances_employee_date_status` ON `attendances` (`employee_id`, `date`, `status`);
CREATE INDEX `attendances_status_index` ON `attendances` (`status`);
CREATE INDEX `attendances_date_index` ON `attendances` (`date`);
CREATE INDEX `idx_bpjs_configs_year_active` ON `bpjs_configs` (`applicable_year`, `is_active`);
CREATE INDEX `cache_expiration_index` ON `cache` (`expiration`);
CREATE INDEX `cache_locks_expiration_index` ON `cache_locks` (`expiration`);
CREATE INDEX `idx_employees_company_active` ON `employees` (`company_id`, `is_active`);
CREATE INDEX `idx_employees_position_active` ON `employees` (`position`, `is_active`);
CREATE INDEX `idx_employees_department_active` ON `employees` (`department`, `is_active`);
CREATE INDEX `holidays_company_id_index` ON `holidays` (`company_id`);
CREATE INDEX `holidays_date_index` ON `holidays` (`date`);
CREATE INDEX `incident_updates_created_at_index` ON `incident_updates` (`created_at`);
CREATE INDEX `incidents_severity_index` ON `incidents` (`severity`);
CREATE INDEX `incidents_status_index` ON `incidents` (`status`);
CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);
CREATE INDEX `idx_leave_requests_company_status_approved` ON `leave_requests` (`company_id`, `status`, `approved_at`);
CREATE INDEX `idx_leave_requests_company_status_created` ON `leave_requests` (`company_id`, `status`, `created_at`);
CREATE INDEX `leave_requests_start_date_index` ON `leave_requests` (`start_date`);
CREATE INDEX `leave_requests_employee_id_status_index` ON `leave_requests` (`employee_id`, `status`);
CREATE INDEX `maintenance_schedules_scheduled_start_index` ON `maintenance_schedules` (`scheduled_start`);
CREATE INDEX `idx_manual_attendance_company_status_updated` ON `manual_attendance_requests` (`company_id`, `status`, `updated_at`);
CREATE INDEX `manual_attendance_requests_request_type_requested_date_index` ON `manual_attendance_requests` (`request_type`, `requested_date`);
CREATE INDEX `manual_attendance_requests_status_created_at_index` ON `manual_attendance_requests` (`status`, `created_at`);
CREATE INDEX `manual_attendance_requests_employee_id_requested_date_index` ON `manual_attendance_requests` (`employee_id`, `requested_date`);
CREATE INDEX `model_has_permissions_model_id_model_type_index` ON `model_has_permissions` (`model_id`, `model_type`);
CREATE INDEX `model_has_roles_model_id_model_type_index` ON `model_has_roles` (`model_id`, `model_type`);
CREATE INDEX `idx_notifications_user_read` ON `notifications` (`notifiable_id`, `read_at`);
CREATE INDEX `notifications_notifiable_type_notifiable_id_read_at_index` ON `notifications` (`notifiable_type`, `notifiable_id`, `read_at`);
CREATE INDEX `notifications_notifiable_type_notifiable_id_index` ON `notifications` (`notifiable_type`, `notifiable_id`);
CREATE INDEX `idx_overtime_requests_employee_date_status` ON `overtime_requests` (`employee_id`, `date`, `status`);
CREATE INDEX `overtime_requests_employee_id_status_index` ON `overtime_requests` (`employee_id`, `status`);
CREATE INDEX `overtime_requests_employee_id_date_index` ON `overtime_requests` (`employee_id`, `date`);
CREATE INDEX `overtime_rules_company_id_overtime_type_applicable_year_index` ON `overtime_rules` (`company_id`, `overtime_type`, `applicable_year`);
CREATE INDEX `idx_payroll_items_employee_payroll` ON `payroll_items` (`employee_id`, `payroll_id`);
CREATE INDEX `idx_payroll_items_employee_created` ON `payroll_items` (`employee_id`, `created_at`);
CREATE INDEX `idx_payrolls_company_status_created` ON `payrolls` (`company_id`, `status`, `created_at`);
CREATE INDEX `idx_payrolls_company_period_status` ON `payrolls` (`company_id`, `period_end`, `status`);
CREATE INDEX `idx_payrolls_status_period` ON `payrolls` (`status`, `period_end`);
CREATE INDEX `payrolls_status_index` ON `payrolls` (`status`);
CREATE INDEX `payrolls_period_start_index` ON `payrolls` (`period_start`);
CREATE INDEX `payrolls_period_end_index` ON `payrolls` (`period_end`);
CREATE INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` ON `personal_access_tokens` (`tokenable_type`, `tokenable_id`);
CREATE INDEX `idx_pph21_configs_year_active` ON `pph21_configs` (`applicable_year`, `is_active`);
CREATE INDEX `idx_ptkp_configs_year_active` ON `ptkp_configs` (`applicable_year`, `is_active`);
CREATE INDEX `ptkp_configs_applicable_year_index` ON `ptkp_configs` (`applicable_year`);
CREATE INDEX `pulse_aggregates_period_type_aggregate_bucket_index` ON `pulse_aggregates` (`period`, `type`, `aggregate`, `bucket`);
CREATE INDEX `pulse_aggregates_type_index` ON `pulse_aggregates` (`type`);
CREATE INDEX `pulse_aggregates_period_bucket_index` ON `pulse_aggregates` (`period`, `bucket`);
CREATE INDEX `pulse_entries_timestamp_type_key_hash_value_index` ON `pulse_entries` (`timestamp`, `type`, `key_hash`, `value`);
CREATE INDEX `pulse_entries_key_hash_index` ON `pulse_entries` (`key_hash`);
CREATE INDEX `pulse_entries_type_index` ON `pulse_entries` (`type`);
CREATE INDEX `pulse_entries_timestamp_index` ON `pulse_entries` (`timestamp`);
CREATE INDEX `pulse_values_type_index` ON `pulse_values` (`type`);
CREATE INDEX `pulse_values_timestamp_index` ON `pulse_values` (`timestamp`);
CREATE INDEX `realtime_notifications_company_id_topic_index` ON `realtime_notifications` (`company_id`, `topic`);
CREATE INDEX `realtime_notifications_topic_occurred_at_index` ON `realtime_notifications` (`topic`, `occurred_at`);
CREATE INDEX `salary_components_employee_id_type_index` ON `salary_components` (`employee_id`, `type`);
CREATE INDEX `service_metrics_recorded_at_index` ON `service_metrics` (`recorded_at`);
CREATE INDEX `service_metrics_system_service_id_metric_type_index` ON `service_metrics` (`system_service_id`, `metric_type`);
CREATE INDEX `sessions_last_activity_index` ON `sessions` (`last_activity`);
CREATE INDEX `sessions_user_id_index` ON `sessions` (`user_id`);
CREATE INDEX `idx_shift_assignments_company_date` ON `shift_assignments` (`company_id`, `date`);
CREATE INDEX `idx_shift_assignments_employee_date` ON `shift_assignments` (`employee_id`, `date`);
CREATE INDEX `shift_assignments_date_index` ON `shift_assignments` (`date`);
CREATE INDEX `shifts_is_active_index` ON `shifts` (`is_active`);
CREATE INDEX `system_services_category_index` ON `system_services` (`category`);
CREATE INDEX `system_services_slug_index` ON `system_services` (`slug`);
CREATE INDEX `uptime_logs_system_service_id_checked_at_index` ON `uptime_logs` (`system_service_id`, `checked_at`);
CREATE INDEX `notifications_type_index` ON `user_notifications` (`type`);
CREATE INDEX `notifications_user_id_read_at_index` ON `user_notifications` (`user_id`, `read_at`);
CREATE INDEX `users_account_status_index` ON `users` (`account_status`);

-- --------------------------------------------------------
-- Migrations table data (so Laravel knows which migrations ran)
-- --------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`) VALUES
  ('0001_01_01_000000_create_users_table', 1),
  ('0001_01_01_000001_create_cache_table', 1),
  ('0001_01_01_000002_create_jobs_table', 1),
  ('2026_05_11_072136_create_permission_tables', 2),
  ('2026_05_11_142225_create_employees_table', 2),
  ('2026_05_11_142226_create_attendances_table', 2),
  ('2026_05_11_142227_create_salary_components_table', 2),
  ('2026_05_11_142228_create_payrolls_table', 2),
  ('2026_05_11_142229_create_payroll_items_table', 2),
  ('2026_05_11_142239_create_payslips_table', 2),
  ('2026_05_11_142240_create_settings_table', 2),
  ('2026_05_11_142241_create_activity_logs_table', 2),
  ('2026_05_11_142242_create_pph21_configs_table', 2),
  ('2026_05_11_142243_create_bpjs_configs_table', 2),
  ('2026_05_14_000001_create_system_status_tables', 2),
  ('2026_05_15_000001_create_companies_table', 2),
  ('2026_05_15_000002_create_approvals_and_notifications_tables', 2),
  ('2026_05_15_000003_create_leave_and_overtime_tables', 2),
  ('2026_05_15_000004_create_shift_and_holiday_tables', 2),
  ('2026_05_15_000005_create_office_selfie_ptkp_tables', 2),
  ('2026_05_15_000006_add_company_id_to_existing_tables', 2),
  ('2026_06_02_000001_rename_notifications_table', 2),
  ('2026_06_02_000002_create_notifications_table', 2),
  ('2026_06_02_000003_add_marital_status_to_employees', 2),
  ('2026_06_03_000001_add_composite_indexes', 2),
  ('2026_06_03_000002_encrypt_existing_employee_sensitive_data', 2),
  ('2026_06_03_000003_update_bpjs_jp_salary_cap', 2),
  ('2026_06_03_183518_create_pulse_tables', 2),
  ('2026_06_04_000001_create_personal_access_tokens_table', 2),
  ('2026_06_04_180000_add_account_status_to_users_table', 2),
  ('2026_06_05_000001_harden_payroll_processing_and_employee_identity', 2),
  ('2026_06_05_000002_backfill_default_company_context', 2),
  ('2026_06_05_000003_create_realtime_notifications_table', 2),
  ('2026_06_10_000001_create_manual_attendance_requests_table', 2),
  ('2026_06_12_000001_add_supabase_performance_indexes', 2),
  ('2026_06_13_000001_add_performance_indexes_phase2', 2);

-- --------------------------------------------------------
-- Seed data: Roles and Permissions
-- --------------------------------------------------------

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
  ('manage-employees', 'web', NOW(), NOW()),
  ('manage-attendance', 'web', NOW(), NOW()),
  ('manage-leaves', 'web', NOW(), NOW()),
  ('view-attendance', 'web', NOW(), NOW()),
  ('manage-payroll', 'web', NOW(), NOW()),
  ('view-payroll', 'web', NOW(), NOW()),
  ('manage-settings', 'web', NOW(), NOW()),
  ('view-reports', 'web', NOW(), NOW()),
  ('view-dashboard', 'web', NOW(), NOW());

INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
  ('Admin', 'web', NOW(), NOW()),
  ('HR', 'web', NOW(), NOW()),
  ('Employee', 'web', NOW(), NOW());

-- Assign permissions to roles
-- Assumes: Admin=1, HR=2, Employee=3
-- Admin gets all permissions (1-9)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
  (1, 1),
  (2, 1),
  (3, 1),
  (4, 1),
  (5, 1),
  (6, 1),
  (7, 1),
  (8, 1),
  (9, 1),
  (1, 2),
  (2, 2),
  (3, 2),
  (4, 2),
  (5, 2),
  (6, 2),
  (8, 2),
  (9, 2),
  (4, 3),
  (6, 3),
  (9, 3);

-- --------------------------------------------------------
-- Seed data: Company
-- --------------------------------------------------------

INSERT INTO `companies` (`name`, `slug`, `is_active`, `subscription_plan`, `created_at`, `updated_at`)
VALUES ('Project KP', 'project-kp', 1, 'internal', NOW(), NOW());

-- --------------------------------------------------------
-- Seed data: BPJS Config
-- --------------------------------------------------------

INSERT INTO `bpjs_configs` (`name`, `type`, `payer`, `rate_percentage`, `salary_cap`, `description`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES
  ('BPJS Kesehatan - Company', 'kesehatan', 'company', 4, 12000000.00, '4% dari gaji bulanan, maksimal Rp 12.000.000', 2025, 1, NOW(), NOW()),
  ('BPJS Kesehatan - Employee', 'kesehatan', 'employee', 1, 12000000.00, '1% dari gaji bulanan, maksimal Rp 12.000.000', 2025, 1, NOW(), NOW()),
  ('BPJS TK JHT - Company', 'tk_jht', 'company', 3.7, NULL, '3.7% dari gaji bulanan', 2025, 1, NOW(), NOW()),
  ('BPJS TK JHT - Employee', 'tk_jht', 'employee', 2, NULL, '2% dari gaji bulanan', 2025, 1, NOW(), NOW()),
  ('BPJS TK JP - Company', 'tk_jp', 'company', 2, 10547400.00, '2% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)', 2025, 1, NOW(), NOW()),
  ('BPJS TK JP - Employee', 'tk_jp', 'employee', 1, 10547400.00, '1% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)', 2025, 1, NOW(), NOW()),
  ('BPJS TK JKK', 'tk_jkk', 'company', 0.24, NULL, '0.24% dari gaji bulanan (company only, risiko rendah)', 2025, 1, NOW(), NOW()),
  ('BPJS TK JKM', 'tk_jkm', 'company', 0.3, NULL, '0.3% dari gaji bulanan (company only)', 2025, 1, NOW(), NOW()),
  ('BPJS Kesehatan - Company', 'kesehatan', 'company', 4, 12000000.00, '4% dari gaji bulanan, maksimal Rp 12.000.000', 2026, 1, NOW(), NOW()),
  ('BPJS Kesehatan - Employee', 'kesehatan', 'employee', 1, 12000000.00, '1% dari gaji bulanan, maksimal Rp 12.000.000', 2026, 1, NOW(), NOW()),
  ('BPJS TK JHT - Company', 'tk_jht', 'company', 3.7, NULL, '3.7% dari gaji bulanan', 2026, 1, NOW(), NOW()),
  ('BPJS TK JHT - Employee', 'tk_jht', 'employee', 2, NULL, '2% dari gaji bulanan', 2026, 1, NOW(), NOW()),
  ('BPJS TK JP - Company', 'tk_jp', 'company', 2, 10547400.00, '2% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)', 2026, 1, NOW(), NOW()),
  ('BPJS TK JP - Employee', 'tk_jp', 'employee', 1, 10547400.00, '1% dari gaji bulanan, maksimal Rp 10.547.400 (PPU 2026)', 2026, 1, NOW(), NOW()),
  ('BPJS TK JKK', 'tk_jkk', 'company', 0.24, NULL, '0.24% dari gaji bulanan (company only, risiko rendah)', 2026, 1, NOW(), NOW()),
  ('BPJS TK JKM', 'tk_jkm', 'company', 0.3, NULL, '0.3% dari gaji bulanan (company only)', 2026, 1, NOW(), NOW());

-- --------------------------------------------------------
-- Seed data: PPh21 Config
-- --------------------------------------------------------

INSERT INTO `pph21_configs` (`income_bracket_start`, `income_bracket_end`, `rate_percentage`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES
  (0, 60000000, 5, 2025, 1, NOW(), NOW()),
  (60000000, 250000000, 15, 2025, 1, NOW(), NOW()),
  (250000000, 500000000, 25, 2025, 1, NOW(), NOW()),
  (500000000, 5000000000, 30, 2025, 1, NOW(), NOW()),
  (5000000000, NULL, 35, 2025, 1, NOW(), NOW()),
  (0, 60000000, 5, 2026, 1, NOW(), NOW()),
  (60000000, 250000000, 15, 2026, 1, NOW(), NOW()),
  (250000000, 500000000, 25, 2026, 1, NOW(), NOW()),
  (500000000, 5000000000, 30, 2026, 1, NOW(), NOW()),
  (5000000000, NULL, 35, 2026, 1, NOW(), NOW());

-- --------------------------------------------------------
-- Seed data: PTKP Config
-- --------------------------------------------------------

INSERT INTO `ptkp_configs` (`category`, `description`, `annual_amount`, `applicable_year`, `is_active`, `created_at`, `updated_at`) VALUES
  ('TK/0', 'Tidak Kawin, 0 tanggungan', 54000000, 2025, 1, NOW(), NOW()),
  ('TK/1', 'Tidak Kawin, 1 tanggungan', 58500000, 2025, 1, NOW(), NOW()),
  ('TK/2', 'Tidak Kawin, 2 tanggungan', 63000000, 2025, 1, NOW(), NOW()),
  ('TK/3', 'Tidak Kawin, 3 tanggungan', 67500000, 2025, 1, NOW(), NOW()),
  ('K/0', 'Kawin, 0 tanggungan', 58500000, 2025, 1, NOW(), NOW()),
  ('K/1', 'Kawin, 1 tanggungan', 63000000, 2025, 1, NOW(), NOW()),
  ('K/2', 'Kawin, 2 tanggungan', 67500000, 2025, 1, NOW(), NOW()),
  ('K/3', 'Kawin, 3 tanggungan', 72000000, 2025, 1, NOW(), NOW()),
  ('TK/0', 'Tidak Kawin, 0 tanggungan', 54000000, 2026, 1, NOW(), NOW()),
  ('TK/1', 'Tidak Kawin, 1 tanggungan', 58500000, 2026, 1, NOW(), NOW()),
  ('TK/2', 'Tidak Kawin, 2 tanggungan', 63000000, 2026, 1, NOW(), NOW()),
  ('TK/3', 'Tidak Kawin, 3 tanggungan', 67500000, 2026, 1, NOW(), NOW()),
  ('K/0', 'Kawin, 0 tanggungan', 58500000, 2026, 1, NOW(), NOW()),
  ('K/1', 'Kawin, 1 tanggungan', 63000000, 2026, 1, NOW(), NOW()),
  ('K/2', 'Kawin, 2 tanggungan', 67500000, 2026, 1, NOW(), NOW()),
  ('K/3', 'Kawin, 3 tanggungan', 72000000, 2026, 1, NOW(), NOW());

-- --------------------------------------------------------
-- Pulse: jika Pulse error untuk key_hash, jalankan ALTER manual:
-- ALTER TABLE pulse_values MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;
-- ALTER TABLE pulse_entries MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;
-- ALTER TABLE pulse_aggregates MODIFY key_hash CHAR(16) CHARACTER SET binary AS (UNHEX(MD5(`key`))) VIRTUAL;
-- --------------------------------------------------------

COMMIT;
-- ================================================================
-- End of migrate.sql
-- ================================================================

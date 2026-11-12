-- ==============================================================================
-- UNIVERSAL DIGITAL INVITATION AND EVENT GUEST VERIFICATION MANAGEMENT SYSTEM
-- FULL PRODUCTION DATABASE SCHEMA & INITIAL DEMONSTRATION SEED DATA
-- Target DBMS: MySQL 8.0+ / MariaDB 10.4+ (XAMPP Compatible)
-- Character Set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ------------------------------------------------------------------------------
-- 1. DATABASE CREATION
-- ------------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `invitation_system` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `invitation_system`;

-- ------------------------------------------------------------------------------
-- 2. DROP EXISTING TABLES (REVERSE ORDER OF DEPENDENCY)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `security_events`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `message_logs`;
DROP TABLE IF EXISTS `check_ins`;
DROP TABLE IF EXISTS `rsvp_records`;
DROP TABLE IF EXISTS `gate_officer`;
DROP TABLE IF EXISTS `gates`;
DROP TABLE IF EXISTS `qr_tokens`;
DROP TABLE IF EXISTS `invitations`;
DROP TABLE IF EXISTS `invitation_designs`;
DROP TABLE IF EXISTS `invitation_templates`;
DROP TABLE IF EXISTS `guests`;
DROP TABLE IF EXISTS `guest_categories`;
DROP TABLE IF EXISTS `event_sessions`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `event_categories`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `permission_role`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `organizations`;

-- ------------------------------------------------------------------------------
-- 3. TABLE DEFINITIONS
-- ------------------------------------------------------------------------------

-- Table: organizations
CREATE TABLE `organizations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `brand_color_primary` VARCHAR(50) DEFAULT '#0f172a',
  `brand_color_secondary` VARCHAR(50) DEFAULT '#10b981',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `organizations_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: roles
CREATE TABLE `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `display_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: permissions
CREATE TABLE `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: permission_role
CREATE TABLE `permission_role` (
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: users (Hardened with TOTP, Lockout & Argon2/Bcrypt)
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `role_id` BIGINT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `activated_at` TIMESTAMP NULL DEFAULT NULL,
  `suspended_at` TIMESTAMP NULL DEFAULT NULL,
  `suspension_reason` TEXT DEFAULT NULL,
  `language` VARCHAR(10) NOT NULL DEFAULT 'en',
  `profile_photo_path` VARCHAR(2048) DEFAULT NULL,
  `two_factor_secret` TEXT DEFAULT NULL,
  `two_factor_recovery_codes` TEXT DEFAULT NULL,
  `two_factor_confirmed_at` TIMESTAMP NULL DEFAULT NULL,
  `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `failed_login_attempts` INT NOT NULL DEFAULT 0,
  `lockout_until` TIMESTAMP NULL DEFAULT NULL,
  `password_changed_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_at` TIMESTAMP NULL DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_organization_id_foreign` (`organization_id`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: password_reset_tokens
CREATE TABLE `password_reset_tokens` (
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: personal_access_tokens (Sanctum)
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: event_categories
CREATE TABLE `event_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `icon` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: events
CREATE TABLE `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL,
  `event_category_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `banner_image` VARCHAR(255) DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME DEFAULT NULL,
  `timezone` VARCHAR(100) NOT NULL DEFAULT 'Africa/Dar_es_Salaam',
  `venue_name` VARCHAR(255) NOT NULL,
  `venue_address` TEXT DEFAULT NULL,
  `google_maps_link` TEXT DEFAULT NULL,
  `guest_capacity` INT DEFAULT NULL,
  `status` ENUM('draft', 'published', 'closed', 'archived') NOT NULL DEFAULT 'draft',
  `is_published` TINYINT(1) NOT NULL DEFAULT 0,
  `rsvp_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `rsvp_deadline` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `events_slug_unique` (`slug`),
  KEY `events_organization_id_foreign` (`organization_id`),
  KEY `events_created_by_foreign` (`created_by`),
  KEY `events_event_category_id_foreign` (`event_category_id`),
  CONSTRAINT `events_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_event_category_id_foreign` FOREIGN KEY (`event_category_id`) REFERENCES `event_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: event_sessions
CREATE TABLE `event_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `speaker_name` VARCHAR(255) DEFAULT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_sessions_event_id_foreign` (`event_id`),
  CONSTRAINT `event_sessions_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: guest_categories
CREATE TABLE `guest_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `color` VARCHAR(50) NOT NULL DEFAULT '#3b82f6',
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guest_categories_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: guests
CREATE TABLE `guests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `guest_category_id` BIGINT UNSIGNED DEFAULT NULL,
  `registered_by` BIGINT UNSIGNED DEFAULT NULL,
  `first_name` VARCHAR(150) NOT NULL,
  `last_name` VARCHAR(150) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `permitted_attendees` INT NOT NULL DEFAULT 1,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('active', 'cancelled', 'blacklisted') NOT NULL DEFAULT 'active',
  `is_duplicate_checked` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guests_organization_id_foreign` (`organization_id`),
  KEY `guests_event_id_foreign` (`event_id`),
  KEY `guests_guest_category_id_foreign` (`guest_category_id`),
  KEY `guests_registered_by_foreign` (`registered_by`),
  CONSTRAINT `guests_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guests_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guests_guest_category_id_foreign` FOREIGN KEY (`guest_category_id`) REFERENCES `guest_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `guests_registered_by_foreign` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invitation_templates
CREATE TABLE `invitation_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'General',
  `orientation` ENUM('portrait', 'landscape') NOT NULL DEFAULT 'portrait',
  `width` INT NOT NULL DEFAULT 800,
  `height` INT NOT NULL DEFAULT 1200,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `canvas_data` LONGTEXT DEFAULT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invitation_designs
CREATE TABLE `invitation_designs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `orientation` ENUM('portrait', 'landscape') NOT NULL DEFAULT 'portrait',
  `width` INT NOT NULL DEFAULT 800,
  `height` INT NOT NULL DEFAULT 1200,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `canvas_data` LONGTEXT DEFAULT NULL,
  `canva_bg_data` LONGBLOB DEFAULT NULL,
  `canva_bg_mime` VARCHAR(50) DEFAULT NULL,
  `background_color` VARCHAR(50) NOT NULL DEFAULT '#0f172a',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invitation_designs_event_id_foreign` (`event_id`),
  CONSTRAINT `invitation_designs_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: invitations
CREATE TABLE `invitations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` BIGINT UNSIGNED NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `guest_id` BIGINT UNSIGNED NOT NULL,
  `invitation_design_id` BIGINT UNSIGNED DEFAULT NULL,
  `reference_code` VARCHAR(50) NOT NULL,
  `status` ENUM('generated', 'sent', 'delivered', 'viewed', 'failed', 'cancelled') NOT NULL DEFAULT 'generated',
  `sent_via` ENUM('whatsapp', 'sms', 'email', 'manual') DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `delivered_at` DATETIME DEFAULT NULL,
  `viewed_at` DATETIME DEFAULT NULL,
  `card_image_url` VARCHAR(255) DEFAULT NULL,
  `pdf_url` VARCHAR(255) DEFAULT NULL,
  `is_checked_in` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitations_reference_code_unique` (`reference_code`),
  KEY `invitations_organization_id_foreign` (`organization_id`),
  KEY `invitations_event_id_foreign` (`event_id`),
  KEY `invitations_guest_id_foreign` (`guest_id`),
  KEY `invitations_invitation_design_id_foreign` (`invitation_design_id`),
  CONSTRAINT `invitations_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invitations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invitations_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invitations_invitation_design_id_foreign` FOREIGN KEY (`invitation_design_id`) REFERENCES `invitation_designs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: qr_tokens (With SHA-256 Hashing)
CREATE TABLE `qr_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invitation_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `token_hash` VARCHAR(64) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `scan_count` INT NOT NULL DEFAULT 0,
  `last_scanned_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_tokens_token_unique` (`token`),
  UNIQUE KEY `qr_tokens_token_hash_unique` (`token_hash`),
  KEY `qr_tokens_invitation_id_foreign` (`invitation_id`),
  CONSTRAINT `qr_tokens_invitation_id_foreign` FOREIGN KEY (`invitation_id`) REFERENCES `invitations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: gates
CREATE TABLE `gates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gates_event_id_foreign` (`event_id`),
  CONSTRAINT `gates_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: gate_officer (Gate staff assignment pivot)
CREATE TABLE `gate_officer` (
  `gate_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`gate_id`, `user_id`),
  KEY `gate_officer_user_id_foreign` (`user_id`),
  CONSTRAINT `gate_officer_gate_id_foreign` FOREIGN KEY (`gate_id`) REFERENCES `gates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gate_officer_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: check_ins (Gate Admission Audit)
CREATE TABLE `check_ins` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invitation_id` BIGINT UNSIGNED NOT NULL,
  `guest_id` BIGINT UNSIGNED NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `gate_id` BIGINT UNSIGNED DEFAULT NULL,
  `verified_by` BIGINT UNSIGNED DEFAULT NULL,
  `qr_token_id` BIGINT UNSIGNED DEFAULT NULL,
  `verification_method` ENUM('qr', 'manual', 'nfc') NOT NULL DEFAULT 'qr',
  `attendee_count` INT NOT NULL DEFAULT 1,
  `checked_in_at` DATETIME NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `check_ins_invitation_id_foreign` (`invitation_id`),
  KEY `check_ins_guest_id_foreign` (`guest_id`),
  KEY `check_ins_event_id_foreign` (`event_id`),
  KEY `check_ins_gate_id_foreign` (`gate_id`),
  KEY `check_ins_verified_by_foreign` (`verified_by`),
  KEY `check_ins_qr_token_id_foreign` (`qr_token_id`),
  CONSTRAINT `check_ins_invitation_id_foreign` FOREIGN KEY (`invitation_id`) REFERENCES `invitations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_ins_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_ins_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `check_ins_gate_id_foreign` FOREIGN KEY (`gate_id`) REFERENCES `gates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `check_ins_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `check_ins_qr_token_id_foreign` FOREIGN KEY (`qr_token_id`) REFERENCES `qr_tokens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: message_logs (WhatsApp, SMS, Email logs)
CREATE TABLE `message_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guest_id` BIGINT UNSIGNED DEFAULT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `channel` ENUM('whatsapp', 'sms', 'email') NOT NULL,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `body` LONGTEXT DEFAULT NULL,
  `status` ENUM('queued', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'queued',
  `external_id` VARCHAR(255) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_logs_guest_id_foreign` (`guest_id`),
  KEY `message_logs_event_id_foreign` (`event_id`),
  CONSTRAINT `message_logs_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `message_logs_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: rsvp_records
CREATE TABLE `rsvp_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invitation_id` BIGINT UNSIGNED NOT NULL,
  `guest_id` BIGINT UNSIGNED NOT NULL,
  `event_id` BIGINT UNSIGNED NOT NULL,
  `response` ENUM('accepted', 'declined', 'tentative') NOT NULL,
  `attendee_count` INT NOT NULL DEFAULT 1,
  `dietary_notes` TEXT DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `responded_at` DATETIME NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rsvp_records_invitation_id_foreign` (`invitation_id`),
  KEY `rsvp_records_guest_id_foreign` (`guest_id`),
  KEY `rsvp_records_event_id_foreign` (`event_id`),
  CONSTRAINT `rsvp_records_invitation_id_foreign` FOREIGN KEY (`invitation_id`) REFERENCES `invitations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rsvp_records_guest_id_foreign` FOREIGN KEY (`guest_id`) REFERENCES `guests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rsvp_records_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_logs
CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `action` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_organization_id_foreign` (`organization_id`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `audit_logs_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications
CREATE TABLE `notifications` (
  `id` CHAR(36) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `notifiable_type` VARCHAR(255) NOT NULL,
  `notifiable_id` BIGINT UNSIGNED NOT NULL,
  `data` TEXT NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: system_settings
CREATE TABLE `system_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` LONGTEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: security_events (Tamper-Resistant Security Event Telemetry)
CREATE TABLE `security_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(100) NOT NULL,
  `severity` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL DEFAULT 'LOW',
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `organization_id` BIGINT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `context` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_events_user_id_foreign` (`user_id`),
  KEY `security_events_organization_id_foreign` (`organization_id`),
  KEY `security_events_severity_index` (`severity`),
  CONSTRAINT `security_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `security_events_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. SEED DATA INGESTION
-- ------------------------------------------------------------------------------

-- 4.1 Organizations
INSERT INTO `organizations` (`id`, `name`, `slug`, `email`, `phone`, `address`, `website`, `brand_color_primary`, `brand_color_secondary`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Leula Events Ltd', 'leula-events', 'contact@leulaevents.com', '+255 700 123 456', 'Ocean View Tower, 14th Floor, Dar es Salaam, Tanzania', 'https://leulaevents.com', '#0f172a', '#10b981', 1, NOW(), NOW());

-- 4.2 Roles
INSERT INTO `roles` (`id`, `name`, `slug`, `display_name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'super-admin', 'Super Admin', 'Complete platform administration across all tenants and settings', 1, NOW(), NOW()),
(2, 'Event Administrator', 'event-admin', 'Event Admin', 'Full administrative access to manage organization events, card designs, guests and dispatches', 1, NOW(), NOW()),
(3, 'Verification Officer', 'verification-officer', 'Verification Officer', 'Gate personnel authorized to perform optical QR scanning and manual gate verification', 1, NOW(), NOW()),
(4, 'Guest', 'guest', 'Guest Attendee', 'Attendee access to personal invitations, passes, QR codes, and RSVP forms', 1, NOW(), NOW());

-- 4.3 Permissions
INSERT INTO `permissions` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Manage Organizations', 'manage-organizations', 'Create, edit and oversee client organizations', NOW(), NOW()),
(2, 'Manage Events', 'manage-events', 'Create, edit, publish and manage event schedules and venues', NOW(), NOW()),
(3, 'Manage Guests', 'manage-guests', 'Register, bulk import, edit and categorize guest attendees', NOW(), NOW()),
(4, 'Manage Invitations', 'manage-invitations', 'Design cards with visual editor, generate QR badges and distribute', NOW(), NOW()),
(5, 'Manage Gates', 'manage-gates', 'Create entrance checkpoints, gates and assign verification officers', NOW(), NOW()),
(6, 'Scan QR', 'scan-qr', 'Scan digital QR passes and verify real-time admission', NOW(), NOW()),
(7, 'View Reports', 'view-reports', 'View and export attendance rosters, audits and analytics in Excel/PDF', NOW(), NOW()),
(8, 'Manage Users', 'manage-users', 'Create and administer user accounts and security roles', NOW(), NOW()),
(9, 'Manage Settings', 'manage-settings', 'Configure platform branding, WhatsApp Cloud API, SMS gateway and SMTP', NOW(), NOW());

-- 4.4 Permission-Role Mappings
-- Super Admin: All Permissions (1 - 9)
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1), (2, 1), (3, 1), (4, 1), (5, 1), (6, 1), (7, 1), (8, 1), (9, 1);

-- Event Admin: Permissions (2 - 9)
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(2, 2), (3, 2), (4, 2), (5, 2), (6, 2), (7, 2), (8, 2), (9, 2);

-- Verification Officer: Permissions (6, 7)
INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(6, 3), (7, 3);

-- 4.5 Users
-- Default password for all seed accounts is: password123 (hashed with bcrypt / compatible with Laravel Auth)
INSERT INTO `users` (`id`, `organization_id`, `role_id`, `name`, `email`, `password`, `phone`, `status`, `is_active`, `activated_at`, `language`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'System Super Administrator', 'admin@leulaevents.com', '$2y$10$J5CLpke4nZ/fkQCTK40pJeAL3Gmtx2a4T5kTJ3n7biX9PjYXjmFA2', '+255 711 000 001', 'active', 1, NOW(), 'en', NOW(), NOW()),
(2, 1, 2, 'Sarah Mwangi (Event Director)', 'organizer@leulaevents.com', '$2y$10$J5CLpke4nZ/fkQCTK40pJeAL3Gmtx2a4T5kTJ3n7biX9PjYXjmFA2', '+255 712 345 678', 'active', 1, NOW(), 'en', NOW(), NOW()),
(3, 1, 3, 'David Kimani (Gate Lead)', 'officer@leulaevents.com', '$2y$10$J5CLpke4nZ/fkQCTK40pJeAL3Gmtx2a4T5kTJ3n7biX9PjYXjmFA2', '+255 713 999 888', 'active', 1, NOW(), 'sw', NOW(), NOW());

-- 4.6 Event Categories
INSERT INTO `event_categories` (`id`, `name`, `slug`, `icon`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Corporate Events', 'corporate-events', 'briefcase', 'Product launches, annual galas, banquets and executive symposiums', NOW(), NOW()),
(2, 'Conferences', 'conferences', 'presentation-chart-line', 'Professional summits, seminars, keynote lectures and trade expos', NOW(), NOW()),
(3, 'Weddings', 'weddings', 'heart', 'Traditional weddings, white weddings, bridal showers and send-offs', NOW(), NOW()),
(4, 'Government Events', 'government-events', 'building-library', 'State functions, civic celebrations and diplomatic forums', NOW(), NOW()),
(5, 'Graduations', 'graduations', 'academic-cap', 'University commencements, college and secondary school graduations', NOW(), NOW()),
(6, 'Birthdays', 'birthdays', 'cake', 'Milestone birthdays, anniversaries and jubilees', NOW(), NOW()),
(7, 'Religious Ceremonies', 'religious-ceremonies', 'sparkles', 'Ordinations, dedication ceremonies and spiritual gatherings', NOW(), NOW()),
(8, 'Funerals & Memorials', 'funerals', 'sun', 'Memorial services and celebrations of life', NOW(), NOW()),
(9, 'Private Parties', 'private-parties', 'sparkles', 'VIP private dinners, reunions and exclusive networking soirees', NOW(), NOW()),
(10, 'Other Special Gatherings', 'other', 'calendar', 'General gatherings and social events', NOW(), NOW());

-- 4.7 Guest Categories
INSERT INTO `guest_categories` (`id`, `name`, `slug`, `color`, `description`, `created_at`, `updated_at`) VALUES
(1, 'VVIP', 'vvip', '#8b5cf6', 'Head of State, Ministers, Ambassadors, C-Suite Executives and Dignitaries', NOW(), NOW()),
(2, 'VIP', 'vip', '#f59e0b', 'Honored guests, sponsors, board members, keynote speakers and VIP delegates', NOW(), NOW()),
(3, 'Regular', 'regular', '#3b82f6', 'Standard admitted guests and general invited attendees', NOW(), NOW()),
(4, 'Staff', 'staff', '#10b981', 'Event organizers, security coordinators, protocol crew and technical hosts', NOW(), NOW()),
(5, 'Family', 'family', '#ec4899', 'Immediate family members and special honorees', NOW(), NOW());

-- 4.8 Ready-Made Invitation Card Templates
INSERT INTO `invitation_templates` (`id`, `name`, `category`, `orientation`, `width`, `height`, `thumbnail`, `canvas_data`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'Royal Gold Gala (English)', 'Corporate Events', 'portrait', 600, 900, NULL, '{"background":"#0f172a","objects":[{"type":"text","text":"YOU ARE CORDIALLY INVITED","left":170,"top":60,"fontSize":14,"fill":"#fcd34d","fontFamily":"serif","fontWeight":"bold"},{"type":"text","text":"{event_name}","left":60,"top":110,"fontSize":26,"fill":"#ffffff","fontFamily":"serif","fontWeight":"bold"},{"type":"text","text":"Specially Honoring","left":240,"top":230,"fontSize":13,"fill":"#94a3b8","fontStyle":"italic"},{"type":"text","text":"{guest_name}","left":190,"top":260,"fontSize":28,"fill":"#38bdf8","fontWeight":"bold"},{"type":"text","text":"Tier: {guest_category}","left":235,"top":310,"fontSize":13,"fill":"#10b981","fontWeight":"bold"},{"type":"text","text":"Date: {event_date} at {event_time}\\nVenue: {venue}","left":160,"top":380,"fontSize":15,"fill":"#e2e8f0","textAlign":"center"},{"type":"text","text":"Admit: {permitted_attendees} Person(s) | Ref: {reference_code}","left":150,"top":480,"fontSize":12,"fill":"#cbd5e1"}]}', 1, NOW(), NOW()),
(2, 'Harusi ya Kifahari (Swahili Luxury Wedding)', 'Weddings', 'portrait', 600, 900, NULL, '{"background":"#450a0a","objects":[{"type":"text","text":"MUALIKO WA HARUSI YA KIFAHARI","left":160,"top":60,"fontSize":14,"fill":"#fde047","fontFamily":"serif","fontWeight":"bold"},{"type":"text","text":"{event_name}","left":60,"top":110,"fontSize":26,"fill":"#ffffff","fontFamily":"serif","fontWeight":"bold"},{"type":"text","text":"Mheshimiwa Mwalikwa","left":240,"top":230,"fontSize":13,"fill":"#fca5a5","fontStyle":"italic"},{"type":"text","text":"{guest_name}","left":180,"top":260,"fontSize":28,"fill":"#ffffff","fontWeight":"bold"},{"type":"text","text":"Kitengo: {guest_category}","left":235,"top":310,"fontSize":13,"fill":"#fde047","fontWeight":"bold"},{"type":"text","text":"Tarehe: {event_date} Saa: {event_time}\\nUkumbi: {venue}","left":160,"top":380,"fontSize":15,"fill":"#fecaca","textAlign":"center"},{"type":"text","text":"Idadi ya Watu: Watu {permitted_attendees} | Rejea: {reference_code}","left":150,"top":480,"fontSize":12,"fill":"#fca5a5"}]}', 1, NOW(), NOW()),
(3, 'Emerald Prestige Leadership Summit', 'Conferences', 'landscape', 800, 550, NULL, '{"background":"#064e3b","objects":[{"type":"text","text":"EAST AFRICA LEADERSHIP SUMMIT","left":60,"top":50,"fontSize":24,"fill":"#6ee7b7","fontWeight":"bold"},{"type":"text","text":"{event_name}","left":60,"top":100,"fontSize":32,"fill":"#ffffff","fontWeight":"bold"},{"type":"text","text":"Honored Delegate: {guest_name} ({guest_category})","left":60,"top":200,"fontSize":22,"fill":"#a7f3d0"},{"type":"text","text":"Date: {event_date} | Venue: {venue}","left":60,"top":270,"fontSize":16,"fill":"#e2e8f0"}]}', 1, NOW(), NOW());

-- 4.9 Sample Demonstration Event
INSERT INTO `events` (`id`, `organization_id`, `created_by`, `event_category_id`, `title`, `slug`, `description`, `banner_image`, `start_date`, `end_date`, `start_time`, `end_time`, `timezone`, `venue_name`, `venue_address`, `google_maps_link`, `guest_capacity`, `status`, `is_published`, `rsvp_enabled`, `rsvp_deadline`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 'Leula Events Annual Gala & Awards 2026', 'leula-events-annual-gala-2026', 'The premier corporate gala celebrating technological excellence and leadership in East Africa. Dress code: Formal / Black Tie.', 'https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80', '2026-11-21', '2026-11-21', '18:00:00', '23:30:00', 'Africa/Dar_es_Salaam', 'Serena Grand Ballroom', 'Ohio Street, City Centre, Dar es Salaam, Tanzania', 'https://maps.google.com/?q=Dar+es+Salaam+Serena+Hotel', 500, 'published', 1, 1, '2026-11-14 23:59:00', NOW(), NOW()),
(2, 1, 2, 2, 'Executive Leadership Symposium 2026', 'executive-symposium-2026', 'A closed-door summit convening regional CEOs, central bank governors, and tech founders.', NULL, '2026-12-05', '2026-12-05', '09:00:00', '17:00:00', 'Africa/Dar_es_Salaam', 'Hyatt Regency Dar es Salaam (The Kilimanjaro)', 'Kivukoni Road, Dar es Salaam, Tanzania', 'https://maps.google.com/?q=Hyatt+Regency+Dar+es+Salaam', 150, 'published', 1, 1, '2026-11-28 23:59:00', NOW(), NOW()),
(3, 1, 2, 3, 'Massawe & Salum Royal Wedding Celebration', 'massawe-salum-wedding-2026', 'Celebrating the holy matrimony and reception of Dr. Reginald Massawe and Hon. Amina Salum.', NULL, '2026-12-19', '2026-12-19', '15:00:00', '23:00:00', 'Africa/Dar_es_Salaam', 'St. Peters Church & Mlimani City Conference Center', 'Sam Nujoma Road, Dar es Salaam, Tanzania', 'https://maps.google.com/?q=Mlimani+City+Conference+Centre', 800, 'draft', 0, 1, '2026-12-10 23:59:00', NOW(), NOW());

-- 4.10 Event Entrance Gates
INSERT INTO `gates` (`id`, `event_id`, `name`, `code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'VIP & Dignitaries Entrance Gate', 'GATE-VIP', 'Fast-track gate for VVIP, VIP and Diplomatic guests with biometric pass lane', 1, NOW(), NOW()),
(2, 1, 'West General Entrance Gate', 'GATE-WEST', 'Primary entry checkpoint for Regular attendees and Media', 1, NOW(), NOW()),
(3, 2, 'Main Conference Check-in Desk', 'GATE-CONF-1', 'Main registration and security badge verification point', 1, NOW(), NOW());

-- 4.11 Gate Officer Assignment
INSERT INTO `gate_officer` (`gate_id`, `user_id`, `assigned_at`) VALUES
(1, 3, NOW()),
(2, 3, NOW());

-- 4.12 Sample Guests
INSERT INTO `guests` (`id`, `organization_id`, `event_id`, `guest_category_id`, `registered_by`, `first_name`, `last_name`, `full_name`, `email`, `phone`, `permitted_attendees`, `notes`, `status`, `is_duplicate_checked`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, 'Dr. Reginald', 'Massawe', 'Dr. Reginald Massawe', 'reginald.m@tanzaniatech.org', '+255712000001', 2, 'Keynote Guest Honoree - High Table 1', 'active', 1, NOW(), NOW()),
(2, 1, 1, 1, 2, 'Hon. Amina', 'Salum', 'Hon. Amina Salum', 'amina.salum@ict.gov.tz', '+255712000002', 2, 'Ministerial Delegation & Protocol Escort', 'active', 1, NOW(), NOW()),
(3, 1, 1, 2, 2, 'Eng. Joseph', 'Kagame', 'Eng. Joseph Kagame', 'joseph.k@fintechafrica.com', '+255712000003', 1, 'Fintech Award Nominee - VIP Table 3', 'active', 1, NOW(), NOW()),
(4, 1, 1, 2, 2, 'Neema', 'Ndlovu', 'Neema Ndlovu', 'neema@cloudsolutions.co.tz', '+255712000004', 2, 'Enterprise Sponsor Representative', 'active', 1, NOW(), NOW()),
(5, 1, 1, 3, 2, 'Baraka', 'Mushi', 'Baraka Mushi', 'baraka.m@innovations.tz', '+255712000005', 1, 'Regular Attendee Ticket #2041', 'active', 1, NOW(), NOW()),
(6, 1, 1, 3, 2, 'Grace', 'Tarimo', 'Grace Tarimo', 'grace.t@ventures.africa', '+255712000006', 1, 'Regular Attendee Ticket #2042', 'active', 1, NOW(), NOW()),
(7, 1, 1, 4, 2, 'Michael', 'Otieno', 'Michael Otieno', 'michael@crewstaff.org', '+255712000007', 1, 'Audio-Visual & Event Production Staff', 'active', 1, NOW(), NOW()),
(8, 1, 1, 5, 2, 'Fatuma', 'Juma', 'Fatuma Juma', 'fatuma.juma@familyconnect.tz', '+255712000008', 3, 'Founders Family Guest Suite', 'active', 1, NOW(), NOW());

-- 4.13 Invitations
INSERT INTO `invitations` (`id`, `organization_id`, `event_id`, `guest_id`, `invitation_design_id`, `reference_code`, `status`, `sent_via`, `sent_at`, `delivered_at`, `viewed_at`, `is_checked_in`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, NULL, 'INV-REG-8821', 'delivered', 'whatsapp', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, 1, NOW(), NOW()),
(2, 1, 1, 2, NULL, 'INV-AMI-9943', 'delivered', 'whatsapp', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY, 1, NOW(), NOW()),
(3, 1, 1, 3, NULL, 'INV-JOS-5512', 'sent', 'sms', NOW() - INTERVAL 1 DAY, NULL, NULL, 0, NOW(), NOW()),
(4, 1, 1, 4, NULL, 'INV-NEE-3321', 'sent', 'email', NOW() - INTERVAL 1 DAY, NULL, NULL, 0, NOW(), NOW()),
(5, 1, 1, 5, NULL, 'INV-BAR-1149', 'generated', NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
(6, 1, 1, 6, NULL, 'INV-GRA-7764', 'generated', NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
(7, 1, 1, 7, NULL, 'INV-MIC-4402', 'generated', NULL, NULL, NULL, NULL, 0, NOW(), NOW()),
(8, 1, 1, 8, NULL, 'INV-FAT-6639', 'generated', NULL, NULL, NULL, NULL, 0, NOW(), NOW());

-- 4.14 QR Tokens (Cryptographic 256-bit Tokens with SHA-256 Hashes)
INSERT INTO `qr_tokens` (`id`, `invitation_id`, `token`, `token_hash`, `is_active`, `is_revoked`, `scan_count`, `last_scanned_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'DEMO-TOKEN-VIP-REG-01', SHA2('DEMO-TOKEN-VIP-REG-01', 256), 1, 0, 1, NOW() - INTERVAL 2 HOUR, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(2, 2, 'DEMO-TOKEN-VVIP-AMI-02', SHA2('DEMO-TOKEN-VVIP-AMI-02', 256), 1, 0, 1, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(3, 3, 'DEMO-TOKEN-VIP-JOS-03', SHA2('DEMO-TOKEN-VIP-JOS-03', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(4, 4, 'DEMO-TOKEN-VIP-NEE-04', SHA2('DEMO-TOKEN-VIP-NEE-04', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(5, 5, 'DEMO-TOKEN-REG-BAR-05', SHA2('DEMO-TOKEN-REG-BAR-05', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(6, 6, 'DEMO-TOKEN-REG-GRA-06', SHA2('DEMO-TOKEN-REG-GRA-06', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(7, 7, 'DEMO-TOKEN-STF-MIC-07', SHA2('DEMO-TOKEN-STF-MIC-07', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW()),
(8, 8, 'DEMO-TOKEN-FAM-FAT-08', SHA2('DEMO-TOKEN-FAM-FAT-08', 256), 1, 0, 0, NULL, NOW() + INTERVAL 30 DAY, NOW(), NOW());

-- 4.15 RSVP Records
INSERT INTO `rsvp_records` (`id`, `invitation_id`, `guest_id`, `event_id`, `response`, `attendee_count`, `dietary_notes`, `message`, `responded_at`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'accepted', 2, 'None (Standard Executive Dining)', 'Delighted to attend and looking forward to delivering the opening remarks.', NOW() - INTERVAL 1 DAY, '197.250.201.4', NOW(), NOW()),
(2, 2, 1, 1, 'accepted', 2, 'Halal preferred', 'Confirmed. Protocol officers have noted arrival time at 18:15.', NOW() - INTERVAL 1 DAY, '197.250.198.12', NOW(), NOW()),
(3, 3, 1, 1, 'tentative', 1, 'Vegetarian', 'Awaiting flight confirmation from Nairobi conference.', NOW() - INTERVAL 12 HOUR, '105.163.2.88', NOW(), NOW());

-- 4.16 Live Check-ins (Simulated Admitted Guests)
INSERT INTO `check_ins` (`id`, `invitation_id`, `guest_id`, `event_id`, `gate_id`, `verified_by`, `qr_token_id`, `verification_method`, `attendee_count`, `checked_in_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 3, 1, 'qr', 2, NOW() - INTERVAL 2 HOUR, 'VVIP entry authorized via VIP Gate 1. Both attendees verified.', NOW(), NOW()),
(2, 2, 2, 1, 1, 3, 2, 'qr', 2, NOW() - INTERVAL 1 HOUR, 'VVIP ministerial entry validated. Assigned VIP Table 1.', NOW(), NOW());

-- 4.17 Message Dispatch Logs
INSERT INTO `message_logs` (`id`, `guest_id`, `event_id`, `channel`, `recipient`, `subject`, `body`, `status`, `external_id`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'whatsapp', '+255712000001', 'Official Digital Invitation - Leula Events Gala 2026', 'Habari Dr. Reginald Massawe,\nUmealikwa rasmi kwenye Leula Events Annual Gala & Awards 2026.\nTarehe: 2026-11-21 | Ukumbi: Serena Grand Ballroom.\nFungua kiungo chako cha kidijitali kupata QR code ya kuingilia:\nhttp://localhost:5173/invitation/DEMO-TOKEN-VIP-REG-01', 'delivered', 'wamid_HBgLMjU1NzEyMDAwMDAxFQIA', NOW() - INTERVAL 2 DAY, NOW(), NOW()),
(2, 2, 1, 'whatsapp', '+255712000002', 'Official Digital Invitation - Leula Events Gala 2026', 'Habari Hon. Amina Salum,\nUmealikwa rasmi kwenye Leula Events Annual Gala & Awards 2026.\nTarehe: 2026-11-21 | Ukumbi: Serena Grand Ballroom.\nFungua kiungo chako cha kidijitali kupata QR code ya kuingilia:\nhttp://localhost:5173/invitation/DEMO-TOKEN-VVIP-AMI-02', 'delivered', 'wamid_HBgLMjU1NzEyMDAwMDAyFQIA', NOW() - INTERVAL 2 DAY, NOW(), NOW()),
(3, 3, 1, 'sms', '+255712000003', NULL, 'Leula Events Annual Gala: Mheshimiwa Eng. Joseph Kagame, mualiko wako umekamilika. Fungua kiungo kupata QR pass: http://localhost:5173/invitation/DEMO-TOKEN-VIP-JOS-03', 'sent', 'SMS_TZ_8849201', NOW() - INTERVAL 1 DAY, NOW(), NOW());

-- 4.18 System Settings & Configurations
INSERT INTO `system_settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'platform_name', 'Leula Events', 'Public branding name of the invitation platform', NOW(), NOW()),
(2, 'default_language', 'en', 'Default system interface language (en or sw)', NOW(), NOW()),
(3, 'whatsapp_provider', 'meta_cloud_api', 'WhatsApp Business Provider (meta_cloud_api, twilio, infobip)', NOW(), NOW()),
(4, 'whatsapp_phone_number_id', '109847291823901', 'Meta WhatsApp Business Cloud API Phone Number ID', NOW(), NOW()),
(5, 'whatsapp_business_account_id', '789123456789012', 'Meta WhatsApp Business Account WABA ID', NOW(), NOW()),
(6, 'sms_gateway_provider', 'beem_africa', 'Default SMS gateway provider for East Africa (beem_africa, twilio, infobip)', NOW(), NOW()),
(7, 'sms_sender_id', 'LEULA', 'Approved alphanumeric Sender ID for SMS dispatches', NOW(), NOW()),
(8, 'mail_from_address', 'invitations@leulaevents.com', 'Default outgoing SMTP email address', NOW(), NOW()),
(9, 'mail_from_name', 'Leula Events Secretariat', 'Default outgoing email sender display name', NOW(), NOW()),
(10, 'pdpa_compliance_mode', 'true', 'Strict adherence to Tanzania Personal Data Protection Act 2022', NOW(), NOW());

-- 4.19 Initial Security Events Audit Log
INSERT INTO `security_events` (`id`, `event_type`, `severity`, `user_id`, `organization_id`, `ip_address`, `user_agent`, `context`, `created_at`, `updated_at`) VALUES
(1, 'SYSTEM_INITIALIZATION', 'LOW', 1, 1, '127.0.0.1', 'MigrationRunner/1.0', '{"action":"Database seeded with hardened cryptographic schema and tables"}', NOW(), NOW()),
(2, 'GATE_SCAN_SUCCESS', 'LOW', 3, 1, '197.250.201.10', 'LeulaEventsScanner/MobileBrowser', '{"guest":"Dr. Reginald Massawe","token_ref":"INV-REG-8821","gate":"VIP Gate 1"}', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR),
(3, 'GATE_SCAN_SUCCESS', 'LOW', 3, 1, '197.250.201.10', 'LeulaEventsScanner/MobileBrowser', '{"guest":"Hon. Amina Salum","token_ref":"INV-AMI-9943","gate":"VIP Gate 1"}', NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

-- ==============================================================================
-- DATABASE CREATION AND INITIAL SEEDING COMPLETE
-- Database: invitation_system
-- Tables: 25 Normalized Tables Created
-- Default Admin: admin@leulaevents.com / password123
-- Organizer: organizer@leulaevents.com / password123
-- Gate Officer: officer@leulaevents.com / password123
-- ==============================================================================


-- ============================================================================
-- 26. LARAVEL CACHE & SESSIONS TABLES
-- ============================================================================
CREATE TABLE IF NOT EXISTS \cache\ (
    \key\ VARCHAR(255) NOT NULL PRIMARY KEY,
    \alue\ MEDIUMTEXT NOT NULL,
    \expiration\ INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS \cache_locks\ (
    \key\ VARCHAR(255) NOT NULL PRIMARY KEY,
    \owner\ VARCHAR(255) NOT NULL,
    \expiration\ INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS \sessions\ (
    \id\ VARCHAR(255) NOT NULL PRIMARY KEY,
    \user_id\ BIGINT UNSIGNED NULL,
    \ip_address\ VARCHAR(45) NULL,
    \user_agent\ TEXT NULL,
    \payload\ LONGTEXT NOT NULL,
    \last_activity\ INT NOT NULL,
    INDEX \sessions_user_id_index\ (\user_id\),
    INDEX \sessions_last_activity_index\ (\last_activity\)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


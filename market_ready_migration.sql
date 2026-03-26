-- Market-Ready Migration SQL for Calldesk CRM (COMPREHENSIVE)
-- Run this if you are upgrading from ANY previous version.

-- 1. Organizations Base
CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `plan` ENUM('free', 'pro', 'enterprise') DEFAULT 'free',
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO `organizations` (id, name) VALUES (1, 'Default Organization');

-- 2. Core Columns for SaaS (Users)
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `organization_id` INT NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `last_active_at` DATETIME NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `current_status` ENUM('online', 'offline', 'break', 'on-call') DEFAULT 'offline';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `break_start_time` DATETIME NULL;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1;

-- 3. Geography Tables
CREATE TABLE IF NOT EXISTS `states` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `state_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`state_id`) REFERENCES `states`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`district_id`) REFERENCES `blocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Update Leads Table
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `organization_id` INT NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `alternate_mobile` VARCHAR(15) NULL AFTER `mobile`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `state_id` INT NULL AFTER `organization_id`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `district_id` INT NULL AFTER `state_id`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `block_id` INT NULL AFTER `district_id`;

-- 5. Update Call Logs Table (CRITICAL FIX FOR RECORDING_PATH)
ALTER TABLE `call_logs` ADD COLUMN IF NOT EXISTS `organization_id` INT NOT NULL DEFAULT 1 AFTER `id`;
ALTER TABLE `call_logs` ADD COLUMN IF NOT EXISTS `recording_path` VARCHAR(255) NULL AFTER `executive_id`;
ALTER TABLE `call_logs` ADD COLUMN IF NOT EXISTS `contact_name` VARCHAR(100) NULL AFTER `mobile`;

-- 6. Dynamic Lead Statuses
CREATE TABLE IF NOT EXISTS `lead_statuses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `status_name` VARCHAR(50) NOT NULL,
    `color_code` VARCHAR(20) DEFAULT '#64748b',
    `is_default` TINYINT(1) DEFAULT 0,
    `display_order` INT DEFAULT 0,
    UNIQUE KEY `unique_status` (`organization_id`, `status_name`),
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Populate Lead Statuses
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
SELECT id, 'New', '#6366f1', 1, 1 FROM organizations;
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
SELECT id, 'Follow-up', '#f59e0b', 0, 2 FROM organizations;
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
SELECT id, 'Interested', '#8b5cf6', 0, 3 FROM organizations;
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
SELECT id, 'Converted', '#10b981', 0, 4 FROM organizations;
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
SELECT id, 'Lost', '#ef4444', 0, 5 FROM organizations;

-- 7. Custom CRM Fields
CREATE TABLE IF NOT EXISTS `custom_lead_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_type` ENUM('TEXT', 'NUMBER', 'DATE', 'OPTION', 'MULTIPLE') DEFAULT 'TEXT',
    `field_options` TEXT NULL,
    `is_readonly` TINYINT(1) DEFAULT 0,
    `is_mandatory` TINYINT(1) DEFAULT 0,
    `is_auto` TINYINT(1) DEFAULT 0,
    `is_filterable` TINYINT(1) DEFAULT 0,
    `display_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `custom_lead_values` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT NOT NULL,
    `field_id` INT NOT NULL,
    `field_value` TEXT NULL,
    UNIQUE KEY `lead_field` (`lead_id`, `field_id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`field_id`) REFERENCES `custom_lead_fields`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Allocation Rules
CREATE TABLE IF NOT EXISTS `allocation_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `rule_name` VARCHAR(100) NOT NULL,
    `rule_type` ENUM('AUTO_ASSIGN', 'ROUND_ROBIN', 'USER_BASED') DEFAULT 'ROUND_ROBIN',
    `criteria_json` TEXT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. System & Organization Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    UNIQUE KEY `org_setting` (`organization_id`, `setting_key`),
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Role and Permission Tracking
CREATE TABLE IF NOT EXISTS `roles_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `role_name` VARCHAR(50) NOT NULL,
    `permissions` TEXT NOT NULL,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. WhatsApp Messages
CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organization_id` int(11) NOT NULL,
  `executive_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Add default sources if missing
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'Facebook' FROM organizations;
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'Google' FROM organizations;
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'WhatsApp' FROM organizations;
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'Just Dial' FROM organizations;
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'Indiamart' FROM organizations;
INSERT IGNORE INTO `lead_sources` (organization_id, source_name) 
SELECT id, 'Poster' FROM organizations;

-- 13. Advanced Privacy & Executive Security (NEW)
ALTER TABLE `organizations` ADD COLUMN IF NOT EXISTS `mask_numbers` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `allow_screenshot` TINYINT(1) DEFAULT 1 AFTER `role`;

-- 14. Performance & Data Integrity
-- Prevent duplicate call syncs from mobile app
ALTER TABLE `call_logs` ADD UNIQUE KEY IF NOT EXISTS `unique_call_sync` (`executive_id`, `mobile`, `call_time`);

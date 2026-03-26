-- Calldesk CRM - Definitive SaaS Schema
-- Last Updated: 2026-03-21

CREATE DATABASE IF NOT EXISTS `calldesk`;
USE `calldesk`;

-- 1. Organizations (Multi-tenant)
CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `address` TEXT NULL,
    `plan` ENUM('free', 'pro', 'enterprise') DEFAULT 'free',
    `status` TINYINT(1) DEFAULT 1,
    `mask_numbers` TINYINT(1) DEFAULT 0, -- Privacy Control
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Users & Executives
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL DEFAULT 1,
    `name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(15) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'executive') NOT NULL DEFAULT 'executive',
    `allow_screenshot` TINYINT(1) DEFAULT 1, -- Privacy Control
    `status` TINYINT(1) DEFAULT 1,
    `api_token` VARCHAR(100) NULL,
    `current_status` ENUM('online', 'offline', 'break', 'on-call') DEFAULT 'offline',
    `last_active_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Lead Sources
CREATE TABLE IF NOT EXISTS `lead_sources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL DEFAULT 1,
    `source_name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `org_source` (`organization_id`, `source_name`),
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Geography Meta
CREATE TABLE IF NOT EXISTS `states` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `districts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `state_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`state_id`) REFERENCES `states`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `blocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `district_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`district_id`) REFERENCES `districts`(`id`) ON DELETE CASCADE
);

-- 5. Leads Portfolio
CREATE TABLE IF NOT EXISTS `leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL DEFAULT 1,
    `name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(15) NOT NULL,
    `alternate_mobile` VARCHAR(15) NULL,
    `source_id` INT NULL,
    `status` VARCHAR(50) DEFAULT 'New',
    `assigned_to` INT NULL,
    `state_id` INT NULL,
    `district_id` INT NULL,
    `block_id` INT NULL,
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`source_id`) REFERENCES `lead_sources`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Call Intelligence
CREATE TABLE IF NOT EXISTS `call_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL DEFAULT 1,
    `mobile` VARCHAR(15) NOT NULL,
    `type` ENUM('Incoming', 'Outgoing', 'Missed') NOT NULL,
    `duration` INT DEFAULT 0,
    `call_time` DATETIME NOT NULL,
    `lead_id` INT NULL,
    `executive_id` INT NULL,
    `recording_path` VARCHAR(255) NULL,
    `is_converted` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_call_sync` (`executive_id`, `mobile`, `call_time`),
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`executive_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. Follow-up Engine
CREATE TABLE IF NOT EXISTS `follow_ups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL DEFAULT 1,
    `lead_id` INT NOT NULL,
    `executive_id` INT NOT NULL,
    `remark` TEXT,
    `next_follow_up_date DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`executive_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Lead Status Customization
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

-- 9. Custom CRM Fields
CREATE TABLE IF NOT EXISTS `custom_lead_fields` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `organization_id` INT NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `field_type` ENUM('TEXT', 'NUMBER', 'DATE', 'OPTION', 'MULTIPLE') DEFAULT 'TEXT',
    `field_options` TEXT NULL,
    `is_readonly` TINYINT(1) DEFAULT 0,
    `is_mandatory` TINYINT(1) DEFAULT 0,
    `is_filterable` TINYINT(1) DEFAULT 1,
    `display_order` INT DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Seed Data
INSERT IGNORE INTO `organizations` (id, name) VALUES (1, 'Default Organization');

-- System Admin (admin123)
REPLACE INTO `users` (id, organization_id, name, mobile, password, role) VALUES 
(1, 1, 'System Admin', '9999999999', '$2y$10$mC7GjtL7E6S2S.mYn8m6u.VvGqj7R1.e5G5G5G5G5G5G5G5G5G5G', 'admin');

-- Default Statuses
INSERT IGNORE INTO `lead_statuses` (organization_id, status_name, color_code, is_default, display_order)
VALUES (1, 'New', '#6366f1', 1, 1),
       (1, 'Follow-up', '#f59e0b', 0, 2),
       (1, 'Interested', '#8b5cf6', 0, 3),
       (1, 'Converted', '#10b981', 0, 4),
       (1, 'Lost', '#ef4444', 0, 5);

<?php
/**
 * LIVE DATABASE UPDATE SCRIPT
 * Run this at: https://calldesk.offerplant.com/live_db_update.php
 */
require_once 'config/db.php';

echo "<h2>Starting Live Database Update...</h2>";

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    if (mysqli_num_rows($res) == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green'>[ADDED] Column '$column' to table '$table'</p>";
        } else {
            echo "<p style='color:red'>[ERROR] Failed to add '$column' to '$table': " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:gray'>[SKIPPED] Column '$column' already exists in '$table'</p>";
    }
}

function addIndexIfNotExists($conn, $table, $indexName, $columns) {
    $res = mysqli_query($conn, "SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
    if (mysqli_num_rows($res) == 0) {
        $sql = "ALTER TABLE `$table` ADD UNIQUE KEY `$indexName` ($columns)";
        if (mysqli_query($conn, $sql)) {
            echo "<p style='color:green'>[INDEXED] Created unique key '$indexName' on '$table'</p>";
        } else {
            echo "<p style='color:red'>[ERROR] Failed to create index on '$table': " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color:gray'>[SKIPPED] Index '$indexName' already exists on '$table'</p>";
    }
}

// 1. Core Organizations Support (SaaS)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB");
mysqli_query($conn, "INSERT IGNORE INTO `organizations` (id, name) VALUES (1, 'Default Organization')");

// 2. SaaS Identity Columns
addColumnIfNotExists($conn, 'users', 'organization_id', "INT NOT NULL DEFAULT 1 AFTER id");
addColumnIfNotExists($conn, 'leads', 'organization_id', "INT NOT NULL DEFAULT 1 AFTER id");
addColumnIfNotExists($conn, 'call_logs', 'organization_id', "INT NOT NULL DEFAULT 1 AFTER id");
addColumnIfNotExists($conn, 'lead_sources', 'organization_id', "INT NOT NULL DEFAULT 1 AFTER id");
addColumnIfNotExists($conn, 'follow_ups', 'organization_id', "INT NOT NULL DEFAULT 1 AFTER id");

// 3. Advanced Privacy Settings
addColumnIfNotExists($conn, 'organizations', 'mask_numbers', "TINYINT(1) DEFAULT 0");
addColumnIfNotExists($conn, 'users', 'allow_screenshot', "TINYINT(1) DEFAULT 1 AFTER role");

// 4. Modern Leads Architecture
addColumnIfNotExists($conn, 'leads', 'alternate_mobile', "VARCHAR(15) NULL AFTER mobile");
addColumnIfNotExists($conn, 'leads', 'state_id', "INT NULL AFTER organization_id");
addColumnIfNotExists($conn, 'leads', 'district_id', "INT NULL AFTER state_id");
addColumnIfNotExists($conn, 'leads', 'block_id', "INT NULL AFTER district_id");

// 5. Data Integrity
addIndexIfNotExists($conn, 'call_logs', 'unique_call_sync', "executive_id, mobile, call_time");

echo "<h3>Update Complete. Please verify the Leads page now.</h3>";
?>

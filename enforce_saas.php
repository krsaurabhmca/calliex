<?php
// enforce_saas.php
require_once 'config/db.php';

function addColumn($conn, $table, $column, $type) {
    $res = mysqli_query($conn, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$column' AND TABLE_SCHEMA = DATABASE()");
    if (mysqli_num_rows($res) == 0) {
        $sql = "ALTER TABLE $table ADD COLUMN $column $type";
        return mysqli_query($conn, $sql);
    }
    return true;
}

// Ensure Organizations Table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS organizations (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// Organizations columns
addColumn($conn, 'organizations', 'plan_type', "VARCHAR(50) DEFAULT 'Trial' AFTER name");
addColumn($conn, 'organizations', 'plan_status', "VARCHAR(50) DEFAULT 'Active' AFTER plan_type");
addColumn($conn, 'organizations', 'expiry_date', "DATE NULL AFTER plan_status");
addColumn($conn, 'organizations', 'billing_cycle', "VARCHAR(50) DEFAULT 'Monthly' AFTER expiry_date");

// Default Org
mysqli_query($conn, "INSERT IGNORE INTO organizations (id, name, plan_type) VALUES (1, 'Main Organization', 'Trial')");
mysqli_query($conn, "UPDATE organizations SET plan_type = 'Trial' WHERE plan_type IS NULL");
mysqli_query($conn, "UPDATE organizations SET plan_status = 'Active' WHERE plan_status IS NULL");
mysqli_query($conn, "UPDATE organizations SET expiry_date = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE expiry_date IS NULL");

// SaaS Multi-tenancy Columns
$saas_tables = ['users', 'lead_sources', 'leads', 'call_logs', 'follow_ups', 'whatsapp_messages'];
foreach ($saas_tables as $table) {
    addColumn($conn, $table, 'organization_id', "INT NULL AFTER id");
    mysqli_query($conn, "UPDATE $table SET organization_id = 1 WHERE organization_id IS NULL");
}

// Special WhatsApp Trigger column
addColumn($conn, 'whatsapp_messages', 'template_category', "VARCHAR(50) DEFAULT 'GENERAL' AFTER is_default");

echo "SaaS Schema enforced successfully using safe migrations.\n";
?>

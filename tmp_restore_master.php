<?php
require_once 'config/db.php';

// Fix Foreign Key (may fail if name is different, but we'll try)
mysqli_query($conn, "ALTER TABLE blocks DROP FOREIGN KEY IF EXISTS blocks_ibfk_1");
mysqli_query($conn, "ALTER TABLE blocks ADD CONSTRAINT fk_block_dist_new FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE CASCADE");

// Fix organization_id for states
// Map all current states to organization 1 if they have no valid org
mysqli_query($conn, "UPDATE states SET organization_id = 1 WHERE organization_id = 0 OR organization_id IS NULL");

// If organization 2 exists, ensure it also has states for testing
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM states WHERE organization_id = 2");
$row = mysqli_fetch_assoc($res);
if ($row['cnt'] == 0) {
    mysqli_query($conn, "INSERT INTO states (organization_id, name) SELECT 2, name FROM states WHERE organization_id = 1");
}

// Seed Statuses for ALL Orgs
$orgs = mysqli_query($conn, "SELECT id FROM organizations");
while($org = mysqli_fetch_assoc($orgs)) {
    $oid = $org['id'];
    mysqli_query($conn, "INSERT IGNORE INTO lead_statuses (organization_id, status_name, color_code, is_default, display_order)
        VALUES ($oid, 'New', '#6366f1', 1, 1),
               ($oid, 'Follow-up', '#f59e0b', 0, 2),
               ($oid, 'Interested', '#8b5cf6', 0, 3),
               ($oid, 'Converted', '#10b981', 0, 4),
               ($oid, 'Lost', '#ef4444', 0, 5)");

    mysqli_query($conn, "INSERT IGNORE INTO lead_sources (organization_id, source_name) 
        VALUES ($oid, 'Website'), ($oid, 'Direct Call'), ($oid, 'WhatsApp Broadcast')");
}

echo "MASTER RESTORE COMPLETED.";
?>

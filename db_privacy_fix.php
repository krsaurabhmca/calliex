<?php
require_once 'config/db.php';

echo "Updating database schema for Privacy Features...\n";

// Add mask_numbers to organizations
$q1 = "ALTER TABLE organizations ADD COLUMN IF NOT EXISTS mask_numbers TINYINT(1) DEFAULT 0";
if(mysqli_query($conn, $q1)) echo "Column 'mask_numbers' added to organizations.\n";
else echo "Error adding mask_numbers: " . mysqli_error($conn) . "\n";

// Add allow_screenshot to users
$q2 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS allow_screenshot TINYINT(1) DEFAULT 1";
if(mysqli_query($conn, $q2)) echo "Column 'allow_screenshot' added to users.\n";
else echo "Error adding allow_screenshot: " . mysqli_error($conn) . "\n";

echo "Migration Complete.\n";
?>

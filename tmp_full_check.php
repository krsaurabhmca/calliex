<?php
require_once 'config/db.php';
$tables = ['organizations', 'users', 'lead_statuses', 'lead_sources', 'states', 'districts', 'blocks', 'custom_lead_fields'];
foreach($tables as $t) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM $t");
    $row = mysqli_fetch_assoc($res);
    echo "$t: " . $row['c'] . " rows\n";
}
?>

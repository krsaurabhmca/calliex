<?php
require_once 'config/db.php';
$tables = ['states', 'districts', 'blocks', 'leads'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $t");
    $row = mysqli_fetch_assoc($res);
    echo "$t: " . $row['cnt'] . " rows\n";
}

$res = mysqli_query($conn, "SELECT id, name, state_id, district_id, block_id FROM leads ORDER BY id DESC LIMIT 5");
echo "\nRECENT LEADS GEOGRAPHY:\n";
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>

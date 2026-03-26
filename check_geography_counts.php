<?php
require_once 'config/db.php';
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM states");
echo "States: " . mysqli_fetch_assoc($r)['c'] . "\n";
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM districts");
echo "Districts: " . mysqli_fetch_assoc($r)['c'] . "\n";
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM blocks");
echo "Blocks: " . mysqli_fetch_assoc($r)['c'] . "\n";

echo "\nSample Districts:\n";
$r = mysqli_query($conn, "SELECT * FROM districts LIMIT 5");
while($row = mysqli_fetch_assoc($r)) print_r($row);
?>

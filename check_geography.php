<?php
require_once 'config/db.php';
echo "--- DISTRICTS ---\n";
$res = mysqli_query($conn, "DESCRIBE districts");
while($row = mysqli_fetch_assoc($res)) print_r($row);
echo "\n--- BLOCKS ---\n";
$res = mysqli_query($conn, "DESCRIBE blocks");
while($row = mysqli_fetch_assoc($res)) print_r($row);
?>

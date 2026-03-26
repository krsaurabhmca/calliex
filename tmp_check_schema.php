<?php
require_once 'config/db.php';
$res = mysqli_query($conn, "SHOW CREATE TABLE blocks");
$row = mysqli_fetch_assoc($res);
echo "BLOCKS TABLE SCHEMA:\n" . $row['Create Table'] . "\n";
?>

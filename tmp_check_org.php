<?php
require_once 'config/db.php';
$res = mysqli_query($conn, "SELECT * FROM states");
while($row = mysqli_fetch_assoc($res)) print_r($row);

$res = mysqli_query($conn, "SELECT id, organization_id, name FROM users WHERE id=1");
echo "USER 1:\n";
print_r(mysqli_fetch_assoc($res));
?>

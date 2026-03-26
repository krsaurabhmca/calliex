<?php
require_once 'config/db.php';
$res = mysqli_query($conn, "SHOW COLUMNS FROM call_logs");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ", ";
}
?>

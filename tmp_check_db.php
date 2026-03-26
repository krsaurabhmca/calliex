<?php
$conn = mysqli_connect('localhost', 'root', '', 'calldesk');
if(!$conn) die('Connection Failed');
$res = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . "\n";
}
?>

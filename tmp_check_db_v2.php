<?php
$conn = mysqli_connect('localhost', 'root', '', 'calldesk');
if(!$conn) die('Connection Failed');
$res = mysqli_query($conn, "DESCRIBE organizations");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . " | " . $row[1] . "\n";
}
echo "---\n";
$res = mysqli_query($conn, "DESCRIBE system_settings");
while($row = mysqli_fetch_row($res)) {
    echo $row[0] . " | " . $row[1] . "\n";
}
?>

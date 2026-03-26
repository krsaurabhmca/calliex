<?php
// api/geography.php
require_once 'auth_check.php';
require_once '../includes/functions.php';

$type = $_GET['type'] ?? '';
$org_id = $auth_user['organization_id'];

if ($type === 'districts') {
    $state_id = (int)($_GET['state_id'] ?? 0);
    $sql = "SELECT id, name FROM districts WHERE state_id = $state_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    sendResponse(true, 'Districts fetched', $data);
} elseif ($type === 'blocks') {
    $district_id = (int)($_GET['district_id'] ?? 0);
    $sql = "SELECT id, name FROM blocks WHERE district_id = $district_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    sendResponse(true, 'Blocks fetched', $data);
} elseif ($type === 'states') {
    $sql = "SELECT id, name FROM states WHERE organization_id = $org_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) $data[] = $row;
    sendResponse(true, 'States fetched', $data);
} else {
    sendResponse(false, 'Invalid type', null, 400);
}
?>

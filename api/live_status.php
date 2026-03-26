<?php
// api/live_status.php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$org_id = $_SESSION['organization_id'] ?? ($_GET['org_id'] ?? null);

if (!$org_id) {
    echo json_encode(['success' => false, 'message' => 'Org ID missing']);
    exit;
}

// Heartbeat update for current user
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET last_active_at = NOW(), current_status = IF(current_status = 'offline' OR current_status IS NULL, 'online', current_status) WHERE id = $uid");
}

$sql = "SELECT id, name, last_active_at, current_status FROM users WHERE organization_id = $org_id AND role = 'executive'";
$res = mysqli_query($conn, $sql);
$team = [];

while ($u = mysqli_fetch_assoc($res)) {
    $is_online = (strtotime($u['last_active_at'] ?? '2000-01-01') > strtotime('-15 minutes'));
    $status = $is_online ? ($u['current_status'] ?: 'online') : 'offline';
    
    $status_color = '#94a3b8'; // Offline
    if ($is_online) {
        switch ($status) {
            case 'online': $status_color = '#10b981'; break;
            case 'break': $status_color = '#f59e0b'; break;
            case 'on-call': $status_color = '#6366f1'; break;
        }
    }

    $team[] = [
        'id' => $u['id'],
        'name' => $u['name'],
        'status' => $status,
        'status_color' => $status_color,
        'initials' => strtoupper(substr($u['name'], 0, 1))
    ];
}

echo json_encode(['success' => true, 'team' => $team]);
?>

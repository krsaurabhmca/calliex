<?php
// api/calendar_events.php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$org_id = getOrgId();
$role = $_SESSION['role'];
$type = $_GET['type'] ?? 'leads'; // 'leads' or 'calls'

$events = [];

if ($type === 'leads') {
    // 1. Fetch Follow-ups
    $where = "WHERE l.organization_id = $org_id AND f.next_follow_up_date IS NOT NULL";
    if ($role !== 'admin') $where .= " AND l.assigned_to = $user_id";

    $sql = "SELECT f.*, l.name as lead_name, l.status as lead_status, l.id as lead_id
            FROM follow_ups f 
            JOIN leads l ON f.lead_id = l.id 
            $where";

    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $color = '#cbd5e1'; 
        switch ($row['lead_status']) {
            case 'Converted': $color = '#10b981'; break;
            case 'Interested': $color = '#3b82f6'; break;
            case 'Follow-up': $color = '#6366f1'; break;
            case 'Lost': $color = '#ef4444'; break;
        }
        $events[] = [
            'id' => 'task_' . $row['id'],
            'title' => '⚡ ' . $row['lead_name'],
            'start' => $row['next_follow_up_date'],
            'description' => $row['remark'],
            'color' => $color,
            'url' => 'lead_view.php?id=' . $row['lead_id'],
            'allDay' => true
        ];
    }

    // 2. Fetch Lead Creation
    $lead_sql = "SELECT id, name, created_at FROM leads WHERE organization_id = $org_id";
    if ($role !== 'admin') $lead_sql .= " AND assigned_to = $user_id";
    $lead_res = mysqli_query($conn, $lead_sql);
    while ($row = mysqli_fetch_assoc($lead_res)) {
        $events[] = [
            'id' => 'lead_' . $row['id'],
            'title' => '👤 ' . $row['name'],
            'start' => date('Y-m-d', strtotime($row['created_at'])),
            'color' => '#1e293b',
            'url' => 'lead_view.php?id=' . $row['id'],
            'allDay' => true
        ];
    }
} else {
    // 3. Fetch Call Logs (Aggregated by day & type)
    $call_sql = "SELECT DATE(call_time) as call_date, type, COUNT(*) as call_count 
                FROM call_logs c 
                WHERE organization_id = $org_id";
    if ($role !== 'admin') $call_sql .= " AND executive_id = $user_id";
    $call_sql .= " GROUP BY DATE(call_time), type ORDER BY call_date DESC, type ASC";

    $call_res = mysqli_query($conn, $call_sql);
    while ($row = mysqli_fetch_assoc($call_res)) {
        $type_label = strtoupper($row['type']);
        $color = '#64748b'; // default
        $icon = '📞';
        
        if (strpos($type_label, 'INCOMING') !== false) { $color = '#10b981'; $icon = '📥'; }
        elseif (strpos($type_label, 'OUTGOING') !== false) { $color = '#3b82f6'; $icon = '📤'; }
        elseif (strpos($type_label, 'MISSED') !== false) { $color = '#ef4444'; $icon = '❌'; }
        elseif (strpos($type_label, 'REJECTED') !== false) { $color = '#f59e0b'; $icon = '🚫'; }

        $events[] = [
            'id' => 'call_' . $row['call_date'] . '_' . $row['type'],
            'title' => $icon . ' ' . $row['call_count'] . ' ' . $type_label,
            'start' => $row['call_date'],
            'color' => $color,
            'textColor' => '#ffffff',
            'allDay' => true,
            'description' => "Total " . $type_label . " calls on this day",
            'url' => 'call_logs.php?date=' . $row['call_date'] . '&type=' . $row['type']
        ];
    }
}

echo json_encode($events);
?>

<?php
// export_csv.php - Intelligence Export Layer
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$org_id = getOrgId() ?: 1; // Fallback to 1 for legacy/unsynced accounts
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Set Headers for Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Calldesk_Report_' . date('Y-m-d_His') . '.csv"');

// 2. Open output stream
$output = fopen('php://output', 'w');

// 3. Define Header Columns (Multi-Angle View)
fputcsv($output, [
    'Lead ID', 
    'Lead Name', 
    'Phone Number', 
    'Source Channel', 
    'Current Status', 
    'Assigned To (Rep)', 
    'Created Date', 
    'Total Interactions', 
    'Last Remark (Snippet)', 
    'Next Scheduled Follow-up',
    'Success Velocity (Days)'
]);

// 4. Detailed Multi-Join Query
$sql = "SELECT 
    l.id, 
    l.name, 
    l.phone, 
    s.source_name, 
    l.status, 
    u.name as rep_name, 
    l.created_at,
    (SELECT COUNT(*) FROM follow_ups WHERE lead_id = l.id) as interaction_count,
    (SELECT remark FROM follow_ups WHERE lead_id = l.id ORDER BY id DESC LIMIT 1) as last_remark,
    (SELECT next_follow_up_date FROM follow_ups WHERE lead_id = l.id ORDER BY id DESC LIMIT 1) as next_date,
    DATEDIFF(IF(l.status='Converted', NOW(), l.created_at), l.created_at) as cycle_days
FROM leads l
LEFT JOIN lead_sources s ON l.source_id = s.id
LEFT JOIN users u ON l.assigned_to = u.id
WHERE l.organization_id = " . (int)$org_id . " 
AND DATE(l.created_at) BETWEEN '$start_date' AND '$end_date'
ORDER BY l.created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    fputcsv($output, ['Error executing report query: ' . mysqli_error($conn)]);
    fclose($output);
    exit();
}

// 5. Stream Results to CSV
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['phone'],
        $row['source_name'],
        $row['status'],
        $row['rep_name'],
        date('M d, Y', strtotime($row['created_at'])),
        $row['interaction_count'],
        $row['last_remark'] ?: 'No Interactions Yet',
        $row['next_date'] ?: 'N/A',
        $row['cycle_days'] . ' Days'
    ]);
}

fclose($output);
exit();
?>

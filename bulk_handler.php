<?php
// bulk_handler.php - High Volume Operations Data Layer
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$org_id = getOrgId();
$action = $_POST['action'] ?? '';
$lead_ids = $_POST['lead_ids'] ?? [];

if (empty($lead_ids)) {
    header("Location: leads.php?error=No leads selected");
    exit();
}

// Convert to safety-array
$ids_csv = implode(',', array_map('intval', $lead_ids));

if ($action === 'delete') {
    // 1. Delete Interactions
    mysqli_query($conn, "DELETE FROM follow_ups WHERE lead_id IN ($ids_csv) AND organization_id = $org_id");
    // 2. Delete Call Logs
    mysqli_query($conn, "DELETE FROM call_logs WHERE lead_id IN ($ids_csv) AND organization_id = $org_id");
    // 3. Delete Lead Details
    mysqli_query($conn, "DELETE FROM leads WHERE id IN ($ids_csv) AND organization_id = $org_id");
    
    $msg = count($lead_ids) . " leads removed permanently.";
} 
elseif ($action === 'assign') {
    $exec_id = (int)($_POST['executive_id'] ?? 0);
    if ($exec_id <= 0) {
        header("Location: leads.php?error=Please select an executive for assignment");
        exit();
    }
    
    // Multi-tenant safe update
    mysqli_query($conn, "UPDATE leads SET assigned_to = $exec_id WHERE id IN ($ids_csv) AND organization_id = $org_id");
    
    $msg = count($lead_ids) . " leads successfully re-assigned.";
}

header("Location: leads.php?success=" . urlencode($msg));
exit();

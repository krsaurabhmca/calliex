<?php
// api/assign.php
require_once 'auth_check.php';

if ($auth_user['role'] !== 'admin') {
    sendResponse(false, 'Unauthorized: Admin access required', null, 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method', null, 405);
}

$lead_id = (int)($_POST['lead_id'] ?? 0);
$assign_to = (int)($_POST['assign_to'] ?? 0); // User id of the executive

if ($lead_id <= 0 || $assign_to <= 0) {
    sendResponse(false, 'Lead ID and Executive ID (assign_to) are required', null, 400);
}

// Check if user exists and is an executive within the same organization
$org_id = $auth_user['organization_id'];
$user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $assign_to AND organization_id = $org_id AND role = 'executive' AND status = 1");
if (mysqli_num_rows($user_check) === 0) {
    sendResponse(false, 'Invalid executive ID or executive is inactive in your organization', null, 400);
}

$sql = "UPDATE leads SET assigned_to = $assign_to WHERE id = $lead_id AND organization_id = $org_id";

if (mysqli_query($conn, $sql)) {
    // --- WhatsApp Automation on Assign ---
    $wa_on_assign = getOrgSetting($conn, $org_id, 'whatsapp_on_assign', '0') === '1';
    if ($wa_on_assign) {
        require_once '../includes/whatsapp_helper.php';
        $wa = new WhatsAppHelper($conn, $org_id);
        
        // Get lead/executive details for custom message
        $lead_res = mysqli_query($conn, "SELECT name, mobile FROM leads WHERE id = $lead_id");
        $lead = mysqli_fetch_assoc($lead_res);
        $exec_res = mysqli_query($conn, "SELECT name FROM users WHERE id = $assign_to");
        $exec = mysqli_fetch_assoc($exec_res);

        if ($lead && $exec) {
            $msg_body = "Hi {$lead['name']}, our executive {$exec['name']} has been assigned to assist you. You can expect a call from us shortly. Regards, " . getOrgSetting($conn, $org_id, 'company_name', 'CallDesk');
            $wa->sendMessage($lead['mobile'], $msg_body);
        }
    }
    
    sendResponse(true, 'Lead assigned successfully', ['lead_id' => $lead_id, 'assigned_to' => $assign_to]);
} else {
    sendResponse(false, 'Database error: ' . mysqli_error($conn), null, 500);
}
?>

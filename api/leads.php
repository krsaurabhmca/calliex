<?php
// api/leads.php
require_once 'auth_check.php';
require_once '../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$executive_id = $auth_user['id'];
$org_id = $auth_user['organization_id'];
$role = $auth_user['role'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'form_metadata') {
        // Fetch metadata for dynamic form rendering on mobile
        $custom_fields = getCustomFields($conn, $org_id);
        $statuses = getLeadStatuses($conn, $org_id);
        $sources = [];
        $src_res = mysqli_query($conn, "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC");
        while ($s = mysqli_fetch_assoc($src_res)) $sources[] = $s;
        
        sendResponse(true, 'Metadata fetched', [
            'custom_fields' => $custom_fields,
            'statuses' => $statuses,
            'sources' => $sources,
            'user' => [
                'role' => $role,
                'mask_numbers' => (int)(getOrgPrivacySettings($conn, $org_id)['mask_numbers'] ?? 0)
            ]
        ]);

    } elseif ($action === 'executives') {
        // Fetch active executives (Admin only)
        if ($role !== 'admin') sendResponse(false, 'Unauthorized', null, 403);
        $sql = "SELECT id, name FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1";
        $result = mysqli_query($conn, $sql);
        $executives = [];
        while ($row = mysqli_fetch_assoc($result)) $executives[] = $row;
        sendResponse(true, 'Executives fetched', $executives);

    } else {
        // List leads with filters
        $search = mysqli_real_escape_string($conn, $_REQUEST['search'] ?? '');
        $status = mysqli_real_escape_string($conn, $_REQUEST['status'] ?? '');
        
        $where = "l.organization_id = " . (int)$org_id;
        if ($role !== 'admin') $where .= " AND l.assigned_to = " . (int)$executive_id;
        if ($search) $where .= " AND (l.name LIKE '%$search%' OR l.mobile LIKE '%$search%')";
        if ($status) $where .= " AND l.status = '$status'";
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id > 0) $where .= " AND l.id = $id";
        
        $sql = "SELECT l.*, s.source_name, u.name as assigned_to_name, 
                st.name as state_name, dt.name as district_name, bl.name as block_name
                FROM leads l 
                LEFT JOIN lead_sources s ON l.source_id = s.id 
                LEFT JOIN users u ON l.assigned_to = u.id 
                LEFT JOIN states st ON l.state_id = st.id
                LEFT JOIN districts dt ON l.district_id = dt.id
                LEFT JOIN blocks bl ON l.block_id = bl.id
                WHERE $where ORDER BY l.id DESC LIMIT 100";
        
        $result = mysqli_query($conn, $sql);
        if (!$result) sendResponse(false, 'Database Error: ' . mysqli_error($conn), null, 500);
        
        $leads = [];
        $priv = getOrgPrivacySettings($conn, $org_id);
        $mask = (int)($priv['mask_numbers'] ?? 0) === 1 && $role !== 'admin';
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['display_mobile'] = $row['mobile'];
            if ($mask && !empty($row['mobile'])) {
                $row['display_mobile'] = substr($row['mobile'], 0, -5) . "XXXXX";
                if (!empty($row['alternate_mobile'])) $row['alternate_mobile'] = substr($row['alternate_mobile'], 0, -5) . "XXXXX";
            }
            if ($id > 0) {
                // Fetch custom values for single lead
                $row['custom_values'] = [];
                $cv_res = mysqli_query($conn, "SELECT field_id, field_value FROM custom_lead_values WHERE lead_id = $id");
                while ($cv = mysqli_fetch_assoc($cv_res)) $row['custom_values'][$cv['field_id']] = $cv['field_value'];
            }
            $leads[] = $row;
        }
        sendResponse(true, 'Leads fetched successfully', $id > 0 ? ($leads[0] ?? null) : $leads);
    }

} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'bulk_assign') {
        if ($role !== 'admin') sendResponse(false, 'Unauthorized', null, 403);
        $lead_ids_str = $_POST['lead_ids'] ?? '';
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);
        if (empty($lead_ids_str) || empty($assigned_to)) sendResponse(false, 'Lead IDs and Executive ID are required', null, 400);

        $ids_array = array_map('intval', explode(',', $lead_ids_str));
        $sanitized_ids = implode(',', $ids_array);
        if (empty($sanitized_ids)) sendResponse(false, 'Invalid Lead IDs', null, 400);

        $sql = "UPDATE leads SET assigned_to = $assigned_to WHERE id IN ($sanitized_ids) AND organization_id = $org_id";
        if (mysqli_query($conn, $sql)) sendResponse(true, 'Leads assigned successfully');
        else sendResponse(false, 'Error assigning leads: ' . mysqli_error($conn), null, 500);

    } else {
        // Add new lead
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile'] ?? '');
        $alternate_mobile = mysqli_real_escape_string($conn, $_POST['alternate_mobile'] ?? '');
        $source_id = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : 0;
        
        // Map Lead Source by Name if ID is not provided
        if (!$source_id && !empty($_POST['source'])) {
            $src_name = mysqli_real_escape_string($conn, $_POST['source']);
            $src_res = mysqli_query($conn, "SELECT id FROM lead_sources WHERE name = '$src_name' AND (organization_id = $org_id OR organization_id IS NULL) LIMIT 1");
            if ($src_res && mysqli_num_rows($src_res) > 0) {
                $source_id = (int)mysqli_fetch_assoc($src_res)['id'];
            }
        }

        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'New');
        $remarks = mysqli_real_escape_string($conn, $_POST['remarks'] ?? '');
        $assigned_to = (int)($_POST['assigned_to'] ?? 0);

        // Geography
        $state_id = !empty($_POST['state_id']) ? (int)$_POST['state_id'] : "NULL";
        $district_id = !empty($_POST['district_id']) ? (int)$_POST['district_id'] : "NULL";
        $block_id = !empty($_POST['block_id']) ? (int)$_POST['block_id'] : "NULL";

        if (empty($name) || empty($mobile)) sendResponse(false, 'Name and Mobile are required', null, 400);

        // Duplicate check
        $check_sql = "SELECT id FROM leads WHERE mobile = '$mobile' AND organization_id = $org_id LIMIT 1";
        if (mysqli_num_rows(mysqli_query($conn, $check_sql)) > 0) {
            sendResponse(false, 'This mobile number is already registered.', null, 400);
        }
        
        $sql = "INSERT INTO leads (organization_id, name, mobile, alternate_mobile, source_id, status, assigned_to, remarks, state_id, district_id, block_id) 
                VALUES ($org_id, '$name', '$mobile', '$alternate_mobile', " . ($source_id ?: "NULL") . ", '$status', " . ($assigned_to ?: "NULL") . ", '$remarks', $state_id, $district_id, $block_id)";
                
        if (mysqli_query($conn, $sql)) {
            $lead_id = mysqli_insert_id($conn);
            
            // Auto Allocate if no assigned_to
            if (!$assigned_to) allocateLead($conn, $lead_id);
            
            // Custom Fields
            $custom_fields_meta = getCustomFields($conn, $org_id);
            $cf_data = isset($_POST['custom_fields']) ? json_decode($_POST['custom_fields'], true) : [];
            if (!is_array($cf_data)) $cf_data = [];

            foreach ($custom_fields_meta as $f) {
                $field_id = $f['id'];
                if ($f['field_type'] === 'AUTO') {
                    $val = "CD-" . date('Y') . "-" . str_pad($lead_id, 4, '0', STR_PAD_LEFT);
                    saveCustomValue($conn, $lead_id, $field_id, $val);
                } else {
                    // Mapping priority: 1. cf_ID parameter, 2. custom_fields JSON object
                    $val = $_POST["cf_$field_id"] ?? ($cf_data[$field_id] ?? null);
                    if ($val !== null) {
                        saveCustomValue($conn, $lead_id, $field_id, mysqli_real_escape_string($conn, $val));
                    }
                }
            }

            // Automation: Welcome WhatsApp
            if (getOrgSetting($conn, $org_id, 'whatsapp_welcome_enabled', '0') === '1') {
                require_once '../includes/whatsapp_helper.php';
                $wa = new WhatsAppHelper($conn, $org_id);
                $msg_body = getWhatsAppTemplate($conn, $org_id, 'WELCOME', $name);
                if (!$msg_body) {
                    $msg_body = "Hello $name, thank you for your inquiry with " . getOrgSetting($conn, $org_id, 'company_name', 'our team') . ". We have received your request and will get back to you shortly.";
                }
                $wa->sendMessage($mobile, $msg_body);
            }

            sendResponse(true, 'Lead added successfully', ['id' => $lead_id]);
        } else {
            sendResponse(false, 'Error adding lead: ' . mysqli_error($conn), null, 500);
        }
    }
} elseif ($method === 'PUT') {
    // Update lead details
    parse_str(file_get_contents("php://input"), $_PUT);
    $lead_id = (int)($_PUT['id'] ?? 0);
    if ($lead_id <= 0) sendResponse(false, 'Lead ID is required', null, 400);
    
    $where_security = $role === 'admin' ? "" : " AND assigned_to = $executive_id";
    $name = mysqli_real_escape_string($conn, $_PUT['name'] ?? '');
    $alternate_mobile = mysqli_real_escape_string($conn, $_PUT['alternate_mobile'] ?? '');
    $source_id = (int)($_PUT['source_id'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_PUT['status'] ?? '');
    $state_id = !empty($_PUT['state_id']) ? (int)$_PUT['state_id'] : "NULL";
    $district_id = !empty($_PUT['district_id']) ? (int)$_PUT['district_id'] : "NULL";
    $block_id = !empty($_PUT['block_id']) ? (int)$_PUT['block_id'] : "NULL";

    $sql = "UPDATE leads SET ";
    if ($name) $sql .= "name = '$name', ";
    $sql .= "alternate_mobile = '$alternate_mobile', ";
    if ($source_id) $sql .= "source_id = $source_id, ";
    if ($status) $sql .= "status = '$status', ";
    $sql .= "state_id = $state_id, district_id = $district_id, block_id = $block_id ";
    $sql .= "WHERE id = $lead_id AND organization_id = $org_id" . $where_security;

    if (mysqli_query($conn, $sql)) {
        // Update Custom Fields
        $cf_data = isset($_PUT['custom_fields']) ? json_decode($_PUT['custom_fields'], true) : [];
        $custom_fields_meta = getCustomFields($conn, $org_id);
        foreach ($custom_fields_meta as $f) {
            $f_id = $f['id'];
            $f_val = $_PUT["cf_$f_id"] ?? ($cf_data[$f_id] ?? null);
            if ($f_val !== null) {
                saveCustomValue($conn, $lead_id, (int)$f_id, mysqli_real_escape_string($conn, $f_val));
            }
        }
        sendResponse(true, 'Lead updated successfully');
    } else {
        sendResponse(false, 'Error updating lead: ' . mysqli_error($conn), null, 500);
    }
} elseif ($method === 'DELETE') {
    // Delete lead (Admin only)
    if ($role !== 'admin') sendResponse(false, 'Unauthorized', null, 403);
    
    $id = (int)($_REQUEST['id'] ?? 0);
    if ($id <= 0) sendResponse(false, 'Lead ID is required', null, 400);

    $sql = "DELETE FROM leads WHERE id = $id AND organization_id = $org_id";
    if (mysqli_query($conn, $sql)) sendResponse(true, 'Lead deleted successfully');
    else sendResponse(false, 'Error deleting lead: ' . mysqli_error($conn), null, 500);
}
?>

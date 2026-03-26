<?php
// includes/functions.php

/**
 * Fetch lead statuses for an organization
 */
function getLeadStatuses($conn, $org_id) {
    $sql = "SELECT * FROM lead_statuses WHERE organization_id = $org_id ORDER BY display_order ASC";
    $result = mysqli_query($conn, $sql);
    $statuses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $statuses[] = $row;
    }
    return $statuses;
}

/**
 * Fetch custom fields for an organization
 */
function getCustomFields($conn, $org_id, $is_active_only = true) {
    $status_filter = $is_active_only ? "AND status = 1" : "";
    $sql = "SELECT * FROM custom_lead_fields 
            WHERE organization_id = $org_id $status_filter 
            ORDER BY display_order ASC";
    $result = mysqli_query($conn, $sql);
    $fields = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['field_options']) {
            $row['options'] = json_decode($row['field_options'], true);
        }
        $fields[] = $row;
    }
    return $fields;
}

/**
 * Get custom field values for a specific lead
 */
function getLeadCustomValues($conn, $lead_id) {
    $sql = "SELECT v.*, f.field_name, f.field_type 
            FROM custom_lead_values v
            JOIN custom_lead_fields f ON v.field_id = f.id
            WHERE v.lead_id = $lead_id";
    $result = mysqli_query($conn, $sql);
    $values = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $values[$row['field_id']] = $row['field_value'];
    }
    return $values;
}

/**
 * Fetch States
 */
function getStates($conn, $org_id) {
    $sql = "SELECT * FROM states WHERE organization_id = $org_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $states = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $states[] = $row;
    }
    return $states;
}

/**
 * Fetch Districts by State
 */
function getDistricts($conn, $state_id) {
    if (!$state_id) return [];
    $sql = "SELECT * FROM districts WHERE state_id = $state_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $districts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $districts[] = $row;
    }
    return $districts;
}

/**
 * Fetch Blocks by District
 */
function getBlocks($conn, $district_id) {
    if (!$district_id) return [];
    $sql = "SELECT * FROM blocks WHERE district_id = $district_id AND status = 1 ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    $blocks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $blocks[] = $row;
    }
    return $blocks;
}

/**
 * Save custom field value for a lead
 */
function saveCustomValue($conn, $lead_id, $field_id, $value) {
    $value = mysqli_real_escape_string($conn, $value);
    $sql = "INSERT INTO custom_lead_values (lead_id, field_id, field_value) 
            VALUES ($lead_id, $field_id, '$value')
            ON DUPLICATE KEY UPDATE field_value = '$value'";
    return mysqli_query($conn, $sql);
}

/**
 * Log live activity / Update user status
 */
function updateLiveStatus($conn, $user_id, $status = 'online') {
    if (!$user_id) return false;
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE users SET 
            last_active_at = '$now', 
            current_status = '$status' 
            WHERE id = $user_id";
    return mysqli_query($conn, $sql);
}

/**
 * Get an organization setting
 */
function getOrgSetting($conn, $org_id, $key, $default = '') {
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT setting_value FROM system_settings WHERE organization_id = $org_id AND setting_key = '$key'";
    $res = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($res)) {
        return $row['setting_value'];
    }
    return $default;
}

/**
 * Save an organization setting
 */
function saveOrgSetting($conn, $org_id, $key, $value) {
    if (!$org_id) return false;
    $key = mysqli_real_escape_string($conn, $key);
    $value = mysqli_real_escape_string($conn, $value);
    $sql = "INSERT INTO system_settings (organization_id, setting_key, setting_value) 
            VALUES ($org_id, '$key', '$value')
            ON DUPLICATE KEY UPDATE setting_value = '$value'";
    return mysqli_query($conn, $sql);
}

/**
 * Automatic Lead Allocation Logic
 */
function allocateLead($conn, $lead_id) {
    $res = mysqli_query($conn, "SELECT organization_id FROM leads WHERE id = $lead_id");
    if (!$row = mysqli_fetch_assoc($res)) return false;
    $org_id = $row['organization_id'];

    $rule_sql = "SELECT * FROM allocation_rules WHERE organization_id = $org_id AND status = 1 LIMIT 1";
    $rule_res = mysqli_query($conn, $rule_sql);
    if ($rule = mysqli_fetch_assoc($rule_res)) {
        if ($rule['rule_type'] === 'ROUND_ROBIN') {
            // Get active executives
            $execs = [];
            $er = mysqli_query($conn, "SELECT id FROM users WHERE organization_id = $org_id AND role = 'executive' AND status = 1");
            while ($e = mysqli_fetch_assoc($er)) $execs[] = $e['id'];
            
            if (!empty($execs)) {
                // Get last assigned user ID
                $last_user = (int)getOrgSetting($conn, $org_id, 'last_allocated_user_id', '0');
                $index = array_search($last_user, $execs);
                $next_index = ($index === false || $index >= count($execs) - 1) ? 0 : $index + 1;
                $target_user = $execs[$next_index];
                
                mysqli_query($conn, "UPDATE leads SET assigned_to = $target_user WHERE id = $lead_id");
                saveOrgSetting($conn, $org_id, 'last_allocated_user_id', $target_user);
                return $target_user;
            }
        }
    }
    return false;
}
/**
 * Get organization privacy settings
 */
function getOrgPrivacySettings($conn, $org_id) {
    if (!$org_id) return ['mask_numbers' => 0];
    $sql = "SELECT mask_numbers FROM organizations WHERE id = $org_id";
    $res = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($res)) {
        return $row;
    }
    return ['mask_numbers' => 0];
}

/**
 * Format seconds into HH:MM:SS
 */
function formatDuration($seconds) {
    if (!$seconds) return "00:00:00";
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
/**
 * Get the effective WhatsApp template for a category
 */
function getWhatsAppTemplate($conn, $org_id, $category, $name = '') {
    $category = mysqli_real_escape_string($conn, $category);
    $sql = "SELECT message FROM whatsapp_messages 
            WHERE organization_id = $org_id AND template_category = '$category' AND is_default = 1 
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($res)) {
        $msg = $row['message'];
        if ($name) $msg = str_replace('{name}', $name, $msg);
        return $msg;
    }
    return "";
}
?>

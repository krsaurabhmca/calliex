<?php
/**
 * CallDesk API Integration Samples
 * This script demonstrates how to interact with the CallDesk API using PHP.
 */

// 1. CONFIGURATION
$BASE_URL = "http://localhost/calldesk/api/"; // Replace with your actual URL
$API_TOKEN = "YOUR_ORG_API_KEY"; // Get this from Settings -> API & Connectors

/**
 * Helper function to make API requests
 */
function callAPI($method, $endpoint, $data = [], $token = "") {
    global $BASE_URL;
    $url = $BASE_URL . $endpoint;
    
    $curl = curl_init();
    
    // Set headers (Custom API Key Authentication)
    $headers = [
        "X-API-KEY: " . $token,
        "Accept: application/json"
    ];

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
            break;
        default:
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    $result = curl_exec($curl);
    if(!$result) {
        return ["success" => false, "message" => "CURL Error: " . curl_error($curl)];
    }
    curl_close($curl);
    
    return json_decode($result, true);
}

// --- SAMPLE TASKS ---

// A. CREATE A NEW LEAD
echo "--- Adding Lead ---\n";
$newLead = [
    'name' => 'John Doe',
    'mobile' => '9876543210',
    'status' => 'New',
    'remarks' => 'Added via PHP Sample Script'
];
$resp = callAPI("POST", "leads.php", $newLead, $API_TOKEN);
print_r($resp);

if ($resp['success']) {
    $leadId = $resp['data']['id'];

    // B. ASSIGN LEAD TO EXECUTIVE (Only for Admin API keys)
    echo "\n--- Assigning Lead ---\n";
    $assignData = [
        'lead_id' => $leadId,
        'assign_to' => 2 // ID of the executive
    ];
    $resp = callAPI("POST", "assign.php", $assignData, $API_TOKEN);
    print_r($resp);

    // C. GET FOLLOW-UP HISTORY / LIST
    echo "\n--- Fetching Follow-ups ---\n";
    $resp = callAPI("GET", "followups.php", ['date_filter' => 'today'], $API_TOKEN);
    print_r($resp);
}

// D. SEND WHATSAPP MESSAGE (Server-Side via Green-API)
echo "\n--- Sending WhatsApp ---\n";
$waData = [
    'mobile' => '919876543210',
    'message' => 'Hello! This is an automated message from CallDesk CRM.'
];
$resp = callAPI("POST", "send_whatsapp.php", $waData, $API_TOKEN);
print_r($resp);

// E. UPLOAD CALL RECORDING
echo "\n--- Uploading Recording ---\n";
// Note: Recordings use multipart/form-data
function uploadRecording($filePath, $mobile, $callTime, $token) {
    global $BASE_URL;
    $url = $BASE_URL . "upload_recording.php";
    
    $cfile = new CURLFile($filePath, 'audio/mpeg', basename($filePath));
    $data = [
        'recording' => $cfile,
        'mobile' => $mobile,
        'call_time' => $callTime
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ["X-API-KEY: " . $token]);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return json_decode($result, true);
}
// Usage: uploadRecording('path/to/file.mp3', '9876543210', '2026-03-21 22:30:00', $API_TOKEN);

echo "\nDone.\n";

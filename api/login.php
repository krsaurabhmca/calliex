<?php
// api/login.php
header('Content-Type: application/json');
require_once '../config/db.php';

function sendResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// 1. Get input (Handle both POST and JSON)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];
}

$mobile = mysqli_real_escape_string($conn, $input['mobile'] ?? '');
$password = $input['password'] ?? '';

if (empty($mobile) || empty($password)) {
    sendResponse(false, 'Mobile number and password are required', null, 400);
}

$sql = "SELECT u.*, o.name as organization_name 
        FROM users u 
        LEFT JOIN organizations o ON u.organization_id = o.id 
        WHERE u.mobile = '$mobile'";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    
    if (password_verify($password, $user['password'])) {
        if ($user['status'] == 0) {
            sendResponse(false, 'Account is disabled', null, 403);
        }
        
        // Generate or reuse token
        $token = $user['api_token'];
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            mysqli_query($conn, "UPDATE users SET api_token = '$token' WHERE id = " . $user['id']);
        }
        
        $sql_org = "SELECT mask_numbers, plan_type, expiry_date FROM organizations WHERE id = " . (int)$user['organization_id'];
        $res_org = mysqli_query($conn, $sql_org);
        $org_data = ($res_org) ? mysqli_fetch_assoc($res_org) : [];
        $mask_numbers = (int)($org_data['mask_numbers'] ?? 0);

        sendResponse(true, 'Login successful', [
            'token' => $token,
            'user' => [
                'id' => (int)$user['id'],
                'organization_id' => (int)$user['organization_id'],
                'organization_name' => $user['organization_name'],
                'name' => $user['name'],
                'role' => $user['role'],
                'allow_screenshot' => (int)($user['allow_screenshot'] ?? 1),
                'mask_numbers' => $mask_numbers,
                'plan_type' => $org_data['plan_type'] ?? 'Trial',
                'expiry_date' => $org_data['expiry_date'] ?? null
            ]
        ]);
    }
}

sendResponse(false, 'Invalid credentials', null, 401);
?>

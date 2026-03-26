<?php
// api/send_whatsapp.php
require_once '../config/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/whatsapp_helper.php';

// Check Auth (Session based for web or token based for mobile)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $org_id = getOrgId();
} else {
    // API Auth for mobile
    require_once 'auth_check.php';
    $user_id = $auth_user['id'];
    $org_id = $auth_user['organization_id'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$mobile = $_POST['mobile'] ?? '';
$message = $_POST['message'] ?? '';

if (empty($mobile) || empty($message)) {
    die(json_encode(['success' => false, 'message' => 'Mobile and message are required']));
}

$whatsapp = new WhatsAppHelper($conn, $org_id);
$result = $whatsapp->sendMessage($mobile, $message);

echo json_encode($result);

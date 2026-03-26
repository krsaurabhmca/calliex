<?php
// whatsapp_test.php - Professional Diagnostics Tool
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/whatsapp_helper.php';
checkAdmin();

$org_id = getOrgId();
$whatsapp = new WhatsAppHelper($conn, $org_id);

$test_result = null;
$status = $whatsapp->getStateInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $mobile = $_POST['test_mobile'];
    $message = $_POST['test_message'] ?: "Hello! This is a test message from Calldesk CRM WhatsApp API.";
    $test_result = $whatsapp->sendMessage($mobile, $message);
}

include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">WhatsApp API Diagnostic</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Verify your Green-API connection and send test payloads</p>
        </div>
        <a href="settings.php#whatsapp" style="color: var(--primary); font-size: 0.875rem; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
            <i class="fas fa-gear"></i> Configure API
        </a>
    </div>

    <!-- Status Overview -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <div style="background: #fff; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; letter-spacing: 0.05em;">INSTANT CONNECTION STATUS</div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if(isset($status['stateInstance']) && $status['stateInstance'] === 'authorized'): ?>
                    <div style="width: 12px; height: 12px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 10px rgba(34, 197, 94, 0.4);"></div>
                    <span style="font-size: 1.25rem; font-weight: 800; color: #0f172a;">AUTHENTICATED</span>
                <?php else: ?>
                    <div style="width: 12px; height: 12px; background: #ef4444; border-radius: 50%; box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);"></div>
                    <span style="font-size: 1.25rem; font-weight: 800; color: #0f172a;"><?= strtoupper($status['stateInstance'] ?? 'DISCONNECTED') ?></span>
                <?php endif; ?>
            </div>
            <p style="font-size: 0.8125rem; color: #64748b; margin-top: 1rem; line-height: 1.5;">
                Your WhatsApp instance is linked to <strong>Green-API</strong>. Make sure your phone is online and has an active internet connection.
            </p>
        </div>

        <div style="background: #fff; padding: 2rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; letter-spacing: 0.05em;">API FOOTPRINT</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: #64748b;">ID Instance</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #1e293b;"><?= getOrgSetting($conn, $org_id, 'whatsapp_id_instance', 'Not Set') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="font-size: 0.875rem; color: #64748b;">API Host</span>
                    <span style="font-size: 0.875rem; font-weight: 700; color: #1e293b;"><?= getOrgSetting($conn, $org_id, 'whatsapp_api_host', 'Standard') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Sender -->
    <div style="background: #fff; padding: 2.5rem; border-radius: 24px; border: 1px solid var(--border); box-shadow: var(--shadow-md);">
        <h3 style="font-size: 1.125rem; font-weight: 800; color: #0f172a; margin-bottom: 1.5rem;">Send Test Message</h3>
        
        <?php if ($test_result): ?>
            <div style="margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: <?= $test_result['success'] ? '#f0fdf4' : '#fef2f2' ?>; border: 1px solid <?= $test_result['success'] ? '#dcfce7' : '#fee2e2' ?>; color: <?= $test_result['success'] ? '#15803d' : '#991b1b' ?>; font-size: 0.875rem; display: flex; gap: 0.75rem; align-items: flex-start;">
                <i class="fas <?= $test_result['success'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" style="margin-top: 0.2rem;"></i>
                <div>
                    <span style="font-weight: 800; display: block; margin-bottom: 0.25rem;">Result: <?= $test_result['success'] ? 'Success' : 'Failed' ?></span>
                    <?= htmlspecialchars($test_result['message']) ?>
                    <?php if(isset($test_result['details'])): ?>
                        <pre style="margin-top: 0.75rem; font-size: 0.75rem; background: rgba(0,0,0,0.05); padding: 0.75rem; border-radius: 6px; overflow-x: auto;"><?= print_r($test_result['details'], true) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; margin-bottom: 2rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; margin-bottom: 0.6rem; text-transform: uppercase;">Destination (with Country Code)</label>
                    <input type="text" name="test_mobile" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.9375rem;" placeholder="e.g. 919999999999" required>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; margin-bottom: 0.6rem; text-transform: uppercase;">Message Content</label>
                    <input type="text" name="test_message" style="width: 100%; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.9375rem;" placeholder="Leave blank for default test message...">
                </div>
            </div>
            
            <button type="submit" name="send_test" style="background: var(--primary); color: #fff; border: none; padding: 1rem 2rem; border-radius: 14px; font-weight: 800; font-size: 0.9375rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.75rem; box-shadow: 0 4px 14px rgba(88, 81, 255, 0.2); transition: 0.2s; width: 100%;">
                <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> DISPATCH TEST MESSAGE
            </button>
        </form>
    </div>

    <!-- Instructions -->
    <div style="margin-top: 3rem; padding: 2rem; background: #f8fafc; border-radius: 20px; border: 1px dashed #cbd5e1;">
        <h4 style="font-size: 0.875rem; font-weight: 800; color: #334155; margin-bottom: 1rem;">Expert Configuration Tips</h4>
        <ul style="padding-left: 1.25rem; color: #64748b; font-size: 0.8125rem; line-height: 1.8;">
            <li>Always use the <strong>International Format</strong> (e.g., 91 for India, 1 for USA) without the '+' icon.</li>
            <li>Messages are dispatched via <strong>Green-API.com</strong>. Make sure your account has enough credits/active subscription.</li>
            <li>If the status shows "not-authorized", open your WhatsApp and scan the QR code in your Green-API dashboard.</li>
            <li>Sent messages will typically appear in your "Sent" folder on your linked phone within seconds.</li>
        </ul>
    </div>
</div>

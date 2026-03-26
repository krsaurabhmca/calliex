<?php
// settings.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAdmin();

include 'includes/header.php';
$org_id = getOrgId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general'])) {
        saveOrgSetting($conn, $org_id, 'company_name', $_POST['company_name']);
        saveOrgSetting($conn, $org_id, 'auto_dial', $_POST['auto_dial'] ?? '0');
        saveOrgSetting($conn, $org_id, 'max_attempts', $_POST['max_attempts']);
        $msg = "General settings updated!";
    }
    if (isset($_POST['save_privacy'])) {
        $mask = isset($_POST['mask_numbers']) ? 1 : 0;
        mysqli_query($conn, "UPDATE organizations SET mask_numbers = $mask WHERE id = $org_id");
        $msg = "Privacy settings successfully applied!";
    }
    if (isset($_POST['save_allocation'])) {
        $type = $_POST['allocation_type'];
        mysqli_query($conn, "DELETE FROM allocation_rules WHERE organization_id = $org_id");
        mysqli_query($conn, "INSERT INTO allocation_rules (organization_id, rule_name, rule_type, status) 
                             VALUES ($org_id, 'Primary Allocation', '$type', 1)");
        $msg = "Allocation rules updated!";
    }
    if (isset($_POST['save_whatsapp'])) {
        saveOrgSetting($conn, $org_id, 'whatsapp_enabled', isset($_POST['whatsapp_enabled']) ? '1' : '0');
        saveOrgSetting($conn, $org_id, 'whatsapp_id_instance', $_POST['whatsapp_id_instance']);
        saveOrgSetting($conn, $org_id, 'whatsapp_api_token', $_POST['whatsapp_api_token']);
        saveOrgSetting($conn, $org_id, 'whatsapp_api_host', $_POST['whatsapp_api_host'] ?: 'https://api.green-api.com');
        
        // Automation Triggers
        saveOrgSetting($conn, $org_id, 'whatsapp_on_new_lead', isset($_POST['whatsapp_on_new_lead']) ? '1' : '0');
        saveOrgSetting($conn, $org_id, 'whatsapp_on_assign', isset($_POST['whatsapp_on_assign']) ? '1' : '0');
        saveOrgSetting($conn, $org_id, 'whatsapp_on_followup', isset($_POST['whatsapp_on_followup']) ? '1' : '0');
        
        $msg = "WhatsApp API configuration and automation triggers updated!";
    }
}

$company_name = getOrgSetting($conn, $org_id, 'company_name', 'My CallDesk');
$auto_dial = getOrgSetting($conn, $org_id, 'auto_dial', '0');
$max_attempts = getOrgSetting($conn, $org_id, 'max_attempts', '3');

$whatsapp_enabled = getOrgSetting($conn, $org_id, 'whatsapp_enabled', '0');
$whatsapp_id_instance = getOrgSetting($conn, $org_id, 'whatsapp_id_instance', '');
$whatsapp_api_token = getOrgSetting($conn, $org_id, 'whatsapp_api_token', '');
$whatsapp_api_host = getOrgSetting($conn, $org_id, 'whatsapp_api_host', 'https://api.green-api.com');

$whatsapp_on_new_lead = getOrgSetting($conn, $org_id, 'whatsapp_on_new_lead', '0');
$whatsapp_on_assign = getOrgSetting($conn, $org_id, 'whatsapp_on_assign', '0');
$whatsapp_on_followup = getOrgSetting($conn, $org_id, 'whatsapp_on_followup', '0');

$privacy = getOrgPrivacySettings($conn, $org_id);
$mask_numbers = $privacy['mask_numbers'] ?? 0;

$rule_res = mysqli_query($conn, "SELECT rule_type FROM allocation_rules WHERE organization_id = $org_id AND status = 1");
$current_rule = mysqli_fetch_assoc($rule_res)['rule_type'] ?? 'NONE';

// Fetch Admin API Token
$admin_res = mysqli_query($conn, "SELECT api_token FROM users WHERE id = $user_id");
$api_token = mysqli_fetch_assoc($admin_res)['api_token'] ?? 'GENERATE_IN_USERS_SECTION';
?>

<div style="max-width: 1200px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0;">Organization Control Center</h1>
        <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Manage system parameters, security policies, and automation rules</p>
    </div>

    <?php if (isset($msg)): ?>
        <div style="background: #f0fdf4; color: #16a34a; padding: 1.25rem 1.75rem; border-radius: 16px; margin-bottom: 2rem; font-weight: 800; display: flex; align-items: center; gap: 1rem; border: 1px solid #dcfce7; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.05);">
            <i class="fas fa-circle-check" style="font-size: 1.2rem;"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 2.5rem; align-items: start;">
        
        <!-- Sidebar Navigation -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; background: #fff; padding: 1rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <button onclick="showTab('general')" class="setting-tab active" id="btn-general">
                <i class="fas fa-sliders"></i> General Config
            </button>
            <button onclick="showTab('privacy')" class="setting-tab" id="btn-privacy">
                <i class="fas fa-shield-halved"></i> Privacy & Security
            </button>
            <button onclick="showTab('allocation')" class="setting-tab" id="btn-allocation">
                <i class="fas fa-code-branch"></i> Lead Distribution
            </button>
            <button onclick="showTab('whatsapp')" class="setting-tab" id="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp API
            </button>
            <button onclick="showTab('api')" class="setting-tab" id="btn-api">
                <i class="fas fa-terminal"></i> API & Connectors
            </button>
        </div>

        <!-- Main Content Panel -->
        <div class="dash-card" style="padding: 2.5rem; background: #fff; position: relative; min-height: 500px;">
            
            <!-- General Tab -->
            <div id="tab-general" class="tab-content transition-fade">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 2.5rem; color: var(--text-main);">CORE CONFIGURATION</h3>
                <form method="POST">
                    <div style="margin-bottom: 2rem;">
                        <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem; display: block;">Brand Identity / Company Name</label>
                        <input type="text" name="company_name" class="form-input-elite" value="<?= htmlspecialchars($company_name) ?>">
                    </div>
                    <div style="margin-bottom: 2rem;">
                        <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem; display: block;">Max Retries per Prospect</label>
                        <input type="number" name="max_attempts" class="form-input-elite" value="<?= $max_attempts ?>">
                    </div>
                    <div style="background: #fbfcfe; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2.5rem; display: flex; align-items: center; gap: 1.25rem;">
                        <input type="checkbox" name="auto_dial" value="1" <?= $auto_dial == '1' ? 'checked' : '' ?> style="width: 24px; height: 24px; accent-color: var(--primary);">
                        <div>
                            <div style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main);">Cloud Dialer Protocol</div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">Enable 1-tap automated dialing from the mobile application</div>
                        </div>
                    </div>
                    <button type="submit" name="save_general" class="elite-btn" style="width: 200px;">SAVE CHANGES</button>
                </form>
            </div>

            <!-- Privacy Tab -->
            <div id="tab-privacy" class="tab-content transition-fade" style="display: none;">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 2.5rem; color: var(--text-main);">PRIVACY & DATA PROTECTION</h3>
                <form method="POST">
                    <div style="background: #fbfcfe; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1.25rem;">
                        <input type="checkbox" name="mask_numbers" value="1" <?= $mask_numbers == '1' ? 'checked' : '' ?> style="width: 24px; height: 24px; accent-color: var(--primary);">
                        <div>
                            <div style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main);">Executive Number Masking</div>
                            <div style="font-size: 0.8125rem; color: var(--text-muted);">Hide prospect phone numbers from field executives. Admin/Supervisors retain full visibility.</div>
                        </div>
                    </div>
                    <div style="background: #fcfcfc; padding: 1.25rem; border-radius: 12px; border: 1px dashed var(--border); margin-bottom: 2.5rem;">
                        <p style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            <i class="fas fa-circle-info" style="margin-right: 5px;"></i> 
                            Enabling masking will replace the last 5 digits of the contact number with 'XXXXX' in all executive-facing dashboards and reports.
                        </p>
                    </div>
                    <button type="submit" name="save_privacy" class="elite-btn" style="width: 240px;">APPLY PRIVACY POLICY</button>
                </form>
            </div>

            <!-- Allocation Tab -->
            <div id="tab-allocation" class="tab-content transition-fade" style="display: none;">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 1rem; color: var(--text-main);">INTELLIGENT DISTRIBUTION</h3>
                <form method="POST">
                    <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 2rem;">Configure how incoming inquiries are balanced across your available team members.</p>
                    
                    <div style="display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2.5rem;">
                        <label class="distribution-card <?= $current_rule == 'NONE' ? 'active' : '' ?>">
                            <input type="radio" name="allocation_type" value="NONE" <?= $current_rule == 'NONE' ? 'checked' : '' ?> style="display: none;">
                            <div class="check-icon"><i class="fas fa-circle-check"></i></div>
                            <div>
                                <div style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main);">Manual Assignment</div>
                                <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 2px;">New leads remain in global pool until claimed or assigned by admin.</div>
                            </div>
                        </label>
                        
                        <label class="distribution-card <?= $current_rule == 'ROUND_ROBIN' ? 'active' : '' ?>">
                            <input type="radio" name="allocation_type" value="ROUND_ROBIN" <?= $current_rule == 'ROUND_ROBIN' ? 'checked' : '' ?> style="display: none;">
                            <div class="check-icon"><i class="fas fa-circle-check"></i></div>
                            <div>
                                <div style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main);">Round Robin Algorithm</div>
                                <div style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 2px;">Equally distributes leads in a circular order to all active staff.</div>
                            </div>
                        </label>
                    </div>
                    
                    <button type="submit" name="save_allocation" class="elite-btn" style="width: 200px;">UPDATE ROUTING</button>
                </form>
            </div>

            <!-- API Tab (Premium Redesign) -->
            <div id="tab-api" class="tab-content transition-fade" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem;">
                    <div>
                        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin: 0; font-size: 1.5rem;">API & Connectors</h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.4rem;">Integrate CallDesk with your website, Facebook Ads, or custom workflows.</p>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block;"></span> API STATUS: ACTIVE
                    </div>
                </div>

                <!-- Bento Grid for API Meta -->
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
                    <!-- Auth Card -->
                    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 2rem; border-radius: 20px; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
                        <i class="fas fa-shield-halved" style="position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.05;"></i>
                        <div style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 0.1em; margin-bottom: 1.5rem;">Secure Access Token</div>
                        
                        <div style="margin-bottom: 2rem;">
                            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 1rem 1.25rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; backdrop-filter: blur(4px);">
                                <code style="font-family: 'Cascadia Mono', monospace; font-size: 1.125rem; font-weight: 700; color: #f8fafc; letter-spacing: 0.05em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= $api_token ?></code>
                                <button onclick="copyKey(this)" style="background: #38bdf8; color: #0f172a; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">COPY</button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 2rem;">
                            <div>
                                <div style="font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.25rem;">Auth Method</div>
                                <div style="font-size: 0.875rem; font-weight: 700;">Bearer Token</div>
                            </div>
                            <div>
                                <div style="font-size: 0.65rem; font-weight: 800; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.25rem;">Permission</div>
                                <div style="font-size: 0.875rem; font-weight: 700; color: #38bdf8;">Full Write Access</div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Stats / Info -->
                    <div style="background: #fff; border: 1px solid var(--border); padding: 2rem; border-radius: 20px; display: flex; flex-direction: column; justify-content: center;">
                        <h4 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1rem; color: var(--text-main); margin-bottom: 0.75rem;">Integration Guide</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.6; margin: 0;">
                            Use this permanent token to authenticate external requests. We recommend rotating your token if you suspect it has been compromised.
                        </p>
                        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem;">
                            <a href="docs_whatsapp.md" target="_blank" style="font-size: 0.75rem; font-weight: 800; color: var(--primary); text-decoration: none;"><i class="fas fa-book" style="margin-right: 6px;"></i> View Full Documentation</a>
                            <a href="api_samples.php" target="_blank" style="font-size: 0.75rem; font-weight: 800; color: var(--primary); text-decoration: none;"><i class="fas fa-file-code" style="margin-right: 6px;"></i> Download Offline Samples</a>
                        </div>
                    </div>
                </div>

                <!-- Custom Fields Mapping Section -->
                <?php $api_cf = getCustomFields($conn, $org_id); ?>
                <?php if (!empty($api_cf)): ?>
                <div style="margin-bottom: 3.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                        <h5 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin: 0;">Lead Custom Field Mapping</h5>
                        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700;">Maps directly to <code style="color:var(--primary)">leads.php</code> POST parameters</div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <?php foreach ($api_cf as $cf): ?>
                        <div style="background: #f8fafc; border: 1px solid var(--border); padding: 1.25rem; border-radius: 16px; transition: 0.2s;" onmouseover="this.style.borderColor='var(--primary)';this.style.background='#fff';" onmouseout="this.style.borderColor='var(--border)';this.style.background='#f8fafc';">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div style="font-size: 0.875rem; font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($cf['field_name']) ?></div>
                                <div style="font-size: 0.6rem; color: var(--primary); font-weight: 800; background: rgba(88, 81, 255, 0.05); padding: 0.2rem 0.4rem; border-radius: 4px;"><?= strtoupper($cf['field_type']) ?></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid var(--border); padding: 0.5rem 0.75rem; border-radius: 8px;">
                                <code style="font-family: 'Cascadia Mono', monospace; font-size: 0.8125rem; font-weight: 800; color: var(--text-main);">cf_<?= $cf['id'] ?></code>
                                <i class="fas fa-copy" style="font-size: 0.75rem; color: var(--text-muted); cursor: pointer;" onclick="copyToClipboard('cf_<?= $cf['id'] ?>', this)"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Code Implementation Tabs -->
                <div style="background: #fff; border: 1px solid var(--border); border-radius: 20px; overflow: hidden;">
                    <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                        <h4 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin: 0; font-size: 1rem;">Ready-to-Use Implementations</h4>
                        <div style="display: flex; gap: 0.5rem; background: #f1f5f9; padding: 0.3rem; border-radius: 10px;">
                            <button onclick="switchCode('php')" class="code-tab active" id="btn-php">PHP</button>
                            <button onclick="switchCode('curl')" class="code-tab" id="btn-curl">cURL</button>
                        </div>
                    </div>
                    
                    <div id="code-php" class="code-view">
                        <div style="padding: 1rem 1.5rem; background: #1e293b; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 0.4rem;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></div>
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                            </div>
                            <button onclick="copyCode(this)" style="background: rgba(255,255,255,0.1); border: none; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.65rem; font-weight: 800; cursor: pointer;">COPY PHP</button>
                        </div>
                        <pre style="margin: 0; padding: 1.5rem; background: #0f172a; color: #38bdf8; font-family: 'Cascadia Mono', monospace; font-size: 0.8125rem; line-height: 1.6; overflow-x: auto;">
&lt;?php
<span style="color: #94a3b8;">// 1. Setup Credentials</span>
<span style="color: #f472b6;">$BASE_URL</span> = <span style="color: #fbbf24;">"<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL ?>api/"</span>;
<span style="color: #f472b6;">$TOKEN</span>    = <span style="color: #fbbf24;">"<?= $api_token ?>"</span>;

<span style="color: #94a3b8;">// 2. Prepare Lead Data</span>
<span style="color: #f472b6;">$data</span> = [
    <span style="color: #fbbf24;">'name'</span>   => <span style="color: #fbbf24;">'John Doe'</span>,
    <span style="color: #fbbf24;">'mobile'</span> => <span style="color: #fbbf24;">'9876543210'</span>,
    <span style="color: #fbbf24;">'status'</span> => <span style="color: #fbbf24;">'New'</span>,
    <?php if(!empty($api_cf)): ?><span style="color: #fbbf24;">'cf_<?= $api_cf[0]['id'] ?>'</span> => <span style="color: #fbbf24;">'Direct Map'</span><?php endif; ?>
];

<span style="color: #94a3b8;">// 3. Execute Request</span>
<span style="color: #f472b6;">$ch</span> = curl_init(<span style="color: #f472b6;">$BASE_URL</span> . <span style="color: #fbbf24;">"leads.php"</span>);
curl_setopt(<span style="color: #f472b6;">$ch</span>, CURLOPT_POST, 1);
curl_setopt(<span style="color: #f472b6;">$ch</span>, CURLOPT_POSTFIELDS, http_build_query(<span style="color: #f472b6;">$data</span>));
curl_setopt(<span style="color: #f472b6;">$ch</span>, CURLOPT_HTTPHEADER, [<span style="color: #fbbf24;">"Authorization: Bearer "</span> . <span style="color: #f472b6;">$TOKEN</span>]);
curl_setopt(<span style="color: #f472b6;">$ch</span>, CURLOPT_RETURNTRANSFER, true);

<span style="color: #f472b6;">$raw_resp</span> = curl_exec(<span style="color: #f472b6;">$ch</span>);
<span style="color: #f472b6;">$response</span> = json_decode(<span style="color: #f472b6;">$raw_resp</span>, true);

<span style="color: #818cf8;">echo</span> <span style="color: #fbbf24;">"Lead ID: "</span> . <span style="color: #f472b6;">$response</span>[<span style="color: #fbbf24;">'data'</span>][<span style="color: #fbbf24;">'id'</span>];
?&gt;</pre>
                    </div>

                    <div id="code-curl" class="code-view" style="display: none;">
                        <div style="padding: 1rem 1.5rem; background: #1e293b; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; gap: 0.4rem;">
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></div>
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></div>
                                <div style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></div>
                            </div>
                            <button onclick="copyCode(this)" style="background: rgba(255,255,255,0.1); border: none; color: white; padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.65rem; font-weight: 800; cursor: pointer;">COPY CURL</button>
                        </div>
                        <pre style="margin: 0; padding: 1.5rem; background: #0f172a; color: #fbbf24; font-family: 'Cascadia Mono', monospace; font-size: 0.8125rem; line-height: 1.6; overflow-x: auto;">
curl -X POST <span style="color: #38bdf8;">"<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL ?>api/leads.php"</span> \
     -H <span style="color: #38bdf8;">"Authorization: Bearer <?= $api_token ?>"</span> \
     -d <span style="color: #38bdf8;">"name=John Doe"</span> \
     -d <span style="color: #38bdf8;">"mobile=9876543210"</span> \
     <?php if(!empty($api_cf)): ?>-d <span style="color: #38bdf8;">"cf_<?= $api_cf[0]['id'] ?>=Value"</span><?php endif; ?></pre>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Tab -->
            <div id="tab-whatsapp" class="tab-content transition-fade" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2.5rem;">
                    <div>
                        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin: 0;">WHATSAPP AUTOMATION</h3>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.4rem;">Connect Green-API for background messaging and auto-notifications.</p>
                    </div>
                    <img src="https://green-api.com/img/logo-dark-en.svg" alt="Green API" style="height: 30px; opacity: 0.8;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">
                    <form method="POST">
                        <div style="background: #fbfcfe; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2rem; display: flex; align-items: center; gap: 1.25rem;">
                            <input type="checkbox" name="whatsapp_enabled" value="1" <?= $whatsapp_enabled == '1' ? 'checked' : '' ?> style="width: 24px; height: 24px; accent-color: #25d366;">
                            <div>
                                <div style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main);">Activate Green-API Services</div>
                                <div style="font-size: 0.8125rem; color: var(--text-muted);">Enable server-side messaging for lead alerts and campaigns</div>
                            </div>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">ID Instance</label>
                            <input type="text" name="whatsapp_id_instance" class="form-input-elite" placeholder="1101xxxxxx" value="<?= htmlspecialchars($whatsapp_id_instance) ?>">
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">API Token Instance</label>
                            <input type="password" name="whatsapp_api_token" class="form-input-elite" placeholder="da6376xxxxxxxxxxxxxxxxxxxxxx" value="<?= htmlspecialchars($whatsapp_api_token) ?>">
                        </div>

                        <div style="margin-bottom: 2.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">API Host Base URL</label>
                            <input type="text" name="whatsapp_api_host" class="form-input-elite" placeholder="https://api.green-api.com" value="<?= htmlspecialchars($whatsapp_api_host) ?>">
                        </div>

                        <div style="margin-bottom: 2.5rem;">
                            <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; display: block;">AUTOMATION TRIGGERS (Auto-Messaging)</label>
                            
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #fff; padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border);">
                                    <input type="checkbox" name="whatsapp_on_new_lead" value="1" <?= $whatsapp_on_new_lead == '1' ? 'checked' : '' ?> style="accent-color: #25d366;">
                                    <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);">Welcome message to New Leads</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #fff; padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border);">
                                    <input type="checkbox" name="whatsapp_on_assign" value="1" <?= $whatsapp_on_assign == '1' ? 'checked' : '' ?> style="accent-color: #25d366;">
                                    <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);">Notification upon Lead Assignment</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; background: #fff; padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border);">
                                    <input type="checkbox" name="whatsapp_on_followup" value="1" <?= $whatsapp_on_followup == '1' ? 'checked' : '' ?> style="accent-color: #25d366;">
                                    <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);">Summary/Thank you after Follow-up</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="save_whatsapp" class="elite-btn" style="background: #25d366; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.2); width: 220px;">UPDATE WHATSAPP</button>
                    </form>

                    <div style="background: #f8fafc; padding: 1.5rem; border-radius: 20px; border: 1px solid var(--border);">
                        <h5 style="font-weight: 800; color: var(--text-main); margin-bottom: 1rem; font-size: 0.9rem;">Setup Instructions</h5>
                        <ul style="padding-left: 1.25rem; font-size: 0.8125rem; color: var(--text-muted); line-height: 1.8; margin: 0;">
                            <li>Visit <a href="https://green-api.com" target="_blank" style="color: var(--primary); font-weight: 700; text-decoration: none;">Green-API.com</a> and sign up.</li>
                            <li>Create a new 'Instance' and scan the QR code with your WhatsApp.</li>
                            <li>Copy the 'Id Instance' and 'Api Token Instance' from your dashboard.</li>
                            <li>Paste them here and save settings.</li>
                            <li>Verify connection by sending a test message from a lead's profile.</li>
                        </ul>
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Status Check</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= $whatsapp_enabled == '1' ? '#25d366' : '#94a3b8' ?>;"></div>
                                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-main);"><?= $whatsapp_enabled == '1' ? 'CONNECTED' : 'DISABLED' ?></span>
                                </div>
                            </div>
                            <a href="whatsapp_test.php" style="background: #eef2ff; color: #4f46e5; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.7rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.4rem; border: 1px solid #e0e7ff;">
                                <i class="fab fa-whatsapp"></i> DIAGNOSTIC HUB
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .setting-tab { padding: 1rem 1.25rem; background: transparent; border: none; border-radius: 12px; text-align: left; font-weight: 800; font-size: 0.875rem; color: var(--secondary); cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 1rem; border: 1px solid transparent; }
    .setting-tab:hover { background: #fbfcfe; color: var(--text-main); }
    .setting-tab.active { background: #fff; color: var(--primary); border-color: var(--border); box-shadow: var(--shadow-sm); }
    .setting-tab i { font-size: 1rem; opacity: 0.5; }
    .setting-tab.active i { opacity: 1; }
    
    .form-input-elite { width: 100%; border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1.25rem; font-size: 0.9375rem; font-weight: 600; color: var(--text-main); transition: 0.2s; outline: none; background: #fbfcfe; }
    .form-input-elite:focus { border-color: var(--primary); box-shadow: 0 0 0 5px rgba(88, 81, 255, 0.1); background: #fff; }
    
    .elite-btn { background: var(--primary); color: white; border: none; padding: 1rem 1.5rem; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2); }
    .elite-btn:hover { background: #463fed; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(88, 81, 255, 0.3); }

    .distribution-card { display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; border: 2px solid var(--border); border-radius: 20px; cursor: pointer; transition: 0.2s; background: #fff; }
    .distribution-card:hover { border-color: var(--secondary); background: #fbfcfe; }
    .distribution-card.active { border-color: var(--primary); background: rgba(88, 81, 255, 0.05); }
    .distribution-card .check-icon { width: 24px; height: 24px; color: var(--border); font-size: 1.5rem; transition: 0.2s; }
    .distribution-card.active .check-icon { color: var(--primary); }
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.setting-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabId).style.display = 'block';
    document.getElementById('btn-' + tabId).classList.add('active');
}

function copyKey(btn) {
    const key = btn.previousElementSibling.innerText;
    navigator.clipboard.writeText(key).then(() => {
        const original = btn.innerText;
        btn.innerText = 'COPIED!';
        btn.style.background = 'var(--success)';
        setTimeout(() => {
            btn.innerText = original;
            btn.style.background = 'rgba(255,255,255,0.1)';
        }, 2000);
    });
}

function copyCode(btn) {
    const code = btn.closest('div').nextElementSibling.innerText;
    navigator.clipboard.writeText(code).then(() => {
        const original = btn.innerText;
        btn.innerText = 'COPIED!';
        btn.style.borderColor = '#10b981';
        btn.style.color = '#10b981';
        setTimeout(() => {
            btn.innerText = original;
            btn.style.borderColor = 'rgba(255,255,255,0.2)';
            btn.style.color = '#fff';
        }, 2000);
    });
}

function copyToClipboard(text, icon) {
    navigator.clipboard.writeText(text).then(() => {
        const original = icon.className;
        icon.className = 'fas fa-check-circle';
        icon.style.color = '#10b981';
        setTimeout(() => {
            icon.className = original;
            icon.style.color = '';
        }, 1500);
    });
}

function switchCode(type) {
    document.querySelectorAll('.code-view').forEach(v => v.style.display = 'none');
    document.querySelectorAll('.code-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('code-' + type).style.display = 'block';
    document.getElementById('btn-' + type).classList.add('active');
}

// Logic for radio card styling
document.querySelectorAll('input[name="allocation_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.distribution-card').forEach(card => card.classList.remove('active'));
        this.closest('.distribution-card').classList.add('active');
    });
});
</script>

<style>
.code-tab { border: none; padding: 0.4rem 1rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); cursor: pointer; background: transparent; transition: 0.2s; }
.code-tab:hover { color: var(--text-main); }
.code-tab.active { background: #fff; color: var(--primary); box-shadow: 0 4px 12px rgba(88, 81, 255, 0.1); }
</style>

<?php include 'includes/footer.php'; ?>

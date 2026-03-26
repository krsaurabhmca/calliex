<?php
// lead_add.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAuth();

$message = '';
$error = '';
$org_id = getOrgId();

$prefill_mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';
$call_id = isset($_GET['call_id']) ? (int)$_GET['call_id'] : 0;

// Fetch Custom Fields
$custom_fields = getCustomFields($conn, $org_id);
$statuses = getLeadStatuses($conn, $org_id);
$states = getStates($conn, $org_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $alternate_mobile = mysqli_real_escape_string($conn, $_POST['alternate_mobile'] ?? '');
    $source_id = !empty($_POST['source_id']) ? (int)$_POST['source_id'] : "NULL";
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $assigned_to = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : "NULL";
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    // Geography
    $state_id = !empty($_POST['state_id']) ? (int)$_POST['state_id'] : "NULL";
    $district_id = !empty($_POST['district_id']) ? (int)$_POST['district_id'] : "NULL";
    $block_id = !empty($_POST['block_id']) ? (int)$_POST['block_id'] : "NULL";

    $sql = "INSERT INTO leads (organization_id, name, mobile, alternate_mobile, source_id, status, assigned_to, remarks, state_id, district_id, block_id) 
            VALUES ($org_id, '$name', '$mobile', '$alternate_mobile', $source_id, '$status', $assigned_to, '$remarks', $state_id, $district_id, $block_id)";
    
    try {
        if (mysqli_query($conn, $sql)) {
            $lead_id = mysqli_insert_id($conn);
            
            // Apply Auto-Allocation Rules
            allocateLead($conn, $lead_id);
            
            // Handle Custom Fields Value Saving
            foreach ($custom_fields as $f) {
                $field_id = $f['id'];
                $val = "";
                if ($f['field_type'] === 'AUTO') {
                    // Generate Unique Serial (e.g. CD-2024-0001)
                    $val = "CD-" . date('Y') . "-" . str_pad($lead_id, 4, '0', STR_PAD_LEFT);
                } else if (isset($_POST["custom_fields"][$field_id])) {
                    $val = is_array($_POST["custom_fields"][$field_id]) ? implode(", ", $_POST["custom_fields"][$field_id]) : $_POST["custom_fields"][$field_id];
                }
                
                if (!empty($val) || $f['field_type'] === 'AUTO') {
                    saveCustomValue($conn, $lead_id, $field_id, $val);
                }
            }

            // If coming from call log, link it
            if ($call_id > 0) {
                mysqli_query($conn, "UPDATE call_logs SET lead_id = $lead_id, is_converted = 1 WHERE id = $call_id AND organization_id = $org_id");
            }

            // --- WhatsApp Automation ---
            $wa_trigger = getOrgSetting($conn, $org_id, 'whatsapp_on_new_lead', '0');
            if ($wa_trigger === '1') {
                require_once 'includes/whatsapp_helper.php';
                $wa = new WhatsAppHelper($conn, $org_id);
                
                // Fetch Template (Priority: NEW_LEAD > GENERAL)
                $msg_content = getWhatsAppTemplate($conn, $org_id, 'NEW_LEAD', $name);
                if (empty($msg_content)) {
                    $msg_content = getWhatsAppTemplate($conn, $org_id, 'GENERAL', $name);
                }
                
                if (!empty($msg_content)) {
                    $wa->sendMessage($mobile, $msg_content);
                }
            }
            // ---------------------------
            
            header("Location: leads.php?success=1");
            exit();
        } else {
            $error = "Failed to create lead: " . mysqli_error($conn);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = "A lead with this mobile number already exists in your organization.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$users_result = mysqli_query($conn, "SELECT id, name FROM users WHERE organization_id = $org_id AND status = 1 ORDER BY name ASC");
$sources_result = mysqli_query($conn, "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id ORDER BY source_name ASC");

include 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
        <a href="leads.php" style="margin-right: 0.75rem; color: var(--text-muted);"><i class="fas fa-arrow-left"></i></a>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">Create New Lead</h2>
    </div>

    <?php if ($error): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #fee2e2; margin-bottom: 1.25rem; font-size: 0.8125rem; font-weight: 600;">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <!-- Primary Info -->
            <div style="grid-column: span 2;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Primary Information</h3></div>
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter lead name" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Main Mobile Number</label>
                <input type="text" name="mobile" class="form-control" placeholder="Primary phone" value="<?php echo htmlspecialchars($prefill_mobile); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Alternate Phone (Optional)</label>
                <input type="text" name="alternate_mobile" class="form-control" placeholder="Secondary phone">
            </div>
            <div class="form-group">
                <label class="form-label">Lead Source</label>
                <select name="source_id" class="form-control" required>
                    <option value="">-- Select Source --</option>
                    <?php while ($s = mysqli_fetch_assoc($sources_result)): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['source_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Lead Status</label>
                <select name="status" class="form-control" required>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo $st['status_name']; ?>" <?php echo $st['is_default'] ? 'selected' : ''; ?>><?php echo $st['status_name']; ?></option>
                    <?php endforeach; if(empty($statuses)): ?>
                        <option value="New">New</option>
                        <option value="Follow-up">Follow-up</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php if (isAdmin()): ?>
            <div class="form-group">
                <label class="form-label">Assign To Executive</label>
                <select name="assigned_to" class="form-control">
                    <option value="">-- Unassigned (Auto) --</option>
                    <?php while ($u = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo $u['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="assigned_to" value="<?php echo $_SESSION['user_id']; ?>">
            <?php endif; ?>

            <!-- Geography -->
            <div style="grid-column: span 2; margin-top: 1rem;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Location Details</h3></div>
            
            <div class="form-group">
                <label class="form-label">State</label>
                <select name="state_id" id="state_id" class="form-control" onchange="loadDistricts(this.value)">
                    <option value="">-- Select State --</option>
                    <?php foreach ($states as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">District</label>
                <select name="district_id" id="district_id" class="form-control" onchange="loadBlocks(this.value)">
                    <option value="">-- Select District --</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Block</label>
                <select name="block_id" id="block_id" class="form-control">
                    <option value="">-- Select Block --</option>
                </select>
            </div>

            <!-- Custom Fields -->
            <?php if (!empty($custom_fields)): ?>
            <div style="grid-column: span 2; margin-top: 1rem;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Additional Information</h3></div>
            <?php foreach ($custom_fields as $f): 
                if ($f['field_type'] === 'AUTO') continue; 
            ?>
                <div class="form-group">
                    <label class="form-label"><?php echo htmlspecialchars($f['field_name']); ?><?php echo $f['is_mandatory'] ? ' <span style="color:red">*</span>' : ''; ?></label>
                    <?php if ($f['field_type'] === 'TEXT'): ?>
                        <input type="text" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" <?php echo $f['is_mandatory'] ? 'required' : ''; ?> <?php echo $f['is_readonly'] ? 'readonly' : ''; ?>>
                    <?php elseif ($f['field_type'] === 'NUMBER'): ?>
                        <input type="number" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" <?php echo $f['is_mandatory'] ? 'required' : ''; ?> <?php echo $f['is_readonly'] ? 'readonly' : ''; ?>>
                    <?php elseif ($f['field_type'] === 'DATE'): ?>
                        <input type="date" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" <?php echo $f['is_mandatory'] ? 'required' : ''; ?> <?php echo $f['is_readonly'] ? 'readonly' : ''; ?>>
                    <?php elseif ($f['field_type'] === 'OPTION'): ?>
                        <select name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" <?php echo $f['is_mandatory'] ? 'required' : ''; ?>>
                            <option value="">-- Select --</option>
                            <?php 
                            $opts = explode(',', $f['field_options']);
                            foreach ($opts as $o): $o = trim($o);
                            ?>
                                <option value="<?php echo $o; ?>"><?php echo $o; ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($f['field_type'] === 'MULTIPLE'): ?>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                        <?php 
                        $opts = explode(',', $f['field_options']);
                        foreach ($opts as $o): $o = trim($o);
                        ?>
                            <label style="border: 1px solid #e2e8f0; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.8125rem; display: flex; align-items: center; gap: 0.35rem; cursor: pointer; background: #f8fafc;">
                                <input type="checkbox" name="custom_fields[<?php echo $f['id']; ?>][]" value="<?php echo $o; ?>"> <?php echo $o; ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>

            <div class="form-group" style="grid-column: span 2; margin-top: 1rem;">
                <label class="form-label">General Remarks / Notes</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Additional comments..."></textarea>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 3rem;">Save Lead</button>
            <a href="leads.php" class="btn" style="width: auto; padding: 0.75rem 3rem; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<script>
async function loadDistricts(stateId) {
    const distSelect = document.getElementById('district_id');
    const blockSelect = document.getElementById('block_id');
    distSelect.innerHTML = '<option value="">-- Loading --</option>';
    blockSelect.innerHTML = '<option value="">-- Select Block --</option>';
    
    if (!stateId) {
        distSelect.innerHTML = '<option value="">-- Select District --</option>';
        return;
    }

    const res = await fetch(`api/geography.php?type=districts&state_id=${stateId}`);
    const result = await res.json();
    const data = result.data || [];
    let html = '<option value="">-- Select District --</option>';
    data.forEach(d => {
        html += `<option value="${d.id}">${d.name}</option>`;
    });
    distSelect.innerHTML = html;
}

async function loadBlocks(distId) {
    const blockSelect = document.getElementById('block_id');
    blockSelect.innerHTML = '<option value="">-- Loading --</option>';
    
    if (!distId) {
        blockSelect.innerHTML = '<option value="">-- Select Block --</option>';
        return;
    }

    const res = await fetch(`api/geography.php?type=blocks&district_id=${distId}`);
    const result = await res.json();
    const data = result.data || [];
    let html = '<option value="">-- Select Block --</option>';
    data.forEach(b => {
        html += `<option value="${b.id}">${b.name}</option>`;
    });
    blockSelect.innerHTML = html;
}
</script>

<?php include 'includes/footer.php'; ?>

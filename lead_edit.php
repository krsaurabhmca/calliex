<?php
// lead_edit.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAuth();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$org_id = getOrgId();

// Fetch Lead Details
$sql = "SELECT * FROM leads WHERE id = $lead_id AND organization_id = $org_id";
if ($role !== 'admin') {
    $sql .= " AND assigned_to = $user_id";
}
$result = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead not found or access denied.");
}

$privacy = getOrgPrivacySettings($conn, $org_id);
$should_mask = (int)($privacy['mask_numbers'] ?? 0) === 1 && $role !== 'admin';

function mask_val($v) {
    if (strlen($v) < 5) return $v;
    return substr($v, 0, -5) . 'XXXXX';
}

$message = '';
$error = '';

// Fetch Form Metadata
$custom_fields = getCustomFields($conn, $org_id);
$statuses = getLeadStatuses($conn, $org_id);
$states = getStates($conn, $org_id);
$districts = getDistricts($conn, $lead['state_id']);
$blocks = getBlocks($conn, $lead['district_id']);
$lead_custom_values = getLeadCustomValues($conn, $lead_id);

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

    $sql = "UPDATE leads SET 
            name = '$name', 
            mobile = '$mobile', 
            alternate_mobile = '$alternate_mobile',
            source_id = $source_id, 
            status = '$status', 
            assigned_to = $assigned_to, 
            remarks = '$remarks',
            state_id = $state_id,
            district_id = $district_id,
            block_id = $block_id
            WHERE id = $lead_id AND organization_id = $org_id";
    
    try {
        if (mysqli_query($conn, $sql)) {
            // Update Custom Fields
            $cf_input = $_POST['custom_fields'] ?? [];
            foreach ($custom_fields as $f) {
                if ($f['field_type'] === 'AUTO') continue;
                $f_id = $f['id'];
                if (isset($cf_input[$f_id])) {
                    $val = is_array($cf_input[$f_id]) ? implode(", ", $cf_input[$f_id]) : $cf_input[$f_id];
                    saveCustomValue($conn, $lead_id, $f_id, $val);
                }
            }
            header("Location: lead_view.php?id=$lead_id&success=1");
            exit();
        } else {
            $error = "Failed to update lead: " . mysqli_error($conn);
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = "Another lead with this mobile number already exists.";
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
        <a href="lead_view.php?id=<?php echo $lead_id; ?>" style="margin-right: 0.75rem; color: var(--text-muted);"><i class="fas fa-arrow-left"></i></a>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">Edit Lead Profile</h2>
    </div>

    <?php if ($error): ?>
    <div style="background: #fef2f2; color: #991b1b; padding: 0.75rem 1rem; border-radius: 6px; border: 1px solid #fee2e2; margin-bottom: 1.25rem; font-size: 0.8125rem; font-weight: 600;">
        <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            
            <div style="grid-column: span 2;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Primary Details</h3></div>
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($lead['name']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mobile Number</label>
                <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($lead['mobile']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Alternate Mobile</label>
                <input type="text" name="alternate_mobile" class="form-control" value="<?php echo htmlspecialchars($lead['alternate_mobile']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Lead Source</label>
                <select name="source_id" class="form-control" required>
                    <option value="">-- Select Source --</option>
                    <?php while ($s = mysqli_fetch_assoc($sources_result)): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $lead['source_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo $s['source_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Current Stage</label>
                <select name="status" class="form-control" required>
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo $st['status_name']; ?>" <?php echo $lead['status'] == $st['status_name'] ? 'selected' : ''; ?>><?php echo $st['status_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (isAdmin()): ?>
            <div class="form-group">
                <label class="form-label">Ownership</label>
                <select name="assigned_to" class="form-control">
                    <option value="">-- Unassigned --</option>
                    <?php while ($u = mysqli_fetch_assoc($users_result)): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $lead['assigned_to'] == $u['id'] ? 'selected' : ''; ?>><?php echo $u['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <?php else: ?>
                <input type="hidden" name="assigned_to" value="<?php echo $lead['assigned_to']; ?>">
            <?php endif; ?>

            <!-- Geography -->
            <div style="grid-column: span 2; margin-top: 1rem;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Location Context</h3></div>
            
            <div class="form-group">
                <label class="form-label">State</label>
                <select name="state_id" id="state_id" class="form-control" onchange="loadDistricts(this.value)">
                    <option value="">-- Select State --</option>
                    <?php foreach ($states as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo $lead['state_id'] == $s['id'] ? 'selected' : ''; ?>><?php echo $s['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">District</label>
                <select name="district_id" id="district_id" class="form-control" onchange="loadBlocks(this.value)">
                    <option value="">-- Select District --</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $lead['district_id'] == $d['id'] ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Block</label>
                <select name="block_id" id="block_id" class="form-control">
                    <option value="">-- Select Block --</option>
                    <?php foreach ($blocks as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo $lead['block_id'] == $b['id'] ? 'selected' : ''; ?>><?php echo $b['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Custom Fields -->
            <?php if (!empty($custom_fields)): ?>
            <div style="grid-column: span 2; margin-top: 1rem;"><h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 1rem;">Custom Attributes</h3></div>
            <?php foreach ($custom_fields as $f): 
                if ($f['field_type'] === 'AUTO') continue; 
                $val = $lead_custom_values[$f['id']] ?? '';
            ?>
                <div class="form-group">
                    <label class="form-label"><?php echo htmlspecialchars($f['field_name']); ?></label>
                    <?php if ($f['field_type'] === 'TEXT'): ?>
                        <input type="text" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                    <?php elseif ($f['field_type'] === 'NUMBER'): ?>
                        <input type="number" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                    <?php elseif ($f['field_type'] === 'DATE'): ?>
                        <input type="date" name="custom_fields[<?php echo $f['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                    <?php elseif ($f['field_type'] === 'OPTION'): ?>
                        <select name="custom_fields[<?php echo $f['id']; ?>]" class="form-control">
                            <option value="">-- Select --</option>
                            <?php 
                            $opts = explode(',', $f['field_options']);
                            foreach ($opts as $o): $o = trim($o);
                            ?>
                                <option value="<?php echo $o; ?>" <?php echo $val == $o ? 'selected' : ''; ?>><?php echo $o; ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>

            <div class="form-group" style="grid-column: span 2; margin-top: 1rem;">
                <label class="form-label">Internal Remarks</label>
                <textarea name="remarks" class="form-control" rows="3"><?php echo htmlspecialchars($lead['remarks']); ?></textarea>
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
            <button type="submit" class="btn btn-primary" style="width: auto; padding: 0.75rem 2.5rem;">Update Profile</button>
            <a href="lead_view.php?id=<?php echo $lead_id; ?>" class="btn" style="width: auto; padding: 0.75rem 2.5rem; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Cancel</a>
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

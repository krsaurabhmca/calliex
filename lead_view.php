<?php
// lead_view.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAuth();

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$org_id = getOrgId();

$sql = "SELECT l.*, u.name as executive_name, s.source_name, 
        st.name as state_name, dt.name as district_name, bl.name as block_name
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id
        LEFT JOIN states st ON l.state_id = st.id
        LEFT JOIN districts dt ON l.district_id = dt.id
        LEFT JOIN blocks bl ON l.block_id = bl.id
        WHERE l.id = $lead_id AND l.organization_id = $org_id";

if ($role !== 'admin') {
    $sql .= " AND l.assigned_to = $user_id";
}
$result = mysqli_query($conn, $sql);
$lead = mysqli_fetch_assoc($result);

if (!$lead) {
    die("Lead not found or access denied.");
}

// Search/Masking
$privacy = getOrgPrivacySettings($conn, $org_id);
$should_mask = (int)($privacy['mask_numbers'] ?? 0) === 1 && $role !== 'admin';

function mask_val($v) {
    if (strlen($v) < 5) return $v;
    return substr($v, 0, -5) . 'XXXXX';
}

// Fetch Custom Values
$custom_values = getLeadCustomValues($conn, $lead_id);
$custom_fields = getCustomFields($conn, $org_id);
$statuses = getLeadStatuses($conn, $org_id);

// Handle New Follow-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_followup'])) {
    $remark = mysqli_real_escape_string($conn, $_POST['remark']);
    $next_date = mysqli_real_escape_string($conn, $_POST['next_follow_up_date']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    mysqli_query($conn, "INSERT INTO follow_ups (organization_id, lead_id, executive_id, remark, next_follow_up_date) VALUES ($org_id, $lead_id, $user_id, '$remark', '$next_date')");
    mysqli_query($conn, "UPDATE leads SET status = '$new_status', remarks = '$remark' WHERE id = $lead_id");
    
    header("Location: lead_view.php?id=$lead_id&success=1");
    exit();
}

$history_res = mysqli_query($conn, "SELECT f.*, u.name as executive_name FROM follow_ups f JOIN users u ON f.executive_id = u.id WHERE f.lead_id = $lead_id ORDER BY f.created_at DESC");
$lead_mobile = $lead['mobile'];
$calls_res = mysqli_query($conn, "SELECT * FROM call_logs WHERE (mobile = '$lead_mobile' OR lead_id = $lead_id) ORDER BY call_time DESC LIMIT 50");

include 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center;">
            <a href="leads.php" style="margin-right: 1rem; color: #64748b;"><i class="fas fa-arrow-left"></i></a>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #0f172a;">Lead Profile</h2>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="lead_edit.php?id=<?php echo $lead_id; ?>" class="btn" style="width: auto; background: #fff; border: 1px solid #e2e8f0; color: #1e293b; text-decoration: none; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            <button onclick="window.print()" class="btn" style="width: auto; background: #fff; border: 1px solid #e2e8f0; color: #1e293b; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; cursor: pointer;">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; margin-bottom: 1.5rem; align-items: start;">
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Main Info Card -->
            <div class="card" style="padding: 1.5rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 56px; height: 56px; border-radius: 16px; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">
                            <?php echo strtoupper(substr($lead['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin: 0;"><?php echo htmlspecialchars($lead['name']); ?></h3>
                            <div style="display: flex; gap: 0.5rem; margin-top: 0.25rem;">
                                <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 4px;"><?php echo $lead['source_name'] ?: 'Direct'; ?></span>
                                <span style="font-size: 0.75rem; font-weight: 700; color: #fff; background: var(--primary); padding: 0.15rem 0.5rem; border-radius: 4px;"><?php echo $lead['status']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">Primary Mobile</label>
                        <div style="font-weight: 700; font-size: 1.125rem; color: #0f172a; display: flex; align-items: center; gap: 0.75rem;">
                            <?php echo $should_mask ? mask_val($lead['mobile']) : $lead['mobile']; ?>
                            <a href="tel:<?php echo $lead['mobile']; ?>" style="color: #6366f1;"><i class="fas fa-phone"></i></a>
                            <a href="https://wa.me/<?php echo $lead['mobile']; ?>" target="_blank" style="color: #22c55e;"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">Alternate Mobile</label>
                        <div style="font-weight: 700; font-size: 1.125rem; color: #0f172a;">
                            <?php 
                                $alt = $lead['alternate_mobile'];
                                if($should_mask && $alt) $alt = mask_val($alt);
                                echo $alt ?: '<span style="color:#cbd5e1;font-weight:400;">Not Provided</span>'; 
                            ?>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">Location</label>
                        <div style="font-weight: 600; font-size: 0.9375rem; color: #1e293b;">
                            <?php 
                            $loc = array_filter([$lead['block_name'], $lead['district_name'], $lead['state_name']]);
                            echo !empty($loc) ? implode(', ', $loc) : '<span style="color:#cbd5e1;font-weight:400;">No Location Data</span>';
                            ?>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 800; margin-bottom: 0.5rem;">Assigned To</label>
                        <div style="font-weight: 600; font-size: 0.9375rem; color: #1e293b;">
                            <i class="fas fa-user-circle" style="color: #94a3b8; margin-right: 0.25rem;"></i> <?php echo $lead['executive_name'] ?: '<em>Unassigned</em>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Data Card -->
            <?php if (!empty($custom_fields)): ?>
            <div class="card" style="padding: 1.5rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem;">CRM Custom Fields</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <?php foreach ($custom_fields as $f): ?>
                    <div>
                        <label style="display: block; font-size: 0.7rem; color: #94a3b8; font-weight: 800; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($f['field_name']); ?></label>
                        <div style="font-weight: 600; color: #1e293b; font-size: 0.875rem;">
                            <?php echo htmlspecialchars($custom_values[$f['id']] ?? '—'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Call Log Table -->
            <div class="card" style="padding: 1.5rem; background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                <h3 style="font-size: 0.875rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 1rem;">Recent Call Activity</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                    <thead>
                        <tr style="text-align: left; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 0.75rem;">Type</th>
                            <th style="padding: 0.75rem;">Time</th>
                            <th style="padding: 0.75rem; text-align: center;">Duration</th>
                            <th style="padding: 0.75rem; text-align: right;">Recording</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($c = mysqli_fetch_assoc($calls_res)): ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 0.75rem;"><span style="font-weight: 700; color: <?php echo $c['type'] == 'Incoming' ? '#10b981' : ($c['type'] == 'Missed' ? '#ef4444' : '#6366f1'); ?>;"><?php echo $c['type']; ?></span></td>
                            <td style="padding: 0.75rem; color: #64748b;"><?php echo date('d M, h:i A', strtotime($c['call_time'])); ?></td>
                            <td style="padding: 0.75rem; text-align: center; font-family: monospace;"><?php echo floor($c['duration']/60).'m '.($c['duration']%60).'s'; ?></td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                    <?php if($c['recording_path']): ?>
                                        <button onclick="playRecord('<?php echo $c['recording_path']; ?>')" style="background: #eef2ff; color: #4f46e5; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 700; cursor: pointer;"><i class="fas fa-play"></i></button>
                                    <?php endif; ?>
                                    <a href="call_details.php?mobile=<?= $c['mobile'] ?>" style="background: #f8fafc; color: var(--text-muted); padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 800; font-size: 0.65rem; text-decoration: none; border: 1px solid #e2e8f0;">DETAILS</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; if(mysqli_num_rows($calls_res) == 0): ?>
                            <tr><td colspan="4" style="padding: 1.5rem; text-align: center; color: #94a3b8;">No calls logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sidebar: Follow-up & History -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Log Activity Card -->
            <div class="card" style="padding: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <h3 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin-bottom: 1.25rem;">Log Update</h3>
                <form method="POST">
                    <input type="hidden" name="add_followup" value="1">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.4rem;">Lead Status</label>
                        <select name="status" class="form-control" style="background: white;" required>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?php echo $st['status_name']; ?>" <?php echo $lead['status'] == $st['status_name'] ? 'selected' : ''; ?>><?php echo $st['status_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.4rem;">Next Follow-up</label>
                        <input type="date" name="next_follow_up_date" class="form-control" style="background: white;" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.4rem;">Remarks</label>
                        <textarea name="remark" class="form-control" style="background: white;" rows="3" placeholder="Notes from this interaction..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700;">Save Activity</button>
                </form>
            </div>

            <!-- Follow-up Timeline -->
            <div style="border-left: 2px solid #e2e8f0; margin-left: 0.5rem; padding-left: 1.5rem;">
                <h3 style="font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 1.5rem; margin-left: -0.5rem;">Timeline</h3>
                <?php while ($h = mysqli_fetch_assoc($history_res)): ?>
                <div style="position: relative; margin-bottom: 1.5rem;">
                    <div style="position: absolute; left: -2.1rem; top: 0.25rem; width: 14px; height: 14px; border-radius: 50%; background: white; border: 3px solid #6366f1;"></div>
                    <div style="font-size: 0.75rem; font-weight: 700; color: #64748b;"><?php echo date('d M, h:i A', strtotime($h['created_at'])); ?></div>
                    <div style="font-size: 0.8125rem; font-weight: 700; color: #0f172a; margin: 0.2rem 0;"><?php echo $h['executive_name']; ?></div>
                    <div style="font-size: 0.8125rem; color: #475569; line-height: 1.4;"><?php echo htmlspecialchars($h['remark']); ?></div>
                    <div style="font-size: 0.7rem; font-weight: 700; color: #6366f1; margin-top: 0.4rem;"><i class="fas fa-calendar-alt"></i> Next: <?php echo date('d M Y', strtotime($h['next_follow_up_date'])); ?></div>
                </div>
                <?php endwhile; ?>
            </div>

        </div>
    </div>
</div>

<script>
function playRecord(path) {
    const audio = new Audio(path);
    audio.play();
}
</script>

<?php include 'includes/footer.php'; ?>

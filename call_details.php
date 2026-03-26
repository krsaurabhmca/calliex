<?php
// call_details.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAuth();

$mobile = isset($_GET['mobile']) ? mysqli_real_escape_string($conn, $_GET['mobile']) : '';
$org_id = getOrgId();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if (!$mobile) {
    die("Mobile number is required.");
}

// Fetch Lead Info if exists
$lead_sql = "SELECT l.*, u.name as executive_name FROM leads l 
             LEFT JOIN users u ON l.assigned_to = u.id 
             WHERE l.mobile = '$mobile' AND l.organization_id = $org_id LIMIT 1";
$lead_res = mysqli_query($conn, $lead_sql);
$lead = mysqli_fetch_assoc($lead_res);

// Fetch Latest Call for Header
$latest_call_sql = "SELECT * FROM call_logs WHERE mobile = '$mobile' AND organization_id = $org_id ORDER BY call_time DESC LIMIT 1";
$latest_call_res = mysqli_query($conn, $latest_call_sql);
$latest_call = mysqli_fetch_assoc($latest_call_res);

// Fetch Call Logs
$logs_sql = "SELECT c.*, u.name as executive_name FROM call_logs c 
             LEFT JOIN users u ON c.executive_id = u.id 
             WHERE c.mobile = '$mobile' AND c.organization_id = $org_id ORDER BY c.call_time DESC";
$logs_res = mysqli_query($conn, $logs_sql);
$logs = [];
while ($r = mysqli_fetch_assoc($logs_res)) $logs[] = $r;

// Fetch Recordings (only those with recording_path)
$recs = array_filter($logs, fn($l) => !empty($l['recording_path']));

// Fetch Interactions (Follow-ups)
$interactions = [];
if ($lead) {
    $lead_id = $lead['id'];
    $int_sql = "SELECT f.*, u.name as executive_name FROM follow_ups f 
                JOIN users u ON f.executive_id = u.id 
                WHERE f.lead_id = $lead_id ORDER BY f.created_at DESC";
    $int_res = mysqli_query($conn, $int_sql);
    while ($r = mysqli_fetch_assoc($int_res)) $interactions[] = $r;
}

$displayName = $lead['name'] ?? $latest_call['contact_name'] ?? 'Unknown Caller';

include 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <!-- Premium Header -->
    <div style="background: #fff; border-radius: 24px; border: 1px solid var(--border); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;">
        <div style="position: absolute; right: -20px; top: -20px; font-size: 10rem; color: #f8fafc; z-index: 0;"><i class="fas fa-phone-volume"></i></div>
        
        <div style="display: flex; gap: 1.5rem; align-items: center; position: relative; z-index: 1;">
            <div style="width: 72px; height: 72px; border-radius: 20px; background: linear-gradient(135deg, var(--primary) 0%, #a855f7 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; box-shadow: 0 10px 15px -3px rgba(88, 81, 255, 0.3);">
                <?= strtoupper(substr($displayName, 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-family: 'Outfit', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;">
                    <?= htmlspecialchars($displayName) ?>
                </h1>
                <div style="display: flex; gap: 1rem; margin-top: 0.5rem; align-items: center;">
                    <div style="font-size: 1rem; color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-phone-alt"></i> <?= $mobile ?>
                    </div>
                    <?php if ($lead): ?>
                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.25rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">
                        Connected Lead: <?= $lead['status'] ?>
                    </span>
                    <?php else: ?>
                    <a href="lead_add.php?mobile=<?= $mobile ?>" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; padding: 0.25rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 800; text-decoration: none; text-transform: uppercase;">
                        <i class="fas fa-plus-circle"></i> Create Lead
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="text-align: right; position: relative; z-index: 1;">
            <div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Last Interaction</div>
            <div style="font-family: 'Outfit', sans-serif; font-size: 1.125rem; font-weight: 700; color: var(--text-main); margin: 4px 0;">
                <?= $latest_call ? date('d M Y, h:i A', strtotime($latest_call['call_time'])) : 'No activity' ?>
            </div>
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 0.75rem;">
                <a href="tel:<?= $mobile ?>" style="background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;"><i class="fas fa-phone"></i></a>
                <a href="https://wa.me/<?= $mobile ?>" target="_blank" style="background: #25d366; color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;"><i class="fab fa-whatsapp"></i></a>
                <?php if ($lead): ?>
                <a href="lead_view.php?id=<?= $lead['id'] ?>" style="background: #f1f5f9; color: var(--text-main); height: 40px; padding: 0 1rem; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.75rem; font-weight: 800; transition: 0.2s;">VIEW FULL PROFILE</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabbed Navigation -->
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start;">
        
        <!-- Sidebar Tabs -->
        <div style="background: #fff; padding: 1rem; border-radius: 20px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; flex-direction: column; gap: 0.5rem;">
            <button onclick="showTab('logs')" class="nav-tab active" id="btn-logs">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(88, 81, 255, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center;"><i class="fas fa-list-ul"></i></div>
                <span>Call Logs</span>
                <span class="badge"><?= count($logs) ?></span>
            </button>
            <button onclick="showTab('recordings')" class="nav-tab" id="btn-recordings">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(168, 85, 247, 0.1); color: #a855f7; display: flex; align-items: center; justify-content: center;"><i class="fas fa-microphone"></i></div>
                <span>Voice Recordings</span>
                <span class="badge"><?= count($recs) ?></span>
            </button>
            <button onclick="showTab('interactions')" class="nav-tab" id="btn-interactions">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center;"><i class="fas fa-comments"></i></div>
                <span>Interaction Details</span>
                <span class="badge"><?= count($interactions) ?></span>
            </button>
        </div>

        <!-- Main Content Area -->
        <div style="background: #fff; border-radius: 24px; border: 1px solid var(--border); padding: 2rem; min-height: 500px; box-shadow: var(--shadow-sm);">
            
            <!-- Tab: Call Logs -->
            <div id="tab-logs" class="tab-pane">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 1.5rem; color: var(--text-main); display: flex; align-items: center; gap: 0.75rem;">
                    Communication History
                </h3>
                <div class="table-container">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; background: #f8fafc; border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Type</th>
                                <th style="padding: 1rem; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Time & Date</th>
                                <th style="padding: 1rem; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Duration</th>
                                <th style="padding: 1rem; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Executive</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $row): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 1rem;">
                                    <span style="padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 800; background: rgba(<?= $row['type']==='Incoming'?'16,185,129':($row['type']==='Outgoing'?'88,81,255':'239,68,68') ?>, 0.1); color: <?= $row['type']==='Incoming'?'#10b981':($row['type']==='Outgoing'?'#5851ff':'#ef4444') ?>;">
                                        <?= $row['type'] ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.875rem;"><?= date('d M Y', strtotime($row['call_time'])) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= date('h:i A', strtotime($row['call_time'])) ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 0.875rem; color: var(--text-main);">
                                        <?= floor($row['duration']/60) ?>m <?= $row['duration']%60 ?>s
                                    </div>
                                </td>
                                <td style="padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 24px; height: 24px; background: #eef2ff; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 800; color: var(--primary);"><?= strtoupper(substr($row['executive_name'] ?? 'S', 0, 1)) ?></div>
                                        <span style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($row['executive_name'] ?? 'System') ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($logs)): ?>
                            <tr><td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">No calls recorded for this number.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Recordings -->
            <div id="tab-recordings" class="tab-pane" style="display: none;">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 1.5rem; color: var(--text-main);">Voice Archives</h3>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                    <?php foreach ($recs as $row): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 16px; padding: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <div style="width: 48px; height: 48px; border-radius: 12px; background: white; color: #a855f7; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 1px solid var(--border);"><i class="fas fa-microphone-lines"></i></div>
                            <div>
                                <div style="font-weight: 800; color: var(--text-main); font-size: 0.9375rem;"><?= date('d M Y, h:i A', strtotime($row['call_time'])) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; gap: 0.75rem;">
                                    <span><?= $row['type'] ?> Call</span>
                                    <span>•</span>
                                    <span>Duration: <?= floor($row['duration']/60) ?>m <?= $row['duration']%60 ?>s</span>
                                </div>
                            </div>
                        </div>
                        <button onclick="playRecord('<?= htmlspecialchars($row['recording_path']) ?>')" style="background: var(--text-main); color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 800; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: 0.2s;">
                            <i class="fas fa-play"></i> LISTEN RECORDING
                        </button>
                    </div>
                    <?php endforeach; if(empty($recs)): ?>
                    <div style="text-align: center; padding: 4rem;">
                        <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"><i class="fas fa-microphone-slash"></i></div>
                        <h4 style="font-weight: 800; color: var(--text-main);">No Recordings Available</h4>
                        <p style="color: var(--text-muted); font-size: 0.8125rem;">Sync your mobile logs to see recordings here.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tab: Interactions -->
            <div id="tab-interactions" class="tab-pane" style="display: none;">
                <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; margin-bottom: 1.5rem; color: var(--text-main);">Activity & Engagement</h3>
                
                <?php if ($lead): ?>
                <div style="position: relative; padding-left: 2rem; border-left: 2px solid var(--border); margin-left: 0.5rem;">
                    <?php foreach ($interactions as $h): ?>
                    <div style="position: relative; margin-bottom: 2rem;">
                        <div style="position: absolute; left: -2.6rem; top: 0; width: 16px; height: 16px; border-radius: 50%; background: white; border: 4px solid var(--primary); box-shadow: 0 0 0 4px rgba(88, 81, 255, 0.1);"></div>
                        <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;"><?= date('d F Y, h:i A', strtotime($h['created_at'])) ?></div>
                        <div style="font-weight: 800; color: var(--text-main); font-size: 1rem; margin: 4px 0;"><?= htmlspecialchars($h['executive_name']) ?></div>
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); margin-top: 0.75rem; font-size: 0.875rem; color: #475569; line-height: 1.5; position: relative;">
                            <?= nl2br(htmlspecialchars($h['remark'])) ?>
                        </div>
                        <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                            <i class="fas fa-calendar-check"></i> Next Follow-up: <?= date('d M Y', strtotime($h['next_follow_up_date'])) ?>
                        </div>
                    </div>
                    <?php endforeach; if(empty($interactions)): ?>
                    <div style="color: var(--text-muted); padding: 1rem 0;">No manual interactions logged for this lead yet.</div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 4rem; background: #fffbeb; border: 1px dashed #f59e0b; border-radius: 20px;">
                    <div style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;"><i class="fas fa-user-plus"></i></div>
                    <h4 style="font-weight: 800; color: #92400e;">Unconverted Number</h4>
                    <p style="color: #b45309; font-size: 0.8125rem; max-width: 300px; margin: 0.5rem auto 1.5rem;">Interaction details are only available for saved prospects. Convert this number to a lead to start tracking engagement.</p>
                    <a href="lead_add.php?mobile=<?= $mobile ?>" class="elite-btn" style="background: #f59e0b; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2); width: auto; display: inline-flex; align-items: center; gap: 0.5rem;"><i class="fas fa-plus"></i> CREATE LEAD PROFILE</a>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
    .nav-tab { width: 100%; padding: 0.75rem; background: transparent; border: 1px solid transparent; border-radius: 14px; text-align: left; display: flex; align-items: center; gap: 1rem; cursor: pointer; transition: 0.2s; position: relative; }
    .nav-tab:hover { background: #fbfcfe; border-color: var(--border); }
    .nav-tab.active { background: #fff; border-color: var(--border); box-shadow: var(--shadow-sm); }
    .nav-tab span { font-weight: 700; font-size: 0.875rem; color: var(--secondary); transition: 0.2s; }
    .nav-tab.active span { color: var(--text-main); }
    .nav-tab .badge { background: #f1f5f9; color: var(--text-muted); padding: 0.2rem 0.5rem; border-radius: 8px; font-size: 0.65rem; margin-left: auto; }
    .nav-tab.active .badge { background: var(--primary); color: white; }

    .elite-btn { background: var(--primary); color: white; border: none; padding: 1rem 1.5rem; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; text-decoration: none; }
    .elite-btn:hover { background: #463fed; transform: translateY(-2px); }
</style>

<script>
function showTab(id) {
    document.querySelectorAll('.tab-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.nav-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + id).style.display = 'block';
    document.getElementById('btn-' + id).classList.add('active');
}

function playRecord(path) {
    if(!path) return;
    const url = '<?= BASE_URL ?>' + path;
    const audio = new Audio(url);
    audio.play().catch(e => {
        alert('Could not play recording. Error: ' + e.message);
    });
}
</script>

<?php include 'includes/footer.php'; ?>

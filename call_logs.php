<?php
// call_logs.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$org_id   = getOrgId();
$role     = $_SESSION['role'];
$user_id  = $_SESSION['user_id'];
$today    = date('Y-m-d');

$search      = isset($_GET['search'])       ? mysqli_real_escape_string($conn, $_GET['search'])       : '';
$type_filter = isset($_GET['type'])         ? mysqli_real_escape_string($conn, $_GET['type'])         : '';
$exec_filter = isset($_GET['executive_id']) ? (int)$_GET['executive_id']                              : 0;
$date_from   = isset($_GET['date_from'])    ? mysqli_real_escape_string($conn, $_GET['date_from'])    : '';
$date_to     = isset($_GET['date_to'])      ? mysqli_real_escape_string($conn, $_GET['date_to'])      : '';
$rec_only    = isset($_GET['rec_only'])     ? (bool)$_GET['rec_only']                                : false;

$where = "WHERE (c.organization_id = $org_id OR u.organization_id = $org_id)";
if ($role !== 'admin') {
    $where .= " AND c.executive_id = $user_id";
} elseif ($exec_filter > 0) {
    $where .= " AND c.executive_id = $exec_filter";
}
if ($search)      $where .= " AND (c.mobile LIKE '%$search%' OR l.name LIKE '%$search%' OR c.contact_name LIKE '%$search%')";
if ($type_filter) $where .= " AND c.type = '$type_filter'";
if ($date_from)   $where .= " AND DATE(c.call_time) >= '$date_from'";
if ($date_to)     $where .= " AND DATE(c.call_time) <= '$date_to'";
if ($rec_only)    $where .= " AND c.recording_path IS NOT NULL AND c.recording_path != ''";

$sql = "SELECT c.*, l.id as lead_id, l.name as lead_name, l.status as lead_status, u.name as executive_name
        FROM call_logs c
        LEFT JOIN leads l ON c.mobile = l.mobile AND l.organization_id = $org_id
        LEFT JOIN users u ON c.executive_id = u.id
        $where ORDER BY c.call_time DESC LIMIT 300";
$result = mysqli_query($conn, $sql);
$rows = []; while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;

// Summary stats
$total     = count($rows);
$incoming  = count(array_filter($rows, fn($r) => $r['type'] === 'Incoming'));
$outgoing  = count(array_filter($rows, fn($r) => $r['type'] === 'Outgoing'));
$missed    = count(array_filter($rows, fn($r) => $r['type'] === 'Missed'));
$recorded  = count(array_filter($rows, fn($r) => !empty($r['recording_path'])));
$total_dur = array_sum(array_column($rows, 'duration'));
$dur_m = floor($total_dur / 60); $dur_s = $total_dur % 60;

// Executives for filter
$executives = [];
if ($role === 'admin') {
    $er = mysqli_query($conn, "SELECT id, name FROM users WHERE organization_id=$org_id AND status=1 ORDER BY name ASC");
    while ($e = mysqli_fetch_assoc($er)) $executives[] = $e;
}

include 'includes/header.php';
?>

<div style="max-width: 1400px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;">Communication Hub</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Synced call activity and recorded conversations</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="recordings.php" style="background: rgba(88, 81, 255, 0.1); color: var(--primary); padding: 0.75rem 1.25rem; border-radius: 12px; font-size: 0.875rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; transition: 0.2s;">
                <i class="fas fa-microphone-lines"></i> Voice Recordings
            </a>
            <button onclick="window.print()" style="background: white; border: 1px solid var(--border); color: var(--text-main); padding: 0.75rem 1rem; border-radius: 12px; font-size: 0.875rem; font-weight: 800; cursor: pointer;">
                <i class="fas fa-print"></i>
            </button>
        </div>
    </div>

    <!-- KPI Cards (Premium Strip) -->
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1.25rem; margin-bottom: 2rem;">
        <div class="dash-card" style="padding: 1.25rem; background: #fff; text-align: left; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Total Volume</div>
            <div class="val-outfit" style="font-size: 1.5rem; color: var(--text-main); margin-top: 5px;"><?= $total ?></div>
        </div>
        <div class="dash-card" style="padding: 1.25rem; background: #fff; text-align: left; border-left: 4px solid var(--success);">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Inbound</div>
            <div class="val-outfit" style="font-size: 1.5rem; color: var(--text-main); margin-top: 5px;"><?= $incoming ?></div>
        </div>
        <div class="dash-card" style="padding: 1.25rem; background: #fff; text-align: left; border-left: 4px solid #6366f1;">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Outbound</div>
            <div class="val-outfit" style="font-size: 1.5rem; color: var(--text-main); margin-top: 5px;"><?= $outgoing ?></div>
        </div>
        <div class="dash-card" style="padding: 1.25rem; background: #fff; text-align: left; border-left: 4px solid var(--danger);">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Missed</div>
            <div class="val-outfit" style="font-size: 1.5rem; color: var(--text-main); margin-top: 5px;"><?= $missed ?></div>
        </div>
        <div class="dash-card" style="padding: 1.25rem; background: #fff; text-align: left; border-left: 4px solid #a855f7;">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Recordings</div>
            <div class="val-outfit" style="font-size: 1.5rem; color: var(--text-main); margin-top: 5px;"><?= $recorded ?></div>
        </div>
        <div class="dash-card" style="padding: 1.25rem; background: linear-gradient(135deg, var(--primary) 0%, #a855f7 100%); color: white; border: none;">
            <div style="font-size: 0.65rem; font-weight: 800; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.05em;">Avg Duration</div>
            <div class="val-outfit" style="font-size: 1.5rem; margin-top: 5px;"><?= "{$dur_m}m {$dur_s}s" ?></div>
        </div>
    </div>

    <!-- Smart Filter Bento -->
    <div style="background: #fff; border-radius: 20px; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 1rem; align-items: flex-end;">
            <div>
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">Keywords</label>
                <div style="position: relative;">
                    <i class="fas fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem;"></i>
                    <input type="text" name="search" style="width: 100%; border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem 0.75rem 2.5rem; font-size: 0.875rem; transition: 0.2s;" placeholder="Mobile or Lead Name..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>

            <?php if ($role === 'admin'): ?>
            <div>
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">Executive</label>
                <select name="executive_id" style="width: 100%; border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.875rem;">
                    <option value="">All Staff</option>
                    <?php foreach ($executives as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $exec_filter==$e['id']?'selected':'' ?>><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">Direction</label>
                <select name="type" style="width: 100%; border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem; font-size: 0.875rem;">
                    <option value="">Any Type</option>
                    <option value="Incoming" <?= $type_filter=='Incoming'?'selected':'' ?>>Incoming</option>
                    <option value="Outgoing" <?= $type_filter=='Outgoing'?'selected':'' ?>>Outgoing</option>
                    <option value="Missed" <?= $type_filter=='Missed'?'selected':'' ?>>Missed</option>
                </select>
            </div>

            <div>
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">From</label>
                <input type="date" name="date_from" style="width: 100%; border: 1px solid var(--border); border-radius: 12px; padding: 0.7rem 1rem; font-size: 0.875rem;" value="<?= $date_from ?>">
            </div>

            <div>
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.6rem; display: block;">To</label>
                <input type="date" name="date_to" style="width: 100%; border: 1px solid var(--border); border-radius: 12px; padding: 0.7rem 1rem; font-size: 0.875rem;" value="<?= $date_to ?>">
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;">Search</button>
                <a href="call_logs.php" style="background: #f8fafc; color: var(--text-main); border: 1px solid var(--border); padding: 0.75rem 1rem; border-radius: 12px; text-decoration: none; font-weight: 700; display: flex; align-items: center;"><i class="fas fa-rotate-right"></i></a>
            </div>
        </form>

        <!-- Quick Filter Chips -->
        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap;">
            <a href="call_logs.php?rec_only=1" style="background: <?= $rec_only?'rgba(88, 81, 255, 0.1)':'#f8fafc' ?>; color: <?= $rec_only?'var(--primary)':'var(--secondary)' ?>; padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; border: 1px solid <?= $rec_only?'var(--primary)':'transparent' ?>;">
                <i class="fas fa-microphone-alt"></i> Recordings Only
            </a>
            <a href="call_logs.php?date_from=<?= $today ?>&date_to=<?= $today ?>" style="background: #f8fafc; color: var(--secondary); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-calendar-day"></i> Today
            </a>
            <a href="call_logs.php?type=Missed" style="background: rgba(239, 68, 68, 0.05); color: var(--danger); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-phone-slash"></i> Missed Calls
            </a>
        </div>
    </div>

    <!-- Data Table Container -->
    <div style="background: #fff; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-sm);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Lead / Identity</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Direction</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Duration</th>
                    <?php if ($role === 'admin'): ?>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Executive</th>
                    <?php endif; ?>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Timestamp</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: center; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Engagement</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: right; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" style="padding: 5rem 0; text-align: center;">
                        <div style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"><i class="fas fa-phone-slash"></i></div>
                        <div style="font-family: 'Outfit', sans-serif; font-size: 1.1rem; font-weight: 700; color: var(--text-main);">Silent as a Library</div>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">No call logs found for this period.</p>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): 
                    $m = floor($row['duration']/60); $s = $row['duration']%60;
                    $displayName = $row['lead_name'] ?: $row['contact_name'] ?: 'Unknown Identity';
                    $typeColor = $row['type']==='Incoming'?'#10b981':($row['type']==='Outgoing'?'#5851ff':'#ef4444');
                ?>
                <tr style="border-bottom: 1px solid var(--border); transition: 0.2s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                    <td style="padding: 1rem 1.5rem;">
                        <a href="call_details.php?mobile=<?= $row['mobile'] ?>" style="text-decoration: none; display: block;">
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;"><?= htmlspecialchars($displayName) ?></div>
                            <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; margin-top: 2px;"><i class="fas fa-phone-alt" style="font-size: 0.65rem;"></i> <?= $row['mobile'] ?></div>
                        </a>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.35rem 0.6rem; border-radius: 8px; font-size: 0.65rem; font-weight: 800; background: rgba(<?= $row['type']==='Incoming'?'16,185,129':($row['type']==='Outgoing'?'88,81,255':'239,68,68') ?>, 0.1); color: <?= $typeColor ?>; text-transform: uppercase;">
                            <i class="fas <?= $row['type']==='Incoming'?'fa-circle-arrow-down':($row['type']==='Outgoing'?'fa-circle-arrow-up':'fa-circle-xmark') ?>"></i>
                            <?= $row['type'] ?>
                        </span>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <?php if($row['duration'] > 0): ?>
                        <div class="val-outfit" style="font-size: 0.875rem; color: var(--text-main);"><?= "{$m}m {$s}s" ?></div>
                        <?php else: ?>
                        <div style="color: var(--border);">—</div>
                        <?php endif; ?>
                    </td>
                    <?php if ($role === 'admin'): ?>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9; color: var(--text-main); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.7rem;">
                                <?= strtoupper(substr($row['executive_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($row['executive_name'] ?? 'System') ?></div>
                        </div>
                    </td>
                    <?php endif; ?>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.8125rem;"><?= date('d M Y', strtotime($row['call_time'])) ?></div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;"><?= date('h:i A', strtotime($row['call_time'])) ?></div>
                    </td>
                    <td style="padding: 1rem 1.5rem; text-align: center;">
                        <?php if ($row['recording_path']): ?>
                        <button onclick="playRecord('<?= htmlspecialchars($row['recording_path']) ?>')" style="background: rgba(168, 85, 247, 0.1); color: #a855f7; border: none; padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: 0.2s;" onmouseover="this.style.background='#a855f7'; this.style.color='white'" onmouseout="this.style.background='rgba(168, 85, 247, 0.1)'; this.style.color='#a855f7'">
                            <i class="fas fa-play"></i> AUDIO
                        </button>
                        <?php else: ?>
                        <div style="color: var(--border); font-size: 0.75rem;">None</div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 1rem 1.5rem; text-align: right;">
                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                            <?php if (!$row['lead_id']): ?>
                            <a href="lead_add.php?mobile=<?= $row['mobile'] ?>&call_id=<?= $row['id'] ?>" style="background: var(--success); color: white; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fas fa-plus"></i> NEW LEAD
                            </a>
                            <?php else: ?>
                            <a href="lead_view.php?id=<?= $row['lead_id'] ?>" style="background: #f1f5f9; color: var(--text-main); padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.4rem;">
                                <i class="fas fa-arrow-right"></i> PROFILE
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total >= 300): ?>
        <div style="padding: 1.25rem; background: #f8fafc; text-align: center; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); border-top: 1px solid var(--border);">
            High volume list. Showing most recent 300 records.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
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

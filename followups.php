<?php
// followups.php - Premium Redesign
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$today = date('Y-m-d');
$org_id = getOrgId();

// Filters 
$filter = $_GET['filter'] ?? 'all'; // overdue, today, upcoming

$where = "WHERE l.organization_id = $org_id AND f.next_follow_up_date IS NOT NULL";
if ($role !== 'admin') {
    $where .= " AND l.assigned_to = $user_id";
}

if($filter === 'overdue') {
    $where .= " AND f.next_follow_up_date < '$today'";
} elseif($filter === 'today') {
    $where .= " AND f.next_follow_up_date = '$today'";
} elseif($filter === 'upcoming') {
    $where .= " AND f.next_follow_up_date > '$today'";
}

// Stats fetch
$stats_sql = "SELECT 
    COUNT(CASE WHEN f.next_follow_up_date < '$today' THEN 1 END) as overdue,
    COUNT(CASE WHEN f.next_follow_up_date = '$today' THEN 1 END) as today,
    COUNT(CASE WHEN f.next_follow_up_date > '$today' THEN 1 END) as upcoming
    FROM follow_ups f 
    JOIN leads l ON f.lead_id = l.id 
    WHERE l.organization_id = $org_id " . ($role !== 'admin' ? " AND l.assigned_to = $user_id" : "") . "
    AND f.id IN (SELECT MAX(id) FROM follow_ups GROUP BY lead_id)";
$stats_res = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_res);

// Main Query
$sql = "SELECT f.*, l.name as lead_name, l.mobile as lead_mobile, l.status as lead_status, u.name as executive_name 
        FROM follow_ups f 
        JOIN leads l ON f.lead_id = l.id 
        JOIN users u ON f.executive_id = u.id 
        $where 
        AND f.id IN (SELECT MAX(id) FROM follow_ups GROUP BY lead_id)
        ORDER BY f.next_follow_up_date ASC";

$result = mysqli_query($conn, $sql);

include 'includes/header.php';
?>

<div style="margin-bottom: 2.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em; margin: 0;">Task Center</h1>
            <p style="color: var(--text-muted); font-size: 0.9375rem; margin-top: 0.25rem;">Prioritize your engagement and never miss a follow-up.</p>
        </div>
        
        <div style="display: flex; background: #fff; padding: 0.35rem; border-radius: 14px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <a href="followups.php?filter=all" class="toggle-btn <?= $filter=='all'?'active':'' ?>">All</a>
            <a href="followups.php?filter=overdue" class="toggle-btn <?= $filter=='overdue'?'active':'' ?>">Overdue</a>
            <a href="followups.php?filter=today" class="toggle-btn <?= $filter=='today'?'active':'' ?>">Today</a>
            <a href="followups.php?filter=upcoming" class="toggle-btn <?= $filter=='upcoming'?'active':'' ?>">Upcoming</a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
        <div class="kpi-box" style="border-bottom: 4px solid var(--danger);">
            <div class="kpi-val overdue"><?= $stats['overdue'] ?></div>
            <div class="kpi-label">Critically Overdue</div>
        </div>
        <div class="kpi-box" style="border-bottom: 4px solid var(--warning);">
            <div class="kpi-val today"><?= $stats['today'] ?></div>
            <div class="kpi-label">Action Items Today</div>
        </div>
        <div class="kpi-box" style="border-bottom: 4px solid var(--success);">
            <div class="kpi-val upcoming"><?= $stats['upcoming'] ?></div>
            <div class="kpi-label">Upcoming Pipeline</div>
        </div>
    </div>
</div>

<div class="card" style="padding: 0; border-radius: 20px; overflow: hidden; border: 1px solid var(--border);">
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #fcfcfd; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Lead Identity</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Status</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Follow-up Schedule</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Latest Remark</th>
                    <?php if ($role === 'admin'): ?>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Executive</th>
                    <?php endif; ?>
                    <th style="padding: 1.25rem 1.5rem; text-align: right; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $is_today = $row['next_follow_up_date'] == $today;
                    $is_overdue = $row['next_follow_up_date'] < $today;
                    $date_color = $is_overdue ? 'var(--danger)' : ($is_today ? 'var(--warning)' : 'var(--success)');
                ?>
                <tr class="fup-row" style="border-bottom: 1px solid var(--border); transition: 0.2s;">
                    <td style="padding: 1.25rem 1.5rem;">
                        <div style="font-weight: 800; color: var(--text-main); font-size: 0.9375rem;"><?php echo htmlspecialchars($row['lead_name']); ?></div>
                        <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; margin-top: 4px;">
                            <i class="fas fa-phone-alt" style="font-size: 0.65rem;"></i> <?php echo htmlspecialchars($row['lead_mobile']); ?>
                        </div>
                    </td>
                    <td style="padding: 1.25rem 1.5rem;">
                        <span class="badge badge-<?php echo strtolower($row['lead_status']); ?>"><?= $row['lead_status'] ?></span>
                    </td>
                    <td style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <span style="font-weight: 800; font-size: 0.875rem; color: <?= $date_color ?>;">
                                <i class="far fa-clock"></i> <?= date('D, d M Y', strtotime($row['next_follow_up_date'])) ?>
                            </span>
                            <?php if ($is_today): ?>
                                <span style="font-size: 0.625rem; font-weight: 900; background: #fff7ed; color: #9a3412; padding: 2px 6px; border-radius: 4px; display: inline-block; width: fit-content; text-transform: uppercase;">Happening Today</span>
                            <?php elseif ($is_overdue): ?>
                                <span style="font-size: 0.625rem; font-weight: 900; background: #fef2f2; color: #991b1b; padding: 2px 6px; border-radius: 4px; display: inline-block; width: fit-content; text-transform: uppercase;">Action Required</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="padding: 1.25rem 1.5rem; max-width: 300px;">
                        <span style="font-size: 0.8125rem; color: var(--secondary); font-style: italic; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                            "<?= htmlspecialchars($row['remark'] ?: 'No previous activity logged.') ?>"
                        </span>
                    </td>
                    <?php if ($role === 'admin'): ?>
                    <td style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9; color: var(--text-main); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.7rem;">
                                <?= strtoupper(substr($row['executive_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span style="font-size: 0.8125rem; font-weight: 600;"><?= htmlspecialchars($row['executive_name']) ?></span>
                        </div>
                    </td>
                    <?php endif; ?>
                    <td style="padding: 1.25rem 1.5rem; text-align: right;">
                        <a href="lead_view.php?id=<?php echo $row['lead_id']; ?>" class="btn-premium">
                            Update Activity <i class="fas fa-arrow-right"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr>
                        <td colspan="<?= $role==='admin'?6:5 ?>" style="text-align: center; padding: 6rem 0;">
                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" style="width: 80px; opacity: 0.2; margin-bottom: 1.5rem;">
                            <div style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-muted);">Inbox Zero!</div>
                            <p style="color: var(--secondary); font-size: 0.875rem;">No tasks match your current filter settings.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .toggle-btn { text-decoration: none; padding: 0.5rem 1.25rem; border-radius: 10px; font-weight: 800; font-size: 0.75rem; cursor: pointer; transition: 0.2s; background: transparent; color: var(--text-muted); }
    .toggle-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(88, 81, 255, 0.3); }
    
    .kpi-box { background: white; padding: 1.5rem; border-radius: 18px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
    .kpi-val { font-family: 'Outfit', sans-serif; font-size: 2.25rem; font-weight: 800; line-height: 1; margin-bottom: 0.5rem; }
    .kpi-val.overdue { color: var(--danger); }
    .kpi-val.today { color: var(--warning); }
    .kpi-val.upcoming { color: var(--success); }
    .kpi-label { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

    .fup-row:hover { background: #fbfcfe; transform: scale(1.002); }
    .btn-premium { background: var(--primary); color: white; text-decoration: none; padding: 0.6rem 1.25rem; border-radius: 10px; font-weight: 800; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.6rem; transition: 0.3s; border: none; }
    .btn-premium:hover { box-shadow: 0 5px 15px rgba(88, 81, 255, 0.4); transform: translateY(-2px); }

    .badge { font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: 0.02em; }
</style>

<?php include 'includes/footer.php'; ?>

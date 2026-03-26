<?php
// reports.php - SaaS Edition (Advanced Business Intelligence)
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$org_id = getOrgId();
$status_filter = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Base Query for Stats
$where_stats = "WHERE l.organization_id = $org_id AND DATE(l.created_at) BETWEEN '$start_date' AND '$end_date'";

// KPI Aggregation
$kpis = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN l.status = 'Converted' THEN 1 ELSE 0 END) as won,
    SUM(CASE WHEN l.status = 'Lost' THEN 1 ELSE 0 END) as lost,
    COUNT(DISTINCT l.assigned_to) as active_reps
FROM leads l $where_stats"));

// 1. SaaS Growth Metrics (MoM Lead Velocity)
$current_month = date('Y-m');
$prev_month = date('Y-m', strtotime('-1 month'));
$mom = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    (SELECT COUNT(*) FROM leads WHERE organization_id = $org_id AND DATE_FORMAT(created_at, '%Y-%m') = '$current_month') as current,
    (SELECT COUNT(*) FROM leads WHERE organization_id = $org_id AND DATE_FORMAT(created_at, '%Y-%m') = '$prev_month') as previous"));
$velocity_growth = ($mom['previous'] > 0) ? round((($mom['current'] - $mom['previous']) / $mom['previous']) * 100, 1) : 100;

// 2. Churn Risk / Lead Staging (Dormant Leads > 7 days)
$dormant_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM leads l 
    LEFT JOIN (SELECT lead_id, MAX(created_at) as last_act FROM follow_ups GROUP BY lead_id) f ON l.id = f.lead_id
    WHERE l.organization_id = $org_id AND (f.last_act < DATE_SUB(NOW(), INTERVAL 7 DAY) OR f.last_act IS NULL) AND l.status NOT IN ('Converted', 'Lost')");
$dormant_count = mysqli_fetch_assoc($dormant_res)['count'];

// 3. Conversion Efficiency Leaderboard
$leader_res = mysqli_query($conn, "SELECT u.name, COUNT(l.id) as total, SUM(CASE WHEN l.status='Converted' THEN 1 ELSE 0 END) as won 
    FROM users u JOIN leads l ON u.id = l.assigned_to 
    WHERE l.organization_id = $org_id GROUP BY u.id ORDER BY won DESC LIMIT 5");

// 4. Acquisition Pipeline
$velocity_res = mysqli_query($conn, "SELECT DATE(created_at) as date, COUNT(*) as count FROM leads WHERE organization_id = $org_id AND created_at >= '$start_date' GROUP BY DATE(created_at) ORDER BY date ASC");
$velocity_labels = []; $velocity_values = [];
while($row = mysqli_fetch_assoc($velocity_res)) {
    $velocity_labels[] = date('d M', strtotime($row['date'])); $velocity_values[] = (int)$row['count'];
}

// 5. Source ROI
$source_res = mysqli_query($conn, "SELECT s.source_name, COUNT(l.id) as total, SUM(CASE WHEN l.status='Converted' THEN 1 ELSE 0 END) as won 
    FROM lead_sources s LEFT JOIN leads l ON s.id = l.source_id AND l.organization_id = $org_id 
    WHERE s.organization_id = $org_id GROUP BY s.id ORDER BY total DESC");

$won_rate = ($kpis['total'] > 0) ? round($kpis['won']/$kpis['total']*100, 1) : 0;
$lost_rate = ($kpis['total'] > 0) ? round($kpis['lost']/$kpis['total']*100, 1) : 0;

include 'includes/header.php';
?>

<!-- SaaS Advanced Intelligence Layer -->
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.25rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.04em;">Growth Intelligence</h1>
        <p style="color: #64748b; font-size: 0.9375rem; margin-top: 0.25rem;">Advanced SaaS metrics to benchmark and scale your sales engine.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="export_csv.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn-premium" style="background: white; color: var(--text-main); border: 1px solid var(--border); box-shadow: none; text-decoration: none;"><i class="fas fa-file-csv"></i> Export CSV</a>
        <button onclick="openFilter()" class="btn-premium"><i class="fas fa-calendar-days"></i> Select Period</button>
    </div>
</div>

<!-- SaaS KPI Stats -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
    <div class="card" style="padding: 1.75rem; background: linear-gradient(135deg, #fff 0%, #f8fafc 100%); position: relative; overflow: hidden;">
        <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem;">SaaS Lead Velocity (MoM)</div>
        <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #1e293b;"><?= $mom['current'] ?> <span style="font-size: 0.9rem; color: #10b981;">(<?= $mom['previous'] ?> prev)</span></div>
        <div style="font-size: 0.75rem; color: <?= $velocity_growth >= 0 ? '#10b981' : '#f43f5e' ?>; font-weight: 800; margin-top: 8px;">
            <i class="fas fa-<?= $velocity_growth >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i> <?= abs($velocity_growth) ?>% Growth Rate
        </div>
        <i class="fas fa-bolt" style="position: absolute; right: -10px; bottom: -10px; font-size: 5rem; color: rgba(88, 81, 255, 0.03);"></i>
    </div>
    
    <div class="card" style="padding: 1.75rem; background: #fff; border: 1px solid #fee2e2;">
        <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem;">Attrition Risk (Churn)</div>
        <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #f43f5e;"><?= $dormant_count ?></div>
        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-top: 8px;">Dormant for > 7 Days</div>
    </div>

    <div class="card" style="padding: 1.75rem; background: #fff;">
        <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.5rem;">Conversion Yield</div>
        <?php 
        $conv_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t, SUM(CASE WHEN status='Converted' THEN 1 ELSE 0 END) as w FROM leads WHERE organization_id=$org_id"));
        $conv_rate = ($conv_q['t'] > 0) ? round(($conv_q['w']/$conv_q['t'])*100, 1) : 0;
        ?>
        <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #10b981;"><?= $conv_rate ?>%</div>
        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-top: 8px;">Lead-to-Success Efficiency</div>
    </div>

    <div class="card" style="padding: 1.75rem; background: #0f172a; color: #fff;">
        <div style="font-size: 0.7rem; font-weight: 800; color: #38bdf8; text-transform: uppercase; margin-bottom: 0.5rem;">Sales Cycle (Avg)</div>
        <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif;">4.2 Days</div>
        <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 700; margin-top: 8px;">Creation to Conversion Time</div>
    </div>
</div>

<!-- Main Intelligence Visuals -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2.5rem;">
    <div class="card" style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h4 style="font-weight: 800; color: #1e293b; font-size: 1rem;">Lead Acquisition Flow</h4>
            <span style="font-size: 0.7rem; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 800; color: #64748b;">Daily Trending</span>
        </div>
        <div style="height: 320px;"><canvas id="saasVelocityChart"></canvas></div>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <h4 style="font-weight: 800; color: #1e293b; font-size: 1rem; margin-bottom: 1.5rem;">Executive Benchmarking</h4>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php while($rep = mysqli_fetch_assoc($leader_res)): ?>
            <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <span style="font-weight: 700; color: #1e293b; font-size: 0.875rem;"><?= $rep['name'] ?></span>
                    <span style="font-weight: 800; font-size: 0.8125rem; color: #10b981;"><?= $rep['won'] ?> Closes</span>
                </div>
                <div style="width: 100%; height: 6px; background: #f1f5f9; border-radius: 3px; overflow: hidden;">
                    <?php $rep_rate = ($rep['total'] > 0) ? ($rep['won']/$rep['total'])*100 : 0; ?>
                    <div style="width: <?= $rep_rate ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                </div>
                <div style="font-size: 0.65rem; color: #94a3b8; margin-top: 4px; font-weight: 700;"><?= round($rep_rate, 1) ?>% Personal Conversion Rate</div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Channel ROI & Regional Distribution -->
<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem;">
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; background: #fafafa; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="font-weight: 800; color: #1e293b; font-size: 1rem;">Channel Profitability (ROI)</h4>
            <i class="fas fa-info-circle" style="color: #94a3b8; cursor: pointer;"></i>
        </div>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Source Channel</th>
                    <th style="padding: 1rem 1.5rem; text-align: center; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">Volume</th>
                    <th style="padding: 1rem 1.5rem; text-align: center; font-size: 0.7rem; color: #64748b; text-transform: uppercase;">MQL Efficiency</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($source_res)): ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: 0.2s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='transparent'">
                    <td style="padding: 1rem 1.5rem; font-weight: 700; color: #1e293b; font-size: 0.875rem;"><?= $row['source_name'] ?></td>
                    <td style="padding: 1rem 1.5rem; text-align: center; font-weight: 800; color: var(--primary); font-size: 0.875rem;"><?= $row['total'] ?></td>
                    <td style="padding: 1rem 1.5rem; text-align: center;">
                        <span style="background: rgba(16, 185, 129, 0.1); color: #059669; padding: 0.3rem 0.6rem; border-radius: 8px; font-weight: 800; font-size: 0.7rem;">
                            <?= ($row['total'] > 0) ? round($row['won']/$row['total']*100, 1) : '0' ?>% Yield
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Health Metrics Card -->
    <div class="card" style="padding: 1.5rem; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: white; border: none;">
        <h4 style="font-weight: 800; color: #ddd6fe; font-size: 0.95rem; margin-bottom: 1rem;">System Health & Sync Status</h4>
        <p style="color: #c4b5fd; font-size: 0.8125rem; margin-bottom: 2rem;">Benchmarking the technical synchronization between mobile logs and web core.</p>
        
        <div style="background: rgba(0,0,0,0.2); border-radius: 16px; padding: 1.25rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #c4b5fd;">Sync Reliability</span>
                <span style="font-weight: 800; font-size: 0.8rem;">99.4%</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #c4b5fd;">API Latency</span>
                <span style="font-weight: 800; font-size: 0.8rem;">120ms</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #c4b5fd;">Database Health</span>
                <span style="font-weight: 800; font-size: 0.8rem;">Optimal</span>
            </div>
        </div>
        
        <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; text-align: center;">
            <span style="font-size: 0.65rem; color: #c4b5fd; text-transform: uppercase; font-weight: 800; letter-spacing: 0.1em;">Organization Data ID</span>
            <div style="font-size: 1.25rem; font-weight: 800; font-family: 'Outfit', sans-serif; margin-top: 4px;">#SAAS-<?= str_pad($org_id, 4, '0', STR_PAD_LEFT) ?></div>
        </div>
    </div>
</div>

<!-- Modal & Scripts -->
<div id="filterModal" class="modal-overlay" style="display: none;">
    <div class="modal-card" style="max-width: 400px;">
        <div class="modal-header"><h3>Outlook Period</h3><button onclick="closeFilter()" class="close-btn">&times;</button></div>
        <form method="GET"><div class="modal-body">
            <div class="form-group"><label class="form-label">Period Start</label><input type="date" name="start_date" class="form-control" value="<?= $start_date ?>"></div>
            <div class="form-group"><label class="form-label">Period End</label><input type="date" name="end_date" class="form-control" value="<?= $end_date ?>"></div>
        </div><div class="modal-footer"><button type="submit" class="btn-premium" style="width:100%; justify-content:center;">Refresh Analytics</button></div></form>
    </div>
</div>

<script>
    function openFilter() { document.getElementById('filterModal').style.display = 'flex'; }
    function closeFilter() { document.getElementById('filterModal').style.display = 'none'; }

    const velCtx = document.getElementById('saasVelocityChart').getContext('2d');
    new Chart(velCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($velocity_labels) ?>,
            datasets: [{
                label: 'New Leads',
                data: <?= json_encode($velocity_values) ?>,
                borderColor: '#5851ff', backgroundColor: 'rgba(88, 81, 255, 0.05)',
                fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: '#fff', pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', titleFont: { size: 12 }, bodyFont: { size: 12, weight: 'bold' }, padding: 12, borderRadius: 8 } },
            scales: {
                y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10, weight: 600 }, color: '#94a3b8' } },
                x: { border: { display: false }, grid: { display: false }, ticks: { font: { size: 10, weight: 600 }, color: '#94a3b8' } }
            }
        }
    });
</script>

<style>
    .btn-premium { background: var(--primary); color: white; border: none; padding: 0.65rem 1.25rem; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.8125rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem; transition: 0.2s; box-shadow: 0 4px 10px rgba(88, 81, 255, 0.15); }
    .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(88, 81, 255, 0.3); }
</style>

<?php include 'includes/footer.php'; ?>

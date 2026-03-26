<?php
// index.php
require_once 'config/db.php';
require_once 'includes/auth.php';
if (!isLoggedIn()) { redirect(BASE_URL . 'landing.php'); }

$user_id = $_SESSION['user_id'];
$org_id  = getOrgId();
$role    = $_SESSION['role'];

// --- Time Filters ---
$period = $_GET['period'] ?? 'today';
$start_date = '';
$end_date = date('Y-m-d');

switch ($period) {
    case 'custom':
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = $start_date;
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'lifetime':
        $start_date = '2000-01-01';
        break;
    default: // today
        $start_date = $end_date;
}

$where_period = " AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$where_period_calls = " AND DATE(call_time) BETWEEN '$start_date' AND '$end_date'";
$where_period_followups = " AND next_follow_up_date BETWEEN '$start_date' AND '$end_date'";

// --- Stats Query ---
function getSummaryStats($conn, $org_id, $role, $user_id, $w_leads, $w_calls, $w_follow) {
    $stats = [];
    $owner_where = ($role !== 'admin') ? " AND assigned_to = $user_id" : "";
    $owner_where_calls = ($role !== 'admin') ? " AND executive_id = $user_id" : "";

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id $owner_where $w_leads");
    $stats['total_leads'] = mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN type = 'Incoming' THEN 1 ELSE 0 END) as inbound,
        SUM(CASE WHEN type = 'Outgoing' THEN 1 ELSE 0 END) as outbound,
        SUM(CASE WHEN type = 'Missed' THEN 1 ELSE 0 END) as missed,
        SUM(CASE WHEN duration > 0 THEN 1 ELSE 0 END) as connected,
        SUM(CASE WHEN duration = 0 AND type != 'Missed' THEN 1 ELSE 0 END) as not_connected,
        SUM(duration) as talk_time
        FROM call_logs 
        WHERE (organization_id=$org_id OR executive_id IN (SELECT id FROM users WHERE organization_id=$org_id)) $owner_where_calls $w_calls");
    $row = mysqli_fetch_assoc($r);
    $stats['total_calls'] = $row['total'];
    $stats['inbound'] = $row['inbound'];
    $stats['outbound'] = $row['outbound'];
    $stats['missed'] = $row['missed'];
    $stats['connected'] = $row['connected'];
    $stats['not_connected'] = $row['not_connected'];
    $stats['talk_time'] = (int)$row['talk_time'];
    $stats['avg_duration'] = ($stats['total_calls'] > 0) ? round($stats['talk_time'] / $stats['total_calls']) : 0;

    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT lead_id) c FROM follow_ups WHERE organization_id=$org_id $owner_where_calls $w_follow");
    $stats['total_followups'] = mysqli_fetch_assoc($r)['c'];

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id $owner_where AND status='Converted' $w_leads");
    $stats['converted'] = mysqli_fetch_assoc($r)['c'];

    return $stats;
}

$stats = getSummaryStats($conn, $org_id, $role, $user_id, $where_period, $where_period_calls, $where_period_followups);

// --- Graph Data (Last 7 Days) ---
$graph_labels = [];
$graph_calls = [];
$graph_leads = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $graph_labels[] = date('D', strtotime($d));
    
    $qr = mysqli_query($conn, "SELECT COUNT(*) c FROM call_logs WHERE (organization_id=$org_id OR executive_id IN (SELECT id FROM users WHERE organization_id=$org_id)) " . ($role!=='admin'?" AND executive_id=$user_id":"") . " AND DATE(call_time)='$d'");
    $graph_calls[] = mysqli_fetch_assoc($qr)['c'];

    $qrl = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id=$org_id " . ($role!=='admin'?" AND assigned_to=$user_id":"") . " AND DATE(created_at)='$d'");
    $graph_leads[] = mysqli_fetch_assoc($qrl)['c'];
}

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="max-width: 1400px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.25rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.03em;">Dashboard Overview</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">
                Showing analytics for <span style="background: rgba(88, 81, 255, 0.1); color: var(--primary); padding: 0.2rem 0.6rem; border-radius: 8px; font-weight: 700;">
                    <?= $period=='custom' ? date('d M', strtotime($start_date)).' - '.date('d M', strtotime($end_date)) : ucfirst($period) ?>
                </span>
            </p>
        </div>
        
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <!-- Custom Filter Form (Modern) -->
            <form method="GET" style="background: #fff; padding: 0.5rem 1rem; border-radius: 16px; display: flex; gap: 1rem; align-items: center; border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 0;">
                <input type="hidden" name="period" value="custom">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.6rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">From</span>
                        <input type="date" name="start_date" value="<?= $start_date ?>" style="border: none; padding: 0; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.875rem; color: var(--text-main); background: transparent; outline: none;">
                    </div>
                    <div style="width: 1px; height: 24px; background: var(--border);"></div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-size: 0.6rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">To</span>
                        <input type="date" name="end_date" value="<?= $end_date ?>" style="border: none; padding: 0; font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.875rem; color: var(--text-main); background: transparent; outline: none;">
                    </div>
                </div>
                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 0.6rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">Filter</button>
            </form>

            <div style="display: flex; background: #fff; padding: 0.4rem; border-radius: 16px; border: 1px solid var(--border); gap: 0.25rem; box-shadow: var(--shadow-sm);">
                <a href="?period=today" class="period-btn <?= $period=='today'?'active':'' ?>">Today</a>
                <a href="?period=yesterday" class="period-btn <?= $period=='yesterday'?'active':'' ?>">Yesterday</a>
                <a href="?period=week" class="period-btn <?= $period=='week'?'active':'' ?>">7D</a>
                <a href="?period=month" class="period-btn <?= $period=='month'?'active':'' ?>">30D</a>
                <a href="?period=lifetime" class="period-btn <?= $period=='lifetime'?'active':'' ?>">ALL</a>
            </div>
        </div>
    </div>

    <!-- KPI Row (Modern Bento) -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="dash-card hover-glow" style="padding: 1.5rem; background: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.03; color: var(--primary);"><i class="fas fa-users-rays"></i></div>
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(88, 81, 255, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; font-size: 1.2rem;"><i class="fas fa-users"></i></div>
            <div class="val-outfit" style="font-size: 2.25rem; color: var(--text-main); line-height: 1;"><?= number_format($stats['total_leads']) ?></div>
            <div class="kpi-title" style="font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; margin-top: 0.75rem; opacity: 0.8;">New Leads Acquired</div>
        </div>
        
        <div class="dash-card hover-glow" style="padding: 1.5rem; background: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.03; color: var(--success);"><i class="fas fa-phone-flip"></i></div>
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: var(--success); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; font-size: 1.2rem;"><i class="fas fa-phone-alt"></i></div>
            <div class="val-outfit" style="font-size: 2.25rem; color: var(--text-main); line-height: 1;"><?= number_format($stats['total_calls']) ?></div>
            <div class="kpi-title" style="font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; margin-top: 0.75rem; opacity: 0.8;">Total Call Volume</div>
        </div>

        <div class="dash-card hover-glow" style="padding: 1.5rem; background: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.03; color: var(--warning);"><i class="fas fa-hourglass-half"></i></div>
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: var(--warning); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; font-size: 1.2rem;"><i class="fas fa-clock"></i></div>
            <div class="val-outfit" style="font-size: 1.75rem; color: var(--text-main); line-height: 1.4;"><?= floor($stats['talk_time']/60) ?><small style="font-size: 0.8rem; opacity: 0.5;">m</small> <?= $stats['talk_time']%60 ?><small style="font-size: 0.8rem; opacity: 0.5;">s</small></div>
            <div class="kpi-title" style="font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; margin-top: 0.75rem; opacity: 0.8;">Active Talk Time</div>
        </div>

        <div class="dash-card hover-glow" style="padding: 1.5rem; background: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -10px; right: -10px; font-size: 4rem; opacity: 0.03; color: var(--accent);"><i class="fas fa-gem"></i></div>
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(0, 210, 255, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; font-size: 1.2rem;"><i class="fas fa-trophy"></i></div>
            <div class="val-outfit" style="font-size: 2.25rem; color: var(--text-main); line-height: 1;"><?= number_format($stats['converted']) ?></div>
            <div class="kpi-title" style="font-size: 0.75rem; color: var(--secondary); text-transform: uppercase; margin-top: 0.75rem; opacity: 0.8;">Successful Conversions</div>
        </div>

        <div class="dash-card hover-glow" style="padding: 1.5rem; background: linear-gradient(135deg, var(--primary) 0%, #a855f7 100%); position: relative; overflow: hidden; border: none; color: white;">
            <div style="position: absolute; top: -5px; right: -5px; font-size: 3.5rem; opacity: 0.2;"><i class="fas fa-chart-line"></i></div>
            <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; font-size: 1.2rem;"><i class="fas fa-percent"></i></div>
            <div class="val-outfit" style="font-size: 2.25rem; line-height: 1;"><?= ($stats['total_leads'] > 0) ? round(($stats['converted'] / $stats['total_leads']) * 100, 1) : 0 ?>%</div>
            <div class="kpi-title" style="font-size: 0.75rem; text-transform: uppercase; margin-top: 0.75rem; opacity: 0.9; font-weight: 700;">Conversion Efficiency</div>
        </div>
    </div>

    <!-- Secondary Efficiency Metrics Row -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: white; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
            <div style="color: var(--primary); font-size: 1.25rem;"><i class="fas fa-phone-slash"></i></div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Missed</div>
                <div class="val-outfit" style="font-size: 1.25rem; color: var(--text-main);"><?= number_format($stats['missed']) ?></div>
            </div>
        </div>
        <div style="background: white; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
            <div style="color: var(--success); font-size: 1.25rem;"><i class="fas fa-link"></i></div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Connected</div>
                <div class="val-outfit" style="font-size: 1.25rem; color: var(--text-main);"><?= number_format($stats['connected']) ?></div>
            </div>
        </div>
        <div style="background: white; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
            <div style="color: var(--danger); font-size: 1.25rem;"><i class="fas fa-link-slash"></i></div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Rejected</div>
                <div class="val-outfit" style="font-size: 1.25rem; color: var(--text-main);"><?= number_format($stats['not_connected']) ?></div>
            </div>
        </div>
        <div style="background: white; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
            <div style="color: var(--warning); font-size: 1.25rem;"><i class="fas fa-stopwatch"></i></div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Avg Duration</div>
                <div class="val-outfit" style="font-size: 1.25rem; color: var(--text-main);"><?= $stats['avg_duration'] ?>s</div>
            </div>
        </div>
        <div style="background: white; padding: 1rem 1.25rem; border-radius: 16px; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
            <div style="color: var(--secondary); font-size: 1.25rem;"><i class="fas fa-rotate"></i></div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em;">Total Followups</div>
                <div class="val-outfit" style="font-size: 1.25rem; color: var(--text-main);"><?= number_format($stats['total_followups']) ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem;">
        
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Charts Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Call Frequency Chart -->
                <div class="dash-card" style="background: #fff; padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <div>
                            <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">Call Volume Trend</h4>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Periodic activity analysis</p>
                        </div>
                        <div style="padding: 0.4rem; border-radius: 8px; background: rgba(88, 81, 255, 0.05); color: var(--primary); font-size: 0.8rem;"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div style="height: 250px;"><canvas id="callChart"></canvas></div>
                </div>
                <!-- Lead Generation Chart -->
                <div class="dash-card" style="background: #fff; padding: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <div>
                            <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">Lead Acquisition</h4>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">Growth in lead pipeline</p>
                        </div>
                        <div style="padding: 0.4rem; border-radius: 8px; background: rgba(16, 185, 129, 0.05); color: var(--success); font-size: 0.8rem;"><i class="fas fa-users-viewfinder"></i></div>
                    </div>
                    <div style="height: 250px;"><canvas id="leadChart"></canvas></div>
                </div>
            </div>

            <!-- Team Performance Section (Enhanced for Play Store Style) -->
            <div style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin: 0;">Comprehensive Team Analytics</h4>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">In-depth performance breakdown per executive</p>
                    </div>
                    <div style="background: rgba(88, 81, 255, 0.05); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; color: var(--primary);">
                        <i class="fas fa-users-gear" style="margin-right: 6px;"></i> Active Today
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                    <?php
                    $exec_res = mysqli_query($conn, "SELECT u.id, u.name, u.role,
                                (SELECT COUNT(*) FROM call_logs WHERE executive_id = u.id $where_period_calls) as total_calls,
                                (SELECT SUM(duration) FROM call_logs WHERE executive_id = u.id $where_period_calls) as total_dur,
                                (SELECT COUNT(*) FROM call_logs WHERE executive_id = u.id AND type='Incoming' $where_period_calls) as inc_count,
                                (SELECT SUM(duration) FROM call_logs WHERE executive_id = u.id AND type='Incoming' $where_period_calls) as inc_dur,
                                (SELECT COUNT(*) FROM call_logs WHERE executive_id = u.id AND type='Outgoing' $where_period_calls) as out_count,
                                (SELECT SUM(duration) FROM call_logs WHERE executive_id = u.id AND type='Outgoing' $where_period_calls) as out_dur,
                                (SELECT COUNT(*) FROM call_logs WHERE executive_id = u.id AND type='Missed' $where_period_calls) as missed,
                                (SELECT COUNT(*) FROM call_logs WHERE executive_id = u.id AND duration=0 AND type!='Missed' $where_period_calls) as rejected,
                                (SELECT COUNT(DISTINCT mobile) FROM call_logs WHERE executive_id = u.id $where_period_calls) as unique_cl,
                                (SELECT TIMEDIFF(MAX(call_time), MIN(call_time)) FROM call_logs WHERE executive_id = u.id $where_period_calls) as working_hrs,
                                (SELECT COUNT(*) FROM leads WHERE assigned_to = u.id AND status = 'Converted' $where_period) as conv
                                FROM users u WHERE u.organization_id = $org_id AND u.role = 'executive'");
                    
                    while ($ex = mysqli_fetch_assoc($exec_res)):
                    ?>
                    <div class="dash-card" style="padding: 0; background: #fff; border: 1px solid var(--border); overflow: hidden;">
                        <div style="padding: 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem;"><?= strtoupper(substr($ex['name'], 0, 1)) ?></div>
                                <div>
                                    <div style="font-weight: 800; color: var(--text-main); font-size: 0.95rem; line-height: 1.2;"><?= $ex['name'] ?></div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800;">Executive • Online</div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;"><?= ucfirst($period) ?></div>
                                <div style="font-size: 0.75rem; color: var(--secondary); font-weight: 700;"><?= date('d M, Y') ?></div>
                            </div>
                        </div>

                        <div style="padding: 1.25rem;">
                            <!-- Performance Row 1 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #f8fafc;">
                                <div style="padding: 0.75rem 0;">
                                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);"><?= $ex['total_calls'] ?></div>
                                    <div style="font-size: 0.7rem; color: var(--secondary); font-weight: 700;"><i class="fas fa-headset" style="margin-right: 4px;"></i> Total Calls</div>
                                </div>
                                <div style="padding: 0.75rem 0; text-align: right;">
                                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif;"><?= formatDuration($ex['total_dur']) ?></div>
                                    <div style="font-size: 0.7rem; color: var(--secondary); font-weight: 700;"><i class="fas fa-hourglass-half" style="margin-right: 4px;"></i> Call Duration</div>
                                </div>
                            </div>

                            <!-- Performance Row 2 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #f8fafc;">
                                <div style="padding: 0.75rem 0;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #10b981;"><?= $ex['inc_count'] ?></div>
                                    <div style="font-size: 0.7rem; color: #10b981; font-weight: 800;"><i class="fas fa-arrow-down" style="margin-right: 4px;"></i> Incoming</div>
                                </div>
                                <div style="padding: 0.75rem 0; text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #10b981; font-family: 'Outfit', sans-serif;"><?= formatDuration($ex['inc_dur']) ?></div>
                                    <div style="font-size: 0.7rem; color: #10b981; font-weight: 800;"><i class="fas fa-clock" style="margin-right: 4px;"></i> Incoming Dur.</div>
                                </div>
                            </div>

                            <!-- Performance Row 3 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #f8fafc;">
                                <div style="padding: 0.75rem 0;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #f59e0b;"><?= $ex['out_count'] ?></div>
                                    <div style="font-size: 0.7rem; color: #f59e0b; font-weight: 800;"><i class="fas fa-arrow-up" style="margin-right: 4px;"></i> Outgoing</div>
                                </div>
                                <div style="padding: 0.75rem 0; text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #f59e0b; font-family: 'Outfit', sans-serif;"><?= formatDuration($ex['out_dur']) ?></div>
                                    <div style="font-size: 0.7rem; color: #f59e0b; font-weight: 800;"><i class="fas fa-clock" style="margin-right: 4px;"></i> Outgoing Dur.</div>
                                </div>
                            </div>

                            <!-- Performance Row 4 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #f8fafc;">
                                <div style="padding: 0.75rem 0;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #ef4444;"><?= $ex['missed'] ?></div>
                                    <div style="font-size: 0.7rem; color: #ef4444; font-weight: 800;"><i class="fas fa-phone-slash" style="margin-right: 4px;"></i> Missed</div>
                                </div>
                                <div style="padding: 0.75rem 0; text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #ef4444;"><?= $ex['rejected'] ?></div>
                                    <div style="font-size: 0.7rem; color: #ef4444; font-weight: 800;"><i class="fas fa-ban" style="margin-right: 4px;"></i> Rejected</div>
                                </div>
                            </div>

                            <!-- Performance Row 5 -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid #f8fafc;">
                                <div style="padding: 0.75rem 0;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #475569;"><?= $ex['unique_cl'] ?></div>
                                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 800;"><i class="fas fa-user-check" style="margin-right: 4px;"></i> Unique Clients</div>
                                </div>
                                <div style="padding: 0.75rem 0; text-align: right;">
                                    <div style="font-size: 1.15rem; font-weight: 800; color: #475569;"><?= $ex['working_hrs'] ?: '00:00:00' ?></div>
                                    <div style="font-size: 0.7rem; color: #64748b; font-weight: 800;"><i class="fas fa-business-time" style="margin-right: 4px;"></i> Working Hours</div>
                                </div>
                            </div>
                        </div>

                        <div style="padding: 1rem 1.25rem; background: #fdfdfd; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                             <div style="display: flex; align-items: center; gap: 6px;">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></div>
                                <span style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Productivity Score</span>
                             </div>
                             <span style="font-weight: 800; color: var(--primary); font-size: 0.85rem;"><?= min(100, round(($ex['total_calls']/50)*100)) ?>%</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        </div>

        <!-- Right Side Bento -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Lead Funnel -->
            <div class="dash-card" style="padding: 1.5rem; background: #fff;">
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">Pipeline Funnel</h4>
                <?php
                $lead_statuses = getLeadStatuses($conn, $org_id);
                $total_leads_all = max(1, $stats['total_leads']);
                foreach ($lead_statuses as $st):
                    $rs = mysqli_query($conn, "SELECT COUNT(*) c FROM leads WHERE organization_id = $org_id AND status = '{$st['status_name']}' $where_period");
                    $cnt = (int)mysqli_fetch_assoc($rs)['c'];
                    $pct = ($cnt / $total_leads_all) * 100;
                ?>
                <div style="margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 800; margin-bottom: 0.4rem;">
                        <span style="color: var(--secondary);"><?= $st['status_name'] ?></span>
                        <span style="color: var(--text-main);"><?= $cnt ?></span>
                    </div>
                    <div style="height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                        <div style="width: <?= $pct ?>%; height: 100%; background: <?= $st['color_code'] ?>; border-radius: 4px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Live Indicators -->
            <?php if ($role === 'admin'): ?>
            <div class="dash-card" style="padding: 0; background: #fff;">
                <div style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                    <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">Live Presence</h4>
                    <span id="liveIndicator" style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2); animation: pulse 2s infinite;"></span>
                </div>
                <div id="liveStatusContainer" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <!-- JS Loaded -->
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.75rem; padding: 1rem;">Syncing team status...</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activity Feed -->
            <div class="dash-card" style="padding: 1.5rem; background: #fff;">
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 1rem; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">Recent Interaction</h4>
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <?php
                    $act_where = ($role !== 'admin') ? " AND executive_id = $user_id" : "";
                    $act_res = mysqli_query($conn, "SELECT c.*, u.name as exec_name FROM call_logs c LEFT JOIN users u ON c.executive_id = u.id WHERE (c.organization_id = $org_id OR u.organization_id = $org_id) $act_where ORDER BY c.call_time DESC LIMIT 4");
                    while ($a = mysqli_fetch_assoc($act_res)):
                    ?>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: <?= $a['type'] == 'Incoming' ? '#16a34a' : ($a['type'] == 'Outgoing' ? '#5851ff' : '#dc2626') ?>;">
                             <i class="fas <?= $a['type'] == 'Incoming' ? 'fa-arrow-left-long' : ($a['type'] == 'Outgoing' ? 'fa-arrow-right-long' : 'fa-phone-slash') ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; font-size: 0.8125rem; color: var(--text-main);"><?= $a['mobile'] ?></div>
                            <div style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);"><?= $a['exec_name'] ?> • <?= date('h:i A', strtotime($a['call_time'])) ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <a href="call_logs.php" style="display: block; text-align: center; margin-top: 1.5rem; font-size: 0.75rem; font-weight: 800; color: var(--primary); text-decoration: none; text-transform: uppercase; letter-spacing: 0.05em;">Full Engagement Log</a>
            </div>

        </div>

    </div>

</div>

<style>
.period-btn { padding: 0.5rem 1rem; text-decoration: none; color: var(--secondary); font-size: 0.75rem; font-weight: 800; border-radius: 12px; transition: 0.2s; box-sizing: border-box; border: 1px solid transparent; }
.period-btn:hover { background: #f8fafc; }
.period-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.25); }

@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

#liveStatusContainer::-webkit-scrollbar { display: none; }
</style>

<script>
    // Call Performance Chart (Professional Styling)
    const ctxLive = document.getElementById('callChart');
    if(ctxLive) {
        new Chart(ctxLive, {
            type: 'line',
            data: {
                labels: <?= json_encode($graph_labels) ?>,
                datasets: [{
                    label: 'Call Activity',
                    data: <?= json_encode($graph_calls) ?>,
                    borderColor: '#5851ff',
                    backgroundColor: 'rgba(88, 81, 255, 0.08)',
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#5851ff',
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        border: { display: false }, 
                        grid: { color: 'rgba(0,0,0,0.02)', drawTicks: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 700 }, padding: 10 }
                    },
                    x: { 
                        border: { display: false }, 
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 700 }, padding: 10 }
                    }
                }
            }
        });
    }

    // Lead Generation Chart (Lux Layout)
    const ctxLead = document.getElementById('leadChart');
    if(ctxLead) {
        new Chart(ctxLead, {
            type: 'bar',
            data: {
                labels: <?= json_encode($graph_labels) ?>,
                datasets: [{
                    label: 'Lead Inflow',
                    data: <?= json_encode($graph_leads) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    hoverBackgroundColor: '#10b981',
                    borderRadius: 8,
                    barThickness: 16
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        border: { display: false }, 
                        grid: { color: 'rgba(0,0,0,0.02)', drawTicks: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 700 }, padding: 10 }
                    },
                    x: { 
                        border: { display: false }, 
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10, weight: 700 }, padding: 10 }
                    }
                }
            }
        });
    }

    function refreshLivePresence() {
        const container = document.getElementById('liveStatusContainer');
        if(!container) return;
        
        container.style.opacity = '0.6';
        fetch('api/live_status.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.team) {
                    container.innerHTML = data.team.map(u => `
                        <div style="display: flex; align-items: center; gap: 0.875rem; background: #fff; padding: 0.875rem 1rem; border-radius: 14px; border: 1px solid var(--border);">
                            <div style="position: relative;">
                                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); border: 2px solid ${u.status_color};">
                                    ${u.initials}
                                </div>
                                <div style="position: absolute; bottom: -3px; right: -3px; width: 12px; height: 12px; border-radius: 50%; background: ${u.status_color}; border: 2.5px solid #fff;"></div>
                            </div>
                            <div>
                                <div style="font-family: 'Outfit', sans-serif; font-size: 0.8125rem; font-weight: 700; color: var(--text-main); line-height: 1.2;">${u.name}</div>
                                <div style="font-size: 0.65rem; color: var(--secondary); text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; margin-top: 1px;">${u.status}</div>
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(err => console.error('Presence Sync Fail:', err))
            .finally(() => {
                container.style.opacity = '1';
            });
    }
    
    // Initial load and sync 
    refreshLivePresence();
    setInterval(refreshLivePresence, 20000); 
</script>

<?php include 'includes/footer.php'; ?>

<?php
// calendar.php - Premium Redesign
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

include 'includes/header.php';

$org_id = getOrgId();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Quick Stats fetch for the current month
$start_month = date('Y-m-01');
$end_month = date('Y-m-t');
$where_c = "organization_id = $org_id AND DATE(call_time) BETWEEN '$start_month' AND '$end_month'";
if ($role !== 'admin') $where_c .= " AND executive_id = $user_id";

$stats = [
    'OUTGOING' => 0,
    'INCOMING' => 0,
    'MISSED' => 0
];
$sum_res = mysqli_query($conn, "SELECT type, COUNT(*) as count FROM call_logs WHERE $where_c GROUP BY type");
while($row = mysqli_fetch_assoc($sum_res)) {
    if(strpos($row['type'], 'OUTGOING') !== false) $stats['OUTGOING'] += $row['count'];
    elseif(strpos($row['type'], 'INCOMING') !== false) $stats['INCOMING'] += $row['count'];
    elseif(strpos($row['type'], 'MISSED') !== false) $stats['MISSED'] += $row['count'];
}
?>

<div style="margin-bottom: 2.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.03em; margin: 0;">Timeline Analysis</h2>
            <p style="color: var(--text-muted); font-size: 0.9375rem; margin-top: 0.25rem;">Navigate your communication history and follow-up activities.</p>
        </div>
        
        <div style="display: flex; background: #fff; padding: 0.35rem; border-radius: 14px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <button onclick="switchCalendarView('leads')" id="btn-leads" class="toggle-btn active">Activities</button>
            <button onclick="switchCalendarView('calls')" id="btn-calls" class="toggle-btn">Call History</button>
        </div>
    </div>

    <!-- Stats Bar (Conditional) -->
    <div id="call-stats-bar" style="display: none; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
        <div class="stat-mini-card" style="border-left: 4px solid #3b82f6;">
            <div class="label">Outgoing</div>
            <div class="value"><?= $stats['OUTGOING'] ?> Calls</div>
        </div>
        <div class="stat-mini-card" style="border-left: 4px solid #10b981;">
            <div class="label">Incoming</div>
            <div class="value"><?= $stats['INCOMING'] ?> Calls</div>
        </div>
        <div class="stat-mini-card" style="border-left: 4px solid #ef4444;">
            <div class="label">Missed</div>
            <div class="value"><?= $stats['MISSED'] ?> Calls</div>
        </div>
    </div>

    <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;" id="legend-leads">
        <div class="legend-item"><span style="background: #3b82f6;"></span> Interested</div>
        <div class="legend-item"><span style="background: #10b981;"></span> Converted</div>
        <div class="legend-item"><span style="background: #f59e0b;"></span> Pending</div>
        <div class="legend-item"><span style="background: #6366f1;"></span> Follow-up</div>
        <div class="legend-item"><span style="background: #1e293b;"></span> Creation</div>
    </div>
    <div style="display: none; gap: 1.5rem; flex-wrap: wrap;" id="legend-calls">
        <div class="legend-item"><span style="background: #10b981;"></span> Incoming</div>
        <div class="legend-item"><span style="background: #3b82f6;"></span> Outgoing</div>
        <div class="legend-item"><span style="background: #ef4444;"></span> Missed</div>
        <div class="legend-item"><span style="background: #f59e0b;"></span> Rejected</div>
    </div>
</div>

<div class="card calendar-card" style="padding: 1.5rem; border-radius: 20px; overflow: hidden; border: 1px solid var(--border);">
    <div id="calendar"></div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<style>
    :root {
        --fc-border-color: #f1f5f9;
        --fc-button-bg-color: var(--primary);
        --fc-button-border-color: var(--primary);
        --fc-today-bg-color: #f8fafc;
    }
    .toggle-btn { padding: 0.5rem 1.25rem; border: none; border-radius: 10px; font-weight: 800; font-size: 0.8125rem; cursor: pointer; transition: 0.2s; background: transparent; color: var(--text-muted); }
    .toggle-btn.active { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(88, 81, 255, 0.3); }
    
    .legend-item { display: flex; align-items: center; gap: 0.625rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.02em; }
    .legend-item span { width: 10px; height: 10px; border-radius: 4px; }
    
    .stat-mini-card { background: #fff; padding: 1.25rem 1.5rem; border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
    .stat-mini-card .label { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.25rem; }
    .stat-mini-card .value { font-size: 1.125rem; font-weight: 800; color: var(--text-main); font-family: 'Outfit', sans-serif; }

    #calendar { font-family: 'Inter', sans-serif; }
    .fc .fc-toolbar-title { font-family: 'Outfit', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--text-main); }
    .fc .fc-button { font-weight: 800; font-size: 0.8125rem; border-radius: 10px; padding: 0.4rem 0.8rem; box-shadow: none !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active, .fc .fc-button-primary:not(:disabled):active { background: var(--primary); border: none; }
    .fc-event { cursor: pointer; padding: 4px 6px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; border: none !important; margin: 1px 0; }
    .fc-theme-standard td, .fc-theme-standard th { border-color: #f1f5f9; }
    .fc-col-header-cell-cushion { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; padding: 10px 0 !important; }
</style>

<script>
    let calendar;
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            events: 'api/calendar_events.php?type=leads',
            eventClick: function(info) {
                if (info.event.url && info.event.url !== '#') {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            },
            height: 'auto',
            firstDay: 1,
            dayMaxEvents: 4,
            eventDidMount: function(info) {
                // Potential for tooltips or adding icons
            }
        });
        calendar.render();
    });

    function switchCalendarView(type) {
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + type).classList.add('active');
        
        if (type === 'leads') {
            document.getElementById('legend-leads').style.display = 'flex';
            document.getElementById('legend-calls').style.display = 'none';
            document.getElementById('call-stats-bar').style.display = 'none';
        } else {
            document.getElementById('legend-leads').style.display = 'none';
            document.getElementById('legend-calls').style.display = 'flex';
            document.getElementById('call-stats-bar').style.display = 'grid';
        }

        calendar.removeAllEventSources();
        calendar.addEventSource('api/calendar_events.php?type=' + type);
    }
</script>

<?php include 'includes/footer.php'; ?>

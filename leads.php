<?php
// leads.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$org_id = getOrgId();

// Privacy Settings
$privacy = getOrgPrivacySettings($conn, $org_id);
$should_mask = (int)($privacy['mask_numbers'] ?? 0) === 1 && $role !== 'admin';

function mask_mobile($mobile) {
    if (strlen($mobile) < 5) return $mobile;
    return substr($mobile, 0, -5) . 'XXXXX';
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = "WHERE l.organization_id = $org_id";
if ($role !== 'admin') {
    $where .= " AND assigned_to = $user_id";
}
if ($search) {
    $where .= " AND (l.name LIKE '%$search%' OR l.mobile LIKE '%$search%')";
}
if ($status_filter) {
    $where .= " AND l.status = '$status_filter'";
}

$sql = "SELECT l.*, u.name as executive_name, s.source_name 
        FROM leads l 
        LEFT JOIN users u ON l.assigned_to = u.id 
        LEFT JOIN lead_sources s ON l.source_id = s.id
        $where ORDER BY l.id DESC";
$result = mysqli_query($conn, $sql);

// Fetch Executives for Bulk Assignment
$exec_res = mysqli_query($conn, "SELECT id, name FROM users WHERE organization_id = $org_id ORDER BY name ASC");
$executives = []; while($e = mysqli_fetch_assoc($exec_res)) $executives[] = $e;

include 'includes/header.php';
?>

<style>
    .bulk-bar {
        position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%);
        background: #0f172a; color: #fff; padding: 1rem 2rem; border-radius: 16px;
        display: none; align-items: center; gap: 2rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5);
        z-index: 1000; border: 1px solid rgba(255,255,255,0.1);
        animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    @keyframes slideUp { from { bottom: -5rem; opacity: 0; } to { bottom: 2rem; opacity: 1; } }
    .bulk-count { background: var(--primary); padding: 0.2rem 0.6rem; border-radius: 6px; font-weight: 800; font-size: 0.75rem; }
    .check-cell { width: 40px; padding-left: 1.5rem !important; }
    .bulk-action-btn { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff; padding: 0.6rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 0.5rem; }
    .bulk-action-btn:hover { background: rgba(255,255,255,0.1); border-color: #fff; }
    .bulk-action-btn.danger:hover { background: #ef4444; border-color: #ef4444; }
</style>

<div style="max-width: 1400px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <!-- Hero Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;">Lead Portfolio</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Efficiently manage your growth pipeline</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="bulk_upload.php" style="background: white; border: 1px solid var(--border); color: var(--text-main); padding: 0.75rem 1.25rem; border-radius: 12px; font-size: 0.875rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-file-csv"></i> Import
            </a>
            <a href="lead_add.php" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-size: 0.875rem; font-weight: 800; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2);">
                <i class="fas fa-plus"></i> NEW LEAD
            </a>
        </div>
    </div>

    <!-- Filter Component -->
    <div style="background: #fff; border-radius: 20px; border: 1px solid var(--border); padding: 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <form method="GET" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.8rem; display: block; letter-spacing: 0.05em;">SEARCH IDENTITY</label>
                    <div style="position: relative;">
                        <i class="fas fa-magnifying-glass" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem;"></i>
                        <input type="text" name="search" style="width: 100%; border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1rem 1rem 3rem; font-size: 0.9375rem; transition: 0.2s; background: #fbfcfe;" placeholder="Enter name or mobile number..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.8rem; display: block; letter-spacing: 0.05em;">STAGE / STATUS</label>
                    <div style="position: relative;">
                         <i class="fas fa-filter" style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.9rem; z-index: 1;"></i>
                        <select name="status" style="width: 100%; border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1rem 1rem 3rem; font-size: 0.9375rem; cursor: pointer; background: #fbfcfe; appearance: none; font-weight: 600;">
                            <option value="">All Leads</option>
                            <?php 
                            $st_list = getLeadStatuses($conn, $org_id);
                            foreach($st_list as $st): ?>
                            <option value="<?= $st['status_name'] ?>" <?= $status_filter==$st['status_name']?'selected':'' ?>><?= $st['status_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down" style="position: absolute; right: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.8rem; pointer-events: none;"></i>
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 1rem 2.5rem; border-radius: 14px; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 14px rgba(88, 81, 255, 0.2); font-size: 0.875rem;">APPLY FILTERS</button>
                <?php if ($search || $status_filter): ?>
                <a href="leads.php" style="background: #f1f5f9; color: var(--text-main); border: none; padding: 1rem 1.5rem; border-radius: 14px; font-weight: 700; display: flex; align-items: center; text-decoration: none; font-size: 0.875rem;">RESET ALL</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <form id="bulkForm" action="bulk_handler.php" method="POST">
    <div style="background: #fff; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-sm);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                    <?php if($role === 'admin'): ?>
                    <th class="check-cell">
                        <input type="checkbox" id="checkAll" style="width: 16px; height: 16px; cursor: pointer;">
                    </th>
                    <?php endif; ?>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">CLIENT DETAILS</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">AQUISITION</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">LIFECYCLE STATUS</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">OWNERSHIP</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">TIMELINE</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $m_display = $row['mobile'];
                    if ($should_mask) {
                        $m_display = substr($row['mobile'], 0, -5) . 'XXXXX';
                    }
                ?>
                <tr class="lead-row" style="border-bottom: 1px solid var(--border); transition: 0.2s;">
                    <?php if($role === 'admin'): ?>
                    <td class="check-cell">
                        <input type="checkbox" name="lead_ids[]" value="<?= $row['id'] ?>" class="lead-check" style="width: 16px; height: 16px; cursor: pointer;">
                    </td>
                    <?php endif; ?>
                    <td style="padding: 1rem 1.5rem;">
                        <a href="lead_view.php?id=<?= $row['id']; ?>" style="text-decoration: none; display: block;">
                            <div style="font-weight: 800; color: var(--text-main); font-size: 0.9375rem; letter-spacing: -0.01em;"><?= htmlspecialchars($row['name']); ?></div>
                            <div style="color: var(--primary); font-size: 0.75rem; font-weight: 700; margin-top: 3px; font-family: 'Outfit', sans-serif;">
                                <i class="fas fa-phone-alt" style="font-size: 0.65rem;"></i> <?= $m_display ?>
                            </div>
                        </a>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <span style="background: rgba(0,0,0,0.03); padding: 0.35rem 0.6rem; border-radius: 8px; font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">
                            <?= $row['source_name'] ?: 'ORGANIC' ?>
                        </span>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <span style="background: rgba(<?= $row['status']=='Converted'?'16,185,129':'88,81,255' ?>, 0.1); color: <?= $row['status']=='Converted'?'#10b981':'var(--primary)' ?>; padding: 0.35rem 0.75rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; border: 1px solid transparent;">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.6rem;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.7rem;">
                                <?= strtoupper(substr($row['executive_name'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div style="font-size: 0.8125rem; font-weight: 600; color: var(--text-main);"><?= $row['executive_name'] ?? '<span style="color:var(--danger)">Unassigned</span>'; ?></div>
                        </div>
                    </td>
                    <td style="padding: 1rem 1.5rem; color: var(--text-muted); font-size: 0.8125rem; font-weight: 600;">
                        <?= date('d M, Y', strtotime($row['created_at'])); ?>
                    </td>
                    <td style="padding: 1rem 1.5rem; text-align: right;">
                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                        <a href="https://wa.me/<?= $row['mobile']; ?>" target="_blank" class="status-badge" style="background: #dcfce7; color: #16a34a; text-decoration: none; border: none; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="fab fa-whatsapp"></i></a>
                        <a href="tel:<?= $row['mobile']; ?>" class="status-badge" style="background: #eef2ff; color: #6366f1; text-decoration: none; border: none; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 0.85rem;"><i class="fas fa-phone-alt"></i></a>
                            <a href="lead_view.php?id=<?= $row['id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--text-main); border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'" onmouseout="this.style.background='#f1f5f9'; this.style.color='var(--text-main)'">
                                <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
                            </a>
                            <a href="lead_edit.php?id=<?= $row['id']; ?>" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: var(--secondary); border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='var(--warning)'; this.style.color='white'" onmouseout="this.style.background='#f1f5f9'; this.style.color='var(--secondary)'">
                                <i class="fas fa-edit" style="font-size: 0.8rem;"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) === 0): ?>
                <tr>
                    <td colspan="6" style="padding: 6rem 0; text-align: center;">
                        <div style="font-size: 3rem; color: var(--border); margin-bottom: 1.5rem;"><i class="fas fa-users-slash"></i></div>
                        <h4 style="font-family: 'Outfit', sans-serif; font-size: 1.125rem; font-weight: 800; color: var(--text-main);">No leads found</h4>
                        <p style="color: var(--text-muted); font-size: 0.875rem;">Adjust your filters or try a different search keyword.</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if($role === 'admin'): ?>
<div class="bulk-bar" id="bulkBar">
    <div style="display: flex; align-items: center; gap: 1rem; border-right: 1px solid rgba(255,255,255,0.1); padding-right: 2rem;">
        <span class="bulk-count" id="selectedCount">0</span>
        <span style="font-size: 0.8125rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Leads Selected</span>
    </div>
    
    <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="position: relative; display: flex; align-items: center;">
            <i class="fas fa-user-tag" style="position: absolute; left: 1rem; color: #94a3b8; font-size: 0.8rem; z-index: 1;"></i>
            <select name="executive_id" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 0.75rem 1rem 0.75rem 2.5rem; border-radius: 12px; font-size: 0.8125rem; outline: none; width: 220px; font-weight: 600; cursor: pointer;">
                <option value="" style="color: #1e293b;">Choose Executive...</option>
                <?php foreach($executives as $e): ?>
                    <option value="<?= $e['id'] ?>" style="color: #1e293b;"><?= htmlspecialchars($e['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="action" value="assign" class="bulk-action-btn" style="background: var(--primary); border: none; padding: 0.75rem 1.25rem;">
            <i class="fas fa-user-plus"></i> ASSIGN
        </button>

        <button type="submit" name="action" value="delete" class="bulk-action-btn danger" onclick="return confirm('CRITICAL: These leads and their entire history will be permanently erased. Proceed?')" style="border: 1px solid rgba(239, 68, 68, 0.4); padding: 0.75rem 1rem;">
            <i class="fas fa-trash-can"></i>
        </button>

        <div style="width: 1px; height: 24px; background: rgba(255,255,255,0.1); margin: 0 0.5rem;"></div>

        <button type="button" onclick="clearSelection()" class="bulk-action-btn" style="border: none; color: #94a3b8; font-size: 0.75rem;">
            <i class="fas fa-times"></i> CANCEL
        </button>
    </div>
</div>
</form>

<script>
    const checkAll = document.getElementById('checkAll');
    const leadChecks = document.querySelectorAll('.lead-check');
    const bulkBar = document.getElementById('bulkBar');
    const selectedCount = document.getElementById('selectedCount');

    function updateBulkBar() {
        const checkedCount = document.querySelectorAll('.lead-check:checked').length;
        selectedCount.innerText = checkedCount;
        bulkBar.style.display = checkedCount > 0 ? 'flex' : 'none';
        
        // Highlight rows
        document.querySelectorAll('.lead-row').forEach(row => {
            const cb = row.querySelector('.lead-check');
            if(cb && cb.checked) {
                row.style.background = 'rgba(88, 81, 255, 0.03)';
            } else {
                row.style.background = 'white';
            }
        });
    }

    function clearSelection() {
        checkAll.checked = false;
        leadChecks.forEach(c => c.checked = false);
        updateBulkBar();
    }

    checkAll.addEventListener('change', () => {
        leadChecks.forEach(c => c.checked = checkAll.checked);
        updateBulkBar();
    });

    leadChecks.forEach(c => {
        c.addEventListener('change', updateBulkBar);
    });
</script>
<?php endif; ?>

</form>

<?php include 'includes/footer.php'; ?>

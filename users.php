<?php
// users.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$message = '';
$error = '';
$org_id = getOrgId();

// Handle Add/Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $allow_ss = isset($_POST['allow_screenshot']) ? 1 : 0;

    if ($user_id > 0) {
        // Edit logic
        $update_fields = "name = '$name', mobile = '$mobile', role = '$role', allow_screenshot = $allow_ss";
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $update_fields .= ", password = '$pass'";
        }
        $sql = "UPDATE users SET $update_fields WHERE id = $user_id AND organization_id = $org_id";
    } else {
        // Add logic
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (organization_id, name, mobile, password, role, allow_screenshot) 
                VALUES ($org_id, '$name', '$mobile', '$pass', '$role', $allow_ss)";
    }

    if (mysqli_query($conn, $sql)) {
        $message = "Executive " . ($user_id > 0 ? "updated" : "onboarded") . " successfully!";
    } else {
        $error = "System Error: " . mysqli_error($conn);
    }
}

$sql = "SELECT id, name, mobile, role, status, allow_screenshot, created_at FROM users WHERE organization_id = $org_id ORDER BY role ASC, name ASC";
$result = mysqli_query($conn, $sql);
$user_rows = []; while($r = mysqli_fetch_assoc($result)) $user_rows[] = $r;

include 'includes/header.php';
?>

<div style="max-width: 1400px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;">Team Command</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Manage access, roles, and security permissions for your sales staff</p>
        </div>
        <button onclick="openUserModal()" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2);">
            <i class="fas fa-user-plus"></i> ONBOARD EXECUTIVE
        </button>
    </div>

    <?php if ($message): ?>
        <div style="background: #f0fdf4; color: #16a34a; padding: 1rem 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #dcfce7; font-weight: 700;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?= $message ?>
        </div>
    <?php endif; ?>

    <div style="background: #fff; border-radius: 20px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-sm);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">MEMBER</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">CONTACT INFO</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">ACCESS ROLE</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: left; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">PRIVACY POLICY</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: center; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">STATUS</th>
                    <th style="padding: 1.25rem 1.5rem; text-align: right; font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_rows as $u): ?>
                <tr style="border-bottom: 1px solid var(--border); transition: 0.15s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='white'">
                    <td style="padding: 1rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.875rem;">
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(88, 81, 255, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.8rem;"><?= strtoupper(substr($u['name'], 0, 1)) ?></div>
                            <div style="font-weight: 800; color: var(--text-main); font-size: 0.9375rem;"><?= htmlspecialchars($u['name']) ?></div>
                        </div>
                    </td>
                    <td style="padding: 1rem 1.5rem; color: var(--text-main); font-weight: 600; font-size: 0.875rem;"><?= $u['mobile'] ?></td>
                    <td style="padding: 1rem 1.5rem;">
                        <span style="background: <?= $u['role']=='admin'?'#fff7ed':'#f0fdf4' ?>; color: <?= $u['role']=='admin'?'#ea580c':'#16a34a' ?>; padding: 0.35rem 0.65rem; border-radius: 8px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; border: 1px solid transparent;">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <td style="padding: 1rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: <?= $u['allow_screenshot']?'#10b981':'#94a3b8' ?>;">
                            <i class="fas <?= $u['allow_screenshot']?'fa-shield-check':'fa-eye-slash' ?>"></i>
                            <?= $u['allow_screenshot'] ? 'Screenshots Allowed' : 'Anti-Spyware Active' ?>
                        </div>
                    </td>
                    <td style="padding: 1rem 1.5rem; text-align: center;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15);"></span>
                    </td>
                    <td style="padding: 1rem 1.5rem; text-align: right;">
                        <button onclick='editUser(<?= json_encode($u) ?>)' style="background: #f1f5f9; color: var(--text-main); border: none; padding: 0.5rem 0.75rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'" onmouseout="this.style.background='#f1f5f9'; this.style.color='var(--text-main)'">
                            <i class="fas fa-gear" style="margin-right: 4px;"></i> CONFIGURE
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Elite User Modal -->
<div id="userModal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp" style="width: 460px;">
        <h3 id="modalTitle" style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 2rem;">ONBOARDING PLAN</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Legal Name</label>
                <input type="text" name="name" id="edit_name" required class="form-input-elite" style="padding: 0.75rem 1rem; margin-top: 5px;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Mobile Identification</label>
                <input type="text" name="mobile" id="edit_mobile" required class="form-input-elite" style="padding: 0.75rem 1rem; margin-top: 5px;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Auth Password (Min 8 Chars)</label>
                <input type="password" name="password" id="edit_password" class="form-input-elite" style="padding: 0.75rem 1rem; margin-top: 5px;" placeholder="Keep empty to preserve original">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">Access Privileges</label>
                <select name="role" id="edit_role" class="form-input-elite" style="padding: 0.75rem 1rem; margin-top: 5px; cursor: pointer;">
                    <option value="executive">Sales Executive (Field Ops)</option>
                    <option value="admin">System Administrator (Control)</option>
                </select>
            </div>

            <div style="background: #fbfcfe; padding: 1rem; border-radius: 14px; border: 1px solid var(--border); margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem;">
                <input type="checkbox" name="allow_screenshot" id="edit_ss" value="1" style="width: 20px; height: 20px; accent-color: var(--primary);">
                <label for="edit_ss" style="font-size: 0.8125rem; font-weight: 800; color: var(--text-main); margin: 0; cursor: pointer;">Allow App Screenshots</label>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="elite-btn" style="flex: 1; padding: 0.875rem;">CONFIRM ACCESS</button>
                <button type="button" onclick="closeUserModal()" style="background: #f1f5f9; color: var(--text-main); border: none; padding: 0.875rem 1.25rem; border-radius: 12px; font-weight: 800; cursor: pointer;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; display: flex; }
.modal-content { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
.form-input-elite { width: 100%; border: 1px solid var(--border); border-radius: 12px; outline: none; transition: 0.2s; box-sizing: border-box; }
.form-input-elite:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(88, 81, 255, 0.1); }
.elite-btn { background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2); }
.elite-btn:hover { background: #463fed; transform: translateY(-2px); }
</style>

<script>
function openUserModal() {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'EXPAND TEAM';
    document.getElementById('edit_user_id').value = '';
    document.getElementById('edit_name').value = '';
    document.getElementById('edit_mobile').value = '';
    document.getElementById('edit_password').required = true;
    document.getElementById('edit_role').value = 'executive';
    document.getElementById('edit_ss').checked = true;
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

function editUser(user) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = 'RECONFIGURE ACCESS';
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_mobile').value = user.mobile;
    document.getElementById('edit_password').required = false;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_ss').checked = (user.allow_screenshot == '1');
}

window.onclick = function(event) {
    if (event.target.className === 'modal-overlay') {
        closeUserModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>

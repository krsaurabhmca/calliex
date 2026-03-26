<?php
// profile.php - User & Organization Profile
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$org_id = getOrgId();
$role = $_SESSION['role'];
$message = ''; $error = '';

// Fetch Current User
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));
// Fetch Org (if admin)
$org = ($role === 'admin') ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM organizations WHERE id = $org_id")) : null;

// 1. Update Profile Info
if(isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    
    if(mysqli_query($conn, "UPDATE users SET name = '$name', mobile = '$mobile' WHERE id = $user_id")) {
        $_SESSION['name'] = $name;
        $message = "Personal profile updated successfully.";
        $user['name'] = $name; $user['mobile'] = $mobile;
    } else {
        $error = "Error updating profile.";
    }
}

// 2. Change Password
if(isset($_POST['change_password'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if(password_verify($old, $user['password'])) {
        if($new === $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            if(mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $user_id")) {
                $message = "Password changed successfully.";
            } else {
                $error = "Database error updating password.";
            }
        } else {
            $error = "Passwords do not match or too short (min 6 chars).";
        }
    } else {
        $error = "Incorrect current password.";
    }
}

// 3. Update Org Name (Admin only)
if(isset($_POST['update_org']) && $role === 'admin') {
    $org_name = mysqli_real_escape_string($conn, $_POST['org_name']);
    if(mysqli_query($conn, "UPDATE organizations SET name = '$org_name' WHERE id = $org_id")) {
        $message = "Organization settings saved.";
        $org['name'] = $org_name;
    }
}

include 'includes/header.php';
?>

<div class="page-header" style="margin-bottom: 2.5rem;">
    <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.25rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.04em;">Identity & Privacy</h1>
    <p style="color: #64748b; font-size: 0.9375rem; margin-top: 0.25rem;">Manage your individual account and organizational presence.</p>
</div>

<?php if($message): ?>
    <div style="background: #ecfdf5; color: #059669; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; border: 1px solid #10b981;">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
<?php endif; ?>
<?php if($error): ?>
    <div style="background: #fef2f2; color: #dc2626; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; border: 1px solid #fecaca;">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Personal Profile -->
    <div class="card" style="padding: 2rem; border-radius: 24px;">
        <h3 style="font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; color: #1e293b;">
            <i class="fas fa-user-circle" style="color: var(--primary);"></i> Personal Profile
        </h3>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= $user['name'] ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mobile Identification</label>
                <input type="text" name="mobile" class="form-control" value="<?= $user['mobile'] ?>" required maxlength="10">
            </div>
            <button type="submit" name="update_profile" class="btn-premium" style="width: 100%; justify-content: center;">Save Profile</button>
        </form>

        <hr style="margin: 2.5rem 0; opacity: 0.1;">

        <h3 style="font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; color: #1e293b;">
            <i class="fas fa-key" style="color: #f59e0b;"></i> Security Shield
        </h3>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="old_password" class="form-control" required placeholder="••••••••">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required placeholder="Min 6 chars">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                </div>
            </div>
            <button type="submit" name="change_password" class="btn-premium" style="width: 100%; justify-content: center; background: #0f172a; box-shadow: none;">Change Secret Key</button>
        </form>
    </div>

    <!-- Org Profile (if admin) -->
    <?php if($role === 'admin'): ?>
    <div>
        <div class="card" style="padding: 2rem; border-radius: 24px; margin-bottom: 2rem; border-left: 4px solid #8b5cf6;">
            <h3 style="font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px; color: #1e293b;">
                <i class="fas fa-building" style="color: #8b5cf6;"></i> Organization Identity
            </h3>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Organization Display Name</label>
                    <input type="text" name="org_name" class="form-control" value="<?= $org['name'] ?>" required>
                    <small style="color: #64748b; margin-top: 4px; display: block;">This will appear on internal reports and communication.</small>
                </div>
                <button type="submit" name="update_org" class="btn-premium" style="width: 100%; justify-content: center; background: #8b5cf6;">Update Organization</button>
            </form>
        </div>

        <div class="card" style="padding: 2rem; border-radius: 24px; background: rgba(88, 81, 255, 0.03); border: 1px dashed rgba(88, 81, 255, 0.2);">
            <h4 style="font-weight: 800; color: #1e293b; margin-bottom: 1rem;">Quick Stats</h4>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: #64748b;">Member since:</span>
                    <span style="font-weight: 700; color: #1e293b;"><?= date('M Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: #64748b;">Current Role:</span>
                    <span style="font-weight: 800; text-transform: uppercase; color: var(--primary); font-size: 0.75rem; letter-spacing: 0.05em; background: rgba(88, 81, 255, 0.1); padding: 2px 8px; border-radius: 4px;"><?= $role ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .btn-premium { background: var(--primary); color: white; border: none; padding: 1rem; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.9375rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem; transition: 0.2s; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2); }
    .btn-premium:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(88, 81, 255, 0.35); }
</style>

<?php include 'includes/footer.php'; ?>

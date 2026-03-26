<?php
// plans.php - SaaS Billing & Subscriptions (Updated with Offer Prices)
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

$org_id = getOrgId();
$role = $_SESSION['role'];

// Fetch Org Details
$org_res = mysqli_query($conn, "SELECT * FROM organizations WHERE id = $org_id");
$org = mysqli_fetch_assoc($org_res);

// Plans Logic
$plans = [
    'Trial' => [
        'id' => 'Trial', 
        'name' => 'Trial / Free Plan', 
        'price' => 0, 
        'regular_price' => 0,
        'features' => ['30 Days Free Access', 'Up to 3 Team Users', 'All Basic CRM Features', 'Shared Cloud Sync']
    ],
    'Pro' => [
        'id' => 'Pro', 
        'name' => 'Small Business Growth', 
        'price' => 199, 
        'regular_price' => 350,
        'billing' => 'Billed Annually',
        'features' => ['Unlimited Leads', 'Unlimited Users', 'Advanced Business Analytics', 'Auto-WhatsApp API', 'SaaS Reports Access']
    ],
    'Enterprise' => [
        'id' => 'Enterprise', 
        'name' => 'Corporate Elite', 
        'price' => 'Contact Us', 
        'regular_price' => null,
        'billing' => 'For Large Teams',
        'features' => ['Custom Branding', 'API Webhook Access', 'Dedicated Support', 'Daily Backups', 'SLA Continuity']
    ]
];

// Handle Activation
if (isset($_POST['activate_plan']) && $role === 'admin') {
    $plan_key = mysqli_real_escape_string($conn, $_POST['plan_type']);
    $expiry = date('Y-m-d', strtotime('+30 days'));
    mysqli_query($conn, "UPDATE organizations SET plan_type = '$plan_key', plan_status = 'Active', expiry_date = '$expiry' WHERE id = $org_id");
    header("Location: plans.php?success=1");
    exit();
}

include 'includes/header.php';
?>

<div class="page-header" style="margin-bottom: 3.5rem; text-align: center;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 2.75rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.05em;">Scale Your Growth</h1>
        <p style="color: #64748b; font-size: 1.1rem; margin-top: 0.5rem; max-width: 600px; margin-inline: auto;">Choose the right engine for your sales organization. All prices are in INR/user/month.</p>
    </div>
</div>

<?php if(isset($_GET['success'])): ?>
<div style="background: #ecfdf5; color: #059669; padding: 1.25rem; border-radius: 16px; margin-bottom: 2.5rem; text-align: center; font-weight: 800; border: 1px solid #10b981; animation: slideDown 0.4s ease-out;">
    <i class="fas fa-check-circle"></i> Plan activation successful! Your account is now upgraded.
</div>
<?php endif; ?>

<!-- Subscription Snapshot -->
<div class="card" style="padding: 2.5rem; border: none; background: #0f172a; color: white; margin-bottom: 4rem; display: flex; align-items: center; justify-content: space-between; border-radius: 32px; box-shadow: 0 30px 60px rgba(0,0,0,0.2);">
    <div>
        <div style="font-size: 0.75rem; font-weight: 800; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.75rem;">Current Organization Identity</div>
        <h2 style="font-family: 'Outfit', sans-serif; font-size: 2.5rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 15px;">
            <?= $org['plan_type'] ?? 'Trial' ?> Plan
            <span style="font-size: 0.8rem; background: #10b981; color: white; padding: 6px 16px; border-radius: 30px; letter-spacing: 0.05em;">ACTIVE</span>
        </h2>
        <div style="margin-top: 1rem; color: #94a3b8; font-size: 0.9375rem; display: flex; gap: 20px;">
            <span><i class="fas fa-calendar-alt" style="margin-right: 8px;"></i> Renewal: <?= $org['expiry_date'] ? date('d M, Y', strtotime($org['expiry_date'])) : '30-Day Free Trial' ?></span>
            <span><i class="fas fa-info-circle" style="margin-right: 8px;"></i> Next Cycle ID: #<?= strtoupper(substr(md5($org_id), 0, 8)) ?></span>
        </div>
    </div>
    <div style="text-align: right;">
        <div style="width: 60px; height: 60px; border-radius: 16px; background: rgba(56, 189, 248, 0.1); color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-inline-start: auto; margin-bottom: 1rem;">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div style="font-size: 1rem; font-weight: 800; color: white;">#SAAS-<?= str_pad($org_id, 5, '0', STR_PAD_LEFT) ?></div>
        <div style="font-size: 0.7rem; color: #64748b; margin-top: 4px; font-weight: 700;">Verified Org ID</div>
    </div>
</div>

<!-- Plan Tiers -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2.5rem;">
    <?php foreach($plans as $id => $p): ?>
    <div class="card" style="padding: 3rem 2.25rem; border-radius: 32px; position: relative; transition: transform 0.3s; border: <?= ($org['plan_type'] ?? 'Trial') == $id ? '2px solid var(--primary)' : '1px solid #f1f5f9' ?>">
        
        <?php if($id == 'Pro'): ?>
        <div style="position: absolute; top: -14px; right: 2rem; background: #f59e0b; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase;">LIMITED TIME OFFER</div>
        <?php endif; ?>

        <div style="margin-bottom: 2.5rem;">
            <div style="font-size: 0.8125rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;"><?= $p['name'] ?></div>
            
            <?php if($p['price'] === 'Contact Us'): ?>
                <div style="font-family: 'Outfit', sans-serif; font-size: 2.25rem; font-weight: 800; color: #0f172a;">Contact Us</div>
                <div style="font-size: 0.8125rem; color: #64748b; font-weight: 700; margin-top: 4px;"><?= $p['billing'] ?></div>
            <?php else: ?>
                <div style="display: flex; align-items: baseline; gap: 8px;">
                    <div style="font-family: 'Outfit', sans-serif; font-size: 3.25rem; font-weight: 800; color: #0f172a;">₹<?= $p['price'] ?></div>
                    <div style="font-size: 0.9375rem; color: #94a3b8; font-weight: 600;">/user/mo</div>
                </div>
                <div style="font-size: 0.8125rem; color: #64748b; font-weight: 700; margin-top: 4px;"><?= $p['billing'] ?? 'No Contract' ?></div>
                <?php if($p['regular_price'] > 0 && $p['regular_price'] > $p['price']): ?>
                <div style="font-size: 0.875rem; color: #f43f5e; font-weight: 700; margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                    <span style="text-decoration: line-through; opacity: 0.5;">₹<?= $p['regular_price'] ?></span>
                    <span>Regular Price</span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <ul style="list-style: none; padding: 0; margin: 0 0 3rem 0; display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach($p['features'] as $f): ?>
            <li style="display: flex; align-items: flex-start; gap: 12px; font-size: 1rem; color: #475569; font-weight: 500;">
                <i class="fas fa-circle-check" style="color: <?= ($org['plan_type'] ?? 'Trial') == $id ? '#10b981' : '#cbd5e1' ?>; margin-top: 3px;"></i>
                <span><?= $f ?></span>
            </li>
            <?php endforeach; ?>
        </ul>

        <form method="POST">
            <input type="hidden" name="plan_type" value="<?= $id ?>">
            <button type="submit" name="activate_plan" 
                <?= ($org['plan_type'] ?? 'Trial') == $id ? 'disabled' : '' ?>
                class="btn-activate" 
                style="<?= ($org['plan_type'] ?? 'Trial') == $id ? 'background: #f1f5f9; color: #94a3b8; cursor: not-allowed; box-shadow: none;' : '' ?>">
                <?= ($org['plan_type'] ?? 'Trial') == $id ? 'Currently Active' : ($id == 'Enterprise' ? 'Contact Success' : 'Choose Plan') ?>
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<style>
    @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    .btn-activate { width: 100%; padding: 1.125rem; border-radius: 16px; border: none; background: var(--primary); color: white; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1rem; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 20px rgba(88, 81, 255, 0.2); display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-activate:hover:not(:disabled) { transform: translateY(-4px); box-shadow: 0 15px 30px rgba(88, 81, 255, 0.3); }
    .card:hover { transform: translateY(-10px); }
</style>

<?php include 'includes/footer.php'; ?>

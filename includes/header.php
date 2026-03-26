<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
checkAuth();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$org_id = getOrgId();

updateLiveStatus($conn, $user_id); // Update online status on every page load
$today = date('Y-m-d');

// Fetch notification count (Today's follow-ups)
$notif_sql = "SELECT COUNT(DISTINCT f.lead_id) as count FROM follow_ups f JOIN leads l ON f.lead_id = l.id WHERE f.next_follow_up_date = '$today'";
if ($role !== 'admin') {
    $notif_sql .= " AND l.assigned_to = $user_id";
}
$notif_res = mysqli_query($conn, $notif_sql);
$notif_count = mysqli_fetch_assoc($notif_res)['count'];

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calldesk CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div style="padding: 0.5rem 0.75rem 2rem 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.875rem; margin-bottom: 0.5rem;">
                    <div style="background: linear-gradient(135deg, var(--primary) 0%, #a855f7 100%); color: white; width: 42px; height: 42px; border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(88, 81, 255, 0.4);">
                        <i class="fas fa-headset" style="font-size: 1.1rem;"></i>
                    </div>
                    <div style="display: flex; flex-direction: column; line-height: 1.1;">
                        <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.5rem; color: var(--text-main); letter-spacing: -0.03em;">Calldesk</span>
                        <span style="font-size: 0.65rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.8;">CRM SYSTEM</span>
                    </div>
                </div>
                <?php if (isset($_SESSION['organization_name'])): ?>
                <div style="background: rgba(88, 81, 255, 0.04); padding: 0.625rem 0.875rem; border-radius: 12px; border: 1px solid rgba(88, 81, 255, 0.1); margin-top: 1.25rem; display: flex; align-items: center; gap: 0.625rem;">
                    <i class="fas fa-shield-halved" style="font-size: 0.75rem; color: var(--primary);"></i>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo $_SESSION['organization_name']; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <nav>
                <div class="nav-section-label">Main Menu</div>
                <a href="<?php echo BASE_URL; ?>index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> <span>Overview</span>
                </a>
                <a href="<?php echo BASE_URL; ?>leads.php" class="nav-link <?php echo ($current_page == 'leads.php' || $current_page == 'lead_view.php' || $current_page == 'lead_add.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <span>Leads</span>
                </a>
                <a href="<?php echo BASE_URL; ?>followups.php" class="nav-link <?php echo $current_page == 'followups.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i> <span>Tasks</span>
                </a>
                <a href="<?php echo BASE_URL; ?>calendar.php" class="nav-link <?php echo $current_page == 'calendar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> <span>Calendar</span>
                </a>
                <a href="<?php echo BASE_URL; ?>call_logs.php" class="nav-link <?php echo $current_page == 'call_logs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-phone-volume"></i> <span>Call Logs</span>
                </a>
                <a href="<?php echo BASE_URL; ?>recordings.php" class="nav-link <?php echo $current_page == 'recordings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-microphone-alt"></i> <span>Recordings</span>
                </a>

                <div class="nav-section-label">Communication</div>
                <a href="<?php echo BASE_URL; ?>messages.php" class="nav-link <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fab fa-whatsapp"></i> <span>WA Templates</span>
                </a>
                
                <?php if (isAdmin()): ?>
                <div class="nav-section-label">Administration</div>
                <a href="<?php echo BASE_URL; ?>geography.php" class="nav-link <?php echo $current_page == 'geography.php' ? 'active' : ''; ?>">
                    <i class="fas fa-location-dot"></i> <span>Geography</span>
                </a>
                <a href="<?php echo BASE_URL; ?>custom_fields.php" class="nav-link <?php echo $current_page == 'custom_fields.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list-check"></i> <span>Custom Fields</span>
                </a>
                <a href="<?php echo BASE_URL; ?>sources.php" class="nav-link <?php echo $current_page == 'sources.php' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i> <span>Lead Sources</span>
                </a>
                <a href="<?php echo BASE_URL; ?>users.php" class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-gear"></i> <span>Team Access</span>
                </a>
                <a href="<?php echo BASE_URL; ?>reports.php" class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> <span>Reports</span>
                </a>
                <a href="<?php echo BASE_URL; ?>plans.php" class="nav-link <?php echo $current_page == 'plans.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> <span>Plans & Billing</span>
                </a>
                <a href="<?php echo BASE_URL; ?>settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </a>
                <a href="<?php echo BASE_URL; ?>docs.php" class="nav-link <?php echo $current_page == 'docs.php' ? 'active' : ''; ?>">
                    <i class="fas fa-code"></i> <span>Developer API</span>
                </a>
                
                <div class="nav-section-label">Support</div>
                <a href="javascript:void(0)" onclick="openWizard()" class="nav-link" style="background: rgba(16, 185, 129, 0.05); color: #059669; border: 1px dashed rgba(16, 185, 129, 0.3); margin-top: 10px;">
                    <i class="fas fa-magic"></i> <span style="font-weight: 800;">Getting Started</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-link">
                    <i class="fas fa-power-off"></i> <span>Sign out</span>
                </a>
            </div>
        </aside>

        <!-- Onboarding Wizard Modal (Compact) -->
        <div id="onboardingWizard" class="wizard-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
            <div class="wizard-content" style="background: white; width: 440px; border-radius: 24px; overflow: hidden; box-shadow: 0 40px 60px rgba(0,0,0,0.15); position: relative; animation: slideUp 0.3s ease-out;">
                <button onclick="closeWizard()" style="position: absolute; top: 16px; right: 16px; border: none; background: rgba(0,0,0,0.05); width: 28px; height: 28px; border-radius: 50%; cursor: pointer; color: #64748b; z-index: 10; font-size: 0.8rem;"><i class="fas fa-times"></i></button>
                
                <div style="background: var(--primary); padding: 32px 32px 48px; color: white; position: relative; text-align: center;">
                    <div id="wizardIcon" style="font-size: 2.5rem; margin-bottom: 12px;"><i class="fas fa-bolt"></i></div>
                    <h2 id="wizardTitle" style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.02em;">Quick Start</h2>
                    <p id="wizardSub" style="font-size: 0.875rem; opacity: 0.8; font-weight: 500;">Set up your CRM in 60 seconds.</p>
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: rgba(255,255,255,0.1);">
                        <div id="wizardProgress" style="width: 20%; height: 100%; background: #fff; transition: width 0.3s ease;"></div>
                    </div>
                </div>

                <div style="padding: 32px; min-height: 240px; text-align: left;">
                    <div id="step1" class="wizard-step">
                        <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 12px; font-size: 1rem;">Why Calldesk?</h4>
                        <p style="color: var(--text-muted); line-height: 1.5; font-size: 0.8125rem;">Manual entry is slow. Calldesk captures every sales call from your phone and puts it on this dashboard automatically. Never lose a lead again.</p>
                    </div>

                    <div id="step2" class="wizard-step" style="display: none;">
                        <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 12px; font-size: 1rem;">1. Install Mobile App</h4>
                        <p style="color: var(--text-muted); line-height: 1.5; font-size: 0.8125rem;">Download the app on your team's Android phones. Log in once, and you're connected.</p>
                    </div>

                    <div id="step3" class="wizard-step" style="display: none;">
                        <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 12px; font-size: 1rem;">2. One-Tap Sync</h4>
                        <p style="color: var(--text-muted); line-height: 1.5; font-size: 0.8125rem;">After calling clients, just open the app and tap "Sync". Your call history moves here instantly.</p>
                    </div>

                    <div id="step4" class="wizard-step" style="display: none;">
                        <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 12px; font-size: 1rem;">3. Auto-WhatsApp</h4>
                        <p style="color: var(--text-muted); line-height: 1.5; font-size: 0.8125rem;">Send automated "Thank You" or "Welcome" messages to new leads directly from your own WhatsApp.</p>
                    </div>

                    <div id="step5" class="wizard-step" style="display: none;">
                        <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 12px; font-size: 1rem;">Ready to Grow?</h4>
                        <p style="color: var(--text-muted); line-height: 1.5; font-size: 0.8125rem;">You're all set. Start adding leads and see your team's performance improve today!</p>
                    </div>
                </div>

                <div style="padding: 20px 32px; background: #fcfcfd; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <button id="prevBtn" onclick="prevStep()" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e2e8f0; background: white; font-weight: 800; color: #64748b; cursor: pointer; font-size: 0.75rem; visibility: hidden;">Back</button>
                    <button id="nextBtn" onclick="nextStep()" style="padding: 10px 24px; border-radius: 10px; border: none; background: var(--primary); color: white; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.75rem;">Next Step <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
        </div>

        <script>
            let currentStep = 1;
            const titles = ["Welcome to Calldesk!", "Mobile Setup", "Sync Data", "Auto-WhatsApp", "All Set!"];
            const subs = ["Automate your sales flow in 60s.", "Install app on Android phones.", "Sync logs with one simple tap.", "Auto-reply to leads instantly.", "Start capturing your leads!"];
            const icons = ["<i class='fas fa-rocket'></i>", "<i class='fas fa-mobile'></i>", "<i class='fas fa-sync'></i>", "<i class='fas fa-message'></i>", "<i class='fas fa-check-circle'></i>"];

            function openWizard() {
                localStorage.setItem('wizardShown', 'true');
                document.getElementById('onboardingWizard').style.display = 'flex';
                currentStep = 1;
                updateStep();
            }

            function closeWizard() {
                document.getElementById('onboardingWizard').style.display = 'none';
            }

            function updateStep() {
                document.getElementById('wizardProgress').style.width = (currentStep * 20) + '%';
                
                const iconContainer = document.getElementById('wizardIcon');
                const titleContainer = document.getElementById('wizardTitle');
                const subContainer = document.getElementById('wizardSub');
                
                if(iconContainer) iconContainer.innerHTML = icons[currentStep-1];
                if(titleContainer) titleContainer.innerText = titles[currentStep-1];
                if(subContainer) subContainer.innerText = subs[currentStep-1];
                
                for(let i=1; i<=5; i++) {
                    const step = document.getElementById('step' + i);
                    if(step) step.style.display = (i === currentStep) ? 'block' : 'none';
                }
                
                const prev = document.getElementById('prevBtn');
                const next = document.getElementById('nextBtn');
                if(prev) prev.style.visibility = (currentStep === 1) ? 'hidden' : 'visible';
                if(next) {
                    if(currentStep === 5) {
                        next.innerHTML = 'Launch CRM <i class="fas fa-check"></i>';
                        next.style.background = '#10b981';
                    } else {
                        next.innerHTML = 'Next Step <i class="fas fa-arrow-right"></i>';
                        next.style.background = 'var(--primary)';
                    }
                }
            }

            function nextStep() {
                if(currentStep < 5) {
                    currentStep++;
                    updateStep();
                } else {
                    closeWizard();
                }
            }

            function prevStep() {
                if(currentStep > 1) {
                    currentStep--;
                    updateStep();
                }
            }

            window.onload = function() {
                if(!localStorage.getItem('wizardShown') && "<?php echo $current_page; ?>" == "index.php" && "<?php echo $role; ?>" == "admin") {
                    // openWizard(); 
                }
            }
        </script>

        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    <div style="position: relative; width: 100%; max-width: 450px;">
                        <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--secondary); font-size: 0.85rem; opacity: 0.6;"></i>
                        <input type="text" placeholder="Search for anything..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 1px solid var(--border); border-radius: 14px; font-size: 0.875rem; background: #fff; transition: all 0.3s; box-shadow: var(--shadow-sm);">
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <?php if ($notif_count > 0): ?>
                    <a href="<?php echo BASE_URL; ?>followups.php" style="position: relative; color: var(--secondary); padding: 0.6rem; border-radius: 12px; background: #fff; border: 1px solid var(--border); box-shadow: var(--shadow-sm); transition: all 0.2s;">
                        <i class="fas fa-bell" style="font-size: 1rem;"></i>
                        <span style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; border: 2.5px solid white; font-family: 'Outfit', sans-serif;">
                            <?php echo $notif_count; ?>
                        </span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo BASE_URL; ?>profile.php" style="display: flex; align-items: center; gap: 0.875rem; padding: 0.35rem; padding-left: 1rem; background: #fff; border: 1px solid var(--border); border-radius: 16px; box-shadow: var(--shadow-sm); text-decoration: none; transition: all 0.2s; cursor: pointer;" 
                       onmouseover="this.style.borderColor='var(--primary)'; this.style.transform='translateY(-1px)';" 
                       onmouseout="this.style.borderColor='var(--border)'; this.style.transform='translateY(0)';" 
                    >
                        <div style="text-align: right;">
                            <div style="font-family: 'Outfit', sans-serif; font-weight: 700; font-size: 0.8125rem; color: var(--text-main); line-height: 1.1;"><?php echo $_SESSION['name']; ?></div>
                            <div style="font-size: 0.625rem; color: var(--text-muted); text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; margin-top: 2px;">
                                <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #10b981; margin-right: 4px;"></span>
                                <?php echo $_SESSION['role']; ?>
                            </div>
                        </div>
                        <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, var(--primary) 0%, #a855f7 100%); color: white; display: flex; align-items: center; justify-content: center; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1rem; box-shadow: 0 4px 10px rgba(88, 81, 255, 0.3);">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </a>
                </div>
            </header>

            <main class="content-body" style="padding: 0.75rem 1.5rem;">

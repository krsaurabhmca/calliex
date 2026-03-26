<?php
// docs.php - High Premium Redesign
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAuth();

include 'includes/header.php';

$org_id = getOrgId();
$admin_res = mysqli_query($conn, "SELECT api_token FROM users WHERE id = {$_SESSION['user_id']}");
$api_token = mysqli_fetch_assoc($admin_res)['api_token'] ?? 'YOUR_API_TOKEN';
$base_url_full = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . "api/";
?>

<div class="docs-wrapper" style="display: flex; gap: 3rem; max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem 100px;">
    
    <!-- Left Navigation Sidebar -->
    <aside class="docs-sidebar" style="width: 280px; position: sticky; top: 2rem; height: calc(100vh - 4rem); overflow-y: auto; padding-right: 1.5rem;">
        <div style="margin-bottom: 2rem;">
            <div style="font-size: 0.65rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 0.5rem;">Version 2.4.0</div>
            <h2 style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.25rem; color: var(--text-main);">Developer Center</h2>
        </div>

        <nav class="docs-nav-group">
            <div class="nav-label">Foundations</div>
            <a href="#intro" class="nav-item active"><i class="fas fa-play"></i> Getting Started</a>
            <a href="#auth" class="nav-item"><i class="fas fa-lock"></i> Authentication</a>
            
            <div class="nav-label">Core Resources</div>
            <a href="#leads" class="nav-item"><i class="fas fa-users-gear"></i> Lead Management</a>
            <a href="#custom-fields" class="nav-item"><i class="fas fa-list-check"></i> Custom Mappings</a>
            <a href="#followups" class="nav-item"><i class="fas fa-calendar-check"></i> Interactions</a>
            
            <div class="nav-label">Advanced Tools</div>
            <a href="#whatsapp" class="nav-item"><i class="fab fa-whatsapp"></i> WA Automation</a>
            <a href="#recordings" class="nav-item"><i class="fas fa-microphone-lines"></i> Call Syncing</a>
            
            <div class="nav-label">Community & Support</div>
            <a href="api_samples.php.sample" download class="nav-item"><i class="fas fa-download"></i> PHP SDK Example</a>
            <a href="api_samples.http" target="_blank" class="nav-item"><i class="fas fa-code"></i> REST Client File</a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="docs-content" style="flex: 1; min-width: 0;">
        
        <!-- Getting Started -->
        <section id="intro">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 3.5rem;">
                <div style="max-width: 700px;">
                    <h1 style="font-family: 'Outfit', sans-serif; font-size: 3rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.04em; line-height: 1.1;">Build Anything with <span style="color: var(--primary);">CallDesk API</span></h1>
                    <p style="font-size: 1.15rem; color: var(--text-muted); margin-top: 1.25rem; line-height: 1.6;">
                        Seamlessly connect your lead generation engines, websites, and custom tools to our CRM infrastructure. Professional-grade endpoints designed for scale.
                    </p>
                </div>
            </div>

            <!-- Bento Cards for Intro -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 4rem;">
                <div class="bento-card" style="background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%); border: 1px solid rgba(88, 81, 255, 0.1);">
                    <div style="display: flex; gap: 1.5rem; align-items: flex-start;">
                        <div style="background: #fff; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                            <i class="fas fa-link" style="color: var(--primary); font-size: 1.25rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; font-size: 1rem;">Base API Endpoint</h4>
                            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.25rem;">All API requests must be directed to our base URL. Use HTTPS to secure your data in transit.</p>
                            <code style="background: #fff; padding: 0.75rem 1rem; border-radius: 10px; border: 1px solid var(--border); color: var(--primary); font-weight: 700; width: 100%; display: block; overflow: hidden; text-overflow: ellipsis;"><?= $base_url_full ?></code>
                        </div>
                    </div>
                </div>
                <div class="bento-card" style="background: #0f172a; color: white;">
                    <h4 style="font-weight: 800; color: #38bdf8; margin-bottom: 0.5rem; font-size: 0.9375rem;">Data Protocol</h4>
                    <p style="font-size: 0.8125rem; color: #94a3b8; line-height: 1.5;">We communicate exclusively via JSON format for both requests and responses.</p>
                    <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                        <span style="background: rgba(16, 185, 129, 0.2); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.65rem; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.3);">UTF-8</span>
                        <span style="background: rgba(56, 189, 248, 0.2); color: #38bdf8; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.65rem; font-weight: 800; border: 1px solid rgba(56, 189, 248, 0.3);">JSON</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Authentication -->
        <section id="auth">
            <h2 class="section-title">Step 1: Secure Authentication</h2>
            <div class="doc-card" style="padding: 2.5rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white;">
                <p style="color: #94a3b8; font-size: 1rem; margin-bottom: 2rem; line-height: 1.6;">Use your permanent **Bearer Token** to authenticate requests. This token never expires unless regenerated in the admin panel.</p>
                <div class="code-header">CURL IMPLEMENTATION</div>
                <pre class="code-block">
curl -X GET "<?= $base_url_full ?>leads.php" \
     -H "Authorization: Bearer <span style="color: #fbbf24;"><?= $api_token ?></span>" \
     -H "Content-Type: application/json"</pre>
                <div style="margin-top: 2rem; display: flex; align-items: center; gap: 1rem; color: #94a3b8; font-size: 0.8125rem;">
                    <i class="fas fa-shield-halved" style="color: #38bdf8;"></i>
                    <span>Protect this token. It grants full write access to your organization's CRM data.</span>
                </div>
            </div>
        </section>

        <!-- Lead Management -->
        <section id="leads">
            <h2 class="section-title">Step 2: Automating Lead Entry</h2>
            <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 2.5rem; line-height: 1.6;">Our robust endpoint allows you to push leads from websites, Facebook lead ads, or any lead generation engine with zero latency.</p>
            
            <div class="doc-card">
                <div class="endpoint-header">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="method post">POST</span>
                        <span class="url">/api/leads.php</span>
                    </div>
                </div>
                <div style="padding: 2.5rem;">
                    <div style="margin-bottom: 2rem;">
                        <h5 style="font-weight: 800; color: var(--text-main); margin-bottom: 1.25rem;">Request Parameters (Body)</h5>
                        <div class="param-grid">
                            <div class="param-row">
                                <div class="param-meta"><span class="param-name">name</span> <span class="required">REQUIRED</span></div>
                                <div class="param-desc">Full customer name (String, max 255)</div>
                            </div>
                            <div class="param-row">
                                <div class="param-meta"><span class="param-name">mobile</span> <span class="required">REQUIRED</span></div>
                                <div class="param-desc">Main phone number (e.g. 9876543210)</div>
                            </div>
                            <div class="param-row">
                                <div class="param-meta"><span class="param-name">source</span> <span class="label">RECOMMENDED</span></div>
                                <div class="param-desc">Exact Name from Lead Sources (e.g. 'Facebook')</div>
                            </div>
                            <div class="param-row">
                                <div class="param-meta"><span class="param-name">remarks</span></div>
                                <div class="param-desc">Initial notes or inquiry details</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Custom Field Mapping -->
        <section id="custom-fields">
            <h2 class="section-title">Mapping Your Custom Fields</h2>
            <p style="color: var(--text-muted); font-size: 1rem; margin-bottom: 2.5rem; line-height: 1.6;">Each organization has unique requirements. Below are the specific parameter keys to map your custom lead data.</p>
            <div class="doc-card" style="padding: 1.5rem;">
                <table class="premium-table">
                    <thead>
                        <tr>
                            <th>Field Identity</th>
                            <th>API Parameter</th>
                            <th style="text-align: right;">Data Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $cfs = getCustomFields($conn, $org_id);
                        foreach ($cfs as $cf): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($cf['field_name']) ?></div>
                                <div style="font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase;">Organization Reference</div>
                            </td>
                            <td><code class="cf-badge">cf_<?= $cf['id'] ?></code></td>
                            <td style="text-align: right;"><span class="type-pill"><?= $cf['field_type'] ?></span></td>
                        </tr>
                        <?php endforeach; if(empty($cfs)): ?>
                        <tr><td colspan="3" style="padding: 3rem; text-align: center; color: var(--text-muted);">Configure your custom fields in the <a href="custom_fields.php" style="color:var(--primary); font-weight:700;">Settings Panel</a> to see them here.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Follow-ups -->
        <section id="followups">
            <h2 class="section-title">Logging Interaction Activity</h2>
            <div class="doc-card" style="background: #fafbfc;">
                <div class="endpoint-header">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span class="method post">POST</span>
                        <span class="url">/api/followups.php</span>
                    </div>
                </div>
                <div style="padding: 2.5rem;">
                    <p style="color: var(--text-muted); font-size: 0.9375rem; line-height: 1.6; margin-bottom: 2rem;">Log call notes and set reminders for next follow-ups through this endpoint or via our mobile SDK.</p>
                    <div class="code-header">PHP SNIPPET EXAMPLE</div>
                    <pre class="code-block" style="background: #fff; border: 1px solid var(--border); color: var(--text-main);">
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'lead_id' => 124,
    'remark'  => 'Client accepted the proposal. Schedule meeting.',
    'next_follow_up_date' => '2024-04-12'
]);</pre>
                </div>
            </div>
        </section>

        <!-- WhatsApp Automation -->
        <section id="whatsapp">
            <h2 class="section-title" style="display: flex; align-items: center; gap: 1rem;">
                <i class="fab fa-whatsapp" style="color: #25d366;"></i> WhatsApp Logic
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <div class="bento-card" style="border: 1px solid rgba(37, 211, 102, 0.2);">
                    <h5 style="font-weight: 800; color: #128c7e; margin-bottom: 1rem;">Auto-Response Engine</h5>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">If enabled, our server-side engine automatically sends welcome messages via Green-API when a lead is created.</p>
                </div>
                <div class="bento-card">
                    <h5 style="font-weight: 800; color: var(--text-main); margin-bottom: 1rem;">Manual Sending</h5>
                    <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6;">Trigger custom messages using <code>/api/send_whatsapp.php</code>. Requires valid Green-API credentials in settings.</p>
                </div>
            </div>
        </section>

    </main>
</div>

<style>
/* Developer Experience CSS */
:root {
    --doc-bg: #fff;
    --doc-sidebar-bg: #fdfdfd;
}

.docs-wrapper {
    background: var(--doc-bg);
}

.nav-label {
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 1rem 0.75rem 0.5rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #475569;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 12px;
    transition: 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    margin-bottom: 2px;
}

.nav-item i {
    width: 20px;
    font-size: 0.9rem;
    opacity: 0.7;
}

.nav-item:hover {
    background: #f1f5f9;
    color: var(--text-main);
}

.nav-item.active {
    background: rgba(88, 81, 255, 0.08);
    color: var(--primary);
    box-shadow: 0 4px 12px rgba(88, 81, 255, 0.05);
}

.section-title {
    font-family: 'Outfit', sans-serif;
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--text-main);
    margin-top: 6rem;
    margin-bottom: 2rem;
    letter-spacing: -0.02em;
}

.bento-card {
    padding: 2rem;
    border-radius: 24px;
    border: 1px solid var(--border);
    transition: 0.3s ease;
}

.doc-card {
    border: 1px solid var(--border);
    border-radius: 28px;
    overflow: hidden;
    margin-bottom: 2.5rem;
    box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02);
}

.endpoint-header {
    background: #fafafa;
    border-bottom: 1px solid var(--border);
    padding: 1.25rem 2.5rem;
}

.method {
    font-size: 0.7rem;
    font-weight: 900;
    color: #fff;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    letter-spacing: 0.05em;
}

.method.post { background: #10b981; }
.method.get { background: #3b82f6; }

.url {
    font-family: 'Cascadia Mono', monospace;
    font-weight: 700;
    font-size: 0.9375rem;
    color: var(--text-main);
}

.param-grid {
    display: flex;
    flex-direction: column;
}

.param-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.param-name {
    font-family: 'Cascadia Mono', monospace;
    font-weight: 800;
    color: var(--text-main);
    font-size: 0.9375rem;
}

.required {
    font-size: 0.6rem;
    color: #ef4444;
    font-weight: 800;
    letter-spacing: 0.05em;
    margin-left: 0.5rem;
}

.label {
    font-size: 0.6rem;
    color: var(--primary);
    font-weight: 800;
    letter-spacing: 0.05em;
    margin-left: 0.5rem;
}

.param-desc {
    color: var(--text-muted);
    font-size: 0.8125rem;
    text-align: right;
    max-width: 300px;
}

.premium-table {
    width: 100%;
    border-collapse: collapse;
}

.premium-table th {
    text-align: left;
    padding: 1.25rem;
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--text-muted);
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
}

.premium-table td {
    padding: 1.5rem 1.25rem;
    border-bottom: 1px solid #f8fafc;
}

.cf-badge {
    background: rgba(88, 81, 255, 0.05);
    color: var(--primary);
    font-weight: 800;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

.type-pill {
    background: #f1f5f9;
    color: var(--text-muted);
    font-weight: 800;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.65rem;
    text-transform: uppercase;
}

.code-header {
    font-size: 0.65rem;
    font-weight: 800;
    color: #475569;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.code-block {
    margin: 0;
    padding: 1.5rem;
    background: #0f172a;
    color: #38bdf8;
    font-family: 'Cascadia Mono', monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    border-radius: 16px;
    overflow-x: auto;
}

/* Scrollbar Style */
.docs-sidebar::-webkit-scrollbar { width: 4px; }
.docs-sidebar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<script>
    // Intersection Observer for Sidebar Links
    const observerOptions = {
        root: null,
        rootMargin: '-50px 0px -60% 0px',
        threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                document.querySelectorAll('.nav-item').forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                });
            }
        });
    }, observerOptions);

    document.querySelectorAll('section[id]').forEach(section => {
        observer.observe(section);
    });

    // Smooth Scrolling
    document.querySelectorAll('.nav-item').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href.startsWith('#')) {
                e.preventDefault();
                const targetEl = document.querySelector(href);
                if (targetEl) {
                    targetEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Manual active toggle
                    document.querySelectorAll('.nav-item').forEach(link => link.classList.remove('active'));
                    this.classList.add('active');
                }
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

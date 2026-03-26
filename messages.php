<?php
require_once 'config/db.php';
require_once 'includes/header.php';

$executive_id = $_SESSION['user_id'];
$org_id = getOrgId();

// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $template_category = mysqli_real_escape_string($conn, $_POST['template_category'] ?? 'GENERAL');
        
        if ($is_default) {
            mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id AND template_category = '$template_category'");
        }

        $sql = "INSERT INTO whatsapp_messages (organization_id, executive_id, title, message, is_default, template_category) VALUES ($org_id, $executive_id, '$title', '$message', $is_default, '$template_category')";
        mysqli_query($conn, $sql);
        echo "<script>window.location.href='messages.php';</script>";
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM whatsapp_messages WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id");
        echo "<script>window.location.href='messages.php';</script>";
    } elseif ($_POST['action'] === 'set_default') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id");
        mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 1 WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id");
        echo "<script>window.location.href='messages.php';</script>";
    } elseif ($_POST['action'] === 'edit') {
        $id = (int)$_POST['id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        $template_category = mysqli_real_escape_string($conn, $_POST['template_category'] ?? 'GENERAL');

        if ($is_default) {
            mysqli_query($conn, "UPDATE whatsapp_messages SET is_default = 0 WHERE executive_id = $executive_id AND organization_id = $org_id AND template_category = '$template_category'");
        }

        $sql = "UPDATE whatsapp_messages SET title = '$title', message = '$message', is_default = $is_default, template_category = '$template_category' WHERE id = $id AND executive_id = $executive_id AND organization_id = $org_id";
        mysqli_query($conn, $sql);
        echo "<script>window.location.href='messages.php';</script>";
    }
}

// Fetch Messages
$sql = "SELECT * FROM whatsapp_messages WHERE executive_id = $executive_id AND organization_id = $org_id ORDER BY is_default DESC, id DESC";
$result = mysqli_query($conn, $sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div>
        <h2 style="font-size: 1.125rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.01em;">WhatsApp Templates</h2>
    </div>
    <button class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> New Template
    </button>
</div>

<div class="message-grid">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="message-card <?php echo $row['is_default'] ? 'default' : ''; ?>">
            <div class="message-header">
                <div class="message-title">
                    <span style="font-size: 0.7rem; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 0.25rem; display: block;">
                        <?= htmlspecialchars($row['template_category'] ?? 'GENERAL') ?>
                    </span>
                    <?php echo htmlspecialchars($row['title']); ?>
                    <?php if ($row['is_default']): ?>
                        <span class="default-badge">DEFAULT</span>
                    <?php endif; ?>
                </div>
                <div class="message-actions">
                    <?php if (!$row['is_default']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="set_default">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="action-btn" title="Set as Default">
                                <i class="far fa-star"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <button type="button" class="action-btn" title="Edit Template" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES); ?>)">
                        <i class="far fa-edit"></i>
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="action-btn delete" title="Delete">
                            <i class="far fa-trash-can"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($row['message'])); ?>
            </div>
            <div class="message-footer">
                <i class="far fa-clock"></i> Created on <?php echo date('d M, Y', strtotime($row['created_at'])); ?>
            </div>
        </div>
    <?php endwhile; ?>
    
    <?php if (mysqli_num_rows($result) === 0): ?>
        <div class="empty-state">
            <i class="fab fa-whatsapp"></i>
            <h3>No Templates Found</h3>
            <p>Add your first WhatsApp message template to start saving time!</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modal_label">New Message Template</h3>
            <button class="close-btn" onclick="document.getElementById('addModal').style.display='none'">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="modal_action" value="add">
            <input type="hidden" name="id" id="modal_id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Template Title</label>
                    <input type="text" name="title" id="modal_title" class="form-control" placeholder="e.g., Welcome Message" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Template Category / Scenario</label>
                    <select name="template_category" id="modal_category" class="form-control" required style="width: 100%; padding: 0.75rem; border-radius: 12px; border: 1px solid var(--border);">
                        <option value="GENERAL">General Message (Manual Template)</option>
                        <option value="WELCOME">Auto Welcome (New Leads)</option>
                        <option value="ASSIGNED">Auto Assigned (Lead Assignment)</option>
                        <option value="FOLLOWUP">Follow-up Reminder</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0.5rem;">
                    <label class="form-label">Message Content</label>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem; overflow-x: auto; padding-bottom: 4px;">
                        <button type="button" class="format-btn" onclick="wrapText('modal_message', '*', '*')" title="Bold"><i class="fas fa-bold"></i></button>
                        <button type="button" class="format-btn" onclick="wrapText('modal_message', '_', '_')" title="Italic"><i class="fas fa-italic"></i></button>
                        <button type="button" class="format-btn" onclick="wrapText('modal_message', '~', '~')" title="Strike"><i class="fas fa-strikethrough"></i></button>
                        <button type="button" class="format-btn" onclick="wrapText('modal_message', '```', '```')" title="Monospace"><i class="fas fa-code"></i></button>
                        <span style="border-right: 1px solid #e2e8f0; margin: 0 4px;"></span>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '✅')" title="Success">✅</button>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '📞')" title="Call">📞</button>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '🚀')" title="Rocket">🚀</button>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '👋')" title="Hello">👋</button>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '✨')" title="Spark">✨</button>
                        <button type="button" class="format-btn" onclick="insertEmoji('modal_message', '⏳')" title="Wait">⏳</button>
                    </div>
                </div>
                <div class="form-group">
                    <textarea name="message" id="modal_message" class="form-control" rows="6" placeholder="Type your WhatsApp message here..." required style="font-family: inherit; resize: vertical;"></textarea>
                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.5rem; display: flex; justify-content: space-between;">
                        <span>Use <code>{name}</code> for client's name.</span>
                        <span id="charCount" style="font-weight: 700;">0 chars</span>
                    </div>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_default" id="modal_is_default">
                    <label for="modal_is_default" style="font-size: 0.875rem; font-weight: 500; color: var(--text-main);">Set as Default for this Scenario</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: #f1f5f9; color: var(--text-main);" onclick="document.getElementById('addModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

<style>
.format-btn {
    padding: 0.35rem 0.65rem;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.8rem;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.2s;
    min-width: 32px;
}
.format-btn:hover {
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-color: var(--primary);
    color: var(--primary);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}
.page-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text-main);
}
.page-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
}
.message-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}
.message-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-sm);
}
.message-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}
.message-card.default {
    border-color: var(--primary);
    background: #f5f7ff;
}
.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}
.message-title {
    font-weight: 700;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.default-badge {
    background: var(--primary);
    color: white;
    font-size: 0.625rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-weight: 800;
}
.message-actions {
    display: flex;
    gap: 0.5rem;
}
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border);
    background: white;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s;
}
.action-btn:hover {
    color: var(--primary);
    border-color: var(--primary);
    background: #f5f7ff;
}
.action-btn.delete:hover {
    color: var(--danger);
    border-color: var(--danger);
    background: #fff5f5;
}
.message-content {
    font-size: 0.875rem;
    color: var(--text-main);
    line-height: 1.6;
    flex: 1;
    margin-bottom: 1.25rem;
}
.message-footer {
    font-size: 0.75rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    border: 2px dashed var(--border);
}
.empty-state i {
    font-size: 3rem;
    color: #25d366;
    margin-bottom: 1rem;
}
.empty-state h3 {
    font-weight: 700;
    color: var(--text-main);
}
.empty-state p {
    color: var(--text-muted);
}
.format-btn {
    padding: 0.35rem 0.65rem;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.8rem;
    color: var(--text-main);
    cursor: pointer;
    transition: all 0.2s;
    min-width: 32px;
}
.format-btn:hover {
    background: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-color: var(--primary);
    color: var(--primary);
}
</style>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display='flex';
    document.getElementById('modal_action').value = 'add';
    document.getElementById('modal_id').value = '';
    document.getElementById('modal_title').value = '';
    document.getElementById('modal_message').value = '';
    document.getElementById('modal_category').value = 'GENERAL';
    document.getElementById('modal_is_default').checked = false;
    document.getElementById('modal_label').innerText = 'New Message Template';
    updateCharCount();
}

function openEditModal(data) {
    document.getElementById('addModal').style.display = 'flex';
    document.getElementById('modal_action').value = 'edit';
    document.getElementById('modal_id').value = data.id;
    document.getElementById('modal_title').value = data.title;
    document.getElementById('modal_message').value = data.message;
    document.getElementById('modal_category').value = data.template_category;
    document.getElementById('modal_is_default').checked = data.is_default == 1;
    document.getElementById('modal_label').innerText = 'Edit Message Template';
    updateCharCount();
}

function wrapText(el_id, start_char, end_char) {
    const el = document.getElementById(el_id);
    const start = el.selectionStart;
    const end = el.selectionEnd;
    const text = el.value;
    const selectedText = text.substring(start, end);
    const replacement = start_char + selectedText + end_char;
    el.value = text.substring(0, start) + replacement + text.substring(end);
    el.focus();
    el.setSelectionRange(start + start_char.length, start + start_char.length + selectedText.length);
    updateCharCount();
}

function insertEmoji(el_id, emoji) {
    const el = document.getElementById(el_id);
    const pos = el.selectionStart;
    const text = el.value;
    el.value = text.substring(0, pos) + emoji + text.substring(pos);
    el.focus();
    el.setSelectionRange(pos + emoji.length, pos + emoji.length);
    updateCharCount();
}

function updateCharCount() {
    const text = document.getElementById('modal_message').value;
    document.getElementById('charCount').innerText = text.length + ' chars';
}

document.getElementById('modal_message').addEventListener('input', updateCharCount);
</script>

<?php require_once 'includes/footer.php'; ?>

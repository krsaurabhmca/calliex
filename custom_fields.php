<?php
// custom_fields.php - Premium Unified Editor
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

$org_id = getOrgId();
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action === 'add' || $action === 'edit') {
            $name = mysqli_real_escape_string($conn, $_POST['field_name']);
            $type = mysqli_real_escape_string($conn, $_POST['field_type']);
            $mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
            $readonly = isset($_POST['is_readonly']) ? 1 : 0;
            $filterable = isset($_POST['is_filterable']) ? 1 : 0;
            $options = mysqli_real_escape_string($conn, $_POST['field_options'] ?? '');

            if ($action === 'add') {
                $sql = "INSERT INTO custom_lead_fields (organization_id, field_name, field_type, field_options, is_mandatory, is_readonly, is_filterable) 
                        VALUES ($org_id, '$name', '$type', '$options', $mandatory, $readonly, $filterable)";
                if (mysqli_query($conn, $sql)) {
                    $message = '<div class="alert alert-success">New custom field created successfully!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Error: ' . mysqli_error($conn) . '</div>';
                }
            } else {
                $id = (int)$_POST['id'];
                $sql = "UPDATE custom_lead_fields 
                        SET field_name = '$name', field_type = '$type', field_options = '$options', 
                            is_mandatory = $mandatory, is_readonly = $readonly, is_filterable = $filterable 
                        WHERE id = $id AND organization_id = $org_id";
                if (mysqli_query($conn, $sql)) {
                    $message = '<div class="alert alert-success">Field configuration updated!</div>';
                } else {
                    $message = '<div class="alert alert-danger">Update Error: ' . mysqli_error($conn) . '</div>';
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "UPDATE custom_lead_fields SET status = 0 WHERE id = $id AND organization_id = $org_id");
            $message = '<div class="alert alert-warning">Field deactivated. It will no longer show in forms.</div>';
        } elseif ($action === 'activate') {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "UPDATE custom_lead_fields SET status = 1 WHERE id = $id AND organization_id = $org_id");
            $message = '<div class="alert alert-success">Field restored and activated!</div>';
        }
    }
}

include 'includes/header.php';
$fields = getCustomFields($conn, $org_id, false); 
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <div>
        <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.02em;">Data Architecture</h1>
        <p style="color: #64748b; font-size: 0.9375rem; margin-top: 0.25rem;">Scale your CRM with custom data points and specialized fields.</p>
    </div>
    <button onclick="openModal('add')" class="btn-premium">
        <i class="fas fa-plus"></i> Design New Field
    </button>
</div>

<?php echo $message; ?>

<div class="card" style="padding: 0; border: 1px solid var(--border); border-radius: 20px; overflow: hidden; background: white;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <th style="padding: 1.25rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em;">Field Name</th>
                <th style="padding: 1.25rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em;">Input Type</th>
                <th style="padding: 1.25rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em;">Logical Rules</th>
                <th style="padding: 1.25rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em;">System Status</th>
                <th style="padding: 1.25rem 1.5rem; font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.05em; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fields as $f): ?>
            <tr style="border-bottom: 1px solid #f1f5f9; transition: 0.2s;" onmouseover="this.style.background='#fbfcfe'" onmouseout="this.style.background='transparent'">
                <td style="padding: 1rem 1.5rem;">
                    <div style="font-weight: 700; color: #1e293b; font-size: 0.9375rem;"><?php echo htmlspecialchars($f['field_name']); ?></div>
                    <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-top: 4px;">ID: #<?= $f['id'] ?></div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                    <span style="background: rgba(88, 81, 255, 0.08); color: var(--primary); padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.02em;">
                        <?= $f['field_type'] ?>
                    </span>
                </td>
                <td style="padding: 1rem 1.5rem;">
                    <div style="display: flex; gap: 0.75rem; font-size: 0.9rem;">
                        <span title="Mandatory" style="color: <?= $f['is_mandatory'] ? 'var(--danger)' : '#e2e8f0' ?>;"><i class="fas fa-asterisk"></i></span>
                        <span title="Read-only" style="color: <?= $f['is_readonly'] ? 'var(--secondary)' : '#e2e8f0' ?>;"><i class="fas fa-lock"></i></span>
                        <span title="Filterable" style="color: <?= $f['is_filterable'] ? '#06b6d4' : '#e2e8f0' ?>;"><i class="fas fa-filter"></i></span>
                    </div>
                </td>
                <td style="padding: 1rem 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: <?= $f['status'] ? 'var(--success)' : 'var(--border)' ?>"></div>
                        <span style="font-size: 0.75rem; font-weight: 800; color: <?= $f['status'] ? '#15803d' : '#94a3b8' ?>;">
                            <?= $f['status'] ? 'Active' : 'Hidden' ?>
                        </span>
                    </div>
                </td>
                <td style="padding: 1rem 1.5rem; text-align: right; white-space: nowrap;">
                    <?php if($f['status']): ?>
                    <button onclick='openModal("edit", <?= json_encode($f) ?>)' class="icon-btn-edit" title="Edit Properties">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Deactivate this field?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button type="submit" class="icon-btn-delete" title="Deactivate">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="activate">
                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                        <button type="submit" class="icon-btn-restore">
                            <i class="fas fa-circle-check"></i> Restore
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Unified Premium Modal -->
<div id="fieldModal" class="modal-overlay" style="display: none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3 id="modal_label">Design Custom Field</h3>
            <button onclick="closeModal()" class="close-btn">&times;</button>
        </div>
        <form id="fieldForm" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="form_action" value="add">
                <input type="hidden" name="id" id="field_id" value="">
                
                <div class="form-group">
                    <label class="form-label">Field Identity (Title)</label>
                    <input type="text" name="field_name" id="field_name" class="form-control" placeholder="e.g. GST Number" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Data Format</label>
                    <select name="field_type" id="field_type" class="form-control" onchange="toggleOptions()">
                        <option value="TEXT">Short Text</option>
                        <option value="NUMBER">Numeric Value</option>
                        <option value="DATE">Calendar Date</option>
                        <option value="OPTION">Single Pick List</option>
                        <option value="MULTIPLE">Multi-Select Tags</option>
                        <option value="AUTO">Serial Number (Auto)</option>
                    </select>
                </div>

                <div id="options-container" class="form-group" style="display: none;">
                    <label class="form-label">List Items (Comma Separated)</label>
                    <textarea name="field_options" id="field_options" class="form-control" rows="3" placeholder="Red, Green, Blue..."></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #f8fafc; padding: 1rem; border-radius: 12px; margin-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer;">
                        <input type="checkbox" name="is_mandatory" id="is_mandatory"> Required Field
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer;">
                        <input type="checkbox" name="is_readonly" id="is_readonly"> Read Only
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer;">
                        <input type="checkbox" name="is_filterable" id="is_filterable"> Smart Filter
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal()" style="background: transparent; color: var(--secondary); font-weight: 800;">Discard</button>
                <button type="submit" class="btn-premium" style="padding: 0.75rem 1.75rem;">Confirm & Save</button>
            </div>
        </form>
    </div>
</div>

<style>
    .btn-premium { background: var(--primary); color: white; border: none; padding: 0.625rem 1.25rem; border-radius: 12px; font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 0.875rem; cursor: pointer; display: flex; align-items: center; gap: 0.6rem; transition: 0.2s; box-shadow: 0 4px 10px rgba(88, 81, 255, 0.2); }
    .btn-premium:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(88, 81, 255, 0.35); }
    
    .icon-btn-edit { background: rgba(88, 81, 255, 0.1); color: var(--primary); border: none; padding: 0.5rem; border-radius: 8px; cursor: pointer; margin-right: 0.5rem; transition: 0.2s; }
    .icon-btn-edit:hover { background: var(--primary); color: white; }
    
    .icon-btn-delete { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: none; padding: 0.5rem; border-radius: 8px; cursor: pointer; transition: 0.2s; }
    .icon-btn-delete:hover { background: var(--danger); color: white; }
    
    .icon-btn-restore { background: rgba(16, 185, 129, 0.1); color: var(--success); border: none; padding: 0.4rem 0.75rem; border-radius: 8px; cursor: pointer; font-size: 0.75rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.4rem; transition: 0.2s; }
    .icon-btn-restore:hover { background: var(--success); color: white; }
</style>

<script>
function openModal(mode, data = null) {
    const modal = document.getElementById('fieldModal');
    const label = document.getElementById('modal_label');
    const action = document.getElementById('form_action');
    
    modal.style.display = 'flex';
    if (mode === 'add') {
        label.innerText = 'Design Custom Field';
        action.value = 'add';
        document.getElementById('fieldForm').reset();
        document.getElementById('field_id').value = '';
    } else {
        label.innerText = 'Edit Field Properties';
        action.value = 'edit';
        document.getElementById('field_id').value = data.id;
        document.getElementById('field_name').value = data.field_name;
        document.getElementById('field_type').value = data.field_type;
        document.getElementById('field_options').value = data.field_options || '';
        document.getElementById('is_mandatory').checked = data.is_mandatory == 1;
        document.getElementById('is_readonly').checked = data.is_readonly == 1;
        document.getElementById('is_filterable').checked = data.is_filterable == 1;
    }
    toggleOptions();
}

function closeModal() {
    document.getElementById('fieldModal').style.display = 'none';
}

function toggleOptions() {
    const type = document.getElementById('field_type').value;
    const container = document.getElementById('options-container');
    container.style.display = (type === 'OPTION' || type === 'MULTIPLE') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>

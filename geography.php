<?php
// geography.php
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
checkAdmin();

include 'includes/header.php';
$org_id = getOrgId();
$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add_state') {
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "INSERT INTO states (organization_id, name) VALUES ($org_id, '$name')");
        } elseif ($action === 'edit_state') {
            $id = (int)$_POST['id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "UPDATE states SET name = '$name' WHERE id = $id AND organization_id = $org_id");
        } elseif ($action === 'delete_state') {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "DELETE FROM states WHERE id = $id AND organization_id = $org_id");
            mysqli_query($conn, "DELETE FROM districts WHERE state_id = $id"); 
        } elseif ($action === 'add_district') {
            $state_id = (int)$_POST['state_id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "INSERT INTO districts (state_id, name) VALUES ($state_id, '$name')");
        } elseif ($action === 'edit_district') {
            $id = (int)$_POST['id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "UPDATE districts SET name = '$name' WHERE id = $id");
        } elseif ($action === 'delete_district') {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "DELETE FROM districts WHERE id = $id");
            mysqli_query($conn, "DELETE FROM blocks WHERE district_id = $id");
        } elseif ($action === 'add_block') {
            $district_id = (int)$_POST['district_id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "INSERT INTO blocks (district_id, name) VALUES ($district_id, '$name')");
        } elseif ($action === 'edit_block') {
            $id = (int)$_POST['id'];
            $name = mysqli_real_escape_string($conn, $_POST['name']);
            mysqli_query($conn, "UPDATE blocks SET name = '$name' WHERE id = $id");
        } elseif ($action === 'delete_block') {
            $id = (int)$_POST['id'];
            mysqli_query($conn, "DELETE FROM blocks WHERE id = $id");
        }
    }
}

$states = getStates($conn, $org_id);
?>

<div style="max-width: 1400px; margin: 0 auto; padding-top: 1rem;" class="animate-fadeIn">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.02em;">Geography Hub</h1>
            <p style="color: var(--secondary); font-size: 0.875rem; margin-top: 0.4rem; font-weight: 500;">Configure regions for intelligent lead distribution</p>
        </div>
        <button onclick="document.getElementById('add-state-modal').style.display='flex'" style="background: var(--primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(88, 81, 255, 0.2);">
            <i class="fas fa-plus"></i> NEW STATE
        </button>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.2fr 1.2fr; gap: 1.5rem;">
        
        <!-- States List -->
        <div class="dash-card" style="background: white; border-radius: 20px; border: 1px solid var(--border); height: 650px; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: #fbfcfe; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 800; color: var(--text-main); margin: 0;">STATES</h4>
                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary);"></div>
            </div>
            <div style="flex: 1; overflow-y: auto; padding: 1rem;">
                <?php foreach($states as $s): ?>
                <div class="geo-item state-item" id="state-<?= $s['id']; ?>" onclick="loadDistricts(<?= $s['id']; ?>, '<?= addslashes($s['name']); ?>')" style="padding: 1rem; border-radius: 14px; cursor: pointer; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; border: 1px solid transparent;">
                    <span style="font-weight: 700; color: var(--text-main);"><?= htmlspecialchars($s['name']); ?></span>
                    <div style="display: flex; gap: 0.75rem;" class="geo-actions">
                        <i class="fas fa-pen-to-square" style="color: var(--primary); font-size: 0.85rem;" onclick="event.stopPropagation(); showEditState(<?= $s['id']; ?>, '<?= addslashes($s['name']); ?>')"></i>
                        <i class="fas fa-trash-can" style="color: var(--danger); font-size: 0.85rem;" onclick="event.stopPropagation(); deleteItem('state', <?= $s['id']; ?>)"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Districts List -->
        <div class="dash-card" style="background: white; border-radius: 20px; border: 1px solid var(--border); height: 650px; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: #fbfcfe; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 800; color: var(--text-main); margin: 0;" id="district-title">DISTRICTS</h4>
                <button id="add-district-btn" style="display: none; background: rgba(88, 81, 255, 0.1); color: var(--primary); border: none; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer;" onclick="showAddDistrict()"><i class="fas fa-plus"></i> ADD</button>
            </div>
            <div id="districts-container" style="flex: 1; overflow-y: auto; padding: 1rem;">
                <div style="text-align: center; color: var(--text-muted); padding: 5rem 2rem;">
                    <i class="fas fa-map-location-dot" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 1.5rem;"></i>
                    <p style="font-size: 0.875rem; font-weight: 600;">Select a state to explore its districts</p>
                </div>
            </div>
        </div>

        <!-- Blocks List -->
        <div class="dash-card" style="background: white; border-radius: 20px; border: 1px solid var(--border); height: 650px; display: flex; flex-direction: column; overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border); background: #fbfcfe; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="font-family: 'Outfit', sans-serif; font-size: 0.9rem; font-weight: 800; color: var(--text-main); margin: 0;" id="block-title">BLOCKS</h4>
                <button id="add-block-btn" style="display: none; background: rgba(88, 81, 255, 0.1); color: var(--primary); border: none; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.7rem; font-weight: 800; cursor: pointer;" onclick="showAddBlock()"><i class="fas fa-plus"></i> ADD</button>
            </div>
            <div id="blocks-container" style="flex: 1; overflow-y: auto; padding: 1rem;">
                <div style="text-align: center; color: var(--text-muted); padding: 5rem 2rem;">
                    <i class="fas fa-city" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 1.5rem;"></i>
                    <p style="font-size: 0.875rem; font-weight: 600;">Select a district to view active blocks</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals (Enhanced) -->
<div id="add-state-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">NEW STATE</h3>
        <form method="POST"><input type="hidden" name="action" value="add_state">
            <input type="text" name="name" required placeholder="Ex: Uttar Pradesh" class="form-input">
            <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                <button type="submit" style="flex: 1; background: var(--primary); color: white; border: none; padding: 0.75rem; border-radius: 12px; font-weight: 800; cursor: pointer;">SAVE STATE</button>
                <button type="button" onclick="document.getElementById('add-state-modal').style.display='none'" style="background: #f1f5f9; color: var(--text-main); border: none; padding: 0.75rem 1rem; border-radius: 12px; font-weight: 700; cursor: pointer;">CANCEL</button>
            </div>
        </form>
    </div>
</div>

<div id="edit-state-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">EDIT STATE</h3>
        <form method="POST"><input type="hidden" name="action" value="edit_state"><input type="hidden" name="id" id="edit-state-id">
            <input type="text" name="name" id="edit-state-name" required class="form-input">
            <div style="margin-top: 1.5rem;"><button type="submit" class="elite-btn" style="width: 100%;">APPLY CHANGES</button></div>
        </form>
    </div>
</div>

<div id="add-district-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">ADD DISTRICT</h3>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">Adding to: <span id="modal-state-name" style="color: var(--primary); font-weight: 800;"></span></p>
        <form method="POST"><input type="hidden" name="action" value="add_district"><input type="hidden" name="state_id" id="modal-state-id">
            <input type="text" name="name" required placeholder="District Name" class="form-input">
            <div style="margin-top: 1.5rem;"><button type="submit" class="elite-btn" style="width: 100%;">SAVE DISTRICT</button></div>
        </form>
    </div>
</div>

<div id="edit-district-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">EDIT DISTRICT</h3>
        <form method="POST"><input type="hidden" name="action" value="edit_district"><input type="hidden" name="id" id="edit-dist-id">
            <input type="text" name="name" id="edit-dist-name" required class="form-input">
            <div style="margin-top: 1.5rem;"><button type="submit" class="elite-btn" style="width: 100%;">UPDATE DISTRICT</button></div>
        </form>
    </div>
</div>

<div id="add-block-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">ADD BLOCK</h3>
        <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">District: <span id="modal-dist-name" style="color: var(--primary); font-weight: 800;"></span></p>
        <form method="POST"><input type="hidden" name="action" value="add_block"><input type="hidden" name="district_id" id="modal-dist-id">
            <input type="text" name="name" required placeholder="Block Name" class="form-input">
            <div style="margin-top: 1.5rem;"><button type="submit" class="elite-btn" style="width: 100%;">SAVE BLOCK</button></div>
        </form>
    </div>
</div>

<div id="edit-block-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content animate-slideUp">
        <h3 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">EDIT BLOCK</h3>
        <form method="POST"><input type="hidden" name="action" value="edit_block"><input type="hidden" name="id" id="edit-block-id">
            <input type="text" name="name" id="edit-block-name" required class="form-input">
            <div style="margin-top: 1.5rem;"><button type="submit" class="elite-btn" style="width: 100%;">UPDATE BLOCK</button></div>
        </form>
    </div>
</div>

<form id="delete-form" method="POST" style="display:none;">
    <input type="hidden" name="action" id="delete-action">
    <input type="hidden" name="id" id="delete-id">
</form>

<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
.modal-content { background: white; padding: 2.5rem; border-radius: 20px; width: 420px; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
.form-input { width: 100%; padding: 0.875rem 1rem; border: 1px solid var(--border); border-radius: 12px; font-size: 0.9rem; transition: 0.2s; outline: none; }
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(88, 81, 255, 0.1); }
.geo-item:hover { background: #f8fafc; }
.state-item.active { background: rgba(88, 81, 255, 0.08) !important; color: var(--primary); border-color: var(--primary) !important; }
.item-row { padding: 1rem; border-radius: 14px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; transition: 0.2s; border: 1px solid transparent; }
.item-row:hover { background: #f8fafc; }
.item-row.active { background: rgba(88, 81, 255, 0.08); border-color: var(--primary); }

.geo-actions { opacity: 0; transition: opacity 0.2s; }
.geo-item:hover .geo-actions, .item-row:hover .geo-actions { opacity: 1; }
.elite-btn { background: var(--primary); color: white; border: none; padding: 0.875rem; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.2s; }
.elite-btn:hover { background: #463fed; transform: translateY(-2px); }
</style>

<script>
let currentStateId = null;
let currentDistId = null;

async function loadDistricts(stateId, stateName) {
    currentStateId = stateId;
    document.querySelectorAll('.state-item').forEach(el => el.classList.remove('active'));
    document.getElementById('state-'+stateId).classList.add('active');
    document.getElementById('district-title').innerText = stateName.toUpperCase();
    document.getElementById('add-district-btn').style.display = 'block';
    
    document.getElementById('districts-container').innerHTML = '<div style="padding: 3rem; text-align: center;"><i class="fas fa-spinner fa-spin" style="color: var(--primary);"></i></div>';
    
    try {
        const res = await fetch('api/geography.php?type=districts&state_id=' + stateId);
        const response = await res.json();
        const data = response.data; // HANDLING WRAPPED RESPONSE
        
        let html = '';
        if(data && data.length > 0) {
            data.forEach(d => {
                html += `<div class="item-row dist-item" id="dist-${d.id}" onclick="loadBlocks(${d.id}, '${d.name.replace(/'/g, "\\'")}')">
                            <span style="font-weight: 700; color: var(--text-main);">${d.name}</span>
                            <div style="display:flex; gap:0.75rem;" class="geo-actions">
                                <i class="fas fa-pen-to-square" style="color: var(--primary); font-size: 0.8rem;" onclick="event.stopPropagation(); showEditDistrict(${d.id}, '${d.name.replace(/'/g, "\\'")}')"></i>
                                <i class="fas fa-trash-can" style="color: var(--danger); font-size: 0.8rem;" onclick="event.stopPropagation(); deleteItem('district', ${d.id})"></i>
                                <i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--border);"></i>
                            </div>
                         </div>`;
            });
        } else {
            html = '<p style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.875rem;">No districts added yet.</p>';
        }
        document.getElementById('districts-container').innerHTML = html;
        document.getElementById('blocks-container').innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 5rem 2rem;"><i class="fas fa-city" style="font-size: 2.5rem; opacity: 0.2; display: block; margin-bottom: 1.5rem;"></i><p style="font-size: 0.875rem; font-weight: 600;">Select a district</p></div>';
        document.getElementById('add-block-btn').style.display = 'none';
        document.getElementById('block-title').innerText = 'BLOCKS';
    } catch(e) {
        document.getElementById('districts-container').innerHTML = '<p style="text-align:center;padding:1rem;color:var(--danger);">Error loading data.</p>';
    }
}

async function loadBlocks(distId, distName) {
    currentDistId = distId;
    document.querySelectorAll('.dist-item').forEach(el => el.classList.remove('active'));
    const distEl = document.getElementById('dist-'+distId);
    if(distEl) distEl.classList.add('active');
    
    document.getElementById('block-title').innerText = distName.toUpperCase();
    document.getElementById('add-block-btn').style.display = 'block';
    
    document.getElementById('blocks-container').innerHTML = '<div style="padding: 3rem; text-align: center;"><i class="fas fa-spinner fa-spin" style="color: var(--primary);"></i></div>';
    
    try {
        const res = await fetch('api/geography.php?type=blocks&district_id=' + distId);
        const response = await res.json();
        const data = response.data; // HANDLING WRAPPED RESPONSE
        
        let html = '';
        if(data && data.length > 0) {
            data.forEach(b => {
                html += `<div class="item-row">
                            <span style="font-weight: 700; color: var(--text-main);">${b.name}</span>
                            <div style="display:flex; gap:0.75rem;" class="geo-actions">
                                <i class="fas fa-pen-to-square" style="color: var(--primary); font-size: 0.8rem;" onclick="showEditBlock(${b.id}, '${b.name.replace(/'/g, "\\'")}')"></i>
                                <i class="fas fa-trash-can" style="color: var(--danger); font-size: 0.8rem;" onclick="deleteItem('block', ${b.id})"></i>
                            </div>
                         </div>`;
            });
        } else {
            html = '<p style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.875rem;">No blocks found.</p>';
        }
        document.getElementById('blocks-container').innerHTML = html;
    } catch(e) {
        document.getElementById('blocks-container').innerHTML = '<p style="text-align:center;padding:1rem;color:var(--danger);">Error loading data.</p>';
    }
}

function showEditState(id, name) {
    document.getElementById('edit-state-id').value = id;
    document.getElementById('edit-state-name').value = name;
    document.getElementById('edit-state-modal').style.display = 'flex';
}

function showEditDistrict(id, name) {
    document.getElementById('edit-dist-id').value = id;
    document.getElementById('edit-dist-name').value = name;
    document.getElementById('edit-district-modal').style.display = 'flex';
}

function showEditBlock(id, name) {
    document.getElementById('edit-block-id').value = id;
    document.getElementById('edit-block-name').value = name;
    document.getElementById('edit-block-modal').style.display = 'flex';
}

function showAddDistrict() {
    document.getElementById('modal-state-id').value = currentStateId;
    document.getElementById('modal-state-name').innerText = document.getElementById('district-title').innerText;
    document.getElementById('add-district-modal').style.display = 'flex';
}

function showAddBlock() {
    document.getElementById('modal-dist-id').value = currentDistId;
    document.getElementById('modal-dist-name').innerText = document.getElementById('block-title').innerText;
    document.getElementById('add-block-modal').style.display = 'flex';
}

function deleteItem(type, id) {
    if (confirm('Are you sure you want to delete this ' + type + '? This will also delete any child records.')) {
        document.getElementById('delete-action').value = 'delete_' + type;
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-form').submit();
    }
}

window.onclick = function(event) {
    if (event.target.className === 'modal-overlay') {
        event.target.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

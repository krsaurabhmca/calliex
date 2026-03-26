<?php
// bulk_upload.php
require_once 'config/db.php';
require_once 'includes/auth.php';
checkAdmin();

include 'includes/header.php';
$org_id = getOrgId();
$sources = mysqli_query($conn, "SELECT id, source_name FROM lead_sources WHERE organization_id = $org_id");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $source_id = (int)$_POST['source_id'];
    $file = $_FILES['csv_file']['tmp_name'];
    $success = 0; $errors = 0;

    if (($handle = fopen($file, "r")) !== FALSE) {
        fgetcsv($handle); // Skip header row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $name = mysqli_real_escape_string($conn, $data[0] ?? '');
            $mobile = mysqli_real_escape_string($conn, $data[1] ?? '');
            
            if (!$name || !$mobile) { $errors++; continue; }

            $sql = "INSERT INTO leads (organization_id, name, mobile, source_id, status) 
                    VALUES ($org_id, '$name', '$mobile', $source_id, 'New')";
            if (mysqli_query($conn, $sql)) {
                $lead_id = mysqli_insert_id($conn);
                allocateLead($conn, $lead_id);
                $success++;
            } else {
                $errors++;
            }
        }
        fclose($handle);
    }
    $msg = "$success leads imported successfully! ($errors errors)";
}
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <div style="width: 50px; height: 50px; border-radius: 12px; background: #eef2ff; color: #6366f1; display:flex; align-items:center; justify-content:center; font-size: 1.25rem;">
            <i class="fas fa-file-csv"></i>
        </div>
        <div>
            <h2 style="font-weight: 800; color: var(--text-main); margin: 0;">Bulk Lead Upload</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.2rem;">Import multiple prospects quickly via CSV.</p>
        </div>
    </div>

    <?php if (isset($msg)): ?>
        <div style="background: #f0fdf4; color: #16a34a; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700;">
            <i class="fas fa-info-circle"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 2rem;">
        <form method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 1.5rem;">
                <label class="form-label">Default Lead Source</label>
                <select name="source_id" class="form-control" required>
                    <?php while ($s = mysqli_fetch_assoc($sources)): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['source_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="margin-bottom: 2rem; padding: 2.5rem; border: 2px dashed #e2e8f0; border-radius: 16px; text-align: center; background: #f8fafc;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 1rem;"></i>
                <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">Choose CSV File</div>
                <input type="file" name="csv_file" accept=".csv" required style="font-size: 0.875rem;">
                <div style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-muted);">
                    CSV format must be: <strong>Name, Mobile</strong> (No header prefix required)
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; height: 56px; font-size: 1rem;">
                <i class="fas fa-upload"></i> Start Import Process
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

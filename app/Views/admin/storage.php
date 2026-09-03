<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">Storage & Offsite Backup Configurations</h2>
            <p class="text-muted small mb-0">Configure retention limits, empty trash policies, and manage automated Google Cloud Storage (GCP) offsite backups.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Configuration Columns -->
        <div class="col-lg-8">
            <!-- Storage Policies Card -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <form id="formAdminStorage">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-hdd text-primary"></i>
                        <span>Local Storage Policies</span>
                    </h5>

                    <!-- Auto purge select -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Auto-Purge Trash Folder</label>
                        <select name="autoPurgeMonths" class="form-select bg-light border-0 py-2">
                            <option value="0" <?= $storage['autoPurgeMonths'] == 0 ? 'selected' : '' ?>>Never (Keep deleted items indefinitely)</option>
                            <option value="1" <?= $storage['autoPurgeMonths'] == 1 ? 'selected' : '' ?>>After 1 month</option>
                            <option value="3" <?= $storage['autoPurgeMonths'] == 3 ? 'selected' : '' ?>>After 3 months (Recommended)</option>
                            <option value="6" <?= $storage['autoPurgeMonths'] == 6 ? 'selected' : '' ?>>After 6 months</option>
                            <option value="12" <?= $storage['autoPurgeMonths'] == 12 ? 'selected' : '' ?>>After 12 months</option>
                        </select>
                        <span class="text-muted small">Wipe files in user Trash bins automatically once they exceed this threshold.</span>
                    </div>

                    <!-- Footprint breakdown -->
                    <h6 class="fw-bold mt-5 mb-3"><i class="bi bi-pie-chart text-success me-2"></i>Disk Footprint Breakdown</h6>
                    <div class="d-flex flex-column gap-2 small mb-4">
                        <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                            <span class="text-muted">Original Photos Uploaded:</span>
                            <span class="fw-bold"><?= esc($storage['uploadsSize']) ?> <span class="text-muted fw-normal">(<?= esc($storage['uploadsCount']) ?> files)</span></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                            <span class="text-muted">Generated Thumbnails:</span>
                            <span class="fw-bold"><?= esc($storage['thumbsSize']) ?> <span class="text-muted fw-normal">(<?= esc($storage['thumbsCount']) ?> files)</span></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                            <span class="text-muted">MySQL Database Size:</span>
                            <span class="fw-bold"><?= esc($storage['dbSize']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between pt-1">
                            <span class="text-muted fw-bold">Total Platform Footprint:</span>
                            <span class="fw-bold text-primary"><?= esc($storage['totalFootprint']) ?></span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-check-lg me-1"></i> Save Local Storage Config
                        </button>
                    </div>
                </form>
            </div>

            <!-- Google Cloud Storage (GCP) Card -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-cloud-arrow-up-fill text-info"></i>
                        <span>Google Cloud Storage (GCP) Offsite Backup</span>
                    </h5>
                    <div>
                        <?php if ($gcp['isConfigured']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1.5 small fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> CONFIGURED
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary text-white rounded-pill px-3 py-1.5 small fw-bold">
                                <i class="bi bi-dash-circle me-1"></i> NOT CONFIGURED
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="text-muted small mb-4">
                    Protect your photo database and uploaded media against data loss by mirroring backups to a Google Cloud Storage bucket.
                    Automated background tasks prune archives older than your configured retention policy.
                </p>

                <form id="formGcpStorage">
                    <!-- Bucket Name -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">GCP Bucket Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 text-muted font-monospace small">gs://</span>
                            <input type="text" name="bucket" class="form-control bg-light border-0 py-2 font-monospace"
                                   placeholder="chege-photos-backups" value="<?= esc($gcp['bucket']) ?>" required>
                        </div>
                        <span class="text-muted small">Enter only the bucket name (without <code>gs://</code>).</span>
                    </div>

                    <!-- Authentication Mode Selection -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Authentication Method</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="authType" id="authTypeJson" value="json"
                                       <?= ($gcp['authType'] ?? 'json') === 'json' ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold" for="authTypeJson">
                                    <i class="bi bi-file-earmark-code text-primary me-1"></i> Service Account JSON (Recommended)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="authType" id="authTypeHmac" value="hmac"
                                       <?= ($gcp['authType'] ?? '') === 'hmac' ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold" for="authTypeHmac">
                                    <i class="bi bi-key text-warning me-1"></i> S3-Compatible HMAC Keys
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Mode A: Service Account JSON -->
                    <div id="sectionAuthJson" class="mb-3" style="<?= ($gcp['authType'] ?? 'json') === 'hmac' ? 'display: none;' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">Service Account Key JSON</label>
                            <div>
                                <input type="file" id="fileJsonUpload" accept=".json" style="display: none;">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" id="btnUploadJson">
                                    <i class="bi bi-upload me-1"></i> Upload .json File
                                </button>
                            </div>
                        </div>
                        <textarea name="serviceAccountJson" id="serviceAccountJson" rows="5" class="form-control bg-light border-0 font-monospace small"
                                  placeholder='Paste the full contents of your Google Cloud service account JSON key here:
{
  "type": "service_account",
  "project_id": "chege-photos-12345",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...",
  "client_email": "backup-runner@chege-photos-12345.iam.gserviceaccount.com"
}'><?= esc($gcp['serviceAccountJson']) ?></textarea>
                        <span class="text-muted small">Credentials are encrypted and stored in application settings.</span>
                    </div>

                    <!-- Mode B: HMAC S3 Keys -->
                    <div id="sectionAuthHmac" class="mb-3" style="<?= ($gcp['authType'] ?? 'json') === 'json' ? 'display: none;' : '' ?>">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Access Key ID</label>
                                <input type="text" name="accessKey" class="form-control bg-light border-0 font-monospace small"
                                       placeholder="GOOG..." value="<?= esc($gcp['accessKey']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Secret Access Key</label>
                                <input type="password" name="secretKey" class="form-control bg-light border-0 font-monospace small"
                                       placeholder="Secret Key" value="<?= esc($gcp['secretKey']) ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Retention & Auto-Pruning Policy -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Cloud Backup Retention &amp; Auto-Prune</label>
                        <select name="retentionDays" class="form-select bg-light border-0 py-2">
                            <option value="7" <?= $gcp['retentionDays'] == 7 ? 'selected' : '' ?>>7 days</option>
                            <option value="14" <?= $gcp['retentionDays'] == 14 ? 'selected' : '' ?>>14 days</option>
                            <option value="30" <?= $gcp['retentionDays'] == 30 ? 'selected' : '' ?>>30 days (Recommended)</option>
                            <option value="60" <?= $gcp['retentionDays'] == 60 ? 'selected' : '' ?>>60 days</option>
                            <option value="90" <?= $gcp['retentionDays'] == 90 ? 'selected' : '' ?>>90 days</option>
                            <option value="180" <?= $gcp['retentionDays'] == 180 ? 'selected' : '' ?>>180 days (6 months)</option>
                            <option value="365" <?= $gcp['retentionDays'] == 365 ? 'selected' : '' ?>>365 days (1 year)</option>
                            <option value="0" <?= $gcp['retentionDays'] == 0 ? 'selected' : '' ?>>Never delete (Disabled)</option>
                        </select>
                        <span class="text-muted small">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            The daily <code>db:backup</code> cron job will automatically purge and delete backups in GCP older than this threshold.
                        </span>
                    </div>

                    <!-- Actions toolbar -->
                    <div class="d-flex flex-wrap gap-2 pt-3 border-top" style="border-color: var(--border-color) !important;">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill" id="btnSaveGcp">
                            <i class="bi bi-check-lg me-1"></i> Save GCP Settings
                        </button>
                        <button type="button" class="btn btn-outline-info px-4 rounded-pill" id="btnTestGcp">
                            <i class="bi bi-speedometer2 me-1"></i> Test Connection &amp; Permissions
                        </button>
                        <button type="button" class="btn btn-outline-success px-4 rounded-pill ms-auto" id="btnTriggerBackup">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Backup Database &amp; Sync Now
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Local & Cloud Database Backups -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-secondary"></i>
                        <span>Recent Database Snapshots</span>
                    </h5>
                    <span class="badge bg-light text-muted border px-3 py-1 rounded-pill small">
                        <?= count($recentBackups) ?> snapshot(s) found
                    </span>
                </div>

                <?php if (empty($recentBackups)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-archive display-6 text-muted mb-2 d-block"></i>
                        No database snapshots created yet. Click "Backup Database &amp; Sync Now" above or let the scheduled <code>db:backup</code> task run.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead>
                                <tr class="text-muted border-bottom" style="border-color: var(--border-color) !important;">
                                    <th>Snapshot Archive</th>
                                    <th>Size</th>
                                    <th>Created At</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recentBackups, 0, 10) as $b): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-zip text-danger me-2"></i>
                                            <code class="font-monospace text-primary"><?= esc($b['filename']) ?></code>
                                        </td>
                                        <td><?= esc($b['size']) ?></td>
                                        <td class="text-muted"><?= esc($b['created_at']) ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1">Ready</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar / Setup Guides -->
        <div class="col-lg-4">
            <!-- 3-Step Setup Guide Accordion -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-question-circle-fill text-info"></i>
                    <span>How to set up GCP in 3 minutes</span>
                </h6>
                
                <div class="small text-muted d-flex flex-column gap-3">
                    <div class="border rounded p-2.5 bg-light bg-opacity-25">
                        <div class="fw-bold text-dark mb-1">Step 1: Create a Bucket</div>
                        Open <a href="https://console.cloud.google.com/storage/browser" target="_blank" class="text-primary text-decoration-none">Google Cloud Storage <i class="bi bi-box-arrow-up-right small"></i></a>, click <strong>Create Bucket</strong>, and choose a unique name (e.g., <code>chege-photos-backup</code>).
                    </div>
                    
                    <div class="border rounded p-2.5 bg-light bg-opacity-25">
                        <div class="fw-bold text-dark mb-1">Step 2: Create a Service Account</div>
                        Navigate to <strong>IAM &amp; Admin &gt; Service Accounts</strong>. Create an account named <code>chege-backup-bot</code> and grant it the role:
                        <div class="mt-1"><span class="badge bg-primary text-white">Storage Object Admin</span></div>
                    </div>

                    <div class="border rounded p-2.5 bg-light bg-opacity-25">
                        <div class="fw-bold text-dark mb-1">Step 3: Download &amp; Paste Key</div>
                        Click your new Service Account &gt; <strong>Keys</strong> tab &gt; <strong>Add Key &gt; Create new key (JSON)</strong>. Paste the downloaded JSON file into the text box and click <strong>Test Connection</strong>.
                    </div>
                </div>
            </div>

            <!-- Danger Zone Card -->
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary); border-top: 4px solid var(--bs-danger) !important;">
                <h6 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h6>
                <p class="text-muted small">Perform administrative data resets, empty trash files, or a complete factory reset.</p>
                
                <div class="d-flex flex-column gap-2 mt-3">
                    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill w-100 fw-bold py-2" id="btnResetData">
                        <i class="bi bi-trash-fill me-1"></i> Clear Data (Keep Users)
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-bold py-2" id="btnEmptyTrashAll">
                        <i class="bi bi-trash3-fill me-1"></i> Empty Trash (All Users)
                    </button>
                    <button type="button" class="btn btn-danger btn-sm rounded-pill w-100 fw-bold py-2" id="btnWipeSystem">
                        <i class="bi bi-shield-slash-fill me-1"></i> Factory Reset (Full Wipe)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Diagnostic Connection Probe Checklist -->
<div class="modal fade" id="modalGcpDiagnostics" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-card" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="modal-header border-bottom pb-3" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-speedometer2 text-info"></i>
                    <span>GCP Connection Diagnostics</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div id="probeLoading" class="text-center py-4">
                    <div class="spinner-border text-info mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="fw-bold mb-1">Running Connectivity Probes...</h6>
                    <p class="text-muted small mb-0">Testing DNS, OAuth2 assertion, bucket access, and write/delete permissions.</p>
                </div>

                <div id="probeResults" style="display: none;">
                    <div class="mb-3 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0" id="probeOverallTitle">Test Results</h6>
                        <span id="probeOverallBadge" class="badge rounded-pill px-3 py-1"></span>
                    </div>

                    <div id="probeStepsList" class="d-flex flex-column gap-2 mb-3"></div>

                    <div class="alert alert-light border small text-muted mb-0" id="probeDurationNotice"></div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3" style="border-color: var(--border-color) !important;">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function promptConfirmation(title, text, callback) {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed) {
                    callback();
                }
            });
        }

        // Toggle Auth Type JSON vs HMAC
        $('input[name="authType"]').on('change', function() {
            if ($(this).val() === 'json') {
                $('#sectionAuthJson').slideDown(200);
                $('#sectionAuthHmac').slideUp(200);
            } else {
                $('#sectionAuthJson').slideUp(200);
                $('#sectionAuthHmac').slideDown(200);
            }
        });

        // JSON file upload button
        $('#btnUploadJson').on('click', function() {
            $('#fileJsonUpload').trigger('click');
        });

        $('#fileJsonUpload').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    $('#serviceAccountJson').val(evt.target.result);
                    showToast('Service account JSON loaded successfully!', 'success');
                };
                reader.readAsText(file);
            }
        });

        // Save Local Storage Settings
        $('#formAdminStorage').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'api/v1/admin/storage/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Local Storage Config');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Local Storage Config');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });

        // Save GCP Settings
        $('#formGcpStorage').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#btnSaveGcp');
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/storage/save-gcp', $(this).serialize(), function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save GCP settings: ' + err, 'danger');
            });
        });

        // Test Connection & Permissions Probe
        $('#btnTestGcp').on('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('modalGcpDiagnostics'));
            modal.show();

            $('#probeLoading').show();
            $('#probeResults').hide();

            var postData = $('#formGcpStorage').serialize();

            $.post(BASE_URL + 'admin/storage/test-gcp', postData, function(res) {
                $('#probeLoading').hide();
                $('#probeResults').show();

                var overallBadge = $('#probeOverallBadge');
                if (res.success) {
                    overallBadge.attr('class', 'badge bg-success text-white rounded-pill px-3 py-1').text('OPERATIONAL');
                    $('#probeOverallTitle').text('All Diagnostic Probes Passed');
                } else {
                    overallBadge.attr('class', 'badge bg-danger text-white rounded-pill px-3 py-1').text('FAILED');
                    $('#probeOverallTitle').text('Diagnostic Failed: ' + (res.message || ''));
                }

                var stepsHtml = '';
                if (res.steps && res.steps.length > 0) {
                    res.steps.forEach(function(s) {
                        var icon = 'bi-check-circle-fill text-success';
                        var badge = '<span class="badge bg-success text-white px-2 py-0.5 rounded-pill small">PASS</span>';
                        if (s.status === 'error') {
                            icon = 'bi-x-circle-fill text-danger';
                            badge = '<span class="badge bg-danger text-white px-2 py-0.5 rounded-pill small">FAIL</span>';
                        } else if (s.status === 'warning') {
                            icon = 'bi-exclamation-triangle-fill text-warning';
                            badge = '<span class="badge bg-warning text-dark px-2 py-0.5 rounded-pill small">WARN</span>';
                        }

                        stepsHtml += '<div class="d-flex align-items-start gap-2 p-2.5 rounded border bg-light bg-opacity-25 small">' +
                            '<i class="bi ' + icon + ' fs-6 mt-0.5"></i>' +
                            '<div class="flex-grow-1">' +
                                '<div class="d-flex justify-content-between align-items-center mb-0.5">' +
                                    '<span class="fw-bold text-dark">' + s.name + '</span>' +
                                    badge +
                                '</div>' +
                                '<div class="text-muted small">' + s.detail + '</div>' +
                            '</div>' +
                        '</div>';
                    });
                }
                $('#probeStepsList').html(stepsHtml);
                $('#probeDurationNotice').html('<i class="bi bi-clock me-1"></i> Total probe latency: <strong>' + (res.duration || 0) + ' seconds</strong>.');
            }).fail(function(xhr) {
                $('#probeLoading').hide();
                $('#probeResults').show();
                $('#probeOverallBadge').attr('class', 'badge bg-danger text-white rounded-pill px-3 py-1').text('REQUEST ERROR');
                $('#probeOverallTitle').text('Diagnostic check failed with server error.');
                $('#probeStepsList').html('<div class="alert alert-danger small mb-0">HTTP ' + xhr.status + ': ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText) + '</div>');
            });
        });

        // Trigger Cloud Backup On-Demand
        $('#btnTriggerBackup').on('click', function() {
            var btn = $(this);
            var originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Backing Up...');

            $.post(BASE_URL + 'admin/storage/trigger-backup', {}, function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Backup Successful!',
                        html: '<p class="small text-muted">' + res.message + '</p><pre class="bg-light p-2 rounded text-start small font-monospace" style="max-height:200px; overflow-y:auto;">' + (res.output || '') + '</pre>',
                        icon: 'success'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Backup failed: ' + err, 'danger');
            });
        });

        // Danger Zone: Clear Data (Keep Users)
        $('#btnResetData').on('click', function() {
            promptConfirmation(
                "CLEAR PLATFORM DATA?",
                "This will permanently delete all uploaded photos, albums, shares, tokens, and logs. User accounts will remain intact.",
                function() {
                    var btn = $('#btnResetData');
                    var originalHtml = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clearing...');

                    $.post(BASE_URL + 'api/v1/admin/storage/reset-data', {}, function(res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.status === 'success') {
                            showToast(res.message, 'success');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to clear data: ' + err, 'danger');
                    });
                }
            );
        });

        // Danger Zone: Factory Reset (Full Wipe)
        $('#btnWipeSystem').on('click', function() {
            promptConfirmation(
                "FACTORY RESET / FULL SYSTEM WIPE?",
                "WARNING: This will completely destroy all databases, drop all tables, delete all files, and run a fresh installation seeder. You will be logged out.",
                function() {
                    var btn = $('#btnWipeSystem');
                    var originalHtml = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Wiping...');

                    $.post(BASE_URL + 'api/v1/admin/storage/wipe-system', {}, function(res) {
                        if (res.status === 'success') {
                            showToast(res.message, 'success');
                            setTimeout(function() { window.location.href = BASE_URL + 'login'; }, 2000);
                        } else {
                            btn.prop('disabled', false).html(originalHtml);
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to wipe system: ' + err, 'danger');
                    });
                }
            );
        });

        // Danger Zone: Empty Trash (All Users)
        $('#btnEmptyTrashAll').on('click', function() {
            promptConfirmation(
                "EMPTY ALL USER TRASH BINS?",
                "This will permanently and irreversibly delete all soft-deleted photos and files from every user's trash bin.",
                function() {
                    var btn = $('#btnEmptyTrashAll');
                    var originalHtml = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Purging...');

                    $.post(BASE_URL + 'api/v1/admin/storage/empty-trash', {}, function(res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.status === 'success') {
                            showToast(res.message, 'success');
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to empty trash: ' + err, 'danger');
                    });
                }
            );
        });
    });
</script>
<?= $this->endSection() ?>

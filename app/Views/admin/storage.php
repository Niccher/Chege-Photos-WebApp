<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">Storage Configurations</h2>
            <p class="text-muted small mb-0">Configure retention limits, empty trash policies, and monitor real-time file footprints.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Storage Panel -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <form id="formAdminStorage">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-hdd text-primary"></i>
                        <span>Storage Policies</span>
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
                            <i class="bi bi-check-lg me-1"></i> Save Storage Config
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Trash Lifecycle</h6>
                <p class="text-muted small" style="line-height: 1.6;">
                    When users delete photos, they are sent to the Trash folder and soft-deleted.
                </p>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    If Auto-Purge is enabled, a scheduled daily background task permanently clears files that have exceeded the selected retention threshold.
                </p>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Save Storage Settings
        $('#formAdminStorage').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/storage/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Storage Config');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Storage Config');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });
    });
</script>
<?php $this->endSection() ?>

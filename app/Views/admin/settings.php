<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">Global Configs</h2>
            <p class="text-muted small">Configure global application variables and storage policies.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <form id="formAdminSettings">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-gear text-primary"></i>
                        <span>System Settings</span>
                    </h5>

                    <!-- User Storage quota limit override -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">Default Storage Limit</label>
                        <select name="storageLimit" class="form-select bg-light border-0 py-2">
                            <option value="536870912" <?= $settings['storageLimit'] == 536870912 ? 'selected' : '' ?>>500 MB</option>
                            <option value="1073741824" <?= $settings['storageLimit'] == 1073741824 ? 'selected' : '' ?>>1 GB (Default)</option>
                            <option value="2147483648" <?= $settings['storageLimit'] == 2147483648 ? 'selected' : '' ?>>2 GB</option>
                            <option value="5368709120" <?= $settings['storageLimit'] == 5368709120 ? 'selected' : '' ?>>5 GB</option>
                            <option value="10737418240" <?= $settings['storageLimit'] == 10737418240 ? 'selected' : '' ?>>10 GB</option>
                        </select>
                        <span class="text-muted small">The storage space allocated to new users by default.</span>
                    </div>

                    <!-- Allow registration toggle -->
                    <div class="mb-4">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">Allow Public Registrations</label>
                                <span class="text-muted small">Enable or disable signup page accessibility.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="allowRegistration" value="1" <?= $settings['allowRegistration'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- System Maintenance Mode -->
                    <div class="mb-4">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">System Maintenance Mode</label>
                                <span class="text-muted small">Enable to show offline page and restrict user access.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="maintenanceMode" value="1" <?= (isset($settings['maintenanceMode']) && $settings['maintenanceMode']) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Form submission -->
                    <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-check-lg me-1"></i> Save Configs
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Config Lifecycle</h6>
                <p class="text-muted small" style="line-height: 1.6;">
                    System policies are stored in the database's <code>settings</code> table.
                </p>
                <p class="text-muted small" style="line-height: 1.6;">
                    Any changes saved here will apply system-wide to all user libraries immediately.
                </p>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle settings form save
        $('#formAdminSettings').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/settings/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Configs');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Configs');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });
    });
</script>
<?php $this->endSection() ?>

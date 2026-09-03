<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-1" style="color: var(--text-primary);">Global System Settings</h2>
            <p class="text-muted small mb-0">Configure platform branding, security policies, upload constraints, and system governance.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Settings Form Column -->
        <div class="col-lg-8">
            <form id="formAdminSettings">
                <!-- Section 1: Platform Identity & Branding -->
                <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                    <h5 class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-palette-fill text-primary"></i>
                        <span>Platform Identity &amp; Branding</span>
                    </h5>
                    <p class="text-muted small mb-4">Customize the application name, administrative contact points, and localized date/time presentation.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Application Name</label>
                            <input type="text" name="siteName" class="form-control bg-light border-0 py-2" value="<?= esc($settings['siteName']) ?>" required>
                            <span class="text-muted small" style="font-size: 11px;">Displayed in browser title bars, emails, and header navigation.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Support / Contact Email</label>
                            <input type="email" name="supportEmail" class="form-control bg-light border-0 py-2" value="<?= esc($settings['supportEmail']) ?>" required>
                            <span class="text-muted small" style="font-size: 11px;">Target address for system alerts, password resets, and user help requests.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Default Timezone</label>
                            <select name="timezone" class="form-select bg-light border-0 py-2">
                                <?php 
                                $timezones = [
                                    'Africa/Nairobi'   => 'Africa/Nairobi (EAT, UTC+3)',
                                    'UTC'              => 'UTC (Coordinated Universal Time)',
                                    'Europe/London'    => 'Europe/London (GMT/BST)',
                                    'Europe/Paris'     => 'Europe/Paris (CET, UTC+1)',
                                    'America/New_York' => 'America/New_York (EST/EDT)',
                                    'America/Chicago'  => 'America/Chicago (CST/CDT)',
                                    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
                                    'Asia/Dubai'       => 'Asia/Dubai (GST, UTC+4)',
                                    'Asia/Kolkata'     => 'Asia/Kolkata (IST, UTC+5:30)',
                                    'Asia/Tokyo'       => 'Asia/Tokyo (JST, UTC+9)',
                                    'Australia/Sydney' => 'Australia/Sydney (AEST, UTC+10)',
                                ];
                                foreach ($timezones as $tzKey => $tzLabel): ?>
                                    <option value="<?= $tzKey ?>" <?= $settings['timezone'] === $tzKey ? 'selected' : '' ?>><?= $tzLabel ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Used to parse EXIF timestamps and schedule automated cron tasks.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Display Date Format</label>
                            <select name="dateFormat" class="form-select bg-light border-0 py-2">
                                <option value="Y-m-d" <?= $settings['dateFormat'] === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (e.g. <?= date('Y-m-d') ?>)</option>
                                <option value="d/m/Y" <?= $settings['dateFormat'] === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (e.g. <?= date('d/m/Y') ?>)</option>
                                <option value="m/d/Y" <?= $settings['dateFormat'] === 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY (e.g. <?= date('m/d/Y') ?>)</option>
                                <option value="M j, Y" <?= $settings['dateFormat'] === 'M j, Y' ? 'selected' : '' ?>>Month Day, Year (e.g. <?= date('M j, Y') ?>)</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Standard format for timeline cards, metadata sidebars, and audits.</span>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Security & Authentication (CodeIgniter Shield) -->
                <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                    <h5 class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-success"></i>
                        <span>Security &amp; Authentication Policies</span>
                    </h5>
                    <p class="text-muted small mb-4">Govern registration accessibility, email verification, session expirations, and brute-force defenses.</p>

                    <div class="mb-3 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">Allow Public Registrations</label>
                                <span class="text-muted small">Enable or disable sign-up form access. When disabled, only administrators can invite users.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="allowRegistration" value="1" <?= $settings['allowRegistration'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-3 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">Require Email Verification</label>
                                <span class="text-muted small">Force users to verify their email address before they can access and view their photo library.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="requireEmailVerification" value="1" <?= $settings['requireEmailVerification'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Session Inactivity Lifetime</label>
                            <select name="sessionLifetime" class="form-select bg-light border-0 py-2">
                                <option value="43200" <?= (int)$settings['sessionLifetime'] === 43200 ? 'selected' : '' ?>>12 Hours</option>
                                <option value="86400" <?= (int)$settings['sessionLifetime'] === 86400 ? 'selected' : '' ?>>24 Hours (1 Day - Default)</option>
                                <option value="259200" <?= (int)$settings['sessionLifetime'] === 259200 ? 'selected' : '' ?>>3 Days</option>
                                <option value="604800" <?= (int)$settings['sessionLifetime'] === 604800 ? 'selected' : '' ?>>7 Days (1 Week)</option>
                                <option value="2592000" <?= (int)$settings['sessionLifetime'] === 2592000 ? 'selected' : '' ?>>30 Days (1 Month)</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Duration before inactive login sessions expire automatically.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Max Failed Login Attempts</label>
                            <select name="maxLoginAttempts" class="form-select bg-light border-0 py-2">
                                <option value="3" <?= (int)$settings['maxLoginAttempts'] === 3 ? 'selected' : '' ?>>3 Attempts (Strictest)</option>
                                <option value="5" <?= (int)$settings['maxLoginAttempts'] === 5 ? 'selected' : '' ?>>5 Attempts (Recommended)</option>
                                <option value="10" <?= (int)$settings['maxLoginAttempts'] === 10 ? 'selected' : '' ?>>10 Attempts (Relaxed)</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Shield rate-limiting locks the IP address for 15 minutes after threshold.</span>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Upload & Media Constraints -->
                <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                    <h5 class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-cloud-upload-fill text-info"></i>
                        <span>Upload &amp; Media Constraints</span>
                    </h5>
                    <p class="text-muted small mb-4">Control photo and video ingestion boundaries, maximum file sizes, and permitted media formats.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Max Single Upload File Size</label>
                            <select name="maxUploadSizeMb" id="selectMaxUploadSize" class="form-select bg-light border-0 py-2">
                                <option value="25" <?= (int)$settings['maxUploadSizeMb'] === 25 ? 'selected' : '' ?>>25 MB (Photos only)</option>
                                <option value="50" <?= (int)$settings['maxUploadSizeMb'] === 50 ? 'selected' : '' ?>>50 MB</option>
                                <option value="100" <?= (int)$settings['maxUploadSizeMb'] === 100 ? 'selected' : '' ?>>100 MB (High-Res RAW / Pro)</option>
                                <option value="250" <?= (int)$settings['maxUploadSizeMb'] === 250 ? 'selected' : '' ?>>250 MB</option>
                                <option value="500" <?= (int)$settings['maxUploadSizeMb'] === 500 ? 'selected' : '' ?>>500 MB (Recommended for HD Video)</option>
                                <option value="1024" <?= (int)$settings['maxUploadSizeMb'] === 1024 ? 'selected' : '' ?>>1 GB / 1024 MB (4K Mobile Video)</option>
                                <option value="2048" <?= (int)$settings['maxUploadSizeMb'] === 2048 ? 'selected' : '' ?>>2 GB / 2048 MB (Long Video Clips)</option>
                                <option value="4096" <?= (int)$settings['maxUploadSizeMb'] === 4096 ? 'selected' : '' ?>>4 GB / 4096 MB (Cinematic Footage)</option>
                                <option value="5120" <?= (int)$settings['maxUploadSizeMb'] === 5120 ? 'selected' : '' ?>>5 GB / 5120 MB (Enterprise Maximum)</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Enforced both in backend <code>Photos::upload()</code> and frontend Dropzone uploader.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Max Files Per Batch Upload</label>
                            <select name="maxBatchUploadCount" class="form-select bg-light border-0 py-2">
                                <option value="20" <?= (int)$settings['maxBatchUploadCount'] === 20 ? 'selected' : '' ?>>20 Files</option>
                                <option value="50" <?= (int)$settings['maxBatchUploadCount'] === 50 ? 'selected' : '' ?>>50 Files (Default)</option>
                                <option value="100" <?= (int)$settings['maxBatchUploadCount'] === 100 ? 'selected' : '' ?>>100 Files</option>
                                <option value="200" <?= (int)$settings['maxBatchUploadCount'] === 200 ? 'selected' : '' ?>>200 Files</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Prevents browser memory exhaustion during multi-file drag-and-drops.</span>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Permitted File Extensions (Photos &amp; Videos)</label>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-2 py-0.5" id="btnPresetPhotosVideos" style="font-size: 11px;">
                                        <i class="bi bi-camera-video me-1"></i> Photos &amp; Videos
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-2 py-0.5" id="btnPresetPhotosOnly" style="font-size: 11px;">
                                        <i class="bi bi-image me-1"></i> Photos Only
                                    </button>
                                </div>
                            </div>
                            <input type="text" name="allowedExtensions" id="inputAllowedExtensions" class="form-control bg-light border-0 py-2 font-monospace small" value="<?= esc($settings['allowedExtensions']) ?>" required>
                            <span class="text-muted small" style="font-size: 11px;">Supported: <strong>Photos</strong> (<code>jpg,jpeg,png,webp,heic,tiff</code>) • <strong>Videos</strong> (<code>mp4,mov,m4v,webm,mkv,avi</code>).</span>
                        </div>
                    </div>
                </div>

                <!-- Section 4: User Quotas & Retention -->
                <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                    <h5 class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-pie-chart-fill text-warning"></i>
                        <span>Storage Quotas &amp; Retention Policies</span>
                    </h5>
                    <p class="text-muted small mb-4">Set default user library quotas and automated trash cleanup schedules.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Default User Storage Quota</label>
                            <select name="storageLimit" class="form-select bg-light border-0 py-2">
                                <option value="536870912" <?= (int)$settings['storageLimit'] === 536870912 ? 'selected' : '' ?>>500 MB</option>
                                <option value="1073741824" <?= (int)$settings['storageLimit'] === 1073741824 ? 'selected' : '' ?>>1 GB (Default)</option>
                                <option value="2147483648" <?= (int)$settings['storageLimit'] === 2147483648 ? 'selected' : '' ?>>2 GB</option>
                                <option value="5368709120" <?= (int)$settings['storageLimit'] === 5368709120 ? 'selected' : '' ?>>5 GB</option>
                                <option value="10737418240" <?= (int)$settings['storageLimit'] === 10737418240 ? 'selected' : '' ?>>10 GB</option>
                                <option value="21474836480" <?= (int)$settings['storageLimit'] === 21474836480 ? 'selected' : '' ?>>20 GB</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Storage space allocated to new users upon account creation.</span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Trash Auto-Prune Schedule</label>
                            <select name="trashRetentionDays" class="form-select bg-light border-0 py-2">
                                <option value="14" <?= (int)$settings['trashRetentionDays'] === 14 ? 'selected' : '' ?>>14 Days</option>
                                <option value="30" <?= (int)$settings['trashRetentionDays'] === 30 ? 'selected' : '' ?>>30 Days (Default)</option>
                                <option value="60" <?= (int)$settings['trashRetentionDays'] === 60 ? 'selected' : '' ?>>60 Days</option>
                                <option value="90" <?= (int)$settings['trashRetentionDays'] === 90 ? 'selected' : '' ?>>90 Days</option>
                                <option value="0" <?= (int)$settings['trashRetentionDays'] === 0 ? 'selected' : '' ?>>Never (Retain until manual purge)</option>
                            </select>
                            <span class="text-muted small" style="font-size: 11px;">Soft-deleted photos in Trash older than this threshold are permanently purged.</span>
                        </div>
                    </div>
                </div>

                <!-- Section 5: System Governance & Maintenance -->
                <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                    <h5 class="mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-tools text-danger"></i>
                        <span>System Governance &amp; Maintenance</span>
                    </h5>
                    <p class="text-muted small mb-4">Control maintenance mode and user-facing announcements during upgrades.</p>

                    <div class="mb-3 pb-3 border-bottom" style="border-color: var(--border-color) !important;">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">System Maintenance Mode</label>
                                <span class="text-muted small">When enabled, non-admin users are restricted and presented with the maintenance message.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="maintenanceMode" value="1" <?= $settings['maintenanceMode'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-bold">Maintenance Notice Message</label>
                        <textarea name="maintenanceMessage" class="form-control bg-light border-0 py-2 small" rows="3"><?= esc($settings['maintenanceMessage']) ?></textarea>
                        <span class="text-muted small" style="font-size: 11px;">Displayed prominently on the offline screen when maintenance mode is active.</span>
                    </div>
                </div>

                <!-- Form Submission Footer -->
                <div class="d-flex align-items-center gap-2 mb-5">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Save System Configurations
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar: Live Server Diagnostics & Related Modules -->
        <div class="col-lg-4">
            <!-- Live Server Environment Specs -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-cpu-fill text-primary"></i>
                    <span>Server Environment Specs</span>
                </h6>
                <p class="text-muted small mb-3">Live runtime configuration metrics from PHP and MySQL.</p>

                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">PHP Version:</span>
                        <span class="badge bg-light text-dark border font-monospace"><?= esc($serverSpecs['phpVersion']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Framework:</span>
                        <span class="badge bg-light text-dark border font-monospace">CI <?= esc($serverSpecs['ciVersion']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Database Engine:</span>
                        <span class="badge bg-light text-dark border font-monospace"><?= esc($serverSpecs['dbVersion']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Memory Limit:</span>
                        <span class="badge bg-info text-dark font-monospace"><?= esc($serverSpecs['memoryLimit']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">PHP Max Upload Size:</span>
                        <span class="badge bg-primary text-white font-monospace"><?= esc($serverSpecs['uploadMaxFilesize']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">PHP Post Max Size:</span>
                        <span class="badge bg-light text-dark border font-monospace"><?= esc($serverSpecs['postMaxSize']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Max Execution Time:</span>
                        <span class="badge bg-light text-dark border font-monospace"><?= esc($serverSpecs['maxExecutionTime']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Related Admin Modules -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-grid-fill text-success"></i>
                    <span>Dedicated Configuration Consoles</span>
                </h6>
                <p class="text-muted small mb-3">Specialized modules with their own administration interfaces:</p>

                <div class="d-flex flex-column gap-2">
                    <a href="<?= base_url('admin/smtp') ?>" class="btn btn-outline-secondary btn-sm text-start rounded-pill d-flex align-items-center justify-content-between px-3 py-2">
                        <span><i class="bi bi-envelope-gear text-primary me-2"></i>Email Delivery (SMTP)</span>
                        <i class="bi bi-arrow-right small text-muted"></i>
                    </a>
                    <a href="<?= base_url('admin/storage') ?>" class="btn btn-outline-secondary btn-sm text-start rounded-pill d-flex align-items-center justify-content-between px-3 py-2">
                        <span><i class="bi bi-cloud-arrow-up text-info me-2"></i>Offsite Cloud Backup (GCP)</span>
                        <i class="bi bi-arrow-right small text-muted"></i>
                    </a>
                    <a href="<?= base_url('admin/ml') ?>" class="btn btn-outline-secondary btn-sm text-start rounded-pill d-flex align-items-center justify-content-between px-3 py-2">
                        <span><i class="bi bi-cpu text-warning me-2"></i>AI Vision &amp; Model Inventory</span>
                        <i class="bi bi-arrow-right small text-muted"></i>
                    </a>
                    <a href="<?= base_url('admin/crons') ?>" class="btn btn-outline-secondary btn-sm text-start rounded-pill d-flex align-items-center justify-content-between px-3 py-2">
                        <span><i class="bi bi-clock-history text-success me-2"></i>Scheduled Tasks &amp; Crons</span>
                        <i class="bi bi-arrow-right small text-muted"></i>
                    </a>
                </div>
            </div>

            <!-- Configuration Persistence Notes -->
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-2"><i class="bi bi-database-check me-1 text-primary"></i>Configuration Persistence</h6>
                <p class="text-muted small mb-2" style="line-height: 1.6;">
                    All options on this page are stored in the MySQL <code>settings</code> table.
                </p>
                <p class="text-muted small mb-0" style="line-height: 1.6;">
                    Values are cached in memory for zero database latency and survive all server restarts and Railway deployments.
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
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save System Configurations');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save System Configurations');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });

        // Quick extension presets
        $('#btnPresetPhotosVideos').on('click', function() {
            $('#inputAllowedExtensions').val('jpg,jpeg,png,webp,heic,tiff,mp4,mov,m4v,webm,mkv,avi');
            var currentLimit = parseInt($('#selectMaxUploadSize').val()) || 50;
            if (currentLimit < 500) {
                $('#selectMaxUploadSize').val('1024'); // Suggest 1 GB for videos
            }
            showToast('Applied Photos & Videos preset (Max upload set to 1 GB).', 'info');
        });

        $('#btnPresetPhotosOnly').on('click', function() {
            $('#inputAllowedExtensions').val('jpg,jpeg,png,webp,heic,tiff');
            showToast('Applied Photos Only preset.', 'info');
        });
    });
</script>
<?php $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-0" style="color: var(--text-primary);">Registered API &amp; Mobile Devices</h2>
                <p class="text-muted small mb-0">Monitor active authenticated Android and Web API hardware profiles uploading to the library.</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload();">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Devices
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-phone text-primary"></i>
                    <span>Authenticated Device Profiles</span>
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle table-hover text-nowrap small mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Device Name</th>
                                <th>UUID</th>
                                <th>Owner</th>
                                <th>OS Level</th>
                                <th>Screen / Density</th>
                                <th>Locale / Timezone</th>
                                <th>Kernel Info</th>
                                <th>Images Uploaded</th>
                                <th>Last Active (Used)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($devices)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="bi bi-phone-mute fs-1 d-block mb-2"></i>
                                        No active paired mobile devices found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($devices as $dev): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><i class="bi bi-android text-success me-1"></i><?= esc($dev['device_name'] ?: 'Android Device') ?></span>
                                        </td>
                                        <td class="font-monospace text-muted"><?= esc($dev['device_uuid']) ?></td>
                                        <td>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1">
                                                <?= esc($dev['username'] ?: 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-monospace">Android <?= esc($dev['os_version'] ?: '-') ?></span>
                                        </td>
                                        <td class="font-monospace"><?= esc($dev['screen_metrics'] ?: '-') ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark me-1"><?= esc($dev['locale'] ?: '-') ?></span>
                                            <span class="text-muted small"><?= esc($dev['timezone'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block font-monospace" style="max-width: 150px;" title="<?= esc($dev['kernel_version']) ?>">
                                                <?= esc($dev['kernel_version'] ?: '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success"><i class="bi bi-images me-1"></i><?= esc($dev['image_count']) ?> photos</span>
                                        </td>
                                        <td class="font-monospace text-muted"><?= esc($dev['used_at'] ?: ($dev['created_at'] ?: '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

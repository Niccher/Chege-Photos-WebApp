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
                    <table class="table align-middle table-hover text-nowrap small mb-0" style="color: var(--text-primary);">
                        <thead>
                            <tr class="text-muted" style="border-color: var(--border-color) !important;">
                                <th>Device Name</th>
                                <th>Owner</th>
                                <th>OS Level</th>
                                <th>Uploaded Media</th>
                                <th>Last Active</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($devices)): ?>
                                <tr style="border-color: var(--border-color) !important;">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-phone-mute fs-1 d-block mb-2"></i>
                                        No active paired mobile devices found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($devices as $dev): ?>
                                    <tr style="border-color: var(--border-color) !important;">
                                        <td>
                                            <div class="fw-bold">
                                                <i class="bi bi-android text-success me-1 fs-5"></i><?= esc($dev['device_name'] ?: 'Android Device') ?>
                                            </div>
                                            <span class="text-muted font-monospace" style="font-size: 0.75rem;"><?= esc($dev['device_id'] ? substr($dev['device_id'], 0, 16) . '...' : 'Client') ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-body">
                                                <i class="bi bi-person-circle text-primary me-1"></i><?= esc($dev['username'] ?: 'Unknown') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white font-monospace px-2.5 py-1">Android <?= esc($dev['os_version'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success"><i class="bi bi-images me-1"></i><?= esc($dev['image_count']) ?> photos</span>
                                        </td>
                                        <td class="font-monospace text-muted"><?= esc($dev['used_at'] ?: ($dev['created_at'] ?: '-')) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 btn-admin-device-specs" data-device='<?= esc(json_encode($dev), 'attr') ?>'>
                                                <i class="bi bi-cpu me-1"></i> View Specs
                                            </button>
                                        </td>
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

<!-- Admin Device Specifications Modal -->
<div class="modal fade" id="adminDeviceSpecsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); color: var(--text-primary); border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="bi bi-cpu fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="adminSpecsDeviceTitle">Device Hardware Telemetry</h5>
                        <p class="text-muted small mb-0">Complete specifications, display configuration, and hardware identifiers</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="table-responsive rounded-3 border" style="border-color: var(--border-color) !important;">
                    <table class="table table-striped align-middle mb-0 small" style="color: var(--text-primary);">
                        <tbody id="adminDeviceSpecsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnCopyAdminDeviceSpecs"><i class="bi bi-clipboard me-1"></i>Copy Telemetry</button>
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).on('click', '.btn-admin-device-specs', function() {
        var dev = $(this).data('device');
        if (typeof dev === 'string') {
            try { dev = JSON.parse(dev); } catch(e) { dev = {}; }
        }
        $('#adminSpecsDeviceTitle').text((dev.device_name || 'Device') + ' (' + (dev.username || 'Unknown') + ')');

        var rows = [
            { icon: 'bi-phone text-primary', label: 'Device Model', val: dev.device_name || 'N/A' },
            { icon: 'bi-person-circle text-info', label: 'Owner Account', val: '<i class="bi bi-person-circle text-primary me-1"></i><strong>' + (dev.username || 'Unknown') + '</strong> (ID: ' + (dev.user_id || 'N/A') + ')' },
            { icon: 'bi-android2 text-success', label: 'OS & API Level', val: 'Android ' + (dev.os_version || 'N/A') },
            { icon: 'bi-aspect-ratio text-info', label: 'Display & Density', val: dev.screen_metrics || 'N/A' },
            { icon: 'bi-cpu text-warning', label: 'Kernel Version', val: dev.kernel_version || 'N/A' },
            { icon: 'bi-translate text-secondary', label: 'System Locale', val: dev.locale || 'N/A' },
            { icon: 'bi-clock-history text-secondary', label: 'System Timezone', val: dev.timezone || 'N/A' },
            { icon: 'bi-fingerprint text-danger', label: 'Device UUID', val: dev.device_uuid ? '<code>' + dev.device_uuid + '</code>' : 'N/A' },
            { icon: 'bi-fingerprint text-danger', label: 'Device ID', val: dev.device_id ? '<code>' + dev.device_id + '</code>' : 'N/A' },
            { icon: 'bi-shield-check text-warning', label: 'Build Fingerprint', val: dev.device_fingerprint || 'N/A' },
            { icon: 'bi-images text-success', label: 'Photos Uploaded', val: '<strong class="text-success">' + (dev.image_count || 0) + '</strong> assets' },
            { icon: 'bi-calendar-plus text-primary', label: 'First Paired On', val: dev.created_at || 'N/A' },
            { icon: 'bi-activity text-success', label: 'Last Active Session', val: dev.used_at || 'Active' }
        ];

        var tbodyHtml = '';
        rows.forEach(function(item) {
            tbodyHtml += '<tr style="border-color: var(--border-color) !important;">' +
                '<th class="text-muted fw-semibold" style="width: 35%;"><i class="bi ' + item.icon + ' me-2"></i>' + item.label + '</th>' +
                '<td class="font-monospace text-break">' + item.val + '</td>' +
                '</tr>';
        });
        $('#adminDeviceSpecsTableBody').html(tbodyHtml);
        var modal = new bootstrap.Modal(document.getElementById('adminDeviceSpecsModal'));
        modal.show();
    });

    $(document).on('click', '#btnCopyAdminDeviceSpecs', function () {
        var text = '';
        $('#adminDeviceSpecsTableBody tr').each(function () {
            var label = $(this).find('th').text().trim();
            var val = $(this).find('td').text().trim();
            text += label + ': ' + val + '\n';
        });
        navigator.clipboard.writeText(text).then(function () {
            showToast('Device telemetry copied to clipboard!', 'success');
        });
    });
</script>
<?= $this->endSection() ?>

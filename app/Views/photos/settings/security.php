<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<!-- Password Card -->
<div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-4">Change Password</h5>
    <form id="formPassword">
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label small fw-bold">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="col-12 mt-4">
                <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Password</button>
            </div>
        </div>
    </form>
</div>

<!-- Active Devices & Connected Apps Card -->
<div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-2"><i class="bi bi-phone me-2 text-success"></i>Active Devices &amp; Connected Sessions</h5>
    <p class="text-muted small mb-4">Manage Android smartphones and browser sessions currently authorized to access your library.</p>

    <div class="table-responsive">
        <table class="table align-middle table-hover text-nowrap small mb-0" style="color: var(--text-primary);">
            <thead>
                <tr class="text-muted">
                    <th>Device</th>
                    <th>System / OS</th>
                    <th>Authorized Date</th>
                    <th>Last Active</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activeDevices)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-phone-vibrate fs-2 d-block mb-1 text-muted"></i>
                            No additional devices connected.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activeDevices as $device): ?>
                        <tr style="border-bottom: 1px solid var(--border-color) !important;">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= (stripos($device['name'], 'android') !== false) ? 'bi-phone text-success fs-5' : 'bi-laptop text-primary fs-5' ?>"></i>
                                    <div>
                                        <div class="fw-bold"><?= esc($device['name']) ?></div>
                                        <span class="text-muted" style="font-size: 11px;"><?= esc($device['device_id'] ? substr($device['device_id'], 0, 16) . '...' : 'Browser/Token') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    <?= esc($device['os_version'] ?: 'Web App') ?>
                                </span>
                            </td>
                            <td class="font-monospace text-muted"><?= esc($device['created_at'] ?? 'N/A') ?></td>
                            <td class="font-monospace text-muted"><?= esc($device['last_used_at'] ?? 'Active') ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2.5 py-1 me-1 btn-view-device-specs" data-device='<?= esc(json_encode($device), 'attr') ?>'>
                                    <i class="bi bi-cpu me-1"></i> Specs
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-xs rounded-pill px-3 py-1 btn-revoke-device" data-id="<?= esc($device['id']) ?>" data-name="<?= esc($device['name']) ?>">
                                    <i class="bi bi-x-circle me-1"></i> Revoke
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Security & Logs Card -->
<div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-2"><i class="bi bi-clock-history me-2 text-info"></i>Security &amp; Activity Logs</h5>
    <p class="text-muted small mb-4">Track login attempts, security adjustments, and device connections associated with your account.</p>

    <div class="table-responsive">
        <table class="table align-middle table-hover text-nowrap small mb-0" style="color: var(--text-primary);">
            <thead>
                <tr class="text-muted">
                    <th>Timestamp</th>
                    <th>Action Event</th>
                    <th>Status</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th class="text-end">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-slash fs-1 d-block mb-2"></i>
                            No security logs recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid var(--border-color) !important;">
                            <td class="font-monospace text-muted"><?= esc($log['created_at']) ?></td>
                            <td>
                                <code class="px-2 py-1 rounded text-primary fw-bold font-monospace" style="background: rgba(13, 110, 253, 0.08); border: 1px solid rgba(13, 110, 253, 0.2);">
                                    <?= esc($log['action']) ?>
                                </code>
                            </td>
                            <td>
                                <?php if (strtoupper($log['status']) === 'SUCCESS'): ?>
                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-check-circle me-1"></i>SUCCESS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-x-circle me-1"></i>FAILURE</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-monospace"><?= esc($log['ip_address'] ?? 'Unknown') ?></td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 120px;" title="<?= esc($log['user_agent']) ?>">
                                    <?= esc($log['user_agent'] ?: 'None') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($log['details']): ?>
                                    <button class="btn btn-outline-info btn-xs py-0.5 px-2 rounded-pill small" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#user-log-details-<?= $log['id'] ?>">
                                        View
                                    </button>
                                    <div class="collapse mt-2 text-start" id="user-log-details-<?= $log['id'] ?>">
                                        <pre class="bg-light p-2 rounded text-dark font-monospace text-xs mb-0 overflow-auto" style="max-width: 250px; max-height: 150px;"><?= esc(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT)) ?></pre>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Device Specifications Modal -->
<div class="modal fade" id="deviceSpecsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); color: var(--text-primary); border-radius: 1rem;">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="bi bi-cpu fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="specsDeviceName">Device Specifications</h5>
                        <p class="text-muted small mb-0">Hardware telemetry and system identifiers</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="table-responsive rounded-3 border" style="border-color: var(--border-color) !important;">
                    <table class="table table-striped align-middle mb-0 small" style="color: var(--text-primary);">
                        <tbody id="deviceSpecsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnCopyDeviceSpecs"><i class="bi bi-clipboard me-1"></i>Copy Telemetry</button>
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#formPassword').on('submit', function(e) {
            e.preventDefault();
            $.post(BASE_URL + 'settings/password', $(this).serialize(), function(res) {
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    $('#formPassword')[0].reset();
                } else {
                    showToast(res.message, 'danger');
                }
            });
        });

        // View Device Specs Modal
        $(document).on('click', '.btn-view-device-specs', function() {
            var dev = $(this).data('device');
            if (typeof dev === 'string') {
                try { dev = JSON.parse(dev); } catch(e) { dev = {}; }
            }
            $('#specsDeviceName').text(dev.device_name || dev.name || 'Device Specifications');
            
            var rows = [
                { icon: 'bi-phone text-primary', label: 'Device Model / Name', val: dev.device_name || dev.name || 'N/A' },
                { icon: 'bi-android2 text-success', label: 'Operating System', val: (dev.os_version ? 'Android ' + dev.os_version : (dev.os || 'Web App')) },
                { icon: 'bi-aspect-ratio text-info', label: 'Screen Metrics', val: dev.screen_metrics || 'N/A (Browser Client)' },
                { icon: 'bi-cpu text-warning', label: 'Kernel / Architecture', val: dev.kernel_version || 'N/A' },
                { icon: 'bi-translate text-secondary', label: 'System Locale', val: dev.locale || 'N/A' },
                { icon: 'bi-clock-history text-secondary', label: 'Timezone', val: dev.timezone || 'N/A' },
                { icon: 'bi-fingerprint text-danger', label: 'Device UUID', val: dev.device_uuid ? '<code>' + dev.device_uuid + '</code>' : 'N/A' },
                { icon: 'bi-fingerprint text-danger', label: 'Device ID', val: dev.device_id ? '<code>' + dev.device_id + '</code>' : 'N/A' },
                { icon: 'bi-shield-check text-warning', label: 'Device Fingerprint', val: dev.device_fingerprint || 'N/A' },
                { icon: 'bi-globe text-primary', label: 'Last IP Address', val: dev.ip_address || 'N/A' },
                { icon: 'bi-calendar-plus text-primary', label: 'Linked On', val: dev.created_at || 'N/A' },
                { icon: 'bi-activity text-success', label: 'Last Active', val: dev.last_used_at || dev.used_at || 'Active' }
            ];

            var tbodyHtml = '';
            rows.forEach(function(item) {
                tbodyHtml += '<tr style="border-color: var(--border-color) !important;">' +
                    '<th class="text-muted fw-semibold" style="width: 35%;"><i class="bi ' + item.icon + ' me-2"></i>' + item.label + '</th>' +
                    '<td class="font-monospace text-break">' + item.val + '</td>' +
                    '</tr>';
            });
            $('#deviceSpecsTableBody').html(tbodyHtml);
            var modal = new bootstrap.Modal(document.getElementById('deviceSpecsModal'));
            modal.show();
        });

        $(document).on('click', '#btnCopyDeviceSpecs', function () {
            var text = '';
            $('#deviceSpecsTableBody tr').each(function () {
                var label = $(this).find('th').text().trim();
                var val = $(this).find('td').text().trim();
                text += label + ': ' + val + '\n';
            });
            navigator.clipboard.writeText(text).then(function () {
                showToast('Device telemetry copied to clipboard!', 'success');
            });
        });

        // Revoke Device
        $(document).on('click', '.btn-revoke-device', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            if (!confirm('Are you sure you want to revoke access for "' + name + '"? This will sign out the device.')) {
                return;
            }
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
            $.post(BASE_URL + 'settings/tokens/revoke-device', { id: id }, function(res) {
                if (res.status === 'success') {
                    showToast('Device revoked successfully.', 'success');
                    setTimeout(function() { location.reload(); }, 800);
                } else {
                    showToast(res.message || 'Failed to revoke device', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i> Revoke');
                }
            }).fail(function() {
                showToast('Failed to revoke device.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i> Revoke');
            });
        });
    });
</script>
<?= $this->endSection() ?>

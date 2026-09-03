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
                                <span class="font-monospace fw-bold text-dark bg-light px-2 py-0.5 rounded small border">
                                    <?= esc($log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtoupper($log['status']) === 'SUCCESS'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">SUCCESS</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">FAILURE</span>
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

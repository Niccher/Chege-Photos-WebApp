<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-3">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">Email Console</h2>
            <p class="text-muted small mb-0">Manage global mail delivery protocols, inspect outbound delivery logs, and review platform trigger events.</p>
        </div>
    </div>

    <!-- Navigation Pills -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills gap-2 rounded-card p-1" style="background: rgba(0,0,0,0.03); max-width: max-content;">
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-4 small text-dark" href="<?= base_url('admin/smtp') ?>">
                        <i class="bi bi-gear me-1"></i> SMTP Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-4 small text-dark" href="<?= base_url('admin/sent-mails') ?>">
                        <i class="bi bi-envelope-paper me-1"></i> Sent Mail History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active rounded-pill px-4 small" href="<?= base_url('admin/trigger-events') ?>">
                        <i class="bi bi-lightning me-1"></i> Trigger Events
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
        <h5 class="mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-lightning-charge text-warning"></i>
            <span>System Trigger Events</span>
        </h5>
        <p class="text-muted small mb-4">Emails are dynamically dispatched from the platform under the following transaction conditions:</p>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="p-3 border rounded h-100" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.01);">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-person-check text-primary me-2"></i>User Registration &amp; Verification</h6>
                    <p class="text-muted small mb-0">Dispatches an email activation token to verify the registration of new accounts when public signup is enabled.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded h-100" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.01);">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-key-fill text-warning me-2"></i>Password Recovery / Reset Request</h6>
                    <p class="text-muted small mb-0">Sends a secure, timed recovery link to users who request a credentials override via the forgotten-password portal.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded h-100" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.01);">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Password Change Alerts</h6>
                    <p class="text-muted small mb-0">Dispatches security alerts immediately upon updating password configurations to warn users of potential unauthorized access.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded h-100" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.01);">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Storage Quota Warnings</h6>
                    <p class="text-muted small mb-0">Notifies active platform users when their aggregate uploads approach or hit 100% of their dynamic storage quota.</p>
                </div>
            </div>
        </div>

        <!-- Event Trigger Verifier Card -->
        <div class="card border-0 shadow-sm p-4" style="background: rgba(0,0,0,0.02); border: 1px dashed var(--border-color) !important;">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-send text-primary"></i>
                <span>Trigger Event Verifier</span>
            </h6>
            <p class="text-muted small mb-3">Simulate application events to verify email template compilation and dispatch triggers.</p>
            <form id="formVerifyEvent">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Select Application Event</label>
                        <select name="event_type" class="form-select bg-light border-0 py-2" required>
                            <option value="welcome">Welcome &amp; Account Setup</option>
                            <option value="storage_warning">Storage Quota Warning (85%)</option>
                            <option value="password_reset">Password Recovery Request</option>
                            <option value="system_alert">Administrative System Alert</option>
                            <option value="maintenance_on">Maintenance Mode Enabled</option>
                            <option value="maintenance_off">Maintenance Mode Disabled</option>
                            <option value="user_registration">User Registration Confirmation</option>
                            <option value="user_deleted_data">User Data Deleted Confirmation</option>
                            <option value="new_features">New Platform Features Promo</option>
                            <option value="account_info">Account Info Updated Alert</option>
                            <option value="user_suspended">User Account Suspended Alert</option>
                            <option value="login_alert">Login Alert / Unknown Device</option>
                            <option value="weekly_summary">Weekly Library Summary</option>
                            <option value="album_invite">Shared Album Invitation</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Recipient Address</label>
                        <input type="email" name="recipient_email" class="form-control bg-light border-0 py-2" value="<?= esc(auth()->user()->email) ?>" required placeholder="user@domain.com">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary rounded-pill w-100 py-2">
                            <i class="bi bi-lightning-charge-fill me-1"></i> Fire Event Email
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fire Event Email
        $('#formVerifyEvent').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Firing...');

            $.post(BASE_URL + 'admin/smtp/verify-event', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1"></i> Fire Event Email');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1"></i> Fire Event Email');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to fire event: ' + err, 'danger');
            });
        });
    });
</script>
<?= $this->endSection() ?>

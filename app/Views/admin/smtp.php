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
                    <button class="nav-link active rounded-pill px-4 small" data-bs-toggle="pill" data-bs-target="#tab-smtp-settings" type="button">
                        <i class="bi bi-gear me-1"></i> SMTP Settings
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4 small" data-bs-toggle="pill" data-bs-target="#tab-mail-history" type="button">
                        <i class="bi bi-envelope-paper me-1"></i> Sent Mail History
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4 small" data-bs-toggle="pill" data-bs-target="#tab-event-triggers" type="button">
                        <i class="bi bi-lightning me-1"></i> Trigger Events
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <!-- TAB 1: SMTP Settings -->
        <div class="tab-pane fade show active" id="tab-smtp-settings">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <form id="formAdminSmtp">
                            <h5 class="mb-4 d-flex align-items-center gap-2">
                                <i class="bi bi-envelope-at text-primary"></i>
                                <span>Mail Dispatch Parameters</span>
                            </h5>

                            <div class="row g-3">
                                <!-- Sender Details -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">From Email Address</label>
                                    <input type="email" name="fromEmail" class="form-control bg-light border-0 py-2" value="<?= esc($smtp['fromEmail']) ?>" required placeholder="e.g. support@domain.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">From Name</label>
                                    <input type="text" name="fromName" class="form-control bg-light border-0 py-2" value="<?= esc($smtp['fromName']) ?>" required placeholder="e.g. Photos Admin">
                                </div>

                                <!-- Protocol -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Mail Protocol</label>
                                    <select name="protocol" class="form-select bg-light border-0 py-2" id="mailProtocol">
                                        <option value="smtp" <?= $smtp['protocol'] === 'smtp' ? 'selected' : '' ?>>SMTP (Recommended)</option>
                                        <option value="mail" <?= $smtp['protocol'] === 'mail' ? 'selected' : '' ?>>Mail (PHP mail)</option>
                                        <option value="sendmail" <?= $smtp['protocol'] === 'sendmail' ? 'selected' : '' ?>>Sendmail</option>
                                    </select>
                                </div>

                                <!-- Encryption -->
                                <div class="col-md-6 smtp-field">
                                    <label class="form-label small fw-bold">Encryption Security</label>
                                    <select name="SMTPCrypto" class="form-select bg-light border-0 py-2">
                                        <option value="" <?= empty($smtp['SMTPCrypto']) ? 'selected' : '' ?>>None (Plaintext / STARTTLS)</option>
                                        <option value="ssl" <?= $smtp['SMTPCrypto'] === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                                        <option value="tls" <?= $smtp['SMTPCrypto'] === 'tls' ? 'selected' : '' ?>>TLS (Port 587)</option>
                                    </select>
                                </div>

                                <!-- Host & Port -->
                                <div class="col-md-8 smtp-field">
                                    <label class="form-label small fw-bold">SMTP Hostname</label>
                                    <input type="text" name="SMTPHost" class="form-control bg-light border-0 py-2" value="<?= esc($smtp['SMTPHost']) ?>" required placeholder="e.g. mail.domain.com">
                                </div>
                                <div class="col-md-4 smtp-field">
                                    <label class="form-label small fw-bold">SMTP Port</label>
                                    <input type="number" name="SMTPPort" class="form-control bg-light border-0 py-2" value="<?= (int) $smtp['SMTPPort'] ?>" required placeholder="e.g. 465">
                                </div>

                                <!-- SMTP Credentials -->
                                <div class="col-md-6 smtp-field">
                                    <label class="form-label small fw-bold">SMTP Username</label>
                                    <input type="text" name="SMTPUser" class="form-control bg-light border-0 py-2" value="<?= esc($smtp['SMTPUser']) ?>" placeholder="e.g. sender@domain.com">
                                </div>
                                <div class="col-md-6 smtp-field">
                                    <label class="form-label small fw-bold">SMTP Password</label>
                                    <input type="password" name="SMTPPass" class="form-control bg-light border-0 py-2" value="<?= esc($smtp['SMTPPass']) ?>" placeholder="Email password">
                                </div>
                            </div>

                            <!-- Form submission -->
                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill">
                                    <i class="bi bi-check-lg me-1"></i> Save Configurations
                                </button>
                                <button type="button" class="btn btn-outline-warning px-4 rounded-pill" id="btnTestEmail">
                                    <i class="bi bi-send-check me-1"></i> Test Sample Email
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>SMTP Config Tips</h6>
                        <p class="text-muted small mb-2" style="line-height: 1.6;">
                            *   **SSL Configuration:** Connect on port **465** with SSL encryption set to `ssl`.
                        </p>
                        <p class="text-muted small mb-2" style="line-height: 1.6;">
                            *   **TLS Configuration:** Connect on port **587** with TLS encryption set to `tls`.
                        </p>
                        <p class="text-muted small" style="line-height: 1.6;">
                            *   SMTP details are loaded dynamically to overwrite static parameters inside <code>Config\Email.php</code> when sending emails (such as token validations or password resets).
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Sent Mail History -->
        <div class="tab-pane fade" id="tab-mail-history">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-envelope-paper text-info"></i>
                    <span>Delivery Logs</span>
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="color: var(--text-primary);">
                        <thead>
                            <tr class="text-muted small" style="border-color: var(--border-color) !important;">
                                <th>Tracking ID</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Sent At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($emailLogs)): ?>
                                <tr style="border-color: var(--border-color) !important;">
                                    <td colspan="6" class="text-center py-4 text-muted small">No emails sent yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($emailLogs as $log): ?>
                                    <tr style="border-color: var(--border-color) !important;">
                                        <td class="small fw-bold"><code><?= esc($log['tracking_id']) ?></code></td>
                                        <td class="small"><?= esc($log['recipient']) ?></td>
                                        <td class="small text-muted"><?= esc($log['subject']) ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'sent'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small">Delivered</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 small">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= esc($log['sent_at']) ?></td>
                                        <td class="text-end">
                                            <?php if ($log['status'] === 'failed' && !empty($log['debug_log'])): ?>
                                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 py-0.5 btn-view-debug" data-debug="<?= esc($log['debug_log']) ?>">
                                                    <i class="bi bi-bug me-1"></i> Debug
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: Trigger Events Guide -->
        <div class="tab-pane fade" id="tab-event-triggers">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    <span>System Trigger Events</span>
                </h5>
                <p class="text-muted small mb-4">Emails are dynamically dispatched from the platform under the following transaction conditions:</p>

                <div class="row g-4">
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
            </div>
        </div>
    </div>
</div>

<!-- Modal: SMTP Debug logs -->
<div class="modal fade" id="debugEmailModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-bug me-2"></i>SMTP Debug Logs</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2">The SMTP server rejected connection or handshake request. Detailed logs:</p>
                <pre class="bg-dark text-light p-3 rounded small overflow-auto" id="debugLogsArea" style="max-height: 400px; white-space: pre-wrap;"></pre>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle SMTP fields visibility based on protocol selection
        function toggleSmtpFields() {
            var protocol = $('#mailProtocol').val();
            if (protocol === 'smtp') {
                $('.smtp-field').show().find('input, select').prop('disabled', false);
            } else {
                $('.smtp-field').hide().find('input, select').prop('disabled', true);
            }
        }

        $('#mailProtocol').on('change', toggleSmtpFields);
        toggleSmtpFields();

        // Save SMTP Configurations
        $('#formAdminSmtp').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/smtp/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Configurations');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Configurations');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });

        // Test Email configuration
        $('#btnTestEmail').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Testing...');

            $.post(BASE_URL + 'admin/smtp/test', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Test Sample Email');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Test Sample Email');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast(err, 'danger');
                if (xhr.responseJSON && xhr.responseJSON.debug) {
                    $('#debugLogsArea').text(xhr.responseJSON.debug);
                    new bootstrap.Modal(document.getElementById('debugEmailModal')).show();
                }
            });
        });

        // View Debug Logs from history
        $('.btn-view-debug').on('click', function() {
            var debugInfo = $(this).data('debug');
            $('#debugLogsArea').text(debugInfo);
            new bootstrap.Modal(document.getElementById('debugEmailModal')).show();
        });
    });
</script>
<?php $this->endSection() ?>

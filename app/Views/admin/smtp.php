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
                    <a class="nav-link active rounded-pill px-4 small" href="<?= base_url('admin/smtp') ?>">
                        <i class="bi bi-gear me-1"></i> SMTP Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-4 small text-dark" href="<?= base_url('admin/sent-mails') ?>">
                        <i class="bi bi-envelope-paper me-1"></i> Sent Mail History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill px-4 small text-dark" href="<?= base_url('admin/trigger-events') ?>">
                        <i class="bi bi-lightning me-1"></i> Trigger Events
                    </a>
                </li>
            </ul>
        </div>
    </div>

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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle SMTP fields based on protocol
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

        // Save SMTP configs
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
                showToast('Failed to save SMTP configurations: ' + err, 'danger');
            });
        });

        // Test SMTP dispatch
        $('#btnTestEmail').on('click', function() {
            var btn = $(this);
            var originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Dispatched...');

            $.post(BASE_URL + 'admin/smtp/test', {}, function(res) {
                btn.prop('disabled', false).html(originalHtml);
                showToast(res.message, 'success');
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Test email failed: ' + err, 'danger');
            });
        });
    });
</script>
<?= $this->endSection() ?>

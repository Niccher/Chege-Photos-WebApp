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
                    <a class="nav-link active rounded-pill px-4 small" href="<?= base_url('admin/sent-mails') ?>">
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
                                        <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-check-circle me-1"></i>Delivered</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-semibold"><i class="bi bi-x-circle me-1"></i>Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= esc($log['sent_at']) ?></td>
                                <td class="text-end">
                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-0.5 btn-view-debug" data-debug="<?= esc(!empty($log['debug_log']) ? $log['debug_log'] : 'Sent successfully.') ?>">
                                        <i class="bi bi-eye me-1"></i> View Trail
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

<!-- Modal: Trail Viewer -->
<div class="modal fade" id="debugModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-light">
                <h6 class="modal-title fw-bold"><i class="bi bi-terminal me-2"></i>Outbound SMTP Communication Trail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <pre class="bg-dark text-success p-3 rounded font-monospace small mb-0" id="debugContent" style="max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;"></pre>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('.btn-view-debug').on('click', function() {
            var dbg = $(this).data('debug');
            $('#debugContent').text(dbg);
            new bootstrap.Modal(document.getElementById('debugModal')).show();
        });
    });
</script>
<?= $this->endSection() ?>

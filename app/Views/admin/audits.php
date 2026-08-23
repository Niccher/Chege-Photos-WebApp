<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-0" style="color: var(--text-primary);">Security &amp; Audit Logs</h2>
                <p class="text-muted small mb-0">Monitor admin and user authentication events, critical updates, and system data resets.</p>
            </div>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload();">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Logs
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-success"></i>
                    <span>Audit Log History (Last 100 entries)</span>
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle table-hover text-nowrap small mb-0">
                        <thead>
                            <tr class="text-muted">
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action Event</th>
                                <th>Status</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-folder-x fs-1 d-block mb-2"></i>
                                        No security auditing logs registered yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="font-monospace text-muted"><?= esc($log['created_at']) ?></td>
                                        <td>
                                            <?php if ($log['username']): ?>
                                                <span class="fw-bold"><i class="bi bi-person-fill text-muted me-1"></i><?= esc($log['username']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted italic"><i class="bi bi-cpu-fill text-muted me-1"></i>SYSTEM / GUEST</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark bg-light px-2 py-1 rounded small border">
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
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?= esc($log['user_agent']) ?>">
                                                <?= esc($log['user_agent'] ?: 'None') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['details']): ?>
                                                <button class="btn btn-outline-info btn-xs py-0 px-2 rounded-pill small" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#details-<?= $log['id'] ?>">
                                                    View Details
                                                </button>
                                                <div class="collapse mt-2" id="details-<?= $log['id'] ?>">
                                                    <pre class="bg-light p-2 rounded text-dark font-monospace text-xs mb-0 overflow-auto" style="max-width: 300px; max-height: 200px;"><?= esc(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT)) ?></pre>
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
        </div>
    </div>
</div>
<?php $this->endSection() ?>

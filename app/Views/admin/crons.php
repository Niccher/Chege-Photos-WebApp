<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-3">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">System Tasks &amp; Cron Jobs</h2>
            <p class="text-muted small mb-0">Configure dynamic schedules, manage background processes, and inspect task execution history logs.</p>
        </div>
    </div>

    <!-- Navigation Pills -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills gap-2 rounded-card p-1" style="background: rgba(0,0,0,0.03); max-width: max-content;">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4 small" data-bs-toggle="pill" data-bs-target="#tab-cron-schedules" type="button">
                        <i class="bi bi-clock me-1"></i> Task Schedules
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4 small" data-bs-toggle="pill" data-bs-target="#tab-cron-history" type="button">
                        <i class="bi bi-clock-history me-1"></i> Execution Logs
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <!-- TAB 1: Task Schedules -->
        <div class="tab-pane fade show active" id="tab-cron-schedules">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <form id="formAdminCrons">
                            <h5 class="mb-4 d-flex align-items-center gap-2">
                                <i class="bi bi-sliders text-primary"></i>
                                <span>Task Configurations</span>
                            </h5>

                            <!-- Task 1: ML Face Clustering -->
                            <div class="p-3 border rounded mb-4" style="border-color: var(--border-color) !important; background: rgba(0, 0, 0, 0.01);">
                                <h6 class="small fw-bold mb-1">1. ML Face Clustering (`ml:cluster`)</h6>
                                <p class="text-muted small mb-3">Re-groups unassigned face embeddings into unique identified People clusters.</p>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold mb-1">Cron Expression</label>
                                        <input type="text" name="mlCluster" class="form-control bg-light border-0 py-2 font-monospace" value="<?= esc($settings['mlCluster']) ?>" required placeholder="e.g. 0 * * * *">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-1">Preset Quick-select</label>
                                        <select class="form-select bg-light border-0 py-2 preset-select" data-target="mlCluster">
                                            <option value="">-- Select Preset --</option>
                                            <option value="*/10 * * * *">Every 10 minutes</option>
                                            <option value="0 * * * *">Hourly (Default)</option>
                                            <option value="0 0 * * *">Daily at midnight</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Task 2: Trash Auto-Purge -->
                            <div class="p-3 border rounded mb-4" style="border-color: var(--border-color) !important; background: rgba(0, 0, 0, 0.01);">
                                <h6 class="small fw-bold mb-1">2. Trash Auto-Purge (`trash:purge`)</h6>
                                <p class="text-muted small mb-3">Cleans soft-deleted user photos exceeding the retention storage policy threshold.</p>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold mb-1">Cron Expression</label>
                                        <input type="text" name="trashPurge" class="form-control bg-light border-0 py-2 font-monospace" value="<?= esc($settings['trashPurge']) ?>" required placeholder="e.g. 0 2 * * *">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-1">Preset Quick-select</label>
                                        <select class="form-select bg-light border-0 py-2 preset-select" data-target="trashPurge">
                                            <option value="">-- Select Preset --</option>
                                            <option value="0 * * * *">Hourly</option>
                                            <option value="0 2 * * *">Daily at 2 AM (Default)</option>
                                            <option value="0 2 * * 0">Weekly on Sundays</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Task 3: Temp Uploads Cleanup -->
                            <div class="p-3 border rounded mb-4" style="border-color: var(--border-color) !important; background: rgba(0, 0, 0, 0.01);">
                                <h6 class="small fw-bold mb-1">3. Stale Temp Uploads (`storage:clean-temp`)</h6>
                                <p class="text-muted small mb-3">Clears stale exports and incomplete temporary chunk directories older than 24 hours.</p>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold mb-1">Cron Expression</label>
                                        <input type="text" name="cleanTemp" class="form-control bg-light border-0 py-2 font-monospace" value="<?= esc($settings['cleanTemp']) ?>" required placeholder="e.g. 30 1 * * *">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small fw-bold mb-1">Preset Quick-select</label>
                                        <select class="form-select bg-light border-0 py-2 preset-select" data-target="cleanTemp">
                                            <option value="">-- Select Preset --</option>
                                            <option value="0 * * * *">Hourly</option>
                                            <option value="30 1 * * *">Daily at 1:30 AM (Default)</option>
                                            <option value="0 0 1 * *">Monthly on the 1st</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Form submit -->
                            <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill">
                                    <i class="bi bi-check-lg me-1"></i> Save Task Schedules
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Task Scheduler</h6>
                        <p class="text-muted small mb-2" style="line-height: 1.6;">
                            *   The platform runs a master cron job runner inside the container that triggers every minute.
                        </p>
                        <p class="text-muted small mb-2" style="line-height: 1.6;">
                            *   Schedules configured here are evaluated dynamically by this runner. If the current server time matches the cron expression, the task executes immediately.
                        </p>
                        <p class="text-muted small" style="line-height: 1.6;">
                            *   Expressions use standard 5-field crontab structures: <code>[minute] [hour] [day-of-month] [month] [day-of-week]</code>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Execution Logs -->
        <div class="tab-pane fade" id="tab-cron-history">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-info"></i>
                    <span>Cron Run History</span>
                </h5>

                <div class="table-responsive">
                    <table class="table align-middle" style="color: var(--text-primary);">
                        <thead>
                            <tr class="text-muted small" style="border-color: var(--border-color) !important;">
                                <th>Job Target</th>
                                <th>Status</th>
                                <th>Result / Output</th>
                                <th>Duration</th>
                                <th>Run At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cronLogs)): ?>
                                <tr style="border-color: var(--border-color) !important;">
                                    <td colspan="5" class="text-center py-4 text-muted small">No background executions recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cronLogs as $log): ?>
                                    <tr style="border-color: var(--border-color) !important;">
                                        <td class="fw-bold small"><code><?= esc($log['job_name']) ?></code></td>
                                        <td>
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 small">Success</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1 small">Failed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small text-break"><?= esc($log['output']) ?></td>
                                        <td class="small"><?= number_format($log['duration_seconds'], 3) ?>s</td>
                                        <td class="small text-muted"><?= esc($log['run_at']) ?></td>
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

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quick presets sync
        $('.preset-select').on('change', function() {
            var target = $(this).data('target');
            var val = $(this).val();
            if (val) {
                $('input[name="' + target + '"]').val(val);
            }
        });

        // Save Cron Configurations
        $('#formAdminCrons').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/crons/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Task Schedules');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Task Schedules');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save schedules: ' + err, 'danger');
            });
        });
    });
</script>
<?php $this->endSection() ?>

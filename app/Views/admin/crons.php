<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row align-items-center mb-4">
        <div class="col-md-7">
            <h2 class="h4 mb-1" style="color: var(--text-primary);">Automated Background Tasks &amp; Crons</h2>
            <p class="text-muted small mb-0">Monitor scheduled daemon routines, inspect last execution outputs, and adjust automated task intervals.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex gap-2 justify-content-md-end">
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload();">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Status
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" id="btnRunAllCrons">
                <i class="bi bi-play-circle-fill me-1"></i> Run Due Tasks Now
            </button>
        </div>
    </div>

    <!-- Daemon & Server Runtime Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-3 p-md-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4 d-flex align-items-center gap-3">
                        <div class="p-3 rounded-circle <?= $daemonActive ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' ?>">
                            <i class="bi <?= $daemonActive ? 'bi-broadcast-pin fs-3' : 'bi-clock-history fs-3' ?>"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0">Cron Service Status</h6>
                                <?php if ($daemonActive): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-0.5 small">
                                        <i class="bi bi-circle-fill me-1 small" style="font-size: 8px;"></i> ACTIVE &amp; RUNNING
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2 py-0.5 small">
                                        <i class="bi bi-circle-fill me-1 small" style="font-size: 8px;"></i> READY / ON-DEMAND
                                    </span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-0">Container scheduler evaluates every minute (<code>* * * * *</code>)</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 border-start border-light ps-md-4">
                        <span class="text-muted small d-block">Server Time</span>
                        <span class="fw-bold font-monospace small"><i class="bi bi-clock me-1 text-primary"></i><?= esc($serverTime) ?></span>
                        <span class="badge bg-light text-muted rounded-pill px-2 py-0 ms-1 small"><?= esc($serverTimezone) ?></span>
                    </div>
                    <div class="col-6 col-md-4 border-start border-light ps-md-4">
                        <span class="text-muted small d-block">Configured Background Jobs</span>
                        <span class="fw-bold text-success"><i class="bi bi-check2-circle me-1"></i><?= count($tasks) ?> Tasks Seeded &amp; Active</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills gap-2 rounded-card p-1" style="background: rgba(0,0,0,0.03); max-width: max-content;">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4 small d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tab-cron-schedules" type="button">
                        <i class="bi bi-grid"></i>
                        <span>Created Cron Tasks</span>
                        <span class="badge bg-primary rounded-pill px-2 py-0.5" style="font-size: 11px;"><?= count($tasks) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4 small d-flex align-items-center gap-2" data-bs-toggle="pill" data-bs-target="#tab-cron-history" type="button">
                        <i class="bi bi-clock-history"></i>
                        <span>Execution Logs</span>
                        <span class="badge bg-secondary rounded-pill px-2 py-0.5" style="font-size: 11px;"><?= count($cronLogs) ?></span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <div class="tab-content">
        <!-- TAB 1: Created Cron Tasks Grid -->
        <div class="tab-pane fade show active" id="tab-cron-schedules">
            <form id="formAdminCrons">
                <div class="row g-4">
                    <?php foreach ($tasks as $task): ?>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm rounded-card p-4 h-100 d-flex flex-column" style="background: var(--card-bg); color: var(--text-primary); border-top: 4px solid var(--bs-<?= $task['color'] ?>) !important;">
                                <!-- Header -->
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="p-3 bg-<?= $task['color'] ?> bg-opacity-10 rounded text-<?= $task['color'] ?>">
                                            <i class="bi <?= $task['icon'] ?> fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1"><?= esc($task['name']) ?></h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="text-primary bg-light px-2 py-0.5 rounded font-monospace small"><?= esc($task['command']) ?></code>
                                                <span class="text-muted small">• <?= esc($task['category']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if ($task['last_status'] === 'success'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 small">
                                                <i class="bi bi-check-circle-fill me-1"></i> ACTIVE &amp; RUNNING
                                            </span>
                                        <?php elseif ($task['last_status'] === 'failed'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 small">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> FAILED LAST RUN
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 small">
                                                <i class="bi bi-clock-history me-1"></i> SCHEDULED
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Description -->
                                <p class="text-muted small mb-3"><?= esc($task['description']) ?></p>

                                <!-- Timing & Schedule Info Box -->
                                <div class="rounded p-3 mb-3 bg-light bg-opacity-50 border small">
                                    <div class="row g-2">
                                        <div class="col-sm-6">
                                            <div class="text-muted small mb-1"><i class="bi bi-calendar-event me-1"></i>Execution Schedule:</div>
                                            <div class="fw-bold text-dark mb-1"><?= esc($task['human_schedule']) ?></div>
                                            <div><code class="bg-dark text-warning px-2 py-0.5 rounded font-monospace small"><?= esc($task['expression']) ?></code></div>
                                        </div>
                                        <div class="col-sm-6 border-start border-light ps-sm-3">
                                            <div class="text-muted small mb-1"><i class="bi bi-stopwatch me-1"></i>Next Run:</div>
                                            <div class="fw-bold text-primary mb-1"><?= esc($task['next_run_diff']) ?></div>
                                            <div class="text-muted font-monospace small"><?= esc($task['next_run_at']) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Last Execution Details -->
                                <div class="mb-3 small">
                                    <div class="d-flex justify-content-between align-items-center mb-1 text-muted">
                                        <span><i class="bi bi-clock-history me-1"></i>Last execution: <strong><?= esc($task['last_run_diff']) ?></strong></span>
                                        <span>Duration: <strong><?= esc($task['last_duration']) ?></strong></span>
                                    </div>
                                    <?php if ($task['last_run_at']): ?>
                                        <div class="font-monospace text-muted bg-light p-2 rounded small text-truncate" title="<?= esc($task['last_output']) ?>">
                                            <i class="bi bi-terminal me-1"></i><?= esc($task['last_output']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted font-italic small bg-light p-2 rounded">
                                            Task is queued and waiting for its first scheduled trigger.
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Expandable Edit Schedule Drawer -->
                                <div class="collapse mb-3" id="collapse-edit-<?= esc($task['key']) ?>">
                                    <div class="p-3 border rounded bg-white shadow-sm">
                                        <h6 class="small fw-bold mb-2"><i class="bi bi-pencil-square me-1 text-primary"></i>Adjust Schedule Interval</h6>
                                        <div class="row g-2">
                                            <div class="col-md-7">
                                                <label class="form-label small text-muted mb-1">Crontab Expression</label>
                                                <input type="text" name="<?= esc($task['key']) ?>" class="form-control form-control-sm font-monospace bg-light border" value="<?= esc($task['expression']) ?>" required placeholder="e.g. */5 * * * *">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted mb-1">Preset Quick-select</label>
                                                <select class="form-select form-select-sm bg-light border preset-select" data-target="<?= esc($task['key']) ?>">
                                                    <option value="">-- Presets --</option>
                                                    <?php foreach ($task['presets'] as $val => $label): ?>
                                                        <option value="<?= esc($val) ?>" <?= $val === $task['expression'] ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Buttons -->
                                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center gap-2" style="border-color: var(--border-color) !important;">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-toggle="collapse" data-bs-target="#collapse-edit-<?= esc($task['key']) ?>">
                                        <i class="bi bi-sliders me-1"></i> Edit Interval
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-run-single" data-job="<?= esc($task['command']) ?>">
                                        <i class="bi bi-play-fill me-1"></i> Run Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Global Save Bar -->
                <div class="card border-0 shadow-sm rounded-card p-3 mt-4" style="background: var(--card-bg);">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h6 class="fw-bold mb-0">Apply Schedule Updates</h6>
                            <span class="text-muted small">Updated cron expressions are written to system settings and evaluated immediately on the next scheduler minute.</span>
                        </div>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                            <i class="bi bi-check-lg me-1"></i> Save Task Schedules
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TAB 2: Execution Logs History -->
        <div class="tab-pane fade" id="tab-cron-history">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-info"></i>
                        <span>Cron Execution History Logs</span>
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="location.reload();">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh Logs
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover small" style="color: var(--text-primary);">
                        <thead>
                            <tr class="text-muted small" style="border-color: var(--border-color) !important;">
                                <th>Job Target</th>
                                <th>Status</th>
                                <th>Output Summary</th>
                                <th>Duration</th>
                                <th>Executed At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cronLogs)): ?>
                                <tr style="border-color: var(--border-color) !important;">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-journal-text fs-2 d-block mb-2"></i>
                                        No background executions recorded in <code>sys_cron_logs</code> yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cronLogs as $log): ?>
                                    <tr style="border-color: var(--border-color) !important;">
                                        <td class="fw-bold">
                                            <code class="text-primary font-monospace"><?= esc($log['job_name']) ?></code>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'success'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1">
                                                    <i class="bi bi-check-circle me-1"></i> Success
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3 py-1">
                                                    <i class="bi bi-exclamation-triangle me-1"></i> Failed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted font-monospace text-break" style="max-width: 350px;">
                                            <?= esc(mb_strimwidth($log['output'], 0, 85, '...')) ?>
                                        </td>
                                        <td><?= number_format($log['duration_seconds'], 3) ?>s</td>
                                        <td class="text-muted"><?= esc($log['run_at']) ?></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 btn-view-cron-log"
                                                data-job="<?= esc($log['job_name']) ?>"
                                                data-status="<?= esc($log['status']) ?>"
                                                data-output="<?= esc($log['output']) ?>"
                                                data-duration="<?= number_format($log['duration_seconds'], 3) ?>s"
                                                data-time="<?= esc($log['run_at']) ?>">
                                                <i class="bi bi-eye me-1"></i> View Output
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

<!-- Modal: Cron Log Output Viewer -->
<div class="modal fade" id="cronLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title h6 fw-bold mb-0">
                    <i class="bi bi-terminal me-2 text-primary"></i> Execution Log Output Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <span class="fw-bold me-2 font-monospace" id="modalCronJob"></span>
                        <span id="modalCronBadge"></span>
                    </div>
                    <div class="small text-muted">
                        <span id="modalCronTime"></span> | Duration: <span id="modalCronDuration" class="fw-bold text-dark"></span>
                    </div>
                </div>
                <label class="form-label small fw-bold text-muted">Raw Log Stream Output:</label>
                <pre class="bg-dark text-success p-3 rounded font-monospace small mb-0" id="modalCronOutput" style="max-height: 350px; overflow-y: auto; white-space: pre-wrap; word-break: break-word;"></pre>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
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

        // Open Cron Log Modal
        $('.btn-view-cron-log').on('click', function() {
            var btn = $(this);
            $('#modalCronJob').text(btn.data('job'));
            $('#modalCronTime').text(btn.data('time'));
            $('#modalCronDuration').text(btn.data('duration'));
            $('#modalCronOutput').text(btn.data('output'));

            var status = btn.data('status');
            var badgeHtml = status === 'success'
                ? '<span class="badge bg-success rounded-pill px-3">Success</span>'
                : '<span class="badge bg-danger rounded-pill px-3">Failed</span>';
            $('#modalCronBadge').html(badgeHtml);

            var modal = new bootstrap.Modal(document.getElementById('cronLogModal'));
            modal.show();
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
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Task Schedules');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save schedules: ' + err, 'danger');
            });
        });

        // Run single job manually
        $('.btn-run-single').on('click', function() {
            var btn = $(this);
            var job = btn.data('job');
            var originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Running...');

            $.post(BASE_URL + 'admin/crons/run-job', { job: job }, function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Job run failed: ' + err, 'danger');
            });
        });

        // Run all due crons manually
        $('#btnRunAllCrons').on('click', function() {
            var btn = $(this);
            var originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Executing...');

            $.post(BASE_URL + 'admin/crons/run-all', {}, function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Cron runner execution failed: ' + err, 'danger');
            });
        });
    });
</script>
<?php $this->endSection() ?>

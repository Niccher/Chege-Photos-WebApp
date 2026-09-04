<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-2">
    <!-- Top Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Container Telemetry &amp; Health</h1>
            <p class="text-muted small mb-0">Near-realtime RAM utilization, CPU performance, and Railway OOM prevention monitoring.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="toggleAutoRefresh" checked style="cursor: pointer;">
                <label class="form-check-label small fw-semibold text-muted" for="toggleAutoRefresh">Auto-Refresh (3s)</label>
            </div>
            <button id="btnManualRefresh" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Now
            </button>
            <span id="lastUpdatedBadge" class="badge bg-secondary bg-opacity-25 text-muted border border-secondary font-monospace small py-2 px-3">Syncing...</span>
        </div>
    </div>

    <!-- Railway OOM Warning Alert (Dynamic) -->
    <div id="oomAlertBanner" class="alert alert-danger d-none align-items-center shadow-sm rounded-3 mb-4" role="alert" style="border-left: 5px solid #dc3545;">
        <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-danger"></i>
        <div class="flex-grow-1">
            <h6 class="fw-bold mb-1 text-danger">High Memory Usage Warning — Railway OOM Risk</h6>
            <p class="small mb-0 text-danger opacity-75" id="oomAlertMessage">
                One or more containers exceed 85% allocated RAM. Railway may terminate and restart containers exceeding hard memory limits.
            </p>
        </div>
    </div>

    <!-- 4 Container Resource Cards -->
    <div class="row g-4 mb-4">
        <!-- 1. WebApp Container -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: var(--card-bg); border: 1px solid var(--border-color) !important;">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary px-2 py-1 small"><i class="bi bi-globe me-1"></i>WebApp</span>
                    <span id="webStatusBadge" class="badge bg-success rounded-pill px-2">Online</span>
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">PHP-FPM / Nginx Container</h6>
                    
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">RAM Utilization</span>
                        <span id="webMemPct" class="fw-bold fs-5" style="color: var(--text-primary);">0%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px; background: var(--border-color);">
                        <div id="webMemProgressBar" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3 font-monospace">
                        <span>Used: <strong id="webMemUsed" style="color: var(--text-primary);">0 MB</strong></span>
                        <span>Limit: <span id="webMemLimit">512 MB</span></span>
                    </div>

                    <hr class="opacity-15 my-2">

                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>PHP Peak Memory:</span>
                        <strong id="webPhpPeak" class="font-monospace" style="color: var(--text-primary);">0 MB</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Disk Usage:</span>
                        <strong id="webDiskUsage" class="font-monospace" style="color: var(--text-primary);">0 / 0 GB</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>System Load (1m):</span>
                        <strong id="webLoadAvg" class="font-monospace" style="color: var(--text-primary);">0.00</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ML Microservice Container -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: var(--card-bg); border: 1px solid var(--border-color) !important;">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-info bg-opacity-25 text-info border border-info px-2 py-1 small"><i class="bi bi-cpu me-1"></i>ML Service</span>
                    <span id="mlStatusBadge" class="badge bg-secondary rounded-pill px-2">Checking...</span>
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">FastAPI / PyTorch Engine</h6>
                    
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">Process RSS RAM</span>
                        <span id="mlMemPct" class="fw-bold fs-5" style="color: var(--text-primary);">0%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px; background: var(--border-color);">
                        <div id="mlMemProgressBar" class="progress-bar bg-info" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3 font-monospace">
                        <span>Used: <strong id="mlMemUsed" style="color: var(--text-primary);">0 MB</strong></span>
                        <span>Total: <span id="mlMemLimit">512 MB</span></span>
                    </div>

                    <hr class="opacity-15 my-2">

                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>CPU Usage:</span>
                        <strong id="mlCpuPct" class="font-monospace" style="color: var(--text-primary);">0%</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Process Uptime:</span>
                        <strong id="mlUptime" class="font-monospace" style="color: var(--text-primary);">0m</strong>
                    </div>
                    <div class="small text-muted pt-2 d-flex flex-wrap gap-1" id="mlModelsList">
                        <span class="badge bg-secondary bg-opacity-25 text-muted small">Loading models...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Qdrant Vector DB -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: var(--card-bg); border: 1px solid var(--border-color) !important;">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-purple bg-opacity-25 border px-2 py-1 small" style="color: #7c3aed; border-color: #7c3aed !important;"><i class="bi bi-diagram-3 me-1"></i>Qdrant</span>
                    <span id="qdrantStatusBadge" class="badge bg-secondary rounded-pill px-2">Checking...</span>
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">Vector Database</h6>
                    
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">RAM Utilization</span>
                        <span id="qdrantMemPct" class="fw-bold fs-5" style="color: var(--text-primary);">0%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px; background: var(--border-color);">
                        <div id="qdrantMemProgressBar" class="progress-bar" role="progressbar" style="width: 0%; background-color: #7c3aed;"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3 font-monospace">
                        <span>Used: <strong id="qdrantMemUsed" style="color: var(--text-primary);">0 MB</strong></span>
                        <span>Estimated Limit: 512 MB</span>
                    </div>

                    <hr class="opacity-15 my-2">

                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Indexed Vectors:</span>
                        <strong id="qdrantVectors" class="font-monospace" style="color: var(--text-primary);">0</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Collections:</span>
                        <strong id="qdrantCollections" class="font-monospace" style="color: var(--text-primary);">0</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Port &amp; Protocol:</span>
                        <strong class="font-monospace" style="color: var(--text-primary);">6333 / REST &amp; gRPC</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. MySQL Database -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: var(--card-bg); border: 1px solid var(--border-color) !important;">
                <div class="card-header bg-transparent border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning px-2 py-1 small"><i class="bi bi-database me-1"></i>MySQL</span>
                    <span id="dbStatusBadge" class="badge bg-success rounded-pill px-2">Online</span>
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase fw-bold mb-3">InnoDB Storage Engine</h6>
                    
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="small text-muted">Buffer Pool Used</span>
                        <span id="dbMemPct" class="fw-bold fs-5" style="color: var(--text-primary);">0%</span>
                    </div>
                    <div class="progress mb-3" style="height: 8px; background: var(--border-color);">
                        <div id="dbMemProgressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3 font-monospace">
                        <span>Pool Data: <strong id="dbMemUsed" style="color: var(--text-primary);">0 MB</strong></span>
                        <span>Pool Size: <span id="dbMemLimit">128 MB</span></span>
                    </div>

                    <hr class="opacity-15 my-2">

                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Total DB Size:</span>
                        <strong id="dbTotalSize" class="font-monospace" style="color: var(--text-primary);">0 MB</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Active Connections:</span>
                        <strong id="dbThreads" class="font-monospace" style="color: var(--text-primary);">1 thread</strong>
                    </div>
                    <div class="small text-muted py-1 d-flex justify-content-between">
                        <span>Storage Type:</span>
                        <strong class="font-monospace" style="color: var(--text-primary);">InnoDB / utf8mb4</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informational Footer Tips -->
    <div class="card border-0 rounded-4 p-4" style="background: var(--input-bg); border: 1px solid var(--border-color) !important;">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-info-circle text-primary fs-4 mt-1"></i>
            <div>
                <h6 class="fw-bold mb-1">About Telemetry &amp; Memory Safeguards</h6>
                <p class="small text-muted mb-2">
                    Railway container instances operate inside Linux control groups (cgroups v2). If a service exceeds 100% of its provisioned container memory, the Linux kernel invokes the OOM Killer and abruptly restarts the container.
                </p>
                <p class="small text-muted mb-0">
                    This dashboard continuously samples real-time memory pressure across all four backend nodes. If memory reaches <strong>85%</strong>, an automated alert appears to provide timely visibility before any interruption occurs.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let pollTimer = null;
        const POLL_INTERVAL_MS = 3000;

        function fetchTelemetry() {
            $.ajax({
                url: BASE_URL + 'admin/telemetry/stats',
                method: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        renderTelemetry(res);
                    }
                },
                error: function (xhr) {
                    $('#lastUpdatedBadge').text('Polling error').removeClass('border-secondary').addClass('border-danger text-danger');
                }
            });
        }

        function renderTelemetry(data) {
            $('#lastUpdatedBadge').text('Updated: ' + data.timestamp).removeClass('border-danger text-danger').addClass('border-secondary text-muted');

            // OOM Warning Banner
            if (data.oom_warning) {
                $('#oomAlertBanner').removeClass('d-none').addClass('d-flex');
                $('#oomAlertMessage').html('Elevated memory utilization detected in: <strong>' + data.oom_services.join(', ') + '</strong>. Consider scaling or reviewing heavy background jobs.');
            } else {
                $('#oomAlertBanner').addClass('d-none').removeClass('d-flex');
            }

            const s = data.services;

            // 1. WebApp
            if (s.webapp) {
                const w = s.webapp;
                $('#webMemPct').text(w.percent + '%');
                $('#webMemProgressBar').css('width', Math.min(w.percent, 100) + '%')
                    .toggleClass('bg-danger', w.percent >= 85)
                    .toggleClass('bg-warning', w.percent >= 70 && w.percent < 85)
                    .toggleClass('bg-primary', w.percent < 70);
                $('#webMemUsed').text(w.used_mb + ' MB');
                $('#webMemLimit').text(w.limit_mb + ' MB');
                $('#webPhpPeak').text(w.php_peak_mb + ' MB');
                $('#webDiskUsage').text(w.disk_used_gb + ' / ' + w.disk_total_gb + ' GB');
                $('#webLoadAvg').text(w.load_avg_1m);
                $('#webStatusBadge').attr('class', 'badge rounded-pill px-2 ' + (w.status === 'online' ? 'bg-success' : 'bg-danger')).text(w.status);
            }

            // 2. ML Service
            if (s.ml) {
                const m = s.ml;
                $('#mlMemPct').text(m.percent + '%');
                $('#mlMemProgressBar').css('width', Math.min(m.percent, 100) + '%')
                    .toggleClass('bg-danger', m.percent >= 85)
                    .toggleClass('bg-warning', m.percent >= 70 && m.percent < 85)
                    .toggleClass('bg-info', m.percent < 70);
                $('#mlMemUsed').text(m.used_mb + ' MB');
                $('#mlMemLimit').text(m.limit_mb + ' MB');
                $('#mlCpuPct').text(m.cpu_pct + '%');
                
                const uptimeMin = Math.round(m.uptime_s / 60);
                $('#mlUptime').text(uptimeMin > 60 ? (Math.round(uptimeMin / 6) / 10) + 'h' : uptimeMin + 'm');

                let statusBadgeClass = 'bg-secondary';
                if (m.status === 'online') statusBadgeClass = 'bg-success';
                else if (m.status === 'degraded') statusBadgeClass = 'bg-warning text-dark';
                else statusBadgeClass = 'bg-danger';
                $('#mlStatusBadge').attr('class', 'badge rounded-pill px-2 ' + statusBadgeClass).text(m.status);

                if (m.models) {
                    let tagsHtml = '';
                    tagsHtml += `<span class="badge ${m.models.face_insightface ? 'bg-success bg-opacity-25 text-success border border-success' : 'bg-secondary bg-opacity-25 text-muted'}"><i class="bi bi-person-bounding-box me-1"></i>Face</span>`;
                    tagsHtml += `<span class="badge ${m.models.clip_semantic ? 'bg-primary bg-opacity-25 text-primary border border-primary' : 'bg-secondary bg-opacity-25 text-muted'}"><i class="bi bi-search me-1"></i>CLIP</span>`;
                    tagsHtml += `<span class="badge ${m.models.yolov8_objects ? 'bg-info bg-opacity-25 text-info border border-info' : 'bg-secondary bg-opacity-25 text-muted'}"><i class="bi bi-tag me-1"></i>YOLO</span>`;
                    $('#mlModelsList').html(tagsHtml);
                }
            }

            // 3. Qdrant — purple accent, fixed logic: danger only when >85%, not when healthy
            if (s.qdrant) {
                const q = s.qdrant;
                $('#qdrantMemPct').text(q.percent + '%');
                const qdrantBar = $('#qdrantMemProgressBar');
                qdrantBar.css('width', Math.min(q.percent, 100) + '%');
                if (q.percent >= 85) {
                    qdrantBar.css('background-color', '#dc3545');
                } else if (q.percent >= 70) {
                    qdrantBar.css('background-color', '#fd7e14');
                } else {
                    qdrantBar.css('background-color', '#7c3aed');
                }
                $('#qdrantMemUsed').text(q.used_mb + ' MB');
                $('#qdrantVectors').text(q.vectors.toLocaleString());
                $('#qdrantCollections').text(q.collections);
                $('#qdrantStatusBadge').attr('class', 'badge rounded-pill px-2 ' + (q.status === 'online' ? 'bg-success' : 'bg-danger')).text(q.status);
            }

            // 4. MySQL — green when healthy, warning at 70%, danger at 85%
            if (s.mysql) {
                const my = s.mysql;
                $('#dbMemPct').text(my.percent + '%');
                $('#dbMemProgressBar').css('width', Math.min(my.percent, 100) + '%')
                    .toggleClass('bg-danger', my.percent >= 85)
                    .toggleClass('bg-warning', my.percent >= 70 && my.percent < 85)
                    .toggleClass('bg-success', my.percent < 70);
                $('#dbMemUsed').text(my.used_mb + ' MB');
                $('#dbMemLimit').text(my.limit_mb + ' MB');
                $('#dbTotalSize').text(my.db_size_mb + ' MB');
                $('#dbThreads').text(my.threads + (my.threads === 1 ? ' thread' : ' threads'));
                $('#dbStatusBadge').attr('class', 'badge rounded-pill px-2 ' + (my.status === 'online' ? 'bg-success' : 'bg-danger')).text(my.status);
            }
        }

        function setupPolling() {
            if ($('#toggleAutoRefresh').is(':checked')) {
                if (!pollTimer) {
                    pollTimer = setInterval(fetchTelemetry, POLL_INTERVAL_MS);
                }
            } else {
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            }
        }

        $('#toggleAutoRefresh').on('change', setupPolling);
        $('#btnManualRefresh').on('click', fetchTelemetry);

        // Initial fetch and start polling
        fetchTelemetry();
        setupPolling();
    });
</script>
<?= $this->endSection() ?>

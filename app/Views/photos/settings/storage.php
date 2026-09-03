<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-4">Storage Usage</h5>
    <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
            <div style="max-width: 250px; margin: 0 auto;">
                <canvas id="storageChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex flex-column gap-3">
                <div class="p-3 border shadow-sm storage-stat-panel">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-bold"><i class="bi bi-image me-2 text-primary"></i>Photos</span>
                        <span class="small fw-bold"><?= round($storage['photos'] / 1024 / 1024, 1) ?> MB</span>
                    </div>
                </div>
                <div class="p-3 border shadow-sm storage-stat-panel">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-bold"><i class="bi bi-play-btn me-2 text-success"></i>Videos</span>
                        <span class="small fw-bold"><?= round($storage['videos'] / 1024 / 1024, 1) ?> MB</span>
                    </div>
                </div>
                <div class="p-3 border shadow-sm storage-stat-panel">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-bold text-muted"><i class="bi bi-hdd-network me-2"></i>Total Used</span>
                        <span class="small fw-bold text-muted"><?= round($storage['total'] / 1024 / 1024, 1) ?> MB / <?= esc($storage['limit']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr class="my-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold mb-1"><i class="bi bi-arrow-repeat me-1"></i>Refresh Metadata</h6>
            <p class="text-muted small mb-0">Re-scan all photos to extract the latest EXIF data (GPS, camera info, etc.).</p>
        </div>
        <button class="btn btn-outline-secondary rounded-pill px-3 flex-shrink-0" id="btnRefreshMetadata">
            <i class="bi bi-arrow-repeat me-1"></i> Refresh Now
        </button>
    </div>
</div>

<!-- Storage Optimization Assistant -->
<div class="card border-0 shadow-sm rounded-card p-4 mt-4" style="background: var(--card-bg); color: var(--text-primary);">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1"><i class="bi bi-magic text-warning me-2"></i>Storage Optimization Assistant</h5>
            <p class="text-muted small mb-0">Identify redundant media, purge duplicates, and recover valuable storage quota.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-3 py-1" id="btnScanOptimizer">
            <i class="bi bi-search me-1"></i> Scan Library
        </button>
    </div>

    <div id="optimizerResults" class="d-none">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100" style="border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small"><i class="bi bi-files text-danger me-2"></i>Exact Duplicates</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2" id="optDuplicateCount">0 files</span>
                    </div>
                    <p class="text-muted small mb-3" id="optDuplicateSize">Reclaimable space: 0 MB</p>
                    <button class="btn btn-outline-danger btn-sm rounded-pill w-100" id="btnPurgeDuplicates" disabled>
                        <i class="bi bi-trash me-1"></i> Clean Up Duplicates
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded-3 h-100" style="border-color: var(--border-color) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small"><i class="bi bi-camera-video text-info me-2"></i>Large Videos (&gt; 100 MB)</span>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2" id="optLargeVideoCount">0 files</span>
                    </div>
                    <p class="text-muted small mb-3" id="optLargeVideoSize">Total size: 0 MB</p>
                    <a href="<?= base_url('explore') ?>" class="btn btn-outline-info btn-sm rounded-pill w-100">
                        <i class="bi bi-eye me-1"></i> Review in Explorer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('storageChart').getContext('2d');
        const rs = getComputedStyle(document.documentElement);
        const chartBg = [
            rs.getPropertyValue('--chart-1').trim(),
            rs.getPropertyValue('--chart-2').trim(),
            rs.getPropertyValue('--chart-neutral').trim()
        ];
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Photos', 'Videos', 'Free'],
                datasets: [{
                    data: [
                        <?= $storage['photos'] ?>, 
                        <?= $storage['videos'] ?>, 
                        <?= max(0, (setting('App.storageLimit') ?: (1024 * 1024 * 1024)) - $storage['total']) ?>
                    ],
                    backgroundColor: chartBg,
                    borderWidth: 0,
                    cutout: '75%'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                maintainAspectRatio: true
            }
        });

        $('#btnRefreshMetadata').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Refreshing...');
            $.post(BASE_URL + 'settings/refresh-metadata', {}, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Refresh Now');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Refresh Now');
                showToast('Failed to refresh metadata', 'danger');
            });
        });

        // Scan Optimizer
        $('#btnScanOptimizer').on('click', function() {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Scanning...');
            $.post(BASE_URL + 'settings/storage/scan-clean', {}, function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Rescan Library');
                if (res.status === 'success') {
                    $('#optimizerResults').removeClass('d-none');
                    $('#optDuplicateCount').text(res.duplicates_count + ' duplicate(s)');
                    $('#optDuplicateSize').text('Reclaimable space: ' + res.duplicates_size);
                    $('#optLargeVideoCount').text(res.large_videos_count + ' video(s)');
                    $('#optLargeVideoSize').text('Total size: ' + res.large_videos_size);

                    if (res.duplicates_count > 0) {
                        $('#btnPurgeDuplicates').prop('disabled', false);
                    } else {
                        $('#btnPurgeDuplicates').prop('disabled', true);
                    }
                    showToast('Scan complete! Found ' + res.duplicates_count + ' duplicates.', 'info');
                } else {
                    showToast(res.message || 'Scan failed', 'danger');
                }
            }).fail(function() {
                $btn.prop('disabled', false).html('<i class="bi bi-search me-1"></i> Scan Library');
                showToast('Storage scan failed.', 'danger');
            });
        });

        // Purge Duplicates
        $('#btnPurgeDuplicates').on('click', function() {
            if (!confirm('Move duplicate photos to Trash? The newest copy of each duplicate will be kept.')) {
                return;
            }
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Purging...');
            $.post(BASE_URL + 'settings/storage/purge-duplicates', {}, function(res) {
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message || 'Purge failed', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Clean Up Duplicates');
                }
            }).fail(function() {
                showToast('Purge request failed.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Clean Up Duplicates');
            });
        });
    });
</script>
<?= $this->endSection() ?>

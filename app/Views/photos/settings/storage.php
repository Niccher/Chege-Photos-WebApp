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
    });
</script>
<?= $this->endSection() ?>

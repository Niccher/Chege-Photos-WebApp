<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0 fw-bold">Analytics</h2>
    <p class="text-muted small mb-0">Detailed insights into your photo library and storage usage.</p>
</div>

<!-- 1. Top Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-cloud-check text-primary fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.65rem;">Storage Used</div>
                    <div class="h5 mb-0 fw-bold"><?= $storageUsed ?></div>
                </div>
            </div>
            <div class="mt-2 small text-muted"><?= round($storagePercent, 1) ?>% of limit</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-images text-success fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.65rem;">Total Items</div>
                    <div class="h5 mb-0 fw-bold"><?= number_format($totalCount) ?></div>
                </div>
            </div>
            <div class="mt-2 small text-muted">Ø <?= $avgSizeFormatted ?> per file</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-heart text-warning fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.65rem;">Favorites</div>
                    <div class="h5 mb-0 fw-bold"><?= number_format($favoritesCount) ?></div>
                </div>
            </div>
            <div class="mt-2 small text-muted"><?= $totalCount > 0 ? round($favoritesCount / $totalCount * 100, 1) : 0 ?>% of all items</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-geo-alt text-info fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px; font-size: 0.65rem;">GPS Tagged</div>
                    <div class="h5 mb-0 fw-bold"><?= number_format($gpsCount) ?></div>
                </div>
            </div>
            <div class="mt-2 small text-muted"><?= $totalCount > 0 ? round($gpsCount / $totalCount * 100, 1) : 0 ?>% geotagged</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- 2. Quota Usage & Library Lifespan -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 fw-bold">Storage Distribution</h6>
                <span class="badge bg-<?= ($storagePercent > 90) ? 'danger' : (($storagePercent > 70) ? 'warning' : 'primary') ?> bg-opacity-10 text-<?= ($storagePercent > 90) ? 'danger' : (($storagePercent > 70) ? 'warning' : 'primary') ?> rounded-pill px-3">
                    <?= round($storagePercent, 1) ?>% Full
                </span>
            </div>

            <div class="mb-3">
                <div class="progress rounded-pill mb-2" style="height: 10px;">
                    <div class="progress-bar rounded-pill <?= ($storagePercent > 90) ? 'bg-danger' : (($storagePercent > 70) ? 'bg-warning' : 'bg-primary') ?>"
                         role="progressbar"
                         style="width: <?= $storagePercent ?>%"
                         aria-valuenow="<?= $storagePercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                    <span>Using <?= $storageUsed ?></span>
                    <span>1.00 GB Total Limit</span>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <div class="p-2 border rounded-3">
                        <div class="small text-muted">Images</div>
                        <div class="fw-bold"><?= number_format($imageCount) ?> files</div>
                        <div class="small text-muted"><?= round($imageBytes / 1024 / 1024, 1) ?> MB</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-2 border rounded-3">
                        <div class="small text-muted">Videos</div>
                        <div class="fw-bold"><?= number_format($videoCount) ?> files</div>
                        <div class="small text-muted"><?= round($videoBytes / 1024 / 1024, 1) ?> MB</div>
                    </div>
                </div>
            </div>

            <h6 class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing: 0.5px;">Library Timeline</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody class="small">
                        <tr><td class="ps-0 border-0 text-muted">Library span</td><td class="pe-0 border-0 text-end fw-bold"><?= $lifespanDays ?> days</td></tr>
                        <tr><td class="ps-0 border-0 text-muted">Oldest photo</td><td class="pe-0 border-0 text-end fw-bold"><?= $oldestDate ? date('M j, Y', strtotime($oldestDate)) : '—' ?></td></tr>
                        <tr><td class="ps-0 border-0 text-muted">Newest photo</td><td class="pe-0 border-0 text-end fw-bold"><?= $newestDate ? date('M j, Y', strtotime($newestDate)) : '—' ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Library Stats Cards -->
    <div class="col-lg-5">
        <div class="row g-4">
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-journal-album text-primary fs-4"></i>
                        <div class="small text-muted text-uppercase fw-bold" style="font-size:0.6rem;letter-spacing:0.5px;">Albums</div>
                    </div>
                    <div class="h4 mb-0 fw-bold"><?= number_format($albumCount) ?></div>
                    <div class="small text-muted mt-1">includes smart albums</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-archive text-warning fs-4"></i>
                        <div class="small text-muted text-uppercase fw-bold" style="font-size:0.6rem;letter-spacing:0.5px;">Archived</div>
                    </div>
                    <div class="h4 mb-0 fw-bold"><?= number_format($archivedCount) ?></div>
                    <div class="small text-muted mt-1"><?= $totalCount > 0 ? round($archivedCount / $totalCount * 100, 1) : 0 ?>% of library</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-trash text-danger fs-4"></i>
                        <div class="small text-muted text-uppercase fw-bold" style="font-size:0.6rem;letter-spacing:0.5px;">Trash</div>
                    </div>
                    <div class="h4 mb-0 fw-bold"><?= number_format($trashCount) ?></div>
                    <div class="small text-muted mt-1">items pending deletion</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-share text-info fs-4"></i>
                        <div class="small text-muted text-uppercase fw-bold" style="font-size:0.6rem;letter-spacing:0.5px;">Shares</div>
                    </div>
                    <div class="h4 mb-0 fw-bold"><?= number_format($sharingStats['public'] + $sharingStats['internal']) ?></div>
                    <div class="small text-muted mt-1"><?= $sharingStats['public'] ?> public, <?= $sharingStats['internal'] ?> internal</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. File Types + Camera Models -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h6 class="mb-4 fw-bold">File Types</h6>
            <div class="list-group list-group-flush">
                <?php foreach ($mimeStats as $stat): ?>
                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-0 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light p-2 me-3">
                            <i class="bi bi-file-earmark-<?= strpos($stat['mime_type'], 'video') !== false ? 'play' : 'image' ?> text-primary"></i>
                        </div>
                        <span class="fw-medium small"><?= strtoupper(explode('/', $stat['mime_type'])[1] ?? $stat['mime_type']) ?></span>
                    </div>
                    <span class="badge bg-light text-dark rounded-pill fw-normal"><?= number_format($stat['count']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <h6 class="mb-4 fw-bold">Top Cameras <span class="small text-muted fw-normal">(from EXIF data)</span></h6>
            <?php if (!empty($cameraStats)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-muted">
                        <tr><th class="border-0 ps-0">Camera</th><th class="border-0 text-end pe-0">Photos</th><th class="border-0 text-end pe-0" style="width:40%;">Share</th></tr>
                    </thead>
                    <tbody class="small">
                        <?php $totalCam = array_sum(array_column($cameraStats, 'count')); ?>
                        <?php foreach ($cameraStats as $cam): ?>
                        <tr>
                            <td class="ps-0 border-0 fw-medium"><?= esc($cam['camera'] ?? 'Unknown') ?></td>
                            <td class="pe-0 border-0 text-end"><?= number_format($cam['count']) ?></td>
                            <td class="pe-0 border-0 text-end">
                                <div class="d-flex align-items-center gap-2 justify-content-end">
                                    <div class="progress rounded-pill flex-grow-1" style="height:6px;max-width:100px;">
                                        <div class="progress-bar bg-primary rounded-pill" style="width:<?= round($cam['count'] / max($totalCam, 1) * 100) ?>%"></div>
                                    </div>
                                    <span class="text-muted" style="min-width:36px;"><?= round($cam['count'] / max($totalCam, 1) * 100) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-camera fs-2 d-block mb-2"></i>
                <span>No EXIF camera data found in your photos.</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 5. Charts Row -->
<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 fw-bold">Monthly Uploads (<?= date('Y') ?>)</h6>
            </div>
            <div style="height: 300px;">
                <canvas id="uploadChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 fw-bold">Hourly Activity</h6>
            </div>
            <div style="height: 300px;">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 6. Year-over-Year Growth -->
<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="mb-0 fw-bold">Year-over-Year Growth</h6>
            </div>
            <div style="height: 250px;">
                <canvas id="yearlyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyLabels = <?= json_encode(array_column($monthlyQuery, 'month')) ?>;
    const monthlyData   = <?= json_encode(array_column($monthlyQuery, 'count')) ?>;

    const hourlyLabels = <?= json_encode(array_map(function($h) { return sprintf('%02d:00', $h['hour']); }, $hourlyQuery)) ?>;
    const hourlyData   = <?= json_encode(array_column($hourlyQuery, 'count')) ?>;

    const yearlyLabels = <?= json_encode(array_column($yearlyQuery, 'year')) ?>;
    const yearlyData   = <?= json_encode(array_column($yearlyQuery, 'count')) ?>;

    // Monthly Upload Chart
    const uploadCtx = document.getElementById('uploadChart').getContext('2d');
    const grad = uploadCtx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, 'rgba(66, 133, 244, 0.2)');
    grad.addColorStop(1, 'rgba(66, 133, 244, 0.0)');

    new Chart(uploadCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels.length ? monthlyLabels : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            datasets: [{
                label: 'Uploads',
                data: monthlyData.length ? monthlyData : [0,0,0,0,0,0,0,0,0,0,0,0],
                backgroundColor: '#4285f4',
                borderRadius: 6,
                borderSkipped: false,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#1a1b1e',
                    bodyColor: '#5f6368',
                    borderColor: '#e0e0e0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    titleFont: { size: 14, weight: 'bold' }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f3f4', drawBorder: false }, ticks: { color: '#bdc1c6', padding: 10 } },
                x: { grid: { display: false, drawBorder: false }, ticks: { color: '#bdc1c6', padding: 10 } }
            }
        }
    });

    // Hourly Activity Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hrGrad = hourlyCtx.createLinearGradient(0, 0, 0, 300);
    hrGrad.addColorStop(0, 'rgba(251, 188, 4, 0.2)');
    hrGrad.addColorStop(1, 'rgba(251, 188, 4, 0.0)');

    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: hourlyLabels.length ? hourlyLabels : Array.from({length: 24}, (_,i) => sprintf('%02d:00', i)),
            datasets: [{
                label: 'Photos',
                data: hourlyData.length ? hourlyData : Array(24).fill(0),
                borderColor: '#fbbc04',
                borderWidth: 2,
                backgroundColor: hrGrad,
                fill: true,
                tension: 0.3,
                pointRadius: 0,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#1a1b1e',
                    bodyColor: '#5f6368',
                    borderColor: '#e0e0e0',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f3f4', drawBorder: false }, ticks: { color: '#bdc1c6', padding: 8 } },
                x: { grid: { display: false, drawBorder: false }, ticks: { color: '#bdc1c6', padding: 8, maxTicksLimit: 12 } }
            }
        }
    });

    // Year-over-Year Chart
    const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
    const yrGrad = yearlyCtx.createLinearGradient(0, 0, 0, 250);
    yrGrad.addColorStop(0, 'rgba(52, 168, 83, 0.15)');
    yrGrad.addColorStop(1, 'rgba(52, 168, 83, 0.0)');

    new Chart(yearlyCtx, {
        type: 'line',
        data: {
            labels: yearlyLabels.length ? yearlyLabels : ['—'],
            datasets: [{
                label: 'Items Added',
                data: yearlyData.length ? yearlyData : [0],
                borderColor: '#34a853',
                borderWidth: 3,
                backgroundColor: yrGrad,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#34a853',
                pointRadius: 4,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#1a1b1e',
                    bodyColor: '#5f6368',
                    borderColor: '#e0e0e0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f3f4', drawBorder: false }, ticks: { color: '#bdc1c6', padding: 10 } },
                x: { grid: { display: false, drawBorder: false }, ticks: { color: '#bdc1c6', padding: 10 } }
            }
        }
    });
});
</script>

<?= $this->endSection() ?>

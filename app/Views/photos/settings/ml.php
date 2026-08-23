<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card overflow-hidden" style="background: var(--card-bg); color: var(--text-primary);">
    <!-- Inner pill tabs -->
    <ul class="nav nav-pills mb-0 border-bottom px-4 pt-3 pb-0" id="mlPills" role="tablist" style="background: transparent;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-0 border-0 px-3 py-2 fw-semibold small" id="ml-scan-tab" data-bs-toggle="pill" data-bs-target="#mlScan" type="button" role="tab">
                <i class="bi bi-gear me-1"></i> Scan & Manage
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-0 border-0 px-3 py-2 fw-semibold small" id="ml-about-tab" data-bs-toggle="pill" data-bs-target="#mlAbout" type="button" role="tab">
                <i class="bi bi-info-circle me-1"></i> About
            </button>
        </li>
    </ul>

    <div class="tab-content p-4" id="mlPillsContent">
        <!-- Scan & Manage -->
        <div class="tab-pane fade show active" id="mlScan" role="tabpanel">
            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 border border-opacity-10 rounded-3 text-center">
                        <div class="fs-3 fw-bold text-primary"><i class="bi bi-person-badge"></i> <?= (int) ($mlStats['detected_faces'] ?? 0) ?></div>
                        <div class="small text-muted mt-1">Detected Faces</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border border-opacity-10 rounded-3 text-center">
                        <div class="fs-3 fw-bold text-success"><i class="bi bi-shield-check"></i> <?= (int) ($mlStats['analyzed_images'] ?? 0) ?> / <?= (int) ($mlStats['total_images'] ?? 0) ?></div>
                        <div class="small text-muted mt-1">Analysis Coverage</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border border-opacity-10 rounded-3 text-center">
                        <div class="fs-3 fw-bold text-info"><i class="bi bi-people"></i> <?= (int) ($mlStats['persons'] ?? 0) ?></div>
                        <div class="small text-muted mt-1">Identified Persons</div>
                    </div>
                </div>
            </div>

            <!-- Analyze New Photos -->
            <div class="border rounded-3 p-4 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1 fw-bold"><i class="bi bi-search me-1"></i> Analyze New Photos</h6>
                        <p class="text-muted small mb-0">Find people, identify objects, and index scenes in all newly uploaded photos.</p>
                    </div>
                    <button class="btn btn-primary rounded-pill px-4 flex-shrink-0" id="btnScanUnscanned">
                        <i class="bi bi-play-fill me-1"></i> Analyze New
                    </button>
                </div>
            </div>

            <!-- Re-group Similar Faces -->
            <div class="border rounded-3 p-4 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1 fw-bold"><i class="bi bi-diagram-3 me-1"></i> Re-group Similar Faces</h6>
                        <p class="text-muted small mb-0">Re-organize face groupings to clean up matches and merge duplicate profiles based on face shapes.</p>
                    </div>
                    <button class="btn btn-outline-info rounded-pill px-4 flex-shrink-0" id="btnRecluster">
                        <i class="bi bi-arrow-repeat me-1"></i> Re-group Faces
                    </button>
                </div>
            </div>

            <!-- Danger Zone: Re-analyze Entire Library -->
            <div class="border border-danger rounded-3 p-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <h6 class="mb-1 fw-bold text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Re-analyze Entire Library</h6>
                        <p class="text-muted small mb-0">Deletes all current face and search metadata and re-scans every photo from scratch. This action is irreversible.</p>
                    </div>
                    <button class="btn btn-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#forceRescanModal">
                        <i class="bi bi-arrow-clockwise me-1"></i> Re-analyze All
                    </button>
                </div>
            </div>
        </div>

        <!-- About -->
        <div class="tab-pane fade" id="mlAbout" role="tabpanel">
            <h5 class="mb-1 fw-bold"><i class="bi bi-info-circle me-2"></i>About the AI Engine</h5>
            <p class="text-muted small mb-4">Learn how the background artificial intelligence processes your photo library.</p>

            <div class="row g-3">
                <!-- Face Detection -->
                <div class="col-md-6">
                    <div class="p-3 border border-opacity-10 rounded-3 h-100">
                        <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-person-bounding-box text-primary"></i> Face Recognition
                        </h6>
                        <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                            Finds human faces in photos, extracts facial shapes, and groups identical people together. You can name these groups to quickly find photos of specific friends and family.
                        </p>
                    </div>
                </div>
                <!-- Scene Tagging -->
                <div class="col-md-6">
                    <div class="p-3 border border-opacity-10 rounded-3 h-100">
                        <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-tags text-success"></i> Category Tagging
                        </h6>
                        <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                            Automatically scans your photos for objects, settings, and scenes (like "beach", "sunset", "food", or "cars"). This lets you browse photos by categories without manual labeling.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Force Rescan Modal -->
<div class="modal fade" id="forceRescanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-arrow-clockwise me-2"></i>Re-analyze Entire Library</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> Warning: This takes a long time!</p>
                <p class="text-muted small mb-3">We will clear all current facial index points, objects, and search caches. Then every photo in your library will be completely processed from scratch.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">RESCAN</span> to confirm:</label>
                    <input type="text" id="forceRescanConfirmInput" class="form-control" placeholder="Type RESCAN here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmForceRescan" disabled>
                    <i class="bi bi-arrow-clockwise me-1"></i> Force Rescan
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Scan Unscanned
        $('#btnScanUnscanned').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Scanning...');
            $.post(BASE_URL + 'api/v1/faces/scan-all', function (res) {
                if (res.status === 'success') {
                    var msg = 'Scanned ' + res.processed + ' new photos' + (res.skipped ? ', skipped ' + res.skipped : '') + '.';
                    if (res.errors && res.errors.length) msg += ' ' + res.errors.length + ' error(s).';
                    showToast(msg, res.errors && res.errors.length ? 'warning' : 'success');
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    showToast(res.message || 'Scan failed', 'danger');
                }
            }).fail(function () {
                showToast('Scan request failed.', 'danger');
            }).always(function () {
                $btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> Scan Unscanned');
            });
        });

        // Re-cluster
        $('#btnRecluster').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clustering...');
            $.post(BASE_URL + 'api/v1/faces/cluster', function (res) {
                if (res.status === 'success') {
                    var data = res.data || {};
                    showToast(
                        'Clustering complete — ' + (data.clusters || 0) + ' clusters, ' + (data.assigned || 0) + ' faces assigned.',
                        'success'
                    );
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    showToast(res.message || 'Clustering failed', 'danger');
                }
            }).fail(function () {
                showToast('Clustering request failed.', 'danger');
            }).always(function () {
                $btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Re-cluster');
            });
        });

        // Force Rescan confirm input
        $('#forceRescanConfirmInput').on('input', function () {
            $('#btnConfirmForceRescan').prop('disabled', $(this).val() !== 'RESCAN');
        });

        // Force Rescan
        $('#btnConfirmForceRescan').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Rescanning all...');
            $.post(BASE_URL + 'api/v1/faces/force-scan', function (res) {
                if (res.status === 'success') {
                    $('#forceRescanModal').modal('hide');
                    showToast(
                        'Force rescan complete — ' + res.processed + ' photos processed' +
                        (res.errors && res.errors.length ? ' (' + res.errors.length + ' errors)' : '') + '.',
                        res.errors && res.errors.length ? 'warning' : 'success'
                    );
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    showToast(res.message || 'Force rescan failed', 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Force Rescan');
                }
            }).fail(function () {
                showToast('Force rescan request failed.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Force Rescan');
            });
        });

        // Reset modal input when hidden
        $('#forceRescanModal').on('hidden.bs.modal', function () {
            $(this).find('input[type="text"]').val('');
            $(this).find('.btn-danger').prop('disabled', true);
        });
    });
</script>
<?= $this->endSection() ?>

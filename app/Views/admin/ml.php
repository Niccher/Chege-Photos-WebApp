<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">ML Engine Configurations</h2>
            <p class="text-muted small mb-0">Control Insightface model parameters, vector database spaces, and clustering parameters.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Settings Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <form id="formAdminMl">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary"></i>
                        <span>Model Parameters</span>
                    </h5>

                    <!-- Model Pack Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">InsightFace Model Pack</label>
                        <select name="faceModelPack" class="form-select bg-light border-0 py-2">
                            <option value="buffalo_l" <?= $settings['faceModelPack'] === 'buffalo_l' ? 'selected' : '' ?>>buffalo_l (Large - High Accuracy, GPU recommended)</option>
                            <option value="buffalo_m" <?= $settings['faceModelPack'] === 'buffalo_m' ? 'selected' : '' ?>>buffalo_m (Medium - Balanced)</option>
                            <option value="buffalo_s" <?= $settings['faceModelPack'] === 'buffalo_s' ? 'selected' : '' ?>>buffalo_s (Small - Fast)</option>
                            <option value="buffalo_sc" <?= $settings['faceModelPack'] === 'buffalo_sc' ? 'selected' : '' ?>>buffalo_sc (Smallest - Optimized for CPU execution)</option>
                        </select>
                        <span class="text-muted small">Choosing a new pack triggers a dynamic model weight reload on the FastAPI backend.</span>
                    </div>

                    <!-- Face detection threshold confidence slider -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">RetinaFace Detection Threshold</label>
                            <span class="badge bg-primary rounded-pill" id="detThreshValue"><?= esc($settings['faceDetThresh']) ?></span>
                        </div>
                        <input type="range" class="form-range" name="faceDetThresh" min="0.1" max="1.0" step="0.05" value="<?= esc($settings['faceDetThresh']) ?>" id="detThreshSlider">
                        <span class="text-muted small">Minimum confidence score to extract/register a face.</span>
                    </div>

                    <!-- Minimum cluster size -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">HDBSCAN Minimum Cluster Size</label>
                        <input type="number" class="form-control bg-light border-0 py-2" name="hdbscanMinCluster" min="1" max="20" value="<?= esc($settings['hdbscanMinCluster']) ?>">
                        <span class="text-muted small">Minimum number of facial occurrences required to form a new Person.</span>
                    </div>

                    <!-- Age/gender estimation toggle -->
                    <div class="mb-4">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">Estimate Sensitive Attributes</label>
                                <span class="text-muted small">Perform age and gender estimation during scans.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="includeSensitive" value="1" <?= $settings['includeSensitive'] ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Save submit -->
                    <div class="mt-4 pt-3 border-top" style="border-color: var(--border-color) !important;">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-check-lg me-1"></i> Save ML Parameters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Operational triggers -->
            <div class="card border-0 shadow-sm rounded-card p-4 mt-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    <span>Operational Triggers</span>
                </h5>
                <p class="text-muted small">Administratively invoke batch jobs on the FastAPI service.</p>

                <div class="d-flex flex-column gap-3 mt-3">
                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold">Trigger HDBSCAN Clustering</h6>
                            <p class="text-muted small mb-1">Re-group and align all extracted face vectors.</p>
                            <span class="badge bg-primary bg-opacity-10 text-primary small me-1">Unassigned Faces: <?= esc($mlStats['unassigned']) ?></span>
                            <span class="badge bg-success bg-opacity-10 text-success small">Persons (Clusters): <?= esc($mlStats['total_persons']) ?></span>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnTriggerCluster">
                            <i class="bi bi-diagram-3 me-1"></i> Cluster
                        </button>
                    </div>

                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold text-danger">Reset Face Encodings</h6>
                            <p class="text-muted small mb-1">Wipe vector spaces and MySQL tables clean.</p>
                            <span class="badge bg-danger bg-opacity-10 text-danger small">Total Encodings (Vectors): <?= esc($mlStats['total_encodings']) ?></span>
                        </div>
                        <button class="btn btn-danger btn-sm rounded-pill px-3" id="btnTriggerReset" data-bs-toggle="modal" data-bs-target="#resetMlModal">
                            <i class="bi bi-trash me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Sidebar -->
        <div class="col-lg-5">
            <!-- Health Stats -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-4"><i class="bi bi-cpu text-primary me-2"></i>Microservice Health</h6>
                
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">FastAPI Service:</span>
                        <span class="fw-bold text-<?= $mlHealth['online'] ? 'success' : 'danger' ?>"><?= $mlHealth['online'] ? 'ONLINE' : 'OFFLINE' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">MySQL connection:</span>
                        <span class="fw-bold text-<?= $mlHealth['db'] ? 'success' : 'danger' ?>"><?= $mlHealth['db'] ? 'CONNECTED' : 'DISCONNECTED' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Qdrant Vector DB:</span>
                        <span class="fw-bold text-<?= $mlHealth['qdrant'] ? 'success' : 'danger' ?>"><?= $mlHealth['qdrant'] ? 'CONNECTED' : 'DISCONNECTED' ?></span>
                    </div>
                    <div class="d-flex justify-content-between" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Buffalo-L Weights:</span>
                        <span class="fw-bold text-<?= $mlHealth['models'] ? 'success' : 'danger' ?>"><?= $mlHealth['models'] ? 'LOADED' : 'UNLOADED' ?></span>
                    </div>
                </div>
            </div>

            <!-- Model Info -->
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Model Specifications</h6>
                <div class="small text-muted" style="line-height: 1.7;">
                    <p class="mb-2"><strong>Model Pack:</strong> Insightface Buffalo-L — ResNet-100 backbone trained on MS1MV3 database, creating 512-dimensional float embeddings.</p>
                    <p class="mb-2"><strong>Face Extractor:</strong> RetinaFace running Mobilenet0.25 framework for landmarks estimation.</p>
                    <p class="mb-0"><strong>Vector Space DB:</strong> Qdrant vector engine indexes alignments utilizing HNSW (Hierarchical Navigable Small World) metrics for real-time cosine-similarity searches.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset ML Engine Confirmation Modal -->
<div class="modal fade" id="resetMlModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Reset ML Database</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3">This deletes all extracted face and cluster indexes!</p>
                <p class="text-muted small mb-3">This permanently wipes the Qdrant collections and database face encoding caches. You will need to trigger a library scan to restore facial tags.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">RESET</span> to confirm:</label>
                    <input type="text" id="resetConfirmInput" class="form-control" placeholder="Type RESET here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmReset" disabled>
                    <i class="bi bi-trash me-1"></i> Confirm Reset
                </button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Range label updates
        $('#detThreshSlider').on('input', function() {
            $('#detThreshValue').text($(this).val());
        });

        // Save ML Parameters
        $('#formAdminMl').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            $.post(BASE_URL + 'admin/ml/save', form.serialize(), function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save ML Parameters');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save ML Parameters');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });

        // Handle RESET text confirmation
        $('#resetConfirmInput').on('input', function() {
            var val = $(this).val().trim();
            $('#btnConfirmReset').prop('disabled', val !== 'RESET');
        });

        // Trigger ML clustering
        $('#btnTriggerCluster').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clustering...');
            
            $.post(BASE_URL + 'admin/ml/cluster', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-diagram-3 me-1"></i> Cluster');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-diagram-3 me-1"></i> Cluster');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Clustering failed: ' + err, 'danger');
            });
        });

        // Trigger ML database wipe
        $('#btnConfirmReset').on('click', function() {
            var btn = $(this);
            var modal = bootstrap.Modal.getInstance(document.getElementById('resetMlModal'));
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Resetting...');

            $.post(BASE_URL + 'admin/ml/reset', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Confirm Reset');
                $('#resetConfirmInput').val('');
                modal.hide();
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Confirm Reset');
                modal.hide();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Reset failed: ' + err, 'danger');
            });
        });
    });
</script>
<?php $this->endSection() ?>

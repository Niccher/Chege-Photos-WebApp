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

                    <!-- CLIP Model Name -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">CLIP Model Name (Semantic Search)</label>
                        <input type="text" class="form-control bg-light border-0 py-2" name="clipModelName" value="<?= esc($settings['clipModelName']) ?>">
                        <span class="text-muted small">HuggingFace repository path of the CLIP text/image model to use for semantic search. Changing this triggers a model reload.</span>
                    </div>

                    <!-- YOLOv8 Object Detection Threshold -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">YOLOv8 Tagging Threshold</label>
                            <span class="badge bg-primary rounded-pill" id="objThreshValue"><?= esc($settings['objectDetThresh']) ?></span>
                        </div>
                        <input type="range" class="form-range" name="objectDetThresh" min="0.1" max="1.0" step="0.05" value="<?= esc($settings['objectDetThresh']) ?>" id="objThreshSlider">
                        <span class="text-muted small">Minimum confidence score required to auto-tag a photo with object/scene labels on upload.</span>
                    </div>

                    <!-- Minimum cluster size -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">HDBSCAN Minimum Cluster Size</label>
                        <input type="number" class="form-control bg-light border-0 py-2" name="hdbscanMinCluster" min="1" max="20" value="<?= esc($settings['hdbscanMinCluster']) ?>">
                        <span class="text-muted small">Minimum number of facial occurrences required to form a new Person.</span>
                    </div>

                    <!-- Minimum samples -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">HDBSCAN Minimum Samples</label>
                        <input type="number" class="form-control bg-light border-0 py-2" name="hdbscanMinSamples" min="1" max="20" value="<?= esc($settings['hdbscanMinSamples']) ?>">
                        <span class="text-muted small">The number of samples in a neighborhood for a point to be considered a core point.</span>
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
                            <span class="badge bg-primary bg-opacity-10 text-primary small me-1" id="badgeUnassignedFaces">Unassigned Faces: <?= esc($mlStats['unassigned']) ?></span>
                            <span class="badge bg-success bg-opacity-10 text-success small" id="badgeTotalPersons">Persons (Clusters): <?= esc($mlStats['total_persons']) ?></span>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnTriggerCluster">
                            <i class="bi bi-diagram-3 me-1"></i> Cluster
                        </button>
                    </div>

                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold text-danger">Reset Face Encodings</h6>
                            <p class="text-muted small mb-1">Wipe vector spaces and MySQL tables clean.</p>
                            <span class="badge bg-danger bg-opacity-10 text-danger small" id="badgeTotalEncodings">Total Encodings (Vectors): <?= esc($mlStats['total_encodings']) ?></span>
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
                    <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Buffalo-L Weights:</span>
                        <span class="fw-bold text-<?= $mlHealth['models'] ? 'success' : 'danger' ?>"><?= $mlHealth['models'] ? 'LOADED' : 'UNLOADED' ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">CLIP Model:</span>
                        <span class="fw-bold text-<?= $mlHealth['clip'] ? 'success' : 'danger' ?>"><?= $mlHealth['clip'] ? 'LOADED' : 'UNLOADED' ?></span>
                    </div>
                    <div class="d-flex justify-content-between" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">YOLOv8 ONNX Model:</span>
                        <span class="fw-bold text-<?= $mlHealth['yolo'] ? 'success' : 'danger' ?>"><?= $mlHealth['yolo'] ? 'LOADED' : 'UNLOADED' ?></span>
                    </div>
                </div>
            </div>

            <!-- ML Security & API Key Management -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock text-warning me-2"></i>ML API Key Security</h6>
                <p class="text-muted small mb-3">All WebApp-to-ML HTTP requests pass this secret authorization key via the <code>X-API-KEY</code> header.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Active API Token Key:</label>
                    <div class="input-group">
                        <input type="text" id="apiKeyDisplay" class="form-control font-monospace small bg-light border-0 py-2" value="<?= esc($apiKey) ?>" readonly>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnCopyKey" title="Copy to clipboard">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill w-100 fw-bold py-2" id="btnRegenerateKey">
                    <i class="bi bi-key-fill me-1"></i> Regenerate API Key
                </button>
            </div>

            <!-- Batch Rescan Engine -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-4"><i class="bi bi-arrow-repeat text-primary me-2"></i>Batch Rescan Engine</h6>
                
                <div class="d-flex flex-column gap-3">
                    <!-- Rescan Faces -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">Faces Processing</span>
                            <span class="badge bg-primary rounded-pill" id="badgeScannedFaces"><?= esc($mlStats['scanned_faces']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm rounded-pill w-50 btn-rescan" data-type="faces" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="faces" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>

                    <!-- Rescan Object Tags -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">YOLOv8 Object Tags</span>
                            <span class="badge bg-success rounded-pill" id="badgeScannedTags"><?= esc($mlStats['scanned_tags']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm rounded-pill w-50 btn-rescan" data-type="tags" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="tags" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>

                    <!-- Rescan CLIP Embeddings -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">CLIP Semantic Vectors</span>
                            <span class="badge bg-info rounded-pill text-dark" id="badgeScannedClips"><?= esc($mlStats['scanned_clips']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-info btn-sm rounded-pill w-50 text-dark btn-rescan" data-type="clip" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="clip" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Model Info -->
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Model Specifications</h6>
                <div class="small text-muted" style="line-height: 1.7;">
                    <p class="mb-2"><strong>Model Pack:</strong> Insightface Buffalo-L — ResNet-100 backbone trained on MS1MV3 database, creating 512-dimensional float embeddings.</p>
                    <p class="mb-2"><strong>Face Extractor:</strong> RetinaFace running Mobilenet0.25 framework for landmarks estimation.</p>
                    <p class="mb-2"><strong>Vector Space DB:</strong> Qdrant vector engine indexes alignments utilizing HNSW (Hierarchical Navigable Small World) metrics for real-time cosine-similarity searches.</p>
                    <p class="mb-2"><strong>Object Detector (YOLOv8):</strong> YOLOv8n (Nano) ONNX model running inside OpenCV DNN to classify images into 80 COCO objects with user-defined confidence thresholds.</p>
                    <p class="mb-0"><strong>Semantic Search (CLIP):</strong> Contrastive Language-Image Pre-training (ViT-B/32) multimodal transformer generates 512-dimensional vector projections to power natural-language queries.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generic Action Confirmation Alert Modal -->
<div class="modal fade" id="actionConfirmModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i> Confirm Operation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-2 fw-bold text-dark fs-6" id="actionConfirmTitle">Are you sure?</p>
                <p class="text-muted small mb-0" id="actionConfirmBody">Please confirm if you wish to proceed with this operation.</p>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnProceedAction">Confirm &amp; Execute</button>
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
        var activeActionCallback = null;
        var confirmModalInstance = new bootstrap.Modal(document.getElementById('actionConfirmModal'));

        function promptConfirmation(title, body, onConfirm) {
            $('#actionConfirmTitle').text(title);
            $('#actionConfirmBody').text(body);
            activeActionCallback = onConfirm;
            confirmModalInstance.show();
        }

        $('#btnProceedAction').on('click', function() {
            confirmModalInstance.hide();
            if (activeActionCallback) {
                activeActionCallback();
                activeActionCallback = null;
            }
        });

        // Copy API key to clipboard
        $('#btnCopyKey').on('click', function() {
            var input = document.getElementById('apiKeyDisplay');
            input.select();
            document.execCommand('copy');
            showToast('API Key copied to clipboard!', 'success');
        });

        // Regenerate API Key
        $('#btnRegenerateKey').on('click', function() {
            promptConfirmation(
                "Regenerate ML Access Token Key?",
                "This will invalidate the current key. Requests sent without the new key header will be rejected.",
                function() {
                    var btn = $('#btnRegenerateKey');
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Regenerating...');
                    $.post(BASE_URL + 'admin/ml/regenerate-key', function(res) {
                        btn.prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i> Regenerate API Key');
                        if (res.status === 'success') {
                            $('#apiKeyDisplay').val(res.apiKey);
                            showToast(res.message, 'success');
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i> Regenerate API Key');
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to regenerate key: ' + err, 'danger');
                    });
                }
            );
        });

        // Range label updates
        $('#detThreshSlider').on('input', function() {
            $('#detThreshValue').text($(this).val());
        });

        $('#objThreshSlider').on('input', function() {
            $('#objThreshValue').text($(this).val());
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
            promptConfirmation(
                "Trigger HDBSCAN Face Clustering?",
                "This will analyze unlocked face vectors and group them into identified Person clusters. Manually named persons will be preserved.",
                function() {
                    var btn = $('#btnTriggerCluster');
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
                }
            );
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

        // Dynamic Polling for ML Job Progress
        var pollInterval = null;
        function startStatsPolling() {
            if (pollInterval) return;
            pollInterval = setInterval(function() {
                $.getJSON(BASE_URL + 'admin/ml/stats', function(res) {
                    if (res.status === 'success' && res.stats) {
                        var stats = res.stats;
                        
                        // Update Rescan Badges
                        $('#badgeScannedFaces').text(stats.scanned_faces + ' / ' + stats.total_photos);
                        $('#badgeScannedTags').text(stats.scanned_tags + ' / ' + stats.total_photos);
                        $('#badgeScannedClips').text(stats.scanned_clips + ' / ' + stats.total_photos);
                        
                        // Update Operational Badges
                        $('#badgeUnassignedFaces').text('Unassigned Faces: ' + stats.unassigned);
                        $('#badgeTotalPersons').text('Persons (Clusters): ' + stats.total_persons);
                        $('#badgeTotalEncodings').text('Total Encodings (Vectors): ' + stats.total_encodings);

                        // Stop polling when fully processed
                        var total = parseInt(stats.total_photos);
                        var facesDone = parseInt(stats.scanned_faces) >= total;
                        var tagsDone = parseInt(stats.scanned_tags) >= total;
                        var clipsDone = parseInt(stats.scanned_clips) >= total;
                        
                        if (facesDone && tagsDone && clipsDone) {
                            clearInterval(pollInterval);
                            pollInterval = null;
                        }
                    }
                });
            }, 5000);
        }

        // Start polling on load if work is in progress
        var initTotal = parseInt('<?= $mlStats['total_photos'] ?>');
        var initFaces = parseInt('<?= $mlStats['scanned_faces'] ?>');
        var initTags = parseInt('<?= $mlStats['scanned_tags'] ?>');
        var initClips = parseInt('<?= $mlStats['scanned_clips'] ?>');
        if (initFaces < initTotal || initTags < initTotal || initClips < initTotal) {
            startStatsPolling();
        }

        // Trigger Rescans with Alert Modal
        $('.btn-rescan').on('click', function() {
            var btn = $(this);
            var type = btn.data('type');
            var mode = btn.data('mode');
            var originalHtml = btn.html();

            var title = mode === 'all'
                ? "FORCE Re-Scan All Images for " + type.toUpperCase() + "?"
                : "Queue Missing " + type.toUpperCase() + " Scans?";

            var body = mode === 'all' 
                ? "This will reset scan status flags for all images and re-process the pipeline sequentially in the background."
                : "This will check for any photos missing " + type.toUpperCase() + " scans and queue them for processing.";

            promptConfirmation(title, body, function() {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Queuing...');

                $.post(BASE_URL + 'admin/ml/rescan', { type: type, mode: mode }, function(res) {
                    btn.prop('disabled', false).html(originalHtml);
                    if (res.status === 'success') {
                        showToast(res.message, 'success');
                        // Start polling to show progress immediately
                        startStatsPolling();
                    } else {
                        showToast(res.message, 'danger');
                    }
                }).fail(function(xhr) {
                    btn.prop('disabled', false).html(originalHtml);
                    var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                    showToast('Failed to trigger scan: ' + err, 'danger');
                });
            });
        });
    });
</script>
<?php $this->endSection() ?>

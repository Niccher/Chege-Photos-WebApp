<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-0" style="color: var(--text-primary);">Admin Console</h2>
                <p class="text-muted small mb-0">System-wide monitoring, configuration, and ML execution controls.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/settings') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-sliders me-1"></i> Configs
                </a>
                <a href="<?= base_url('admin/users') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-people me-1"></i> Users
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-card p-3 text-center h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <i class="bi bi-people display-6 text-primary mb-2"></i>
                <div class="fs-4 fw-bold"><?= esc($stats['users']) ?></div>
                <div class="small text-muted">Total Users</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-card p-3 text-center h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <i class="bi bi-image display-6 text-success mb-2"></i>
                <div class="fs-4 fw-bold"><?= esc($stats['photos']) ?></div>
                <div class="small text-muted">Total Photos</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-card p-3 text-center h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <i class="bi bi-play-btn display-6 text-info mb-2"></i>
                <div class="fs-4 fw-bold"><?= esc($stats['videos']) ?></div>
                <div class="small text-muted">Total Videos</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-card p-3 text-center h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <i class="bi bi-hdd-network display-6 text-warning mb-2"></i>
                <div class="fs-4 fw-bold"><?= esc($stats['storage']) ?></div>
                <div class="small text-muted">Footprint on Disk</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Service status cards -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-cpu text-primary"></i>
                    <span>ML Engine Status</span>
                </h5>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center justify-content-between p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <div>
                            <span class="fw-bold d-block small">Service Status</span>
                            <span class="text-muted small">FastAPI API availability</span>
                        </div>
                        <?php if ($mlHealth['online']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">ONLINE</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">OFFLINE</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <div>
                            <span class="fw-bold d-block small">MySQL Connection</span>
                            <span class="text-muted small">ML access to shared DB</span>
                        </div>
                        <?php if ($mlHealth['db']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">CONNECTED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">DISCONNECTED</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <div>
                            <span class="fw-bold d-block small">Qdrant Vector DB</span>
                            <span class="text-muted small">Cosine similarity database</span>
                        </div>
                        <?php if ($mlHealth['qdrant']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">CONNECTED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">DISCONNECTED</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <div>
                            <span class="fw-bold d-block small">Buffalo-L Models</span>
                            <span class="text-muted small">ResNet-100 weights status</span>
                        </div>
                        <?php if ($mlHealth['models']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">LOADED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">UNLOADED</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ML Quick Controls -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    <span>ML Quick Triggers</span>
                </h5>
                <p class="text-muted small">Administrative actions to invoke background jobs on the FastAPI microservice.</p>
                
                <div class="d-flex flex-column gap-3 mt-4">
                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold">Trigger HDBSCAN Clustering</h6>
                            <p class="text-muted small mb-0">Re-group and align all extracted face vectors.</p>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnTriggerCluster">
                            <i class="bi bi-diagram-3 me-1"></i> Cluster
                        </button>
                    </div>

                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold text-danger">Reset Face Encodings</h6>
                            <p class="text-muted small mb-0">Wipe vector spaces and MySQL tables clean.</p>
                        </div>
                        <button class="btn btn-danger btn-sm rounded-pill px-3" id="btnTriggerReset" data-bs-toggle="modal" data-bs-target="#resetMlModal">
                            <i class="bi bi-trash me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset ML Engine Confirmation Modal -->
<div class="modal fade" id="resetMlModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Reset ML Database</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3">This deletes all extracted face and cluster indexes!</p>
                <p class="text-muted small mb-3">This permanently wipes the Qdrant collections and database face encoding caches. You will need to trigger a full library scan to restore facial tags.</p>
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

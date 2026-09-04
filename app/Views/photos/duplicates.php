<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
function formatBytesDup($bytes, $precision = 1) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h2 class="h4 mb-0 d-flex align-items-center gap-2" style="color: var(--text-primary);">
            <i class="bi bi-copy text-primary"></i>
            <span>Duplicate Photo Detection</span>
        </h2>
        <p class="text-muted small mb-0">Identify exact binary matches and reclaim disk space safely.</p>
    </div>
    <?php if (!empty($duplicateSets)): ?>
        <button class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAutoClean">
            <i class="bi bi-trash me-1"></i> Auto-Clean All (<?= $totalDuplicates ?>)
        </button>
    <?php endif; ?>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-collection text-primary fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Duplicate Sets</span>
                    <h4 class="mb-0 fw-bold" id="statTotalGroups"><?= (int)$totalGroups ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-files text-warning fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Redundant Files</span>
                    <h4 class="mb-0 fw-bold" id="statTotalDuplicates"><?= (int)$totalDuplicates ?></h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-hdd-fill text-success fs-4"></i>
                </div>
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Reclaimable Storage</span>
                    <h4 class="mb-0 fw-bold text-success" id="statReclaimable"><?= formatBytesDup($totalReclaimableBytes) ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (empty($duplicateSets)): ?>
    <div class="text-center py-5">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: rgba(25, 135, 84, 0.15);">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
        </div>
        <h3 class="h5">No Duplicate Photos Found</h3>
        <p class="text-muted">Your photo library is completely clean. All uploaded photos have unique file hashes.</p>
        <a href="<?= base_url() ?>" class="btn btn-outline-primary rounded-pill px-4">Return to Gallery</a>
    </div>
<?php else: ?>
    <!-- Duplicate Groups List -->
    <div class="d-flex flex-column gap-4" id="duplicateGroupsContainer">
        <?php foreach ($duplicateSets as $index => $set): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 duplicate-group-card" data-hash="<?= esc($set['hash']) ?>" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom" style="border-color: var(--border-color) !important;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary font-monospace small">SHA: <?= esc(substr($set['hash'], 0, 12)) ?>...</span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= (int)$set['count'] ?> identical copies</span>
                        <span class="text-muted small"><?= formatBytesDup($set['single_size']) ?> each</span>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1.5 rounded-pill">
                        Reclaim <?= formatBytesDup($set['reclaimable_bytes']) ?>
                    </span>
                </div>

                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 g-3">
                    <?php foreach ($set['photos'] as $pIdx => $photo): ?>
                        <div class="col duplicate-photo-item" id="photo-item-<?= $photo['id'] ?>">
                            <div class="card border h-100 rounded-3 overflow-hidden position-relative" style="background: rgba(0,0,0,0.05); border-color: var(--border-color) !important;">
                                <div class="ratio ratio-1x1 overflow-hidden">
                                    <img src="<?= base_url($photo['thumbnail_path'] ?: $photo['path']) ?>" class="object-fit-cover w-100 h-100" alt="<?= esc($photo['filename']) ?>" loading="lazy">
                                </div>
                                <div class="p-2 small">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-truncate fw-semibold" style="max-width: 120px;" title="<?= esc($photo['filename']) ?>"><?= esc($photo['filename']) ?></span>
                                        <?php if ($pIdx === 0): ?>
                                            <span class="badge bg-primary text-white" style="font-size: 0.65rem;">ORIGINAL</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" style="font-size: 0.65rem;">COPY</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted extra-small mb-2">
                                        <span><?= !empty($photo['taken_at']) ? date('M j, Y', strtotime($photo['taken_at'])) : 'No date' ?></span>
                                        <?php if (!empty($photo['width']) && !empty($photo['height'])): ?>
                                            • <span><?= (int)$photo['width'] ?>x<?= (int)$photo['height'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <?php if ($pIdx === 0): ?>
                                            <button class="btn btn-sm btn-outline-secondary w-100 py-1" disabled style="font-size: 0.75rem;">
                                                <i class="bi bi-check-lg"></i> Keep
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-danger w-100 py-1 btn-trash-single" data-id="<?= $photo['id'] ?>" style="font-size: 0.75rem;">
                                                <i class="bi bi-trash"></i> Move to Trash
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Auto Clean Modal -->
<div class="modal fade" id="modalAutoClean" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-magic text-primary me-2"></i>Auto-Clean Duplicates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Automatically keep one copy of each photo and move all other copies to the Trash bin.
                    You can always restore them from Trash if needed.
                </p>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Retention Strategy</label>
                    <div class="form-check p-3 rounded border mb-2" style="border-color: var(--border-color) !important;">
                        <input class="form-check-input" type="radio" name="cleanStrategy" id="stratOldest" value="keep_oldest" checked>
                        <label class="form-check-label small" for="stratOldest">
                            <strong>Keep Oldest (Original Upload)</strong>
                            <div class="text-muted extra-small">Keeps the first photo you uploaded; trashes later duplicates. Recommended.</div>
                        </label>
                    </div>
                    <div class="form-check p-3 rounded border" style="border-color: var(--border-color) !important;">
                        <input class="form-check-input" type="radio" name="cleanStrategy" id="stratNewest" value="keep_newest">
                        <label class="form-check-label small" for="stratNewest">
                            <strong>Keep Newest</strong>
                            <div class="text-muted extra-small">Keeps the most recent copy; trashes older duplicates.</div>
                        </label>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small rounded mb-0">
                    <i class="bi bi-info-circle-fill me-1"></i> Will move <strong><?= (int)$totalDuplicates ?></strong> redundant photos to Trash, reclaiming approximately <strong><?= formatBytesDup($totalReclaimableBytes) ?></strong>.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnConfirmAutoClean">
                    <i class="bi bi-trash me-1"></i> Clean All Duplicates
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Single trash action
    $(document).on('click', '.btn-trash-single', function() {
        const btn = $(this);
        const photoId = btn.data('id');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post(BASE_URL + 'duplicates/trash/' + photoId, function(res) {
            if (res.status === 'success') {
                const item = $('#photo-item-' + photoId);
                const parentGroup = item.closest('.duplicate-group-card');
                
                item.fadeOut(300, function() {
                    $(this).remove();
                    // If group now only has 1 photo left, remove the group
                    if (parentGroup.find('.duplicate-photo-item').length <= 1) {
                        parentGroup.slideUp(300, function() { $(this).remove(); });
                    }
                });
                showToast('Duplicate moved to Trash', 'success');
            } else {
                showToast('Failed: ' + (res.message || 'Error'), 'danger');
                btn.prop('disabled', false).html('<i class="bi bi-trash"></i> Move to Trash');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="bi bi-trash"></i> Move to Trash');
            showToast('Network error while trashing duplicate', 'danger');
        });
    });

    // Auto-clean action
    $('#btnConfirmAutoClean').on('click', function() {
        const btn = $(this).prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Cleaning...');
        const strategy = $('input[name="cleanStrategy"]:checked').val() || 'keep_oldest';

        $.post(BASE_URL + 'duplicates/autoclean', { strategy: strategy }, function(res) {
            if (res.status === 'success') {
                showToast(res.message || 'Duplicates cleaned successfully!', 'success');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                showToast('Auto-clean failed: ' + (res.message || 'Error'), 'danger');
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Clean All Duplicates');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Clean All Duplicates');
            showToast('Network error during auto-clean', 'danger');
        });
    });
});
</script>

<?= $this->endSection() ?>

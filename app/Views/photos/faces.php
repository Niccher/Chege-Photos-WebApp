<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h2 class="h4 mb-0"><i class="bi bi-people me-2"></i>Faces</h2>
        <p class="text-muted small mb-0">Click a face to see all photos containing it</p>
    </div>
    <button class="btn btn-outline-primary btn-sm" id="btnRescanAll" title="Scan all unprocessed photos for faces">
        <i class="bi bi-search"></i> Rescan All
    </button>
</div>

<?php if (!empty($persons)): ?>
    <h5 class="mb-3">Known Faces</h5>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 mb-4">
        <?php foreach ($persons as $person): ?>
            <?php
            $faceCount = (int) ($person['face_count'] ?? 0);
            $thumb = $person['thumbnail'] ?? null;
            ?>
            <div class="col">
                <a href="<?= base_url('faces/person/' . $person['id']) ?>" class="text-decoration-none">
                    <div class="card h-100 text-center border-0 face-card" 
                         style="background:var(--card-bg);cursor:grab;overflow:hidden;transition: transform 0.2s, border 0.2s; border: 2px solid transparent;"
                         draggable="true"
                         data-type="person"
                         data-person-id="<?= $person['id'] ?>"
                         data-person-name="<?= esc($person['name'] ?: 'Person ' . $person['id']) ?>">
                        <div class="card-body py-3">
                            <?php if ($thumb): ?>
                                <?php
                                $px = ($thumb['x'] / $thumb['pw']) * 100;
                                $py = ($thumb['y'] / $thumb['ph']) * 100;
                                $sw = ($thumb['w'] / $thumb['pw']) * 100;
                                $sh = ($thumb['h'] / $thumb['ph']) * 100;
                                $bsW = 100 / $sw * 100;
                                $bsH = 100 / $sh * 100;
                                ?>
                                <div class="rounded-3 mx-auto mb-2 overflow-hidden"
                                     style="width:100px;height:100px;background:var(--card-bg);pointer-events:none;">
                                    <div style="width:100px;height:100px;background:url(<?= $thumb['url'] ?>) no-repeat;background-size:<?= $bsW ?>% <?= $bsH ?>%;background-position:<?= $px ?>% <?= $py ?>%;"></div>
                                </div>
                            <?php else: ?>
                                <div class="rounded-3 bg-secondary d-flex align-items-center justify-content-center mx-auto mb-2"
                                     style="width:100px;height:100px;font-size:24px;color:#fff;pointer-events:none;">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                            <div class="small fw-medium" style="pointer-events:none;"><?= $faceCount ?> photo<?= $faceCount !== 1 ? 's' : '' ?></div>
                            <?php if ($person['age'] || $person['gender']): ?>
                                <div class="small text-muted" style="pointer-events:none;"><?= $person['age'] ? '~' . $person['age'] . 'y' : '' ?><?= $person['age'] && $person['gender'] ? ', ' : '' ?><?= $person['gender'] ? ucfirst($person['gender']) : '' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($unassignedCount > 0): ?>
    <h5 class="mb-3">Unknown Faces (<?= $unassignedCount ?>)</h5>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
        <?php foreach ($unassigned as $face): ?>
            <?php $thumb = $face['thumbnail'] ?? null; ?>
            <div class="col">
                <div class="card h-100 text-center border-0 face-card-unassigned" 
                     style="background:var(--card-bg);cursor:grab; transition: transform 0.2s;"
                     draggable="true"
                     data-type="face"
                     data-face-id="<?= $face['id'] ?>">
                    <div class="card-body py-3">
                        <a href="<?= base_url('faces/photo/' . $face['photo_id']) ?>" class="text-decoration-none d-block">
                            <?php if ($thumb): ?>
                                <?php
                                $px = ($thumb['x'] / $thumb['pw']) * 100;
                                $py = ($thumb['y'] / $thumb['ph']) * 100;
                                $sw = ($thumb['w'] / $thumb['pw']) * 100;
                                $sh = ($thumb['h'] / $thumb['ph']) * 100;
                                $bsW = 100 / $sw * 100;
                                $bsH = 100 / $sh * 100;
                                ?>
                                <div class="rounded-3 mx-auto mb-2 overflow-hidden"
                                     style="width:100px;height:100px;background:var(--card-bg);pointer-events:none;">
                                    <div style="width:100px;height:100px;background:url(<?= $thumb['url'] ?>) no-repeat;background-size:<?= $bsW ?>% <?= $bsH ?>%;background-position:<?= $px ?>% <?= $py ?>%;"></div>
                                </div>
                            <?php else: ?>
                                <div class="rounded-3 bg-warning bg-opacity-25 d-flex align-items-center justify-content-center mx-auto mb-2"
                                     style="width:100px;height:100px;font-size:24px;color:#856404;pointer-events:none;">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="small fw-medium" style="pointer-events:none;">score: <?= round($face['detection_score'], 2) ?></div>
                        <?php if ($face['age'] || $face['gender']): ?>
                            <div class="small text-muted" style="pointer-events:none;"><?= $face['age'] ? '~' . $face['age'] . 'y' : '' ?><?= $face['age'] && $face['gender'] ? ', ' : '' ?><?= $face['gender'] ? ucfirst($face['gender']) : '' ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($persons) && $unassignedCount === 0): ?>
    <div class="text-center py-5">
        <i class="bi bi-people" style="font-size:4rem;color:#dee2e6;"></i>
        <h3 class="mt-3 text-muted">No faces detected yet</h3>
        <p class="text-muted">Upload photos and run face detection to see faces here.</p>
    </div>
<?php endif; ?>

<!-- Merge Confirmation Modal -->
<div class="modal fade" id="confirmMergeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Merge Faces / Persons</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="confirmMergeText">Are you sure you want to merge these?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnConfirmMerge">Confirm Merge</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
.face-card.drag-over {
    transform: scale(1.05);
    border: 2px dashed #0d6efd !important;
    background: rgba(13, 110, 253, 0.05) !important;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    $('#btnRescanAll').on('click', function() {
        const btn = $(this).prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Scanning...');
        $.post(BASE_URL + 'api/faces/scan-all', function(res) {
            if (res.status === 'success') {
                showToast('Scan complete: ' + (res.processed || 0) + ' processed, ' + (res.skipped || 0) + ' skipped');
                if (res.processed > 0) location.reload();
            } else {
                showToast('Scan failed: ' + (res.message || 'Error'), 'danger');
            }
        }, 'json').always(function() {
            btn.prop('disabled', false).html('<i class="bi bi-search"></i> Rescan All');
        });
    });

    // Drag and Drop Logic
    let dragData = null;
    let confirmModal = new bootstrap.Modal(document.getElementById('confirmMergeModal'));
    let pendingAction = null;

    $('.face-card, .face-card-unassigned').on('dragstart', function(e) {
        const target = $(this);
        dragData = {
            type: target.data('type'),
            id: target.data('type') === 'person' ? target.data('person-id') : target.data('face-id'),
            name: target.data('person-name') || ('Face #' + target.data('face-id'))
        };
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(dragData));
        e.originalEvent.dataTransfer.effectAllowed = 'move';
    });

    $('.face-card').on('dragover', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.dropEffect = 'move';
        $(this).addClass('drag-over');
    });

    $('.face-card').on('dragleave drop', function() {
        $(this).removeClass('drag-over');
    });

    $('.face-card').on('drop', function(e) {
        e.preventDefault();
        const dropTarget = $(this);
        const targetPersonId = dropTarget.data('person-id');
        const targetPersonName = dropTarget.data('person-name');

        if (!dragData) return;

        // Prevent dropping onto self
        if (dragData.type === 'person' && dragData.id == targetPersonId) return;

        if (dragData.type === 'person') {
            // Merge two persons
            $('#confirmMergeText').html(`Are you sure you want to merge all photos of <strong>${dragData.name}</strong> into <strong>${targetPersonName}</strong>? This action cannot be undone.`);
            pendingAction = function() {
                $.post(BASE_URL + 'faces/persons/merge', {
                    source_person_id: dragData.id,
                    target_person_id: targetPersonId
                }, function(res) {
                    if (res.status === 'success') {
                        showToast('Persons merged successfully!');
                        location.reload();
                    } else {
                        showToast('Merge failed: ' + (res.message || 'Error'), 'danger');
                    }
                }, 'json');
            };
            confirmModal.show();
        } else if (dragData.type === 'face') {
            // Assign face to person
            $('#confirmMergeText').html(`Are you sure you want to assign <strong>${dragData.name}</strong> to <strong>${targetPersonName}</strong>?`);
            pendingAction = function() {
                $.post(BASE_URL + 'faces/assign-face', {
                    face_id: dragData.id,
                    person_id: targetPersonId
                }, function(res) {
                    if (res.status === 'success') {
                        showToast('Face assigned successfully!');
                        location.reload();
                    } else {
                        showToast('Assignment failed: ' + (res.message || 'Error'), 'danger');
                    }
                }, 'json');
            };
            confirmModal.show();
        }
    });

    $('#btnConfirmMerge').on('click', function() {
        if (pendingAction) {
            pendingAction();
            confirmModal.hide();
        }
    });
});
</script>
<?= $this->endSection() ?>

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

<!-- Live Real-Time Scan Progress & ETA Tracker Banner -->
<div id="facesScanBanner" class="alert alert-info border-0 shadow-sm d-none mb-4 p-3 rounded-4" style="background: rgba(13, 110, 253, 0.08); border-left: 4px solid #0d6efd !important;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            <strong class="text-primary" id="facesScanTitle">Scanning Photos for Faces...</strong>
            <span class="badge bg-primary rounded-pill small" id="facesScanBadge">Active</span>
        </div>
        <div class="d-flex align-items-center gap-2 small text-muted">
            <span id="facesScanEta" class="d-none"><i class="bi bi-clock-history me-1"></i>ETA: <strong id="facesScanEtaVal">--</strong></span>
            <span id="facesScanSpeed" class="d-none"><i class="bi bi-speedometer2 me-1"></i><strong id="facesScanSpeedVal">--</strong>/s</span>
            <span id="facesScanCounts" class="fw-semibold">0 / 0 photos</span>
        </div>
    </div>
    <div class="progress rounded-pill" style="height: 8px; background: rgba(0,0,0,0.08);">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="facesScanProgressBar" role="progressbar" style="width: 0%;"></div>
    </div>
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
                    <div class="card h-100 text-center border-0 face-card position-relative" 
                         style="background:var(--card-bg);cursor:grab;overflow:hidden;transition: transform 0.2s, border 0.2s; border: 2px solid transparent;"
                         draggable="true"
                         data-type="person"
                         data-person-id="<?= $person['id'] ?>"
                         data-person-name="<?= esc($person['name'] ?: 'Person ' . $person['id']) ?>">
                        <button type="button" class="btn btn-sm btn-light border rounded-circle position-absolute top-0 end-0 m-1 shadow-sm btn-quick-merge" 
                                data-person-id="<?= $person['id'] ?>" 
                                data-person-name="<?= esc($person['name'] ?: 'Person ' . $person['id']) ?>" 
                                title="Merge with another person" 
                                style="z-index: 5; width: 26px; height: 26px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="bi bi-arrows-collapse text-muted" style="font-size: 0.75rem;"></i>
                        </button>
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
                        <?php if (isset($face['detection_score']) && $face['detection_score'] !== null): ?>
                            <div class="small fw-medium" style="pointer-events:none;">score: <?= round((float) $face['detection_score'], 2) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($face['age']) || !empty($face['gender'])): ?>
                            <div class="small text-muted" style="pointer-events:none;"><?= !empty($face['age']) ? '~' . $face['age'] . 'y' : '' ?><?= !empty($face['age']) && !empty($face['gender']) ? ', ' : '' ?><?= !empty($face['gender']) ? ucfirst($face['gender']) : '' ?></div>
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

<!-- Quick Person Merge Modal -->
<div class="modal fade" id="quickMergeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); color: var(--text-primary);">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrows-collapse text-primary me-2"></i>Merge Person</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Move all photos and face recognition clusters of <strong id="quickMergeSourceName"></strong> into another person.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Select Person to Merge Into</label>
                    <select id="quickMergeTargetSelect" class="form-select bg-light border-0 py-2">
                        <option value="">-- Choose target person --</option>
                        <?php if (!empty($persons)): ?>
                            <?php foreach ($persons as $p): ?>
                                <option value="<?= $p['id'] ?>" class="opt-person-<?= $p['id'] ?>">
                                    <?= esc($p['name'] ?: 'Person #' . $p['id']) ?> (<?= (int)($p['face_count'] ?? 0) ?> photos)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="alert alert-warning py-2 px-3 small rounded mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    This action will combine all photos from both cards into the selected person.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="btnExecuteQuickMerge" disabled>
                    <i class="bi bi-check-lg me-1"></i> Merge Identities
                </button>
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
    // ── Live Scan Progress Polling & ETA Math ──────────────────
    let facePollTimer = null;
    let lastPollTime = null;
    let lastScannedCount = null;

    function formatSecs(sec) {
        if (!sec || isNaN(sec) || sec <= 0 || !isFinite(sec)) return '--';
        sec = Math.round(sec);
        if (sec < 60) return sec + 's';
        let m = Math.floor(sec / 60);
        let s = sec % 60;
        return m + 'm ' + s + 's';
    }

    function checkScanStatus() {
        $.getJSON(BASE_URL + 'faces/scan-status', function(res) {
            if (res.status === 'success') {
                const total = parseInt(res.total) || 0;
                const scanned = parseInt(res.scanned) || 0;
                const unscanned = parseInt(res.unscanned) || 0;
                const isProc = res.is_processing || (res.queue_size || 0) > 0;
                const pct = total > 0 ? Math.min(100, Math.round((scanned / total) * 100)) : 100;

                if (isProc) {
                    $('#facesScanBanner').removeClass('d-none');
                    $('#facesScanProgressBar').css('width', pct + '%');
                    $('#facesScanCounts').text(scanned + ' / ' + total + ' photos (' + pct + '%)');

                    const now = Date.now();
                    let speed = 0;
                    if (lastPollTime && lastScannedCount !== null) {
                        const dt = (now - lastPollTime) / 1000;
                        const dp = scanned - lastScannedCount;
                        if (dp > 0 && dt > 0) speed = dp / dt;
                    }
                    lastPollTime = now;
                    lastScannedCount = scanned;

                    if (speed > 0) {
                        $('#facesScanSpeed').removeClass('d-none');
                        $('#facesScanSpeedVal').text(speed.toFixed(1));
                        const etaSec = unscanned / speed;
                        $('#facesScanEta').removeClass('d-none');
                        $('#facesScanEtaVal').text(formatSecs(etaSec));
                    }

                    if (!facePollTimer) {
                        facePollTimer = setInterval(checkScanStatus, 3000);
                    }
                } else {
                    if (facePollTimer) {
                        clearInterval(facePollTimer);
                        facePollTimer = null;
                    }
                    $('#facesScanBanner').addClass('d-none');
                }
            }
        });
    }

    // Check scan status on page load
    checkScanStatus();

    $('#btnRescanAll').on('click', function() {
        const btn = $(this).prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Queuing...');
        $.post(BASE_URL + 'api/v1/faces/scan-all', function(res) {
            if (res.status === 'success') {
                showToast(res.message || 'Queued photos for face scanning.', 'success');
                $('#facesScanBanner').removeClass('d-none');
                checkScanStatus();
            } else {
                showToast('Scan failed: ' + (res.message || 'Error'), 'danger');
            }
        }, 'json').always(function() {
            btn.prop('disabled', false).html('<i class="bi bi-search"></i> Rescan All');
        });
    });

    // ── Quick Merge Modal Logic ────────────────────────────────
    let quickMergeSourceId = null;
    const quickMergeModal = new bootstrap.Modal(document.getElementById('quickMergeModal'));

    $(document).on('click', '.btn-quick-merge', function(e) {
        e.preventDefault();
        e.stopPropagation();
        quickMergeSourceId = $(this).data('person-id');
        const sourceName = $(this).data('person-name');

        $('#quickMergeSourceName').text(sourceName);
        $('#quickMergeTargetSelect').val('');
        $('#quickMergeTargetSelect option').show();
        $('#quickMergeTargetSelect .opt-person-' + quickMergeSourceId).hide();
        $('#btnExecuteQuickMerge').prop('disabled', true);

        quickMergeModal.show();
    });

    $('#quickMergeTargetSelect').on('change', function() {
        const targetId = $(this).val();
        $('#btnExecuteQuickMerge').prop('disabled', !targetId || targetId == quickMergeSourceId);
    });

    $('#btnExecuteQuickMerge').on('click', function() {
        const targetId = $('#quickMergeTargetSelect').val();
        if (!quickMergeSourceId || !targetId) return;

        const btn = $(this).prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Merging...');

        $.post(BASE_URL + 'faces/persons/merge', {
            source_person_id: quickMergeSourceId,
            target_person_id: targetId
        }, function(res) {
            if (res.status === 'success') {
                showToast('Persons merged successfully!', 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast('Merge failed: ' + (res.message || 'Error'), 'danger');
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Merge Identities');
            }
        }, 'json').fail(function() {
            showToast('Network error during merge', 'danger');
            btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Merge Identities');
        });
    });

    // ── Drag and Drop Logic ────────────────────────────────────
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
        if (dragData.type === 'person' && dragData.id == targetPersonId) return;

        if (dragData.type === 'person') {
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

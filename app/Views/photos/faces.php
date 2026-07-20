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
                    <div class="card h-100 text-center border-0 face-card" style="background:var(--card-bg);cursor:pointer;overflow:hidden;">
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
                                     style="width:100px;height:100px;background:var(--card-bg);">
                                    <div style="width:100px;height:100px;background:url(<?= $thumb['url'] ?>) no-repeat;background-size:<?= $bsW ?>% <?= $bsH ?>%;background-position:<?= $px ?>% <?= $py ?>%;"></div>
                                </div>
                            <?php else: ?>
                                <div class="rounded-3 bg-secondary d-flex align-items-center justify-content-center mx-auto mb-2"
                                     style="width:100px;height:100px;font-size:24px;color:#fff;">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                            <div class="small fw-medium"><?= $faceCount ?> photo<?= $faceCount !== 1 ? 's' : '' ?></div>
                            <?php if ($person['age'] || $person['gender']): ?>
                                <div class="small text-muted"><?= $person['age'] ? '~' . $person['age'] . 'y' : '' ?><?= $person['age'] && $person['gender'] ? ', ' : '' ?><?= $person['gender'] ? ucfirst($person['gender']) : '' ?></div>
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
                <a href="<?= base_url('faces/photo/' . $face['photo_id']) ?>" class="text-decoration-none">
                    <div class="card h-100 text-center border-0" style="background:var(--card-bg);cursor:pointer;">
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
                                     style="width:100px;height:100px;background:var(--card-bg);">
                                    <div style="width:100px;height:100px;background:url(<?= $thumb['url'] ?>) no-repeat;background-size:<?= $bsW ?>% <?= $bsH ?>%;background-position:<?= $px ?>% <?= $py ?>%;"></div>
                                </div>
                            <?php else: ?>
                                <div class="rounded-3 bg-warning bg-opacity-25 d-flex align-items-center justify-content-center mx-auto mb-2"
                                     style="width:100px;height:100px;font-size:24px;color:#856404;">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                            <?php endif; ?>
                            <div class="small fw-medium">score: <?= round($face['detection_score'], 2) ?></div>
                            <?php if ($face['age'] || $face['gender']): ?>
                                <div class="small text-muted"><?= $face['age'] ? '~' . $face['age'] . 'y' : '' ?><?= $face['age'] && $face['gender'] ? ', ' : '' ?><?= $face['gender'] ? ucfirst($face['gender']) : '' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
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
});
</script>
<?= $this->endSection() ?>

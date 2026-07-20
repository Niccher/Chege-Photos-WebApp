<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0"><i class="bi bi-people me-2"></i>Faces</h2>
    <p class="text-muted small mb-0">Click a face to see all photos containing it</p>
</div>

<?php if (!empty($persons)): ?>
    <h5 class="mb-3">Known Faces</h5>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 mb-4">
        <?php foreach ($persons as $person): ?>
            <?php
            $faceCount = (int) ($person['face_count'] ?? 0);
            $label = $person['name'] ?: 'Person ' . $person['id'];
            ?>
            <div class="col">
                <a href="<?= base_url('faces/person/' . $person['id']) ?>" class="text-decoration-none">
                    <div class="card h-100 text-center border-0" style="background:var(--card-bg);cursor:pointer;">
                        <div class="card-body py-3">
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-2"
                                 style="width:64px;height:64px;font-size:24px;color:#fff;">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="small fw-medium text-truncate" style="color:var(--text-color);"><?= esc($label) ?></div>
                            <div class="small text-muted"><?= $faceCount ?> photo<?= $faceCount !== 1 ? 's' : '' ?></div>
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
        <?php foreach ($faceModel->getUnassigned() as $face): ?>
            <div class="col">
                <a href="<?= base_url('faces/photo/' . $face['photo_id']) ?>" class="text-decoration-none">
                    <div class="card h-100 text-center border-0" style="background:var(--card-bg);cursor:pointer;">
                        <div class="card-body py-3">
                            <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center mx-auto mb-2"
                                 style="width:64px;height:64px;font-size:24px;color:#856404;">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <div class="small text-muted text-truncate">Photo #<?= $face['photo_id'] ?></div>
                            <div class="small text-muted"><?= round($face['detection_score'], 2) ?></div>
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

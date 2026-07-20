<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= base_url('faces') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Faces
    </a>
</div>

<?php if ($photo): ?>
    <div class="position-relative d-inline-block">
        <img src="<?= base_url($photo['path']) ?>" alt="Photo" class="img-fluid rounded"
             id="facePhoto" style="max-height:85vh;width:auto;">
        <?php foreach ($faces as $face): ?>
            <?php
            $x = $photo['width'] ? ($face['bbox_x'] / $photo['width']) * 100 : 0;
            $y = $photo['height'] ? ($face['bbox_y'] / $photo['height']) * 100 : 0;
            $w = $photo['width'] ? ($face['bbox_w'] / $photo['width']) * 100 : 0;
            $h = $photo['height'] ? ($face['bbox_h'] / $photo['height']) * 100 : 0;
            $personName = '';
            if ($face['person_id']) {
                $person = model('App\Models\PersonModel')->find($face['person_id']);
                $personName = $person['name'] ?? '';
            }
            ?>
            <div class="face-bbox position-absolute border border-success border-2 rounded"
                 style="left:<?= $x ?>%;top:<?= $y ?>%;width:<?= $w ?>%;height:<?= $h ?>%;cursor:pointer;"
                 title="Face #<?= $face['id'] ?><?= $personName ? ' – ' . esc($personName) : '' ?> (score: <?= round($face['detection_score'], 2) ?>)"
                 data-face-id="<?= $face['id'] ?>"
                 data-person-id="<?= $face['person_id'] ?? '' ?>"
                 data-bs-toggle="tooltip">
                <?php if ($personName): ?>
                    <span class="position-absolute bottom-0 start-0 bg-dark bg-opacity-75 text-white px-1 small"
                          style="font-size:10px;line-height:1.2;"><?= esc($personName) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($faces)): ?>
        <div class="mt-3">
            <h6>Detected Faces (<?= count($faces) ?>)</h6>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($faces as $face): ?>
                    <?php
                    $personName = '';
                    if ($face['person_id']) {
                        $person = model('App\Models\PersonModel')->find($face['person_id']);
                        $personName = $person['name'] ?? '';
                    }
                    ?>
                    <div class="card border-0" style="width:140px;background:var(--card-bg);">
                        <?php
                        $pw = $photo['width'] ?: 800;
                        $ph = $photo['height'] ?: 600;
                        $px = ($face['bbox_x'] / $pw) * 100;
                        $py = ($face['bbox_y'] / $ph) * 100;
                        $pw_pct = ($face['bbox_w'] / $pw) * 100;
                        $ph_pct = ($face['bbox_h'] / $ph) * 100;
                        ?>
                        <div style="width:100%;height:100px;background:url(<?= base_url($photo['path']) ?>) no-repeat;background-size:<?= 100 / ($face['bbox_w'] / $pw) ?>% <?= 100 / ($face['bbox_h'] / $ph) ?>%;background-position:<?= $px ?>% <?= $py ?>%;border-radius:8px 8px 0 0;"></div>
                        <div class="p-1 text-center small">
                            <?php if ($personName): ?>
                                <div><?= esc($personName) ?></div>
                            <?php else: ?>
                                <span class="text-muted">Unassigned</span>
                            <?php endif; ?>
                            <div class="text-muted" style="font-size:10px;">score: <?= round($face['detection_score'], 2) ?></div>
                            <?php if ($face['age']): ?>
                                <div class="text-muted" style="font-size:10px;">Age: ~<?= $face['age'] ?> <?= $face['gender'] ? '(' . $face['gender'] . ')' : '' ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-warning">Photo not found.</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
<?= $this->endSection() ?>

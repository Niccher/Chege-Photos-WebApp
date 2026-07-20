<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-3 d-flex align-items-center gap-2">
    <a href="<?= base_url('faces') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <h4 class="mb-0"><?= esc($label) ?></h4>
    <span class="badge bg-secondary rounded-pill"><?= count($photos) ?> photo<?= count($photos) !== 1 ? 's' : '' ?></span>
</div>

<?php if (!empty($photos)): ?>
    <div class="row g-2">
        <?php foreach ($photos as $photo): ?>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= base_url('explore?photo=' . $photo['id']) ?>" class="text-decoration-none">
                    <div class="photo-card rounded overflow-hidden" style="aspect-ratio:1;background:#1a1a2e;cursor:pointer;position:relative;">
                        <img src="<?= base_url(esc($photo['thumbnail_path'] ?: $photo['path'])) ?>"
                             alt="<?= esc($photo['filename']) ?>"
                             loading="lazy"
                             style="width:100%;height:100%;object-fit:cover;">
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">No photos found for this face.</div>
<?php endif; ?>

<?= $this->endSection() ?>

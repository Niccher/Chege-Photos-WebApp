<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0">Memories</h2>
    <p class="text-muted small mb-0">Rediscover photos from your past</p>
</div>

<?php if (empty($pastYearsPhotos) && empty($sixMonthsPhotos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-event" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">No memories today</h3>
        <p class="text-muted">Come back on another day to see photos from your past.</p>
    </div>
<?php else: ?>

    <?php if (!empty($pastYearsPhotos)): ?>
        <h5 class="mb-3 mt-4 text-primary px-2"><i class="bi bi-clock-history me-2"></i>On This Day</h5>
        <?php 
        $currentYear = '';
        foreach ($pastYearsPhotos as $photo): 
            $photoYear = date('Y', strtotime($photo['taken_at']));
            $yearsAgo = date('Y') - $photoYear;
            if ($photoYear !== $currentYear):
                if ($currentYear !== '') echo '</div>'; // Close previous grid
                $currentYear = $photoYear;
        ?>
            <h6 class="mb-2 mt-4 text-muted px-2"><?= $yearsAgo ?> <?= $yearsAgo == 1 ? 'Year' : 'Years' ?> Ago (<?= $photoYear ?>)</h6>
            <div class="photo-grid">
        <?php endif; ?>
            
            <div class="photo-item" 
                 data-id="<?= $photo['id'] ?>" 
                 data-full="<?= base_url($photo['path']) ?>"
                 data-filename="<?= $photo['filename'] ?>"
                 data-size="<?= round($photo['size'] / 1024 / 1024, 2) ?> MB"
                 data-dimensions="<?= $photo['width'] ? $photo['width'].' x '.$photo['height'] : 'Video' ?>"
                     data-date="<?= date('M d, Y H:i', strtotime($photo['taken_at'])) ?>"
                     data-favorite="<?= $photo['is_favorite'] ? '1' : '0' ?>"
                     data-exif-b64="<?= $photo['exif_data'] ? base64_encode($photo['exif_data']) : '' ?>"
                     data-location="<?= ($photo['latitude'] && $photo['longitude']) ? $photo['latitude'].','.$photo['longitude'] : '' ?>"
                     data-type="<?= strpos($photo['mime_type'], 'video/') === 0 ? 'video' : 'image' ?>">
                <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                    <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                        <i class="bi bi-check-lg d-none"></i>
                    </div>
                </div>
                <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                    <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                <?php else: ?>
                    <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div> <!-- Close last grid -->
    <?php endif; ?>

    <?php if (!empty($sixMonthsPhotos)): ?>
        <h5 class="mb-3 mt-5 text-primary px-2"><i class="bi bi-hourglass-split me-2"></i>6 Months Ago Today</h5>
        <div class="photo-grid">
            <?php foreach ($sixMonthsPhotos as $photo): ?>
                <div class="photo-item" 
                     data-id="<?= $photo['id'] ?>" 
                     data-full="<?= base_url($photo['path']) ?>"
                     data-filename="<?= $photo['filename'] ?>"
                     data-size="<?= round($photo['size'] / 1024 / 1024, 2) ?> MB"
                     data-dimensions="<?= $photo['width'] ? $photo['width'].' x '.$photo['height'] : 'Video' ?>"
                     data-date="<?= date('M d, Y H:i', strtotime($photo['taken_at'])) ?>"
                     data-favorite="<?= $photo['is_favorite'] ? '1' : '0' ?>"
                     data-exif-b64="<?= $photo['exif_data'] ? base64_encode($photo['exif_data']) : '' ?>"
                     data-location="<?= ($photo['latitude'] && $photo['longitude']) ? $photo['latitude'].','.$photo['longitude'] : '' ?>"
                     data-type="<?= strpos($photo['mime_type'], 'video/') === 0 ? 'video' : 'image' ?>">
                    <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                        <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                            <i class="bi bi-check-lg d-none"></i>
                        </div>
                    </div>
                    <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                        <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                    <?php else: ?>
                        <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>

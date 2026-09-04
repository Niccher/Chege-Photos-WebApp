<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0">Memories</h2>
    <p class="text-muted small mb-0">Rediscover photos from your past and treasured highlights</p>
</div>

<?php 
$hasMemories = !empty($pastYearsPhotos) || !empty($thisMonthPhotos) || !empty($favoritePhotos) || !empty($sixMonthsPhotos);
?>

<?php if (!$hasMemories): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-event" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">No memories yet</h3>
        <p class="text-muted">Upload more photos or mark your best photos with stars (<i class="bi bi-star-fill text-warning"></i>) to see your memories here.</p>
    </div>
<?php else: ?>

    <?php 
    // Helper function to render a photo card in memories
    $renderPhoto = function($photo) {
        $isFav = $photo['is_favorite'] ? '1' : '0';
        $exifB64 = $photo['exif_data'] ? base64_encode($photo['exif_data']) : '';
        $loc = ($photo['latitude'] && $photo['longitude'] && abs((float)$photo['latitude']) > 0.0001) ? $photo['latitude'].','.$photo['longitude'] : '';
        $isVideo = strpos($photo['mime_type'], 'video/') === 0;
        $sizeMb = round($photo['size'] / 1024 / 1024, 2) . ' MB';
        $dims = $photo['width'] ? $photo['width'].' x '.$photo['height'] : 'Video';
        $dateStr = date('M d, Y H:i', strtotime($photo['taken_at']));
        ?>
        <div class="photo-item" 
             data-id="<?= $photo['id'] ?>" 
             data-full="<?= base_url($photo['path']) ?>"
             data-filename="<?= esc($photo['filename']) ?>"
             data-size="<?= $sizeMb ?>"
             data-dimensions="<?= $dims ?>"
             data-date="<?= $dateStr ?>"
             data-favorite="<?= $isFav ?>"
             data-exif-b64="<?= $exifB64 ?>"
             data-location="<?= $loc ?>"
             data-type="<?= $isVideo ? 'video' : 'image' ?>">
            <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                    <i class="bi bi-check-lg d-none"></i>
                </div>
            </div>
            <?php if ($photo['is_favorite']): ?>
                <div class="position-absolute top-0 start-0 p-2" style="z-index: 5;">
                    <i class="bi bi-heart-fill text-danger shadow-sm"></i>
                </div>
            <?php endif; ?>
            <?php if ($isVideo): ?>
                <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>
            <?php else: ?>
                <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= esc($photo['filename']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="position-absolute bottom-0 start-0 end-0 p-1 px-2 d-flex justify-content-between align-items-center text-white small" style="background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%); font-size: 0.72rem; z-index: 4; pointer-events: none;">
                <span><?= date('M d, Y', strtotime($photo['taken_at'])) ?></span>
            </div>
        </div>
        <?php
    };
    ?>

    <!-- Tier 1: On This Day & Week in Past Years -->
    <?php if (!empty($pastYearsPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-4 px-2">
            <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-clock-history me-2"></i>On This Day & Week</h5>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <?php 
        $currentYear = '';
        foreach ($pastYearsPhotos as $photo): 
            $photoYear = date('Y', strtotime($photo['taken_at']));
            $yearsAgo = date('Y') - $photoYear;
            if ($photoYear !== $currentYear):
                if ($currentYear !== '') echo '</div>';
                $currentYear = $photoYear;
        ?>
            <h6 class="mb-2 mt-4 text-muted px-2 small text-uppercase fw-bold"><i class="bi bi-calendar-check me-1"></i><?= $yearsAgo ?> <?= $yearsAgo == 1 ? 'Year' : 'Years' ?> Ago (<?= $photoYear ?>)</h6>
            <div class="photo-grid">
        <?php endif; ?>
            <?php $renderPhoto($photo); ?>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tier 2: Flashback to this Month in Past Years -->
    <?php if (!empty($thisMonthPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-info fw-bold"><i class="bi bi-brightness-high me-2"></i>Flashback: <?= date('F') ?> in Past Years</h5>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($thisMonthPhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tier 3: Starred Favorites -->
    <?php if (!empty($favoritePhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-warning fw-bold"><i class="bi bi-star-fill me-2"></i>Rediscover Your Favorites</h5>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($favoritePhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Tier 4: Recent Throwbacks (1 to 6 Months Ago) -->
    <?php if (!empty($sixMonthsPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-success fw-bold"><i class="bi bi-hourglass-split me-2"></i>Recent Throwbacks (1–6 Months Ago)</h5>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($sixMonthsPhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>

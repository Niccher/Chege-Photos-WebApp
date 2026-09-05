<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
/* ── Stories Reel ── */
.stories-container {
    overflow-x: auto;
    scrollbar-width: thin;
    padding-bottom: 8px;
}
.stories-container::-webkit-scrollbar {
    height: 6px;
}
.stories-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}
.story-card {
    min-width: 145px;
    width: 145px;
    height: 215px;
    border-radius: 18px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    flex-shrink: 0;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    border: 2px solid rgba(255, 255, 255, 0.15);
    background-color: #1a1a1a;
}
.story-card:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
    border-color: #1a73e8;
}
.story-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.story-card:hover img {
    transform: scale(1.08);
}
.story-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.1) 40%, rgba(0,0,0,0.85) 100%);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 12px;
    color: #ffffff;
    pointer-events: none;
}
.story-badge {
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 0.68rem;
    font-weight: 600;
    align-self: flex-start;
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.story-title {
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 2px;
    text-shadow: 0 1px 4px rgba(0,0,0,0.8);
}
.story-subtitle {
    font-size: 0.7rem;
    opacity: 0.85;
    text-shadow: 0 1px 3px rgba(0,0,0,0.8);
}
</style>

<!-- ── Header & Time Machine ── -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Memories
        </h2>
        <p class="text-muted small mb-0">
            <?= $isToday ? 'Rediscover photos from this week in previous years and treasured highlights' : 'Memories recorded around ' . date('F j', strtotime($selectedDate)) . ' across past years' ?>
        </p>
    </div>

    <!-- Time Machine Date Jump -->
    <form method="GET" action="<?= base_url('memories') ?>" class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-dark border-secondary text-light">
                <i class="bi bi-calendar-event"></i>
            </span>
            <input type="date" name="date" class="form-control form-control-sm bg-dark text-light border-secondary" value="<?= esc($selectedDate) ?>" max="<?= date('Y-m-d') ?>">
            <button class="btn btn-primary btn-sm px-3" type="submit">Jump</button>
        </div>
        <?php if (!$isToday): ?>
            <a href="<?= base_url('memories') ?>" class="btn btn-outline-secondary btn-sm" title="Reset to today">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Today
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- ── Story Reels Bar (Google Photos Style) ── -->
<?php if (!empty($stories)): ?>
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2 px-1">
            <span class="small text-uppercase fw-bold text-muted" style="letter-spacing: 0.06em; font-size: 0.72rem;">
                <i class="bi bi-collection-play me-1 text-primary"></i> Story Reels
            </span>
            <span class="text-muted small"><?= count($stories) ?> collection<?= count($stories) === 1 ? '' : 's' ?></span>
        </div>
        <div class="stories-container d-flex gap-3 pb-2">
            <?php foreach ($stories as $story): ?>
                <div class="story-card" data-story-id="<?= esc($story['id']) ?>" data-photo-ids="<?= esc(json_encode($story['photo_ids'])) ?>" title="Click to view story slideshow">
                    <img src="<?= esc($story['cover_url']) ?>" alt="<?= esc($story['title']) ?>" loading="lazy">
                    <div class="story-overlay">
                        <div class="story-badge">
                            <i class="bi bi-play-fill me-1"></i><?= $story['count'] ?>
                        </div>
                        <div>
                            <div class="story-title"><?= esc($story['title']) ?></div>
                            <div class="story-subtitle text-truncate"><?= esc($story['subtitle']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php 
$hasMemories = !empty($pastYearsPhotos) || !empty($thisMonthPhotos) || !empty($favoritePhotos) || !empty($sixMonthsPhotos);
?>

<?php if (!$hasMemories): ?>
    <div class="text-center py-5">
        <i class="bi bi-calendar-x" style="font-size: 3.5rem; color: #6c757d;"></i>
        <h4 class="mt-3 text-muted">No memories found for this period</h4>
        <p class="text-muted small mx-auto" style="max-width: 480px;">
            <?= $isToday ? 'Upload photos from past trips or star your favorite shots to start building automatic memory collections.' : 'No photos were taken around ' . date('F j', strtotime($selectedDate)) . ' in prior years. Pick another date using the Time Machine above!' ?>
        </p>
        <?php if (!$isToday): ?>
            <a href="<?= base_url('memories') ?>" class="btn btn-primary btn-sm rounded-pill px-4 mt-2">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Return to Today's Memories
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>

    <?php 
    // Helper function to render an interactive photo card
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
                <img src="<?= base_url($photo['thumbnail_path'] ?: $photo['path']) ?>" alt="<?= esc($photo['filename']) ?>" loading="lazy">
            <?php endif; ?>
            <div class="position-absolute bottom-0 start-0 end-0 p-1 px-2 d-flex justify-content-between align-items-center text-white small" style="background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%); font-size: 0.72rem; z-index: 4; pointer-events: none;">
                <span><?= date('M d, Y', strtotime($photo['taken_at'])) ?></span>
            </div>
        </div>
        <?php
    };
    ?>

    <!-- ── Tier 1: On This Day in Past Years ── -->
    <?php if (!empty($pastYearsPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-4 px-2">
            <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-clock-history me-2"></i>On This Day &amp; Week</h5>
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary"><?= count($pastYearsPhotos) ?> photos</span>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <?php 
        $currentYear = '';
        foreach ($pastYearsPhotos as $photo): 
            $photoYear = date('Y', strtotime($photo['taken_at']));
            $yearsAgo = (int) date('Y', strtotime($selectedDate)) - (int) $photoYear;
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

    <!-- ── Tier 2: Flashback to this Month in Past Years ── -->
    <?php if (!empty($thisMonthPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-info fw-bold"><i class="bi bi-brightness-high me-2"></i>Flashback: <?= date('F', strtotime($selectedDate)) ?> in Past Years</h5>
            <span class="badge bg-info bg-opacity-25 text-info border border-info"><?= count($thisMonthPhotos) ?> photos</span>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($thisMonthPhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── Tier 3: Starred Favorites ── -->
    <?php if (!empty($favoritePhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-warning fw-bold"><i class="bi bi-star-fill me-2"></i>Rediscover Your Favorites</h5>
            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning"><?= count($favoritePhotos) ?> photos</span>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($favoritePhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ── Tier 4: Recent Throwbacks (1 to 6 Months Ago) ── -->
    <?php if (!empty($sixMonthsPhotos)): ?>
        <div class="d-flex align-items-center gap-2 mb-3 mt-5 px-2">
            <h5 class="mb-0 text-success fw-bold"><i class="bi bi-hourglass-split me-2"></i>Recent Throwbacks (1–6 Months Ago)</h5>
            <span class="badge bg-success bg-opacity-25 text-success border border-success"><?= count($sixMonthsPhotos) ?> photos</span>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
        </div>
        <div class="photo-grid">
            <?php foreach ($sixMonthsPhotos as $photo): ?>
                <?php $renderPhoto($photo); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tapping a story card opens the lightbox on that story's first photo and starts the slideshow
    document.querySelectorAll('.story-card').forEach(function(card) {
        card.addEventListener('click', function() {
            var rawIds = this.getAttribute('data-photo-ids');
            if (!rawIds) return;
            var photoIds = JSON.parse(rawIds);
            if (!photoIds || photoIds.length === 0) return;

            var firstPhotoEl = document.querySelector('.photo-item[data-id="' + photoIds[0] + '"]');
            if (firstPhotoEl) {
                firstPhotoEl.click();
                setTimeout(function() {
                    var slideshowBtn = document.getElementById('btnSlideshow');
                    if (slideshowBtn && !document.getElementById('slideshowProgress').classList.contains('active')) {
                        slideshowBtn.click();
                    }
                }, 350);
            }
        });
    });
});
</script>

<?= $this->endSection() ?>

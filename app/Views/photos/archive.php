<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h2 class="h4 mb-0">Archive</h2>
    <p class="text-muted small mb-0">Photos you've archived to clear up your main view</p>
</div>

<?php if (!empty($searchQuery) && empty($photos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-search" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">No results found</h3>
        <p class="text-muted">We couldn't find any archived photos matching "<?= esc($searchQuery) ?>".</p>
        <a href="<?= base_url('archive') ?>" class="btn btn-link text-decoration-none">Clear search</a>
    </div>
<?php elseif (empty($photos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-archive" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">Archive is empty</h3>
        <p class="text-muted">Hide photos from your main view by moving them here.</p>
    </div>
<?php else: ?>
    <?php 
    $currentDate = ''; 
    foreach ($photos as $photo): 
        $photoDate = date('F Y', strtotime($photo['taken_at']));
        if ($photoDate !== $currentDate):
            if ($currentDate !== '') echo '</div>'; // Close previous grid
            $currentDate = $photoDate;
    ?>
        <div class="d-flex align-items-center gap-3 mb-3 mt-4 px-2">
        <h5 class="mb-0 fw-bold opacity-75 timeline-header" style="color: var(--text-primary);"><?= $currentDate ?></h5>
        <div class="flex-grow-1 border-bottom border-secondary opacity-25" style="border-color: var(--border-color) !important;"></div>
    </div>
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
             data-location="<?= ($photo['latitude'] && $photo['longitude'] && abs((float)$photo['latitude']) > 0.0001 && abs((float)$photo['longitude']) > 0.0001) ? $photo['latitude'].','.$photo['longitude'] : '' ?>"
             data-type="<?= strpos($photo['mime_type'], 'video/') === 0 ? 'video' : 'image' ?>">
            <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                    <i class="bi bi-check-lg d-none"></i>
                </div>
            </div>
            <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>
            <?php else: ?>
                <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
            <?php endif; ?>
        </div>
        
    <?php endforeach; ?>
    </div> <!-- Close last grid -->

    <!-- Infinite Scroll Sentinel -->
    <div id="infiniteScrollSentinel" class="text-center py-4" style="min-height: 100px;">
        <div class="spinner-border text-primary d-none" role="status">
            <span class="visually-hidden">Loading more...</span>
        </div>
    </div>
    
    <!-- Hidden Pagination for SEO/Fallback -->
    <div class="d-none">
        <?= $pager->links() ?>
    </div>
<?php endif; ?>

<!-- Include the lightbox partial if needed, but it's already in index.php. Wait. The lightbox modal is currently only in index.php! I should move it to main.php or duplicate it. -->
<?= $this->endSection() ?>

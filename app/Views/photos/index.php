<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
use App\Libraries\SmartAlbumRules;

$smartRulesEdit = null;
if (isset($album) && ! empty($album['is_smart'])) {
    $smartRulesEdit = SmartAlbumRules::fromJson($album['smart_rules'] ?? null);
}
?>

<?php if (isset($title)): ?>
    <div class="mb-4">
        <h2 class="h4 mb-0"><?= esc($title) ?></h2>
        <?php if (isset($subtitle)): ?>
            <p class="text-muted small mb-0"><?= esc($subtitle) ?></p>
        <?php endif; ?>
        <?php if (isset($album) && ! empty($album['is_smart'])): ?>
            <p class="text-white small mb-2 mt-2"><i class="bi bi-stars me-1"></i> Smart album — membership updates automatically when photos match your rules.</p>
            <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#editSmartAlbumModal">Edit rules</button>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!empty($searchQuery) && empty($photos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-search" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">No results found</h3>
        <p class="text-muted">We couldn't find any photos matching "<?= esc($searchQuery) ?>".</p>
        <a href="<?= base_url() ?>" class="btn btn-link text-decoration-none">Clear search</a>
    </div>
<?php elseif (empty($photos)): ?>
    <div class="text-center py-5">
        <i class="bi bi-image" style="font-size: 4rem; color: #dee2e6;"></i>
        <h3 class="mt-3 text-muted">No photos yet</h3>
        <p class="text-muted">Upload some photos or scan the uploads folder to get started.</p>
    </div>
<?php else: ?>
    <?php
    $currentSort = $currentSort ?? 'date_desc';
    $sortLabels = [
        'date_desc'       => 'Date Taken (Newest)',
        'date_asc'        => 'Date Taken (Oldest)',
        'upload_desc'     => 'Recently Uploaded',
        'upload_asc'      => 'First Uploaded',
        'size_desc'       => 'File Size (Largest)',
        'size_asc'        => 'File Size (Smallest)',
        'resolution_desc' => 'Resolution (Highest MP)',
        'resolution_asc'  => 'Resolution (Lowest MP)',
        'name_asc'        => 'Name (A to Z)',
        'name_desc'       => 'Name (Z to A)',
        'geotagged'       => 'Geotagged First',
        'favorites'       => 'Favorites First',
    ];
    ?>
    <!-- Gallery Action Toolbar: Sort & Display Toggles -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 px-2 py-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small fw-semibold text-uppercase me-1"><i class="bi bi-funnel me-1"></i>Sort:</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill px-3 d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="btnSortDropdown">
                    <i class="bi bi-arrow-down-up me-1"></i>
                    <span id="lblCurrentSort"><?= esc($sortLabels[$currentSort] ?? 'Date Taken (Newest)') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark shadow border-secondary" style="font-size: 0.88rem;">
                    <li><h6 class="dropdown-header text-uppercase small">Capture & Upload Date</h6></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'date_desc' ? 'active' : '' ?>" href="#" data-sort="date_desc"><i class="bi bi-calendar-date me-2 text-primary"></i>Date Taken (Newest First)</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'date_asc' ? 'active' : '' ?>" href="#" data-sort="date_asc"><i class="bi bi-calendar me-2"></i>Date Taken (Oldest First)</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'upload_desc' ? 'active' : '' ?>" href="#" data-sort="upload_desc"><i class="bi bi-cloud-arrow-up me-2 text-info"></i>Recently Uploaded</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'upload_asc' ? 'active' : '' ?>" href="#" data-sort="upload_asc"><i class="bi bi-cloud me-2"></i>First Uploaded</a></li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li><h6 class="dropdown-header text-uppercase small">File Size & Quality</h6></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'size_desc' ? 'active' : '' ?>" href="#" data-sort="size_desc"><i class="bi bi-hdd-network me-2 text-warning"></i>File Size (Largest First)</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'size_asc' ? 'active' : '' ?>" href="#" data-sort="size_asc"><i class="bi bi-hdd me-2"></i>File Size (Smallest First)</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'resolution_desc' ? 'active' : '' ?>" href="#" data-sort="resolution_desc"><i class="bi bi-aspect-ratio me-2 text-success"></i>Resolution (Highest MP)</a></li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li><h6 class="dropdown-header text-uppercase small">Attributes & Location</h6></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'name_asc' ? 'active' : '' ?>" href="#" data-sort="name_asc"><i class="bi bi-sort-alpha-down me-2"></i>Filename (A to Z)</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'geotagged' ? 'active' : '' ?>" href="#" data-sort="geotagged"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Geotagged First</a></li>
                    <li><a class="dropdown-item sort-opt <?= $currentSort === 'favorites' ? 'active' : '' ?>" href="#" data-sort="favorites"><i class="bi bi-star-fill me-2 text-warning"></i>Favorites First</a></li>
                </ul>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Info Badges Toggle Button -->
            <button id="btnToggleMetaBadges" type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" title="Toggle EXIF & size chips on thumbnails">
                <i class="bi bi-info-circle me-1"></i> <span id="lblMetaBadges">Info Badges</span>
            </button>
            <!-- Grid Density Toggle -->
            <div class="btn-group btn-group-sm" role="group">
                <button id="btnGridStandard" type="button" class="btn btn-outline-secondary active" title="Standard Grid"><i class="bi bi-grid-fill"></i></button>
                <button id="btnGridCompact" type="button" class="btn btn-outline-secondary" title="Compact Grid (More items)"><i class="bi bi-grid-3x3-gap-fill"></i></button>
            </div>
        </div>
    </div>

    <?php 
    $currentHeader = ''; 
    foreach ($photos as $photo): 
        $groupHeader = \App\Controllers\Photos::getPhotoGroupHeader($photo, $currentSort);
        if ($groupHeader !== $currentHeader):
            if ($currentHeader !== '') echo '</div>'; // Close previous grid
            $currentHeader = $groupHeader;
    ?>
        <div class="d-flex align-items-center gap-3 mb-3 mt-5 px-2">
            <h5 class="mb-0 fw-bold opacity-75 timeline-header" style="color: var(--text-primary);"><?= esc($currentHeader) ?></h5>
            <div class="flex-grow-1 border-bottom border-secondary opacity-25" style="border-color: var(--border-color) !important;"></div>
        </div>
        <div class="photo-grid">
<?php endif; ?>
        
        <div class="photo-item" 
             draggable="true"
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
            <?php if ($photo['is_favorite']): ?>
                <div class="position-absolute top-0 start-0 p-2" style="z-index: 5;">
                    <i class="bi bi-heart-fill text-danger shadow-sm"></i>
                </div>
            <?php endif; ?>
            <?php if (strpos($photo['mime_type'], 'video/') === 0): ?>
                <video src="<?= base_url($photo['path']) ?>" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>
            <?php else: ?>
                <img src="<?= base_url($photo['thumbnail_path']) ?>" alt="<?= $photo['filename'] ?>" loading="lazy">
            <?php endif; ?>
            <!-- Floating Metadata Info Badge (Toggled on/off) -->
            <div class="photo-meta-badge position-absolute bottom-0 start-0 end-0 p-1 px-2 d-none justify-content-between align-items-center text-white small" style="background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%); font-size: 0.72rem; z-index: 4; pointer-events: none;">
                <span class="text-truncate me-1 font-monospace">
                    <?= $photo['width'] && $photo['height'] ? round(($photo['width'] * $photo['height']) / 1000000, 1) . ' MP' : 'Video' ?>
                    • <?= round($photo['size'] / 1024 / 1024, 1) ?> MB
                </span>
                <?php if (!empty($photo['latitude']) && !empty($photo['longitude']) && abs((float)$photo['latitude']) > 0.0001): ?>
                    <i class="bi bi-geo-alt-fill text-info" title="Geotagged"></i>
                <?php endif; ?>
            </div>
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

<?php if ($smartRulesEdit !== null): ?>
<div class="modal fade" id="editSmartAlbumModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header" style="border-color: var(--border-color) !important;">
                <h5 class="modal-title fw-bold">Edit smart album</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formEditSmartAlbum" data-album-id="<?= (int) $album['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= esc($album['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="2"><?= esc($album['description'] ?? '') ?></textarea>
                    </div>
                    <p class="small text-muted mb-3">Photos must match <strong>all</strong> of the rules you enable below (dates, camera text, GPS, favorites, and type combine with AND).</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small">Taken on or after</label>
                            <input type="date" name="date_from" class="form-control" value="<?= esc($smartRulesEdit['date_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Taken on or before</label>
                            <input type="date" name="date_to" class="form-control" value="<?= esc($smartRulesEdit['date_to'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Camera (matches EXIF text, e.g. Canon or iPhone)</label>
                            <input type="text" name="camera_contains" class="form-control" value="<?= esc($smartRulesEdit['camera_contains'] ?? '') ?>" placeholder="Leave empty to ignore">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="has_gps" value="1" id="editHasGps" <?= ! empty($smartRulesEdit['has_gps']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="editHasGps">Has GPS location</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="favorite_only" value="1" id="editFavOnly" <?= ! empty($smartRulesEdit['favorite_only']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="editFavOnly">Favorites only</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small">GPS bounds (optional — leave blank to ignore)</label>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Min latitude</label>
                            <input type="number" name="min_latitude" step="any" class="form-control" value="<?= esc($smartRulesEdit['min_latitude'] ?? '') ?>" placeholder="-90 to 90">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Max latitude</label>
                            <input type="number" name="max_latitude" step="any" class="form-control" value="<?= esc($smartRulesEdit['max_latitude'] ?? '') ?>" placeholder="-90 to 90">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Min longitude</label>
                            <input type="number" name="min_longitude" step="any" class="form-control" value="<?= esc($smartRulesEdit['min_longitude'] ?? '') ?>" placeholder="-180 to 180">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Max longitude</label>
                            <input type="number" name="max_longitude" step="any" class="form-control" value="<?= esc($smartRulesEdit['max_longitude'] ?? '') ?>" placeholder="-180 to 180">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Media type</label>
                            <select name="mime_kind" class="form-select">
                                <?php $mk = $smartRulesEdit['mime_kind'] ?? SmartAlbumRules::MIME_ANY; ?>
                                <option value="<?= esc(SmartAlbumRules::MIME_ANY) ?>" <?= $mk === SmartAlbumRules::MIME_ANY ? 'selected' : '' ?>>Photos and videos</option>
                                <option value="<?= esc(SmartAlbumRules::MIME_IMAGE) ?>" <?= $mk === SmartAlbumRules::MIME_IMAGE ? 'selected' : '' ?>>Photos only</option>
                                <option value="<?= esc(SmartAlbumRules::MIME_VIDEO) ?>" <?= $mk === SmartAlbumRules::MIME_VIDEO ? 'selected' : '' ?>>Videos only</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-bold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

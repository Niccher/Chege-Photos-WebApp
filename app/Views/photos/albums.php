<?php
use App\Libraries\SmartAlbumRules;
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0">Albums</h2>
        <p class="text-muted small mb-0">Organize your photos into collections and AI smart albums</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlbumModal">
        <i class="bi bi-plus-lg me-2"></i> New Album
    </button>
</div>

<?php if (! empty($aiCollections)): ?>
    <!-- AI Smart Collections Banner -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="h6 mb-0 fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-stars text-warning"></i>
                    <span>AI Smart Collections</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small">Auto-Curated</span>
                </h5>
                <p class="text-muted small mb-0">Live collections generated dynamically by YOLO and CLIP models</p>
            </div>
        </div>
        <div class="row g-3">
            <?php foreach ($aiCollections as $col): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="<?= base_url('albums/collection/' . $col['key']) ?>" class="text-decoration-none group">
                        <div class="card border shadow-sm overflow-hidden h-100 album-card" style="background: var(--card-bg);">
                            <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center overflow-hidden position-relative">
                                <?php if (! empty($col['thumbnail'])): ?>
                                    <img src="<?= base_url($col['thumbnail']) ?>" class="object-fit-cover w-100 h-100" alt="<?= esc($col['name']) ?>">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center w-100 h-100 opacity-50">
                                        <i class="bi <?= esc($col['icon']) ?>" style="font-size: 2.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="position-absolute top-0 start-0 m-2 badge bg-<?= esc($col['color']) ?> rounded-pill shadow-sm">
                                    <i class="bi <?= esc($col['icon']) ?> me-1"></i> <?= esc($col['photo_count']) ?> items
                                </span>
                            </div>
                            <div class="card-body p-3">
                                <h6 class="mb-1 text-truncate fw-semibold" style="color: var(--text-primary);"><?= esc($col['name']) ?></h6>
                                <p class="text-muted small mb-0 text-truncate"><?= esc($col['description']) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="h6 mb-0 fw-bold">My Albums</h5>
</div>

<?php if (empty($albums)): ?>
    <div class="text-center py-5">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: rgba(128,128,128,0.15);">
            <i class="bi bi-folder2-open text-primary" style="font-size: 2.5rem;"></i>
        </div>
        <h3 class="h5">No albums yet</h3>
        <p class="text-muted">Create your first album to start organizing your memories.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($albums as $album): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <a href="<?= base_url('albums/' . $album['id']) ?>" class="text-decoration-none group">
                    <div class="card border shadow-sm overflow-hidden h-100 album-card">
                        <div class="ratio ratio-1x1 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center">
                            <?php if ($album['thumbnail']): ?>
                                <img src="<?= base_url($album['thumbnail']) ?>" class="object-fit-cover w-100 h-100 transition-transform" alt="<?= esc($album['name']) ?>">
                            <?php else: ?>
                                <i class="bi bi-images text-muted opacity-50" style="font-size: 3rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3">
                            <h6 class="mb-1 text-truncate fw-semibold"><?= esc($album['name']) ?></h6>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if (! empty($album['is_smart'])): ?>
                                    <span class="badge bg-info text-white">Smart</span>
                                <?php endif; ?>
                                <span class="text-muted small"><?= (int) ($album['photo_count'] ?? 0) ?> items</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create Album Modal -->
<div class="modal fade" id="createAlbumModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Album</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formCreateAlbum">
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Album type</label>
                        <select name="album_type" id="createAlbumType" class="form-select">
                            <option value="standard">Standard — you choose photos</option>
                            <option value="smart">Smart — photos match rules</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Album Name</label>
                        <input type="text" name="name" class="form-control p-2" placeholder="e.g. Summer Trip 2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Description (Optional)</label>
                        <textarea name="description" class="form-control p-2" rows="3"></textarea>
                    </div>
                    <div id="createSmartRuleFields" class="d-none border rounded p-3 mb-3" style="border-color: var(--border-color) !important;">
                        <p class="small text-muted mb-3">Choose at least one rule. All enabled rules apply together (AND).</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Taken on or after</label>
                                <input type="date" name="date_from" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Taken on or before</label>
                                <input type="date" name="date_to" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Camera (matches EXIF, e.g. Canon or iPhone)</label>
                                <input type="text" name="camera_contains" class="form-control" placeholder="Optional substring">
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="has_gps" value="1" id="createHasGps">
                                    <label class="form-check-label" for="createHasGps">Has GPS location</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="favorite_only" value="1" id="createFavOnly">
                                    <label class="form-check-label" for="createFavOnly">Favorites only</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">GPS bounds (optional — leave blank to ignore)</label>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Min latitude</label>
                                <input type="number" name="min_latitude" step="any" class="form-control" placeholder="-90 to 90">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Max latitude</label>
                                <input type="number" name="max_latitude" step="any" class="form-control" placeholder="-90 to 90">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Min longitude</label>
                                <input type="number" name="min_longitude" step="any" class="form-control" placeholder="-180 to 180">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Max longitude</label>
                                <input type="number" name="max_longitude" step="any" class="form-control" placeholder="-180 to 180">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Media type</label>
                                <select name="mime_kind" class="form-select">
                                    <option value="<?= esc(SmartAlbumRules::MIME_ANY) ?>">Photos and videos</option>
                                    <option value="<?= esc(SmartAlbumRules::MIME_IMAGE) ?>">Photos only</option>
                                    <option value="<?= esc(SmartAlbumRules::MIME_VIDEO) ?>">Videos only</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">AI Detected Tags (comma-separated, e.g. dog, cat, car, food, beach)</label>
                                <input type="text" name="ai_tags" class="form-control" placeholder="dog, cat, car, pizza...">
                                <span class="text-muted extra-small">Automatically matches YOLOv8 and CLIP scene classifications</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary p-2 fw-bold">Create Album</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.album-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.album-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.3) !important;
}
.album-card img {
    transition: transform 0.5s ease;
}
.album-card:hover img {
    transform: scale(1.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var type = document.getElementById('createAlbumType');
    var fields = document.getElementById('createSmartRuleFields');
    if (!type || !fields) return;
    function sync() {
        fields.classList.toggle('d-none', type.value !== 'smart');
    }
    type.addEventListener('change', sync);
    sync();
});
</script>

<?= $this->endSection() ?>

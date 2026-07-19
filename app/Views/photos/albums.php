<?php
use App\Libraries\SmartAlbumRules;
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 mb-0">Albums</h2>
        <p class="text-white small mb-0">Organize your photos into collections</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlbumModal">
        <i class="bi bi-plus-lg me-2"></i> New Album
    </button>
</div>

<?php if (empty($albums)): ?>
    <div class="text-center py-5">
        <div class="rounded-circle bg-dark d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
            <i class="bi bi-folder2-open text-white" style="font-size: 2.5rem;"></i>
        </div>
        <h3 class="h5 text-white">No albums yet</h3>
        <p class="text-white">Create your first album to start organizing your memories.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($albums as $album): ?>
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <a href="<?= base_url('albums/' . $album['id']) ?>" class="text-decoration-none group">
                    <div class="card bg-dark border-0 shadow-sm overflow-hidden h-100 album-card">
                        <div class="ratio ratio-1x1 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center">
                            <?php if ($album['thumbnail']): ?>
                                <img src="<?= base_url($album['thumbnail']) ?>" class="object-fit-cover w-100 h-100 transition-transform" alt="<?= esc($album['name']) ?>">
                            <?php else: ?>
                                <i class="bi bi-images text-white opacity-25" style="font-size: 3rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-3">
                            <h6 class="text-white mb-1 text-truncate"><?= esc($album['name']) ?></h6>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if (! empty($album['is_smart'])): ?>
                                    <span class="badge bg-info text-dark">Smart</span>
                                <?php endif; ?>
                                <span class="text-white small"><?= (int) ($album['photo_count'] ?? 0) ?> items</span>
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
        <div class="modal-content bg-dark text-white border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Album</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formCreateAlbum">
                    <div class="mb-3">
                        <label class="form-label small text-white text-uppercase fw-bold">Album type</label>
                        <select name="album_type" id="createAlbumType" class="form-select bg-black border-secondary text-white">
                            <option value="standard">Standard — you choose photos</option>
                            <option value="smart">Smart — photos match rules</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white text-uppercase fw-bold">Album Name</label>
                        <input type="text" name="name" class="form-control bg-black border-secondary text-white p-2" placeholder="e.g. Summer Trip 2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white text-uppercase fw-bold">Description (Optional)</label>
                        <textarea name="description" class="form-control bg-black border-secondary text-white p-2" rows="3"></textarea>
                    </div>
                    <div id="createSmartRuleFields" class="d-none border border-secondary rounded p-3 mb-3">
                        <p class="small text-white-50 mb-3">Choose at least one rule. All enabled rules apply together (AND).</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small">Taken on or after</label>
                                <input type="date" name="date_from" class="form-control bg-black border-secondary text-white">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Taken on or before</label>
                                <input type="date" name="date_to" class="form-control bg-black border-secondary text-white">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Camera (matches EXIF, e.g. Canon or iPhone)</label>
                                <input type="text" name="camera_contains" class="form-control bg-black border-secondary text-white" placeholder="Optional substring">
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
                                <label class="form-label small">Media type</label>
                                <select name="mime_kind" class="form-select bg-black border-secondary text-white">
                                    <option value="<?= esc(SmartAlbumRules::MIME_ANY) ?>">Photos and videos</option>
                                    <option value="<?= esc(SmartAlbumRules::MIME_IMAGE) ?>">Photos only</option>
                                    <option value="<?= esc(SmartAlbumRules::MIME_VIDEO) ?>">Videos only</option>
                                </select>
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

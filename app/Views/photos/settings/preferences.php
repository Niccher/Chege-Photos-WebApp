<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-4"><i class="bi bi-sliders text-primary me-2"></i>App &amp; Viewer Preferences</h5>
    
    <!-- Theme Selection -->
    <div class="mb-5">
        <label class="form-label d-block mb-3 small fw-bold text-uppercase tracking-wider">Color Theme</label>
        <div class="row g-3">
            <?php 
            $themes = [
                'auto' => ['name' => 'Auto', 'icon' => 'bi-display'],
                'light' => ['name' => 'Light', 'icon' => 'bi-sun'],
                'dark' => ['name' => 'Dark', 'icon' => 'bi-moon-stars'],
                'solarized' => ['name' => 'Solarized', 'icon' => 'bi-brightness-high'],
            ];
            foreach ($themes as $key => $t):
            ?>
            <div class="col-md-4 col-6">
                <div class="theme-card p-3 rounded-control border text-center cursor-pointer <?= $theme === $key ? 'border-primary bg-primary bg-opacity-10' : '' ?>" 
                     onclick="updateAppTheme('<?= $key ?>')" 
                     style="transition: all 0.2s; border-color: var(--border-color) !important;">
                    <i class="bi <?= $t['icon'] ?> fs-3 d-block mb-2 <?= $theme === $key ? 'text-primary' : '' ?>"></i>
                    <span class="small fw-semibold"><?= $t['name'] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Timeline Grid Density -->
    <div class="mb-5">
        <label class="form-label d-block mb-1 small fw-bold text-uppercase tracking-wider">Timeline Grid Density</label>
        <p class="text-muted small mb-3">Control thumbnail scaling and photo card spacing on the main timeline.</p>
        <div class="row g-3">
            <?php
            $densities = [
                'comfortable' => ['title' => 'Comfortable', 'desc' => 'Large cinematic thumbnails (2–3 per row)', 'icon' => 'bi-grid-fill'],
                'standard'    => ['title' => 'Standard', 'desc' => 'Balanced view (4–5 per row, default)', 'icon' => 'bi-grid-3x3-gap-fill'],
                'compact'     => ['title' => 'Compact / Masonry', 'desc' => 'Maximum photos visible on screen', 'icon' => 'bi-grid-3x2-gap-fill']
            ];
            $currentDensity = $density ?? 'standard';
            foreach ($densities as $dKey => $d):
            ?>
            <div class="col-md-4 col-12">
                <div class="p-3 rounded-3 border cursor-pointer density-card <?= $currentDensity === $dKey ? 'border-primary bg-primary bg-opacity-10' : '' ?>"
                     onclick="updateGridDensity('<?= $dKey ?>')"
                     style="border-color: var(--border-color) !important; transition: all 0.2s;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi <?= $d['icon'] ?> text-primary"></i>
                        <span class="fw-bold small"><?= $d['title'] ?></span>
                    </div>
                    <p class="text-muted small mb-0" style="font-size: 11px;"><?= $d['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Video Playback Options -->
    <div class="p-3 border rounded-3" style="border-color: var(--border-color) !important;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1 fw-bold"><i class="bi bi-play-circle text-info me-2"></i>Video Hover-Autoplay</h6>
                <p class="text-muted small mb-0">Automatically preview video clips muted when hovering your mouse cursor over video thumbnails in the feed.</p>
            </div>
            <div class="form-check form-switch ms-3">
                <input class="form-check-input" type="checkbox" role="switch" id="switchVideoAutoplay" <?= ($videoAutoplay ?? true) ? 'checked' : '' ?> onchange="updateVideoAutoplay(this.checked)" style="cursor: pointer; transform: scale(1.3);">
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    function updateAppTheme(themeKey) {
        $.post(BASE_URL + 'settings/theme', { theme: themeKey }, function(res) {
            if (res.status === 'success') {
                showToast('Theme updated successfully!', 'success');
                document.documentElement.setAttribute('data-theme', themeKey);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                showToast(res.message || 'Theme update failed', 'danger');
            }
        });
    }

    function updateGridDensity(densityKey) {
        $.post(BASE_URL + 'settings/preferences/density', { density: densityKey }, function(res) {
            if (res.status === 'success') {
                showToast('Grid density set to ' + densityKey + '!', 'success');
                localStorage.setItem('chege_photos_grid_density', densityKey);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                showToast(res.message || 'Failed to update grid density', 'danger');
            }
        });
    }

    function updateVideoAutoplay(enabled) {
        $.post(BASE_URL + 'settings/preferences/video-autoplay', { autoplay: enabled ? 1 : 0 }, function(res) {
            if (res.status === 'success') {
                showToast('Video autoplay preference saved!', 'success');
                localStorage.setItem('chege_photos_video_autoplay', enabled ? '1' : '0');
            } else {
                showToast(res.message || 'Failed to update video autoplay', 'danger');
            }
        });
    }
</script>
<?= $this->endSection() ?>

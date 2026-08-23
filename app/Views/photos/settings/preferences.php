<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-4">App Preferences</h5>
    <div class="mb-4">
        <label class="form-label d-block mb-3 small fw-bold">Theme</label>
        <div class="row g-3">
            <?php 
            $themes = [
                'auto' => ['name' => 'Auto', 'icon' => 'bi-display'],
                'light' => ['name' => 'Light', 'icon' => 'bi-sun'],
                'dark' => ['name' => 'Dark', 'icon' => 'bi-moon-stars'],
                'solarized' => ['name' => 'Solarized', 'icon' => 'bi-brightness-high'],
                'grey' => ['name' => 'Grey', 'icon' => 'bi-circle-half']
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
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    function updateAppTheme(themeKey) {
        $.post(BASE_URL + 'settings/theme', { theme: themeKey }, function(res) {
            if (res.status === 'success') {
                showToast('Theme updated successfully!', 'success');
                // Apply the theme immediately by changing attribute on root html tag
                document.documentElement.setAttribute('data-theme', themeKey);
                setTimeout(function() { location.reload(); }, 600);
            } else {
                showToast(res.message || 'Theme update failed', 'danger');
            }
        });
    }
</script>
<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0">Settings</h2>
            <p class="text-muted small">Manage your profile, preferences, and account security.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Navigation Tabs -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-card overflow-hidden" style="background: var(--card-bg);">
                <div class="list-group list-group-flush settings-tabs" id="settingsTabs" role="tablist">
                    <a class="list-group-item list-group-item-action active p-3 border-0 d-flex align-items-center gap-3" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span>Profile</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="security-tab" data-bs-toggle="pill" href="#security" role="tab">
                        <i class="bi bi-shield-lock fs-5"></i>
                        <span>Security</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="preferences-tab" data-bs-toggle="pill" href="#preferences" role="tab">
                        <i class="bi bi-sliders fs-5"></i>
                        <span>Preferences</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="storage-tab" data-bs-toggle="pill" href="#storage" role="tab">
                        <i class="bi bi-cloud-check fs-5"></i>
                        <span>Storage</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Profile &amp; account</h5>
                        <div class="row g-4 mb-4 align-items-start">
                            <div class="col-md-auto text-center text-md-start">
                                <div class="position-relative d-inline-block">
                                    <?php $av = $user->avatar ?? null; ?>
                                    <?php if ($av && is_string($av) && str_starts_with($av, 'uploads/')): ?>
                                        <img src="<?= base_url($av) ?>" alt="" id="settingsAvatarPreview" class="rounded-circle border border-secondary" width="96" height="96" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div id="settingsAvatarPreview" class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold border border-secondary"
                                             style="width:96px;height:96px;background:linear-gradient(135deg,#4285f4,#00c6ff);font-size:2rem;">
                                            <?= strtoupper(substr(($user->username ?? $user->email ?? 'U'), 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mt-3">
                                    <label class="btn btn-outline-primary btn-sm mb-0">
                                        Change photo
                                        <input type="file" id="settingsAvatarInput" name="avatar" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif">
                                    </label>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRemoveAvatar" <?= ($av && str_starts_with((string) $av, 'uploads/')) ? '' : 'disabled' ?>>Remove</button>
                                </div>
                                <p class="small text-muted mt-2 mb-0">JPEG, PNG, WebP, or GIF. Max 2&nbsp;MB.</p>
                            </div>
                            <div class="col">
                                <form id="formProfile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Display name</label>
                                            <input type="text" name="name" class="form-control" value="<?= esc($user->name ?? '') ?>" placeholder="Your name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Username</label>
                                            <input type="text" name="username" class="form-control" value="<?= esc($user->username ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold">Email</label>
                                            <input type="email" class="form-control" value="<?= esc($user->email) ?>" readonly tabindex="-1" aria-readonly="true">
                                            <p class="small text-muted mb-0 mt-1">Your sign-in email cannot be changed here.</p>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <button type="submit" class="btn btn-primary px-4 rounded-pill">Save profile</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Change Password</h5>
                        <form id="formPassword">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div class="tab-pane fade" id="preferences" role="tabpanel">
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
                </div>

                <!-- Storage Tab -->
                <div class="tab-pane fade" id="storage" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Storage Usage</h5>
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div style="max-width: 250px; margin: 0 auto;">
                                    <canvas id="storageChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-column gap-3">
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold"><i class="bi bi-image me-2 text-primary"></i>Photos</span>
                                            <span class="small fw-bold"><?= round($storage['photos'] / 1024 / 1024, 1) ?> MB</span>
                                        </div>
                                    </div>
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold"><i class="bi bi-play-btn me-2 text-success"></i>Videos</span>
                                            <span class="small fw-bold"><?= round($storage['videos'] / 1024 / 1024, 1) ?> MB</span>
                                        </div>
                                    </div>
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold text-muted"><i class="bi bi-hdd-network me-2"></i>Total Used</span>
                                            <span class="small fw-bold text-muted"><?= round($storage['total'] / 1024 / 1024, 1) ?> MB / 1 GB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Storage Chart
    const ctx = document.getElementById('storageChart').getContext('2d');
    const rs = getComputedStyle(document.documentElement);
    const chartBg = [
        rs.getPropertyValue('--chart-1').trim(),
        rs.getPropertyValue('--chart-2').trim(),
        rs.getPropertyValue('--chart-neutral').trim()
    ];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Photos', 'Videos', 'Free'],
            datasets: [{
                data: [
                    <?= $storage['photos'] ?>, 
                    <?= $storage['videos'] ?>, 
                    <?= max(0, (1024 * 1024 * 1024) - $storage['total']) ?>
                ],
                backgroundColor: chartBg,
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            maintainAspectRatio: true
        }
    });

    $('#formProfile').on('submit', function(e) {
        e.preventDefault();
        $.post(BASE_URL + 'settings/profile', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    $('#settingsAvatarInput').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('avatar', file);
        $.ajax({
            url: BASE_URL + 'settings/avatar',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showToast(res.message || 'Upload failed', 'danger');
                }
            },
            error: function () {
                showToast('Upload failed', 'danger');
            }
        });
        $(this).val('');
    });

    $('#btnRemoveAvatar').on('click', function () {
        if ($(this).prop('disabled')) return;
        $.post(BASE_URL + 'settings/avatar/remove', function (res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(res.message || 'Could not remove avatar', 'danger');
            }
        });
    });

    // 3. Password Update
    $('#formPassword').on('submit', function(e) {
        e.preventDefault();
        $.post(BASE_URL + 'settings/password', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                $('#formPassword')[0].reset();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });
});

function updateAppTheme(theme) {
    $.post(BASE_URL + 'settings/theme', { theme: theme }, function(res) {
        if (res.status === 'success') {
            // Apply theme immediately via existing JS function
            if (typeof setAppTheme === 'function') {
                setAppTheme(theme);
                localStorage.setItem('theme', theme);
            }
            // Update UI markers
            $('.theme-card').removeClass('border-primary bg-primary bg-opacity-10').find('i').removeClass('text-primary');
            $(`.theme-card[onclick*="'${theme}'"]`).addClass('border-primary bg-primary bg-opacity-10').find('i').addClass('text-primary');
            
            showToast('Theme synced to your account!', 'success');
        }
    });
}
</script>

<?= $this->endSection() ?>

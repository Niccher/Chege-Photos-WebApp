<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
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
                        <input type="email" class="form-control settings-email-static" value="<?= esc($user->email) ?>" disabled readonly autocomplete="off" aria-label="Sign-in email (not editable)">
                        <p class="small text-muted mb-0 mt-1">This is your sign-in email. It cannot be changed here.</p>
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">Save profile</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>
<?= $this->endSection() ?>

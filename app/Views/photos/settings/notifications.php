<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-2"><i class="bi bi-bell-fill text-warning me-2"></i>Notification Preferences</h5>
    <p class="text-muted small mb-4">Choose what updates and memory digests you want delivered to your email.</p>

    <form id="formNotifications">
        <!-- Memory Digest -->
        <div class="p-3 border rounded-3 mb-3" style="border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>"On This Day" Memory Digest</h6>
                    <p class="text-muted small mb-0">Receive a curated weekly email digest of throwback memories and photos taken in previous years.</p>
                </div>
                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifyMemoryDigest" id="switchMemoryDigest" value="1" <?= ($notifications['notifyMemoryDigest'] ?? true) ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.3);">
                </div>
            </div>
        </div>

        <!-- Storage Quota Warning -->
        <div class="p-3 border rounded-3 mb-3" style="border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-pie-chart text-info me-2"></i>Storage Quota Alerts</h6>
                    <p class="text-muted small mb-0">Get alerted by email when your account reaches 80% and 90% of your allocated storage capacity.</p>
                </div>
                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifyQuotaAlert" id="switchQuotaAlert" value="1" <?= ($notifications['notifyQuotaAlert'] ?? true) ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.3);">
                </div>
            </div>
        </div>

        <!-- Shared Album Alerts -->
        <div class="p-3 border rounded-3 mb-3" style="border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-people text-success me-2"></i>Shared Album Activity</h6>
                    <p class="text-muted small mb-0">Receive an email notification when friends or collaborators contribute new photos to shared albums.</p>
                </div>
                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifyAlbumInvites" id="switchAlbumInvites" value="1" <?= ($notifications['notifyAlbumInvites'] ?? true) ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.3);">
                </div>
            </div>
        </div>

        <!-- Security Notifications -->
        <div class="p-3 border rounded-3 mb-4" style="border-color: var(--border-color) !important;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold"><i class="bi bi-shield-check text-danger me-2"></i>Critical Security Alerts</h6>
                    <p class="text-muted small mb-0">Instant email when a new Android device or browser connects to your account, or when your password changes.</p>
                </div>
                <div class="form-check form-switch ms-3">
                    <input class="form-check-input" type="checkbox" role="switch" name="notifySecurityAlerts" id="switchSecurityAlerts" value="1" <?= ($notifications['notifySecurityAlerts'] ?? true) ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.3);">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveNotifications">
                <i class="bi bi-check-lg me-1"></i> Save Notification Preferences
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#formNotifications').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#btnSaveNotifications');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            var data = {
                notifyMemoryDigest: $('#switchMemoryDigest').is(':checked') ? 1 : 0,
                notifyQuotaAlert: $('#switchQuotaAlert').is(':checked') ? 1 : 0,
                notifyAlbumInvites: $('#switchAlbumInvites').is(':checked') ? 1 : 0,
                notifySecurityAlerts: $('#switchSecurityAlerts').is(':checked') ? 1 : 0,
            };

            $.post(BASE_URL + 'settings/notifications', data, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Notification Preferences');
                if (res.status === 'success') {
                    showToast(res.message || 'Notification preferences updated!', 'success');
                } else {
                    showToast(res.message || 'Failed to update preferences', 'danger');
                }
            }).fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save Notification Preferences');
                showToast('Failed to save notification preferences.', 'danger');
            });
        });
    });
</script>
<?= $this->endSection() ?>

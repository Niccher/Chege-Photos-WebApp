<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
<div class="card border-0 shadow-sm rounded-card p-4 border-danger border-opacity-25" style="background: var(--card-bg); color: var(--text-primary);">
    <h5 class="mb-1 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
    <p class="text-muted small mb-4">Irreversible actions that affect your account and data.</p>

    <div class="border rounded-3 p-4 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h6 class="mb-1 fw-bold">Clear All User Data</h6>
                <p class="text-muted small mb-0">Deletes all your photos, albums, and shares. Resets your profile to a blank state — your account stays active.</p>
            </div>
            <button class="btn btn-outline-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                <i class="bi bi-eraser me-1"></i> Clear Data
            </button>
        </div>
    </div>

    <div class="border border-danger rounded-3 p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h6 class="mb-1 fw-bold text-danger">Delete Account</h6>
                <p class="text-muted small mb-0">Permanently deletes your account and all associated data. This cannot be undone.</p>
            </div>
            <button class="btn btn-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                <i class="bi bi-trash3 me-1"></i> Delete Account
            </button>
        </div>
    </div>
</div>

<!-- Clear Data Confirmation Modal -->
<div class="modal fade" id="clearDataModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-eraser me-2"></i>Clear All Data</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> This action is irreversible!</p>
                <p class="text-muted small">This will permanently delete <strong>all your photos, albums, shares, and shared links</strong>. Your account will be reset to a fresh state.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">CLEAR</span> to confirm:</label>
                    <input type="text" id="clearConfirmInput" class="form-control" placeholder="Type CLEAR here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmClear" disabled>
                    <i class="bi bi-eraser me-1"></i> Clear Everything
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-trash3 me-2"></i>Delete Account</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> This action is irreversible!</p>
                <p class="text-muted small">This will permanently delete <strong>your account, all your photos, albums, shares, and profile data</strong>. You will be logged out immediately.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">DELETE</span> to confirm:</label>
                    <input type="text" id="deleteConfirmInput" class="form-control" placeholder="Type DELETE here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmDelete" disabled>
                    <i class="bi bi-trash3 me-1"></i> Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Danger Zone: enable confirm buttons only when the correct text is typed
        $('#clearConfirmInput').on('input', function () {
            $('#btnConfirmClear').prop('disabled', $(this).val() !== 'CLEAR');
        });
        $('#deleteConfirmInput').on('input', function () {
            $('#btnConfirmDelete').prop('disabled', $(this).val() !== 'DELETE');
        });

        // Clear Data
        $('#btnConfirmClear').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clearing...');
            $.post(BASE_URL + 'settings/clear-data', { confirm: 'CLEAR' }, function (res) {
                if (res.status === 'success') {
                    $('#clearDataModal').modal('hide');
                    showToast(res.message, 'success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    showToast(res.message, 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Clear Everything');
                }
            }).fail(function () {
                showToast('Request failed. Please try again.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Clear Everything');
            });
        });

        // Delete Account
        $('#btnConfirmDelete').on('click', function () {
            var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');
            $.post(BASE_URL + 'settings/delete-account', { confirm: 'DELETE' }, function (res) {
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function () { window.location.href = BASE_URL + 'login'; }, 1500);
                } else {
                    showToast(res.message, 'danger');
                    $btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete My Account');
                }
            }).fail(function () {
                showToast('Request failed. Please try again.', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete My Account');
            });
        });

        // Reset modal inputs when hidden
        $('#clearDataModal, #deleteAccountModal').on('hidden.bs.modal', function () {
            $(this).find('input[type="text"]').val('');
            $(this).find('.btn-danger').prop('disabled', true);
        });
    });
</script>
<?= $this->endSection() ?>

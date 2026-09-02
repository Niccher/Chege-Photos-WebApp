<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">User Accounts</h2>
            <p class="text-muted small mb-0">Monitor database sizes, storage usages, and credentials across the tenant platform.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="color: var(--text-primary);">
                        <thead class="table-theme-header">
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th class="py-3 px-4">Username</th>
                                <th class="py-3">Email</th>
                                <th class="py-3">Name</th>
                                <th class="py-3 text-center">Photos</th>
                                <th class="py-3 text-center">Storage Consumed</th>
                                <th class="py-3">Groups / Roles</th>
                                <th class="py-3">Last Active</th>
                                <th class="py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No registered user accounts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td class="py-3 px-4 fw-semibold">
                                            <?= esc($user['username']) ?>
                                            <?php if (isset($user['active']) && !$user['active']): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-0.5 ms-1" style="font-size: 0.68rem;">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3"><?= esc($user['email']) ?></td>
                                        <td class="py-3"><?= esc($user['name']) ?></td>
                                        <td class="py-3 text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1 rounded-pill">
                                                <?= esc($user['photo_count']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-center fw-bold text-muted"><?= esc($user['storage']) ?></td>
                                        <td class="py-3">
                                            <?php 
                                            $groups = explode(', ', $user['groups']);
                                            foreach ($groups as $grp) {
                                                $class = $grp === 'superadmin' ? 'bg-danger' : ($grp === 'admin' ? 'bg-warning' : 'bg-primary');
                                                echo '<span class="badge ' . $class . ' bg-opacity-25 text-' . str_replace('bg-', '', $class) . ' rounded-pill px-3 py-1 me-1 fw-bold">' . esc($grp) . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="py-3 text-muted small"><?= esc($user['last_active']) ?></td>
                                        <td class="py-3 text-center">
                                            <?php if ((int)$user['id'] !== (int)auth()->id()): ?>
                                                <div class="btn-group">
                                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1 me-1 btn-change-role" 
                                                            data-user-id="<?= $user['id'] ?>" 
                                                            data-username="<?= esc($user['username']) ?>"
                                                            data-role="<?= esc(in_array('superadmin', $groups, true) ? 'superadmin' : (in_array('admin', $groups, true) ? 'admin' : 'user')) ?>"
                                                            title="Change Role">
                                                        <i class="bi bi-shield-lock"></i> Role
                                                    </button>
                                                    <button class="btn <?= (isset($user['active']) && $user['active']) ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm rounded-pill px-2 py-1 me-1 btn-toggle-status" 
                                                            data-user-id="<?= $user['id'] ?>" 
                                                            data-username="<?= esc($user['username']) ?>"
                                                            data-active="<?= (isset($user['active']) && $user['active']) ? '1' : '0' ?>"
                                                            title="<?= (isset($user['active']) && $user['active']) ? 'Suspend/Activate User' : 'Activate User' ?>">
                                                        <i class="bi <?= (isset($user['active']) && $user['active']) ? 'bi-person-dash' : 'bi-person-check' ?>"></i> <?= (isset($user['active']) && $user['active']) ? 'Suspend' : 'Activate' ?>
                                                    </button>
                                                    <button class="btn btn-outline-warning btn-sm rounded-pill px-2 py-1 me-1 btn-purge-user" 
                                                            data-user-id="<?= $user['id'] ?>" 
                                                            data-username="<?= esc($user['username']) ?>"
                                                            title="Purge User Data">
                                                        <i class="bi bi-eraser"></i> Purge
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-2 py-1 btn-delete-user" 
                                                            data-user-id="<?= $user['id'] ?>" 
                                                            data-username="<?= esc($user['username']) ?>"
                                                            title="Delete User">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">You (Current User)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Change Role -->
<div class="modal fade" id="roleModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-shield-lock me-2"></i>Change User Role</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">Adjust privilege authorization group for <strong id="roleUsername"></strong>.</p>
                <form id="formChangeRole">
                    <input type="hidden" name="user_id" id="roleUserId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Role</label>
                        <select name="role" class="form-select bg-light border-0 py-2 text-dark" id="roleSelect">
                            <option value="user">User (Standard Library Access)</option>
                            <option value="admin">Admin (Standard Admins)</option>
                            <option value="superadmin">Superadmin (Global Permissions)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill mt-2">
                        Update Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Purge User Data -->
<div class="modal fade" id="purgeModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-warning text-dark">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Purge User Library</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3">This action is irreversible!</p>
                <p class="text-muted small">
                    This deletes all photos, albums, shared resources, and ML vector references belonging to <strong id="purgeUsername"></strong>. The login account remains active, and their profile is reset to default.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">PURGE</span> to confirm:</label>
                    <input type="text" id="purgeConfirmInput" class="form-control" placeholder="Type PURGE here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning rounded-pill px-4" id="btnConfirmPurge" disabled>
                    <i class="bi bi-eraser me-1"></i> Purge Data
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete User -->
<div class="modal fade" id="deleteModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-trash3 me-2"></i>Delete User Account</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3">This action is irreversible!</p>
                <p class="text-muted small">
                    This permanently terminates <strong id="deleteUsername"></strong>'s login registration, and destroys all of their photos, database records, and ML face alignments.
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">DELETE</span> to confirm:</label>
                    <input type="text" id="deleteConfirmInput" class="form-control" placeholder="Type DELETE here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmDelete" disabled>
                    <i class="bi bi-trash3 me-1"></i> Delete Account
                </button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var targetUserId = null;

        // Role Modal
        $('.btn-change-role').on('click', function() {
            var btn = $(this);
            $('#roleUserId').val(btn.data('user-id'));
            $('#roleUsername').text(btn.data('username'));
            $('#roleSelect').val(btn.data('role'));
            new bootstrap.Modal(document.getElementById('roleModal')).show();
        });

        $('#formChangeRole').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var modal = bootstrap.Modal.getInstance(document.getElementById('roleModal'));
            
            btn.prop('disabled', true).text('Updating...');

            $.post(BASE_URL + 'admin/users/update-role', form.serialize(), function(res) {
                btn.prop('disabled', false).text('Update Role');
                modal.hide();
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).text('Update Role');
                modal.hide();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to update role: ' + err, 'danger');
            });
        });

        // Purge Modal
        $('.btn-purge-user').on('click', function() {
            var btn = $(this);
            targetUserId = btn.data('user-id');
            $('#purgeUsername').text(btn.data('username'));
            $('#purgeConfirmInput').val('');
            $('#btnConfirmPurge').prop('disabled', true);
            new bootstrap.Modal(document.getElementById('purgeModal')).show();
        });

        $('#purgeConfirmInput').on('input', function() {
            $('#btnConfirmPurge').prop('disabled', $(this).val().trim() !== 'PURGE');
        });

        $('#btnConfirmPurge').on('click', function() {
            var btn = $(this);
            var modal = bootstrap.Modal.getInstance(document.getElementById('purgeModal'));
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Purging...');

            $.post(BASE_URL + 'admin/users/purge', { user_id: targetUserId }, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Purge Data');
                modal.hide();
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Purge Data');
                modal.hide();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Purge failed: ' + err, 'danger');
            });
        });

        // Delete Modal
        $('.btn-delete-user').on('click', function() {
            var btn = $(this);
            targetUserId = btn.data('user-id');
            $('#deleteUsername').text(btn.data('username'));
            $('#deleteConfirmInput').val('');
            $('#btnConfirmDelete').prop('disabled', true);
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });

        $('#deleteConfirmInput').on('input', function() {
            $('#btnConfirmDelete').prop('disabled', $(this).val().trim() !== 'DELETE');
        });

        $('#btnConfirmDelete').on('click', function() {
            var btn = $(this);
            var modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');

            $.post(BASE_URL + 'admin/users/delete', { user_id: targetUserId }, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete Account');
                modal.hide();
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete Account');
                modal.hide();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Deletion failed: ' + err, 'danger');
            });
        });

        // Toggle User Status (Suspend/Activate)
        $('.btn-toggle-status').on('click', function() {
            var btn = $(this);
            var userId = btn.data('user-id');
            var username = btn.data('username');
            var isActive = btn.data('active') == '1';
            var actionText = isActive ? 'suspend' : 'activate';

            if (confirm('Are you sure you want to ' + actionText + ' user "' + username + '"?')) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                $.post(BASE_URL + 'admin/users/toggle-status', { user_id: userId }, function(res) {
                    if (res.status === 'success') {
                        showToast(res.message, 'success');
                        setTimeout(function() { location.reload(); }, 1000);
                    } else {
                        showToast(res.message, 'danger');
                        location.reload();
                    }
                }).fail(function(xhr) {
                    var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                    showToast('Failed to change status: ' + err, 'danger');
                    location.reload();
                });
            }
        });
    });
</script>
<?php $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="mb-3 d-flex align-items-center flex-wrap gap-2">
    <a href="<?= base_url('faces') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    <h4 class="mb-0" id="personTitle"><?= esc($label) ?></h4>
    <span class="badge bg-secondary rounded-pill"><?= count($photos) ?> photo<?= count($photos) !== 1 ? 's' : '' ?></span>
    <button class="btn btn-outline-primary btn-sm ms-2" data-bs-toggle="modal" data-bs-target="#renamePersonModal">
        <i class="bi bi-pencil me-1"></i>Rename
    </button>
    <button class="btn btn-outline-warning btn-sm ms-auto fw-semibold" id="btnVaultPerson" data-id="<?= $person['id'] ?>" data-name="<?= esc($label) ?>" data-count="<?= count($photos) ?>">
        <i class="bi bi-shield-lock-fill me-1"></i>Move Person to Vault
    </button>
</div>

<?php if (!empty($photos)): ?>
    <div class="row g-2">
        <?php foreach ($photos as $photo): ?>
            <div class="col-6 col-md-3 col-lg-2">
                <a href="<?= base_url('faces/photo/' . $photo['id'] . '?person=' . $person['id']) ?>" class="text-decoration-none">
                    <div class="photo-card rounded overflow-hidden" style="aspect-ratio:1;background:#1a1a2e;cursor:pointer;position:relative;">
                        <img src="<?= base_url(esc($photo['thumbnail_path'] ?: $photo['path'])) ?>"
                             alt="<?= esc($photo['filename']) ?>"
                             loading="lazy"
                             style="width:100%;height:100%;object-fit:cover;">
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">No photos found for this face.</div>
<?php endif; ?>

<!-- Rename Person Modal -->
<div class="modal fade" id="renamePersonModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title"><i class="bi bi-person me-2"></i>Rename Person</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small text-muted">Person Name</label>
                <input type="text" class="form-control form-control-sm fw-semibold" id="newPersonNameInput" 
                       value="<?= esc($person['name'] ?? '') ?>" placeholder="e.g. John">
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSavePersonName">Save</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    $('#btnSavePersonName').on('click', function() {
        const name = $('#newPersonNameInput').val().trim();
        if (!name) {
            alert('Please enter a name.');
            return;
        }
        const btn = $(this).prop('disabled', true);
        $.post(BASE_URL + 'faces/persons/name/<?= $person['id'] ?>', { name: name }, function(res) {
            if (res.status === 'success') {
                $('#personTitle').text(res.person.name);
                showToast('Person renamed successfully!');
                bootstrap.Modal.getInstance(document.getElementById('renamePersonModal')).hide();
            } else {
                showToast('Rename failed: ' + (res.message || 'Error'), 'danger');
            }
        }, 'json').always(() => btn.prop('disabled', false));
    });

    $('#btnVaultPerson').on('click', function() {
        const pid = $(this).data('id');
        const name = $(this).data('name');
        const count = $(this).data('count');

        if (!confirm(`Move all ${count} photo(s) of ${name} to your Private Locked Vault? They will be hidden from all public views, albums, and searches.`)) return;

        const btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Moving...');

        $.post(BASE_URL + 'vault/hide-person', { person_id: pid }, function(res) {
            if (res.status === 'success') {
                alert(res.message);
                window.location.href = BASE_URL + 'faces';
            } else {
                alert(res.message || 'Failed to move person to vault.');
                btn.prop('disabled', false).html('<i class="bi bi-shield-lock-fill me-1"></i>Move Person to Vault');
            }
        }).fail(function(xhr) {
            const err = xhr.responseJSON ? xhr.responseJSON.message : 'Error moving person to vault. Ensure vault is unlocked.';
            alert(err);
            btn.prop('disabled', false).html('<i class="bi bi-shield-lock-fill me-1"></i>Move Person to Vault');
        });
    });
});
</script>
<?= $this->endSection() ?>

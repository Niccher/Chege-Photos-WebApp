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
        <!-- Navigation Sidebar -->
        <div class="col-lg-3">
            <?= $this->include('photos/settings/_sidebar') ?>
        </div>

        <!-- Subpage Content -->
        <div class="col-lg-9">
            <?= $this->renderSection('settings_content') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->renderSection('settings_scripts') ?>
<?= $this->endSection() ?>

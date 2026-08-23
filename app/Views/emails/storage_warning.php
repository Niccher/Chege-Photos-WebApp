<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Storage Threshold Warning Notice</h2>
<p>Hello,</p>
<div class="alert-box-danger">
    <strong>Notice:</strong> Your account storage usage has exceeded 85% of your quota allocation.
</div>
<p>Please review your library trash or clean temporary files to prevent upload interruptions.</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Manage Photos & Storage</a>
</p>
<p>Best regards,<br>Storage Manager</p>
<?= $this->endSection() ?>

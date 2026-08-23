<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>System Notice - Maintenance Completed</h2>
<p>Hello,</p>
<div class="alert-box-info">
    <strong>Success:</strong> Scheduled maintenance has been completed. Chege Photos is fully back online!
</div>
<p>All services, including web dashboard, background ML scanning, and mobile/API photo uploads, are operational. You can now access your photo library normally.</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Go to My Photos</a>
</p>
<p>Best regards,<br>Operations & Infrastructure Team</p>
<?= $this->endSection() ?>

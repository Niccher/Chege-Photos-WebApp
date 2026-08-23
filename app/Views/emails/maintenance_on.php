<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>System Notice - Maintenance Mode Active</h2>
<p>Hello,</p>
<div class="alert-box">
    <strong>Notice:</strong> Chege Photos is currently undergoing scheduled maintenance and upgrades.
</div>
<p>During this period, the web dashboard and API uploads will be temporarily offline. We expect the system to be back online shortly. All your photos and metadata are safe and untouched.</p>
<p>Thank you for your patience and understanding.</p>
<p>Best regards,<br>Operations & Infrastructure Team</p>
<?= $this->endSection() ?>

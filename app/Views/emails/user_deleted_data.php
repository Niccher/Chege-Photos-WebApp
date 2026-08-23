<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Data Purge Confirmation</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<div class="alert-box-danger">
    <strong>Security Alert:</strong> As per your request, your user account and all associated photo backups, database records, and face recognition data have been permanently deleted from Chege Photos.
</div>
<p>This action is irreversible. All of your personal files have been completely purged from our active storage systems.</p>
<p>Thank you for using Chege Photos. If you have any feedback on how we could improve, we'd love to hear from you.</p>
<p>Best regards,<br>Data Privacy & Security Team</p>
<?= $this->endSection() ?>

<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Security Alert - Profile Settings Updated</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<div class="alert-box-info">
    <strong>Security Notification:</strong> Important profile details (such as email, username, or profile avatar) were recently updated on your account.
</div>
<p>If you made these changes, no further action is required.</p>
<p><strong>If you did not authorize this update:</strong> Please reset your password immediately and contact an administrator to lock your account.</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">View Account Profile</a>
</p>
<p>Best regards,<br>Security Operations Team</p>
<?= $this->endSection() ?>

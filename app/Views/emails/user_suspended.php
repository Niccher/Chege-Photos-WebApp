<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Account Suspended</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<div class="alert-box-danger">
    <strong>Security Alert:</strong> Your Chege Photos account has been suspended by an administrator.
</div>
<p>Due to this suspension, you will temporarily be unable to log in, backup new photos, or access your existing photo library. This can be caused by policy violations, billing discrepancies, or administrative reviews.</p>
<p>If you believe this is a mistake or wish to appeal the suspension, please contact our support administration team.</p>
<p>Best regards,<br>Account Security & Policy Team</p>
<?= $this->endSection() ?>

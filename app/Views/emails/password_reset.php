<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Security Notice - Password Reset Requested</h2>
<p>Hello,</p>
<p>A request to reset your password was received. If you initiated this request, use the secure verification code below to proceed with setting a new password:</p>
<div class="code-display">
    <?= esc($code ?? '948-201') ?>
</div>
<p>If you did not request this, please secure your account immediately or notify our support team.</p>
<p>Best regards,<br>Security Operations</p>
<?= $this->endSection() ?>

<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>SMTP Configuration Test</h2>
<p>Hello Administrator,</p>
<div class="alert-box-info">
    <strong>Success:</strong> This is a sample test email confirming that your SMTP connection settings are working properly.
</div>
<p>Your Chege Photos WebApp is now successfully configured to dispatch outbound emails. If you are reading this message, everything is set up correctly!</p>
<p>Best regards,<br>Photos Administration Console</p>
<?= $this->endSection() ?>

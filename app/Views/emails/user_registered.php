<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Confirm Your Registration</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<p>Thank you for registering an account on Chege Photos. To verify your email address and activate your account, please click the button below:</p>
<p style="text-align: center;">
    <a href="<?= base_url('register/confirm/' . ($token ?? '')) ?>" class="btn-primary">Confirm Email Address</a>
</p>
<p>If you did not create this account, you can safely ignore this email.</p>
<p>Best regards,<br>Chege Photos Team</p>
<?= $this->endSection() ?>

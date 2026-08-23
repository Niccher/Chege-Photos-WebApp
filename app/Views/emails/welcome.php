<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Welcome to Chege Photos!</h2>
<p>Hello,</p>
<p>Your account is active and ready to go. You can now backup, organize, and search your photo collection using AI semantics and face recognition.</p>
<p>To get started, click the button below to access your photo library:</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Access Photo Library</a>
</p>
<p>Best regards,<br>Chege Photos Team</p>
<?= $this->endSection() ?>

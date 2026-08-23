<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Shared Album Invitation</h2>
<p>Hello,</p>
<div class="alert-box-info">
    <strong>Shared Album Invite:</strong> You have been invited to view or contribute to a shared album: <strong>"<?= esc($album_name ?? 'Family Gathering 2026') ?>"</strong>.
</div>
<p>This album was shared by <strong><?= esc($sender_name ?? 'Admin') ?></strong>. By joining, you'll be able to view their photos, download high-resolution copies, and optionally upload your own memories to the collection.</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos/shared/' . ($album_token ?? 'invite_token_sample')) ?>" class="btn-primary">View Shared Album</a>
</p>
<p>Best regards,<br>Chege Photos Collaboration Suite</p>
<?= $this->endSection() ?>

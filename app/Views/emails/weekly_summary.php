<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Your Weekly Library Summary</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<p>Here is your weekly activity report for your Chege Photos library:</p>
<div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef;">
    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;">
        <span style="font-weight: 500; color: #495057;">📸 New Backups:</span>
        <span style="font-weight: bold; color: #4285f4;">+42 Photos & Videos</span>
    </div>
    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 12px;">
        <span style="font-weight: 500; color: #495057;">👤 Faces Identified:</span>
        <span style="font-weight: bold; color: #198754;">5 People Clustering Sweeps</span>
    </div>
    <div style="display: flex; justify-content: space-between;">
        <span style="font-weight: 500; color: #495057;">💾 Storage Remaining:</span>
        <span style="font-weight: bold; color: #0dcaf0;">8.4 GB of 10 GB Free</span>
    </div>
</div>
<p>Keep backing up to make sure your memories are securely stored and searchable with AI!</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Open Photos App</a>
</p>
<p>Best regards,<br>Chege Photos Team</p>
<?= $this->endSection() ?>

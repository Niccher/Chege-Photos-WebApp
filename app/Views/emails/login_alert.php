<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Security Alert - New Sign-in Detected</h2>
<p>Hello <?= esc($username ?? 'User') ?>,</p>
<div class="alert-box">
    <strong>Notice:</strong> Your Chege Photos account was accessed from a new or unrecognized device.
</div>
<table style="width:100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;">
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 500; width: 120px;">Device:</td>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= esc($device_name ?? 'Chrome Browser on Linux') ?></td>
    </tr>
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 500;">IP Address:</td>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= esc($ip_address ?? '192.168.100.80') ?></td>
    </tr>
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 500;">Time:</td>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?= date('F j, Y, g:i a') ?></td>
    </tr>
</table>
<p>If this was you, no action is needed. If you do not recognize this activity, please change your password immediately to secure your account.</p>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Review Active Sessions</a>
</p>
<p>Best regards,<br>Security Operations Team</p>
<?= $this->endSection() ?>

<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>System Administrative Alert - ML Task Completed</h2>
<p>Hello Administrator,</p>
<div class="alert-box-info">
    <strong>System Status Update:</strong> The scheduled background ML processing sweep completed successfully.
</div>
<table style="width:100%; border-collapse: collapse; margin-bottom: 20px;">
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 500; width: 120px;">Status:</td>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; color: #198754; font-weight: 600;">Healthy</td>
    </tr>
    <tr>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee; font-weight: 500;">Queue Status:</td>
        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">Idle</td>
    </tr>
</table>
<p style="text-align: center;">
    <a href="<?= base_url('admin/smtp') ?>" class="btn-primary">Open Admin Console</a>
</p>
<p>Best regards,<br>System Administration Console</p>
<?= $this->endSection() ?>

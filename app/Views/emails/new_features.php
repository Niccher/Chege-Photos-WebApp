<?= $this->extend('emails/layout') ?>
<?= $this->section('content') ?>
<h2>Discover What's New in Chege Photos!</h2>
<p>Hello,</p>
<p>We've rolled out exciting new features to help you navigate, search, and manage your photo library even better with artificial intelligence:</p>
<table style="width:100%; border-collapse: collapse; margin-bottom: 25px;">
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #eee;">
            <strong style="display:block; color:#4285f4; font-size:16px;">🔍 AI Semantic Image Search</strong>
            <span style="font-size:14px; color:#666;">Search for conceptual queries like "sunset by the beach" or "person playing guitar" using advanced CLIP embeddings.</span>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #eee;">
            <strong style="display:block; color:#4285f4; font-size:16px;">👤 Improved Face Clustering</strong>
            <span style="font-size:14px; color:#666;">Our system now maps and clusters similar faces automatically with greater precision, making it easier to name and locate friends.</span>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #eee;">
            <strong style="display:block; color:#4285f4; font-size:16px;">⚡ Ultra-Fast Upload Queues</strong>
            <span style="font-size:14px; color:#666;">Enjoy concurrent chunked uploads directly from your browser with drag-and-drop support.</span>
        </td>
    </tr>
</table>
<p style="text-align: center;">
    <a href="<?= base_url('photos') ?>" class="btn-primary">Explore New Features</a>
</p>
<p>Best regards,<br>Chege Photos Team</p>
<?= $this->endSection() ?>

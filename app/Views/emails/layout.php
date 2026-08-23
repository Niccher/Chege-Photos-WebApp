<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?? 'Notification' ?></title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        .email-header {
            background: linear-gradient(135deg, #4285f4, #00c6ff);
            padding: 32px;
            text-align: center;
        }
        .email-header img {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-bottom: 12px;
        }
        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .email-body {
            padding: 40px 32px;
            color: #333333;
            line-height: 1.6;
            font-size: 15px;
        }
        .email-body h2 {
            font-size: 20px;
            font-weight: 600;
            color: #111111;
            margin-top: 0;
            margin-bottom: 20px;
            letter-spacing: -0.3px;
        }
        .email-body p {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .btn-primary {
            display: inline-block;
            background: #4285f4;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 500;
            margin: 10px 0 25px 0;
            box-shadow: 0 2px 5px rgba(66, 133, 244, 0.3);
        }
        .btn-primary:hover {
            background: #357ae8;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            color: #664d03;
        }
        .alert-box-danger {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            color: #842029;
        }
        .alert-box-info {
            background-color: #cff4fc;
            border-left: 4px solid #0dcaf0;
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            color: #087990;
        }
        .code-display {
            background-color: #f1f3f5;
            padding: 16px;
            border-radius: 8px;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 2px;
            text-align: center;
            color: #212529;
            margin: 20px 0;
            border: 1px dashed #ced4da;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #eee;
            font-size: 13px;
            color: #6c757d;
        }
        .email-footer p {
            margin: 4px 0;
        }
        .tracking-id {
            display: inline-block;
            background-color: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            color: #495057;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <img src="<?= base_url('app_icon.png') ?>" alt="Chege Photos" width="48" height="48">
            <h1>Chege Photos</h1>
        </div>
        <div class="email-body">
            <?= $this->renderSection('content') ?>
        </div>
        <div class="email-footer">
            <p><strong>Chege Photos Console</strong></p>
            <p>AI-Powered Photo Backup, Semantics and Face Recognition Suite</p>
            <p>&copy; <?= date('Y') ?> Chege Photos. All rights reserved.</p>
            <?php if (!empty($trackingId)): ?>
                <div><span class="tracking-id">ID: <?= esc($trackingId) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

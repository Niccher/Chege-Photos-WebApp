<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?: 'Photos — Sign in' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="icon" type="image/png" href="<?= base_url('app_icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('app_icon.png') ?>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .brand-logo {
            font-size: 2.5rem;
            color: #4285f4;
            margin-bottom: 0.25rem;
        }
        .brand-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }
        .brand-sub {
            color: rgba(255,255,255,0.55);
            font-size: 0.875rem;
        }
        .auth-label {
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 0.35rem;
        }
        .auth-input {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            color: #fff;
            padding: 0.7rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .auth-input:focus {
            background: rgba(255,255,255,0.15);
            border-color: #4285f4;
            box-shadow: 0 0 0 3px rgba(66,133,244,0.25);
            color: #fff;
            outline: none;
        }
        .auth-input::placeholder { color: rgba(255,255,255,0.3); }
        .btn-auth {
            background: linear-gradient(135deg, #4285f4, #00c6ff);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem;
            font-size: 0.95rem;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-auth:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(66,133,244,0.4);
            color: #fff;
        }
        .auth-divider {
            border-color: rgba(255,255,255,0.12);
            margin: 1.5rem 0;
        }
        .auth-link {
            color: #4285f4;
            text-decoration: none;
            font-weight: 500;
        }
        .auth-link:hover { text-decoration: underline; color: #71a7f7; }
        .alert-auth {
            background: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.4);
            border-radius: 10px;
            color: #ff8a8a;
            font-size: 0.85rem;
        }
        .form-check-input:checked {
            background-color: #4285f4;
            border-color: #4285f4;
        }
        .form-check-label { color: rgba(255,255,255,0.6); font-size: 0.85rem; }
    </style>
</head>
<body>
    <?= $this->renderSection('content') ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

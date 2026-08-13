<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bad Request — Chege Photos</title>
    <meta name="description" content="The request could not be understood by the server due to malformed syntax.">
    <meta name="robots" content="noindex, follow">
    <script>
        (function() {
            var t = localStorage.getItem('theme');
            if (t && t !== 'auto') document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('app_icon.png') ?>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            min-height: 100vh;
            background: var(--bg-primary, linear-gradient(135deg, #0f0c29, #302b63, #24243e));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: var(--text-primary, #fff);
            margin: 0;
        }
        .error-card {
            background: var(--card-bg, rgba(255,255,255,0.08));
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color, rgba(255,255,255,0.15));
            border-radius: 24px;
            padding: 3rem 2rem;
            width: 100%;
            max-width: 520px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        .error-icon {
            font-size: 4.5rem;
            color: var(--accent-color, #a6c1ee);
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        .error-code {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-muted, #a6c1ee);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary, #fff);
        }
        .error-desc {
            color: var(--text-muted, rgba(255,255,255,0.6));
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-home {
            background: var(--accent-color, #4285f4);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            padding: 0.8rem 2rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px color-mix(in srgb, var(--accent-color, #4285f4) 35%, transparent);
            color: #fff;
        }
        .error-links {
            margin-top: 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .error-links a {
            color: var(--text-muted, rgba(255,255,255,0.6));
            font-size: 0.88rem;
            text-decoration: none;
        }
        .error-links a:hover { color: var(--accent-color, #4285f4); }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon"><i class="bi bi-patch-question-fill"></i></div>
        <div class="error-code">400</div>
        <h1 class="error-title">Bad Request</h1>
        <p class="error-desc">
            Your browser sent a request this server could not understand. Check the URL and try again.
        </p>
        <a href="<?= base_url() ?>" class="btn-home">
            <i class="bi bi-house-door-fill"></i> Back to Home
        </a>
        <div class="error-links">
            <a href="<?= base_url('about') ?>">About Chege Photos</a>
            <a href="<?= url_to('login') ?>">Sign In</a>
        </div>
    </div>
</body>
</html>

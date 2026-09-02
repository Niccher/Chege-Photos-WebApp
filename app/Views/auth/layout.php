<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?: 'Chege Photos — Sign in' ?></title>
    <meta name="description" content="Sign in or register for Chege Photos self-hosted photo management.">
    <link rel="canonical" href="<?= current_url() ?>">
    <meta name="theme-color" content="#4f46e5">
    <meta property="og:site_name" content="Chege Photos">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="<?= $this->renderSection('title') ?: 'Chege Photos — Sign in' ?>">
    <meta property="og:description" content="Sign in or register for Chege Photos self-hosted photo management.">
    <meta property="og:image" content="<?= base_url('app_icon.png') ?>">
    <!-- Bulletproof Zero-Dependency Theme Engine -->
    <script>
        window.setAppTheme = function(theme) {
            var root = document.documentElement;
            var t = theme || 'auto';
            try {
                localStorage.setItem('theme', t);
            } catch(e) {}
            
            if (t === 'auto') {
                root.removeAttribute('data-theme');
                var isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
            } else {
                root.setAttribute('data-theme', t);
                if (t === 'dark' || t === 'grey') {
                    root.setAttribute('data-bs-theme', 'dark');
                } else {
                    root.setAttribute('data-bs-theme', 'light');
                }
            }
            
            var opts = document.querySelectorAll('.theme-opt');
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].getAttribute('data-theme') === t) {
                    opts[i].classList.add('active');
                } else {
                    opts[i].classList.remove('active');
                }
            }
        };

        (function() {
            var saved = 'auto';
            try {
                saved = localStorage.getItem('theme') || 'auto';
            } catch(e) {}
            window.setAppTheme(saved);
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('css/style.css?v=' . (file_exists(FCPATH . 'css/style.css') ? filemtime(FCPATH . 'css/style.css') : '1.0')) ?>">
    <link rel="stylesheet" href="<?= base_url('css/photos.css?v=' . (file_exists(FCPATH . 'css/photos.css') ? filemtime(FCPATH . 'css/photos.css') : '1.0')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('app_icon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png') ?>">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            min-height: 100vh;
            background: var(--bg-primary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5rem 1rem 2rem;
            transition: background-color 0.3s ease;
        }
        .auth-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1030;
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .auth-brand {
            display: flex; align-items: center; gap: 0.5rem;
            text-decoration: none; font-weight: 600; font-size: 1.1rem;
            color: var(--text-primary);
        }
        .auth-brand:hover { color: var(--accent-color); }
        .auth-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        .brand-logo { font-size: 2.5rem; color: var(--accent-color); margin-bottom: 0.25rem; }
        .brand-title { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem; }
        .brand-sub { color: var(--text-muted); font-size: 0.875rem; }
        .auth-label { color: var(--text-muted); font-size: 0.8rem; font-weight: 500; margin-bottom: 0.35rem; }
        .auth-input {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            padding: 0.7rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .auth-input:focus {
            background: var(--card-bg);
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-color) 25%, transparent);
            color: var(--text-primary);
            outline: none;
        }
        .auth-input::placeholder { color: var(--text-muted); opacity: 0.5; }
        .btn-auth {
            background: var(--accent-color);
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
            box-shadow: 0 8px 20px color-mix(in srgb, var(--accent-color) 40%, transparent);
            color: #fff;
        }
        .auth-divider { border-color: var(--border-color); margin: 1.5rem 0; }
        .auth-link { color: var(--accent-color); text-decoration: none; font-weight: 500; }
        .auth-link:hover { text-decoration: underline; opacity: 0.85; }
        .alert-auth {
            background: color-mix(in srgb, #dc3545 12%, transparent);
            border: 1px solid color-mix(in srgb, #dc3545 30%, transparent);
            border-radius: 10px;
            color: #dc3545;
            font-size: 0.85rem;
        }
        .alert-success-auth {
            background: color-mix(in srgb, #198754 12%, transparent);
            border: 1px solid color-mix(in srgb, #198754 30%, transparent);
            border-radius: 10px;
            color: #198754;
            font-size: 0.85rem;
        }
        .form-check-input:checked { background-color: var(--accent-color); border-color: var(--accent-color); }
        .form-check-label { color: var(--text-muted); font-size: 0.85rem; }
        .theme-btn-auth {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-primary);
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .theme-btn-auth:hover {
            background: var(--card-bg);
            border-color: var(--accent-color);
        }
        .theme-btn-auth:active { background: var(--card-bg); }
    </style>
</head>
<body>
    <nav class="auth-nav glass-effect">
        <a href="<?= base_url() ?>" class="auth-brand">
            <img src="<?= base_url('app_icon.png') ?>" alt="Logo" width="28" height="28" class="rounded shadow-sm">
            <span>Photos</span>
        </a>
        <div class="dropdown">
            <button class="theme-btn-auth" data-bs-toggle="dropdown" title="Change Theme">
                <i class="bi bi-palette me-1"></i> Theme
            </button>
            <ul class="dropdown-menu dropdown-menu-end glass-effect shadow border-0 p-2" style="min-width: 150px;">
                <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="javascript:void(0)" onclick="setAppTheme('auto'); return false;" data-theme="auto"><i class="bi bi-display me-2"></i>Auto (OS)</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="javascript:void(0)" onclick="setAppTheme('light'); return false;" data-theme="light"><i class="bi bi-sun me-2"></i>Light</a></li>
                <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="javascript:void(0)" onclick="setAppTheme('dark'); return false;" data-theme="dark"><i class="bi bi-moon-stars me-2"></i>Dark</a></li>
                <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="javascript:void(0)" onclick="setAppTheme('solarized'); return false;" data-theme="solarized"><i class="bi bi-brightness-high me-2"></i>Solarized</a></li>
                <li><a class="dropdown-item rounded-3 theme-opt" href="javascript:void(0)" onclick="setAppTheme('grey'); return false;" data-theme="grey"><i class="bi bi-circle-half me-2"></i>Grey</a></li>
            </ul>
        </div>
    </nav>

    <?= $this->renderSection('content') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(function() {
        const savedTheme = localStorage.getItem('theme') || 'auto';
        if (typeof window.setAppTheme === 'function') {
            window.setAppTheme(savedTheme);
        }
    });
    </script>
</body>
</html>

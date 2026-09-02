<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> — Chege Photos</title>
    <meta name="description" content="<?= $this->renderSection('description') ?>">
    <link rel="canonical" href="<?= current_url() ?>">
    <meta name="theme-color" content="#4f46e5">
    <meta property="og:site_name" content="Chege Photos">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:title" content="<?= $this->renderSection('title') ?> — Chege Photos">
    <meta property="og:description" content="<?= $this->renderSection('description') ?: 'Self-hosted photo management platform with ML-powered face recognition.' ?>">
    <meta property="og:image" content="<?= base_url('app_icon.png') ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= $this->renderSection('title') ?> — Chege Photos">
    <meta name="twitter:description" content="<?= $this->renderSection('description') ?: 'Self-hosted photo management platform with ML-powered face recognition.' ?>">
    <meta name="twitter:image" content="<?= base_url('app_icon.png') ?>">
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= base_url('css/style.css?v=' . (file_exists(FCPATH . 'css/style.css') ? filemtime(FCPATH . 'css/style.css') : '1.0')) ?>">
    <link rel="stylesheet" href="<?= base_url('css/photos.css?v=' . (file_exists(FCPATH . 'css/photos.css') ? filemtime(FCPATH . 'css/photos.css') : '1.0')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" href="<?= base_url('app_icon.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('apple-touch-icon.png') ?>">
    <?= $this->renderSection('head') ?>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .pub-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1030;
            padding: 0.6rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .pub-brand {
            display: flex; align-items: center; gap: 0.5rem;
            text-decoration: none; font-weight: 700; font-size: 1.15rem;
            color: var(--text-primary);
        }
        .pub-brand:hover { color: var(--accent-color); }

        .nav-links { display: flex; align-items: center; gap: 0.25rem; }
        .nav-links a {
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.88rem;
            font-weight: 500;
            transition: all 0.15s;
        }
        .nav-links a:hover { color: var(--accent-color); background: color-mix(in srgb, var(--accent-color) 8%, transparent); }
        .nav-links a.active { color: var(--accent-color); }
        .nav-auth { display: flex; align-items: center; gap: 0.5rem; }

        .theme-btn-pub {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            color: var(--text-primary);
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .theme-btn-pub:hover {
            background: var(--card-bg);
            border-color: var(--accent-color);
        }

        .btn-primary-pub {
            background: var(--accent-color);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            font-size: 0.88rem;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary-pub:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px color-mix(in srgb, var(--accent-color) 30%, transparent);
            color: #fff;
        }
        .btn-outline-pub {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            font-size: 0.88rem;
            transition: all 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-outline-pub:hover {
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .hero {
            min-height: 85vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 6rem 1.5rem 3rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.15;
            max-width: 800px;
            color: var(--text-primary);
        }
        .hero .accent { color: var(--accent-color); }
        .hero p {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--text-muted);
            max-width: 600px;
            margin: 1.25rem auto 2rem;
            line-height: 1.6;
        }
        .hero-actions { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }

        .section {
            padding: 5rem 1.5rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .section-sm { padding: 3rem 1.5rem; max-width: 1100px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 3rem; }
        .section-header h2 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            color: var(--text-primary);
        }
        .section-header p {
            color: var(--text-muted);
            max-width: 550px;
            margin: 0.75rem auto 0;
            font-size: 1rem;
        }

        .card-pub {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 2rem;
            height: 100%;
            transition: all 0.2s;
        }
        .card-pub:hover {
            border-color: var(--accent-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        }

        .card-icon { font-size: 2rem; color: var(--accent-color); margin-bottom: 1rem; }
        .card-pub h5 { font-weight: 600; color: var(--text-primary); margin-bottom: 0.75rem; }
        .card-pub p { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 0; }

        .feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }

        .step-num {
            display: inline-flex; align-items: center; justify-content: center;
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--accent-color); color: #fff;
            font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }

        .tech-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            background: color-mix(in srgb, var(--accent-color) 10%, transparent);
            color: var(--accent-color);
            margin: 0.15rem;
        }

        .diagram-box {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 2rem;
            font-family: 'Inter', monospace;
            font-size: 0.82rem;
            line-height: 1.6;
            overflow-x: auto;
            white-space: pre;
            color: var(--text-primary);
        }

        .ml-tag {
            display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px;
            font-size: 0.72rem; font-weight: 600; margin: 0.1rem;
        }
        .ml-tag-fastapi { background: #009688; color: #fff; }
        .ml-tag-insight { background: #FF6F00; color: #fff; }
        .ml-tag-qdrant { background: #FF6600; color: #fff; }
        .ml-tag-hdbscan { background: #7B1FA2; color: #fff; }

        .stack-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 48px; height: 48px; border-radius: 12px;
            background: color-mix(in srgb, var(--accent-color) 10%, transparent);
            color: var(--accent-color); font-size: 1.4rem; margin-bottom: 0.75rem;
        }

        .page-header {
            padding: 7rem 1.5rem 2.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }
        .page-header h1 {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800;
            color: var(--text-primary);
        }
        .page-header p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0.75rem auto 0;
        }

        .footer-pub {
            border-top: 1px solid var(--border-color);
            padding: 3rem 1.5rem 2rem;
        }
        .footer-pub a { color: var(--text-muted); text-decoration: none; font-size: 0.85rem; }
        .footer-pub a:hover { color: var(--accent-color); }

        pre.code-block {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            overflow-x: auto;
            color: var(--text-primary);
        }

        .table-pub { color: var(--text-primary); font-size: 0.88rem; }
        .table-pub th { border-color: var(--border-color); font-weight: 600; }
        .table-pub td { border-color: var(--border-color); }
        .table-pub > :not(caption) > * > * { border-bottom-color: var(--border-color); }

        .accordion-pub { --bs-accordion-bg: var(--card-bg); --bs-accordion-color: var(--text-primary); --bs-accordion-border-color: var(--border-color); --bs-accordion-btn-color: var(--text-primary); --bs-accordion-active-bg: color-mix(in srgb, var(--accent-color) 8%, transparent); --bs-accordion-active-color: var(--accent-color); --bs-accordion-btn-focus-box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent-color) 30%, transparent); }

        @media (max-width: 768px) {
            .hero { min-height: 70vh; }
            .section { padding: 3rem 1.25rem; }
            .feature-grid { grid-template-columns: 1fr; }
            .nav-links { gap: 0; }
            .nav-links a { padding: 0.3rem 0.5rem; font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<nav class="pub-nav glass-effect">
    <a href="<?= base_url() ?>" class="pub-brand">
        <img src="<?= base_url('app_icon.png') ?>" alt="Logo" width="28" height="28" class="rounded shadow-sm">
        <span>Chege Photos</span>
    </a>
    <div class="nav-links d-none d-md-flex">
        <a href="<?= base_url() ?>" class="<?= $this->renderSection('nav-home') ?>" aria-label="Home">Home</a>
        <a href="<?= base_url('about') ?>" class="<?= $this->renderSection('nav-about') ?>" aria-label="About the platform">About</a>
        <a href="<?= base_url('android') ?>" class="<?= $this->renderSection('nav-android') ?>" aria-label="Android app">Android</a>
        <a href="<?= base_url('ml') ?>" class="<?= $this->renderSection('nav-ml') ?>" aria-label="ML backend">ML Backend</a>
        <a href="<?= base_url('setup') ?>" class="<?= $this->renderSection('nav-setup') ?>" aria-label="Setup guide">Setup</a>
        <a href="<?= base_url('faq') ?>" class="<?= $this->renderSection('nav-faq') ?>" aria-label="Frequently asked questions">FAQ</a>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="theme-btn-pub" data-bs-toggle="dropdown" title="Change Theme" aria-label="Change theme">
                <i class="bi bi-palette"></i>
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
        <a href="<?= url_to('login') ?>" class="btn-outline-pub btn-sm px-3 py-1">Sign In</a>
        <a href="<?= url_to('register') ?>" class="btn-primary-pub btn-sm px-3 py-1 d-none d-sm-inline">Get Started</a>
    </div>
</nav>

<main>
    <?= $this->renderSection('content') ?>
</main>

<footer class="footer-pub">
    <div class="max-w-1100" style="max-width: 1100px; margin: 0 auto;">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2 mb-2 fw-bold" style="color: var(--text-primary);">
                    <img src="<?= base_url('app_icon.png') ?>" alt="Logo" width="22" height="22" class="rounded">
                    Chege Photos
                </div>
                <p style="color: var(--text-muted); font-size: 0.85rem; line-height: 1.6;">Self-hosted photo management platform with ML-powered face recognition. Web, Android, and ML service — all on your own infrastructure.</p>
            </div>
            <div class="col-md-2">
                <h6 style="color: var(--text-primary); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Pages</h6>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= base_url() ?>">Home</a>
                    <a href="<?= base_url('about') ?>">About</a>
                    <a href="<?= base_url('android') ?>">Android App</a>
                    <a href="<?= base_url('ml') ?>">ML Backend</a>
                    <a href="<?= base_url('setup') ?>">Setup Guide</a>
                    <a href="<?= base_url('faq') ?>">FAQ</a>
                </div>
            </div>
            <div class="col-md-3">
                <h6 style="color: var(--text-primary); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Get Started</h6>
                <div class="d-flex flex-column gap-1">
                    <a href="<?= url_to('register') ?>">Create Account</a>
                    <a href="<?= url_to('login') ?>">Sign In</a>
                    <a href="<?= url_to('login') ?>">Forgot Password</a>
                </div>
            </div>
            <div class="col-md-3">
                <h6 style="color: var(--text-primary); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Tech Stack</h6>
                <div class="d-flex flex-wrap gap-1">
                    <span class="tech-badge">PHP 8.3</span>
                    <span class="tech-badge">CI4</span>
                    <span class="tech-badge">MySQL 8.4</span>
                    <span class="tech-badge">Python 3.12</span>
                    <span class="tech-badge">FastAPI</span>
                    <span class="tech-badge">Insightface</span>
                    <span class="tech-badge">Qdrant</span>
                    <span class="tech-badge">Kotlin</span>
                    <span class="tech-badge">Docker</span>
                </div>
            </div>
        </div>
        <hr style="border-color: var(--border-color); margin: 2rem 0 1rem;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span style="color: var(--text-muted); font-size: 0.82rem;">&copy; <?= date('Y') ?> Chege Photos. Released under the MIT License.</span>
            <span style="color: var(--text-muted); font-size: 0.82rem;">
                <i class="bi bi-github me-1"></i>Open Source
            </span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function() {
    const saved = localStorage.getItem('theme') || 'auto';
    if (typeof window.setAppTheme === 'function') {
        window.setAppTheme(saved);
    }
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

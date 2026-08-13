<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Self-hosted photo management with ML face recognition<?= $this->endSection() ?>
<?= $this->section('description') ?>Chege Photos is a self-hosted photo management platform with ML-powered face recognition. Upload, organise, search, and share your photos with automatic face detection and clustering.<?= $this->endSection() ?>
<?= $this->section('nav-home') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Chege Photos — Self-hosted photo management with ML face recognition">
<meta property="og:description" content="Upload, organise, search, and share your photos on your own infrastructure. Automatic face detection, clustering, and similarity search via Insightface and Qdrant.">
<meta property="og:type" content="website">
<meta name="keywords" content="self-hosted, photo management, face recognition, ML, docker, open source, photo library">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<section class="hero">
    <h1>Your photos. <span class="accent">Intelligently organised.</span></h1>
    <p>Self-hosted photo management with automatic face recognition, smart albums, and full control over your data. Upload, browse, search, and share — all on your own infrastructure. No subscriptions, no third-party cloud.</p>
    <div class="hero-actions">
        <a href="<?= url_to('register') ?>" class="btn-primary-pub px-4 py-2" style="font-size:1rem;">
            <i class="bi bi-person-plus me-2"></i>Get Started Free
        </a>
        <a href="<?= url_to('login') ?>" class="btn-outline-pub px-4 py-2" style="font-size:1rem;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </a>
    </div>
    <div class="mt-4 d-flex gap-2 flex-wrap justify-content-center" style="font-size: 0.8rem; color: var(--text-muted);">
        <span class="tech-badge">PHP 8.3</span>
        <span class="tech-badge">CodeIgniter 4</span>
        <span class="tech-badge">MySQL 8.4</span>
        <span class="tech-badge">Python 3.12</span>
        <span class="tech-badge">FastAPI</span>
        <span class="tech-badge">Insightface</span>
        <span class="tech-badge">Qdrant</span>
        <span class="tech-badge">Kotlin</span>
        <span class="tech-badge">Jetpack Compose</span>
        <span class="tech-badge">Docker</span>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Three components, one platform</h2>
        <p>Chege Photos is a full-stack ecosystem — a web app for desktop management, an Android companion for on-the-go access, and an ML service that automatically organises faces.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-window-stack"></i></div>
                <h5>Web App</h5>
                <p>PHP/CodeIgniter 4 backend with a responsive Bootstrap 5 UI. Full photo management: upload, albums, sharing, analytics, memories, and face recognition dashboard.</p>
                <a href="<?= base_url('about') ?>" class="auth-link" style="font-size:0.85rem;">Learn more <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-phone"></i></div>
                <h5>Android App</h5>
                <p>Native Kotlin app built with Jetpack Compose. Syncs photos from your device, browses your remote library with face bounding box overlays, and supports biometric unlock.</p>
                <a href="<?= base_url('android') ?>" class="auth-link" style="font-size:0.85rem;">Learn more <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div class="card-icon"><i class="bi bi-cpu"></i></div>
                <h5>ML Backend</h5>
                <p>Python/FastAPI microservice with Insightface (Buffalo-L) for face detection and embedding, Qdrant for vector storage and similarity search, and HDBSCAN for clustering.</p>
                <a href="<?= base_url('ml') ?>" class="auth-link" style="font-size:0.85rem;">Learn more <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Why self-host with Chege Photos?</h2>
        <p>Full data ownership, no recurring fees, and ML-powered organisation that improves as your library grows.</p>
    </div>
    <div class="feature-grid">
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-shield-check"></i></div>
            <h5>Complete Privacy</h5>
            <p>Every photo stays on your own hardware. No third-party servers, no data mining, no cloud storage fees. You own your entire library.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-people"></i></div>
            <h5>Automatic Face Recognition</h5>
            <p>Insightface detects every face across your photos. HDBSCAN clustering automatically groups them into persons. No manual tagging required.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-search-heart"></i></div>
            <h5>Similarity Search</h5>
            <p>Upload any photo and find every matching face in your library in under a second. Powered by Qdrant's approximate nearest neighbour index.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-phone"></i></div>
            <h5>Mobile Companion</h5>
            <p>Android app with biometric unlock, QR-based token login, and full face recognition UI — including per-person photo pagers with bounding box overlays.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-shield-lock"></i></div>
            <h5>Superuser Console</h5>
            <p>Dedicated administration panel to manage user roles, customize storage limits, inspect SMTP logs, and dynamically swap model weights or edit cron frequencies.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-box"></i></div>
            <h5>Docker-Powered</h5>
            <p>Everything runs in Docker containers. Single <code>docker compose up</code> deploys the entire stack. Offline builds via vendored wheels.</p>
        </div>
        <div class="card-pub">
            <div class="stack-icon"><i class="bi bi-graph-up"></i></div>
            <h5>Analytics & Insights</h5>
            <p>Storage usage charts, monthly upload timelines, file type distributions, camera model frequency — all driven by your own EXIF data.</p>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Ready to get started?</h2>
        <p>Create your account, deploy the stack on your own hardware, and start uploading. The face recognition pipeline runs automatically in the background.</p>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-5">
            <div class="card-pub text-center h-100">
                <div style="font-size: 2.2rem; color: var(--accent-color); margin-bottom: 0.5rem;"><i class="bi bi-person-plus"></i></div>
                <h5>New user?</h5>
                <p style="font-size: 0.9rem;">Create an account and start exploring. Upload a few photos to see the ML pipeline in action — faces are detected and grouped automatically.</p>
                <a href="<?= url_to('register') ?>" class="btn-primary-pub"><i class="bi bi-person-plus me-2"></i>Create Account</a>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card-pub text-center h-100">
                <div style="font-size: 2.2rem; color: var(--accent-color); margin-bottom: 0.5rem;"><i class="bi bi-box-arrow-in-right"></i></div>
                <h5>Already have an account?</h5>
                <p style="font-size: 0.9rem;">Sign in to access your photo library, manage albums, review face recognition results, and configure the Android app.</p>
                <a href="<?= url_to('login') ?>" class="btn-outline-pub"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

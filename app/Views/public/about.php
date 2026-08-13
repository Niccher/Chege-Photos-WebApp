<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>About — Self-hosted photo management platform<?= $this->endSection() ?>
<?= $this->section('description') ?>Learn about Chege Photos — a full-featured self-hosted photo management platform with upload, albums, sharing, analytics, memories, and ML-powered face recognition.<?= $this->endSection() ?>
<?= $this->section('nav-about') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="About Chege Photos — Self-hosted photo management">
<meta property="og:description" content="A complete, self-hosted photo platform with web and mobile access, ML face recognition, smart albums, and full data privacy.">
<meta name="keywords" content="self-hosted photos, photo management, open source, docker, PHP, CodeIgniter">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>About Chege Photos</h1>
    <p>A complete self-hosted photo management platform — upload, organise, search, analyse, and share your photos, all on your own infrastructure.</p>
</div>

<section class="section">
    <div class="section-header">
        <h2>What is Chege Photos?</h2>
        <p>It's a full-stack ecosystem that replaces cloud photo services with a self-hosted alternative that includes ML-powered face recognition.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Core Web Application</h5>
                <p>Built on CodeIgniter 4 with Shield authentication, the web app provides:</p>
                <ul style="color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><strong>Upload</strong> — AJAX multi-file upload with Dropzone, 512 MB max per file</li>
                    <li><strong>EXIF extraction</strong> — Camera, GPS, ISO, shutter speed, and more</li>
                    <li><strong>Photo grid</strong> — Infinite scroll pagination with search and type filters</li>
                    <li><strong>Fullscreen viewer</strong> — Carousel with keyboard navigation and slideshow</li>
                    <li><strong>Albums</strong> — Manual albums and smart albums with rule-based auto-population</li>
                    <li><strong>Bulk operations</strong> — Multi-select with batch favorite, archive, delete, add-to-album</li>
                    <li><strong>Sharing</strong> — Share with other users or generate token-authenticated public links</li>
                    <li><strong>Analytics</strong> — Storage charts, timeline, file types, camera stats</li>
                    <li><strong>Memories</strong> — On-this-day and 6-months-ago feeds</li>
                    <li><strong>Map explore</strong> — Leaflet map with photo heatmap overlay</li>
                    <li><strong>Trash</strong> — Soft delete with 60-day retention</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Face Recognition Dashboard</h5>
                <p>The <strong>Faces</strong> section gives you a complete interface for managing detected faces and persons:</p>
                <ul style="color: var(--text-muted); font-size: 0.9rem; line-height: 2;">
                    <li><strong>Person grid</strong> — All auto-discovered persons with face thumbnails, age, and gender</li>
                    <li><strong>Photo detail</strong> — Face bounding boxes highlighted on the photo, gold border for the current person</li>
                    <li><strong>Face modals</strong> — Click any face to see person details, link to all their photos</li>
                    <li><strong>Carousel navigation</strong> — Browse all person's photos with left/right arrows and keyboard</li>
                    <li><strong>Bulk scan</strong> — Scan all photos or individual photos with progress tracking</li>
                    <li><strong>Name persons</strong> — Assign names to automatically-detected persons</li>
                    <li><strong>Merge persons</strong> — Combine duplicate persons into one</li>
                    <li><strong>Re-scan</strong> — Force re-scan of individual photos or the entire library</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Architecture</h2>
        <p>All components run in Docker on a shared network, communicating via HTTP/REST.</p>
    </div>
    <div class="diagram-box">
┌─────────────────────────────────────────────────────────────────────────┐
│                        Docker (hosts-shared-network)                     │
│                                                                         │
│  Browser ──HTTPS──▶ Web App (PHP 8.3 Apache, host 9005)                │
│                       │                                                  │
│                       ├── MySQL 8.4 (container 3306 / host 9306)        │
│                       │   photos, users, albums, shares                  │
│                       │                                                  │
│                       ├── ML Service (FastAPI, host 9051)               │
│                       │   ├── Qdrant (container 6333 / host 9052)       │
│                       │   └── Read-only mount of /public/uploads         │
│                       │                                                  │
│                       └── phpMyAdmin (host 9000) — dev convenience      │
│                                                                         │
│  Android App ──HTTPS──▶ Web App REST API (token auth)                   │
│                    └──▶ ML Service API (face search)                    │
└─────────────────────────────────────────────────────────────────────────┘
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Tech Stack</h2>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5>Web App</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    PHP 8.3<br>
                    CodeIgniter 4 + Shield 1.2<br>
                    Apache 2.4 (mod_rewrite)<br>
                    MySQL 8.4<br>
                    Bootstrap 5.3<br>
                    jQuery, Chart.js, Leaflet<br>
                    Dropzone.js, Fabric.js
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5>ML Backend</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    Python 3.12<br>
                    FastAPI 0.115.6 + Uvicorn<br>
                    SQLAlchemy 2.0 + PyMySQL<br>
                    Insightface 0.7.3 (<?= esc($faceModelPack) ?>)<br>
                    Qdrant 1.13.2 (vector DB)<br>
                    scikit-learn 1.9 (HDBSCAN)<br>
                    OpenCV 5.0, NumPy 2.5
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <h5>Android App</h5>
                <div style="color: var(--text-muted); font-size: 0.88rem; line-height: 2;">
                    Kotlin 2.0.21<br>
                    Jetpack Compose (BOM 2024.09)<br>
                    Material 3<br>
                    Retrofit 2.11 + OkHttp 4.12<br>
                    Coil 2.6 (image loading)<br>
                    Room 2.6 (local cache)<br>
                    CameraX 1.4 + ML Kit 17.3<br>
                    minSdk 29 / targetSdk 36
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Data flow</h2>
        <p>How a photo travels from upload to being searchable by face.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">1</div>
                <h6 style="font-size:0.85rem;">Upload</h6>
                <p style="font-size:0.82rem;">Photo uploaded via web or Android. Stored in <code>public/uploads/</code>. EXIF extracted and saved to MySQL.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">2</div>
                <h6 style="font-size:0.85rem;">Scan</h6>
                <p style="font-size:0.82rem;">ML service reads the photo from the shared mount. Insightface detects faces and extracts 512-d embeddings.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">3</div>
                <h6 style="font-size:0.85rem;">Index</h6>
                <p style="font-size:0.82rem;">Embeddings stored in Qdrant. Face metadata (bbox, landmarks, age, gender) stored in MySQL <code>face_encoding</code> table.</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-pub text-center h-100">
                <div class="step-num mx-auto mb-2">4</div>
                <h6 style="font-size:0.85rem;">Cluster</h6>
                <p style="font-size:0.82rem;">HDBSCAN groups embeddings into persons. Each person gets a centroid vector. The web app displays the grouped results.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-sm" style="border-top: 1px solid var(--border-color);">
    <div class="text-center">
        <h3 style="font-weight: 700; color: var(--text-primary);">Ready to deploy?</h3>
        <p style="color: var(--text-muted);">Jump to the <a href="<?= base_url('setup') ?>" class="auth-link">setup guide</a> or <a href="<?= url_to('register') ?>" class="auth-link">create an account</a> if your instance is already running.</p>
    </div>
</section>

<?= $this->endSection() ?>

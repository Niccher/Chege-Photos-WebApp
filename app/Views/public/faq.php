<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>FAQs — Chege Photos<?= $this->endSection() ?>
<?= $this->section('description') ?>Frequently asked questions about Chege Photos — self-hosted photo management platform with ML-powered face recognition. Installation, privacy, face recognition, and more.<?= $this->endSection() ?>
<?= $this->section('nav-faq') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="FAQs — Chege Photos">
<meta property="og:description" content="Frequently asked questions about Chege Photos — self-hosted photo management platform with ML-powered face recognition.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= base_url('faq') ?>">
<meta name="keywords" content="Chege Photos FAQ, self-hosted photo management, photo privacy questions, face recognition FAQ, open source photo platform">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1><i class="bi bi-question-circle me-2" style="color: var(--accent-color);"></i>Frequently Asked Questions</h1>
    <p>Everything you need to know about Chege Photos — from installation to daily use.</p>
</div>

<div class="section-sm">
    <div class="accordion accordion-pub" id="faqAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    What is Chege Photos?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Chege Photos is a <strong>self-hosted, open-source</strong> photo management platform. It consists of a <strong>web app</strong> (PHP/CI4 + MySQL), an <strong>Android companion app</strong> (Kotlin), and an <strong>ML backend</strong> (Python/FastAPI with Insightface and Qdrant). All components run on your own infrastructure — your data never leaves your servers.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Is Chege Photos free?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes. Chege Photos is open-source and released under the <strong>MIT License</strong> — you can self-host it for free on your own hardware. The only costs are your own infrastructure: server, storage, and optionally a domain name.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Where are my photos stored?
                </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Your photos are stored <strong>exclusively on your own server</strong>. The web app stores originals and thumbnails on your filesystem, metadata in your MySQL database, and face embeddings in a local Qdrant vector database. <strong>Your data never leaves your infrastructure.</strong>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    Does it require an internet connection?
                </button>
            </h2>
            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Not necessarily. The web app and ML backend run on your local network or a VPS. You can access them over <strong>LAN</strong> without internet, or over the internet if you expose them securely with a reverse proxy. The Android app connects to your server over your network.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    How does face recognition work?
                </button>
            </h2>
            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <p>When you upload a photo, the web app sends it to the ML backend over a local API. The pipeline is:</p>
                    <ol>
                        <li><strong>Detect</strong>: RetinaFace finds faces in the image</li>
                        <li><strong>Embed</strong>: Insightface ArcFace extracts a 512-dimensional embedding vector per face</li>
                        <li><strong>Cluster</strong>: HDBSCAN groups similar faces across your library</li>
                        <li><strong>Search</strong>: Qdrant enables fast similarity search — query by face to find all photos containing that person</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                    Can I use it without the ML backend?
                </button>
            </h2>
            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <strong>Yes.</strong> Chege Photos works perfectly as a photo gallery and management platform without the ML backend. Face recognition is optional — you can enable it at any time by deploying the ML service and configuring it in the web app settings.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                    What hardware do I need?
                </button>
            </h2>
            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <ul class="mb-0">
                        <li><strong>Minimum</strong>: 2 CPU cores, 4 GB RAM, 20 GB+ storage</li>
                        <li><strong>Recommended (with ML)</strong>: 4+ CPU cores, 8 GB RAM, GPU with CUDA support</li>
                        <li><strong>Software</strong>: Docker and Docker Compose</li>
                        <li>Storage needs scale with your photo library — plan accordingly.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                    How do I install Chege Photos?
                </button>
            </h2>
            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Clone the three repositories (web app, Android app, ML backend), configure environment variables, create a shared Docker network, and deploy with Docker Compose. See our <a href="<?= base_url('setup') ?>">Setup Guide</a> for a complete step-by-step walkthrough.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_cron">
                    How do I manage background tasks and cron jobs?
                </button>
            </h2>
            <div id="faq_cron" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    The platform runs a built-in container cron service that triggers a master CodeIgniter Spark task scheduler (<code>cron:run</code>) every minute. Administrative superusers can modify the frequencies of individual jobs (Trash Purge, ML Face Clustering, Temp Upload Cleanup) directly from the Web UI under <strong>Admin Console &rarr; System Crons</strong>, and also view a detailed run log history database.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                    How do I connect the Android app?
                </button>
            </h2>
            <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <p>Install the Android app from the GitHub releases. To connect it to your server:</p>
                    <ol>
                        <li>Sign into your web app account</li>
                        <li>Go to Settings → API Tokens and generate a new token</li>
                        <li>Open the Android app and enter your server URL</li>
                        <li>Scan the QR code from Settings to auto-configure, or manually enter the token</li>
                    </ol>
                    <p class="mb-0">The app uses token-based authentication and syncs via REST API over your network.</p>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                    What file formats are supported?
                </button>
            </h2>
            <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <ul class="mb-0">
                        <li><strong>Images</strong>: JPEG, PNG, WebP, GIF</li>
                        <li><strong>Videos</strong>: MP4, MOV, WebM</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                    Can I share albums with others?
                </button>
            </h2>
            <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes. You can share individual photos with other registered users and generate token-authenticated public share links with optional expiration dates.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                    How do I back up my photos?
                </button>
            </h2>
            <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    <p>Since photos are stored on your filesystem, use standard backup tools:</p>
                    <ul>
                        <li><strong>Photos</strong>: rsync, restic, or rclone to back up the uploads directory</li>
                        <li><strong>Database</strong>: <code>mysqldump</code> to dump the MySQL database</li>
                        <li><strong>Full restore</strong>: restore both the uploads directory and the database dump</li>
                    </ul>
                    <p class="mb-0">For Docker deployments, mount volumes to persistent storage and back them up directly.</p>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq13">
                    Is there a mobile web version?
                </button>
            </h2>
            <div id="faq13" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    Yes, the web app is fully responsive and works on mobile browsers. For the best experience, the native Android app supports offline viewing, background sync, and biometric authentication.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq14">
                    How do I update Chege Photos?
                </button>
            </h2>
            <div id="faq14" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    For <strong>Docker deployments</strong>: pull the latest images and recreate containers. For <strong>bare-metal</strong>: git pull the latest code and run any database migrations. Check the GitHub releases for changelogs and migration instructions.
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq15">
                    Can I import photos from Google Photos?
                </button>
            </h2>
            <div id="faq15" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body">
                    There is no direct Google Photos import tool yet. You can use <strong>Google Takeout</strong> to export your data, then upload the photos through the web interface or place them directly in the uploads directory on your server.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center section-sm" style="border-top: 1px solid var(--border-color);">
    <h3 class="fw-bold mb-2" style="color: var(--text-primary);">Still have questions?</h3>
    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem;">
        Browse the Setup Guide for detailed instructions, or sign in to your gallery if you already have an account.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= base_url('setup') ?>" class="btn-primary-pub px-4 py-2"><i class="bi bi-book me-1"></i>Setup Guide</a>
        <a href="<?= url_to('login') ?>" class="btn-outline-pub px-4 py-2"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</a>
    </div>
</div>

<?= $this->endSection() ?>

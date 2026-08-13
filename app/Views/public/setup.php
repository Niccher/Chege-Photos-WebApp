<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Setup Guide — Deploy Chege Photos on your own infrastructure<?= $this->endSection() ?>
<?= $this->section('description') ?>Complete setup guide for Chege Photos. Deploy the web app, ML backend, and Qdrant vector database using Docker Compose. Configure the Android app and enable face recognition.<?= $this->endSection() ?>
<?= $this->section('nav-setup') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Setup Guide — Chege Photos deployment">
<meta property="og:description" content="Step-by-step guide to deploying Chege Photos with Docker Compose, configuring the ML backend, and connecting the Android app.">
<meta name="keywords" content="docker, setup, deployment, self-hosted, docker compose, installation guide">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>Setup Guide</h1>
    <p>Get the entire Chege Photos platform running on your own hardware. Everything runs in Docker with a single command.</p>
</div>

<section class="section">
    <div class="section-header">
        <h2>Prerequisites</h2>
    </div>
    <div class="row g-3 justify-content-center">
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-box"></i></div>
                <h5 class="mt-2">Docker</h5>
                <p style="font-size:0.85rem;">Docker Engine ≥ 24.0 and Docker Compose ≥ 2.20. Install from <a href="https://docs.docker.com/engine/install/" class="auth-link" target="_blank">docker.com</a>.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-hdd-stack"></i></div>
                <h5 class="mt-2">Storage</h5>
                <p style="font-size:0.85rem;">At least 10 GB free disk. Model weights ~500 MB. Vector index grows with your photo library.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub text-center">
                <div style="font-size:2.5rem; color:var(--accent-color);"><i class="bi bi-motherboard"></i></div>
                <h5 class="mt-2">Hardware</h5>
                <p style="font-size:0.85rem;">2+ CPU cores, 4 GB+ RAM recommended. GPU optional — the ML service falls back to CPU automatically.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 1: Clone the repositories</h2>
        <p>The platform is split across three repositories. Clone all three into the same parent directory.</p>
    </div>
    <div class="card-pub">
        <pre class="code-block">mkdir -p ~/hosts && cd ~/hosts

# Web App (PHP/CodeIgniter 4)
git clone https://github.com/niccher/Chege-Photos-WebApp.git

# ML Backend (Python/FastAPI)
git clone https://github.com/niccher/Chege-Photos-ML.git

# Android App (Kotlin/Compose) — optional, for development
git clone https://github.com/niccher/Chege-Photos-Android.git</pre>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 2: Configure environment</h2>
        <p>Copy the environment templates and configure your database credentials and other settings.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>Web App</h5>
                <pre class="code-block">cd Chege-Photos-WebApp
cp .env.example .env
# Edit .env:
#   app.baseURL = 'http://your-server:9005/'
#   database.default.password = 'your_password'</pre>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-pub h-100">
                <h5>ML Backend</h5>
                <pre class="code-block">cd ../Chege-Photos-ML
cp .env.example .env
# Edit .env:
#   DB_PASSWORD=your_password
#   INCLUDE_SENSITIVE_ATTRIBUTES=true
#   FACE_DET_THRESH=0.5</pre>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 3: Create the Docker network</h2>
        <p>Both the web app and ML service need to communicate on the same Docker network. Create it once:</p>
    </div>
    <div class="card-pub">
        <pre class="code-block">docker network create hosts-shared-network</pre>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 4: Start the web app stack</h2>
        <p>Launch the web app, MySQL database, and phpMyAdmin (dev convenience):</p>
    </div>
    <div class="row g-4">
        <div class="col-md-7">
            <div class="card-pub h-100">
                <h5>Command</h5>
                <pre class="code-block">cd Chege-Photos-WebApp
docker compose up --build -d</pre>
                <p class="mt-2 mb-0" style="color:var(--text-muted);font-size:0.85rem;">Migrations run automatically on first start. The internal Cron daemon is also initialized to run background task schedules (via CodeIgniter's <code>cron:run</code> master runner every minute).</p>
                <p class="mt-1 mb-0" style="color:var(--text-muted);font-size:0.85rem;"><strong>Default Superuser:</strong> A default administrative account is seeded: user <code>superadmin</code> / email <code>superadmin@eavesdroid.com</code> / password <code>SuperAdmin@2024!</code>.</p>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card-pub h-100">
                <h5>Services</h5>
                <table class="table table-sm table-pub mb-0">
                    <thead><tr><th>Service</th><th>Port</th></tr></thead>
                    <tbody>
                        <tr><td>Web App</td><td><code>9005</code></td></tr>
                        <tr><td>MySQL</td><td><code>9306</code></td></tr>
                        <tr><td>phpMyAdmin</td><td><code>9000</code></td></tr>
                    </tbody>
                </table>
                <p class="mt-2 mb-0" style="color:var(--text-muted);font-size:0.82rem;">MySQL root password: configured in <code>.env</code> (default: <code>root_password</code>)</p>
            </div>
        </div>
    </div>
    <div class="mt-3 card-pub">
        <p class="mb-0" style="color:var(--text-muted);font-size:0.9rem;">Open <strong>http://your-server:9005</strong> in your browser. You should see the landing page. Click <strong>Create Account</strong> to register your first user.</p>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 5: Start the ML backend</h2>
        <p>With the web app running, launch the ML service and Qdrant vector database:</p>
    </div>
    <div class="row g-4">
        <div class="col-md-7">
            <div class="card-pub h-100">
                <h5>Command</h5>
                <pre class="code-block">cd Chege-Photos-ML
docker compose up --build -d</pre>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card-pub h-100">
                <h5>Services</h5>
                <table class="table table-sm table-pub mb-0">
                    <thead><tr><th>Service</th><th>Port</th></tr></thead>
                    <tbody>
                        <tr><td>ML FastAPI</td><td><code>9051</code></td></tr>
                        <tr><td>Qdrant HTTP</td><td><code>9052</code></td></tr>
                        <tr><td>Qdrant gRPC</td><td><code>9053</code></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3 card-pub">
        <h5>Verify the ML service</h5>
        <pre class="code-block">curl http://localhost:9051/health
# {"status":"healthy","db_connected":true,"qdrant_connected":true,"models_loaded":true}</pre>
        <p class="mb-0" style="color:var(--text-muted);font-size:0.85rem;">The ML service reads photos from the web app's <code>public/uploads</code> directory via a read-only Docker volume mount.</p>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 6: Enable face recognition</h2>
        <p>Once both stacks are running, log in to the web app and scan your photos.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="step-num">1</div>
                    <h6 class="mb-0">Upload photos</h6>
                </div>
                <p style="font-size:0.85rem;">Use the Upload button in the web app to add photos. Supported formats: JPEG, PNG, MP4 (thumbnails only).</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="step-num">2</div>
                    <h6 class="mb-0">Scan for faces</h6>
                </div>
                <p style="font-size:0.85rem;">Go to the Faces page and click <strong>Scan All</strong>. The web app proxies the request to the ML service. Progress is tracked per-photo.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="step-num">3</div>
                    <h6 class="mb-0">Cluster & name</h6>
                </div>
                <p style="font-size:0.85rem;">After scanning, click <strong>Cluster</strong> to group faces into persons. Name persons manually or merge duplicates. Click any face to see all photos of that person.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Step 7: Connect the Android app</h2>
        <p>With the server running, configure the Android companion app.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Generate an auth token</h5>
                <ol style="color:var(--text-muted);font-size:0.88rem;line-height:2;">
                    <li>Log in to the web app</li>
                    <li>Go to <strong>Settings → Tokens</strong></li>
                    <li>Click <strong>Generate New Token</strong></li>
                    <li>A QR code and 8-character token are displayed</li>
                </ol>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-pub h-100">
                <h5>Log in on Android</h5>
                <ol style="color:var(--text-muted);font-size:0.88rem;line-height:2;">
                    <li>Install the app on your Android device (minSdk 29)</li>
                    <li>Tap the server config button and enter your server URL</li>
                    <li>Tap <strong>Token Login</strong></li>
                    <li>Scan the QR code or enter the token manually</li>
                    <li>Optional: enable biometric unlock in Settings</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>Environment reference</h2>
    </div>
    <div class="card-pub">
        <h5>Web App <code>.env</code></h5>
        <table class="table table-sm table-pub">
            <thead><tr><th>Variable</th><th>Example</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>app.baseURL</code></td><td><code>http://192.168.1.212:9005/</code></td><td>Public URL of the web app</td></tr>
                <tr><td><code>database.default.hostname</code></td><td><code>mysql</code></td><td>MySQL host (Docker service name)</td></tr>
                <tr><td><code>database.default.database</code></td><td><code>db_chege_photos</code></td><td>MySQL database name</td></tr>
                <tr><td><code>database.default.password</code></td><td><code>root_password</code></td><td>MySQL password</td></tr>
                <tr><td><code>encryption.key</code></td><td><code>hex2bin:...</code></td><td>CI4 encryption key (auto-generated)</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-pub mt-3">
        <h5>ML Backend <code>.env</code></h5>
        <table class="table table-sm table-pub">
            <thead><tr><th>Variable</th><th>Example</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>DB_HOST</code></td><td><code>mysql</code></td><td>MySQL host (Docker service name)</td></tr>
                <tr><td><code>DB_NAME</code></td><td><code>ml_chege_photos</code></td><td>MySQL database for ML metadata</td></tr>
                <tr><td><code>QDRANT_HOST</code></td><td><code>ml-qdrant</code></td><td>Qdrant host (Docker service name — <code>qdrant</code> if deployed standalone from the ML repo's own compose file)</td></tr>
                <tr><td><code>FACE_MODEL_PACK</code></td><td><code>buffalo_l</code></td><td>Insightface model pack</td></tr>
                <tr><td><code>INCLUDE_SENSITIVE_ATTRIBUTES</code></td><td><code>true</code></td><td>Enable age/gender estimation</td></tr>
                <tr><td><code>HDBSCAN_MIN_CLUSTER_SIZE</code></td><td><code>2</code></td><td>Minimum cluster size</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section-sm" style="border-top: 1px solid var(--border-color); text-align:center;">
    <p style="color:var(--text-muted);">Once everything is running, <a href="<?= url_to('register') ?>" class="auth-link">create your account</a> and start uploading.</p>
</section>

<?= $this->endSection() ?>

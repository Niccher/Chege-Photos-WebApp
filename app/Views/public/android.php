<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?>Android App — Companion app for Chege Photos<?= $this->endSection() ?>
<?= $this->section('description') ?>Native Android companion app for Chege Photos. Sync photos, browse with face recognition overlays, biometric unlock, and QR token login. Built with Kotlin and Jetpack Compose.<?= $this->endSection() ?>
<?= $this->section('nav-android') ?>active<?= $this->endSection() ?>

<?= $this->section('head') ?>
<meta property="og:title" content="Chege Photos Android App — Kotlin Compose companion">
<meta property="og:description" content="Native Android app for Chege Photos with photo sync, face recognition overlays, biometric unlock, and QR-based token login.">
<meta name="keywords" content="android, kotlin, jetpack compose, photo sync, face recognition, open source">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="page-header">
    <h1>Android Companion App</h1>
    <p>A native Kotlin app built with Jetpack Compose that brings Chege Photos to your mobile device — with full face recognition support.</p>
</div>

<section class="section">
    <div class="row g-5 align-items-start">
        <div class="col-lg-7">
            <h3 style="font-weight: 700; color: var(--text-primary);">Features</h3>

            <div class="d-flex flex-column gap-4 mt-4">
                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-arrow-up-circle"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Sync & Upload</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Scans the device's MediaStore for local photos and uploads them to the server with device fingerprint tracking. Per-file progress bars show upload status. Notifications are posted for file transfers.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-images"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Photo Browser</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Responsive grid with infinite scroll. Long-press to enter multi-select mode for batch operations: favorite, archive, delete, download, add to album.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Faces & Person Recognition</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Persons grid displays all detected faces with thumbnails. Tap a person to see all their photos. The <strong>PersonPhotoPager</strong> provides a full-screen horizontal swipe viewer with pinch-to-zoom and face bounding box overlays — current person's faces highlighted in gold, others in green.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-play-btn"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Fullscreen Carousel</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Photo detail view with pinch-to-zoom and pan gestures. Swipe left/right to navigate. Info button shows a bottom sheet with EXIF metadata and a list of detected faces with person names.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-fingerprint"></i></div>
                    <div>
                        <h6 style="font-weight:600;">Biometric Unlock</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Optional biometric authentication (fingerprint / face unlock) on app launch using AndroidX Biometric library.</p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stack-icon flex-shrink-0" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-palette"></i></div>
                    <div>
                        <h6 style="font-weight:600;">5 Themes</h6>
                        <p style="color:var(--text-muted);font-size:0.9rem;">Default (dynamic color on Android 12+), Solarized, Grey, Midnight, and Black. Themes persist across app launches via SharedPreferences.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-pub">
                <h5>Authentication</h5>
                <p style="font-size:0.9rem;">Two ways to log in:</p>
                <ul style="color:var(--text-muted);font-size:0.88rem;line-height:2;">
                    <li><strong>Email & password</strong> — standard login via <code>POST /api/login</code></li>
                    <li><strong>Token login</strong> — generate an 8-character token from the web app's Settings → Tokens page, then either scan the QR code with the built-in scanner or enter it manually</li>
                </ul>
                <p style="font-size:0.9rem;">Token auth uses device fingerprinting (SHA-256 of ~20 Build fields) for secure device identification.</p>
                <hr style="border-color:var(--border-color);">
                <h5>Connection Setup</h5>
                <ol style="color:var(--text-muted);font-size:0.88rem;line-height:2;">
                    <li>Open the app and tap the server config icon</li>
                    <li>Enter your server URL (e.g. <code>https://photos.yourdomain.com</code>)</li>
                    <li>The app auto-detects private IP ranges and switches HTTP/HTTPS accordingly</li>
                    <li>Log in with your credentials or scan a token QR code</li>
                </ol>
                <p style="font-size:0.85rem;color:var(--text-muted);">Default server URL: <code>https://photos.chegecache.co.ke/</code></p>
            </div>

            <div class="card-pub mt-3">
                <h5>Technical Details</h5>
                <table class="table table-sm table-pub mb-0">
                    <tbody>
                        <tr><td>Language</td><td>Kotlin 2.0.21</td></tr>
                        <tr><td>UI</td><td>Jetpack Compose + Material 3</td></tr>
                        <tr><td>Architecture</td><td>Single-activity, state-based navigation</td></tr>
                        <tr><td>Networking</td><td>Retrofit 2.11 + OkHttp 4.12</td></tr>
                        <tr><td>Image loading</td><td>Coil 2.6</td></tr>
                        <tr><td>Local cache</td><td>Room 2.6</td></tr>
                        <tr><td>minSdk / targetSdk</td><td>29 / 36</td></tr>
                        <tr><td>Camera</td><td>CameraX 1.4 + ML Kit 17.3</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<section class="section" style="border-top: 1px solid var(--border-color);">
    <div class="section-header">
        <h2>How to connect</h2>
        <p>Getting the Android app talking to your self-hosted instance.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">1</div>
                <h6>Generate a token</h6>
                <p style="font-size:0.85rem;">In the web app, go to Settings → Tokens and click "Generate New Token". A QR code will appear alongside the 8-character token.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">2</div>
                <h6>Scan or enter</h6>
                <p style="font-size:0.85rem;">In the Android app, tap "Token Login" and either scan the QR code using the built-in CameraX scanner or type the token manually.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-pub h-100 text-center">
                <div class="step-num mx-auto mb-2">3</div>
                <h6>Start syncing</h6>
                <p style="font-size:0.85rem;">Once authenticated, use the Sync tab to upload photos from your device, or browse your existing library from the Gallery tab.</p>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

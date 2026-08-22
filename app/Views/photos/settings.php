<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0">Settings</h2>
            <p class="text-muted small">Manage your profile, preferences, and account security.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Navigation Tabs -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-card overflow-hidden" style="background: var(--card-bg);">
                <div class="list-group list-group-flush settings-tabs" id="settingsTabs" role="tablist">
                    <a class="list-group-item list-group-item-action active p-3 border-0 d-flex align-items-center gap-3" id="profile-tab" data-bs-toggle="pill" href="#profile" role="tab">
                        <i class="bi bi-person-circle fs-5"></i>
                        <span>Profile</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="security-tab" data-bs-toggle="pill" href="#security" role="tab">
                        <i class="bi bi-shield-lock fs-5"></i>
                        <span>Security</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="preferences-tab" data-bs-toggle="pill" href="#preferences" role="tab">
                        <i class="bi bi-sliders fs-5"></i>
                        <span>Preferences</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="storage-tab" data-bs-toggle="pill" href="#storage" role="tab">
                        <i class="bi bi-cloud-check fs-5"></i>
                        <span>Storage</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="ml-tab" data-bs-toggle="pill" href="#ml" role="tab">
                        <i class="bi bi-cpu fs-5"></i>
                        <span>ML / Face Recognition</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="export-tab" data-bs-toggle="pill" href="#export" role="tab">
                        <i class="bi bi-download fs-5"></i>
                        <span>Export Data</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3" id="tokens-tab" data-bs-toggle="pill" href="#tokens" role="tab">
                        <i class="bi bi-key fs-5"></i>
                        <span>Access Tokens</span>
                    </a>
                    <a class="list-group-item list-group-item-action p-3 border-0 d-flex align-items-center gap-3 text-danger" id="danger-tab" data-bs-toggle="pill" href="#danger" role="tab">
                        <i class="bi bi-exclamation-triangle fs-5"></i>
                        <span>Danger Zone</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="col-lg-9">
            <div class="tab-content" id="settingsTabContent">
                
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Profile &amp; account</h5>
                        <div class="row g-4 mb-4 align-items-start">
                            <div class="col-md-auto text-center text-md-start">
                                <div class="position-relative d-inline-block">
                                    <?php $av = $user->avatar ?? null; ?>
                                    <?php if ($av && is_string($av) && str_starts_with($av, 'uploads/')): ?>
                                        <img src="<?= base_url($av) ?>" alt="" id="settingsAvatarPreview" class="rounded-circle border border-secondary" width="96" height="96" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div id="settingsAvatarPreview" class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold border border-secondary"
                                             style="width:96px;height:96px;background:linear-gradient(135deg,#4285f4,#00c6ff);font-size:2rem;">
                                            <?= strtoupper(substr(($user->username ?? $user->email ?? 'U'), 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start mt-3">
                                    <label class="btn btn-outline-primary btn-sm mb-0">
                                        Change photo
                                        <input type="file" id="settingsAvatarInput" name="avatar" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif">
                                    </label>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRemoveAvatar" <?= ($av && str_starts_with((string) $av, 'uploads/')) ? '' : 'disabled' ?>>Remove</button>
                                </div>
                                <p class="small text-muted mt-2 mb-0">JPEG, PNG, WebP, or GIF. Max 2&nbsp;MB.</p>
                            </div>
                            <div class="col">
                                <form id="formProfile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Display name</label>
                                            <input type="text" name="name" class="form-control" value="<?= esc($user->name ?? '') ?>" placeholder="Your name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Username</label>
                                            <input type="text" name="username" class="form-control" value="<?= esc($user->username ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold">Email</label>
                                            <input type="email" class="form-control settings-email-static" value="<?= esc($user->email) ?>" disabled readonly autocomplete="off" aria-label="Sign-in email (not editable)">
                                            <p class="small text-muted mb-0 mt-1">This is your sign-in email. It cannot be changed here.</p>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <button type="submit" class="btn btn-primary px-4 rounded-pill">Save profile</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Change Password</h5>
                        <form id="formPassword">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Preferences Tab -->
                <div class="tab-pane fade" id="preferences" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">App Preferences</h5>
                        <div class="mb-4">
                            <label class="form-label d-block mb-3 small fw-bold">Theme</label>
                            <div class="row g-3">
                                <?php 
                                $themes = [
                                    'auto' => ['name' => 'Auto', 'icon' => 'bi-display'],
                                    'light' => ['name' => 'Light', 'icon' => 'bi-sun'],
                                    'dark' => ['name' => 'Dark', 'icon' => 'bi-moon-stars'],
                                    'solarized' => ['name' => 'Solarized', 'icon' => 'bi-brightness-high'],
                                    'grey' => ['name' => 'Grey', 'icon' => 'bi-circle-half']
                                ];
                                foreach ($themes as $key => $t):
                                ?>
                                <div class="col-md-4 col-6">
                                    <div class="theme-card p-3 rounded-control border text-center cursor-pointer <?= $theme === $key ? 'border-primary bg-primary bg-opacity-10' : '' ?>" 
                                         onclick="updateAppTheme('<?= $key ?>')" 
                                         style="transition: all 0.2s; border-color: var(--border-color) !important;">
                                        <i class="bi <?= $t['icon'] ?> fs-3 d-block mb-2 <?= $theme === $key ? 'text-primary' : '' ?>"></i>
                                        <span class="small fw-semibold"><?= $t['name'] ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Storage Tab -->
                <div class="tab-pane fade" id="storage" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-4">Storage Usage</h5>
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <div style="max-width: 250px; margin: 0 auto;">
                                    <canvas id="storageChart"></canvas>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-column gap-3">
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold"><i class="bi bi-image me-2 text-primary"></i>Photos</span>
                                            <span class="small fw-bold"><?= round($storage['photos'] / 1024 / 1024, 1) ?> MB</span>
                                        </div>
                                    </div>
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold"><i class="bi bi-play-btn me-2 text-success"></i>Videos</span>
                                            <span class="small fw-bold"><?= round($storage['videos'] / 1024 / 1024, 1) ?> MB</span>
                                        </div>
                                    </div>
                                    <div class="p-3 border shadow-sm storage-stat-panel">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="small fw-bold text-muted"><i class="bi bi-hdd-network me-2"></i>Total Used</span>
                                            <span class="small fw-bold text-muted"><?= round($storage['total'] / 1024 / 1024, 1) ?> MB / 1 GB</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-1"><i class="bi bi-arrow-repeat me-1"></i>Refresh Metadata</h6>
                                <p class="text-muted small mb-0">Re-scan all photos to extract the latest EXIF data (GPS, camera info, etc.).</p>
                            </div>
                            <button class="btn btn-outline-secondary rounded-pill px-3 flex-shrink-0" id="btnRefreshMetadata">
                                <i class="bi bi-arrow-repeat me-1"></i> Refresh Now
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ML / Face Recognition Tab -->
                <div class="tab-pane fade" id="ml" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card overflow-hidden" style="background: var(--card-bg); color: var(--text-primary);">
                        <!-- Inner pill tabs -->
                        <ul class="nav nav-pills mb-0 border-bottom px-4 pt-3 pb-0" id="mlPills" role="tablist" style="background: transparent;">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-0 border-0 px-3 py-2 fw-semibold small" id="ml-scan-tab" data-bs-toggle="pill" data-bs-target="#mlScan" type="button" role="tab">
                                    <i class="bi bi-gear me-1"></i> Scan & Manage
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-0 border-0 px-3 py-2 fw-semibold small" id="ml-about-tab" data-bs-toggle="pill" data-bs-target="#mlAbout" type="button" role="tab">
                                    <i class="bi bi-info-circle me-1"></i> About
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-4" id="mlPillsContent">
                            <!-- Scan & Manage -->
                            <div class="tab-pane fade show active" id="mlScan" role="tabpanel">
                                <!-- Stats row -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="p-3 border border-opacity-10 rounded-3 text-center">
                                            <div class="fs-3 fw-bold text-primary"><i class="bi bi-person-badge"></i> <?= (int) ($mlStats['detected_faces'] ?? 0) ?></div>
                                            <div class="small text-muted mt-1">Detected Faces</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 border border-opacity-10 rounded-3 text-center">
                                            <div class="fs-3 fw-bold text-success"><i class="bi bi-shield-check"></i> <?= (int) ($mlStats['analyzed_images'] ?? 0) ?> / <?= (int) ($mlStats['total_images'] ?? 0) ?></div>
                                            <div class="small text-muted mt-1">Analysis Coverage</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 border border-opacity-10 rounded-3 text-center">
                                            <div class="fs-3 fw-bold text-info"><i class="bi bi-people"></i> <?= (int) ($mlStats['persons'] ?? 0) ?></div>
                                            <div class="small text-muted mt-1">Identified Persons</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analyze New Photos -->
                                <div class="border rounded-3 p-4 mb-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><i class="bi bi-search me-1"></i> Analyze New Photos</h6>
                                            <p class="text-muted small mb-0">Find people, identify objects, and index scenes in all newly uploaded photos.</p>
                                        </div>
                                        <button class="btn btn-primary rounded-pill px-4 flex-shrink-0" id="btnScanUnscanned">
                                            <i class="bi bi-play-fill me-1"></i> Analyze New
                                        </button>
                                    </div>
                                </div>

                                <!-- Re-group Similar Faces -->
                                <div class="border rounded-3 p-4 mb-3">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><i class="bi bi-diagram-3 me-1"></i> Re-group Similar Faces</h6>
                                            <p class="text-muted small mb-0">Re-organize face groupings to clean up matches and merge duplicate profiles based on face shapes.</p>
                                        </div>
                                        <button class="btn btn-outline-info rounded-pill px-4 flex-shrink-0" id="btnRecluster">
                                            <i class="bi bi-arrow-repeat me-1"></i> Re-group Faces
                                        </button>
                                    </div>
                                </div>

                                <!-- Danger Zone: Re-analyze Entire Library -->
                                <div class="border border-danger rounded-3 p-4">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Re-analyze Entire Library</h6>
                                            <p class="text-muted small mb-0">Deletes all current face and search metadata and re-scans every photo from scratch. This action is irreversible.</p>
                                        </div>
                                        <button class="btn btn-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#forceRescanModal">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Re-analyze All
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- About -->
                            <div class="tab-pane fade" id="mlAbout" role="tabpanel">
                                <h5 class="mb-1 fw-bold"><i class="bi bi-info-circle me-2"></i>About the AI Engine</h5>
                                <p class="text-muted small mb-4">Learn how the background artificial intelligence processes your photo library.</p>

                                <div class="row g-3">
                                    <!-- Face Detection -->
                                    <div class="col-md-6">
                                        <div class="p-3 border border-opacity-10 rounded-3 h-100">
                                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-person-bounding-box text-primary"></i> Face Recognition
                                            </h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                                                Finds human faces in photos, extracts facial shapes, and groups identical people together. You can name these groups to quickly find photos of specific friends and family.
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Scene Tagging -->
                                    <div class="col-md-6">
                                        <div class="p-3 border border-opacity-10 rounded-3 h-100">
                                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-tags text-success"></i> Category Tagging
                                            </h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                                                Automatically scans your photos for objects, settings, and scenes (like "beach", "sunset", "food", or "cars"). This lets you browse photos by categories without manual labeling.
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Semantic search -->
                                    <div class="col-md-6">
                                        <div class="p-3 border border-opacity-10 rounded-3 h-100">
                                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-search text-info"></i> Semantic Search
                                                <span class="badge bg-secondary-subtle text-muted extra-small" style="font-size: 0.65rem;">CLIP</span>
                                            </h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                                                Allows you to search using descriptive sentences instead of just keywords. For example, search for "a cat sleeping on a blue couch" or "people eating dinner at a restaurant".
                                            </p>
                                        </div>
                                    </div>
                                    <!-- Secure Storage -->
                                    <div class="col-md-6">
                                        <div class="p-3 border border-opacity-10 rounded-3 h-100">
                                            <h6 class="fw-bold d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-shield-lock text-warning"></i> Privacy & Storage
                                            </h6>
                                            <p class="text-muted small mb-0" style="font-size: 0.8rem; line-height: 1.5;">
                                                All intelligence processing runs locally on your server. Your photos never leave your device/server, and face vectors are stored securely inside a private vector index.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Data Tab -->
                <div class="tab-pane fade" id="export" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-1"><i class="bi bi-download me-2"></i>Export Data</h5>
                        <p class="text-muted small mb-4">Download your photos, videos, and metadata as a compressed archive.</p>

                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="p-4 border rounded-3">
                                    <h6 class="fw-bold mb-3">Select export options</h6>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Content to export</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="exportType" id="exportAll" value="all" checked>
                                                <label class="form-check-label" for="exportAll">All files</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="exportType" id="exportImages" value="images">
                                                <label class="form-check-label" for="exportImages">Images only</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="exportType" id="exportVideos" value="videos">
                                                <label class="form-check-label" for="exportVideos">Videos only</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">Additional data</label>
                                        <div class="d-flex flex-wrap gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="includeMetadata" checked>
                                                <label class="form-check-label" for="includeMetadata">
                                                    Include <code>metadata.json</code> <span class="text-muted">(EXIF, GPS, dimensions)</span>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="includeAlbums" checked>
                                                <label class="form-check-label" for="includeAlbums">Include album structure</label>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary rounded-pill px-4" id="btnExport">
                                        <i class="bi bi-file-zip me-1"></i> <span id="exportBtnText">Create Archive</span>
                                    </button>

                                    <div id="exportProgress" class="mt-3 d-none">
                                        <div class="progress rounded-pill" style="height:6px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success rounded-pill" style="width:100%"></div>
                                        </div>
                                        <p class="small text-muted mt-2 mb-0" id="exportStatus">Building archive...</p>
                                    </div>

                                    <div id="exportResult" class="mt-3 d-none">
                                        <div class="alert alert-success border-0 rounded-3 d-flex align-items-center gap-3 py-2 px-3 mb-0">
                                            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                                            <div class="flex-grow-1">
                                                <span id="exportResultMessage"></span>
                                            </div>
                                            <a href="#" id="exportDownloadLink" class="btn btn-sm btn-success rounded-pill px-3 flex-shrink-0">
                                                <i class="bi bi-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="p-4 border rounded-3 bg-light bg-opacity-25">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>About exports</h6>
                                    <ul class="small text-muted mb-0" style="line-height:1.8;">
                                        <li>Files are compressed into a single <code>.zip</code> archive.</li>
                                        <li>Original files are <strong>not</strong> modified or deleted.</li>
                                        <li>Metadata is exported as a separate <code>metadata.json</code> file.</li>
                                        <li>Export archives are deleted after 1 hour.</li>
                                        <li>Large exports may take a moment to generate.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Access Tokens Tab -->
                <div class="tab-pane fade" id="tokens" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <h5 class="mb-1"><i class="bi bi-key me-2"></i>Access Tokens</h5>
                                <p class="text-muted small mb-0">Generate single-use 8-character tokens for device authentication.</p>
                            </div>
                            <button class="btn btn-primary rounded-pill px-4" id="btnGenerateToken">
                                <i class="bi bi-plus-lg me-1"></i> Generate Token
                            </button>
                        </div>

                        <!-- Generate form (hidden by default) -->
                        <div id="tokenGenerateForm" class="d-none border rounded-3 p-3 mb-4 bg-light bg-opacity-25">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Description (optional)</label>
                                    <input type="text" id="tokenDescription" class="form-control" placeholder="e.g. My phone" maxlength="255">
                                </div>
                                <div class="col-md-auto">
                                    <button class="btn btn-success rounded-pill px-4" id="btnCreateToken">
                                        <i class="bi bi-check-lg me-1"></i> Create
                                    </button>
                                    <button class="btn btn-outline-secondary rounded-pill px-3 ms-2" id="btnCancelToken">Cancel</button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold d-block">Scopes / Permissions</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input token-scope-checkbox" type="checkbox" value="*" id="scopeAll" checked>
                                            <label class="form-check-label small" for="scopeAll">Full Access (*)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input token-scope-checkbox" type="checkbox" value="photos:read" id="scopePhotosRead" disabled>
                                            <label class="form-check-label small" for="scopePhotosRead">Read Photos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input token-scope-checkbox" type="checkbox" value="photos:write" id="scopePhotosWrite" disabled>
                                            <label class="form-check-label small" for="scopePhotosWrite">Upload/Write Photos</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input token-scope-checkbox" type="checkbox" value="faces:write" id="scopeFacesWrite" disabled>
                                            <label class="form-check-label small" for="scopeFacesWrite">Manage Faces</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New token display (hidden by default) -->
                        <div id="tokenNewDisplay" class="d-none border rounded-3 p-4 mb-4 text-center">
                            <h6 class="fw-bold mb-3"><i class="bi bi-check-circle-fill text-success me-1"></i> Token Generated</h6>
                            <div class="d-flex justify-content-center mb-3">
                                <div id="tokenQrCode"></div>
                            </div>
                            <div class="mb-3">
                                <code id="tokenCodeDisplay" class="fs-3 fw-bold user-select-all"></code>
                            </div>
                            <p class="text-muted small mb-3">This token will only be shown once. Copy it now.</p>
                            <button class="btn btn-outline-primary rounded-pill px-3 btn-sm" onclick="copyTokenToClipboard()">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                            <button class="btn btn-outline-secondary rounded-pill px-3 btn-sm ms-2" onclick="$('#tokenNewDisplay').addClass('d-none')">
                                <i class="bi bi-x me-1"></i> Close
                            </button>
                        </div>

                        <!-- Token list -->
                        <div class="table-responsive mb-5">
                            <table class="table table-hover align-middle mb-0" id="tokensTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Pairing Token</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Used At</th>
                                        <th>Device</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tokensTableBody">
                                    <tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Active Authenticated Devices Section -->
                        <h5 class="mt-4 mb-3"><i class="bi bi-phone me-2"></i>Active Authenticated Devices</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="devicesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Device / Client</th>
                                        <th>Scopes / Permissions</th>
                                        <th>First Linked</th>
                                        <th>Last Active</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="devicesTableBody">
                                    <tr><td colspan="5" class="text-center text-muted py-4">Loading active devices...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone Tab -->
                <div class="tab-pane fade" id="danger" role="tabpanel">
                    <div class="card border-0 shadow-sm rounded-card p-4 border-danger border-opacity-25" style="background: var(--card-bg); color: var(--text-primary);">
                        <h5 class="mb-1 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h5>
                        <p class="text-muted small mb-4">Irreversible actions that affect your account and data.</p>

                        <div class="border rounded-3 p-4 mb-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <h6 class="mb-1 fw-bold">Clear All User Data</h6>
                                    <p class="text-muted small mb-0">Deletes all your photos, albums, and shares. Resets your profile to a blank state — your account stays active.</p>
                                </div>
                                <button class="btn btn-outline-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                                    <i class="bi bi-eraser me-1"></i> Clear Data
                                </button>
                            </div>
                        </div>

                        <div class="border border-danger rounded-3 p-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <h6 class="mb-1 fw-bold text-danger">Delete Account</h6>
                                    <p class="text-muted small mb-0">Permanently deletes your account and all associated data. This cannot be undone.</p>
                                </div>
                                <button class="btn btn-danger rounded-pill px-4 flex-shrink-0" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                    <i class="bi bi-trash3 me-1"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Force Rescan Confirmation Modal -->
<div class="modal fade" id="forceRescanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-arrow-clockwise me-2"></i>Re-analyze Entire Library</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> This will delete all current face groupings and search indexes!</p>
                <p class="text-muted small mb-3">This permanently deletes <strong>all identified face tags, custom person groupings, and search data</strong>, then re-scans all photos from scratch. Your custom named face groupings will need to be re-computed afterward.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">RESCAN</span> to confirm:</label>
                    <input type="text" id="forceRescanConfirmInput" class="form-control" placeholder="Type RESCAN here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmForceRescan" disabled>
                    <i class="bi bi-arrow-clockwise me-1"></i> Re-analyze Library
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clear Data Confirmation Modal -->
<div class="modal fade" id="clearDataModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-eraser me-2"></i>Clear All Data</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> This action is irreversible!</p>
                <p class="text-muted small">This will permanently delete <strong>all your photos, albums, shares, and shared links</strong>. Your account will be reset to a fresh state.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">CLEAR</span> to confirm:</label>
                    <input type="text" id="clearConfirmInput" class="form-control" placeholder="Type CLEAR here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmClear" disabled>
                    <i class="bi bi-eraser me-1"></i> Clear Everything
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Confirmation Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-trash3 me-2"></i>Delete Account</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i> This action is irreversible!</p>
                <p class="text-muted small">This will permanently delete <strong>your account, all your photos, albums, shares, and profile data</strong>. You will be logged out immediately.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">DELETE</span> to confirm:</label>
                    <input type="text" id="deleteConfirmInput" class="form-control" placeholder="Type DELETE here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmDelete" disabled>
                    <i class="bi bi-trash3 me-1"></i> Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/qrcode.min.js') ?>"></script>
<script>
var currentTokenId = null;

function copyTokenToClipboard() {
    var text = document.getElementById('tokenCodeDisplay').textContent;
    navigator.clipboard.writeText(text).then(function() {
        showToast('Token copied to clipboard!', 'success');
    });
}

function loadTokens() {
    $.get(BASE_URL + 'settings/tokens', function(res) {
        if (res.status !== 'success') return;
        var html = '';
        if (res.tokens.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted py-4">No pairing tokens generated yet.</td></tr>';
        } else {
            res.tokens.forEach(function(t) {
                var statusBadge = t.is_used
                     ? '<span class="badge bg-secondary bg-opacity-25 text-secondary">Used</span>'
                     : '<span class="badge bg-success bg-opacity-25 text-success">Active / Unused</span>';
                var usedAt = t.used_at ? new Date(t.used_at).toLocaleString() : '—';
                var deviceInfo = t.device_name ? (t.device_name + '<br><small class="text-muted">' + (t.device_id || '') + '</small>') : '—';
                var scopesBadge = '';
                if (t.scopes) {
                    try {
                        var parsed = JSON.parse(t.scopes);
                        if (Array.isArray(parsed)) {
                            parsed.forEach(function(s) {
                                scopesBadge += '<span class="badge bg-info bg-opacity-10 text-info me-1 small">' + s + '</span>';
                            });
                        }
                    } catch(e) {}
                }
                var descriptionCell = (t.description || '—') + (scopesBadge ? '<br><small class="text-muted">' + scopesBadge + '</small>' : '');
                var revokeBtn = t.is_used
                     ? ''
                     : '<button class="btn btn-outline-danger btn-sm" onclick="revokeToken(' + t.id + ')" title="Revoke pairing code"><i class="bi bi-trash"></i></button>';
                html += '<tr>' +
                    '<td><code>' + t.token + '</code></td>' +
                    '<td>' + descriptionCell + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td class="small">' + usedAt + '</td>' +
                    '<td class="small">' + deviceInfo + '</td>' +
                    '<td>' + revokeBtn + '</td>' +
                    '</tr>';
            });
        }
        $('#tokensTableBody').html(html);

        // Populate active devices
        var devHtml = '';
        if (!res.active_devices || res.active_devices.length === 0) {
            devHtml = '<tr><td colspan="5" class="text-center text-muted py-4">No active linked devices found.</td></tr>';
        } else {
            res.active_devices.forEach(function(d) {
                var scopesList = '';
                if (d.scopes && Array.isArray(d.scopes)) {
                    d.scopes.forEach(function(s) {
                        scopesList += '<span class="badge bg-primary bg-opacity-10 text-primary me-1">' + s + '</span>';
                    });
                }
                var lastActive = d.last_used_at ? new Date(d.last_used_at).toLocaleString() : 'Just now';
                var firstLinked = d.created_at ? new Date(d.created_at).toLocaleString() : '—';
                var revokeDeviceBtn = '<button class="btn btn-sm btn-outline-danger" onclick="revokeDevice(' + d.id + ')" title="Log out / Revoke device"><i class="bi bi-box-arrow-right me-1"></i> Log Out</button>';
                devHtml += '<tr>' +
                    '<td><span class="fw-bold"><i class="bi bi-phone me-1"></i>' + d.name + '</span></td>' +
                    '<td>' + (scopesList || '<span class="badge bg-secondary">none</span>') + '</td>' +
                    '<td class="small">' + firstLinked + '</td>' +
                    '<td class="small">' + lastActive + '</td>' +
                    '<td>' + revokeDeviceBtn + '</td>' +
                    '</tr>';
            });
        }
        $('#devicesTableBody').html(devHtml);
    });
}

function revokeToken(id) {
    if (!confirm('Revoke this pairing token? This will prevent it from being used to pair new devices.')) return;
    $.post(BASE_URL + 'settings/tokens/revoke', { id: id }, function(res) {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadTokens();
        } else {
            showToast(res.message, 'danger');
        }
    });
}

function revokeDevice(id) {
    if (!confirm('Log out and revoke access for this device? It will be instantly disconnected and forced to log in again.')) return;
    $.post(BASE_URL + 'settings/tokens/revoke-device', { id: id }, function(res) {
        if (res.status === 'success') {
            showToast(res.message, 'success');
            loadTokens();
        } else {
            showToast(res.message, 'danger');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Storage Chart
    const ctx = document.getElementById('storageChart').getContext('2d');
    const rs = getComputedStyle(document.documentElement);
    const chartBg = [
        rs.getPropertyValue('--chart-1').trim(),
        rs.getPropertyValue('--chart-2').trim(),
        rs.getPropertyValue('--chart-neutral').trim()
    ];
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Photos', 'Videos', 'Free'],
            datasets: [{
                data: [
                    <?= $storage['photos'] ?>, 
                    <?= $storage['videos'] ?>, 
                    <?= max(0, (1024 * 1024 * 1024) - $storage['total']) ?>
                ],
                backgroundColor: chartBg,
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            maintainAspectRatio: true
        }
    });

    $('#formProfile').on('submit', function(e) {
        e.preventDefault();
        $.post(BASE_URL + 'settings/profile', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    $('#settingsAvatarInput').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        var fd = new FormData();
        fd.append('avatar', file);
        $.ajax({
            url: BASE_URL + 'settings/avatar',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function () { location.reload(); }, 600);
                } else {
                    showToast(res.message || 'Upload failed', 'danger');
                }
            },
            error: function () {
                showToast('Upload failed', 'danger');
            }
        });
        $(this).val('');
    });

    $('#btnRemoveAvatar').on('click', function () {
        if ($(this).prop('disabled')) return;
        $.post(BASE_URL + 'settings/avatar/remove', function (res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function () { location.reload(); }, 600);
            } else {
                showToast(res.message || 'Could not remove avatar', 'danger');
            }
        });
    });

    // 3. Password Update
    $('#formPassword').on('submit', function(e) {
        e.preventDefault();
        $.post(BASE_URL + 'settings/password', $(this).serialize(), function(res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                $('#formPassword')[0].reset();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    // Danger Zone: enable confirm buttons only when the correct text is typed
    $('#clearConfirmInput').on('input', function () {
        $('#btnConfirmClear').prop('disabled', $(this).val() !== 'CLEAR');
    });
    $('#deleteConfirmInput').on('input', function () {
        $('#btnConfirmDelete').prop('disabled', $(this).val() !== 'DELETE');
    });

    // Clear Data
    $('#btnConfirmClear').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clearing...');
        $.post(BASE_URL + 'settings/clear-data', { confirm: 'CLEAR' }, function (res) {
            if (res.status === 'success') {
                $('#clearDataModal').modal('hide');
                showToast(res.message, 'success');
                setTimeout(function () { location.reload(); }, 1200);
            } else {
                showToast(res.message, 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Clear Everything');
            }
        }).fail(function () {
            showToast('Request failed. Please try again.', 'danger');
            $btn.prop('disabled', false).html('<i class="bi bi-eraser me-1"></i> Clear Everything');
        });
    });

    // Delete Account
    $('#btnConfirmDelete').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');
        $.post(BASE_URL + 'settings/delete-account', { confirm: 'DELETE' }, function (res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
                setTimeout(function () { window.location.href = BASE_URL + 'login'; }, 1500);
            } else {
                showToast(res.message, 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete My Account');
            }
        }).fail(function () {
            showToast('Request failed. Please try again.', 'danger');
            $btn.prop('disabled', false).html('<i class="bi bi-trash3 me-1"></i> Delete My Account');
        });
    });

    // Reset modal inputs when hidden
    $('#clearDataModal, #deleteAccountModal').on('hidden.bs.modal', function () {
        $(this).find('input[type="text"]').val('');
        $(this).find('.btn-danger').prop('disabled', true);
    });

    // Export Data
    $('#btnExport').on('click', function () {
        var $btn = $(this);
        var type = $('input[name="exportType"]:checked').val();
        var metadata = $('#includeMetadata').is(':checked') ? 1 : 0;
        var albums = $('#includeAlbums').is(':checked') ? 1 : 0;

        $('#exportProgress').removeClass('d-none');
        $('#exportResult').addClass('d-none');
        $('#exportBtnText').text('Building...');
        $btn.prop('disabled', true);

        $.post(BASE_URL + 'settings/export', {
            type: type,
            metadata: metadata,
            albums: albums
        }, function (res) {
            if (res.status === 'success') {
                $('#exportProgress').addClass('d-none');
                $('#exportResult').removeClass('d-none');
                $('#exportResultMessage').text(res.message + ' (' + res.size + ')');
                $('#exportDownloadLink').attr('href', res.url);
            } else {
                $('#exportProgress').addClass('d-none');
                showToast(res.message, 'danger');
            }
        }).fail(function () {
            $('#exportProgress').addClass('d-none');
            showToast('Export request failed.', 'danger');
        }).always(function () {
            $('#exportBtnText').text('Create Archive');
            $btn.prop('disabled', false);
        });
    });

    // Tokens tab: Generate
    $('#btnGenerateToken').on('click', function () {
        $('#tokenGenerateForm').removeClass('d-none');
        $('#tokenDescription').focus();
    });
    $('#btnCancelToken').on('click', function () {
        $('#tokenGenerateForm').addClass('d-none');
    });
    // Toggle scopes based on Full Access checkbox
    $('#scopeAll').on('change', function () {
        var isChecked = $(this).is(':checked');
        $('.token-scope-checkbox').not('#scopeAll').prop('disabled', isChecked);
        if (isChecked) {
            $('.token-scope-checkbox').not('#scopeAll').prop('checked', false);
        }
    });

    $('#btnCreateToken').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
        var desc = $('#tokenDescription').val();
        
        var selectedScopes = [];
        $('.token-scope-checkbox:checked').each(function() {
            selectedScopes.push($(this).val());
        });
        if (selectedScopes.length === 0) {
            selectedScopes.push('*');
        }

        $.post(BASE_URL + 'settings/tokens/generate', { description: desc, scopes: selectedScopes }, function (res) {
            if (res.status === 'success') {
                var token = res.token;
                $('#tokenGenerateForm').addClass('d-none');
                $('#tokenDescription').val('');
                // Reset checkboxes to default
                $('#scopeAll').prop('checked', true);
                $('.token-scope-checkbox').not('#scopeAll').prop('disabled', true).prop('checked', false);

                $('#tokenCodeDisplay').text(token.token);
                $('#tokenQrCode').empty();
                new QRCode(document.getElementById('tokenQrCode'), {
                    text: token.token,
                    width: 180,
                    height: 180,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.L
                });
                $('#tokenNewDisplay').removeClass('d-none');
                loadTokens();
            } else {
                showToast(res.message, 'danger');
            }
        }).fail(function () {
            showToast('Failed to generate token.', 'danger');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Create');
        });
    });

    // Load tokens when the tokens tab is shown
    $('#tokens-tab').on('shown.bs.tab', function () {
        loadTokens();
    });

    // Refresh Metadata
    $('#btnRefreshMetadata').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Scanning...');
        $.post(BASE_URL + 'settings/refresh-metadata', function (res) {
            if (res.status === 'success') {
                showToast(res.message, 'success');
            } else {
                showToast(res.message, 'danger');
            }
        }).fail(function () {
            showToast('Refresh request failed.', 'danger');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Refresh Now');
        });
    });

    // ── ML / Face Recognition Actions ─────────────────────────────────

    // Scan Unscanned
    $('#btnScanUnscanned').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Scanning...');
        $.post(BASE_URL + 'api/v1/faces/scan-all', function (res) {
            if (res.status === 'success') {
                var msg = 'Scanned ' + res.processed + ' new photos' + (res.skipped ? ', skipped ' + res.skipped : '') + '.';
                if (res.errors && res.errors.length) msg += ' ' + res.errors.length + ' error(s).';
                showToast(msg, res.errors && res.errors.length ? 'warning' : 'success');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showToast(res.message || 'Scan failed', 'danger');
            }
        }).fail(function () {
            showToast('Scan request failed.', 'danger');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-play-fill me-1"></i> Scan Unscanned');
        });
    });

    // Re-cluster
    $('#btnRecluster').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clustering...');
        $.post(BASE_URL + 'api/v1/faces/cluster', function (res) {
            if (res.status === 'success') {
                var data = res.data || {};
                showToast(
                    'Clustering complete — ' + (data.clusters || 0) + ' clusters, ' + (data.assigned || 0) + ' faces assigned.',
                    'success'
                );
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showToast(res.message || 'Clustering failed', 'danger');
            }
        }).fail(function () {
            showToast('Clustering request failed.', 'danger');
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Re-cluster');
        });
    });

    // Force Rescan confirm input
    $('#forceRescanConfirmInput').on('input', function () {
        $('#btnConfirmForceRescan').prop('disabled', $(this).val() !== 'RESCAN');
    });

    // Force Rescan
    $('#btnConfirmForceRescan').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Rescanning all...');
        $.post(BASE_URL + 'api/v1/faces/force-scan', function (res) {
            if (res.status === 'success') {
                $('#forceRescanModal').modal('hide');
                showToast(
                    'Force rescan complete — ' + res.processed + ' photos processed' +
                    (res.errors && res.errors.length ? ' (' + res.errors.length + ' errors)' : '') + '.',
                    res.errors && res.errors.length ? 'warning' : 'success'
                );
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showToast(res.message || 'Force rescan failed', 'danger');
                $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Force Rescan');
            }
        }).fail(function () {
            showToast('Force rescan request failed.', 'danger');
            $btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Force Rescan');
        });
    });

    // Reset modal input when hidden
    $('#forceRescanModal').on('hidden.bs.modal', function () {
        $(this).find('input[type="text"]').val('');
        $(this).find('.btn-danger').prop('disabled', true);
    });
});

function updateAppTheme(theme) {
    $.post(BASE_URL + 'settings/theme', { theme: theme }, function(res) {
        if (res.status === 'success') {
            // Apply theme immediately via existing JS function
            if (typeof setAppTheme === 'function') {
                setAppTheme(theme);
                localStorage.setItem('theme', theme);
            }
            // Update UI markers
            $('.theme-card').removeClass('border-primary bg-primary bg-opacity-10').find('i').removeClass('text-primary');
            $(`.theme-card[onclick*="'${theme}'"]`).addClass('border-primary bg-primary bg-opacity-10').find('i').addClass('text-primary');
            
            showToast('Theme synced to your account!', 'success');
        }
    });
}
</script>

<?= $this->endSection() ?>

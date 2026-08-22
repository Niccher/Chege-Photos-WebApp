<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Photos</title>
    <base href="<?= base_url() ?>">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Fabric.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Dropzone CSS -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= base_url('app_icon.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('app_icon.png') ?>">
    <script>
        const BASE_URL = '<?= base_url() ?>';
    </script>
    <link rel="stylesheet" href="<?= base_url('css/photos.css') ?>">
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<nav class="navbar navbar-expand-lg sticky-top glass-effect">
        <div class="container-fluid d-flex align-items-center">
            <button class="btn btn-link text-dark me-2 d-xl-none" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand me-auto" href="<?= base_url('photos') ?>">
                <img src="<?= base_url('app_icon.png') ?>" alt="Logo" width="32" height="32" class="me-2 rounded shadow-sm">
                <span>Photos</span>
            </a>
        <?php $isAdminRoute = str_starts_with((uri_string() ?? ''), 'admin'); ?>
        <?php if (!$isAdminRoute): ?>
        <form class="ms-3 flex-grow-1 d-none d-lg-block" style="max-width: 400px;" onsubmit="return false;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Search photos..." value="<?= $searchQuery ?? '' ?>">
            </div>
        </form>
        <?php endif; ?>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (!$isAdminRoute): ?>
            <button class="btn btn-outline-secondary btn-sm px-3 d-none d-sm-inline-block" id="btnToggleSelect" title="Select photos">
                <i class="bi bi-check2-square"></i> <span id="selectModeText">Select</span>
            </button>
            <?php endif; ?>
            <div class="dropdown mx-1">
                <button class="btn btn-link text-dark p-2" id="btnThemeDropdown" data-bs-toggle="dropdown" title="Change Theme">
                    <i class="bi bi-palette fs-5"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-effect shadow border-0 p-2" style="min-width: 150px;">
                    <li><a class="dropdown-item rounded-3 mb-1 theme-opt active" href="#" data-theme="auto"><i class="bi bi-display me-2"></i>Auto (OS)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="#" data-theme="light"><i class="bi bi-sun me-2"></i>Light</a></li>
                    <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="#" data-theme="dark"><i class="bi bi-moon-stars me-2"></i>Dark</a></li>
                    <li><a class="dropdown-item rounded-3 mb-1 theme-opt" href="#" data-theme="solarized"><i class="bi bi-brightness-high me-2"></i>Solarized</a></li>
                    <li><a class="dropdown-item rounded-3 theme-opt" href="#" data-theme="grey"><i class="bi bi-circle-half me-2"></i>Grey</a></li>
                </ul>
            </div>
            <?php if ($isAdminRoute): ?>
            <button class="btn btn-link text-dark p-2 position-relative me-2" id="btnNavMlJobs" data-bs-toggle="modal" data-bs-target="#mlJobsModal" title="ML Background Jobs">
                <i class="bi bi-cpu fs-5"></i>
                <span class="position-absolute top-1 start-75 translate-middle badge rounded-pill bg-danger d-none" id="mlJobsActiveBadge" style="font-size:0.55rem; padding:0.25em 0.4em;">
                    <span class="spinner-grow spinner-grow-sm" role="status" style="width: 6px; height: 6px;"></span>
                </span>
            </button>
            <?php endif; ?>
            <?php if (!$isAdminRoute): ?>
            <button class="btn btn-outline-primary btn-sm" id="btnScan" title="Scan uploads folder">
                <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Scan</span>
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal" title="Upload photos">
                <i class="bi bi-cloud-upload"></i> <span class="d-none d-md-inline">Upload</span>
            </button>
            <?php endif; ?>
            <?php if (auth()->loggedIn()): ?>
            <?php $navUser = auth()->user(); $navAvatar = $navUser->avatar ?? null; ?>
            <div class="dropdown">
                <button class="btn btn-link p-0 d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if ($navAvatar && is_string($navAvatar) && str_starts_with($navAvatar, 'uploads/')): ?>
                        <img src="<?= base_url($navAvatar) ?>" alt="" class="rounded-circle border border-secondary border-opacity-25" width="34" height="34" style="object-fit:cover;">
                    <?php else: ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:34px;height:34px;background:linear-gradient(135deg,#4285f4,#00c6ff);font-size:0.85rem;cursor:pointer;">
                        <?= strtoupper(substr(($navUser->username ?? $navUser->email ?? 'U'), 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;">
                    <li class="px-3 py-2">
                        <div class="fw-semibold" style="font-size:0.9rem;"><?= esc($navUser->name ?: ($navUser->username ?? '')) ?></div>
                        <div class="text-muted" style="font-size:0.78rem;"><?= esc($navUser->username ?? '') ?></div>
                        <div class="text-muted" style="font-size:0.72rem;"><?= esc($navUser->email) ?></div>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= base_url('settings') ?>">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= url_to('logout') ?>">
                            <i class="bi bi-box-arrow-right text-danger"></i>
                            <span>Sign out</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif ?>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <nav id="sidebarMenu" class="sidebar">
        <div class="d-flex flex-column h-100">
            <ul class="nav flex-column mb-auto">
                <?php if (str_starts_with((uri_string() ?? ''), 'admin')): ?>
                <!-- ADMIN CONSOLE SECTION -->
                <li class="sidebar-section-title">Administration</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--photos <?= (uri_string() === 'admin/home') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/home') ?>">
                        <span><i class="bi bi-speedometer2"></i> Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--settings <?= (uri_string() === 'admin/settings') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/settings') ?>">
                        <span><i class="bi bi-sliders"></i> Global Configs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--faces <?= (uri_string() === 'admin/users') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/users') ?>">
                        <span><i class="bi bi-people"></i> User Accounts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--sharing <?= (uri_string() === 'admin/smtp') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/smtp') ?>">
                        <span><i class="bi bi-envelope-at"></i> Email Config</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--settings <?= (uri_string() === 'admin/ml') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/ml') ?>">
                        <span><i class="bi bi-cpu"></i> ML Config</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--photos <?= (uri_string() === 'admin/storage') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/storage') ?>">
                        <span><i class="bi bi-hdd"></i> Storage Config</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--timeline <?= (uri_string() === 'admin/crons') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/crons') ?>">
                        <span><i class="bi bi-clock-history"></i> System Crons</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--sharing <?= (uri_string() === 'admin/health') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/health') ?>">
                        <span><i class="bi bi-heart-pulse"></i> Diagnostics</span>
                    </a>
                </li>
                
                <li class="sidebar-section-title">Navigation</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--explore d-flex justify-content-between align-items-center" href="<?= base_url('photos') ?>">
                        <span><i class="bi bi-arrow-left-circle"></i> Return to Library</span>
                    </a>
                </li>
                <?php else: ?>
                <!-- LIBRARY SECTION -->
                <li class="sidebar-section-title">Library</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--photos <?= (url_is('photos')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('photos') ?>">
                        <span><i class="bi bi-image"></i> Photos</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--photos"><?= (int) ($counts['photos'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--faces <?= (url_is('faces')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('faces') ?>">
                        <span><i class="bi bi-people"></i> Faces</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--explore <?= (url_is('explore')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('explore') ?>">
                        <span><i class="bi bi-compass"></i> Explore</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--explore"><?= (int) ($counts['explore'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--favorites <?= (url_is('favorites')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('favorites') ?>">
                        <span><i class="bi bi-heart"></i> Favorites</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--favorites"><?= (int) ($counts['favorites'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--memories <?= (url_is('memories')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('memories') ?>">
                        <span><i class="bi bi-clock-history"></i> Memories</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--memories"><?= (int) ($counts['memories'] ?? 0) ?></span>
                    </a>
                </li>
 
                <!-- ALBUMS (dropdown) -->
                <li class="nav-item dropdown dropend">
                    <a class="nav-link dropdown-toggle sidebar-nav-tone sidebar-nav-tone--albums d-flex justify-content-between align-items-center gap-2 <?= str_starts_with((string) (uri_string() ?: ''), 'albums') ? 'active' : '' ?>"
                       href="#"
                       id="sidebarAlbumsDropdown"
                       role="button"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="true"
                       aria-expanded="false"
                       aria-haspopup="true"
                       aria-controls="sidebarAlbumsMenu">
                        <span class="text-truncate"><i class="bi bi-journal-album me-2"></i>Albums</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--albums flex-shrink-0"><?= (int) ($counts['albums'] ?? 0) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark shadow border border-secondary py-1 sidebar-albums-dropdown" id="sidebarAlbumsMenu" aria-labelledby="sidebarAlbumsDropdown" style="min-width: 240px; max-height: min(70vh, 360px); overflow-y: auto;">
                        <li>
                            <a class="dropdown-item rounded-1 <?= (uri_string() === 'albums') ? 'active' : '' ?>" href="<?= base_url('albums') ?>">
                                <i class="bi bi-grid-3x3-gap me-2 opacity-75"></i>All albums
                            </a>
                        </li>
                        <?php if (! empty($counts['sidebar_albums'])): ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php foreach ($counts['sidebar_albums'] as $album): ?>
                                <li>
                                    <a class="dropdown-item rounded-1 album-dropzone text-truncate <?= (uri_string() === 'albums/' . (int) $album['id']) ? 'active' : '' ?>"
                                       href="<?= base_url('albums/' . $album['id']) ?>"
                                        data-album-id="<?= $album['id'] ?>"
                                        data-is-smart="<?= ! empty($album['is_smart']) ? '1' : '0' ?>"
                                        title="<?= esc($album['name']) ?>">
                                         <i class="bi <?= ! empty($album['is_smart']) ? 'bi-stars' : 'bi-folder' ?> me-2 flex-shrink-0"></i><?= esc($album['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
 
                <!-- TOOLS SECTION -->
                <li class="sidebar-section-title">Tools</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--settings <?= (uri_string() === 'settings') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('settings') ?>">
                        <span><i class="bi bi-gear"></i> Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--sharing <?= (url_is('sharing')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('sharing') ?>">
                        <span><i class="bi bi-share"></i> Sharing</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--sharing"><?= (int) ($counts['sharing'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--analytics <?= (url_is('analytics')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('analytics') ?>">
                        <span><i class="bi bi-bar-chart-line"></i> Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--archive <?= (url_is('archive')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('archive') ?>">
                        <span><i class="bi bi-archive"></i> Archive</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--archive"><?= (int) ($counts['archive'] ?? 0) ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--trash <?= (url_is('trash')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('trash') ?>">
                        <span><i class="bi bi-trash"></i> Trash</span>
                        <span class="badge rounded-pill sidebar-count sidebar-count--trash"><?= (int) ($counts['trash'] ?? 0) ?></span>
                    </a>
                </li>
                <?php if (auth()->loggedIn() && auth()->user()->inGroup('superadmin')): ?>
                <!-- ADMINISTRATION SECTION -->
                <li class="sidebar-section-title">Administration</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--settings <?= str_starts_with((uri_string() ?? ''), 'admin') ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url('admin/home') ?>">
                        <span><i class="bi bi-shield-lock"></i> Admin Console</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <div class="storage-indicator p-3 rounded-4 mt-4" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="small fw-bold" style="color: var(--text-primary);">Storage</span>
                    <span class="small" style="color: var(--text-muted);"><?= round($storagePercent ?? 0) ?>%</span>
                </div>
                <div class="progress" style="height: 6px; background-color: var(--border-color);">
                    <div class="progress-bar <?= ($storagePercent ?? 0) > 90 ? 'bg-danger' : '' ?>" role="progressbar" style="width: <?= $storagePercent ?? 0 ?>%; background-color: var(--accent-color);" aria-valuenow="<?= $storagePercent ?? 0 ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <p class="small mt-2 mb-0" style="font-size: 0.7rem; color: var(--text-muted);"><?= $storageUsed ?? '0 B' ?> of 1 GB used</p>
            </div>
        </div>
    </nav>

        <main class="main-content">
            <?= $this->renderSection('content') ?>
        </main>
        
        <!-- Interactive Timeline Scrubbar -->
        <div id="timelineScrubbar" class="timeline-scrubbar d-none d-lg-flex">
            <div class="timeline-line"></div>
            <div id="timelineMarkers"></div>
            <div id="timelineTooltip" class="timeline-tooltip d-none">
                <span id="timelineTooltipText"></span>
            </div>
        </div>
</div>

<!-- Bulk Actions Toolbar (Floating) -->
<div id="bulkActionsToolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 bg-dark text-white rounded-pill shadow-lg px-4 py-2 d-none" style="z-index: 1050; border: 1px solid rgba(255,255,255,0.1);">
    <div class="d-flex align-items-center gap-4">
        <span class="small fw-bold"><span id="selectedCount">0</span> selected</span>
        <div class="vr"></div>
        <div class="d-flex gap-2">
            <button class="btn btn-link text-white p-0" id="bulkFavorite" title="Favorite Selected">
                <i class="bi bi-heart fs-5"></i>
            </button>
            <button class="btn btn-link text-white p-0" id="bulkArchive" title="Archive Selected">
                <i class="bi bi-archive fs-5"></i>
            </button>
            <button class="btn btn-link text-white p-0" id="bulkDelete" title="Delete Selected">
                <i class="bi bi-trash fs-5"></i>
            </button>
            <button class="btn btn-link text-white p-0" id="bulkTrash" title="Move Selected to Trash">
                <i class="bi bi-recycle fs-5"></i>
            </button>
            <button class="btn btn-link text-white p-0" id="bulkDownload" title="Download Selected">
                <i class="bi bi-download fs-5"></i>
            </button>
            <button class="btn btn-link text-white p-0" id="bulkAddToAlbum" title="Add Selected to Album">
                <i class="bi bi-plus-circle fs-5"></i>
            </button>
        </div>
        <div class="vr"></div>
        <button class="btn btn-link text-white p-0" id="btnCancelSelect" title="Cancel Selection">
            <i class="bi bi-x-lg fs-6"></i>
        </button>
    </div>
</div>

<!-- Lightbox Modal (Global) -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-black border-0 flex-row">
            <div class="modal-header border-0 p-3 position-absolute top-0 start-0 w-100 d-flex justify-content-center" style="z-index: 1056; background: linear-gradient(to bottom, rgba(0,0,0,0.6), transparent); pointer-events: none;">
                <div class="d-flex align-items-center lightbox-toolbar px-3 py-1 shadow-lg" style="pointer-events: auto;">
                    <!-- Exit / Close Button -->
                    <button type="button" class="btn btn-link text-white p-2" data-bs-dismiss="modal" aria-label="Close" title="Close (Esc)">
                        <i class="bi bi-x-lg fs-6"></i>
                    </button>
                    
                    <div class="vr bg-white opacity-25 mx-2" style="height: 20px;"></div>

                    <!-- Photo Action Buttons -->
                    <button type="button" class="btn btn-link text-white p-2" id="btnShareLink" title="Create Public Link">
                        <i class="bi bi-link-45deg fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnFavorite" title="Favorite">
                        <i class="bi bi-heart fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnAddToAlbum" title="Add to Album">
                        <i class="bi bi-plus-circle fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnEditPhoto" title="Edit Photo">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnRestore" style="display: none;" title="Restore">
                        <i class="bi bi-clock-history fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnArchive" title="Archive/Unarchive">
                        <i class="bi bi-archive fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnDelete" title="Delete">
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" id="btnSlideshow" title="Start Slideshow">
                        <i class="bi bi-play-fill fs-5"></i>
                    </button>

                    <div class="vr bg-white opacity-25 mx-2" style="height: 20px;"></div>

                    <!-- Info Toggle Button -->
                    <button type="button" class="btn btn-link text-white p-2" id="btnInfo" title="Info & Details">
                        <i class="bi bi-info-circle fs-5"></i>
                    </button>

                    <div class="vr bg-white opacity-25 mx-2" style="height: 20px;"></div>
                    <!-- Photo Counter -->
                    <span id="lightboxCounter" class="text-white opacity-75 small font-monospace align-self-center px-1">0 / 0</span>
                </div>
            </div>
            
            <!-- Slideshow Progress Bar -->
            <div id="slideshowProgress" class="position-absolute bottom-0 start-0 w-100 d-none" style="height: 4px; background: rgba(255,255,255,0.1); z-index: 1060;">
                <div class="progress-bar bg-primary h-100" style="width: 0%; transition: width 0.1s linear;"></div>
            </div>
            
            <!-- Link Copy Tooltip (Pseudo) -->
            <div id="shareLinkPopup" class="position-absolute top-10 start-50 translate-middle-x bg-white text-dark rounded shadow px-3 py-2 d-none" style="z-index: 1060; margin-top: 60px;">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold" id="sharedUrlText"></span>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" id="btnCopyLink">Copy</button>
                </div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <label class="small text-muted text-nowrap" for="linkExpiryInput">Expires</label>
                    <input type="datetime-local" id="linkExpiryInput" class="form-control form-control-sm" style="min-width: 180px;">
                    <button class="btn btn-outline-dark btn-sm rounded-pill px-3" id="btnApplyExpiry">Apply</button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center flex-grow-1 overflow-hidden" id="lightboxImageContainer">
            </div>

            <!-- Navigation Arrows -->
            <button class="lightbox-nav lightbox-prev" id="btnPrevPhoto" title="Previous (Left Arrow)">
                <i class="bi bi-chevron-left fs-1"></i>
            </button>
            <button class="lightbox-nav lightbox-next" id="btnNextPhoto" title="Next (Right Arrow)">
                <i class="bi bi-chevron-right fs-1"></i>
            </button>
            
            <!-- Metadata Panel -->
            <div id="metadataPanel" class="h-100 d-none overflow-auto" style="width: 340px; z-index: 1057; background: #111; color: #e8e8e8; border-left: 1px solid rgba(255,255,255,0.08);">

                <!-- Panel Header -->
                <div class="d-flex justify-content-between align-items-center px-4 pt-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <span class="fw-semibold" style="font-size: 1rem; letter-spacing: 0.02em;">Details</span>
                    <button type="button" class="btn btn-link p-1 text-white opacity-50" id="btnCloseMetadata" title="Close panel">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="px-4 py-3">

                    <!-- ── FILE INFO SECTION ── -->
                    <p class="text-uppercase mb-2" style="font-size: 0.67rem; letter-spacing: 0.12em; color: #888;">File Info</p>

                    <!-- Filename -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <i class="bi bi-file-earmark-image mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                        <div>
                            <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Filename</div>
                            <div id="metaFilename" class="text-break" style="font-size: 0.88rem; word-break: break-all;"></div>
                        </div>
                    </div>

                    <!-- Created date -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <i class="bi bi-calendar3 mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                        <div>
                            <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Created</div>
                            <div id="metaDate" style="font-size: 0.88rem;"></div>
                        </div>
                    </div>

                    <!-- File size -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <i class="bi bi-hdd mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                        <div>
                            <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Size</div>
                            <div id="metaSize" style="font-size: 0.88rem;"></div>
                        </div>
                    </div>

                    <!-- Dimensions -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <i class="bi bi-aspect-ratio mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                        <div>
                            <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Dimensions</div>
                            <div id="metaDimensions" style="font-size: 0.88rem;"></div>
                        </div>
                    </div>

                    <!-- Location (hidden until populated) -->
                    <div class="d-flex align-items-start gap-3 mb-3" id="metaLocationContainer" style="display:none !important;">
                        <i class="bi bi-geo-alt mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                        <div>
                            <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Location</div>
                            <a href="#" id="metaLocation" target="_blank" class="text-decoration-none" style="font-size: 0.88rem; color: #7aacff;"></a>
                        </div>
                    </div>

                    <!-- ── CAMERA SPECS SECTION ── -->
                    <div id="metaExifContainer" style="display:none;">
                        <hr style="border-color: rgba(255,255,255,0.08); margin: 1rem 0;">
                        <p class="text-uppercase mb-2" style="font-size: 0.67rem; letter-spacing: 0.12em; color: #888;">Camera Specs</p>

                        <!-- Camera model -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaCameraRow" style="display:none !important;">
                            <i class="bi bi-camera mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Camera</div>
                                <div id="metaCameraModel" style="font-size: 0.92rem; font-weight: 500;"></div>
                            </div>
                        </div>

                        <!-- Shutter speed -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaShutterRow" style="display:none !important;">
                            <i class="bi bi-stopwatch mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Shutter Speed</div>
                                <div id="metaShutter" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- Aperture -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaApertureRow" style="display:none !important;">
                            <i class="bi bi-circle-half mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Aperture</div>
                                <div id="metaAperture" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- ISO -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaIsoRow" style="display:none !important;">
                            <i class="bi bi-brightness-high mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">ISO</div>
                                <div id="metaIso" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- Focal length -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaFocalRow" style="display:none !important;">
                            <i class="bi bi-zoom-in mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Focal Length</div>
                                <div id="metaFocal" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- Flash -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaFlashRow" style="display:none !important;">
                            <i class="bi bi-lightning mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Flash</div>
                                <div id="metaFlash" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- White balance -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaWbRow" style="display:none !important;">
                            <i class="bi bi-thermometer-half mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">White Balance</div>
                                <div id="metaWb" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- Metering mode -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaMeteringRow" style="display:none !important;">
                            <i class="bi bi-bullseye mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Metering Mode</div>
                                <div id="metaMetering" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>

                        <!-- GPS Altitude -->
                        <div class="d-flex align-items-start gap-3 mb-3" id="metaAltitudeRow" style="display:none !important;">
                            <i class="bi bi-bar-chart-steps mt-1" style="font-size: 1.1rem; color: #aaa; min-width: 20px;"></i>
                            <div>
                                <div style="font-size: 0.72rem; color: #888; margin-bottom: 2px;">Altitude</div>
                                <div id="metaAltitude" style="font-size: 0.88rem;"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add to Album Modal -->
<div class="modal fade" id="addToAlbumModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-dark text-white border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Add to Album</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="albumListContainer" class="list-group list-group-flush bg-transparent">
                    <!-- Populated via JS -->
                    <div class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal (Dropzone) -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Photos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= base_url('upload') ?>" class="dropzone border-primary border-dashed rounded-3" id="photoDropzone" style="background: #f8f9fa;">
                    <div class="dz-message needsclick">
                        <i class="bi bi-cloud-arrow-up display-4 text-primary mb-3"></i><br>
                        <h4>Drop photos here or click to upload.</h4>
                        <span class="text-muted note needsclick">Supports JPG, PNG, WEBP, GIF, MP4, MOV, WEBM (up to 500MB per file)</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload()">Done</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;">
    <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-info-circle me-2" id="toastIcon"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Photo Editor Modal -->
<div class="modal fade" id="editorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 bg-black p-2 d-flex justify-content-between align-items-center" style="z-index: 10;">
                <div class="d-flex align-items-center gap-3 ms-2">
                    <h6 class="text-white mb-0 fw-bold"><i class="bi bi-pencil-square me-2"></i>Photo Editor</h6>
                </div>
                <div class="d-flex gap-2 me-2">
                    <button type="button" class="btn btn-outline-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnSaveEdit">Save Changes</button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex flex-column flex-md-row overflow-hidden bg-black">
                <!-- Toolbar -->
                <div class="editor-toolbar bg-dark border-end border-secondary p-3 d-flex flex-md-column gap-3 overflow-auto" style="min-width: 80px; z-index: 5;">
                    <button class="btn btn-link text-white p-2 editor-tool" id="toolRotateLeft" title="Rotate Left">
                        <i class="bi bi-arrow-counterclockwise fs-4"></i>
                    </button>
                    <button class="btn btn-link text-white p-2 editor-tool" id="toolRotateRight" title="Rotate Right">
                        <i class="bi bi-arrow-clockwise fs-4"></i>
                    </button>
                    <div class="vr d-md-none mx-2"></div>
                    <hr class="d-none d-md-block my-2 border-secondary">
                    <button class="btn btn-link text-white p-2 editor-tool" id="toolCrop" title="Crop">
                        <i class="bi bi-crop fs-4"></i>
                    </button>
                    <div class="vr d-md-none mx-2"></div>
                    <hr class="d-none d-md-block my-2 border-secondary">
                    <button class="btn btn-link text-white p-2 editor-tool" data-filter="grayscale" title="Grayscale">
                        <i class="bi bi-circle-half fs-4"></i>
                    </button>
                    <button class="btn btn-link text-white p-2 editor-tool" data-filter="sepia" title="Sepia">
                        <i class="bi bi-palette fs-4"></i>
                    </button>
                    <button class="btn btn-link text-white p-2 editor-tool" data-filter="brightness" title="Auto-Enhance">
                        <i class="bi bi-sun fs-4"></i>
                    </button>
                    <hr class="d-none d-md-block my-2 border-secondary">
                    <button class="btn btn-link text-white p-2 editor-tool text-danger" id="toolReset" title="Reset All">
                        <i class="bi bi-arrow-repeat fs-4"></i>
                    </button>
                </div>
                <!-- Canvas Area -->
                <div class="flex-grow-1 d-flex align-items-center justify-content-center p-4 position-relative" id="editorCanvasContainer">
                    <canvas id="editorCanvas"></canvas>
                    <div id="cropOverlay" class="d-none position-absolute border border-primary" style="box-shadow: 0 0 0 9999px rgba(0,0,0,0.5); cursor: move;">
                        <div class="crop-handle nw"></div>
                        <div class="crop-handle ne"></div>
                        <div class="crop-handle sw"></div>
                        <div class="crop-handle se"></div>
                        <button class="btn btn-primary btn-sm position-absolute bottom-0 start-50 translate-middle-x mb-n5" id="btnConfirmCrop">Apply Crop</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ML Jobs Progress Modal (Admin Only) -->
<?php if ($isAdminRoute): ?>
<div class="modal fade" id="mlJobsModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-md" style="max-width: 450px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; background: #ffffff; color: #212529; border: 1px solid rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 px-4 pt-4">
                <h6 class="modal-title fw-bold d-flex align-items-center gap-2 text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-cpu text-primary"></i>
                    <span>ML Pipeline Status</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <!-- Faces progress -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark"><i class="bi bi-person-bounding-box text-primary me-2"></i>Face Recognition</span>
                        <span class="badge bg-secondary" id="statusBadgeFaces" style="font-size: 0.65rem; padding: 0.25em 0.5em;">Idle</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted" style="font-size: 0.78rem;">Scanned Files</span>
                        <span id="navScannedFaces" class="fw-bold text-dark" style="font-size: 0.85rem;">- / -</span>
                    </div>
                    <div class="progress" style="height: 6px; background: rgba(0,0,0,0.08);">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" id="barNavScannedFaces" role="progressbar" style="width: 0%; transition: width 0.4s ease;"></div>
                    </div>
                </div>
                
                <!-- Tags progress -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark"><i class="bi bi-tags text-success me-2"></i>YOLOv8 Object Tags</span>
                        <span class="badge bg-secondary" id="statusBadgeTags" style="font-size: 0.65rem; padding: 0.25em 0.5em;">Idle</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted" style="font-size: 0.78rem;">Scanned Files</span>
                        <span id="navScannedTags" class="fw-bold text-dark" style="font-size: 0.85rem;">- / -</span>
                    </div>
                    <div class="progress" style="height: 6px; background: rgba(0,0,0,0.08);">
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="barNavScannedTags" role="progressbar" style="width: 0%; transition: width 0.4s ease;"></div>
                    </div>
                </div>
                
                <!-- CLIP progress -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-dark"><i class="bi bi-lightning-charge text-info me-2"></i>CLIP Semantic Search</span>
                        <span class="badge bg-secondary" id="statusBadgeClips" style="font-size: 0.65rem; padding: 0.25em 0.5em;">Idle</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted" style="font-size: 0.78rem;">Scanned Files</span>
                        <span id="navScannedClips" class="fw-bold text-dark" style="font-size: 0.85rem;">- / -</span>
                    </div>
                    <div class="progress" style="height: 6px; background: rgba(0,0,0,0.08);">
                        <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" id="barNavScannedClips" role="progressbar" style="width: 0%; transition: width 0.4s ease;"></div>
                    </div>
                </div>

                <!-- Metrics / Speed Stats Card -->
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.06);" id="mlJobStatsCard">
                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.75rem;">
                        <span class="text-muted"><i class="bi bi-activity me-2"></i>Status:</span>
                        <span class="fw-semibold text-dark" id="mlJobsStatusText">Idle (Waiting)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 d-none" id="mlJobsStartRow" style="font-size: 0.75rem;">
                        <span class="text-muted"><i class="bi bi-clock me-2"></i>Started At:</span>
                        <span class="fw-semibold text-dark" id="mlJobsStartedAt">-</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 d-none" id="mlJobsSpeedRow" style="font-size: 0.75rem;">
                        <span class="text-muted"><i class="bi bi-speedometer2 me-2"></i>Processing Speed:</span>
                        <span class="fw-semibold text-dark" id="mlJobsSpeed">-</span>
                    </div>
                    <div class="d-flex justify-content-between d-none" id="mlJobsEtaRow" style="font-size: 0.75rem;">
                        <span class="text-muted"><i class="bi bi-hourglass-split me-2"></i>Est. Time Left:</span>
                        <span class="fw-semibold text-danger" id="mlJobsEta">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('sidebarAlbumsDropdown');
        if (!el || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;
        var existing = bootstrap.Dropdown.getInstance(el);
        if (existing) existing.dispose();
        new bootstrap.Dropdown(el, {
            popperConfig: { strategy: 'fixed' }
        });
    });
})();
</script>
<!-- Dropzone JS -->
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
    Dropzone.autoDiscover = false;
</script>
<!-- Custom JS -->
<script src="<?= base_url('js/app.js') ?>"></script>
<?php if ($isAdminRoute): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var navPollInterval = null;
        
        function formatElapsedTime(ms) {
            var secs = Math.floor(ms / 1000);
            var mins = Math.floor(secs / 60);
            secs = secs % 60;
            return mins > 0 ? mins + 'm ' + secs + 's' : secs + 's';
        }

        function updateNavMlStats() {
            $.getJSON(BASE_URL + 'admin/ml/stats', function(res) {
                if (res.status === 'success' && res.stats) {
                    var stats = res.stats;
                    var total = parseInt(stats.total_photos) || 0;
                    
                    var faces = parseInt(stats.scanned_faces) || 0;
                    var tags = parseInt(stats.scanned_tags) || 0;
                    var clips = parseInt(stats.scanned_clips) || 0;
                    
                    // Update Text
                    $('#navScannedFaces').text(faces + ' / ' + total);
                    $('#navScannedTags').text(tags + ' / ' + total);
                    $('#navScannedClips').text(clips + ' / ' + total);
                    
                    // Update Bars
                    var fPct = total > 0 ? (faces / total) * 100 : 0;
                    var tPct = total > 0 ? (tags / total) * 100 : 0;
                    var cPct = total > 0 ? (clips / total) * 100 : 0;
                    
                    $('#barNavScannedFaces').css('width', fPct + '%');
                    $('#barNavScannedTags').css('width', tPct + '%');
                    $('#barNavScannedClips').css('width', cPct + '%');

                    // Determine Active tasks
                    var facesActive = faces < total;
                    var tagsActive = tags < total;
                    var clipsActive = clips < total;
                    var anyActive = facesActive || tagsActive || clipsActive;

                    // Update Status Badges
                    if (facesActive) {
                        $('#statusBadgeFaces').text('Processing...').attr('class', 'badge bg-primary progress-bar-animated');
                    } else {
                        $('#statusBadgeFaces').text('Idle').attr('class', 'badge bg-secondary');
                    }

                    if (tagsActive) {
                        $('#statusBadgeTags').text('Processing...').attr('class', 'badge bg-success progress-bar-animated');
                    } else {
                        $('#statusBadgeTags').text('Idle').attr('class', 'badge bg-secondary');
                    }

                    if (clipsActive) {
                        $('#statusBadgeClips').text('Processing...').attr('class', 'badge bg-info text-dark progress-bar-animated');
                    } else {
                        $('#statusBadgeClips').text('Idle').attr('class', 'badge bg-secondary');
                    }

                    // Track & calculate speed and ETA metrics
                    if (anyActive) {
                        $('#mlJobsStatusText').text('Processing sequential pipeline...').removeClass('text-white-50').addClass('text-primary fw-bold');
                        
                        var now = Date.now();
                        var startTime = sessionStorage.getItem('ml_jobs_start_time');
                        if (!startTime) {
                            startTime = now;
                            sessionStorage.setItem('ml_jobs_start_time', startTime);
                            // Store initial values to compute delta
                            sessionStorage.setItem('ml_jobs_init_faces', faces);
                            sessionStorage.setItem('ml_jobs_init_tags', tags);
                            sessionStorage.setItem('ml_jobs_init_clips', clips);
                        }
                        
                        var elapsedMs = now - parseInt(startTime);
                        var initFaces = parseInt(sessionStorage.getItem('ml_jobs_init_faces') || faces);
                        var initTags = parseInt(sessionStorage.getItem('ml_jobs_init_tags') || tags);
                        var initClips = parseInt(sessionStorage.getItem('ml_jobs_init_clips') || clips);
                        
                        var processedDelta = (faces - initFaces) + (tags - initTags) + (clips - initClips);
                        
                        // Render Started At
                        var startDate = new Date(parseInt(startTime));
                        $('#mlJobsStartedAt').text(startDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'}));
                        $('#mlJobsStartRow').removeClass('d-none');

                        if (processedDelta > 0 && elapsedMs > 1000) {
                            var secPerImg = (elapsedMs / 1000) / processedDelta;
                            $('#mlJobsSpeed').text(secPerImg.toFixed(1) + 's / photo');
                            $('#mlJobsSpeedRow').removeClass('d-none');
                            
                            // ETA
                            var remainingPhotos = (total - faces) + (total - tags) + (total - clips);
                            var etaSeconds = remainingPhotos * secPerImg;
                            if (etaSeconds > 0) {
                                $('#mlJobsEta').text(formatElapsedTime(etaSeconds * 1000));
                                $('#mlJobsEtaRow').removeClass('d-none');
                            } else {
                                $('#mlJobsEtaRow').addClass('d-none');
                            }
                        } else {
                            $('#mlJobsSpeed').text('Estimating speed...');
                            $('#mlJobsSpeedRow').removeClass('d-none');
                            $('#mlJobsEtaRow').addClass('d-none');
                        }
                    } else {
                        // Reset all to Idle
                        $('#mlJobsStatusText').text('Idle (Waiting)').removeClass('text-primary fw-bold').addClass('text-white-50');
                        $('#mlJobsStartRow, #mlJobsSpeedRow, #mlJobsEtaRow').addClass('d-none');
                        sessionStorage.removeItem('ml_jobs_start_time');
                        sessionStorage.removeItem('ml_jobs_init_faces');
                        sessionStorage.removeItem('ml_jobs_init_tags');
                        sessionStorage.removeItem('ml_jobs_init_clips');
                    }
                    
                    // Show or hide the active indicator pulse badge
                    if (anyActive) {
                        $('#mlJobsActiveBadge').removeClass('d-none');
                        $('#btnNavMlJobs i').addClass('text-primary');
                    } else {
                        $('#mlJobsActiveBadge').addClass('d-none');
                        $('#btnNavMlJobs i').removeClass('text-primary');
                        if (navPollInterval) {
                            clearInterval(navPollInterval);
                            navPollInterval = null;
                        }
                    }
                }
            });
        }
        
        updateNavMlStats();
        navPollInterval = setInterval(updateNavMlStats, 5000);
        
        // Listen to rescan trigger buttons to start tracking instantly
        $(document).on('click', '.btn-rescan, #btnTriggerCluster', function() {
            var type = $(this).data('type') || 'all';
            sessionStorage.removeItem('ml_jobs_start_time'); // force recalculation on next poll
            setTimeout(function() {
                updateNavMlStats();
                if (!navPollInterval) {
                    navPollInterval = setInterval(updateNavMlStats, 5000);
                }
            }, 1000);
        });
    });
</script>
<?php endif; ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>

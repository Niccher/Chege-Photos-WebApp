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
            <a class="navbar-brand me-auto" href="<?= base_url() ?>">
                <img src="<?= base_url('app_icon.png') ?>" alt="Logo" width="32" height="32" class="me-2 rounded shadow-sm">
                <span>Photos</span>
            </a>
        <form class="ms-3 flex-grow-1 d-none d-lg-block" style="max-width: 400px;" onsubmit="return false;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="Search photos..." value="<?= $searchQuery ?? '' ?>">
            </div>
        </form>
        <div class="ms-auto d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary btn-sm px-3 d-none d-sm-inline-block" id="btnToggleSelect" title="Select photos">
                <i class="bi bi-check2-square"></i> <span id="selectModeText">Select</span>
            </button>
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
            <button class="btn btn-outline-primary btn-sm" id="btnScan" title="Scan uploads folder">
                <i class="bi bi-arrow-repeat"></i> <span class="d-none d-md-inline">Scan</span>
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal" title="Upload photos">
                <i class="bi bi-cloud-upload"></i> <span class="d-none d-md-inline">Upload</span>
            </button>
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
                <!-- LIBRARY SECTION -->
                <li class="sidebar-section-title">Library</li>
                <li class="nav-item">
                    <a class="nav-link sidebar-nav-tone sidebar-nav-tone--photos <?= (url_is('/')) ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="<?= base_url() ?>">
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

                <!-- ALBUMS (dropdown; fixed Popper strategy escapes sidebar overflow clipping) -->
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
                    <button class="nav-link sidebar-nav-tone sidebar-nav-tone--settings d-flex justify-content-between align-items-center border-0 bg-transparent w-100" id="btnBackfillExif" style="color: var(--text-primary);">
                        <span><i class="bi bi-camera-fill"></i> Backfill EXIF</span>
                    </button>
                </li>
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
            <div class="modal-header border-0 p-3 position-absolute top-0 start-0 w-100 d-flex justify-content-between" style="z-index: 1056; background: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent);">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="d-flex align-items-center lightbox-toolbar px-2">
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
                    <button type="button" class="btn btn-link text-white p-2 ms-2 border-start border-secondary" id="btnInfo" title="Info">
                        <i class="bi bi-info-circle fs-5"></i>
                    </button>
                </div>
            </div>
            
            <!-- Slideshow Progress Bar -->
            <div id="slideshowProgress" class="position-absolute bottom-0 start-0 w-100 d-none" style="height: 4px; background: rgba(255,255,255,0.1); z-index: 1060;">
                <div class="progress-bar bg-primary h-100" style="width: 0%; transition: width 0.1s linear;"></div>
            </div>
            
            <!-- Link Copy Tooltip (Pseudo) -->
            <div id="shareLinkPopup" class="position-absolute top-10 start-50 translate-middle-x bg-white text-dark rounded-pill shadow px-3 py-2 d-none" style="z-index: 1060; margin-top: 60px;">
                <div class="d-flex align-items-center gap-2">
                    <span class="small fw-bold" id="sharedUrlText"></span>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" id="btnCopyLink">Copy</button>
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
            <div id="metadataPanel" class="bg-white p-4 h-100 d-none overflow-auto" style="width: 360px; z-index: 1057;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0">Details</h5>
                    <button type="button" class="btn-close" id="btnCloseMetadata"></button>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Filename</label>
                    <span id="metaFilename" class="text-break"></span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Created</label>
                    <span id="metaDate"></span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Size</label>
                    <span id="metaSize"></span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Dimensions</label>
                    <span id="metaDimensions"></span>
                </div>
                <div class="mb-3" id="metaExifContainer" style="display:none;">
                    <label class="small text-muted d-block">Camera</label>
                    <span id="metaExif" class="small"></span>
                </div>
                <div class="mb-3" id="metaLocationContainer" style="display:none;">
                    <label class="small text-muted d-block">Location</label>
                    <a href="#" id="metaLocation" target="_blank" class="small text-decoration-none"></a>
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
                        <span class="text-muted note needsclick">(This is just a demo dropzone. Selected files are actually uploaded.)</span>
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
<?= $this->renderSection('scripts') ?>
</body>
</html>

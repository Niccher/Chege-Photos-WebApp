<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Leaflet, MarkerCluster, and Heatmap Styles -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
/* Full-viewport map layout */
.map-page-wrapper {
    position: relative;
    height: calc(100vh - 80px);
    margin: -1.5rem -1.5rem -1.5rem -1.5rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

#travelMap {
    flex: 1;
    width: 100%;
    z-index: 1;
    background: #12161a;
}

/* Floating top bar */
.map-floating-bar {
    position: absolute;
    top: 1rem;
    left: 1rem;
    right: 1rem;
    z-index: 1000;
    pointer-events: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.map-control-card {
    pointer-events: auto;
    background: rgba(18, 22, 28, 0.88);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 0.5rem 0.85rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
}

.cluster-pill-scroll {
    pointer-events: auto;
    display: flex;
    gap: 0.4rem;
    overflow-x: auto;
    max-width: min(600px, 90vw);
    padding: 2px;
    scrollbar-width: thin;
}

.cluster-pill {
    background: rgba(30, 36, 44, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e2e8f0;
    font-size: 0.78rem;
    font-weight: 500;
    border-radius: 20px;
    padding: 0.25rem 0.75rem;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}
.cluster-pill:hover {
    background: rgba(13, 110, 253, 0.25);
    border-color: #0d6efd;
    color: #fff;
    transform: translateY(-1px);
}

/* Bottom photo drawer */
.map-photo-drawer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: rgba(18, 22, 28, 0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.45);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.map-photo-drawer.collapsed {
    transform: translateY(calc(100% - 44px));
}

.drawer-header {
    padding: 0.6rem 1.25rem;
    cursor: pointer;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.drawer-content {
    padding: 0.75rem 1.25rem 1rem 1.25rem;
    display: flex;
    gap: 0.85rem;
    overflow-x: auto;
    scroll-behavior: smooth;
    scrollbar-width: thin;
    max-height: 190px;
}

.drawer-photo-card {
    flex: 0 0 130px;
    width: 130px;
    cursor: pointer;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    transition: all 0.2s ease;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.drawer-photo-card:hover, .drawer-photo-card.highlighted {
    transform: translateY(-3px) scale(1.03);
    border-color: #0d6efd;
    box-shadow: 0 6px 18px rgba(13, 110, 253, 0.35);
    color: inherit;
}

.drawer-photo-card img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    background: #000;
}

.drawer-photo-info {
    padding: 0.35rem 0.5rem;
    font-size: 0.72rem;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* Marker Cluster overrides for dark theme */
.marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
    background-color: rgba(13, 110, 253, 0.35) !important;
}
.marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
    background-color: rgba(13, 110, 253, 0.9) !important;
    color: #fff !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

/* Leaflet Popup Styling */
.leaflet-popup-content-wrapper {
    background: #181d24 !important;
    color: #f1f5f9 !important;
    border-radius: 10px !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
    box-shadow: 0 12px 36px rgba(0,0,0,0.6) !important;
}
.leaflet-popup-tip {
    background: #181d24 !important;
}
.leaflet-container a.leaflet-popup-close-button {
    color: #94a3b8 !important;
    padding: 6px 6px 0 0 !important;
}
</style>

<div class="map-page-wrapper">
    <!-- ── Floating Top Control Bar ── -->
    <div class="map-floating-bar">
        <!-- Title & Stats Badge -->
        <div class="map-control-card d-flex align-items-center gap-2">
            <a href="<?= base_url('photos') ?>" class="text-secondary text-decoration-none me-1" title="Return to Library">
                <i class="bi bi-arrow-left fs-5"></i>
            </a>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill text-danger"></i>
                    <strong class="text-white small">Travel Map</strong>
                    <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 0.7rem;" id="badgePhotoCount">
                        <?= $geotaggedCount ?> geotagged
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Location Clusters -->
        <?php if (!empty($placeClusters)): ?>
            <div class="cluster-pill-scroll">
                <?php foreach ($placeClusters as $cluster): ?>
                    <button type="button" class="cluster-pill btn-zoom-cluster" 
                            data-lat="<?= $cluster['lat'] ?>" 
                            data-lng="<?= $cluster['lng'] ?>"
                            title="Zoom to location cluster">
                        <i class="bi bi-pin-map text-danger"></i>
                        <span><?= $cluster['count'] ?> photos</span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Layer and Mode Toggles -->
        <div class="map-control-card d-flex align-items-center gap-2">
            <!-- Markers vs Heatmap -->
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="toggleMarkers" title="Clustered Markers">
                    <i class="bi bi-pin-map me-1"></i>Pins
                </button>
                <button type="button" class="btn btn-outline-secondary" id="toggleHeatmap" title="Heatmap Density">
                    <i class="bi bi-fire me-1"></i>Heat
                </button>
            </div>

            <!-- Map Layer Switcher -->
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" id="btnLayerGoogleRoad" title="Google Maps Roadmap">
                    <i class="bi bi-google me-1"></i>Roadmap
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnLayerGoogleSat" title="Google Satellite Imagery">
                    <i class="bi bi-globe-americas me-1"></i>Satellite
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnLayerOsm" title="OpenStreetMap">
                    <i class="bi bi-map me-1"></i>OSM
                </button>
            </div>
        </div>
    </div>

    <!-- ── The Map Canvas ── -->
    <?php if (empty($mapPhotos)): ?>
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center p-4" style="background: #12161a;">
            <div class="rounded-circle bg-dark p-4 border border-secondary mb-3 shadow" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-geo-alt-slash text-warning fs-1"></i>
            </div>
            <h4 class="text-white fw-bold">No Geotagged Photos Found</h4>
            <p class="text-muted small mx-auto" style="max-width: 480px;">
                None of your photos currently have GPS coordinates. When you snap pictures with location tagging enabled on your smartphone, they will automatically appear on this interactive travel map!
            </p>
            <a href="<?= base_url('photos') ?>" class="btn btn-primary btn-sm px-3 mt-2">
                <i class="bi bi-images me-1"></i> Browse Library
            </a>
        </div>
    <?php else: ?>
        <div id="travelMap"></div>

        <!-- ── Dynamic Photo Drawer (Bottom) ── -->
        <div class="map-photo-drawer" id="photoDrawer">
            <div class="drawer-header" id="drawerToggle">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-images text-primary"></i>
                    <strong class="text-light small">Photos in Viewport</strong>
                    <span class="badge bg-secondary bg-opacity-50 text-white rounded-pill" id="viewportCount" style="font-size: 0.68rem;">0</span>
                </div>
                <div class="text-muted small d-flex align-items-center gap-1">
                    <span id="drawerStateLabel">Hide</span>
                    <i class="bi bi-chevron-down" id="drawerChevron"></i>
                </div>
            </div>
            <div class="drawer-content" id="drawerPhotos">
                <!-- Dynamically populated via JS as user pans/zooms map -->
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Leaflet & Plugin Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawPhotos = <?= json_encode($mapPhotos ?? []) ?>;
    const mapEl = document.getElementById('travelMap');
    if (!mapEl || !rawPhotos || rawPhotos.length === 0) return;

    // ── 1. Map & Tile Setup ──
    const googleRoadTile = L.tileLayer('https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps',
        maxZoom: 20
    });
    const googleSatTile = L.tileLayer('https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        subdomains: ['0', '1', '2', '3'],
        attribution: '&copy; Google Maps',
        maxZoom: 20
    });
    const osmTile = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    });

    let currentTileLayer = googleRoadTile;
    const map = L.map('travelMap', {
        center: [0, 0],
        zoom: 3,
        zoomControl: false,
        layers: [currentTileLayer]
    });

    L.control.zoom({ position: 'topright' }).addTo(map);

    // ── 2. Marker Clustering ──
    const markerGroup = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 45,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false
    });

    const heatPoints = [];
    const markerMap = new Map(); // photo.id -> Leaflet marker
    const bounds = L.latLngBounds();

    rawPhotos.forEach(p => {
        const pt = [p.lat, p.lng];
        bounds.extend(pt);
        heatPoints.push([p.lat, p.lng, 0.6]);

        const marker = L.marker(pt);
        const popupHtml = `
            <div style="width: 180px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="position: relative; border-radius: 6px; overflow: hidden; margin-bottom: 6px; aspect-ratio: 4/3; background: #000;">
                    <img src="${p.thumb_url}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                </div>
                <div class="text-truncate fw-semibold mb-1" style="font-size: 0.78rem;" title="${p.filename}">${p.filename}</div>
                ${p.taken_at ? `<div class="text-muted" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i>${p.taken_at}</div>` : ''}
                ${p.camera ? `<div class="text-muted text-truncate" style="font-size: 0.68rem;"><i class="bi bi-camera me-1"></i>${p.camera}</div>` : ''}
                ${p.ocr_text ? `<div class="badge bg-secondary bg-opacity-50 text-light mt-1 text-truncate" style="max-width: 100%; font-size: 0.65rem;"><i class="bi bi-file-text me-1"></i>${p.ocr_text}</div>` : ''}
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                    <a href="${p.photo_url}" target="_blank" class="btn btn-primary btn-sm py-0 px-2" style="font-size: 0.7rem;">View Full</a>
                </div>
            </div>
        `;
        marker.bindPopup(popupHtml);

        marker.on('click', function() {
            highlightDrawerCard(p.id);
        });

        markerGroup.addLayer(marker);
        markerMap.set(p.id, marker);
    });

    map.addLayer(markerGroup);
    map.fitBounds(bounds, { padding: [60, 60], maxZoom: 15 });

    // ── 3. Heatmap Layer ──
    const heatLayer = L.heatLayer(heatPoints, { radius: 28, blur: 18, maxZoom: 13 });

    // ── 4. Floating Bar Handlers ──
    $('#toggleMarkers').on('click', function() {
        $(this).addClass('active');
        $('#toggleHeatmap').removeClass('active');
        map.removeLayer(heatLayer);
        map.addLayer(markerGroup);
    });

    $('#toggleHeatmap').on('click', function() {
        $(this).addClass('active');
        $('#toggleMarkers').removeClass('active');
        map.removeLayer(markerGroup);
        map.addLayer(heatLayer);
    });

    function switchTileLayer(newLayer, activeBtnId) {
        if (currentTileLayer === newLayer) return;
        map.removeLayer(currentTileLayer);
        map.addLayer(newLayer);
        if (newLayer.bringToBack) {
            newLayer.bringToBack();
        }
        currentTileLayer = newLayer;

        $('#btnLayerGoogleRoad, #btnLayerGoogleSat, #btnLayerOsm').removeClass('active');
        $(activeBtnId).addClass('active');
    }

    $('#btnLayerGoogleRoad').on('click', function() {
        switchTileLayer(googleRoadTile, '#btnLayerGoogleRoad');
    });

    $('#btnLayerGoogleSat').on('click', function() {
        switchTileLayer(googleSatTile, '#btnLayerGoogleSat');
    });

    $('#btnLayerOsm').on('click', function() {
        switchTileLayer(osmTile, '#btnLayerOsm');
    });

    // Zoom to cluster pill
    $('.btn-zoom-cluster').on('click', function() {
        const lat = parseFloat($(this).data('lat'));
        const lng = parseFloat($(this).data('lng'));
        if (!isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], 13, { animate: true });
        }
    });

    // ── 5. Viewport Dynamic Photo Drawer ──
    const drawerEl = document.getElementById('photoDrawer');
    const drawerPhotosEl = document.getElementById('drawerPhotos');
    const viewportCountEl = document.getElementById('viewportCount');
    const drawerToggleBtn = document.getElementById('drawerToggle');
    const drawerChevron = document.getElementById('drawerChevron');
    const drawerStateLabel = document.getElementById('drawerStateLabel');

    let isDrawerCollapsed = false;
    drawerToggleBtn.addEventListener('click', function() {
        isDrawerCollapsed = !isDrawerCollapsed;
        drawerEl.classList.toggle('collapsed', isDrawerCollapsed);
        drawerChevron.classList.toggle('bi-chevron-up', isDrawerCollapsed);
        drawerChevron.classList.toggle('bi-chevron-down', !isDrawerCollapsed);
        drawerStateLabel.textContent = isDrawerCollapsed ? 'Show' : 'Hide';
    });

    function updateViewportPhotos() {
        if (!map) return;
        const curBounds = map.getBounds();
        const visiblePhotos = rawPhotos.filter(p => curBounds.contains([p.lat, p.lng]));

        viewportCountEl.textContent = visiblePhotos.length;

        if (visiblePhotos.length === 0) {
            drawerPhotosEl.innerHTML = `
                <div class="text-muted small py-2 d-flex align-items-center gap-2">
                    <i class="bi bi-search"></i> No photos in current map view. Pan or zoom out to see photos.
                </div>
            `;
            return;
        }

        let html = '';
        // Limit rendering to 40 items for smooth scroll performance
        const displayPhotos = visiblePhotos.slice(0, 40);
        displayPhotos.forEach(p => {
            html += `
                <div class="drawer-photo-card" data-photo-id="${p.id}">
                    <img src="${p.thumb_url}" alt="${p.filename}" loading="lazy">
                    <div class="drawer-photo-info">
                        <div class="text-truncate fw-medium text-light" style="font-size: 0.72rem;">${p.filename}</div>
                        ${p.taken_at ? `<div class="text-muted" style="font-size: 0.65rem;">${p.taken_at.split(' ')[0]}</div>` : ''}
                    </div>
                </div>
            `;
        });

        if (visiblePhotos.length > 40) {
            html += `
                <div class="d-flex align-items-center justify-content-center text-muted small px-3 text-center" style="min-width: 100px;">
                    +${visiblePhotos.length - 40} more in view
                </div>
            `;
        }

        drawerPhotosEl.innerHTML = html;

        // Add click events on cards to pan and open popup
        drawerPhotosEl.querySelectorAll('.drawer-photo-card').forEach(card => {
            card.addEventListener('click', function() {
                const photoId = parseInt(this.getAttribute('data-photo-id'), 10);
                const photo = rawPhotos.find(p => p.id === photoId);
                const marker = markerMap.get(photoId);
                if (photo && marker) {
                    map.setView([photo.lat, photo.lng], Math.max(map.getZoom(), 14), { animate: true });
                    markerGroup.zoomToShowLayer(marker, function() {
                        marker.openPopup();
                    });
                }
            });
        });
    }

    function highlightDrawerCard(photoId) {
        drawerPhotosEl.querySelectorAll('.drawer-photo-card').forEach(c => c.classList.remove('highlighted'));
        const target = drawerPhotosEl.querySelector(`.drawer-photo-card[data-photo-id="${photoId}"]`);
        if (target) {
            target.classList.add('highlighted');
            target.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }

    // Debounced update on map move
    let moveTimeout = null;
    map.on('moveend zoomend', function() {
        clearTimeout(moveTimeout);
        moveTimeout = setTimeout(updateViewportPhotos, 150);
    });

    // Initial load
    updateViewportPhotos();
});
</script>

<?= $this->endSection() ?>

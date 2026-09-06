<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
/* ── Explore Hub Styling ── */
.hub-title {
    font-size: 0.76rem;
    letter-spacing: 0.08em;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-muted, #888);
}

/* People Avatars */
.people-scroll {
    overflow-x: auto;
    scrollbar-width: thin;
    padding-bottom: 8px;
}
.person-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 84px;
    flex-shrink: 0;
    text-decoration: none;
    color: inherit;
    transition: transform 0.2s ease;
}
.person-chip:hover {
    transform: translateY(-3px);
    color: #1a73e8;
}
.person-avatar {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    background: #2a2a2a;
    transition: border-color 0.2s ease;
}
.person-chip:hover .person-avatar {
    border-color: #1a73e8;
}
.person-name {
    font-size: 0.76rem;
    font-weight: 600;
    margin-top: 6px;
    text-align: center;
    width: 100%;
}
.person-count {
    font-size: 0.65rem;
    color: var(--text-muted, #888);
}

/* Things & Scenes Cards */
.things-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
}
.thing-card {
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    height: 110px;
    text-decoration: none;
    color: #fff;
    background: #222;
    border: 1px solid rgba(255,255,255,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.thing-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.35);
    border-color: #1a73e8;
    color: #fff;
}
.thing-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.72);
    transition: transform 0.3s ease, filter 0.3s ease;
}
.thing-card:hover img {
    transform: scale(1.06);
    filter: brightness(0.85);
}
.thing-overlay {
    position: absolute;
    inset: 0;
    padding: 10px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.85) 100%);
    pointer-events: none;
}
.thing-name {
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.2;
}
.thing-count {
    font-size: 0.68rem;
    opacity: 0.8;
}

/* Media Types Pills */
.media-type-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 14px 16px;
    text-decoration: none;
    color: inherit;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background 0.2s ease, transform 0.2s ease;
}
.media-type-card:hover {
    background: rgba(255,255,255,0.08);
    transform: translateY(-2px);
    color: inherit;
}

/* Leaflet Map */
#map {
    height: 420px;
    width: 100%;
    border-radius: 14px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    border: 1px solid rgba(255,255,255,0.1);
}
.marker-cluster-small, .marker-cluster-medium, .marker-cluster-large {
    background-color: rgba(26, 115, 232, 0.4);
}
.marker-cluster-small div, .marker-cluster-medium div, .marker-cluster-large div {
    background-color: rgba(26, 115, 232, 0.9);
    color: #fff;
    font-weight: 700;
}
</style>

<!-- ── Explore Header & Search ── -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="h4 mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-compass text-info"></i> Explore Discovery Hub
        </h2>
        <p class="text-muted small mb-0">Search your visual world by people, places, AI categories, and media types</p>
    </div>

    <!-- Quick Search Input -->
    <form method="GET" action="<?= base_url('photos') ?>" class="d-flex align-items-center" style="max-width: 320px; width: 100%;">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-dark border-secondary text-light">
                <i class="bi bi-search"></i>
            </span>
            <input type="text" name="q" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Search by CLIP text (e.g. food, beach)..." value="<?= esc($searchQuery ?? '') ?>">
            <button class="btn btn-primary btn-sm px-3" type="submit">Search</button>
        </div>
    </form>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 1: PEOPLE & PETS (InsightFace Clusters)
     ══════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($topPersons)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="hub-title"><i class="bi bi-people me-1 text-primary"></i> People &amp; Pets</span>
            <a href="<?= base_url('faces') ?>" class="text-decoration-none small text-primary fw-semibold">
                View All Faces <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <div class="people-scroll d-flex gap-3">
            <?php foreach ($topPersons as $person): ?>
                <a href="<?= base_url('faces/person/' . $person['id']) ?>" class="person-chip">
                    <?php if (!empty($person['thumb_url'])): ?>
                        <img src="<?= esc($person['thumb_url']) ?>" alt="<?= esc($person['name'] ?? 'Person') ?>" class="person-avatar" loading="lazy">
                    <?php else: ?>
                        <div class="person-avatar d-flex align-items-center justify-content-center text-muted">
                            <i class="bi bi-person fs-3"></i>
                        </div>
                    <?php endif; ?>
                    <span class="person-name text-truncate"><?= esc(($person['name'] ?? null) ?: 'Unnamed') ?></span>
                    <span class="person-count"><?= (int) $person['face_count'] ?> photo<?= $person['face_count'] == 1 ? '' : 's' ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 2: PLACES & MAP
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 px-1">
        <div>
            <span class="hub-title"><i class="bi bi-geo-alt me-1 text-danger"></i> Places &amp; Map</span>
            <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary ms-2" style="font-size: 0.72rem;">
                <?= count($locations) ?> geotagged of <?= esc($totalPhotos) ?> total
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= base_url('map') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrows-fullscreen me-1"></i>Open Travel Map
            </a>
            <?php if (!empty($locations)): ?>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" id="btnMarkers"><i class="bi bi-pin-map me-1"></i>Clustered</button>
                    <button type="button" class="btn btn-outline-secondary" id="btnHeatmap"><i class="bi bi-fire me-1"></i>Heatmap</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Location Clusters Pills -->
    <?php if (!empty($placeClusters)): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($placeClusters as $cluster): ?>
                <button type="button" class="btn btn-dark btn-sm rounded-pill border border-secondary px-3 py-1 btn-zoom-place" 
                        data-lat="<?= $cluster['lat'] ?>" data-lng="<?= $cluster['lng'] ?>">
                    <i class="bi bi-geo-fill text-danger me-1"></i>
                    Cluster (<?= $cluster['count'] ?> photos)
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($locations)): ?>
        <div class="alert alert-dark border-secondary p-4 rounded-3 text-center mb-0 shadow-sm" style="background: rgba(255,255,255,0.03);">
            <i class="bi bi-geo-alt-slash text-warning" style="font-size: 2rem;"></i>
            <h6 class="fw-bold mt-2">No Geotagged Photos Found</h6>
            <p class="text-muted small mb-0 mx-auto" style="max-width: 520px;">
                None of your photos currently contain embedded GPS coordinates. Turn on location tagging in your camera app to view your photo journey on this map!
            </p>
        </div>
    <?php else: ?>
        <div id="map"></div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 3: THINGS & SCENES (YOLO & CLIP Categorization)
     ══════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($thingsCategories)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="hub-title"><i class="bi bi-tags me-1 text-warning"></i> Things &amp; Scenes (AI Vision)</span>
            <span class="text-muted small"><?= count($thingsCategories) ?> categories</span>
        </div>
        <div class="things-grid">
            <?php foreach ($thingsCategories as $cat): ?>
                <a href="<?= base_url('photos?q=' . urlencode($cat['tag'])) ?>" class="thing-card">
                    <img src="<?= esc($cat['cover_url']) ?>" alt="<?= esc($cat['name']) ?>" loading="lazy">
                    <div class="thing-overlay">
                        <span class="thing-name"><?= esc($cat['name']) ?></span>
                        <span class="thing-count"><?= $cat['count'] ?> photo<?= $cat['count'] == 1 ? '' : 's' ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION: EXPRESSIONS & EMOTIONS (Facial Emotion Classification)
     ══════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($emotionCategories)): ?>
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3 px-1">
            <span class="hub-title"><i class="bi bi-emoji-smile me-1 text-info"></i> Expressions &amp; Emotions</span>
            <span class="text-muted small"><?= count($emotionCategories) ?> expressions</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($emotionCategories as $em): ?>
                <a href="<?= base_url('photos?q=' . urlencode($em['query'] ?: $em['emotion'])) ?>" 
                   class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 d-inline-flex align-items-center gap-2 border-opacity-50"
                   style="transition: all 0.2s;">
                    <span class="fw-semibold"><?= esc($em['emotion']) ?></span>
                    <span class="badge bg-secondary bg-opacity-50 rounded-pill"><?= $em['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION 4: MEDIA TYPES & FORMATS
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="mb-4">
    <span class="hub-title d-block mb-3 px-1"><i class="bi bi-collection me-1 text-success"></i> Media Types</span>
    <div class="row g-3">
        <?php foreach ($mediaTypes as $key => $mt): ?>
            <div class="col-6 col-md-3">
                <a href="<?= $key === 'favorites' ? base_url('favorites') : base_url('photos?sort=upload_desc&filter=' . $mt['filter']) ?>" class="media-type-card">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= $mt['icon'] ?> fs-5" style="color: <?= $mt['color'] ?>;"></i>
                        <span class="fw-semibold small"><?= esc($mt['name']) ?></span>
                    </div>
                    <span class="badge rounded-pill bg-dark border border-secondary text-light"><?= (int) $mt['count'] ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Leaflet & Map Script ── -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locations = <?= json_encode($locations ?? []) ?>;
    const mapEl = document.getElementById('map');
    if (!mapEl || locations.length === 0) return;

    const map = L.map('map').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const markerGroup = L.markerClusterGroup({
        chunkedLoading: true,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false
    }).addTo(map);

    const heatPoints = [];
    let bounds = L.latLngBounds();

    locations.forEach(loc => {
        const lat = parseFloat(loc.latitude);
        const lng = parseFloat(loc.longitude);
        const point = [lat, lng];
        
        heatPoints.push([lat, lng, 0.5]);
        bounds.extend(point);

        const thumbUrl = loc.thumbnail_path ? `<?= base_url() ?>${loc.thumbnail_path}` : `<?= base_url() ?>${loc.path}`;
        const marker = L.marker(point);
        marker.bindPopup(`
            <div style="width: 160px; font-family: var(--bs-body-font-family);">
                <img src="${thumbUrl}" style="width: 100%; border-radius: 6px; margin-bottom: 8px; border: 1px solid rgba(255,255,255,0.1);">
                <strong class="text-truncate d-block" style="font-size: 0.8rem;">${loc.filename}</strong>
                <small class="text-muted"><i class="bi bi-calendar"></i> ${loc.taken_at || 'Unknown'}</small>
            </div>
        `);
        markerGroup.addLayer(marker);
    });

    if (locations.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }

    const heatLayer = L.heatLayer(heatPoints, {radius: 25, blur: 15});

    $('#btnMarkers').on('click', function() {
        $(this).addClass('active');
        $('#btnHeatmap').removeClass('active');
        map.removeLayer(heatLayer);
        map.addLayer(markerGroup);
    });

    $('#btnHeatmap').on('click', function() {
        $(this).addClass('active');
        $('#btnMarkers').removeClass('active');
        map.removeLayer(markerGroup);
        map.addLayer(heatLayer);
    });

    // Zoom to cluster when button is clicked
    document.querySelectorAll('.btn-zoom-place').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));
            if (!isNaN(lat) && !isNaN(lng)) {
                map.setView([lat, lng], 13, { animate: true });
            }
        });
    });
});
</script>

<?= $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #map {
        height: calc(100vh - 120px);
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h2 class="h4 mb-0">Explore</h2>
        <p class="text-muted small mb-0">Discover your photos on a map</p>
    </div>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-outline-secondary active" id="btnMarkers">Markers</button>
        <button type="button" class="btn btn-outline-secondary" id="btnHeatmap">Heatmap</button>
    </div>
</div>

<div id="map"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const locations = <?= json_encode($locations) ?>;
        
        const map = L.map('map').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const markerGroup = L.layerGroup().addTo(map);
        const heatPoints = [];
        let bounds = L.latLngBounds();

        locations.forEach(loc => {
            const lat = parseFloat(loc.latitude);
            const lng = parseFloat(loc.longitude);
            const point = [lat, lng];
            
            heatPoints.push([lat, lng, 0.5]); // intensity 0.5
            bounds.extend(point);

            let exifStr = '';
            function rationalToFloat(v) {
                if (typeof v === 'number') return v;
                if (typeof v !== 'string') return null;
                const parts = v.split('/');
                if (parts.length === 2) {
                    const n = parseFloat(parts[0]), d = parseFloat(parts[1]);
                    return d ? n / d : null;
                }
                return parseFloat(v) || null;
            }
            if (loc.exif_data) {
                try {
                    const exif = JSON.parse(loc.exif_data);
                    if (exif.Make) exifStr += `<br><small class="text-muted"><i class="bi bi-camera"></i> ${exif.Make} ${exif.Model || ''}</small>`;
                    let settings = [];
                    if (exif.FNumber) {
                        const f = rationalToFloat(exif.FNumber);
                        if (f) settings.push('f/' + f.toFixed(1));
                    }
                    if (exif.ExposureTime) {
                        const exp = rationalToFloat(exif.ExposureTime);
                        if (exp) settings.push(exp + 's');
                    }
                    if (exif.ISOSpeedRatings) {
                        settings.push('ISO ' + exif.ISOSpeedRatings);
                    }
                    if (settings.length > 0) {
                        exifStr += `<br><small class="text-muted"><i class="bi bi-sliders"></i> ${settings.join(' • ')}</small>`;
                    }
                } catch(e) {}
            }

            const marker = L.marker(point);
            marker.bindPopup(`
                <div style="width: 150px; font-family: var(--bs-body-font-family);">
                    <img src="<?= base_url() ?>${loc.thumbnail_path}" style="width: 100%; border-radius: 4px; margin-bottom: 8px; border: 1px solid var(--border-color);">
                    <strong class="text-truncate d-block">${loc.filename}</strong>
                    <small class="text-muted"><i class="bi bi-calendar"></i> ${loc.taken_at}</small>
                    ${exifStr}
                </div>
            `);
            markerGroup.addLayer(marker);
        });

        if (locations.length > 0) {
            map.fitBounds(bounds, { padding: [50, 50] });
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
    });
</script>
<?= $this->endSection() ?>

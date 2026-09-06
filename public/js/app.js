function showToast(message, type = 'dark') {
    const el = document.getElementById('liveToast');
    if (!el) return;
    const toast = bootstrap.Toast.getOrCreateInstance(el);
    const $toast = $('#liveToast');
    $toast.removeClass('bg-dark bg-success bg-danger bg-warning').addClass('bg-' + type);
    $('#toastMessage').text(message);
    const icons = {
        'dark': 'bi-info-circle',
        'success': 'bi-check-circle',
        'danger': 'bi-exclamation-circle',
        'warning': 'bi-exclamation-triangle'
    };
    $('#toastIcon').attr('class', 'bi ' + (icons[type] || icons['dark']) + ' me-2');
    toast.show();
}

$(document).ready(function () {
    // --- Immediate Theme Initialization & Event Delegation ---
    const savedTheme = localStorage.getItem('theme') || 'auto';
    if (typeof window.setAppTheme === 'function') {
        window.setAppTheme(savedTheme);
    }

    $(document).on('click', '.theme-opt', function(e) {
        e.preventDefault();
        const newTheme = $(this).data('theme');
        if (typeof window.setAppTheme === 'function') {
            window.setAppTheme(newTheme);
        }
    });

    const $loading = $('#loadingOverlay');

    // Sidebar Toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebarMenu').toggleClass('active');
    });

    // Lightbox Logic
    const $lightboxModal = document.getElementById('lightboxModal') ? new bootstrap.Modal('#lightboxModal') : null;
    const $lightboxImageContainer = $('#lightboxImageContainer');
    let currentPhotoId = null;
    let isSelectMode = false;
    let selectedIds = new Set();
    let currentPage = 1;
    let isFetching = false;
    let hasMore = true;
    let lastDateGroup = $('.timeline-header').last().text().trim() || '';

    // Initialize Intersection Observer for Infinite Scroll
    const sentinel = document.getElementById('infiniteScrollSentinel');
    if (sentinel) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !isFetching && hasMore) {
                loadMorePhotos();
            }
        }, { threshold: 0.1 });
        observer.observe(sentinel);
    }

    function loadMorePhotos() {
        isFetching = true;
        currentPage++;
        $('#infiniteScrollSentinel .spinner-border').removeClass('d-none');

        const urlParams = new URLSearchParams(window.location.search);
        const currentSort = urlParams.get('sort') || localStorage.getItem('gallery_sort') || 'date_desc';
        const q = $('#searchInput').val();
        let queryParams = '?page=' + currentPage;
        if (q) queryParams += '&q=' + encodeURIComponent(q);
        if (currentSort) queryParams += '&sort=' + encodeURIComponent(currentSort);

        $.ajax({
            url: window.location.pathname + queryParams,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res.photos && res.photos.length > 0) {
                    appendPhotos(res.photos);
                    hasMore = res.hasMore;
                } else {
                    hasMore = false;
                }
            },
            complete: function () {
                isFetching = false;
                $('#infiniteScrollSentinel .spinner-border').addClass('d-none');
                if (!hasMore) {
                    $('#infiniteScrollSentinel').html('<p class="text-muted small mt-4">You have reached the end of your collection.</p>');
                }
                // Refresh $allPhotos for lightbox
                $allPhotos = $('.photo-item');
                initTimelineScrubbar(); // Refresh scrubbar markers
            }
        });
    }

    function appendPhotos(photos) {
        const baseUrl = $('base').attr('href') || window.location.origin + '/';
        const isBadgesEnabled = localStorage.getItem('gallery_meta_badges') === 'true';
        
        photos.forEach(photo => {
            const groupHeader = photo.groupHeader || (new Date(photo.taken_at).toLocaleString('en-US', { month: 'long', year: 'numeric' }));
            
            if (groupHeader !== lastDateGroup) {
                lastDateGroup = groupHeader;
                const headerHtml = `
                    <div class="d-flex align-items-center gap-3 mb-3 mt-5 px-2">
                        <h5 class="mb-0 fw-bold opacity-75 timeline-header" style="color: var(--text-primary);">${groupHeader}</h5>
                        <div class="flex-grow-1 border-bottom border-secondary opacity-25" style="border-color: var(--border-color) !important;"></div>
                    </div>
                    <div class="photo-grid"></div>`;
                $('#infiniteScrollSentinel').before(headerHtml);
            }
            
            const $targetGrid = $('.photo-grid').last();
            const isFav = photo.is_favorite ? '1' : '0';
            const exifB64 = exifToBase64(photo.exif_data || '');
            const locationStr = (photo.latitude && photo.longitude &&
                                 parseFloat(photo.latitude) !== 0 && parseFloat(photo.longitude) !== 0)
                                 ? photo.latitude + ',' + photo.longitude : '';
            const mpStr = (photo.width && photo.height) ? (Math.round((photo.width * photo.height) / 100000) / 10) + ' MP' : 'Video';
            const sizeMb = (photo.size / 1024 / 1024).toFixed(1) + ' MB';
            const hasGpsIcon = locationStr ? '<i class="bi bi-geo-alt-fill text-info" title="Geotagged"></i>' : '';
            const badgeClass = isBadgesEnabled ? 'd-flex' : 'd-none';

            const photoHtml = `
                <div class="photo-item" 
                     draggable="true"
                     data-id="${photo.id}" 
                     data-full="${baseUrl + photo.path}"
                     data-filename="${photo.filename}"
                     data-size="${(photo.size / 1024 / 1024).toFixed(2)} MB"
                     data-dimensions="${photo.width ? photo.width + ' x ' + photo.height : 'Video'}"
                     data-date="${new Date(photo.taken_at).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}"
                     data-favorite="${isFav}"
                     data-type="${photo.mime_type.startsWith('video/') ? 'video' : 'image'}"
                     data-exif-b64="${exifB64}"
                     data-location="${locationStr}">
                    <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                        <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                            <i class="bi bi-check-lg d-none"></i>
                        </div>
                    </div>
                    ${photo.is_favorite == '1' ? '<div class="position-absolute top-0 start-0 p-2" style="z-index: 5;"><i class="bi bi-heart-fill text-danger shadow-sm"></i></div>' : ''}
                    ${photo.mime_type.startsWith('video/') 
                        ? `<video src="${baseUrl + photo.path}" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                           <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>`
                        : `<img src="${baseUrl + photo.thumbnail_path}" alt="${photo.filename}" loading="lazy">`
                    }
                    <div class="photo-meta-badge position-absolute bottom-0 start-0 end-0 p-1 px-2 ${badgeClass} justify-content-between align-items-center text-white small" style="background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%); font-size: 0.72rem; z-index: 4; pointer-events: none;">
                        <span class="text-truncate me-1 font-monospace">${mpStr} • ${sizeMb}</span>
                        ${hasGpsIcon}
                    </div>
                </div>`;
            $targetGrid.append(photoHtml);
        });
    }

    // --- Gallery Sort & Display Toggles ---
    $(document).on('click', '.sort-opt', function (e) {
        e.preventDefault();
        const selectedSort = $(this).data('sort');
        localStorage.setItem('gallery_sort', selectedSort);
        const url = new URL(window.location.href);
        url.searchParams.set('sort', selectedSort);
        url.searchParams.delete('page');
        window.location.href = url.href;
    });

    function applyMetaBadges(enabled) {
        if (enabled) {
            $('.photo-meta-badge').removeClass('d-none').addClass('d-flex');
            $('#btnToggleMetaBadges').addClass('btn-primary active text-white').removeClass('btn-outline-secondary');
        } else {
            $('.photo-meta-badge').addClass('d-none').removeClass('d-flex');
            $('#btnToggleMetaBadges').removeClass('btn-primary active text-white').addClass('btn-outline-secondary');
        }
        localStorage.setItem('gallery_meta_badges', enabled ? 'true' : 'false');
    }

    $(document).on('click', '#btnToggleMetaBadges', function () {
        const currentlyEnabled = localStorage.getItem('gallery_meta_badges') === 'true';
        applyMetaBadges(!currentlyEnabled);
    });

    // Initialize badges state on load
    if (localStorage.getItem('gallery_meta_badges') === 'true') {
        applyMetaBadges(true);
    }

    // Grid Density Switcher
    $(document).on('click', '#btnGridStandard', function () {
        $('#btnGridStandard').addClass('active');
        $('#btnGridCompact').removeClass('active');
        $('.photo-grid').css('grid-template-columns', '');
        localStorage.setItem('gallery_density', 'standard');
    });

    $(document).on('click', '#btnGridCompact', function () {
        $('#btnGridCompact').addClass('active');
        $('#btnGridStandard').removeClass('active');
        $('.photo-grid').css('grid-template-columns', 'repeat(auto-fill, minmax(130px, 1fr))');
        localStorage.setItem('gallery_density', 'compact');
    });

    if (localStorage.getItem('gallery_density') === 'compact') {
        $('#btnGridCompact').click();
    }

    // ── EXIF helpers — defined at module level so they're always available ──────
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

    function formatExposure(val) {
        const f = rationalToFloat(val);
        if (!f) return val;
        return f >= 1 ? f.toFixed(1) + 's' : '1/' + Math.round(1 / f) + 's';
    }

    // Safe UTF-8 base64 encoder for EXIF (used by appendPhotos for infinite scroll)
    function exifToBase64(jsonStr) {
        if (!jsonStr) return '';
        try {
            const bytes = new TextEncoder().encode(jsonStr);
            let binary = '';
            bytes.forEach(b => binary += String.fromCharCode(b));
            return btoa(binary);
        } catch(e) { return ''; }
    }

    // Corrected $loading variable initialization
    // const $loading = $('.loading-overlay'); // This line was moved from the top, but the original $loading was for #loadingOverlay. Reverting to original.
    let currentIndex = -1;
    let $allPhotos = $('.photo-item'); // This was moved from inside openPhoto, ensuring it's available globally.

    function openPhoto(index) {
        if (index < 0 || index >= $allPhotos.length) return;

        currentIndex = index;
        $('#lightboxCounter').text((currentIndex + 1) + ' / ' + $allPhotos.length);
        const $this = $allPhotos.eq(index);
        const fullUrl = $this.data('full');
        const dataType = $this.data('type') || 'image';
        currentPhotoId = $this.data('id');
        const context = window.location.pathname.split('/').pop() || 'index';

        $('#shareLinkPopup').addClass('d-none');
        $('#linkPasswordInput').val('');

        console.log('Opening photo index:', currentIndex, 'ID:', currentPhotoId);

        if (context === 'trash') {
            $('#btnRestore').show();
            $('#btnArchive').hide();
            $('#btnDelete').attr('title', 'Delete Permanently');
        } else {
            $('#btnRestore').hide();
            $('#btnArchive').show();
            $('#btnArchive i').attr('class', context === 'archive' ? 'bi bi-archive-fill fs-5' : 'bi bi-archive fs-5');
        }

        // ── Basic file info ──────────────────────────────────────────────────────
        $('#metaFilename').text($this[0].getAttribute('data-filename') || 'Unknown');
        $('#metaDate').text($this[0].getAttribute('data-date') || '-');
        $('#metaSize').text($this[0].getAttribute('data-size') || '-');
        $('#metaDimensions').text($this[0].getAttribute('data-dimensions') || '-');

        // Favorite button icon
        const isFavorite = $this[0].getAttribute('data-favorite') === '1';
        $('#btnFavorite i').attr('class', isFavorite ? 'bi bi-heart-fill text-danger fs-5' : 'bi bi-heart fs-5');

        // ── Decode EXIF ──────────────────────────────────────────────────────────
        const exifB64 = $this[0].getAttribute('data-exif-b64') || '';
        let photoExif = null;
        if (exifB64) {
            try {
                const binStr = atob(exifB64);
                const byteArr = new Uint8Array(binStr.length);
                for (let i = 0; i < binStr.length; i++) byteArr[i] = binStr.charCodeAt(i);
                photoExif = JSON.parse(new TextDecoder('utf-8').decode(byteArr));
            } catch (e) {
                console.warn('[EXIF decode error]', e.message);
            }
        }

        // ── Always reset every metadata row first ────────────────────────────────
        const exifRowIds = ['#metaCameraRow','#metaShutterRow','#metaApertureRow',
                            '#metaIsoRow','#metaFocalRow','#metaFlashRow',
                            '#metaWbRow','#metaMeteringRow','#metaAltitudeRow'];
        exifRowIds.forEach(id => {
            const el = document.querySelector(id);
            if (el) {
                el.style.display = 'none';
                const val = el.querySelector('[id]');
                if (val) val.textContent = '';
            }
        });
        document.getElementById('metaExifContainer').style.display = 'none';

        // Always clear and hide location too
        const locContainer = document.getElementById('metaLocationContainer');
        const locLink      = document.getElementById('metaLocation');
        locContainer.style.display = 'none';
        locLink.textContent = '';
        locLink.removeAttribute('href');

        // ── Populate EXIF ────────────────────────────────────────────────────────
        if (photoExif && typeof photoExif === 'object') {
            let anyExif = false;

            function setRow(rowId, valueId, text) {
                if (!text && text !== 0) return;
                document.getElementById(valueId).textContent = text;
                document.getElementById(rowId).style.display = 'flex';
                anyExif = true;
            }

            // Camera make + model
            if (photoExif.Make || photoExif.Model) {
                setRow('metaCameraRow', 'metaCameraModel',
                    [photoExif.Make, photoExif.Model].filter(Boolean).join(' '));
            }

            // Shutter speed
            if (photoExif.ExposureTime) {
                setRow('metaShutterRow', 'metaShutter', formatExposure(photoExif.ExposureTime));
            }

            // Aperture — FNumber rational first, fallback ApertureFNumber string
            const fNum = rationalToFloat(photoExif.FNumber);
            if (fNum && fNum > 0) {
                setRow('metaApertureRow', 'metaAperture', 'f/' + fNum.toFixed(1));
            } else if (photoExif.ApertureFNumber) {
                setRow('metaApertureRow', 'metaAperture', photoExif.ApertureFNumber);
            }

            // ISO
            if (photoExif.ISOSpeedRatings) {
                setRow('metaIsoRow', 'metaIso', photoExif.ISOSpeedRatings);
            }

            // Focal length
            const focal = rationalToFloat(photoExif.FocalLength);
            if (focal && focal > 0) {
                let focalText = focal.toFixed(1) + ' mm';
                if (photoExif.FocalLengthIn35mmFilm > 0) {
                    focalText += '  (' + photoExif.FocalLengthIn35mmFilm + ' mm equiv.)';
                }
                setRow('metaFocalRow', 'metaFocal', focalText);
            }

            // Flash
            if (photoExif.Flash != null) {
                setRow('metaFlashRow', 'metaFlash',
                    (parseInt(photoExif.Flash) & 0x1) ? 'Fired' : 'Did not fire');
            }

            // White Balance
            if (photoExif.WhiteBalance != null) {
                const wb = { 0: 'Auto', 1: 'Manual' };
                setRow('metaWbRow', 'metaWb', wb[photoExif.WhiteBalance] ?? 'Unknown');
            }

            // Metering Mode
            if (photoExif.MeteringMode != null) {
                const mm = { 0:'Unknown', 1:'Average', 2:'Center-Weighted',
                             3:'Spot', 4:'Multi-Spot', 5:'Multi-Segment', 6:'Partial', 255:'Other' };
                setRow('metaMeteringRow', 'metaMetering', mm[photoExif.MeteringMode] ?? ('Mode ' + photoExif.MeteringMode));
            }

            // GPS Altitude
            const alt = rationalToFloat(photoExif.GPSAltitude);
            if (alt && alt > 0) {
                const below = (photoExif.GPSAltitudeRef && photoExif.GPSAltitudeRef.charCodeAt(0) === 1)
                              ? ' below sea level' : ' m';
                setRow('metaAltitudeRow', 'metaAltitude', Math.round(alt) + below);
            }

            if (anyExif) document.getElementById('metaExifContainer').style.display = 'block';
        }

        // ── Populate Location ─────────────────────────────────────────────────────
        const rawLocation = $this[0].getAttribute('data-location') || '';
        if (rawLocation) {
            const parts = rawLocation.split(',');
            if (parts.length === 2) {
                const lat = parseFloat(parts[0]), lng = parseFloat(parts[1]);
                // Only show if coords are non-zero (0,0 is invalid / default)
                if (lat !== 0 || lng !== 0) {
                    locLink.href = `https://www.google.com/maps?q=${lat},${lng}`;
                    locLink.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    locContainer.style.display = 'flex';
                }
            }
        }

        $lightboxImageContainer.empty();
        if (dataType === 'video') {
            $lightboxImageContainer.append(`<video src="${fullUrl}" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;" controls autoplay></video>`);
        } else {
            $lightboxImageContainer.append(`<img src="${fullUrl}" id="lightboxImage" class="img-fluid" style="max-height: 100%; max-width: 100%; object-fit: contain;">`);
        }

        // Show/hide nav arrows based on position
        $('#btnPrevPhoto').toggle(currentIndex > 0);
        $('#btnNextPhoto').toggle(currentIndex < $allPhotos.length - 1);

        $lightboxModal.show();
    }

    $(document).on('click', '.photo-item', function () {
        // Refresh photo list in case of dynamic changes (AJAX/Masonry)
        $allPhotos = $('.photo-item');
        if (!isSelectMode) { // Only open photo if not in select mode
            openPhoto($allPhotos.index(this));
        }
    });

    $('#btnPrevPhoto').on('click', function (e) {
        e.stopPropagation();
        openPhoto(currentIndex - 1);
    });

    $('#btnNextPhoto').on('click', function (e) {
        e.stopPropagation();
        openPhoto(currentIndex + 1);
    });

    // Keyboard Navigation
    $(document).on('keydown', function (e) {
        if ($('#lightboxModal').is(':visible')) {
            if (e.key === 'ArrowLeft') $('#btnPrevPhoto:visible').click();
            if (e.key === 'ArrowRight') $('#btnNextPhoto:visible').click();
            if (e.key === 'Escape') $lightboxModal.hide();
        }
    });

    // --- Slideshow Logic ---
    let slideshowInterval = null;
    let slideshowSpeed = 5000; // 5 seconds
    let slideshowProgress = 0;

    function startSlideshow() {
        if (slideshowInterval) return;
        
        $('#btnSlideshow i').attr('class', 'bi bi-pause-fill fs-5 text-primary');
        $('#slideshowProgress').removeClass('d-none');
        resetSlideshowProgress();

        slideshowInterval = setInterval(() => {
            if (currentIndex < $allPhotos.length - 1) {
                openPhoto(currentIndex + 1);
                resetSlideshowProgress();
            } else {
                stopSlideshow();
                showToast('Slideshow finished', 'dark');
            }
        }, slideshowSpeed);
    }

    function stopSlideshow() {
        clearInterval(slideshowInterval);
        slideshowInterval = null;
        $('#btnSlideshow i').attr('class', 'bi bi-play-fill fs-5');
        $('#slideshowProgress').addClass('d-none');
        clearInterval(progressTimer);
    }

    let progressTimer = null;
    function resetSlideshowProgress() {
        slideshowProgress = 0;
        clearInterval(progressTimer);
        $('#slideshowProgress .progress-bar').css('width', '0%');
        
        const step = 100 / (slideshowSpeed / 100);
        progressTimer = setInterval(() => {
            slideshowProgress += step;
            $('#slideshowProgress .progress-bar').css('width', slideshowProgress + '%');
            if (slideshowProgress >= 100) clearInterval(progressTimer);
        }, 100);
    }

    $('#btnSlideshow').on('click', function () {
        if (slideshowInterval) stopSlideshow();
        else startSlideshow();
    });

    // Stop slideshow on manual navigation or modal close
    $('#btnPrevPhoto, #btnNextPhoto').on('click', function () {
        if (slideshowInterval) stopSlideshow();
    });

    document.getElementById('lightboxModal').addEventListener('hidden.bs.modal', event => {
        stopSlideshow();
        $lightboxImageContainer.find('video').each(function () {
            this.pause();
        });
        currentIndex = -1;
        $('#shareLinkPopup').addClass('d-none');
        $('#metadataPanel').addClass('d-none');
        $('#similarPhotosPanel').addClass('d-none');
    });

    $('#btnInfo').on('click', function () {
        $('#similarPhotosPanel').addClass('d-none');
        $('#metadataPanel').toggleClass('d-none');
    });

    $('#btnCloseMetadata').on('click', function () {
        $('#metadataPanel').addClass('d-none');
    });

    // ── Visual Similarity Search ("Find Similar Photos") ─────
    $('#btnFindSimilar').on('click', function () {
        $('#metadataPanel').addClass('d-none');
        $('#similarPhotosPanel').toggleClass('d-none');
        if (!$('#similarPhotosPanel').hasClass('d-none') && currentPhotoId) {
            loadSimilarPhotos(currentPhotoId);
        }
    });

    $('#btnCloseSimilar').on('click', function () {
        $('#similarPhotosPanel').addClass('d-none');
    });

    function loadSimilarPhotos(photoId) {
        const container = $('#similarPhotosContent');
        container.html(`
            <div class="text-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm text-primary mb-2" role="status"></span>
                <div class="small">Finding visually similar photos...</div>
            </div>
        `);

        $.getJSON(BASE_URL + 'photos/' + photoId + '/similar', function (res) {
            if (res.status === 'success' && res.photos && res.photos.length > 0) {
                let html = '<div class="row g-2">';
                res.photos.forEach(function (p) {
                    const matchScore = p.similarity_pct !== undefined ? p.similarity_pct : (p.similarity ? Math.round(p.similarity * 100) : (p.score ? Math.round(p.score * 100) : 0));
                    const thumbUrl = p.thumbnail_url || (p.thumbnail_path ? (p.thumbnail_path.startsWith('http') ? p.thumbnail_path : BASE_URL + p.thumbnail_path) : (p.url || (p.path ? (p.path.startsWith('http') ? p.path : BASE_URL + p.path) : '')));
                    const photoPath = p.path || p.url || '';
                    html += `
                        <div class="col-6">
                            <div class="card bg-dark border-0 rounded overflow-hidden similar-photo-card position-relative" style="cursor: pointer;" data-id="${p.id}" data-path="${photoPath}">
                                <div class="ratio ratio-1x1 bg-black">
                                    <img src="${thumbUrl}" class="object-fit-cover w-100 h-100" alt="${p.filename || 'Photo'}" loading="lazy">
                                </div>
                                <span class="position-absolute top-0 end-0 m-1 badge bg-primary bg-opacity-75 text-white" style="font-size: 0.65rem;">
                                    ${matchScore}% match
                                </span>
                                <div class="p-1.5 text-truncate" style="font-size: 0.72rem; color: #ccc;" title="${p.filename || ''}">
                                    ${p.filename || 'Photo #' + p.id}
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.html(html);
            } else {
                container.html(`
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-images fs-1 mb-2 d-block opacity-25"></i>
                        <p class="small mb-1">No visually similar photos found.</p>
                        <p class="extra-small text-secondary mb-0">Ensure CLIP semantic vectors are scanned for your library.</p>
                    </div>
                `);
            }
        }).fail(function () {
            container.html(`
                <div class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle fs-2 mb-2 d-block"></i>
                    <span class="small">Unable to query similarity search.</span>
                </div>
            `);
        });
    }

    // Clicking a similar photo loads it into the viewer
    $(document).on('click', '.similar-photo-card', function () {
        const clickedId = $(this).data('id');
        let foundIdx = -1;
        $allPhotos.each(function (idx) {
            if ($(this).data('id') == clickedId) {
                foundIdx = idx;
                return false;
            }
        });

        if (foundIdx >= 0) {
            openPhoto(foundIdx);
        } else {
            window.location.href = BASE_URL + 'photos/' + clickedId;
        }
    });

    // ── Save AI Preset Collection as Permanent Smart Album ────
    $(document).on('click', '#btnSaveAiAsAlbum', function () {
        const btn = $(this).prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
        $.post(BASE_URL + 'albums/create', {
            album_type: 'smart',
            name: btn.data('name'),
            description: btn.data('description'),
            ai_tags: btn.data('tags')
        }, function (res) {
            if (res.status === 'success') {
                showToast('Saved to My Albums!', 'success');
                setTimeout(function () {
                    window.location.href = BASE_URL + 'albums/' + res.id;
                }, 800);
            } else {
                showToast('Failed: ' + (res.message || 'Error'), 'danger');
                btn.prop('disabled', false).html('<i class="bi bi-plus-circle me-1"></i> Save as Smart Album');
            }
        }, 'json').fail(function () {
            btn.prop('disabled', false).html('<i class="bi bi-plus-circle me-1"></i> Save as Smart Album');
            showToast('Network error saving album', 'danger');
        });
    });

    // Public Sharing Link
    function updateSharePopupUi(res) {
        if (res.status === 'success') {
            $('#sharedUrlText').text(res.url);
            if (res.has_password) {
                $('#linkPasswordStatus').removeClass('d-none').text('🔒 Password protected');
                $('#btnRemovePassword').removeClass('d-none');
            } else {
                $('#linkPasswordStatus').addClass('d-none');
                $('#btnRemovePassword').addClass('d-none');
            }
            $('#linkPasswordInput').val('');
        }
    }

    $('#btnShareLink').on('click', function () {
        if (!currentPhotoId) return;
        const $popup = $('#shareLinkPopup');
        if (!$popup.hasClass('d-none')) {
            $popup.addClass('d-none');
            return;
        }
        $('#linkPasswordInput').val('');
        $.post(BASE_URL + 'photos/generate-link/' + currentPhotoId, {}, function (res) {
            updateSharePopupUi(res);
            $popup.removeClass('d-none').hide().fadeIn(200);
        });
    });

    $('#btnApplyExpiry').on('click', function () {
        if (!currentPhotoId) return;
        const expiresPreset = $('#linkExpiryPreset').val() || '';
        const password = $('#linkPasswordInput').val() || '';
        $.post(BASE_URL + 'photos/generate-link/' + currentPhotoId, {
            expires_preset: expiresPreset,
            password: password
        }, function (res) {
            updateSharePopupUi(res);
        });
    });

    $(document).on('click', '#btnRemovePassword', function () {
        if (!currentPhotoId) return;
        $.post(BASE_URL + 'photos/generate-link/' + currentPhotoId, {
            remove_password: 1
        }, function (res) {
            updateSharePopupUi(res);
        });
    });

    $('#btnCopyLink').on('click', function () {
        const url = $('#sharedUrlText').text();
        navigator.clipboard.writeText(url).then(() => {
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.text('Copied!').addClass('btn-success').removeClass('btn-primary');
            setTimeout(() => {
                $btn.text(originalText).addClass('btn-primary').removeClass('btn-success');
                $('#shareLinkPopup').fadeOut(300, function () { $(this).addClass('d-none'); });
            }, 2000);
        });
    });

    // Photo Actions
    $('#btnArchive').on('click', function () {
        if (!currentPhotoId) return;
        $.post(BASE_URL + 'photos/archive/' + currentPhotoId, function (res) {
            if (res.status === 'success') {
                $lightboxModal.hide();
                $(`[data-id="${currentPhotoId}"]`).fadeOut(300, function () { $(this).remove(); });
            }
        });
    });

    $('#btnDelete').on('click', function () {
        if (!currentPhotoId) return;
        const context = window.location.pathname.split('/').pop() || 'index';
        if (context === 'trash' && !confirm('Permanently delete this photo? This cannot be undone.')) return;

        $.post(BASE_URL + 'photos/delete/' + currentPhotoId, function (res) {
            if (res.status === 'success') {
                $lightboxModal.hide();
                $(`[data-id="${currentPhotoId}"]`).fadeOut(300, function () { $(this).remove(); });
            }
        });
    });

    $('#btnRestore').on('click', function () {
        if (!currentPhotoId) return;
        $.post(BASE_URL + 'photos/restore/' + currentPhotoId, function (res) {
            if (res.status === 'success') {
                $lightboxModal.hide();
                $(`[data-id="${currentPhotoId}"]`).fadeOut(300, function () { $(this).remove(); });
            }
        });
    });

    // Favorites Logic
    $('#btnFavorite').on('click', function () {
        if (!currentPhotoId) return;
        const $btn = $(this);
        const targetPhotoId = currentPhotoId;

        $.post(BASE_URL + 'photos/favorite/' + targetPhotoId, function (res) {
            if (res.status === 'success') {
                const $item = $(`[data-id="${targetPhotoId}"]`);
                $item.data('favorite', res.is_favorite ? '1' : '0');

                // Toggle heart in lightbox only if we are still viewing the same photo
                if (currentPhotoId === targetPhotoId) {
                    $btn.find('i').attr('class', res.is_favorite ? 'bi bi-heart-fill text-danger fs-5' : 'bi bi-heart fs-5');
                }

                // Toggle heart on grid item
                if (res.is_favorite) {
                    if ($item.find('.bi-heart-fill').length === 0) {
                        $item.prepend('<div class="position-absolute top-0 start-0 p-2" style="z-index: 5;"><i class="bi bi-heart-fill text-danger shadow-sm"></i></div>');
                    }
                } else {
                    $item.find('.bi-heart-fill').parent().remove();
                    // If we are on the favorites page, remove the item from view
                    if (window.location.pathname.includes('favorites')) {
                        if (currentPhotoId === targetPhotoId) {
                            $lightboxModal.hide();
                        }
                        $item.fadeOut(300, function () { $(this).remove(); });
                    }
                }
            }
        });
    });

    // Albums Logic
    const $addToAlbumModal = document.getElementById('addToAlbumModal') ? new bootstrap.Modal('#addToAlbumModal') : null;

    $('#btnAddToAlbum').on('click', function () {
        if (!currentPhotoId || !$addToAlbumModal) return;
        $addToAlbumModal.show();

        const $container = $('#albumListContainer');

        $container.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

        $.get(BASE_URL + 'albums', { json: 1 }, function (res) {
            if (res.albums) {
                const manual = res.albums.filter(function (a) { return !parseInt(a.is_smart, 10); });
                if (manual.length === 0) {
                    $container.html('<div class="text-center p-3 text-muted small">No standard albums yet. Smart albums are filled automatically from rules—create a standard album to add photos manually.</div>');
                    return;
                }

                let html = '';
                manual.forEach(album => {
                    html += `<button type="button" class="list-group-item list-group-item-action bg-transparent text-white border-secondary small py-2 btn-confirm-add" data-album-id="${album.id}">${album.name}</button>`;
                });
                $container.html(html);
            }
        });
    });

    $(document).on('click', '.btn-confirm-add', function () {
        const albumId = $(this).data('album-id');

        $.post(BASE_URL + 'albums/add-photo', { album_id: albumId, photo_id: currentPhotoId }, function (res) {
            if (res.status === 'success') {
                $addToAlbumModal.hide();
                showToast('Added to album!', 'success');
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    $('#formCreateAlbum').on('submit', function (e) {
        e.preventDefault();
        $.post(BASE_URL + 'albums/create', $(this).serialize(), function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    $(document).on('submit', '#formEditSmartAlbum', function (e) {
        e.preventDefault();
        const $form = $(this);
        const id = $form.data('album-id');
        $.post(BASE_URL + 'albums/update-smart/' + id, $form.serialize(), function (res) {
            if (res.status === 'success') {
                const el = document.getElementById('editSmartAlbumModal');
                if (el) {
                    const inst = bootstrap.Modal.getInstance(el);
                    if (inst) inst.hide();
                }
                showToast('Smart album saved', 'success');
                location.reload();
            } else {
                showToast(res.message, 'danger');
            }
        });
    });

    // --- Photo Editor Logic ---
    let editorCanvas = null;
    let originalImage = null;
    const $editorModal = document.getElementById('editorModal') ? new bootstrap.Modal('#editorModal') : null;

    $('#btnEditPhoto').on('click', function () {
        if (!currentPhotoId || !$editorModal) return;
        const $item = $(`[data-id="${currentPhotoId}"]`);
        const fullUrl = $item.data('full');
        
        if ($lightboxModal) $lightboxModal.hide();
        $editorModal.show();
        
        initEditor(fullUrl);
    });

    function initEditor(url) {
        if (editorCanvas) {
            editorCanvas.dispose();
        }
        
        editorCanvas = new fabric.Canvas('editorCanvas', {
            backgroundColor: '#000',
            selection: false
        });

        fabric.Image.fromURL(url, function (img) {
            originalImage = img;
            
            // Scale image to fit canvas
            const containerWidth = $('#editorCanvasContainer').width() - 80;
            const containerHeight = $('#editorCanvasContainer').height() - 80;
            
            const scale = Math.min(containerWidth / img.width, containerHeight / img.height);
            
            img.set({
                scaleX: scale,
                scaleY: scale,
                originX: 'center',
                originY: 'center',
                left: editorCanvas.width / 2,
                top: editorCanvas.height / 2,
                selectable: false
            });

            editorCanvas.setWidth($('#editorCanvasContainer').width());
            editorCanvas.setHeight($('#editorCanvasContainer').height());
            
            // Update image center after canvas resize
            img.set({
                left: editorCanvas.width / 2,
                top: editorCanvas.height / 2
            });

            editorCanvas.add(img);
            editorCanvas.renderAll();
        }, { crossOrigin: 'anonymous' });
    }

    // Rotation
    $('#toolRotateLeft').on('click', () => rotateImage(-90));
    $('#toolRotateRight').on('click', () => rotateImage(90));

    function rotateImage(angle) {
        if (!originalImage) return;
        const currentAngle = originalImage.angle || 0;
        originalImage.rotate(currentAngle + angle);
        editorCanvas.renderAll();
    }

    // Filters
    $('.editor-tool[data-filter]').on('click', function () {
        const filter = $(this).data('filter');
        applyFilter(filter);
    });

    function applyFilter(type) {
        if (!originalImage) return;
        
        const filterTypes = {
            'grayscale': [new fabric.Image.filters.Grayscale()],
            'sepia': [new fabric.Image.filters.Sepia()],
            'brightness': [
                new fabric.Image.filters.Brightness({ brightness: 0.05 }),
                new fabric.Image.filters.Contrast({ contrast: 0.1 })
            ]
        };

        const targetFilters = filterTypes[type];
        if (!targetFilters) return;

        let isActive = false;
        targetFilters.forEach(tf => {
            const index = originalImage.filters.findIndex(f => f.type === tf.type);
            if (index > -1) {
                originalImage.filters.splice(index, 1);
                isActive = false;
            } else {
                originalImage.filters.push(tf);
                isActive = true;
            }
        });

        originalImage.applyFilters();
        editorCanvas.renderAll();
        
        $(`.editor-tool[data-filter="${type}"]`).toggleClass('active', isActive);
    }

    // Reset
    $('#toolReset').on('click', function () {
        if (!originalImage) return;
        originalImage.filters = [];
        originalImage.angle = 0;
        originalImage.applyFilters();
        editorCanvas.renderAll();
        $('.editor-tool').removeClass('active');
    });

    // Save
    $('#btnSaveEdit').on('click', function () {
        if (!editorCanvas || !currentPhotoId) return;
        
        $loading.css('display', 'flex');
        
        // Export at original resolution (roughly)
        // Note: Real implementation would handle this better, but for demo we export canvas
        const dataURL = editorCanvas.toDataURL({
            format: 'jpeg',
            quality: 0.9
        });

        // Convert DataURL to Blob
        fetch(dataURL)
            .then(res => res.blob())
            .then(blob => {
                const formData = new FormData();
                formData.append('image', blob, 'edit.jpg');

                $.ajax({
                    url: BASE_URL + 'photos/save-edit/' + currentPhotoId,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        if (res.status === 'success') {
                            showToast('Photo updated successfully!', 'success');
                            $editorModal.hide();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast(res.message, 'danger');
                        }
                    },
                    error: () => showToast('Failed to save edit', 'danger'),
                    complete: () => $loading.hide()
                });
            });
    });

    // --- Crop Tool Logic ---
    let isCropMode = false;
    const $cropOverlay = $('#cropOverlay');
    
    $('#toolCrop').on('click', function () {
        isCropMode = !isCropMode;
        $(this).toggleClass('active', isCropMode);
        
        if (isCropMode) {
            $cropOverlay.removeClass('d-none');
            // Center the crop overlay on the canvas
            const canvasRect = editorCanvas.getElement().getBoundingClientRect();
            $cropOverlay.css({
                width: originalImage.getScaledWidth() / 2,
                height: originalImage.getScaledHeight() / 2,
                left: (canvasRect.width - (originalImage.getScaledWidth() / 2)) / 2,
                top: (canvasRect.height - (originalImage.getScaledHeight() / 2)) / 2
            });
        } else {
            $cropOverlay.addClass('d-none');
        }
    });

    // Make crop overlay draggable
    let isDraggingCrop = false;
    let dragStartX, dragStartY, initialLeft, initialTop;

    $cropOverlay.on('mousedown', function (e) {
        if (e.target !== this) return; // Only drag if clicking the overlay itself, not handles
        isDraggingCrop = true;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        initialLeft = parseFloat($(this).css('left'));
        initialTop = parseFloat($(this).css('top'));
        e.preventDefault();
    });

    $(document).on('mousemove', function (e) {
        if (!isDraggingCrop) return;
        const dx = e.clientX - dragStartX;
        const dy = e.clientY - dragStartY;
        $cropOverlay.css({
            left: initialLeft + dx,
            top: initialTop + dy
        });
    });

    $(document).on('mouseup', function () {
        isDraggingCrop = false;
    });

    $('#btnConfirmCrop').on('click', function () {
        if (!originalImage || !isCropMode) return;

        // Calculate crop relative to original image resolution
        const canvasRect = editorCanvas.getElement().getBoundingClientRect();
        const overlayRect = $cropOverlay[0].getBoundingClientRect();
        
        const scaleX = originalImage.scaleX;
        const scaleY = originalImage.scaleY;

        // Calculate coordinates relative to the image object center
        const relativeX = (overlayRect.left - canvasRect.left - originalImage.left) / scaleX;
        const relativeY = (overlayRect.top - canvasRect.top - originalImage.top) / scaleY;
        
        // Fabric images use cropX/cropY relative to original size
        originalImage.set({
            cropX: (originalImage.width / 2) + relativeX,
            cropY: (originalImage.height / 2) + relativeY,
            width: overlayRect.width / scaleX,
            height: overlayRect.height / scaleY
        });

        editorCanvas.renderAll();
        
        // Exit crop mode
        isCropMode = false;
        $('#toolCrop').removeClass('active');
        $cropOverlay.addClass('d-none');
        showToast('Crop applied!', 'dark');
    });

    // --- Search Logic ---
    $('#searchInput').on('keypress', function (e) {
        if (e.which === 13) { // Enter key
            const q = $(this).val();
            const url = new URL(window.location.href);
            if (q) url.searchParams.set('q', q);
            else url.searchParams.delete('q');
            window.location.href = url.href;
        }
    });

    // --- Bulk Selection Logic ---
    const $bulkToolbar = $('#bulkActionsToolbar');
    const $selectedCount = $('#selectedCount');

    $('#btnToggleSelect, #btnCancelSelect').on('click', function () {
        isSelectMode = !isSelectMode;
        toggleSelectMode();
    });

    function toggleSelectMode() {
        isSelectMode ? $('body').addClass('select-mode') : $('body').removeClass('select-mode');
        $('#btnToggleSelect').toggleClass('btn-primary btn-outline-secondary');
        $('#selectModeText').text(isSelectMode ? 'Cancel' : 'Select');
        $('.selection-overlay').toggleClass('d-none', !isSelectMode);

        if (!isSelectMode) {
            selectedIds.clear();
            $('.photo-item').removeClass('selected').find('.bi-check-lg').addClass('d-none');
            updateBulkToolbar();
        }
    }

    $(document).on('click', '.photo-item', function (e) {
        if (!isSelectMode) return;

        e.preventDefault();
        e.stopPropagation();

        const id = $(this).data('id');
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            $(this).removeClass('selected').find('.bi-check-lg').addClass('d-none');
        } else {
            selectedIds.add(id);
            $(this).addClass('selected').find('.bi-check-lg').removeClass('d-none');
        }

        updateBulkToolbar();
    });

    function updateBulkToolbar() {
        const count = selectedIds.size;
        $selectedCount.text(count);
        $bulkToolbar.toggleClass('d-none', count === 0);
    }

    // --- Bulk Actions ---
    $('#bulkFavorite, #bulkArchive, #bulkDelete, #bulkTrash').on('click', function () {
        const action = $(this).attr('id').replace('bulk', '').toLowerCase();
        if (selectedIds.size === 0) return;

        if ((action === 'delete' || action === 'trash') && !confirm(`Move ${selectedIds.size} selected photos to trash?`)) return;

        $.post(BASE_URL + 'bulk-action', {
            action: action,
            ids: Array.from(selectedIds)
        }, function (res) {
            if (res.status === 'success') {
                location.reload();
            }
        });
    });

    $('#bulkDownload').on('click', function () {
        if (selectedIds.size === 0) return;

        const $form = $('<form method="post" style="display:none"></form>').attr('action', BASE_URL + 'bulk-action').appendTo('body');
        $form.append($('<input type="hidden" name="action" value="download">'));
        selectedIds.forEach(function (id) {
            $form.append($('<input type="hidden" name="ids[]" value="' + id + '">'));
        });
        $form.submit();
        $form.remove();
    });

    $('#bulkAddToAlbum').on('click', function () {
        if (selectedIds.size === 0) return;
        $addToAlbumModal.show();

        // Re-use album list fetching logic
        const $container = $('#albumListContainer');
        $container.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');

        $.get(BASE_URL + 'albums', { json: 1 }, function (res) {
            if (res.albums) {
                const manual = res.albums.filter(function (a) { return !parseInt(a.is_smart, 10); });
                if (manual.length === 0) {
                    $container.html('<div class="text-center p-3 text-muted small">No standard albums. Create a standard album to add photos manually.</div>');
                    return;
                }

                let html = '';
                manual.forEach(album => {
                    html += `<button type="button" class="list-group-item list-group-item-action bg-transparent text-white border-secondary small py-2 btn-confirm-bulk-add" data-album-id="${album.id}">${album.name}</button>`;
                });
                $container.html(html);
            }
        });
    });

    $(document).on('click', '.btn-confirm-bulk-add', function () {
        const albumId = $(this).data('album-id');
        $.post(BASE_URL + 'bulk-action', {
            action: 'add_to_album',
            album_id: albumId,
            ids: Array.from(selectedIds)
        }, function (res) {
            if (res.status === 'success') {
                location.reload();
            } else {
                showToast(res.message || 'Could not add to album', 'danger');
            }
        });
    });

    // Scan Logic
    $('#btnScan').on('click', function () {
        $loading.css('display', 'flex');
        $.ajax({
            url: 'scan',
            method: 'GET',
            success: function (response) {
                showToast(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            },
            error: function () {
                showToast('Scan failed.', 'danger');
            },
            complete: function () {
                $loading.hide();
            }
        });
    });

    // Backfill EXIF Logic
    $('#btnBackfillExif').on('click', function () {
        $loading.css('display', 'flex');
        $.ajax({
            url: 'backfill-exif',
            method: 'GET',
            success: function (response) {
                showToast(response.message, 'success');
            },
            error: function () {
                showToast('EXIF backfill failed.', 'danger');
            },
            complete: function () {
                $loading.hide();
            }
        });
    });

    // --- Drag and Drop Logic ---
    $(document).on('dragstart', '.photo-item', function (e) {
        if (isSelectMode) return;
        const id = $(this).data('id');
        e.originalEvent.dataTransfer.setData('text/plain', id);
        $(this).addClass('dragging');
    });

    $(document).on('dragend', '.photo-item', function () {
        $(this).removeClass('dragging');
    });

    $('.album-dropzone').on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('bg-primary text-white rounded');
    });

    $('.album-dropzone').on('dragleave', function () {
        $(this).removeClass('bg-primary text-white rounded');
    });

    $('.album-dropzone').on('drop', function (e) {
        e.preventDefault();
        const $this = $(this);
        $this.removeClass('bg-primary text-white rounded');

        if ($this.attr('data-is-smart') === '1') {
            showToast('Smart albums follow rules automatically. Use a standard album to add photos by drag and drop.', 'warning');
            return;
        }

        const photoId = e.originalEvent.dataTransfer.getData('text/plain');
        const albumId = $this.data('album-id');

        if (photoId && albumId) {
            $.post(BASE_URL + 'albums/add-photo', { album_id: albumId, photo_id: photoId }, function (res) {
                if (res.status === 'success') {
                    showToast('Added to album!', 'success');
                } else {
                    showToast(res.message, 'warning');
                }
            });
        }
    });

    // --- Timeline Scrubbar Logic ---
    function initTimelineScrubbar() {
        const $scrubbar = $('#timelineScrubbar');
        const $markersContainer = $('#timelineMarkers');
        const $tooltip = $('#timelineTooltip');
        const $tooltipText = $('#timelineTooltipText');
        const $headers = $('.timeline-header');

        if ($headers.length < 1) {
            $scrubbar.addClass('d-none');
            return;
        }

        $scrubbar.removeClass('d-none');
        $markersContainer.empty();

        $headers.each(function (index) {
            const $header = $(this);
            const dateText = $header.text().trim();
            const $marker = $('<div class="timeline-marker"></div>');
            
            $marker.on('mouseenter', function () {
                const pos = $(this).position().top;
                $tooltipText.text(dateText);
                $tooltip.css('top', pos + 'px').removeClass('d-none');
            });

            $marker.on('mouseleave', function () {
                $tooltip.addClass('d-none');
            });

            $marker.on('click', function () {
                $('html, body').animate({
                    scrollTop: $header.offset().top - 80
                }, 500);
            });

            $markersContainer.append($marker);
        });

        // Update active marker on scroll
        $(window).on('scroll', function () {
            const scrollPos = $(window).scrollTop() + 100;
            let activeIndex = 0;

            $headers.each(function (index) {
                if ($(this).offset().top <= scrollPos) {
                    activeIndex = index;
                }
            });

            $('.timeline-marker').removeClass('active').eq(activeIndex).addClass('active');
        });
    }

    // Call init after content is potentially loaded
    initTimelineScrubbar();
    
    // Re-initialize scrubbar when more content is loaded via Infinite Scroll
    $(document).on('contentLoaded', function() {
        initTimelineScrubbar();
    });

    // Dropzone Initialization
    if ($('#photoDropzone').length) {
        let myDropzone = new Dropzone("#photoDropzone", {
            paramName: "file",
            maxFilesize: (typeof window.APP_MAX_UPLOAD_MB !== 'undefined' ? window.APP_MAX_UPLOAD_MB : 500), // MB
            acceptedFiles: "image/*,video/*",
            timeout: 900000, // 15 minutes for large video uploads
            dictDefaultMessage: "Drop photos or videos here or click to upload",
            init: function () {
                this.on("queuecomplete", function (file) {
                    // Reload page when all uploads in the queue are complete
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                });
                this.on("error", function (file, message) {
                    console.error("Upload Error:", message);
                });
            }
        });
    }

    function setAppTheme(theme) {
        if (typeof window.setAppTheme === 'function') {
            window.setAppTheme(theme);
        }
    }

    // Register PWA Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(BASE_URL + 'sw.js').then(function (reg) {
                console.log('PWA Service Worker registered with scope:', reg.scope);
            }).catch(function (err) {
                console.warn('PWA Service Worker registration failed:', err);
            });
        });
    }
});

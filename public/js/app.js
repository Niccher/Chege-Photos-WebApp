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
    const $loading = $('#loadingOverlay');

    // Sidebar Toggle
    $('#sidebarToggle').on('click', function () {
        $('#sidebarMenu').toggleClass('active');
    });

    // Lightbox Logic
    const $lightboxModal = new bootstrap.Modal('#lightboxModal');
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

        const q = $('#searchInput').val();
        $.ajax({
            url: window.location.pathname + '?page=' + currentPage + (q ? '&q=' + q : ''),
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
        const $mainContent = $('main.main-content');
        const baseUrl = $('base').attr('href') || window.location.origin + '/';
        
        photos.forEach(photo => {
            const date = new Date(photo.taken_at);
            const monthYear = date.toLocaleString('en-US', { month: 'long', year: 'numeric' });
            
            if (monthYear !== lastDateGroup) {
                lastDateGroup = monthYear;
                const headerHtml = `
                    <div class="d-flex align-items-center gap-3 mb-3 mt-5 px-2">
                        <h5 class="mb-0 fw-bold text-white opacity-75 timeline-header">${monthYear}</h5>
                        <div class="flex-grow-1 border-bottom border-secondary opacity-25"></div>
                    </div>
                    <div class="photo-grid"></div>`;
                $('#infiniteScrollSentinel').before(headerHtml);
            }
            
            const $targetGrid = $('.photo-grid').last();
            const isFav = photo.is_favorite ? '1' : '0';
            const exifB64 = photo.exif_data ? btoa(unescape(encodeURIComponent(photo.exif_data))) : '';
            const locationStr = photo.latitude && photo.longitude ? photo.latitude + ',' + photo.longitude : '';
            const photoHtml = `
                <div class="photo-item" 
                     draggable="true"
                     data-id="${photo.id}" 
                     data-full="${baseUrl + photo.path}"
                     data-filename="${photo.filename}"
                     data-size="${(photo.size / 1024 / 1024).toFixed(2)} MB"
                     data-dimensions="${photo.width ? photo.width + ' x ' + photo.height : 'Video'}"
                     data-date="${date.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })}"
                     data-favorite="${isFav}"
                     data-type="${photo.mime_type.startsWith('video/') ? 'video' : 'image'}"
                     data-exif-b64="${exifB64}"
                     data-location="${locationStr}">
                    <div class="selection-overlay d-none position-absolute top-0 start-0 w-100 h-100 flex-row align-items-start justify-content-end p-2" style="z-index: 10; background: rgba(0,0,0,0.1);">
                        <div class="selection-check d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 24px; height: 24px; cursor: pointer; border: 2px solid #1a73e8; color: #1a73e8;">
                            <i class="bi bi-check-lg d-none"></i>
                        </div>
                    </div>
                    ${photo.is_favorite ? '<div class="position-absolute top-0 start-0 p-2" style="z-index: 5;"><i class="bi bi-heart-fill text-danger shadow-sm"></i></div>' : ''}
                    ${photo.mime_type.startsWith('video/') 
                        ? `<video src="${baseUrl + photo.path}" class="w-100 h-100 object-fit-cover" muted loop preload="metadata" onmouseover="this.play()" onmouseout="this.pause()"></video>
                           <div class="position-absolute bottom-0 end-0 p-1 m-1 bg-dark bg-opacity-75 text-white rounded small" style="pointer-events: none;"><i class="bi bi-play-btn me-1"></i>Video</div>`
                        : `<img src="${baseUrl + photo.thumbnail_path}" alt="${photo.filename}" loading="lazy">`
                    }
                </div>`;
            $targetGrid.append(photoHtml);
        });
    }

    // Corrected $loading variable initialization
    // const $loading = $('.loading-overlay'); // This line was moved from the top, but the original $loading was for #loadingOverlay. Reverting to original.
    let currentIndex = -1;
    let $allPhotos = $('.photo-item'); // This was moved from inside openPhoto, ensuring it's available globally.

    function openPhoto(index) {
        if (index < 0 || index >= $allPhotos.length) return;

        currentIndex = index;
        const $this = $allPhotos.eq(index);
        const fullUrl = $this.data('full');
        const dataType = $this.data('type') || 'image';
        currentPhotoId = $this.data('id');
        const context = window.location.pathname.split('/').pop() || 'index';

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

        $('#metaFilename').text($this.data('filename') || 'Unknown');
        $('#metaDate').text($this.data('date') || '-');
        $('#metaSize').text($this.data('size') || '-');
        $('#metaDimensions').text($this.data('dimensions') || '-');

        // Favorite status
        const isFavorite = $this.data('favorite') == '1';
        $('#btnFavorite i').attr('class', isFavorite ? 'bi bi-heart-fill text-danger fs-5' : 'bi bi-heart fs-5');

        const exifB64 = $this.data('exif-b64');
        const photoExif = exifB64 ? JSON.parse(decodeURIComponent(escape(atob(exifB64)))) : null;
        const photoLocation = $this.data('location');

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

        // Reset and populate EXIF
        $('#metaExifContainer').hide();
        if (photoExif) {
            try {
                const exif = typeof photoExif === 'string' ? JSON.parse(photoExif) : photoExif;
                let exifHtml = '';
                if (exif.Make || exif.Model) {
                    exifHtml += `<strong>${[exif.Make, exif.Model].filter(Boolean).join(' ')}</strong><br>`;
                }
                if (exif.ExposureTime) exifHtml += `Exposure: ${formatExposure(exif.ExposureTime)}, `;
                const fNum = rationalToFloat(exif.FNumber);
                if (fNum) exifHtml += `f/${fNum.toFixed(1)}, `;
                if (exif.ISOSpeedRatings) exifHtml += `ISO ${exif.ISOSpeedRatings}`;

                $('#metaExif').html(exifHtml);
                $('#metaExifContainer').show();
            } catch (e) {
                console.error('Error parsing EXIF:', e);
            }
        }

        // Reset and populate Location
        $('#metaLocationContainer').hide();
        if (photoLocation) {
            const parts = photoLocation.split(',');
            if (parts.length === 2) {
                const lat = parts[0];
                const lng = parts[1];
                $('#metaLocation').attr('href', `https://www.google.com/maps?q=${lat},${lng}`)
                    .text(`${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}`);
                $('#metaLocationContainer').show();
            }
        }

        $lightboxImageContainer.empty();
        if (dataType === 'video') {
            $lightboxImageContainer.append(`<video src="${fullUrl}" class="img-fluid" style="max-height: 100vh; max-width: 100%;" controls autoplay></video>`);
        } else {
            $lightboxImageContainer.append(`<img src="${fullUrl}" class="img-fluid" style="max-height: 100vh; max-width: 100%; object-fit: contain;">`);
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
    });

    $('#btnInfo').on('click', function () {
        $('#metadataPanel').toggleClass('d-none');
    });

    $('#btnCloseMetadata').on('click', function () {
        $('#metadataPanel').addClass('d-none');
    });

    // Public Sharing Link
    $('#btnShareLink, #btnApplyExpiry').on('click', function () {
        if (!currentPhotoId) return;

        const expiresAt = $('#linkExpiryInput').val() || '';
        $.post(BASE_URL + 'photos/generate-link/' + currentPhotoId, { expires_at: expiresAt }, function (res) {
            if (res.status === 'success') {
                $('#sharedUrlText').text(res.url);
                $('#shareLinkPopup').removeClass('d-none').hide().fadeIn(200);
            }
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
    const $addToAlbumModal = new bootstrap.Modal('#addToAlbumModal');

    $('#btnAddToAlbum').on('click', function () {
        if (!currentPhotoId) return;
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
    const $editorModal = new bootstrap.Modal('#editorModal');

    $('#btnEditPhoto').on('click', function () {
        if (!currentPhotoId) return;
        const $item = $(`[data-id="${currentPhotoId}"]`);
        const fullUrl = $item.data('full');
        
        $lightboxModal.hide();
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
            maxFilesize: 512, // MB
            acceptedFiles: "image/*,video/*",
            timeout: 60000,
            dictDefaultMessage: "Drop photos here or click to upload",
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

    // --- Theme Selector Logic ---
    const $themeOpts = $('.theme-opt');
    
    // Check for saved theme or default to auto
    const savedTheme = localStorage.getItem('theme') || 'auto';
    setAppTheme(savedTheme);

    $themeOpts.on('click', function(e) {
        e.preventDefault();
        const newTheme = $(this).data('theme');
        setAppTheme(newTheme);
        localStorage.setItem('theme', newTheme);
    });

    function setAppTheme(theme) {
        if (theme === 'auto') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
        
        // Update active state in dropdown
        $themeOpts.removeClass('active');
        $(`.theme-opt[data-theme="${theme}"]`).addClass('active');
        
        // Update main palette icon color if needed
        if (theme === 'solarized') {
            $('#btnThemeDropdown i').css('color', '#b58900');
        } else {
            $('#btnThemeDropdown i').css('color', '');
        }
    }
});

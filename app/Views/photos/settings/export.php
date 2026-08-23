<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
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
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
    });
</script>
<?= $this->endSection() ?>

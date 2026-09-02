<?= $this->extend('photos/settings/_layout') ?>

<?= $this->section('settings_content') ?>
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
            <thead class="table-theme-header">
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
                <tr><td colspan="6" class="text-center text-muted py-4">Loading pairing tokens...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Active Authenticated Devices Section -->
    <h5 class="mt-4 mb-3"><i class="bi bi-phone me-2"></i>Active Authenticated Devices</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="devicesTable">
            <thead class="table-theme-header">
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
<?= $this->endSection() ?>

<?= $this->section('settings_scripts') ?>
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
                    var extraInfo = '';
                    if (d.device_uuid) {
                        extraInfo += '<br><small class="text-muted">UUID: <code>' + d.device_uuid + '</code></small>';
                    }
                    if (d.os_version || d.screen_metrics) {
                        extraInfo += '<br><small class="text-muted">OS: ' + (d.os_version || 'N/A') + ' | Screen: ' + (d.screen_metrics || 'N/A') + '</small>';
                    }
                    if (d.locale || d.timezone) {
                        extraInfo += '<br><small class="text-muted">Locale: ' + (d.locale || 'N/A') + ' (' + (d.timezone || 'N/A') + ')</small>';
                    }
                    
                    devHtml += '<tr>' +
                        '<td><span class="fw-bold"><i class="bi bi-phone me-1"></i>' + d.name + '</span>' + extraInfo + '</td>' +
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
        loadTokens();

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
    });
</script>
<?= $this->endSection() ?>

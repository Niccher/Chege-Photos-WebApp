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
    <!-- Device Specifications Modal -->
    <div class="modal fade" id="tokenDeviceSpecsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="background: var(--card-bg); color: var(--text-primary); border-radius: 1rem;">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                            <i class="bi bi-cpu fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="tokenSpecsDeviceName">Device Specifications</h5>
                            <p class="text-muted small mb-0">Hardware telemetry and system identifiers</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="table-responsive rounded-3 border" style="border-color: var(--border-color) !important;">
                        <table class="table table-striped align-middle mb-0 small" style="color: var(--text-primary);">
                            <tbody id="tokenDeviceSpecsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnCopyTokenDeviceSpecs"><i class="bi bi-clipboard me-1"></i>Copy Telemetry</button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
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
            window._cachedActiveDevices = res.active_devices || [];
            var devHtml = '';
            if (!res.active_devices || res.active_devices.length === 0) {
                devHtml = '<tr><td colspan="5" class="text-center text-muted py-4">No active linked devices found.</td></tr>';
            } else {
                res.active_devices.forEach(function(d, idx) {
                    var scopesList = '';
                    if (d.scopes && Array.isArray(d.scopes)) {
                        d.scopes.forEach(function(s) {
                            scopesList += '<span class="badge bg-primary bg-opacity-10 text-primary me-1">' + s + '</span>';
                        });
                    }
                    var lastActive = d.last_used_at ? new Date(d.last_used_at).toLocaleString() : 'Just now';
                    var firstLinked = d.created_at ? new Date(d.created_at).toLocaleString() : '—';
                    
                    var actions = '<div class="btn-group">' +
                        '<button class="btn btn-sm btn-outline-primary btn-specs-device" data-index="' + idx + '" title="View Device Specs"><i class="bi bi-cpu me-1"></i> Specs</button>' +
                        '<button class="btn btn-sm btn-outline-danger" onclick="revokeDevice(' + d.id + ')" title="Log out / Revoke device"><i class="bi bi-box-arrow-right me-1"></i> Log Out</button>' +
                        '</div>';
                    
                    devHtml += '<tr>' +
                        '<td><div class="fw-bold"><i class="bi bi-phone text-success me-1"></i>' + d.name + '</div><span class="badge bg-secondary text-white font-monospace mt-1">' + (d.os_version ? 'Android ' + d.os_version : 'Web App') + '</span></td>' +
                        '<td>' + (scopesList || '<span class="badge bg-secondary">none</span>') + '</td>' +
                        '<td class="small">' + firstLinked + '</td>' +
                        '<td class="small">' + lastActive + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                });
            }
            $('#devicesTableBody').html(devHtml);
        });
    }

    $(document).on('click', '.btn-specs-device', function() {
        var idx = $(this).data('index');
        var d = (window._cachedActiveDevices || [])[idx] || {};
        $('#tokenSpecsDeviceName').text(d.name || 'Device Specifications');
        
        var rows = [
            { icon: 'bi-phone text-primary', label: 'Device Model / Name', val: d.name || 'N/A' },
            { icon: 'bi-android2 text-success', label: 'Operating System', val: (d.os_version ? 'Android ' + d.os_version : 'Web App') },
            { icon: 'bi-aspect-ratio text-info', label: 'Screen Metrics', val: d.screen_metrics || 'N/A (Browser Client)' },
            { icon: 'bi-cpu text-warning', label: 'Kernel / Architecture', val: d.kernel_version || 'N/A' },
            { icon: 'bi-translate text-secondary', label: 'System Locale', val: d.locale || 'N/A' },
            { icon: 'bi-clock-history text-secondary', label: 'Timezone', val: d.timezone || 'N/A' },
            { icon: 'bi-fingerprint text-danger', label: 'Device UUID', val: d.device_uuid ? '<code>' + d.device_uuid + '</code>' : 'N/A' },
            { icon: 'bi-fingerprint text-danger', label: 'Device ID', val: d.device_id ? '<code>' + d.device_id + '</code>' : 'N/A' },
            { icon: 'bi-calendar-plus text-primary', label: 'Linked On', val: d.created_at ? new Date(d.created_at).toLocaleString() : 'N/A' },
            { icon: 'bi-activity text-success', label: 'Last Active', val: d.last_used_at ? new Date(d.last_used_at).toLocaleString() : 'Active' }
        ];

        var tbodyHtml = '';
        rows.forEach(function(item) {
            tbodyHtml += '<tr style="border-color: var(--border-color) !important;">' +
                '<th class="text-muted fw-semibold" style="width: 35%;"><i class="bi ' + item.icon + ' me-2"></i>' + item.label + '</th>' +
                '<td class="font-monospace text-break">' + item.val + '</td>' +
                '</tr>';
        });
        $('#tokenDeviceSpecsTableBody').html(tbodyHtml);
        var modal = new bootstrap.Modal(document.getElementById('tokenDeviceSpecsModal'));
        modal.show();
    });

    $(document).on('click', '#btnCopyTokenDeviceSpecs', function () {
        var text = '';
        $('#tokenDeviceSpecsTableBody tr').each(function () {
            var label = $(this).find('th').text().trim();
            var val = $(this).find('td').text().trim();
            text += label + ': ' + val + '\n';
        });
        navigator.clipboard.writeText(text).then(function () {
            showToast('Device telemetry copied to clipboard!', 'success');
        });
    });

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
                    var qrPayload = JSON.stringify({
                        url: window.location.origin,
                        token: token.token
                    });
                    new QRCode(document.getElementById('tokenQrCode'), {
                        text: qrPayload,
                        width: 180,
                        height: 180,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
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

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">

<?php if (! $hasPin): ?>
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- STATE 1: INITIAL PIN SETUP                                    -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="row justify-content-center py-5">
        <div class="col-12 col-md-6 col-lg-5 col-xl-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center" style="background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color) !important;">
                <div class="p-4 p-md-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-shield-lock-fill" style="font-size: 2.5rem;"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Create Vault PIN</h3>
                    <p class="text-muted small mb-4">
                        Your Private Locked Vault isolates sensitive, intimate, or confidential photos from all main albums, timelines, and smart clusters. Create a 4–8 digit PIN to protect it.
                    </p>

                    <form id="vaultSetupPinForm">
                        <?= csrf_field() ?>
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">Enter 4–8 Digit PIN</label>
                            <input type="password" id="setupPinInput" inputmode="numeric" pattern="[0-9]*" maxlength="8" class="form-control form-control-lg text-center font-monospace fs-4 rounded-3" placeholder="••••" required autofocus>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label small fw-bold">Confirm PIN</label>
                            <input type="password" id="setupPinConfirmInput" inputmode="numeric" pattern="[0-9]*" maxlength="8" class="form-control form-control-lg text-center font-monospace fs-4 rounded-3" placeholder="••••" required>
                        </div>

                        <div id="setupErrorAlert" class="alert alert-danger py-2 small d-none"></div>

                        <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill shadow-sm">
                            <i class="bi bi-key-fill me-1"></i> Set PIN &amp; Open Vault
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-10 text-muted small">
                        <i class="bi bi-info-circle me-1"></i> You can always recover vault access using your primary account login password.
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif (! $isUnlocked): ?>
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- STATE 2: VAULT LOCKED (PIN / PASSWORD ENTRY SCREEN)           -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="row justify-content-center py-5">
        <div class="col-12 col-md-6 col-lg-5 col-xl-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden text-center" style="background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color) !important;">
                <div class="p-4 p-md-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10 text-warning mb-3" style="width: 72px; height: 72px;">
                        <i class="bi bi-lock-fill" style="font-size: 2.2rem;"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Private Locked Vault</h3>
                    <p class="text-muted small mb-4">Enter your PIN or master account password to unlock.</p>

                    <!-- Lockout Notification -->
                    <div id="lockoutNotice" class="alert alert-danger text-start py-3 mb-4 <?= $isLockedOut ? '' : 'd-none' ?>" data-remaining="<?= (int)$lockoutRemaining ?>">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-octagon-fill fs-4"></i>
                            <div>
                                <div class="fw-bold small">Vault Temporarily Locked</div>
                                <div class="small opacity-75">Too many failed attempts. Try again in <strong id="lockoutTimerText"><?= ceil($lockoutRemaining / 60) ?>m</strong>.</div>
                            </div>
                        </div>
                    </div>

                    <!-- PIN Entry Mode -->
                    <div id="pinModeContainer" class="<?= $isLockedOut ? 'd-none' : '' ?>">
                        <form id="vaultUnlockPinForm">
                            <?= csrf_field() ?>
                            <!-- Visual PIN dots -->
                            <div class="d-flex justify-content-center gap-3 mb-4" id="pinDotsDisplay">
                                <span class="pin-dot" id="dot-0"></span>
                                <span class="pin-dot" id="dot-1"></span>
                                <span class="pin-dot" id="dot-2"></span>
                                <span class="pin-dot" id="dot-3"></span>
                            </div>
                            <input type="password" id="vaultPinHidden" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="8" class="form-control text-center font-monospace fs-4 rounded-pill mb-3" placeholder="Enter PIN" autofocus>

                            <div id="unlockErrorAlert" class="alert alert-danger py-2 small d-none"></div>

                            <!-- On-Screen Keypad -->
                            <div class="row g-2 justify-content-center mb-4 pin-keypad">
                                <?php for ($n = 1; $n <= 9; $n++): ?>
                                    <div class="col-4">
                                        <button type="button" class="btn btn-outline-secondary btn-keypad w-100 rounded-4 py-3 fs-5 fw-bold" data-key="<?= $n ?>"><?= $n ?></button>
                                    </div>
                                <?php endfor; ?>
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-danger btn-keypad w-100 rounded-4 py-3 fs-6 fw-bold" id="btnKeypadClear"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-secondary btn-keypad w-100 rounded-4 py-3 fs-5 fw-bold" data-key="0">0</button>
                                </div>
                                <div class="col-4">
                                    <button type="button" class="btn btn-outline-secondary btn-keypad w-100 rounded-4 py-3 fs-6 fw-bold" id="btnKeypadBackspace"><i class="bi bi-backspace"></i></button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill shadow-sm mb-3">
                                <i class="bi bi-unlock-fill me-1"></i> Unlock Vault
                            </button>
                        </form>

                        <button type="button" class="btn btn-link text-muted small text-decoration-none" id="btnTogglePasswordFallback">
                            <i class="bi bi-key me-1"></i> Use master account password instead
                        </button>
                    </div>

                    <!-- Account Password Fallback Mode -->
                    <div id="passwordModeContainer" class="d-none">
                        <form id="vaultUnlockPasswordForm">
                            <?= csrf_field() ?>
                            <div class="mb-3 text-start">
                                <label class="form-label small fw-bold">Master Account Password</label>
                                <input type="password" name="password" id="vaultAccountPasswordInput" class="form-control form-control-lg rounded-3" placeholder="Enter account password" required>
                            </div>

                            <div id="passwordErrorAlert" class="alert alert-danger py-2 small d-none"></div>

                            <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold rounded-pill shadow-sm mb-3">
                                <i class="bi bi-unlock-fill me-1"></i> Unlock with Password
                            </button>
                        </form>

                        <button type="button" class="btn btn-link text-muted small text-decoration-none" id="btnTogglePinMode">
                            <i class="bi bi-grid-3x3 me-1"></i> Use PIN keypad instead
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- STATE 3: UNLOCKED VAULT GALLERY                                -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2 border-bottom border-secondary border-opacity-10">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-warning text-dark px-2 py-1 rounded-pill small fw-bold">
                    <i class="bi bi-unlock-fill me-1"></i> Unlocked
                </span>
                <h2 class="h4 mb-0 fw-bold" style="color: var(--text-primary);">Private Locked Vault</h2>
            </div>
            <p class="text-muted small mb-0">
                Encrypted &amp; isolated space. Photos here are completely hidden from all regular views, search, albums, and the mobile client.
            </p>
        </div>

        <div class="d-flex align-items-center gap-3">
            <!-- Active Session Countdown Indicator -->
            <div class="d-flex align-items-center gap-2 px-3 py-1.5 rounded-pill" style="background: rgba(255, 193, 7, 0.12); border: 1px solid rgba(255, 193, 7, 0.3);">
                <i class="bi bi-stopwatch text-warning"></i>
                <span class="small text-warning fw-semibold">Auto-locking in <span id="sessionCountdown" data-seconds="<?= (int)$unlockedRemaining ?>">5:00</span></span>
            </div>

            <!-- Lock Now Button -->
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-bold" id="btnLockVaultNow">
                <i class="bi bi-lock-fill me-1"></i> Lock Now
            </button>
        </div>
    </div>

    <!-- Stats & Filters Toolbar -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <ul class="nav nav-pills gap-2 p-1 rounded-pill" style="background: var(--card-bg); border: 1px solid var(--border-color); width: fit-content;">
                <li class="nav-item">
                    <a class="nav-link rounded-pill py-1.5 px-3 small <?= $currentTab === 'all' ? 'active bg-warning text-dark fw-bold' : 'text-muted' ?>" href="<?= base_url('vault?tab=all') ?>">
                        <i class="bi bi-collection me-1"></i> All Vaulted <span class="badge bg-dark bg-opacity-25 rounded-pill ms-1"><?= (int)$stats['total'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill py-1.5 px-3 small <?= $currentTab === 'nsfw' ? 'active bg-danger text-white fw-bold' : 'text-muted' ?>" href="<?= base_url('vault?tab=nsfw') ?>">
                        <i class="bi bi-shield-slash me-1"></i> Intimate / Flagged <span class="badge bg-dark bg-opacity-25 rounded-pill ms-1"><?= (int)$stats['nsfw'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-pill py-1.5 px-3 small <?= $currentTab === 'manual' ? 'active bg-primary text-white fw-bold' : 'text-muted' ?>" href="<?= base_url('vault?tab=manual') ?>">
                        <i class="bi bi-pin-angle me-1"></i> Manually Locked <span class="badge bg-dark bg-opacity-25 rounded-pill ms-1"><?= (int)$stats['manual'] ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="col-12 col-md-4 text-md-end">
            <span class="text-muted small">Vault Storage: <strong><?= esc($stats['storage']) ?></strong></span>
        </div>
    </div>

    <?php if (empty($photos)): ?>
        <div class="card border-0 rounded-4 p-5 text-center my-4" style="background: var(--card-bg); border: 1px dashed var(--border-color) !important;">
            <div class="py-5">
                <i class="bi bi-shield-check text-muted" style="font-size: 3.5rem;"></i>
                <h4 class="mt-3 fw-bold">No photos in this vault view</h4>
                <p class="text-muted small mb-0">
                    <?php if ($currentTab === 'nsfw'): ?>
                        No intimate or sensitive photos were flagged by the ML detector.
                    <?php elseif ($currentTab === 'manual'): ?>
                        You haven't manually locked any photos into the vault yet.
                    <?php else: ?>
                        Your private vault is empty. You can select photos in the gallery and click "Move to Vault 🔒".
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Floating Selection Bar for Vault -->
        <div id="vaultSelectionToolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 bg-dark text-white rounded-pill shadow-lg px-4 py-2 d-none" style="z-index: 1060; border: 1px solid rgba(255,255,255,0.15);">
            <div class="d-flex align-items-center gap-3">
                <span class="small fw-bold"><span id="vaultSelectedCount">0</span> selected</span>
                <div class="vr bg-white opacity-25" style="height: 20px;"></div>
                <button type="button" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold" id="btnVaultRestoreSelected">
                    <i class="bi bi-unlock me-1"></i> Restore to Library
                </button>
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 py-1 fw-bold" id="btnVaultDeleteSelected">
                    <i class="bi bi-trash me-1"></i> Permanently Delete
                </button>
                <div class="vr bg-white opacity-25" style="height: 20px;"></div>
                <button type="button" class="btn btn-link text-white p-0 text-decoration-none" id="btnVaultClearSelection">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>

        <!-- Vault Photo Grid -->
        <div class="row g-3" id="vaultPhotoGrid">
            <?php foreach ($photos as $photo): ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="card border-0 rounded-4 overflow-hidden position-relative shadow-sm vault-card h-100" style="background: var(--card-bg);" data-photo-id="<?= $photo['id'] ?>">
                        <!-- Selection Checkbox -->
                        <div class="position-absolute top-0 start-0 p-2 z-2">
                            <div class="form-check">
                                <input class="form-check-input vault-select-chk" type="checkbox" value="<?= $photo['id'] ?>" style="cursor: pointer;">
                            </div>
                        </div>

                        <!-- Intimate / Sensitive Badge -->
                        <?php if (!empty($photo['is_nsfw'])): ?>
                            <div class="position-absolute top-0 end-0 p-2 z-2">
                                <span class="badge bg-danger rounded-pill small shadow-sm" title="Detected as Sensitive/Intimate (Score: <?= round(($photo['nsfw_score'] ?? 0) * 100) ?>%)">
                                    <i class="bi bi-shield-slash-fill me-1"></i> <?= round(($photo['nsfw_score'] ?? 0) * 100) ?>%
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Media Thumbnail (Served via protected vault proxy) -->
                        <div class="ratio ratio-1x1 vault-thumb-wrapper" style="cursor: pointer;" data-full-url="<?= esc($photo['full_url']) ?>" data-filename="<?= esc($photo['filename']) ?>" data-id="<?= $photo['id'] ?>">
                            <img src="<?= esc($photo['thumb_url']) ?>" class="object-fit-cover w-100 h-100 rounded-4 vault-img" alt="<?= esc($photo['filename']) ?>" loading="lazy">
                        </div>

                        <!-- Card Footer Details -->
                        <div class="p-2 d-flex justify-content-between align-items-center text-muted small" style="font-size: 0.75rem;">
                            <span class="text-truncate" style="max-width: 70%;"><?= esc($photo['filename']) ?></span>
                            <div class="dropdown">
                                <button class="btn btn-link text-muted p-0 border-0" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                    <li><a class="dropdown-item btn-restore-single" href="#" data-id="<?= $photo['id'] ?>"><i class="bi bi-unlock text-success me-2"></i>Restore to Library</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger btn-delete-single" href="#" data-id="<?= $photo['id'] ?>"><i class="bi bi-trash me-2"></i>Permanently Delete</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

</div>

<!-- Protected Vault Lightbox Modal -->
<div class="modal fade" id="vaultLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-black border-0">
            <div class="modal-header border-0 p-3 position-absolute top-0 start-0 w-100 d-flex justify-content-between align-items-center z-3" style="background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);">
                <span class="text-white small font-monospace" id="vaultLightboxTitle"></span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 py-1" id="btnVaultLightboxRestore">
                        <i class="bi bi-unlock me-1"></i> Restore
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" id="btnVaultLightboxDelete">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                    <button type="button" class="btn btn-link text-white p-2" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg fs-5"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center h-100">
                <img id="vaultLightboxImg" src="" class="img-fluid" style="max-height: 92vh; object-fit: contain;" alt="Vault Photo">
            </div>
        </div>
    </div>
</div>

<style>
.pin-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid var(--border-color);
    background: transparent;
    transition: all 0.2s ease;
}
.pin-dot.filled {
    background: #ffc107;
    border-color: #ffc107;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}
.btn-keypad {
    height: 60px;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    background: rgba(0,0,0,0.02);
    transition: all 0.15s ease;
}
.btn-keypad:hover, .btn-keypad:active {
    background: #ffc107;
    color: #000;
    border-color: #ffc107;
}
.vault-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--border-color) !important;
}
.vault-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ── 1. PIN Setup Handler ─────────────────────────────────────────
    const setupForm = document.getElementById('vaultSetupPinForm');
    if (setupForm) {
        setupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin1 = document.getElementById('setupPinInput').value.trim();
            const pin2 = document.getElementById('setupPinConfirmInput').value.trim();
            const errAlert = document.getElementById('setupErrorAlert');

            if (pin1 !== pin2) {
                errAlert.textContent = 'PINs do not match. Please verify.';
                errAlert.classList.remove('d-none');
                return;
            }
            if (!/^[0-9]{4,8}$/.test(pin1)) {
                errAlert.textContent = 'PIN must be between 4 and 8 numeric digits.';
                errAlert.classList.remove('d-none');
                return;
            }

            errAlert.classList.add('d-none');
            try {
                const formData = new FormData();
                formData.append('pin', pin1);
                const resp = await fetch('<?= base_url("vault/setup-pin") ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const res = await resp.json();
                if (res.status === 'success') {
                    window.location.reload();
                } else {
                    errAlert.textContent = res.message || 'Failed to setup PIN.';
                    errAlert.classList.remove('d-none');
                }
            } catch (err) {
                errAlert.textContent = 'An unexpected error occurred. Please try again.';
                errAlert.classList.remove('d-none');
            }
        });
    }

    // ── 2. PIN Entry & Keypad Handler ────────────────────────────────
    const pinHidden = document.getElementById('vaultPinHidden');
    const dots = [document.getElementById('dot-0'), document.getElementById('dot-1'), document.getElementById('dot-2'), document.getElementById('dot-3')];
    const unlockPinForm = document.getElementById('vaultUnlockPinForm');
    const unlockErrorAlert = document.getElementById('unlockErrorAlert');

    function updateDots() {
        if (!pinHidden) return;
        const val = pinHidden.value;
        for (let i = 0; i < dots.length; i++) {
            if (i < val.length) {
                dots[i]?.classList.add('filled');
            } else {
                dots[i]?.classList.remove('filled');
            }
        }
    }

    if (pinHidden) {
        pinHidden.addEventListener('input', () => {
            updateDots();
            if (pinHidden.value.length === 4) {
                // Auto submit on 4 digits if desired or allow user to press unlock
            }
        });

        document.querySelectorAll('.pin-keypad button[data-key]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (pinHidden.value.length < 8) {
                    pinHidden.value += btn.getAttribute('data-key');
                    updateDots();
                }
            });
        });

        document.getElementById('btnKeypadClear')?.addEventListener('click', () => {
            pinHidden.value = '';
            updateDots();
        });

        document.getElementById('btnKeypadBackspace')?.addEventListener('click', () => {
            pinHidden.value = pinHidden.value.slice(0, -1);
            updateDots();
        });
    }

    if (unlockPinForm) {
        unlockPinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin = pinHidden.value.trim();
            if (!pin) return;

            unlockErrorAlert.classList.add('d-none');
            try {
                const formData = new FormData();
                formData.append('pin', pin);
                const resp = await fetch('<?= base_url("vault/unlock") ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const res = await resp.json();
                if (resp.ok && res.status === 'success') {
                    window.location.reload();
                } else {
                    unlockErrorAlert.textContent = res.message || 'Incorrect PIN.';
                    unlockErrorAlert.classList.remove('d-none');
                    pinHidden.value = '';
                    updateDots();
                    if (resp.status === 429) {
                        window.location.reload();
                    }
                }
            } catch (err) {
                unlockErrorAlert.textContent = 'Connection error. Please try again.';
                unlockErrorAlert.classList.remove('d-none');
            }
        });
    }

    // ── 3. Password Fallback Toggle & Form ───────────────────────────
    const btnTogglePassword = document.getElementById('btnTogglePasswordFallback');
    const btnTogglePin = document.getElementById('btnTogglePinMode');
    const pinModeContainer = document.getElementById('pinModeContainer');
    const passModeContainer = document.getElementById('passwordModeContainer');
    const unlockPasswordForm = document.getElementById('vaultUnlockPasswordForm');
    const passErrorAlert = document.getElementById('passwordErrorAlert');

    if (btnTogglePassword && btnTogglePin) {
        btnTogglePassword.addEventListener('click', () => {
            pinModeContainer.classList.add('d-none');
            passModeContainer.classList.remove('d-none');
            document.getElementById('vaultAccountPasswordInput')?.focus();
        });
        btnTogglePin.addEventListener('click', () => {
            passModeContainer.classList.add('d-none');
            pinModeContainer.classList.remove('d-none');
            pinHidden?.focus();
        });
    }

    if (unlockPasswordForm) {
        unlockPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pass = document.getElementById('vaultAccountPasswordInput').value;
            passErrorAlert.classList.add('d-none');
            try {
                const formData = new FormData();
                formData.append('password', pass);
                const resp = await fetch('<?= base_url("vault/unlock") ?>', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const res = await resp.json();
                if (resp.ok && res.status === 'success') {
                    window.location.reload();
                } else {
                    passErrorAlert.textContent = res.message || 'Incorrect password.';
                    passErrorAlert.classList.remove('d-none');
                }
            } catch (err) {
                passErrorAlert.textContent = 'Connection error. Please try again.';
                passErrorAlert.classList.remove('d-none');
            }
        });
    }

    // ── 4. Lockout Countdown ─────────────────────────────────────────
    const lockoutNotice = document.getElementById('lockoutNotice');
    if (lockoutNotice) {
        let remaining = parseInt(lockoutNotice.getAttribute('data-remaining') || '0', 10);
        if (remaining > 0) {
            const timerSpan = document.getElementById('lockoutTimerText');
            const interval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(interval);
                    window.location.reload();
                } else {
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    if (timerSpan) timerSpan.textContent = `${m}m ${s < 10 ? '0' : ''}${s}s`;
                }
            }, 1000);
        }
    }

    // ── 5. Unlocked Session Countdown & Immediate Lock ───────────────
    const sessionTimer = document.getElementById('sessionCountdown');
    if (sessionTimer) {
        let secRemaining = parseInt(sessionTimer.getAttribute('data-seconds') || '300', 10);
        const timerInterval = setInterval(() => {
            secRemaining--;
            if (secRemaining <= 0) {
                clearInterval(timerInterval);
                window.location.reload();
            } else {
                const m = Math.floor(secRemaining / 60);
                const s = secRemaining % 60;
                sessionTimer.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
            }
        }, 1000);
    }

    document.getElementById('btnLockVaultNow')?.addEventListener('click', async () => {
        try {
            await fetch('<?= base_url("vault/lock") ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            window.location.reload();
        } catch (err) {
            window.location.href = '<?= base_url("vault") ?>';
        }
    });

    // ── 6. Selection & Batch Actions (Restore / Delete) ───────────────
    const selectionToolbar = document.getElementById('vaultSelectionToolbar');
    const selectedCountSpan = document.getElementById('vaultSelectedCount');
    const checkboxes = document.querySelectorAll('.vault-select-chk');

    function updateVaultSelection() {
        const checked = document.querySelectorAll('.vault-select-chk:checked');
        const count = checked.length;
        if (selectedCountSpan) selectedCountSpan.textContent = count;
        if (count > 0) {
            selectionToolbar?.classList.remove('d-none');
        } else {
            selectionToolbar?.classList.add('d-none');
        }
    }

    checkboxes.forEach(chk => {
        chk.addEventListener('change', updateVaultSelection);
    });

    document.getElementById('btnVaultClearSelection')?.addEventListener('click', () => {
        checkboxes.forEach(chk => chk.checked = false);
        updateVaultSelection();
    });

    async function executeVaultAction(action, photoIds) {
        if (!photoIds || photoIds.length === 0) return;
        const endpoint = action === 'restore' ? '<?= base_url("vault/restore") ?>' : '<?= base_url("vault/delete") ?>';
        const msg = action === 'restore' 
            ? `Restore ${photoIds.length} photo(s) back to the main library?`
            : `Permanently delete ${photoIds.length} photo(s)? This cannot be undone.`;

        if (!confirm(msg)) return;

        try {
            const formData = new FormData();
            photoIds.forEach(id => formData.append('photo_ids[]', id));

            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const res = await resp.json();
            if (res.status === 'success') {
                window.location.reload();
            } else {
                alert(res.message || 'Action failed');
            }
        } catch (err) {
            alert('An error occurred. Please try again.');
        }
    }

    document.getElementById('btnVaultRestoreSelected')?.addEventListener('click', () => {
        const ids = Array.from(document.querySelectorAll('.vault-select-chk:checked')).map(c => c.value);
        executeVaultAction('restore', ids);
    });

    document.getElementById('btnVaultDeleteSelected')?.addEventListener('click', () => {
        const ids = Array.from(document.querySelectorAll('.vault-select-chk:checked')).map(c => c.value);
        executeVaultAction('delete', ids);
    });

    document.querySelectorAll('.btn-restore-single').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            executeVaultAction('restore', [btn.getAttribute('data-id')]);
        });
    });

    document.querySelectorAll('.btn-delete-single').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            executeVaultAction('delete', [btn.getAttribute('data-id')]);
        });
    });

    // ── 7. Vault Lightbox Preview ─────────────────────────────────────
    let activeLightboxPhotoId = null;
    const vaultModalEl = document.getElementById('vaultLightboxModal');
    const vaultModal = vaultModalEl ? new bootstrap.Modal(vaultModalEl) : null;
    const lightboxImg = document.getElementById('vaultLightboxImg');
    const lightboxTitle = document.getElementById('vaultLightboxTitle');

    document.querySelectorAll('.vault-thumb-wrapper').forEach(wrapper => {
        wrapper.addEventListener('click', (e) => {
            if (e.target.classList.contains('form-check-input')) return;
            const fullUrl = wrapper.getAttribute('data-full-url');
            const filename = wrapper.getAttribute('data-filename');
            const id = wrapper.getAttribute('data-id');

            activeLightboxPhotoId = id;
            if (lightboxImg) lightboxImg.src = fullUrl;
            if (lightboxTitle) lightboxTitle.textContent = filename;
            vaultModal?.show();
        });
    });

    document.getElementById('btnVaultLightboxRestore')?.addEventListener('click', () => {
        if (activeLightboxPhotoId) {
            vaultModal?.hide();
            executeVaultAction('restore', [activeLightboxPhotoId]);
        }
    });

    document.getElementById('btnVaultLightboxDelete')?.addEventListener('click', () => {
        if (activeLightboxPhotoId) {
            vaultModal?.hide();
            executeVaultAction('delete', [activeLightboxPhotoId]);
        }
    });
});
</script>

<?= $this->endSection() ?>

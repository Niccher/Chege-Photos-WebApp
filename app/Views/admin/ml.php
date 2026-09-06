<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">ML Engine Configurations</h2>
            <p class="text-muted small mb-0">Control Insightface model parameters, vector database spaces, and clustering parameters.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Settings Form -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <form id="formAdminMl">
                    <h5 class="mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary"></i>
                        <span>Model Parameters</span>
                    </h5>

                    <!-- Model Pack Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">InsightFace Model Pack</label>
                        <select name="faceModelPack" class="form-select bg-light border-0 py-2">
                            <option value="buffalo_l" <?= $settings['faceModelPack'] === 'buffalo_l' ? 'selected' : '' ?>>buffalo_l (Large - High Accuracy, GPU recommended)</option>
                            <option value="buffalo_m" <?= $settings['faceModelPack'] === 'buffalo_m' ? 'selected' : '' ?>>buffalo_m (Medium - Balanced)</option>
                            <option value="buffalo_s" <?= $settings['faceModelPack'] === 'buffalo_s' ? 'selected' : '' ?>>buffalo_s (Small - Fast)</option>
                            <option value="buffalo_sc" <?= $settings['faceModelPack'] === 'buffalo_sc' ? 'selected' : '' ?>>buffalo_sc (Smallest - Optimized for CPU execution)</option>
                        </select>
                        <span class="text-muted small">InsightFace Architecture: Switches facial analysis weights. Dynamically reloads ONNX runtime models into container RAM without rebooting.</span>
                    </div>

                    <!-- Face detection threshold confidence slider -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">RetinaFace Detection Threshold</label>
                            <span class="badge bg-primary rounded-pill" id="detThreshValue"><?= esc($settings['faceDetThresh']) ?></span>
                        </div>
                        <input type="range" class="form-range" name="faceDetThresh" min="0.1" max="1.0" step="0.05" value="<?= esc($settings['faceDetThresh']) ?>" id="detThreshSlider">
                        <span class="text-muted small">Face Detection Confidence: Minimum certainty required to extract a face. Higher thresholds (0.50–0.60) eliminate false positives from ambient shadows, wall textures, and background noise.</span>
                    </div>

                    <!-- CLIP Model Pack Selector -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold d-block">CLIP Semantic Search Model</label>
                        <select name="clipModelName" id="selectClipModel" class="form-select bg-light border-0 py-2">
                            <option value="openai/clip-vit-base-patch32" <?= $settings['clipModelName'] === 'openai/clip-vit-base-patch32' ? 'selected' : '' ?>>openai/clip-vit-base-patch32 (Default • Fastest, 512-d, ~350MB RAM)</option>
                            <option value="openai/clip-vit-base-patch16" <?= $settings['clipModelName'] === 'openai/clip-vit-base-patch16' ? 'selected' : '' ?>>openai/clip-vit-base-patch16 (Recommended Upgrade • High Precision, 512-d, ~500MB RAM)</option>
                            <option value="laion/CLIP-ViT-B-32-laion2B-s34B-b79K" <?= $settings['clipModelName'] === 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K' ? 'selected' : '' ?>>laion/CLIP-ViT-B-32-laion2B-s34B-b79K (2B Images Dataset • 512-d, ~400MB RAM)</option>
                            <option value="laion/CLIP-ViT-B-16-laion2B-s34B-b88K" <?= $settings['clipModelName'] === 'laion/CLIP-ViT-B-16-laion2B-s34B-b88K' ? 'selected' : '' ?>>laion/CLIP-ViT-B-16-laion2B-s34B-b88K (LAION Fine Details • 512-d, ~550MB RAM)</option>
                            <option value="custom" <?= !in_array($settings['clipModelName'], ['openai/clip-vit-base-patch32', 'openai/clip-vit-base-patch16', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K', 'laion/CLIP-ViT-B-16-laion2B-s34B-b88K']) ? 'selected' : '' ?>>Custom HuggingFace Model Repo...</option>
                        </select>
                        <div id="customClipContainer" class="mt-2" style="<?= !in_array($settings['clipModelName'], ['openai/clip-vit-base-patch32', 'openai/clip-vit-base-patch16', 'laion/CLIP-ViT-B-32-laion2B-s34B-b79K', 'laion/CLIP-ViT-B-16-laion2B-s34B-b88K']) ? '' : 'display:none;' ?>">
                            <input type="text" class="form-control bg-light border-0 py-2 font-monospace small" id="inputCustomClip" placeholder="e.g. openai/clip-vit-base-patch32" value="<?= esc($settings['clipModelName']) ?>">
                        </div>
                        <span class="text-muted small">Semantic Vision Transformer: Powers natural-language search (e.g. 'sunset at the beach', 'kids playing soccer'). Selecting a model hot-reloads transformer weights dynamically into RAM. Requires 512-dimensional output to match Qdrant.</span>
                    </div>

                    <!-- YOLOv8 Object Detection Threshold -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">YOLOv8 Tagging Threshold</label>
                            <span class="badge bg-primary rounded-pill" id="objThreshValue"><?= esc($settings['objectDetThresh']) ?></span>
                        </div>
                        <input type="range" class="form-range" name="objectDetThresh" min="0.1" max="1.0" step="0.05" value="<?= esc($settings['objectDetThresh']) ?>" id="objThreshSlider">
                        <span class="text-muted small">Object &amp; Scene Tagging Confidence: Minimum certainty required to auto-assign searchable COCO labels (vehicles, animals, food, nature, electronics) during upload.</span>
                    </div>

                    <!-- Sensitive Content & Moderation (NSFW) Section -->
                    <div class="p-3 mb-4 rounded border" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.02);">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-shield-lock-fill text-warning me-1"></i> Sensitive Content &amp; Moderation (NSFW)
                                </h6>
                                <span class="text-muted small">Computer vision moderation for intimate photo detection and automatic Private Locked Vault isolation.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 fw-bold" id="btnTriggerNsfwSweep">
                                <i class="bi bi-shield-check me-1"></i> Scan Existing Photos
                            </button>
                        </div>

                        <!-- NSFW Model Pack Selector -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold d-block">NSFW Detection Model</label>
                            <select name="nsfwModel" id="selectNsfwModel" class="form-select bg-light border-0 py-2">
                                <option value="opennsfw2" <?= ($settings['nsfwModel'] ?? 'opennsfw2') === 'opennsfw2' ? 'selected' : '' ?>>opennsfw2 (Default • Ultra-lightweight Yahoo ResNet-50, ~6MB weights, <50MB RAM, CPU-optimized)</option>
                                <option value="falconsai" <?= ($settings['nsfwModel'] ?? '') === 'falconsai' ? 'selected' : '' ?>>Falconsai/nsfw_image_detection (HuggingFace Vision Transformer ViT, ~300MB weights, high precision)</option>
                                <option value="nudenet" <?= ($settings['nsfwModel'] ?? '') === 'nudenet' ? 'selected' : '' ?>>NudeNet (Anatomical part detector, ~150MB weights, detects exposed body parts)</option>
                            </select>
                            <span class="text-muted small">Hot-swappable at runtime: Changing the active model automatically evicts previous weights from RAM and loads the selected architecture.</span>
                        </div>

                        <!-- Sensitivity Threshold Slider -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">Sensitivity Confidence Threshold</label>
                                <span class="badge bg-warning text-dark rounded-pill" id="nsfwThreshValue"><?= number_format((float)($settings['nsfwThreshold'] ?? 0.70), 2) ?></span>
                            </div>
                            <input type="range" class="form-range" name="nsfwThreshold" min="0.10" max="1.00" step="0.05" value="<?= esc($settings['nsfwThreshold'] ?? 0.70) ?>" id="nsfwThreshSlider">
                            <span class="text-muted small">Confidence Threshold: Photos scored at or above this threshold are classified as sensitive. Recommended: <strong>0.70</strong>. Lowering below 0.50 may flag swimwear, costumes, or artistic sculpture.</span>
                        </div>

                        <!-- Auto-Vault Toggle -->
                        <div class="mb-1">
                            <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <label class="form-label small fw-bold mb-0 d-block">Automatic Vault Quarantine</label>
                                    <span class="text-muted small">When flagged as sensitive, immediately isolate the photo into the user's Private Locked Vault, revoking shares and hiding it from public views, albums, and facial recognition.</span>
                                </div>
                                <input class="form-check-input ms-0 fs-4" type="checkbox" name="autoVaultNsfw" value="1" <?= (!empty($settings['autoVaultNsfw'])) ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- HDBSCAN Section with Auto-Tuner & Advisory -->
                    <div class="p-3 mb-4 rounded border" style="border-color: var(--border-color) !important; background: rgba(0,0,0,0.02);">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-people-fill text-primary me-1"></i> HDBSCAN Face Clustering Hyperparameters
                                </h6>
                                <span class="text-muted small">Algorithmic density-based clustering to automatically discover identities and group faces without supervision.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold" id="btnAutotuneHdbscan">
                                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Auto-Tune from Data
                            </button>
                        </div>

                        <!-- Minimum cluster size -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold d-block">HDBSCAN Minimum Cluster Size</label>
                            <input type="number" class="form-control bg-light border-0 py-2" name="hdbscanMinCluster" id="inputMinCluster" min="1" max="20" value="<?= esc($settings['hdbscanMinCluster']) ?>">
                            <span class="text-muted small">Minimum Person Occurrences: Number of photos a face must appear in before forming a distinct Person card. Setting this to 3 effectively filters out passing background strangers in crowd shots.</span>
                        </div>

                        <!-- Minimum samples -->
                        <div class="mb-2">
                            <label class="form-label small fw-bold d-block">HDBSCAN Minimum Samples</label>
                            <input type="number" class="form-control bg-light border-0 py-2" name="hdbscanMinSamples" id="inputMinSamples" min="1" max="20" value="<?= esc($settings['hdbscanMinSamples']) ?>">
                            <span class="text-muted small">Density Core Threshold: Governs how strictly faces are grouped. Setting this to 2 forces mutual density, preventing different family members from being mistakenly merged together.</span>
                            <div id="minSamplesWarning" class="alert alert-warning py-2 px-3 rounded small mt-2 mb-0" style="<?= (int)$settings['hdbscanMinSamples'] === 1 ? '' : 'display:none;' ?>">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Advisory:</strong> Min Samples = 1 treats single faces as dense cores. This frequently causes <em>cluster bleeding</em> (falsely merging different people who look slightly similar). <strong>Recommended: 2</strong>.
                            </div>
                        </div>
                    </div>

                    <!-- Age/gender estimation toggle -->
                    <div class="mb-4">
                        <div class="form-check form-switch p-0 d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label small fw-bold mb-0 d-block">Estimate Sensitive Attributes</label>
                                <span class="text-muted small">Demographic Biometrics: Estimates chronological age and gender classification during facial scans for demographic filters.</span>
                            </div>
                            <input class="form-check-input ms-0 fs-4" type="checkbox" name="includeSensitive" value="1" <?= (!isset($settings['includeSensitive']) || $settings['includeSensitive']) ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Save submit -->
                    <div class="mt-4 pt-3 border-top d-flex flex-wrap align-items-center gap-2" style="border-color: var(--border-color) !important;">
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-check-lg me-1"></i> Save ML Parameters
                        </button>
                        <button type="button" class="btn btn-outline-info px-4 rounded-pill" id="btnSimulateClustering">
                            <i class="bi bi-eye me-1"></i> Simulate (Dry-Run)
                        </button>
                        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill ms-auto" id="btnResetMlDefaults">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Defaults
                        </button>
                    </div>
                </form>
            </div>

            <!-- Operational triggers -->
            <div class="card border-0 shadow-sm rounded-card p-4 mt-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h5 class="mb-4 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge text-warning"></i>
                    <span>Operational Triggers</span>
                </h5>
                <p class="text-muted small">Administratively invoke batch jobs and monitor real-time AI pipeline execution.</p>

                <!-- Live Scan Progress & ETA Tracker -->
                <div class="p-3 mb-3 border rounded" id="liveScanTrackerCard" style="border-color: var(--border-color) !important; background: rgba(13, 110, 253, 0.03);">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-activity text-primary fs-5" id="scanTrackerIcon"></i>
                            <div>
                                <h6 class="mb-0 small fw-bold" id="scanTrackerTitle">Real-Time Scan &amp; Pipeline Monitor</h6>
                                <span class="text-muted extra-small" id="scanTrackerSubtitle">Tracking InsightFace, YOLOv8, and CLIP execution</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge rounded-pill px-2.5 py-1 small" id="badgeScanStatus">Idle</span>
                            <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small d-none" id="badgeScanSpeed"><i class="bi bi-speedometer2 me-1"></i><span id="textScanSpeed">0.0</span> /s</span>
                            <span class="badge bg-light text-primary border rounded-pill px-2.5 py-1 small d-none" id="badgeScanEta"><i class="bi bi-clock-history me-1"></i>ETA: <span id="textScanEta">--</span></span>
                        </div>
                    </div>

                    <div class="progress rounded-pill mb-2" style="height: 10px; background: rgba(0,0,0,0.08);">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="progressBarScan" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center extra-small text-muted">
                        <span id="scanProgressText">0 / 0 photos processed across pipeline</span>
                        <span id="scanProgressPercent" class="fw-bold text-primary">0%</span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3 mt-3">
                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold">Trigger HDBSCAN Clustering</h6>
                            <p class="text-muted small mb-1">Execute Identity Grouping: Clusters unlocked face vectors into identified Person cards. Preserves custom names and automatically links newly discovered photos.</p>
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1 small me-1" id="badgeUnassignedFaces">Unassigned Faces: <?= esc($mlStats['unassigned']) ?></span>
                            <span class="badge bg-success text-white rounded-pill px-2 py-1 small" id="badgeTotalPersons">Persons (Clusters): <?= esc($mlStats['total_persons']) ?></span>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" id="btnTriggerCluster">
                            <i class="bi bi-diagram-3 me-1"></i> Cluster
                        </button>
                    </div>

                    <div class="p-3 border rounded d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <div>
                            <h6 class="mb-1 small fw-bold text-danger">Reset Face Encodings</h6>
                            <p class="text-muted small mb-1">Purge Face Embeddings: Clears all Qdrant face vectors and resets Person clusters back to unassigned state. Use before full re-scans.</p>
                            <span class="badge bg-danger text-white rounded-pill px-2 py-1 small" id="badgeTotalEncodings">Total Encodings (Vectors): <?= esc($mlStats['total_encodings']) ?></span>
                        </div>
                        <button class="btn btn-danger btn-sm rounded-pill px-3" id="btnTriggerReset" data-bs-toggle="modal" data-bs-target="#resetMlModal">
                            <i class="bi bi-trash me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Sidebar -->
        <div class="col-lg-5">
            <!-- Health Stats -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-4"><i class="bi bi-cpu text-primary me-2"></i>Microservice Health</h6>
                
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">FastAPI Service:</span>
                        <div>
                            <?php if ($mlHealth['online']): ?>
                                <span class="badge bg-success text-white rounded-pill px-3 py-1">ONLINE</span>
                                <?php if (!empty($mlHealth['latency_ms'])): ?>
                                    <span class="badge bg-light text-muted border rounded-pill ms-1 extra-small"><?= esc($mlHealth['latency_ms']) ?>ms</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-danger text-white rounded-pill px-3 py-1">OFFLINE</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">MySQL connection:</span>
                        <?php if ($mlHealth['db']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">CONNECTED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1">DISCONNECTED</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Qdrant Vector DB:</span>
                        <?php if ($mlHealth['qdrant']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">CONNECTED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1">DISCONNECTED</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">Buffalo-L Weights:</span>
                        <?php if ($mlHealth['models']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">LOADED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1">UNLOADED</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">CLIP Model:</span>
                        <?php if ($mlHealth['clip']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">LOADED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1">UNLOADED</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">YOLOv8 ONNX Model:</span>
                        <?php if ($mlHealth['yolo']): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">LOADED</span>
                        <?php else: ?>
                            <span class="badge bg-danger text-white rounded-pill px-3 py-1">UNLOADED</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="border-color: var(--border-color) !important;">
                        <span class="text-muted">NSFW Detector Model:</span>
                        <?php if (!empty($mlHealth['nsfw'])): ?>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1">LOADED</span>
                        <?php else: ?>
                            <span class="badge bg-secondary text-white rounded-pill px-3 py-1">ON DEMAND</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ML Service Endpoint Connection & Dynamic Prober -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-diagram-2 text-primary me-2"></i>ML Service Endpoint</h6>
                    <span class="badge bg-secondary rounded-pill small" id="badgeMlUrlSource"><?= esc($mlUrlSource ?? 'Auto-detected') ?></span>
                </div>
                <p class="text-muted small mb-3">Target endpoint for InsightFace, YOLOv8, and CLIP microservice. Leave empty to use automatic Railway private DNS discovery.</p>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Service Endpoint URL:</label>
                    <div class="input-group">
                        <input type="text" id="inputMlUrl" name="mlUrl" form="formAdminMl" class="form-control font-monospace small bg-light border-0 py-2" placeholder="e.g. http://ml-chege-photos.railway.internal:8000" value="<?= esc($mlUrlSetting ?? '') ?>">
                        <button type="button" class="btn btn-primary btn-sm px-3" id="btnTestMlEndpoint" title="Test Connection">
                            <i class="bi bi-broadcast me-1"></i> Test
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-muted extra-small">Active: <code id="activeMlUrlText"><?= esc($activeMlUrl ?? 'http://ml-chege-photos:8000') ?></code></span>
                        <?php if (!empty($mlUrlSetting)): ?>
                            <button type="button" class="btn btn-link text-danger p-0 extra-small text-decoration-none" id="btnClearMlUrl">Reset to Auto</button>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="mlTestFeedback" class="alert d-none small py-2 px-3 mb-0 rounded" role="alert"></div>
            </div>

            <!-- ML Security & API Key Management -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock text-warning me-2"></i>ML API Key Security</h6>
                <p class="text-muted small mb-3">All WebApp-to-ML HTTP requests pass this secret authorization key via the <code>X-API-KEY</code> header.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Active API Token Key:</label>
                    <div class="input-group">
                        <input type="text" id="apiKeyDisplay" class="form-control font-monospace small bg-light border-0 py-2" value="<?= esc($apiKey) ?>" readonly>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="btnCopyKey" title="Copy to clipboard">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill w-100 fw-bold py-2" id="btnRegenerateKey">
                    <i class="bi bi-key-fill me-1"></i> Regenerate API Key
                </button>
            </div>

            <!-- Batch Rescan Engine -->
            <div class="card border-0 shadow-sm rounded-card p-4 mb-4" style="background: var(--card-bg); color: var(--text-primary);">
                <h6 class="fw-bold mb-4"><i class="bi bi-arrow-repeat text-primary me-2"></i>Batch Rescan Engine</h6>
                
                <div class="d-flex flex-column gap-3">
                    <!-- Rescan Faces -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">Faces Processing</span>
                            <span class="badge bg-primary rounded-pill" id="badgeScannedFaces"><?= esc($mlStats['scanned_faces']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm rounded-pill w-50 btn-rescan" data-type="faces" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="faces" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>

                    <!-- Rescan Object Tags -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">YOLOv8 Object Tags</span>
                            <span class="badge bg-success rounded-pill" id="badgeScannedTags"><?= esc($mlStats['scanned_tags']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-success btn-sm rounded-pill w-50 btn-rescan" data-type="tags" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="tags" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>

                    <!-- Rescan CLIP Embeddings -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold">CLIP Semantic Vectors</span>
                            <span class="badge bg-info rounded-pill text-dark" id="badgeScannedClips"><?= esc($mlStats['scanned_clips']) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-info btn-sm rounded-pill w-50 text-dark btn-rescan" data-type="clip" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="clip" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>

                    <!-- Rescan Sensitive Media (NSFW) -->
                    <div class="border rounded p-3" style="border-color: var(--border-color) !important;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold">Sensitive Media (NSFW)</span>
                            <span class="badge bg-warning text-dark rounded-pill" id="badgeScannedNsfw"><?= esc($mlStats['scanned_nsfw'] ?? 0) ?> / <?= esc($mlStats['total_photos']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 extra-small text-muted">
                            <span><i class="bi bi-shield-exclamation text-danger me-1"></i><?= esc($mlStats['nsfw_photos'] ?? 0) ?> Flagged</span>
                            <span><i class="bi bi-lock-fill text-warning me-1"></i><?= esc($mlStats['vault_photos'] ?? 0) ?> In Vault</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-warning btn-sm rounded-pill w-50 btn-rescan text-dark fw-semibold" data-type="nsfw" data-mode="missing">
                                <i class="bi bi-play-fill me-1"></i> Missing Only
                            </button>
                            <button class="btn btn-danger btn-sm rounded-pill w-50 btn-rescan" data-type="nsfw" data-mode="all">
                                <i class="bi bi-arrow-clockwise me-1"></i> Force All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Model Info -->
            <div class="card border-0 shadow-sm rounded-card p-4 bg-light bg-opacity-25" style="color: var(--text-primary);">
                <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-primary"></i>Model Specifications</h6>
                <div class="small text-muted" style="line-height: 1.7;">
                    <p class="mb-2"><strong>Model Pack:</strong> Insightface Buffalo-L — ResNet-100 backbone trained on MS1MV3 database, creating 512-dimensional float embeddings.</p>
                    <p class="mb-2"><strong>Face Extractor:</strong> RetinaFace running Mobilenet0.25 framework for landmarks estimation.</p>
                    <p class="mb-2"><strong>Vector Space DB:</strong> Qdrant vector engine indexes alignments utilizing HNSW (Hierarchical Navigable Small World) metrics for real-time cosine-similarity searches.</p>
                    <p class="mb-2"><strong>Object Detector (YOLOv8):</strong> YOLOv8n (Nano) ONNX model running inside OpenCV DNN to classify images into 80 COCO objects with user-defined confidence thresholds.</p>
                    <p class="mb-2"><strong>Semantic Search (CLIP):</strong> Contrastive Language-Image Pre-training (ViT-B/32) multimodal transformer generates 512-dimensional vector projections to power natural-language queries.</p>
                    <p class="mb-0"><strong>Sensitive Media Moderation (NSFW):</strong> Specialized computer vision moderation (opennsfw2 ResNet-50 / Falconsai ViT / NudeNet) assigning intimate probability scores and quarantining flagged photos into the Private Locked Vault.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- On-Disk Model Inventory & Storage Inspector -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-folder-check text-success"></i>
                            <span>On-Disk Model Inventory &amp; Storage Inspector</span>
                        </h5>
                        <p class="text-muted small mb-0">Live filesystem scan of neural network model files inside the ML container. Download missing files or re-verify integrity anytime.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small" id="badgeTotalDiskUsage">
                            <i class="bi bi-hdd-fill text-primary me-1"></i> Disk Usage: <span id="textTotalDiskUsage">Scanning...</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5" id="btnRefreshInventory">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold" id="btnDownloadAllModels">
                            <i class="bi bi-cloud-arrow-down-fill me-1"></i> Force Download All Missing
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tableModelInventory">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 20%;">Model &amp; Category</th>
                                <th style="width: 32%;">Role / Purpose</th>
                                <th style="width: 25%;">Container File Path</th>
                                <th style="width: 8%;">Disk Size</th>
                                <th style="width: 8%;">Status</th>
                                <th style="width: 7%;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyModelInventory" class="small">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    Inspecting ML container filesystem...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline Diagnostics & Failure Analysis -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-card p-4" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-exclamation text-danger"></i>
                            <span>AI Pipeline Diagnostics &amp; Failure Analysis</span>
                        </h5>
                        <p class="text-muted small mb-0">Direct probe of photo download health, background error logs, and container execution metrics.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1.5 fw-semibold" id="btnReapStaleJobs" title="Reap scans stuck in processing longer than timeout">
                            <i class="bi bi-clock-history me-1"></i> Reap Stale Scans
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-semibold" id="btnRetryFailedScans" title="Reset failed scans back to pending and re-queue">
                            <i class="bi bi-arrow-repeat me-1"></i> Retry All Failed
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold" id="btnRefreshDiagnostics">
                            <i class="bi bi-arrow-clockwise me-1"></i> Probe Health &amp; Logs
                        </button>
                    </div>
                </div>

                <div id="diagnosticsLoading" class="text-center py-3 text-muted">
                    <span class="spinner-border spinner-border-sm me-2"></span> Loading diagnostics probe...
                </div>

                <div id="diagnosticsContent" class="d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border" style="background: var(--input-bg);">
                                <div class="fw-bold small text-dark mb-1"><i class="bi bi-link-45deg me-1 text-primary"></i> Photo Download Connectivity</div>
                                <div id="diagPhotoAccessStatus" class="small text-muted mb-1">Checking...</div>
                                <div class="extra-small text-muted" id="diagPhotoAccessUrl"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border" style="background: var(--input-bg);">
                                <div class="fw-bold small text-dark mb-1"><i class="bi bi-cpu me-1 text-success"></i> Aggressive Processing &amp; Workers</div>
                                <div id="diagWorkerInfo" class="small text-muted mb-1">Checking...</div>
                                <div class="extra-small text-muted">Tune via <code>ML_CONCURRENT_WORKERS</code> env variable in Railway.</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold small mb-2"><i class="bi bi-bug text-danger me-1"></i> Recent Photo Processing Errors (<span id="diagErrorCount">0</span>)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 100px;">Photo ID</th>
                                    <th>Error Message</th>
                                    <th style="width: 180px;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDiagErrors">
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i> No recent errors recorded.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generic Action Confirmation Alert Modal -->
<div class="modal fade" id="actionConfirmModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i> Confirm Operation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="mb-2 fw-bold text-dark fs-6" id="actionConfirmTitle">Are you sure?</p>
                <p class="text-muted small mb-0" id="actionConfirmBody">Please confirm if you wish to proceed with this operation.</p>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger rounded-pill px-4" id="btnProceedAction">Confirm &amp; Execute</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset ML Engine Confirmation Modal -->
<div class="modal fade" id="resetMlModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-0 bg-danger text-white">
                <h6 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Reset ML Database</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="fw-bold text-danger mb-3">This deletes all extracted face and cluster indexes!</p>
                <p class="text-muted small mb-3">This permanently wipes the Qdrant collections and database face encoding caches. You will need to trigger a library scan to restore facial tags.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Type <span class="text-danger fw-bold">RESET</span> to confirm:</label>
                    <input type="text" id="resetConfirmInput" class="form-control" placeholder="Type RESET here">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger rounded-pill px-4" id="btnConfirmReset" disabled>
                    <i class="bi bi-trash me-1"></i> Confirm Reset
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Algorithmic Auto-Tuner -->
<div class="modal fade" id="modalMlAutotune" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                    <span>Algorithmic HDBSCAN Auto-Tuner</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="autotuneLoading" class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h6 class="fw-bold mb-1">Analyzing Actual Face Vectors in Qdrant...</h6>
                    <p class="text-muted small mb-0">Running grid search and Silhouette Separation Analysis across cluster combinations.</p>
                </div>

                <div id="autotuneResults" style="display: none;">
                    <div class="alert alert-success d-flex align-items-center gap-3 p-3 rounded mb-4">
                        <i class="bi bi-check-circle-fill fs-2 text-success"></i>
                        <div>
                            <div class="fw-bold text-success fs-6" id="autotuneRecommendationTitle">Optimal Parameters Discovered</div>
                            <div class="small text-muted" id="autotuneRationale"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 border rounded text-center bg-light">
                                <span class="text-muted small d-block mb-1">Recommended Min Cluster Size</span>
                                <span class="fs-3 fw-bold text-primary" id="autoRecMcs">-</span>
                                <span class="d-block text-muted" style="font-size: 11px;">Minimum photos to form a Person</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded text-center bg-light">
                                <span class="text-muted small d-block mb-1">Recommended Min Samples</span>
                                <span class="fs-3 fw-bold text-primary" id="autoRecMs">-</span>
                                <span class="d-block text-muted" style="font-size: 11px;">Density threshold (prevents bleeding)</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold small mb-2 text-dark">Evaluated Parameter Configurations:</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover align-middle small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Min Cluster Size</th>
                                    <th>Min Samples</th>
                                    <th>Silhouette Quality</th>
                                    <th>Discovered People</th>
                                    <th>Noise Ratio</th>
                                    <th class="text-end">Rank</th>
                                </tr>
                            </thead>
                            <tbody id="autotuneCandidatesTable"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" id="btnApplyAutotune" style="display: none;">
                    <i class="bi bi-check2-circle me-1"></i> Apply Recommended Settings
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Clustering Simulation Preview -->
<div class="modal fade" id="modalMlSimulate" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="color: var(--text-primary);">
            <div class="modal-header border-bottom pb-3">
                <h5 class="modal-title h6 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-eye text-info fs-5"></i>
                    <span>Clustering Dry-Run Simulation</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="simulateLoading" class="text-center py-4">
                    <div class="spinner-border text-info mb-2" role="status"></div>
                    <p class="text-muted small mb-0">Simulating clustering impact on current vectors...</p>
                </div>
                <div id="simulateResults" style="display: none;">
                    <p class="small text-muted mb-3">Projected results based on your selected parameters (no database changes made):</p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-2.5 border rounded text-center bg-light">
                                <span class="text-muted small d-block">Projected People</span>
                                <span class="fs-4 fw-bold text-success" id="simPeopleCount">-</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2.5 border rounded text-center bg-light">
                                <span class="text-muted small d-block">Unassigned Faces</span>
                                <span class="fs-4 fw-bold text-muted" id="simNoiseCount">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-2.5 border rounded mb-3 small bg-light">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Silhouette Separation Score:</span>
                            <span class="fw-bold" id="simSilhouette">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Noise Ratio:</span>
                            <span class="fw-bold" id="simNoiseRatio">-</span>
                        </div>
                    </div>
                    <div id="simAdvisoryBox" class="alert alert-warning small mb-0" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var activeActionCallback = null;
        var confirmModalInstance = new bootstrap.Modal(document.getElementById('actionConfirmModal'));

        function promptConfirmation(title, body, onConfirm) {
            $('#actionConfirmTitle').text(title);
            $('#actionConfirmBody').text(body);
            activeActionCallback = onConfirm;
            confirmModalInstance.show();
        }

        $('#btnProceedAction').on('click', function() {
            confirmModalInstance.hide();
            if (activeActionCallback) {
                activeActionCallback();
                activeActionCallback = null;
            }
        });

        // Test ML Endpoint button
        $('#btnTestMlEndpoint').on('click', function() {
            var btn = $(this);
            var urlToTest = $('#inputMlUrl').val().trim();
            var feedback = $('#mlTestFeedback');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Testing...');
            feedback.removeClass('d-none alert-success alert-danger alert-warning')
                    .addClass('alert-info')
                    .html('<span class="spinner-border spinner-border-sm me-1"></span> Pinging ML endpoint...');

            $.post(BASE_URL + 'admin/ml/test-connection', { url: urlToTest }, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-broadcast me-1"></i> Test');
                feedback.removeClass('alert-info alert-danger alert-warning')
                        .addClass('alert-success')
                        .html('<div class="fw-bold mb-1"><i class="bi bi-check-circle-fill me-1"></i> ' + res.message + '</div>' +
                              '<div class="extra-small text-dark">DB: ' + (res.details && res.details.db_connected ? 'Connected' : 'Disconnected') +
                              ' | Qdrant: ' + (res.details && res.details.qdrant_connected ? 'Connected' : 'Disconnected') +
                              ' | Models: ' + (res.details && res.details.models_loaded ? 'Loaded' : 'Unloaded') + '</div>');
                $('#activeMlUrlText').text(res.url);
                showToast('ML Service is online (' + res.latency_ms + 'ms)', 'success');
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-broadcast me-1"></i> Test');
                var err = xhr.responseJSON ? xhr.responseJSON.message : ('HTTP ' + xhr.status + ' error');
                var attempted = (xhr.responseJSON && xhr.responseJSON.attempted) ? ('<div class="extra-small text-muted mt-1">Candidates probed: ' + xhr.responseJSON.attempted.join(', ') + '</div>') : '';
                feedback.removeClass('alert-info alert-success alert-warning')
                        .addClass('alert-danger')
                        .html('<div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Connection Error</div>' +
                              '<div>' + err + '</div>' + attempted +
                              '<div class="extra-small text-muted mt-2"><strong>Railway Tip:</strong> In Railway dashboard, ensure your ML service is running and verify whether its private domain is <code>ml-chege-photos.railway.internal:8000</code> or generate a public domain and enter it here.</div>');
                showToast('Failed to reach ML service', 'danger');
            });
        });

        // Reset to auto button
        $('#btnClearMlUrl').on('click', function() {
            $('#inputMlUrl').val('');
            $('#formAdminMl').trigger('submit');
        });

        // Copy API key to clipboard
        $('#btnCopyKey').on('click', function() {
            var input = document.getElementById('apiKeyDisplay');
            input.select();
            document.execCommand('copy');
            showToast('API Key copied to clipboard!', 'success');
        });

        // Regenerate API Key
        $('#btnRegenerateKey').on('click', function() {
            promptConfirmation(
                "Regenerate ML Access Token Key?",
                "This will invalidate the current key. Requests sent without the new key header will be rejected.",
                function() {
                    var btn = $('#btnRegenerateKey');
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Regenerating...');
                    $.post(BASE_URL + 'admin/ml/regenerate-key', function(res) {
                        btn.prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i> Regenerate API Key');
                        if (res.status === 'success') {
                            $('#apiKeyDisplay').val(res.apiKey);
                            showToast(res.message, 'success');
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i> Regenerate API Key');
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to regenerate key: ' + err, 'danger');
                    });
                }
            );
        });

        // Range label updates
        $('#detThreshSlider').on('input', function() {
            $('#detThreshValue').text($(this).val());
        });

        $('#objThreshSlider').on('input', function() {
            $('#objThreshValue').text($(this).val());
        });

        $('#nsfwThreshSlider').on('input', function() {
            $('#nsfwThreshValue').text(parseFloat($(this).val()).toFixed(2));
        });

        // Trigger NSFW Background Sweep
        $('#btnTriggerNsfwSweep').on('click', function() {
            var btn = $(this);
            var originalHtml = btn.html();
            promptConfirmation(
                "Scan Existing Photos for Sensitive Content?",
                "This will dispatch a background sweep to scan all photos for sensitive/intimate media and isolate flagged items into the Private Locked Vault.",
                function() {
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Queuing...');
                    $.post(BASE_URL + 'admin/ml/scan-nsfw', function(res) {
                        btn.prop('disabled', false).html(originalHtml);
                        if (res.status === 'success') {
                            showToast(res.message, 'success');
                            startStatsPolling();
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Failed to dispatch scan sweep: ' + err, 'danger');
                    });
                }
            );
        });

        // Save ML Parameters
        $('#formAdminMl').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

            var formData = form.serializeArray();
            if ($('#selectClipModel').val() === 'custom') {
                var customVal = $('#inputCustomClip').val().trim();
                formData = formData.map(function(item) {
                    if (item.name === 'clipModelName') {
                        return { name: 'clipModelName', value: customVal };
                    }
                    return item;
                });
            }

            $.post(BASE_URL + 'admin/ml/save', formData, function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save ML Parameters');
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i> Save ML Parameters');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Failed to save settings: ' + err, 'danger');
            });
        });

        // CLIP Model selection
        $('#selectClipModel').on('change', function() {
            var val = $(this).val();
            if (val === 'custom') {
                $('#customClipContainer').slideDown(150);
                $('#inputCustomClip').focus();
            } else {
                $('#customClipContainer').slideUp(150);
            }
        });

        // HDBSCAN Min Samples advisory toggle
        $('#inputMinSamples').on('input', function() {
            if (parseInt($(this).val()) === 1) {
                $('#minSamplesWarning').slideDown(150);
            } else {
                $('#minSamplesWarning').slideUp(150);
            }
        });

        // HDBSCAN Algorithmic Auto-Tuner
        $('#btnAutotuneHdbscan').on('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('modalMlAutotune'));
            modal.show();
            $('#autotuneLoading').show();
            $('#autotuneResults').hide();
            $('#btnApplyAutotune').hide();

            $.post(BASE_URL + 'admin/ml/autotune', function(res) {
                $('#autotuneLoading').hide();
                $('#autotuneResults').show();

                if (res.status === 'success') {
                    $('#autotuneRecommendationTitle').text('Optimal Parameters Discovered');
                    $('#autotuneRationale').text(res.rationale || '');
                    $('#autoRecMcs').text(res.recommended_min_cluster_size);
                    $('#autoRecMs').text(res.recommended_min_samples);
                    $('#btnApplyAutotune').data('mcs', res.recommended_min_cluster_size).data('ms', res.recommended_min_samples).show();

                    var rowsHtml = '';
                    if (res.top_candidates && res.top_candidates.length > 0) {
                        res.top_candidates.forEach(function(c, idx) {
                            var badge = idx === 0 
                                ? '<span class="badge bg-success text-white">Best</span>' 
                                : '<span class="badge bg-secondary text-white">#' + (idx + 1) + '</span>';
                            rowsHtml += '<tr>' +
                                '<td><code>' + c.min_cluster_size + '</code></td>' +
                                '<td><code>' + c.min_samples + '</code></td>' +
                                '<td><span class="fw-bold text-primary">' + c.silhouette_score + '</span></td>' +
                                '<td>' + c.n_clusters + '</td>' +
                                '<td>' + (c.noise_ratio * 100).toFixed(1) + '%</td>' +
                                '<td class="text-end">' + badge + '</td>' +
                            '</tr>';
                        });
                    }
                    $('#autotuneCandidatesTable').html(rowsHtml);
                } else {
                    $('#autotuneRecommendationTitle').text('Autotune Advisory');
                    $('#autotuneRationale').text(res.message || 'Could not run cluster optimization.');
                    $('#autoRecMcs').text('-');
                    $('#autoRecMs').text('-');
                    $('#autotuneCandidatesTable').html('<tr><td colspan="6" class="text-center text-muted py-3">' + (res.message || 'Insufficient data') + '</td></tr>');
                }
            }).fail(function(xhr) {
                $('#autotuneLoading').hide();
                $('#autotuneResults').show();
                $('#autotuneRecommendationTitle').text('Autotune Request Error');
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP ' + xhr.status;
                $('#autotuneRationale').text('Failed to connect to ML microservice: ' + err);
            });
        });

        // Apply autotuned values
        $('#btnApplyAutotune').on('click', function() {
            var mcs = $(this).data('mcs');
            var ms = $(this).data('ms');
            if (mcs) $('#inputMinCluster').val(mcs);
            if (ms) {
                $('#inputMinSamples').val(ms);
                if (parseInt(ms) === 1) $('#minSamplesWarning').slideDown(150); else $('#minSamplesWarning').slideUp(150);
            }
            bootstrap.Modal.getInstance(document.getElementById('modalMlAutotune')).hide();
            showToast('Applied optimal parameters! Click "Save ML Parameters" to persist.', 'success');
        });

        // Simulate Clustering Dry-Run
        $('#btnSimulateClustering').on('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('modalMlSimulate'));
            modal.show();
            $('#simulateLoading').show();
            $('#simulateResults').hide();

            var mcs = $('#inputMinCluster').val();
            var ms = $('#inputMinSamples').val();

            $.post(BASE_URL + 'admin/ml/simulate', { minClusterSize: mcs, minSamples: ms }, function(res) {
                $('#simulateLoading').hide();
                $('#simulateResults').show();

                if (res.status === 'success') {
                    $('#simPeopleCount').text(res.projected_people);
                    $('#simNoiseCount').text(res.projected_noise + ' (' + (res.noise_ratio * 100).toFixed(1) + '%)');
                    $('#simSilhouette').text(res.silhouette_score);
                    $('#simNoiseRatio').text((res.noise_ratio * 100).toFixed(1) + '%');

                    if (res.advisory) {
                        $('#simAdvisoryBox').text(res.advisory).show();
                    } else {
                        $('#simAdvisoryBox').hide();
                    }
                } else {
                    $('#simPeopleCount').text('-');
                    $('#simNoiseCount').text('-');
                    $('#simSilhouette').text('-');
                    $('#simNoiseRatio').text('-');
                    $('#simAdvisoryBox').text(res.message || 'Simulation error').show();
                }
            }).fail(function(xhr) {
                $('#simulateLoading').hide();
                $('#simulateResults').show();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP ' + xhr.status;
                $('#simAdvisoryBox').text('Simulation failed: ' + err).show();
            });
        });

        // Reset default values locally
        $('#btnResetMlDefaults').on('click', function() {
            $('select[name="faceModelPack"]').val('buffalo_l');
            $('#detThreshSlider').val(0.5);
            $('#detThreshValue').text(0.5);
            $('#selectClipModel').val('openai/clip-vit-base-patch32');
            $('#inputClipModelName').val('openai/clip-vit-base-patch32').hide();
            $('#objThreshSlider').val(0.5);
            $('#objThreshValue').text(0.5);
            $('#inputMinCluster').val(2);
            $('#inputMinSamples').val(2);
            $('#minSamplesWarning').hide();
            $('input[name="includeSensitive"]').prop('checked', true);
            showToast('Form fields filled with default values. Click "Save ML Parameters" to submit.', 'info');
        });

        // Handle RESET text confirmation
        $('#resetConfirmInput').on('input', function() {
            var val = $(this).val().trim();
            $('#btnConfirmReset').prop('disabled', val !== 'RESET');
        });

        // Trigger ML clustering
        $('#btnTriggerCluster').on('click', function() {
            promptConfirmation(
                "Trigger HDBSCAN Face Clustering?",
                "This will analyze unlocked face vectors and group them into identified Person clusters. Manually named persons will be preserved.",
                function() {
                    var btn = $('#btnTriggerCluster');
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Clustering...');
                    
                    $.post(BASE_URL + 'admin/ml/cluster', function(res) {
                        btn.prop('disabled', false).html('<i class="bi bi-diagram-3 me-1"></i> Cluster');
                        if (res.status === 'success') {
                            showToast(res.message, 'success');
                        } else {
                            showToast(res.message, 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-diagram-3 me-1"></i> Cluster');
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                        showToast('Clustering failed: ' + err, 'danger');
                    });
                }
            );
        });

        // Trigger ML database wipe
        $('#btnConfirmReset').on('click', function() {
            var btn = $(this);
            var modal = bootstrap.Modal.getInstance(document.getElementById('resetMlModal'));
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Resetting...');

            $.post(BASE_URL + 'admin/ml/reset', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Confirm Reset');
                $('#resetConfirmInput').val('');
                modal.hide();
                if (res.status === 'success') {
                    showToast(res.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Confirm Reset');
                modal.hide();
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                showToast('Reset failed: ' + err, 'danger');
            });
        });

        // Dynamic Polling for ML Job Progress & Real-Time ETA Calculation
        var pollInterval = null;
        var lastPollTime = null;
        var lastCompletedSum = null;

        function formatSecondsEta(sec) {
            if (!sec || isNaN(sec) || sec <= 0 || !isFinite(sec)) return '--';
            sec = Math.round(sec);
            if (sec < 60) return sec + 's';
            var m = Math.floor(sec / 60);
            var s = sec % 60;
            if (m < 60) return m + 'm ' + s + 's';
            var h = Math.floor(m / 60);
            m = m % 60;
            return h + 'h ' + m + 'm';
        }

        function updateScanProgressUI(stats, isProcessing, queueSize) {
            var total = parseInt(stats.total_photos) || 0;
            var scannedFaces = parseInt(stats.scanned_faces) || 0;
            var scannedTags = parseInt(stats.scanned_tags) || 0;
            var scannedClips = parseInt(stats.scanned_clips) || 0;
            var scannedNsfw = parseInt(stats.scanned_nsfw) || 0;
            var totalOps = total * 4;
            var completedOps = scannedFaces + scannedTags + scannedClips + scannedNsfw;
            var remainingOps = Math.max(0, totalOps - completedOps);
            var pct = totalOps > 0 ? Math.min(100, Math.round((completedOps / totalOps) * 100)) : 100;

            $('#progressBarScan').css('width', pct + '%').attr('aria-valuenow', pct);
            $('#scanProgressPercent').text(pct + '%');
            $('#scanProgressText').text(completedOps + ' / ' + totalOps + ' pipeline tasks completed (' + total + ' photos)');

            var now = Date.now();
            var speed = 0;
            if (lastPollTime && lastCompletedSum !== null) {
                var dt = (now - lastPollTime) / 1000;
                var dp = completedOps - lastCompletedSum;
                if (dp > 0 && dt > 0) {
                    speed = dp / dt;
                }
            }
            lastPollTime = now;
            lastCompletedSum = completedOps;

            if (isProcessing || queueSize > 0 || (remainingOps > 0 && pct < 100)) {
                $('#badgeScanStatus').removeClass('bg-secondary bg-success').addClass('bg-primary text-white')
                    .html('<span class="spinner-grow spinner-grow-sm me-1" style="width: 0.55rem; height: 0.55rem;"></span> Processing');
                
                if (speed > 0) {
                    $('#badgeScanSpeed').removeClass('d-none');
                    $('#textScanSpeed').text(speed.toFixed(1));
                    var etaSec = remainingOps / speed;
                    $('#badgeScanEta').removeClass('d-none');
                    $('#textScanEta').text(formatSecondsEta(etaSec));
                } else if (queueSize > 0) {
                    $('#badgeScanSpeed').removeClass('d-none');
                    $('#textScanSpeed').text('Queued: ' + queueSize);
                    $('#badgeScanEta').addClass('d-none');
                }
            } else {
                $('#badgeScanStatus').removeClass('bg-primary text-white bg-warning').addClass('bg-success text-white').text('All Scans Complete');
                $('#badgeScanSpeed').addClass('d-none');
                $('#badgeScanEta').addClass('d-none');
            }
        }

        function startStatsPolling() {
            if (pollInterval) return;
            pollInterval = setInterval(function() {
                $.getJSON(BASE_URL + 'admin/ml/stats', function(res) {
                    if (res.status === 'success' && res.stats) {
                        var stats = res.stats;
                        
                        // Update Rescan Badges
                        $('#badgeScannedFaces').text(stats.scanned_faces + ' / ' + stats.total_photos);
                        $('#badgeScannedTags').text(stats.scanned_tags + ' / ' + stats.total_photos);
                        $('#badgeScannedClips').text(stats.scanned_clips + ' / ' + stats.total_photos);
                        if (stats.scanned_nsfw !== undefined) {
                            $('#badgeScannedNsfw').text(stats.scanned_nsfw + ' / ' + stats.total_photos);
                        }
                        
                        // Update Operational Badges
                        $('#badgeUnassignedFaces').text('Unassigned Faces: ' + stats.unassigned);
                        $('#badgeTotalPersons').text('Persons (Clusters): ' + stats.total_persons);
                        $('#badgeTotalEncodings').text('Total Encodings (Vectors): ' + stats.total_encodings);

                        // Update Live Scan Progress & Speed Tracker
                        updateScanProgressUI(stats, res.is_processing, res.queue_size);

                        // Stop polling when fully processed
                        var total = parseInt(stats.total_photos);
                        var facesDone = parseInt(stats.scanned_faces) >= total;
                        var tagsDone = parseInt(stats.scanned_tags) >= total;
                        var clipsDone = parseInt(stats.scanned_clips) >= total;
                        var nsfwDone = (stats.scanned_nsfw !== undefined) ? parseInt(stats.scanned_nsfw) >= total : true;
                        
                        if (facesDone && tagsDone && clipsDone && nsfwDone && !res.is_processing && (res.queue_size || 0) === 0) {
                            clearInterval(pollInterval);
                            pollInterval = null;
                        }
                    }
                });
            }, 4000);
        }

        // Start polling on load if work is in progress
        var initTotal = parseInt('<?= $mlStats['total_photos'] ?>') || 0;
        var initFaces = parseInt('<?= $mlStats['scanned_faces'] ?>') || 0;
        var initTags = parseInt('<?= $mlStats['scanned_tags'] ?>') || 0;
        var initClips = parseInt('<?= $mlStats['scanned_clips'] ?>') || 0;
        var initNsfw = parseInt('<?= $mlStats['scanned_nsfw'] ?? 0 ?>') || 0;
        updateScanProgressUI({
            total_photos: initTotal,
            scanned_faces: initFaces,
            scanned_tags: initTags,
            scanned_clips: initClips,
            scanned_nsfw: initNsfw
        }, false, 0);

        if (initFaces < initTotal || initTags < initTotal || initClips < initTotal || initNsfw < initTotal) {
            startStatsPolling();
        }

        // Reap Stale Scans
        $('#btnReapStaleJobs').on('click', function() {
            var btn = $(this).prop('disabled', true);
            btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Reaping...');
            $.post(BASE_URL + 'admin/ml/reap-stale', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-clock-history me-1"></i> Reap Stale Scans');
                if (res.status === 'success') {
                    showToast(res.message || 'Reaped stale scans successfully.', 'success');
                    startStatsPolling();
                } else {
                    showToast('Failed: ' + (res.message || 'Error'), 'danger');
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-clock-history me-1"></i> Reap Stale Scans');
                showToast('Network error reaping stale scans', 'danger');
            });
        });

        // Retry All Failed Scans
        $('#btnRetryFailedScans').on('click', function() {
            var btn = $(this).prop('disabled', true);
            btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Retrying...');
            $.post(BASE_URL + 'admin/ml/retry-failed', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Retry All Failed');
                if (res.status === 'success') {
                    showToast(res.message || 'Queued failed scans for retry.', 'success');
                    startStatsPolling();
                } else {
                    showToast('Failed: ' + (res.message || 'Error'), 'danger');
                }
            }, 'json').fail(function() {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat me-1"></i> Retry All Failed');
                showToast('Network error retrying failed scans', 'danger');
            });
        });

        // Trigger Rescans with Alert Modal
        $('.btn-rescan').on('click', function() {
            var btn = $(this);
            var type = btn.data('type');
            var mode = btn.data('mode');
            var originalHtml = btn.html();

            var title = mode === 'all'
                ? "FORCE Re-Scan All Images for " + type.toUpperCase() + "?"
                : "Queue Missing " + type.toUpperCase() + " Scans?";

            var body = mode === 'all' 
                ? "This will reset scan status flags for all images and re-process the pipeline sequentially in the background."
                : "This will check for any photos missing " + type.toUpperCase() + " scans and queue them for processing.";

            promptConfirmation(title, body, function() {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Queuing...');

                $.post(BASE_URL + 'admin/ml/rescan', { type: type, mode: mode }, function(res) {
                    btn.prop('disabled', false).html(originalHtml);
                    if (res.status === 'success') {
                        showToast(res.message, 'success');
                        // Start polling to show progress immediately
                        startStatsPolling();
                    } else {
                        showToast(res.message, 'danger');
                    }
                }).fail(function(xhr) {
                    btn.prop('disabled', false).html(originalHtml);
                    var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP error ' + xhr.status;
                    showToast('Failed to trigger scan: ' + err, 'danger');
                });
            });
        });

        // ── Model Inventory & Downloader Functions ─────────────────
        function renderModelInventory(data) {
            if (!data || !data.inventory) {
                $('#tbodyModelInventory').html('<tr><td colspan="6" class="text-center py-4 text-danger">Failed to load inventory. ML microservice offline or unreachable.</td></tr>');
                return;
            }

            $('#textTotalDiskUsage').text(data.total_disk_usage || '0 B');

            var html = '';
            data.inventory.forEach(function(item) {
                var badgeStatus = '';
                if (item.status === 'loaded') {
                    badgeStatus = '<span class="badge bg-success text-white rounded-pill px-2.5 py-1"><i class="bi bi-cpu-fill me-1"></i> Loaded in RAM</span>';
                } else if (item.status === 'on_disk') {
                    badgeStatus = '<span class="badge bg-primary text-white rounded-pill px-2.5 py-1"><i class="bi bi-hdd-fill me-1"></i> On Disk</span>';
                } else {
                    badgeStatus = '<span class="badge bg-danger text-white rounded-pill px-2.5 py-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Missing</span>';
                }

                var categoryBadge = '<span class="badge bg-light text-dark border rounded-pill px-2 py-0.5 me-1" style="font-size: 11px;">' + item.category + '</span>';

                var actionBtn = '';
                if (item.status === 'loaded') {
                    actionBtn = '<button class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1 btn-download-group" data-group="' + item.download_group + '" title="Re-download/verify and reload into memory"><i class="bi bi-arrow-repeat me-1"></i> Reload</button>';
                } else {
                    actionBtn = '<button class="btn btn-success btn-sm rounded-pill px-2.5 py-1 btn-download-group fw-bold" data-group="' + item.download_group + '" title="Download missing model and load into memory"><i class="bi bi-cloud-arrow-down-fill me-1"></i> Download</button>';
                }

                html += '<tr>' +
                    '<td>' +
                        '<div class="fw-bold text-dark">' + item.name + '</div>' +
                        '<div>' + categoryBadge + '</div>' +
                    '</td>' +
                    '<td><span class="text-muted">' + item.purpose + '</span></td>' +
                    '<td><code class="font-monospace text-break" style="font-size: 11px;">' + item.path + '</code></td>' +
                    '<td><span class="fw-bold">' + (item.exists ? item.size_formatted : '<span class="text-muted">0 B</span>') + '</span></td>' +
                    '<td>' + badgeStatus + '</td>' +
                    '<td class="text-end">' + actionBtn + '</td>' +
                '</tr>';
            });

            $('#tbodyModelInventory').html(html);
        }

        function loadModelInventory() {
            $.get(BASE_URL + 'admin/ml/models-inventory', function(res) {
                if (res.status === 'success') {
                    renderModelInventory(res);
                } else {
                    $('#tbodyModelInventory').html('<tr><td colspan="6" class="text-center py-4 text-danger">' + (res.message || 'Error fetching inventory') + '</td></tr>');
                }
            }).fail(function(xhr) {
                $('#tbodyModelInventory').html('<tr><td colspan="6" class="text-center py-4 text-danger">Cannot connect to ML service (HTTP ' + xhr.status + '). Verify container is running.</td></tr>');
            });
        }

        // Initial inventory load
        loadModelInventory();

        // Refresh inventory button
        $('#btnRefreshInventory').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Refreshing...');
            $.get(BASE_URL + 'admin/ml/models-inventory', function(res) {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Refresh');
                if (res.status === 'success') {
                    renderModelInventory(res);
                    showToast('Model inventory refreshed.', 'success');
                } else {
                    showToast(res.message, 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-1"></i> Refresh');
                showToast('Failed to refresh inventory: HTTP ' + xhr.status, 'danger');
            });
        });

        // Download single group
        $(document).on('click', '.btn-download-group', function() {
            var btn = $(this);
            var group = btn.data('group');
            var originalHtml = btn.html();

            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Downloading...');
            showToast('Downloading model files in background and loading into RAM...', 'info');

            $.post(BASE_URL + 'admin/ml/models-download', { group: group }, function(res) {
                btn.prop('disabled', false).html(originalHtml);
                if (res.status === 'success' || res.status === 'partial_error') {
                    renderModelInventory(res);
                    showToast('Model files downloaded and loaded into memory successfully!', 'success');
                } else {
                    showToast(res.message || 'Download failed', 'danger');
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP ' + xhr.status;
                showToast('Download error: ' + err, 'danger');
            });
        });

        // Force download all models
        $('#btnDownloadAllModels').on('click', function() {
            promptConfirmation(
                "Download All Missing Models?",
                "This will check all registered models (InsightFace pack, YOLOv8, and CLIP), download any missing weights from HuggingFace and GitHub releases, and load them into memory.",
                function() {
                    var btn = $('#btnDownloadAllModels');
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Downloading All...');
                    showToast('Downloading all missing models in background. This may take 1-2 minutes...', 'info');

                    $.post(BASE_URL + 'admin/ml/models-download', { group: 'all' }, function(res) {
                        btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Force Download All Missing');
                        if (res.status === 'success' || res.status === 'partial_error') {
                            renderModelInventory(res);
                            showToast('All models downloaded and loaded into memory successfully!', 'success');
                        } else {
                            showToast(res.message || 'Download failed', 'danger');
                        }
                    }).fail(function(xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-cloud-arrow-down-fill me-1"></i> Force Download All Missing');
                        var err = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP ' + xhr.status;
                        showToast('Download error: ' + err, 'danger');
                    });
                }
            );
        });

        // ── Deep Diagnostics & Failure Analysis ────────────────────
        function loadDiagnostics() {
            $('#diagnosticsLoading').show();
            $('#diagnosticsContent').addClass('d-none');
            $.getJSON(BASE_URL + 'admin/ml/diagnostics', function(res) {
                $('#diagnosticsLoading').hide();
                $('#diagnosticsContent').removeClass('d-none');
                if (res.status === 'success') {
                    // Photo access
                    var test = res.photo_access_test || {};
                    if (test.accessible) {
                        $('#diagPhotoAccessStatus').html('<span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i> Reachable (HTTP ' + test.status_code + ')</span> ' + (test.message || ''));
                    } else {
                        $('#diagPhotoAccessStatus').html('<span class="badge bg-danger text-white"><i class="bi bi-x-circle me-1"></i> Unreachable</span> ' + (test.message || 'Cannot reach WebApp'));
                    }
                    $('#diagPhotoAccessUrl').text('Target: ' + (test.configured_webapp_url || 'Not configured'));

                    // Concurrency
                    var workers = (res.queue && res.queue.num_workers) ? res.queue.num_workers : 4;
                    $('#diagWorkerInfo').html('<span class="badge bg-primary text-white">' + workers + ' Concurrent Workers</span> Active in pool');

                    // Errors
                    var errs = res.recent_errors || [];
                    $('#diagErrorCount').text(errs.length);
                    if (errs.length > 0) {
                        var eHtml = '';
                        errs.forEach(function(e) {
                            eHtml += '<tr>' +
                                '<td><code>#' + (e.photo_id || '-') + '</code></td>' +
                                '<td class="text-danger font-monospace extra-small text-break">' + (e.error || 'Unknown error') + '</td>' +
                                '<td class="text-muted extra-small">' + (e.failed_at || '-') + '</td>' +
                            '</tr>';
                        });
                        $('#tbodyDiagErrors').html(eHtml);
                    } else {
                        $('#tbodyDiagErrors').html('<tr><td colspan="3" class="text-center py-3 text-success"><i class="bi bi-check-circle-fill me-1"></i> No recent errors recorded. All scans healthy.</td></tr>');
                    }
                } else {
                    $('#tbodyDiagErrors').html('<tr><td colspan="3" class="text-center py-3 text-danger">Diagnostics error: ' + (res.message || 'Failed') + '</td></tr>');
                }
            }).fail(function(xhr) {
                $('#diagnosticsLoading').hide();
                $('#diagnosticsContent').removeClass('d-none');
                $('#tbodyDiagErrors').html('<tr><td colspan="3" class="text-center py-3 text-danger">Failed to connect to ML diagnostics endpoint (HTTP ' + xhr.status + ').</td></tr>');
            });
        }
        $('#btnRefreshDiagnostics').on('click', loadDiagnostics);
        loadDiagnostics();
    });
</script>
<?php $this->endSection() ?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h2 class="h4 mb-0" style="color: var(--text-primary);">System Diagnostics & Health</h2>
            <p class="text-muted small mb-0">Monitor platform runtime integrations, verify external cluster reachability, and test active ML networks.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary rounded-pill px-4" id="btnRunAllDiagnostics">
                <i class="bi bi-heart-pulse me-1"></i> Run Complete Diagnostic
            </button>
        </div>
    </div>

    <!-- Health Grid -->
    <div class="row g-4">
        <!-- MySQL Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-mysql" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-primary bg-opacity-10 rounded text-primary">
                        <i class="bi bi-database fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">MySQL Database</h6>
                <p class="text-muted small mb-3">Persistent storage layer housing all image records, face encoding records, metadata properties, and user scopes.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to run query diagnostics.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="mysql">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>

        <!-- phpMyAdmin Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-phpmyadmin" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-info bg-opacity-10 rounded text-info">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">phpMyAdmin Console</h6>
                <p class="text-muted small mb-3">Web-based administration tool for quick direct manipulation, backup creation, and inspection of database records.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to verify web portal response.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="phpmyadmin">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>

        <!-- FastAPI ML Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-ml" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-warning bg-opacity-10 rounded text-warning">
                        <i class="bi bi-cpu fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">FastAPI ML Engine</h6>
                <p class="text-muted small mb-3">Core Python microservice orchestrating face recognition models, scene categorizations, and vector conversions.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to poll service health route.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="ml">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>

        <!-- Qdrant Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-qdrant" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-success bg-opacity-10 rounded text-success">
                        <i class="bi bi-hdd-network fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">Qdrant Vector Database</h6>
                <p class="text-muted small mb-3">Multi-dimensional search index housing facial geometry projections and CLIP semantic search embeddings.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to evaluate collection scroll.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="qdrant">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>

        <!-- CLIP Model Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-clip" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-secondary bg-opacity-10 rounded text-secondary">
                        <i class="bi bi-translate fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">CLIP Semantic Transformer</h6>
                <p class="text-muted small mb-3">Zero-shot multimodal model processing image-to-text queries for natural language photo searching.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to verify memory allocation.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="clip">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>

        <!-- YOLOv8 Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-card p-4 h-100 service-card" id="card-yolo" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="p-3 bg-danger bg-opacity-10 rounded text-danger">
                        <i class="bi bi-eye fs-4"></i>
                    </div>
                    <span class="badge bg-secondary status-badge rounded-pill">PENDING TEST</span>
                </div>
                <h6 class="fw-bold mb-1">YOLOv8 Object Detector</h6>
                <p class="text-muted small mb-3">Computer vision model running dynamic label classification to recognize cats, cars, and other items on upload.</p>
                <div class="console-box rounded p-3 mb-3 bg-light bg-opacity-50 small border font-monospace text-muted" style="min-height: 80px; max-height: 120px; overflow-y: auto;">
                    Click test to verify weights loading.
                </div>
                <button class="btn btn-outline-primary btn-sm w-100 rounded-pill mt-auto btn-test-service" data-service="yolo">
                    <i class="bi bi-play-circle me-1"></i> Test Service
                </button>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        function testService(serviceName, cardId) {
            var card = $(cardId);
            var badge = card.find('.status-badge');
            var consoleBox = card.find('.console-box');
            var btn = card.find('.btn-test-service');

            badge.removeClass('bg-secondary bg-success bg-danger bg-warning')
                 .addClass('bg-warning text-dark')
                 .text('TESTING...');
            consoleBox.removeClass('text-muted text-success text-danger text-warning')
                      .addClass('text-muted')
                      .text('Executing diagnostic request...');
            btn.prop('disabled', true);

            $.post(BASE_URL + 'admin/health/test', { service: serviceName }, function(res) {
                btn.prop('disabled', false);
                if (res.status === 'success') {
                    badge.removeClass('bg-warning text-dark').addClass('bg-success').text('OPERATIONAL');
                    consoleBox.removeClass('text-muted').addClass('text-success').text(res.message);
                } else if (res.status === 'warning') {
                    badge.removeClass('bg-warning text-dark').addClass('bg-warning text-dark').text('STANDBY');
                    consoleBox.removeClass('text-muted').addClass('text-warning text-dark').text(res.message);
                } else {
                    badge.removeClass('bg-warning text-dark').addClass('bg-danger').text('FAILING');
                    consoleBox.removeClass('text-muted').addClass('text-danger').text(res.message);
                }
            }).fail(function(xhr) {
                btn.prop('disabled', false);
                badge.removeClass('bg-warning text-dark').addClass('bg-danger').text('ERROR');
                var errMsg = xhr.responseJSON ? xhr.responseJSON.message : 'HTTP status ' + xhr.status;
                consoleBox.removeClass('text-muted').addClass('text-danger').text('Check failed: ' + errMsg);
            });
        }

        $('.btn-test-service').on('click', function() {
            var service = $(this).data('service');
            var cardId = '#card-' + service;
            testService(service, cardId);
        });

        $('#btnRunAllDiagnostics').on('click', function() {
            var services = ['mysql', 'phpmyadmin', 'ml', 'qdrant', 'clip', 'yolo'];
            services.forEach(function(s) {
                testService(s, '#card-' + s);
            });
        });
    });
</script>
<?php $this->endSection() ?>

<?php
$current_uri = uri_string();
?>
<div class="card border-0 shadow-sm rounded-card overflow-hidden" style="background: var(--card-bg);">
    <div class="list-group list-group-flush settings-tabs" id="settingsTabs">
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/profile' || $current_uri === 'settings') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/profile') ?>">
            <i class="bi bi-person-circle fs-5"></i>
            <span>Profile</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/security') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/security') ?>">
            <i class="bi bi-shield-lock fs-5"></i>
            <span>Security &amp; Logs</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/preferences') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/preferences') ?>">
            <i class="bi bi-sliders fs-5"></i>
            <span>Preferences</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/storage') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/storage') ?>">
            <i class="bi bi-cloud-check fs-5"></i>
            <span>Storage</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/ml') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/ml') ?>">
            <i class="bi bi-cpu fs-5"></i>
            <span>ML / Face Recognition</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/export') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/export') ?>">
            <i class="bi bi-download fs-5"></i>
            <span>Export Data</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/access-tokens') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3" href="<?= base_url('settings/access-tokens') ?>">
            <i class="bi bi-key fs-5"></i>
            <span>Access Tokens</span>
        </a>
        <a class="list-group-item list-group-item-action <?= ($current_uri === 'settings/danger') ? 'active' : '' ?> p-3 border-0 d-flex align-items-center gap-3 text-danger" href="<?= base_url('settings/danger') ?>">
            <i class="bi bi-exclamation-triangle fs-5"></i>
            <span>Danger Zone</span>
        </a>
    </div>
</div>

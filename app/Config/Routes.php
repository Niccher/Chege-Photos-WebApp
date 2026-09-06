<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// All app routes require an authenticated session
// API Auth
$routes->get('test-ping', 'TestAuth::ping', ['filter' => null]);
$routes->get('api/v1/test', function() { return 'API is working'; });
$routes->get('api/v1/test2', 'Api\TestController::counts');
$routes->post('api/v1/login', 'Api\Auth::login');
$routes->post('api/v1/auth-with-token', 'Api\Auth::authWithToken');

// Public pages (no auth required)
$routes->get('/', 'Home::index');
$routes->get('about', 'Home::about');
$routes->get('android', 'Home::android');
$routes->get('ml', 'Home::ml');
$routes->get('setup', 'Home::setup');
$routes->get('faq', 'Home::faq');

// Web-only face action endpoints — inside main chain group (now versioned v1)
$routes->post('api/v1/faces/scan/(:num)', '\App\Controllers\Faces::apiScan/$1', ['filter' => 'chain']);
$routes->post('api/v1/faces/scan-all',    '\App\Controllers\Faces::apiScanAll', ['filter' => 'chain']);
$routes->post('api/v1/faces/cluster',     '\App\Controllers\Faces::apiCluster', ['filter' => 'chain']);
$routes->post('api/v1/faces/reset',       '\App\Controllers\Faces::apiResetScans', ['filter' => 'chain']);
$routes->post('api/v1/faces/force-scan',  '\App\Controllers\Faces::apiForceScanAll', ['filter' => 'chain']);
$routes->get('api/v1/faces/scan-job/(:num)', '\App\Controllers\Faces::apiScanJobStatus/$1', ['filter' => 'chain']);
$routes->post('api/v1/faces/update-metadata', '\App\Controllers\Faces::apiUpdateFaceMetadata', ['filter' => 'chain']);
$routes->post('api/v1/faces/search',      '\App\Controllers\Faces::apiSearch', ['filter' => 'chain']);

// Versioned Admin Storage Config API routes (web session auth)
$routes->post('api/v1/admin/storage/save',        '\App\Controllers\Admin::saveStorageSettings', ['filter' => 'chain']);
$routes->post('api/v1/admin/storage/wipe-system', '\App\Controllers\Admin::wipeSystem', ['filter' => 'chain']);
$routes->post('api/v1/admin/storage/reset-data',  '\App\Controllers\Admin::resetDataKeepUsers', ['filter' => 'chain']);
$routes->post('api/v1/admin/storage/purge-media', '\App\Controllers\Admin::purgeMediaKeepTokens', ['filter' => 'chain']);
$routes->post('admin/storage/purge-media',        '\App\Controllers\Admin::purgeMediaKeepTokens', ['filter' => 'chain']);
$routes->post('api/v1/admin/storage/empty-trash', '\App\Controllers\Admin::emptyTrashAll', ['filter' => 'chain']);

// API Data Endpoints (token auth for Android app)
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->get('config', 'ApiController::config');
    $routes->get('photos', 'ApiController::index', ['filter' => 'tokens:photos:read']);
    $routes->get('albums', 'ApiController::albums', ['filter' => 'tokens:photos:read']);
    $routes->get('albums/(:num)/photos', 'ApiController::albumPhotos/$1', ['filter' => 'tokens:photos:read']);
    $routes->post('albums', 'ApiController::createAlbum', ['filter' => 'tokens:photos:write']);
    $routes->put('albums/(:num)', 'ApiController::updateAlbum/$1', ['filter' => 'tokens:photos:write']);
    $routes->delete('albums/(:num)', 'ApiController::deleteAlbum/$1', ['filter' => 'tokens:photos:write']);
    $routes->post('upload', '\App\Controllers\Photos::upload', ['filter' => 'tokens:photos:write']);
    $routes->post('photos/check-hashes', 'ApiController::checkHashes', ['filter' => 'tokens:photos:read']);
    $routes->post('photos/exists-by-hash', 'ApiController::checkHashes', ['filter' => 'tokens:photos:read']);
    $routes->get('memories', 'ApiController::memories', ['filter' => 'tokens:photos:read']);
    $routes->get('favorites', 'ApiController::favorites', ['filter' => 'tokens:photos:read']);
    $routes->get('archive', 'ApiController::archive', ['filter' => 'tokens:photos:read']);
    $routes->get('trash', 'ApiController::trash', ['filter' => 'tokens:photos:read']);
    $routes->get('explore', 'ApiController::explore', ['filter' => 'tokens:photos:read']);

    // Mapped photo action API endpoints under tokens authentication for Android
    $routes->post('photos/delete/(:num)',  '\App\Controllers\Photos::deletePhoto/$1', ['filter' => 'tokens:photos:write']);
    $routes->post('photos/restore/(:num)', '\App\Controllers\Photos::restorePhoto/$1', ['filter' => 'tokens:photos:write']);
    $routes->post('photos/archive/(:num)', '\App\Controllers\Photos::archivePhoto/$1', ['filter' => 'tokens:photos:write']);
    $routes->post('photos/favorite/(:num)','\App\Controllers\Photos::toggleFavorite/$1', ['filter' => 'tokens:photos:write']);
    $routes->post('photos/empty-trash',    '\App\Controllers\Photos::emptyTrash', ['filter' => 'tokens:photos:write']);
    $routes->post('albums/add-photo',      '\App\Controllers\Photos::addPhotoToAlbum', ['filter' => 'tokens:photos:write']);

    // Face API endpoints (Android — token auth)
    $routes->get('faces/(:num)',       '\App\Controllers\Faces::apiFaces/$1', ['filter' => 'tokens:photos:read']);
    $routes->get('faces/persons',      '\App\Controllers\Faces::apiPersons', ['filter' => 'tokens:photos:read']);
    $routes->get('faces/unassigned',   '\App\Controllers\Faces::apiUnassigned', ['filter' => 'tokens:faces:write']);
    $routes->get('faces/by-person/(:num)', '\App\Controllers\Faces::apiPersonPhotos/$1', ['filter' => 'tokens:photos:read']);
    $routes->post('faces/persons/name/(:num)', '\App\Controllers\Faces::apiNamePerson/$1', ['filter' => 'tokens:faces:write']);
    $routes->post('faces/persons/merge',       '\App\Controllers\Faces::apiMergePersons', ['filter' => 'tokens:faces:write']);
    $routes->post('faces/assign-face',         '\App\Controllers\Faces::apiAssignFaceToPerson', ['filter' => 'tokens:faces:write']);
    $routes->post('faces/update-metadata',     '\App\Controllers\Faces::apiUpdateFaceMetadata', ['filter' => 'tokens:faces:write']);
    $routes->post('faces/bulk-scan',   '\App\Controllers\Faces::apiBulkScan', ['filter' => 'tokens:faces:write']);

    // Mobile Vault endpoints (Token auth)
    $routes->post('vault/unlock',      '\App\Controllers\Vault::unlock',  ['filter' => 'tokens:photos:read']);
    $routes->post('vault/move',        '\App\Controllers\Vault::move',    ['filter' => 'tokens:photos:write']);
    $routes->post('vault/restore',     '\App\Controllers\Vault::restore', ['filter' => 'tokens:photos:write']);
});

// All app routes require an authenticated session or token
$routes->group('', ['filter' => 'chain'], function ($routes) {
    // Private Locked Vault routes
    $routes->get('vault', 'Vault::index');
    $routes->post('vault/setup-pin', 'Vault::setupPin');
    $routes->post('vault/unlock', 'Vault::unlock');
    $routes->post('vault/lock', 'Vault::lock');
    $routes->post('vault/move', 'Vault::move');
    $routes->post('vault/restore', 'Vault::restore');
    $routes->post('vault/delete', 'Vault::delete');
    $routes->post('vault/unflag', 'Vault::unflag');
    $routes->post('vault/bulk-unflag', 'Vault::bulkUnflag');
    $routes->get('vault/photo-reason/(:num)', 'Vault::photoReason/$1');
    $routes->post('vault/hide-person', 'Vault::hidePerson');
    $routes->post('vault/restore-person', 'Vault::restorePerson');
    $routes->get('vault/persons', 'Vault::persons');
    $routes->get('vault/media/(:num)', 'Vault::media/$1');

    $routes->get('photos', 'Photos::index');
    $routes->get('backfill-exif', 'Photos::backfillExif');
    $routes->get('faces',          'Faces::index');
    $routes->get('faces/scan-status', 'Faces::apiScanStatus');
    $routes->get('faces/person/(:num)', 'Faces::personPhotos/$1');
    $routes->get('faces/(:num)',        'Faces::personPhotos/$1');
    $routes->get('faces/photo/(:num)', 'Faces::photo/$1');
    $routes->get('faces/unassigned', 'Faces::apiUnassigned');
    $routes->post('faces/persons/merge', 'Faces::apiMergePersons');
    $routes->post('faces/persons/set-cover', 'Faces::apiSetPersonCover');
    $routes->post('faces/detach', 'Faces::apiDetachFace');
    $routes->post('faces/assign-face', 'Faces::apiAssignFaceToPerson');
    $routes->post('faces/bulk-assign', 'Faces::apiBulkAssign');
    $routes->post('faces/persons/name/(:num)', 'Faces::apiNamePerson/$1');
    $routes->get('photos/(:num)/similar', 'Photos::apiSimilar/$1');
    $routes->get('tools/duplicates', 'Duplicates::index');
    $routes->post('tools/duplicates/trash', 'Duplicates::apiTrashDuplicate');
    $routes->post('tools/duplicates/auto-clean', 'Duplicates::apiAutoClean');
    $routes->post('photos/tags/add', 'Photos::addTag');
    $routes->post('photos/tags/remove', 'Photos::removeTag');
    $routes->post('upload', 'Photos::upload');
    $routes->get('explore', 'Photos::explore');
    $routes->get('map',     'Photos::map');
    $routes->get('sharing', 'Photos::sharing');
    $routes->get('analytics', 'Photos::analytics');
    $routes->get('archive', 'Photos::archive');
    $routes->get('trash', 'Photos::trash');

    // Photo action API
    $routes->post('photos/archive/(:num)', 'Photos::archivePhoto/$1');
    $routes->post('photos/delete/(:num)',  'Photos::deletePhoto/$1');
    $routes->post('photos/restore/(:num)', 'Photos::restorePhoto/$1');
    $routes->post('photos/save-edit/(:num)', 'Photos::saveEdit/$1');
    $routes->post('photos/empty-trash', 'Photos::emptyTrash');

    // Sharing API
    $routes->post('photos/share/(:num)',   'Photos::sharePhoto/$1');
    $routes->post('photos/unshare/(:num)', 'Photos::unsharePhoto/$1');
    $routes->post('photos/generate-link/(:num)', 'Photos::generateShareLink/$1');
    $routes->get('favorites',              'Photos::favorites');
    $routes->get('memories',               'Photos::memories');
    $routes->get('albums',                 'Photos::albums');
    $routes->get('albums/(:num)',          'Photos::viewAlbum/$1');
    $routes->get('albums/collection/(:segment)', 'Photos::smartCollection/$1');
    $routes->post('albums/create',         'Photos::createAlbum');
    $routes->post('albums/update-smart/(:num)', 'Photos::updateSmartAlbum/$1');
    $routes->post('albums/add-photo',      'Photos::addPhotoToAlbum');
    $routes->post('photos/favorite/(:num)','Photos::toggleFavorite/$1');
    $routes->post('bulk-action', 'Photos::bulkAction');

    $routes->get('users/search',           'Photos::searchUsers');

    // Duplicate Detection & Storage Reclaimer
    $routes->get('duplicates',              'Duplicates::index');
    $routes->post('duplicates/trash/(:num)','Duplicates::apiTrashDuplicate/$1');
    $routes->post('duplicates/autoclean',   'Duplicates::apiAutoClean');

    $routes->get('settings', 'Settings::index');
    $routes->get('settings/profile', 'Settings::profile');
    $routes->get('settings/security', 'Settings::security');
    $routes->get('settings/preferences', 'Settings::preferences');
    $routes->get('settings/storage', 'Settings::storage');
    $routes->get('settings/ml', 'Settings::ml');
    $routes->get('settings/notifications', 'Settings::notifications');
    $routes->get('settings/export', 'Settings::export');
    $routes->get('settings/access-tokens', 'Settings::accessTokens');
    $routes->get('settings/danger', 'Settings::danger');

    $routes->post('settings/notifications', 'Settings::updateNotifications');
    $routes->post('settings/preferences/density', 'Settings::updateDensity');
    $routes->post('settings/preferences/video-autoplay', 'Settings::updateVideoAutoplay');
    $routes->post('settings/ml/privacy', 'Settings::updateMlPrivacy');
    $routes->post('settings/storage/scan-clean', 'Settings::scanStorageCleanup');
    $routes->post('settings/storage/purge-duplicates', 'Settings::purgeDuplicates');

    $routes->post('settings/profile', 'Settings::updateProfile');
    $routes->post('settings/avatar', 'Settings::updateAvatar');
    $routes->post('settings/avatar/remove', 'Settings::removeAvatar');
    $routes->post('settings/password', 'Settings::updatePassword');
    $routes->post('settings/theme', 'Settings::updateTheme');
    $routes->post('settings/clear-data', 'Settings::clearData');
    $routes->post('settings/delete-account', 'Settings::deleteAccount');
    $routes->post('settings/export', 'Settings::exportData');
    $routes->get('settings/download-export/(:any)', 'Settings::downloadExport/$1');
    $routes->post('settings/refresh-metadata', 'Settings::refreshMetadata');
    $routes->get('settings/tokens', 'Tokens::index');
    $routes->post('settings/tokens/generate', 'Tokens::generate');
    $routes->post('settings/tokens/revoke', 'Tokens::revoke');
    $routes->post('settings/tokens/revoke-device', 'Tokens::revokeDevice');
    $routes->get('settings/tokens/qr/(:any)', 'Tokens::qr/$1');
    $routes->get('settings/ml-status', 'Settings::mlStatus');
});

// Admin Console Routes (restricted to superadmin group)
$routes->group('admin', ['filter' => 'group:superadmin'], function ($routes) {
    $routes->get('home', 'Admin::home');
    $routes->get('settings', 'Admin::settings');
    $routes->post('settings/save', 'Admin::saveSettings');
    $routes->get('users', 'Admin::users');
    
    // User Management Actions
    $routes->post('users/update-role', 'Admin::updateRole');
    $routes->post('users/purge', 'Admin::purgeUserData');
    $routes->post('users/delete', 'Admin::deleteUser');
    $routes->post('users/toggle-status', 'Admin::toggleUserStatus');
    
    // SMTP Configurations
    $routes->get('smtp', 'Admin::smtp');
    $routes->post('smtp/save', 'Admin::saveSmtp');
    $routes->post('smtp/test', 'Admin::testEmail');
    $routes->post('smtp/verify-event', 'Admin::verifyEventEmail');
    $routes->get('sent-mails', 'Admin::sentMails');
    $routes->get('trigger-events', 'Admin::triggerEvents');

    // ML Configurations & Triggers
    $routes->get('ml', 'Admin::ml');
    $routes->get('ml/stats', 'Admin::mlStats');
    $routes->post('ml/save', 'Admin::saveMlSettings');
    $routes->post('ml/reset', 'Admin::resetMl');
    $routes->post('ml/cluster', 'Admin::triggerCluster');
    $routes->post('ml/sweep', 'Admin::triggerSweep');
    $routes->post('ml/rescan', 'Admin::rescan');
    $routes->post('ml/regenerate-key', 'Admin::regenerateApiKey');
    $routes->post('ml/autotune', 'Admin::autotuneMl');
    $routes->post('ml/simulate', 'Admin::simulateClustering');
    $routes->get('ml/models-inventory', 'Admin::modelsInventory');
    $routes->post('ml/models-download', 'Admin::downloadModel');
    $routes->post('ml/test-connection', 'Admin::testMlConnection');
    $routes->get('ml/diagnostics',      'Admin::mlDiagnostics');
    $routes->post('ml/reap-stale',      'Admin::reapStaleScans');
    $routes->post('ml/retry-failed',    'Admin::retryFailedScans');
    $routes->post('ml/scan-nsfw',       'Admin::triggerNsfwScan');

    // Storage Configs
    $routes->get('storage', 'Admin::storage');
    $routes->post('storage/save-gcp', 'Admin::saveGcpSettings');
    $routes->post('storage/test-gcp', 'Admin::testGcpConnection');
    $routes->post('storage/trigger-backup', 'Admin::triggerCloudBackup');
    $routes->post('storage/sync-media', 'Admin::triggerMediaSync');
    $routes->post('storage/sync-pending', 'Admin::triggerMediaSyncPending');
    $routes->post('storage/hydrate-media', 'Admin::triggerMediaHydrate');
    $routes->post('storage/prune-cache', 'Admin::triggerPruneCache');

    // Audits and Devices
    $routes->get('audits', 'Admin::audits');
    $routes->get('devices', 'Admin::devices');

    // Cron Jobs History & Configs
    $routes->get('crons', 'Admin::crons');
    $routes->post('crons/save', 'Admin::saveCronSettings');
    $routes->post('crons/run-job', 'Admin::runCronJob');
    $routes->post('crons/run-all', 'Admin::runAllCronJobs');

    // Health Diagnostics Dashboard
    $routes->get('health', 'Admin::health');
    $routes->post('health/test', 'Admin::testService');

    // Container Telemetry Dashboard
    $routes->get('telemetry', 'Admin::telemetry');
    $routes->get('telemetry/stats', 'Admin::telemetryStats');
});

// Public Sharing Routes
$routes->match(['get', 'post'], 's/(:any)', 'Photos::viewShared/$1');

// Media Fallback & Hybrid GCP Storage Hydration (Handles ephemeral Railway container restarts)
$routes->get('uploads/(:any)', 'MediaFallback::serveUpload/$1');
$routes->get('thumbnails/(:any)', 'MediaFallback::serveThumbnail/$1');

// Shield's auth routes (login, register, magic-link, etc.)
service('auth')->routes($routes);

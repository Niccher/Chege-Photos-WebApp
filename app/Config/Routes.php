<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// All app routes require an authenticated session
// API Auth
// API Auth
$routes->get('test-ping', 'TestAuth::ping', ['filter' => null]);
$routes->get('api/test', function() { return 'API is working'; });
$routes->get('api/test2', 'Api\TestController::counts');
$routes->post('api/login', 'Api\Auth::login');
$routes->post('api/auth-with-token', 'Api\Auth::authWithToken');

// Public pages (no auth required)
$routes->get('/', 'Home::index');
$routes->get('about', 'Home::about');
$routes->get('android', 'Home::android');
$routes->get('ml', 'Home::ml');
$routes->get('setup', 'Home::setup');
$routes->get('faq', 'Home::faq');

// Web-only face action endpoints — inside main chain group
$routes->post('api/faces/scan/(:num)', '\App\Controllers\Faces::apiScan/$1', ['filter' => 'chain']);
$routes->post('api/faces/scan-all',    '\App\Controllers\Faces::apiScanAll', ['filter' => 'chain']);
$routes->post('api/faces/cluster',     '\App\Controllers\Faces::apiCluster', ['filter' => 'chain']);
$routes->post('api/faces/reset',       '\App\Controllers\Faces::apiResetScans', ['filter' => 'chain']);
$routes->post('api/faces/force-scan',  '\App\Controllers\Faces::apiForceScanAll', ['filter' => 'chain']);
$routes->get('api/faces/scan-job/(:num)', '\App\Controllers\Faces::apiScanJobStatus/$1', ['filter' => 'chain']);
$routes->post('api/faces/search',      '\App\Controllers\Faces::apiSearch', ['filter' => 'chain']);

// API Data Endpoints (token auth for Android app)
$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'tokens'], function ($routes) {
    $routes->get('photos', 'ApiController::index');
    $routes->get('albums', 'ApiController::albums');
    $routes->get('albums/(:num)/photos', 'ApiController::albumPhotos/$1');
    $routes->post('albums', 'ApiController::createAlbum');
    $routes->put('albums/(:num)', 'ApiController::updateAlbum/$1');
    $routes->delete('albums/(:num)', 'ApiController::deleteAlbum/$1');
    $routes->post('upload', '\App\Controllers\Photos::upload');
    $routes->get('memories', 'ApiController::memories');
    $routes->get('favorites', 'ApiController::favorites');
    $routes->get('archive', 'ApiController::archive');
    $routes->get('trash', 'ApiController::trash');
    $routes->get('explore', 'ApiController::explore');

    // Face API endpoints (Android — token auth)
    $routes->get('faces/(:num)',       '\App\Controllers\Faces::apiFaces/$1');
    $routes->get('faces/persons',      '\App\Controllers\Faces::apiPersons');
    $routes->get('faces/unassigned',   '\App\Controllers\Faces::apiUnassigned');
    $routes->get('faces/by-person/(:num)', '\App\Controllers\Faces::apiPersonPhotos/$1');
    $routes->post('faces/persons/name/(:num)', '\App\Controllers\Faces::apiNamePerson/$1');
    $routes->post('faces/persons/merge',       '\App\Controllers\Faces::apiMergePersons');
    $routes->post('faces/bulk-scan',   '\App\Controllers\Faces::apiBulkScan');
});

// All app routes require an authenticated session or token
$routes->group('', ['filter' => 'chain'], function ($routes) {
    $routes->get('photos', 'Photos::index');
    $routes->get('scan', 'Photos::scan');
    $routes->get('backfill-exif', 'Photos::backfillExif');
    $routes->get('faces',          'Faces::index');
    $routes->get('faces/person/(:num)', 'Faces::personPhotos/$1');
    $routes->get('faces/photo/(:num)', 'Faces::photo/$1');
    $routes->post('upload', 'Photos::upload');
    $routes->get('explore', 'Photos::explore');
    $routes->get('sharing', 'Photos::sharing');
    $routes->get('analytics', 'Photos::analytics');
    $routes->get('archive', 'Photos::archive');
    $routes->get('trash', 'Photos::trash');

    // Photo action API
    $routes->post('photos/archive/(:num)', 'Photos::archivePhoto/$1');
    $routes->post('photos/delete/(:num)',  'Photos::deletePhoto/$1');
    $routes->post('photos/restore/(:num)', 'Photos::restorePhoto/$1');
    $routes->post('photos/save-edit/(:num)', 'Photos::saveEdit/$1');

    // Sharing API
    $routes->post('photos/share/(:num)',   'Photos::sharePhoto/$1');
    $routes->post('photos/unshare/(:num)', 'Photos::unsharePhoto/$1');
    $routes->post('photos/generate-link/(:num)', 'Photos::generateShareLink/$1');
    $routes->get('favorites',              'Photos::favorites');
    $routes->get('memories',               'Photos::memories');
    $routes->get('albums',                 'Photos::albums');
    $routes->get('albums/(:num)',          'Photos::viewAlbum/$1');
    $routes->post('albums/create',         'Photos::createAlbum');
    $routes->post('albums/update-smart/(:num)', 'Photos::updateSmartAlbum/$1');
    $routes->post('albums/add-photo',      'Photos::addPhotoToAlbum');
    $routes->post('photos/favorite/(:num)','Photos::toggleFavorite/$1');
    $routes->post('bulk-action', 'Photos::bulkAction');

    $routes->get('users/search',           'Photos::searchUsers');

    $routes->get('settings', 'Settings::index');
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
    $routes->get('settings/tokens/qr/(:any)', 'Tokens::qr/$1');
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
    
    // SMTP Configurations
    $routes->get('smtp', 'Admin::smtp');
    $routes->post('smtp/save', 'Admin::saveSmtp');
    $routes->post('smtp/test', 'Admin::testEmail');

    // ML Configurations & Triggers
    $routes->get('ml', 'Admin::ml');
    $routes->post('ml/save', 'Admin::saveMlSettings');
    $routes->post('ml/reset', 'Admin::resetMl');
    $routes->post('ml/cluster', 'Admin::triggerCluster');

    // Storage Configs
    $routes->get('storage', 'Admin::storage');
    $routes->post('storage/save', 'Admin::saveStorageSettings');

    // Cron Jobs History & Configs
    $routes->get('crons', 'Admin::crons');
    $routes->post('crons/save', 'Admin::saveCronSettings');
});

// Public Sharing Routes
$routes->get('s/(:any)', 'Photos::viewShared/$1');

// Shield's auth routes (login, register, magic-link, etc.)
service('auth')->routes($routes);

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
$routes->get('api/test2', 'Api\TestController::index');
$routes->post('api/login', 'Api\Auth::login');

// API Data Endpoints
$routes->group('api', ['namespace' => 'App\Controllers\Api', 'filter' => 'tokens'], function ($routes) {
    $routes->get('photos', 'ApiController::index');
    $routes->get('albums', 'ApiController::albums');
    $routes->get('albums/(:num)/photos', 'ApiController::albumPhotos/$1');
    $routes->post('upload', '\App\Controllers\Photos::upload');
    $routes->get('memories', 'ApiController::memories');
    $routes->get('favorites', 'ApiController::favorites');
    $routes->get('archive', 'ApiController::archive');
    $routes->get('trash', 'ApiController::trash');
    $routes->get('explore', 'ApiController::explore');
});

// All app routes require an authenticated session or token
$routes->group('', ['filter' => 'chain'], function ($routes) {
    $routes->get('/', 'Photos::index');
    $routes->get('scan', 'Photos::scan');
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
});

// Public Sharing Routes
$routes->get('s/(:any)', 'Photos::viewShared/$1');

// Shield's auth routes (login, register, magic-link, etc.)
service('auth')->routes($routes);

<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::login');
$routes->match(['GET', 'POST'], 'login', 'Auth::login');
$routes->get('logout', 'Auth::logout');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('admin/users', 'Admin::users');
$routes->match(['GET', 'POST'], 'admin/addUser', 'Admin::addUser');
$routes->match(['GET', 'POST'], 'admin/adduser', 'Admin::addUser'); // Support lowercase URL
$routes->match(['GET', 'POST'], 'admin/editUser/(:num)', 'Admin::editUser/$1');
$routes->get('admin/deleteUser/(:num)', 'Admin::deleteUser/$1');
$routes->get('roles', 'Role::index');
$routes->match(['GET', 'POST'], 'roles/add', 'Role::add');
$routes->match(['GET', 'POST'], 'roles/edit/(:num)', 'Role::edit/$1');
$routes->get('roles/delete/(:num)', 'Role::delete/$1');
$routes->get('settings/permissions', 'Settings::permissions');
$routes->post('settings/togglePermission', 'Settings::togglePermission');
$routes->get('settings/managePermissions', 'Settings::managePermissions');
$routes->post('settings/addPermission', 'Settings::addPermission');
$routes->get('settings/deletePermission/(:num)', 'Settings::deletePermission/$1');

// Access Control – Blacklist
$routes->get('access/blacklist', 'AccessControl::blacklist');
$routes->match(['GET', 'POST'], 'access/blacklist/add', 'AccessControl::addBlacklist');
$routes->match(['GET', 'POST'], 'access/blacklist/edit/(:num)', 'AccessControl::editBlacklist/$1');
$routes->get('access/blacklist/delete/(:num)', 'AccessControl::deleteBlacklist/$1');

// Access Control – Whitelist
$routes->get('access/whitelist', 'AccessControl::whitelist');
$routes->match(['GET', 'POST'], 'access/whitelist/add', 'AccessControl::addWhitelist');
$routes->get('access/whitelist/delete/(:num)', 'AccessControl::deleteWhitelist/$1');

// Access Control – Members
$routes->get('access/members', 'AccessControl::members');
$routes->match(['GET', 'POST'], 'access/members/add', 'AccessControl::addMember');
$routes->match(['GET', 'POST'], 'access/members/edit/(:num)', 'AccessControl::editMember/$1');
$routes->get('access/members/delete/(:num)', 'AccessControl::deleteMember/$1');
$routes->post('access/members/addVehicle/(:num)', 'AccessControl::addVehicle/$1');
$routes->get('access/members/deleteVehicle/(:num)', 'AccessControl::deleteVehicle/$1');

// Access Log & Gate Check (API endpoint for camera/ANPR system)
$routes->get('access/log', 'AccessControl::accessLog');
$routes->post('access/check', 'AccessControl::checkPlate');

// Cameras – management UI
$routes->get('access/cameras', 'Cameras::index');
$routes->match(['GET', 'POST'], 'access/cameras/add', 'Cameras::add');
$routes->match(['GET', 'POST'], 'access/cameras/edit/(:num)', 'Cameras::edit/$1');
$routes->get('access/cameras/delete/(:num)', 'Cameras::delete/$1');
$routes->get('access/cameras/events', 'Cameras::events');
$routes->get('access/cameras/events/poll', 'Cameras::eventsPoll');

// ANPR event receiver – called by Hikvision camera firmware
// URL to configure in camera: http://<server>/anpr/event/<camera_token>
$routes->post('anpr/event/(:any)', 'AnprReceiver::event/$1');

// ANPR snapshot viewer – serves images from writable/ securely (auth required)
$routes->get('anpr/snapshot', 'Snapshot::serve');

//ping route for testing camera connectivity
$routes->get('access/cameras/ping/(:num)', 'Cameras::pingCamera/$1');

//calulate downtime data for cameras
$routes->get('access/cameras/downtimeData', 'Cameras::downtimeData');

// Boom gate manual control (for testing/maintenance)
$routes->get('access/boomcontrol', 'BoomControl::index');
$routes->post('access/boomcontrol/trigger', 'BoomControl::trigger');

//Report routes
$routes->get('report/overview', 'ReportController::overview');

// App API routes
$routes->group('api', static function ($routes) {
    $routes->post('login', 'LoginApi::login');
    $routes->get('boom/cameras', 'BoomControlApi::getCameras');
    $routes->post('boom/trigger', 'BoomControlApi::trigger');
    $routes->post('permission/getUserPermissions', 'PermissionApi::getUserPermissions');

    $routes->post('visitor/add', 'VisitorApi::addVisitor');
    $routes->post('visitor/list', 'VisitorApi::getVisitors');

    $routes->post('realtime-stats', 'LoginApi::realtimeStats');
    $routes->post('refresh', 'LoginApi::refresh');

    $routes->post('dashboard/realtime-stats', 'Dashboard::realtimeStats');
    $routes->post('dashboard/downtime', 'Dashboard::downtime');
    $routes->post('dashboard/refresh', 'LoginApi::refresh');
});

$routes->get('test/telegram', 'Home::testTelegram');
$routes->get('test/camera-alert', 'Home::testCameraAlert');

/*
|--------------------------------------------------------------------------
| Hardware
|--------------------------------------------------------------------------
*/

$routes->group('hardware', function ($routes) {

    // Dashboard
    $routes->get('/', 'Hardware\Dashboard::index');

    // Devices
    $routes->get('devices', 'Hardware\Devices::index');
    $routes->get('devices/create', 'Hardware\Devices::create');
    $routes->post('devices/store', 'Hardware\Devices::store');

    $routes->get('devices/edit/(:num)', 'Hardware\Devices::edit/$1');
    $routes->post('devices/update/(:num)', 'Hardware\Devices::update/$1');

    $routes->get('devices/delete/(:num)', 'Hardware\Devices::delete/$1');

    // Diagnostics
    $routes->get('diagnostics/(:num)', 'Hardware\Diagnostics::index/$1');

    $routes->post('sendRaw', 'Hardware\Diagnostics::sendRaw');
    $routes->post('scanModules', 'Hardware\Diagnostics::scanModules');
    $routes->post('scanBaud', 'Hardware\Diagnostics::scanBaud');
    $routes->post('readConfig', 'Hardware\Diagnostics::readConfig');
    $routes->post('setRelay', 'Hardware\Diagnostics::setRelay');
    $routes->post('testRelay', 'Hardware\Diagnostics::testRelay');
    $routes->post('readRelayStatus', 'Hardware\Diagnostics::readRelayStatus');
    $routes->post('connectionStatus', 'Hardware\Diagnostics::connectionStatus');

    $routes->get('functions', 'Hardware\Functions::index');
    $routes->get('mapping', 'Hardware\Mapping::index');
    $routes->get('diagnostics', 'Hardware\Diagnostics::index');
    $routes->get('events', 'Hardware\Events::index');
    
    // Device CRUD
    $routes->post('devices/testConnection/(:num)', 'Hardware\Devices::testConnection/$1');

    $routes->get('dashboard/status', 'Dashboard::status');

    $routes->group('hardware/devices', function($routes){

    $routes->get('/', 'Hardware\Devices::index');

    $routes->get('create', 'Hardware\Devices::create');
    $routes->post('store', 'Hardware\Devices::store');

    $routes->get('edit/(:num)', 'Hardware\Devices::edit/$1');
    $routes->post('update/(:num)', 'Hardware\Devices::update/$1');

    $routes->post('delete/(:num)', 'Hardware\Devices::delete/$1');

    $routes->post('testConnection/(:num)', 'Hardware\Devices::testConnection/$1');

    $routes->get('diagnostics/(:num)', 'Hardware\Devices::diagnostics/$1');

});

});

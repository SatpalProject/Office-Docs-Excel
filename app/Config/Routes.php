<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public / Guest Authentication Routes
$routes->group('', ['filter' => 'guest'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::processLogin');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::processRegister');
});

// Authenticated Session Actions
$routes->match(['GET', 'POST'], 'logout', 'Auth::logout', ['filter' => 'auth']);

// Protected Workspace Routes
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');

    // Document Editor & Lifecycle Routes
    $routes->group('documents', static function ($routes) {
        $routes->match(['GET', 'POST'], 'create', 'Documents::create');
        $routes->get('edit/(:num)', 'Documents::edit/$1');
        $routes->post('save/(:num)', 'Documents::save/$1');
        $routes->post('rename/(:num)', 'Documents::rename/$1');
        $routes->post('delete/(:num)', 'Documents::delete/$1');
        $routes->get('export/html/(:num)', 'Documents::exportHtml/$1');
        $routes->get('export/text/(:num)', 'Documents::exportText/$1');
    });

    // Spreadsheet Editor & Lifecycle Routes
    $routes->group('spreadsheets', static function ($routes) {
        $routes->match(['GET', 'POST'], 'create', 'Spreadsheets::create');
        $routes->get('edit/(:num)', 'Spreadsheets::edit/$1');
        $routes->post('save/(:num)', 'Spreadsheets::save/$1');
        $routes->post('rename/(:num)', 'Spreadsheets::rename/$1');
        $routes->post('delete/(:num)', 'Spreadsheets::delete/$1');
        $routes->get('export/csv/(:num)', 'Spreadsheets::exportCsv/$1');
        $routes->get('export/xlsx/(:num)', 'Spreadsheets::exportXlsx/$1');
    });
});

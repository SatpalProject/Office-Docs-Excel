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
$routes->match(['get', 'post'], 'logout', 'Auth::logout', ['filter' => 'auth']);

// Protected Workspace Routes
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');
});

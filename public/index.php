<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and create the application...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Create the request...
$request = Request::capture();

// Handle the request and send the response...
$response = $app->handle($request);
$response->send();

// Terminate the application...
$app->terminate($request, $response);
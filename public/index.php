<?php

use App\Support\InstallerBootstrap;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

InstallerBootstrap::applyTemporaryAppKeyIfNeeded(
    dirname(__DIR__),
    __DIR__.'/../.env',
    __DIR__.'/../storage/app/installer/installed.lock',
);

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

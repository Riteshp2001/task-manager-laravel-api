<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Increase stack size for PHP 8.5+
if (version_compare(PHP_VERSION, '8.5.0', '>=')) {
    ini_set('zend.max_allowed_stack_size', '32M');
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../bootstrap/vercel_runtime.php';

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());

<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Check If Application Needs Installation
|--------------------------------------------------------------------------
|
| If the vendor directory doesn't exist, redirect to installation
|
*/

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    // Check if we're already in installation mode
    if (strpos($_SERVER['REQUEST_URI'], 'install') === false && 
        strpos($_SERVER['REQUEST_URI'], 'simple-install') === false) {
        header('Location: ../index.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

// Try to load Composer autoloader, fallback to simple autoloader
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    // Create a simple autoloader for basic functionality
    spl_autoload_register(function ($class) {
        $prefix = "App\\";
        $base_dir = __DIR__ . "/../app/";
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

// Simple request handling
$request = Request::capture();

// Basic routing
$uri = $request->getRequestUri();
$method = $request->getMethod();

// Handle different routes
if ($uri === '/' || $uri === '/dashboard') {
    // Dashboard route
    include __DIR__ . '/../resources/views/admin/dashboard.blade.php';
} elseif ($uri === '/login') {
    // Login route
    include __DIR__ . '/../resources/views/auth/login.blade.php';
} elseif ($uri === '/properties') {
    // Properties route
    include __DIR__ . '/../resources/views/admin/properties/index.blade.php';
} elseif ($uri === '/transactions') {
    // Transactions route
    include __DIR__ . '/../resources/views/admin/transactions/index.blade.php';
} else {
    // 404 Not Found
    http_response_code(404);
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The requested page could not be found.</p>';
    echo '<p><a href="/">Go to Dashboard</a></p>';
}
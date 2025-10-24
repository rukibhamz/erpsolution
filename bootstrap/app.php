<?php

// Simple bootstrap for Business Management System
// This provides basic functionality without requiring full Laravel framework

// Define constants
define('LARAVEL_START', microtime(true));
define('APP_PATH', dirname(__DIR__));

// Load environment variables
if (file_exists(APP_PATH . '/.env')) {
    $lines = file(APP_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Set default environment variables
$_ENV['APP_NAME'] = $_ENV['APP_NAME'] ?? 'Business Management System';
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'local';
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 'true';
$_ENV['APP_URL'] = $_ENV['APP_URL'] ?? 'http://localhost';

// Simple application class
class Application
{
    protected $basePath;
    protected $config = [];
    
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath ?: dirname(__DIR__);
        $this->loadConfig();
    }
    
    public function basePath($path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
    
    public function config($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    protected function loadConfig()
    {
        $configPath = $this->basePath . '/config';
        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');
            foreach ($files as $file) {
                $key = basename($file, '.php');
                $this->config[$key] = include $file;
            }
        }
    }
    
    public function make($abstract, $parameters = [])
    {
        // Simple service container
        if (class_exists($abstract)) {
            return new $abstract(...$parameters);
        }
        
        throw new Exception("Class {$abstract} not found");
    }
    
    public function version()
    {
        return '11.0.0';
    }
}

// Create application instance
$app = new Application();

// Return the application
return $app;
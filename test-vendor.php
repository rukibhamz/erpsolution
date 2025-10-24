<?php
/**
 * Test script to verify vendor directory is working
 */

echo "<h1>🧪 Vendor Directory Test</h1>";

// Test 1: Check if vendor directory exists
echo "<h2>✅ Test 1: Vendor Directory</h2>";
if (is_dir(__DIR__ . '/vendor')) {
    echo "<p style='color: green;'>✅ Vendor directory exists</p>";
} else {
    echo "<p style='color: red;'>❌ Vendor directory missing</p>";
}

// Test 2: Check if autoload.php exists
echo "<h2>✅ Test 2: Autoloader</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ Autoloader exists</p>";
    
    // Test 3: Try to load autoloader
    try {
        require_once __DIR__ . '/vendor/autoload.php';
        echo "<p style='color: green;'>✅ Autoloader loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Autoloader failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Autoloader missing</p>";
}

// Test 3: Check if bootstrap/app.php exists
echo "<h2>✅ Test 3: Bootstrap</h2>";
if (file_exists(__DIR__ . '/bootstrap/app.php')) {
    echo "<p style='color: green;'>✅ Bootstrap file exists</p>";
    
    // Test 4: Try to load bootstrap
    try {
        $app = require_once __DIR__ . '/bootstrap/app.php';
        echo "<p style='color: green;'>✅ Bootstrap loaded successfully</p>";
        echo "<p>Application version: " . $app->version() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Bootstrap failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Bootstrap file missing</p>";
}

// Test 4: Check if public/index.php exists
echo "<h2>✅ Test 4: Public Index</h2>";
if (file_exists(__DIR__ . '/public/index.php')) {
    echo "<p style='color: green;'>✅ Public index.php exists</p>";
} else {
    echo "<p style='color: red;'>❌ Public index.php missing</p>";
}

// Test 5: Check directory structure
echo "<h2>✅ Test 5: Directory Structure</h2>";
$directories = [
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'storage',
    'vendor'
];

foreach ($directories as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "<p style='color: green;'>✅ {$dir}/ directory exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$dir}/ directory missing</p>";
    }
}

// Test 6: Check essential files
echo "<h2>✅ Test 6: Essential Files</h2>";
$files = [
    'composer.json',
    'artisan',
    'public/index.php',
    'public/.htaccess',
    'bootstrap/app.php',
    'vendor/autoload.php'
];

foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✅ {$file} exists</p>";
    } else {
        echo "<p style='color: red;'>❌ {$file} missing</p>";
    }
}

echo "<h2>🎉 Test Complete!</h2>";
echo "<p>If all tests pass, your vendor directory is properly set up.</p>";
echo "<p><a href='public/index.php'>Try accessing the application</a></p>";
?>

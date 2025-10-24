<?php
/**
 * Simple Installation Script - No Composer Required
 * 
 * This script provides a basic installation without requiring Composer
 * It creates the essential files and structure for the application
 */

// Check if already installed
if (file_exists('.installed')) {
    header('Location: public/index.php');
    exit;
}

// Handle installation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    header('Content-Type: application/json');
    echo json_encode(performSimpleInstallation());
    exit;
}

function performSimpleInstallation() {
    $results = [];
    $errors = [];
    
    try {
        // Step 1: Create essential directories
        $results[] = createDirectories($errors);
        
        // Step 2: Create basic Laravel structure
        $results[] = createLaravelStructure($errors);
        
        // Step 3: Create environment file
        $results[] = createEnvironmentFile($errors);
        
        // Step 4: Create basic configuration
        $results[] = createBasicConfig($errors);
        
        // Step 5: Create simple autoloader
        $results[] = createSimpleAutoloader($errors);
        
        // Step 6: Mark as installed
        $results[] = markAsInstalled($errors);
        
        if (empty($errors)) {
            return [
                'success' => true,
                'message' => 'Basic installation completed! You can now install Composer dependencies.',
                'redirect' => 'public/index.php',
                'steps' => $results
            ];
        } else {
            return [
                'success' => false,
                'errors' => $errors,
                'steps' => $results
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => ['Installation failed: ' . $e->getMessage()],
            'steps' => $results
        ];
    }
}

function createDirectories(&$errors) {
    $directories = [
        'vendor/laravel/framework/src/Illuminate',
        'vendor/composer',
        'storage/app/public',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Failed to create directory: $dir";
            }
        }
    }
    
    return 'Essential directories created';
}

function createLaravelStructure(&$errors) {
    // Create a minimal Laravel structure
    $files = [
        'vendor/autoload.php' => '<?php
// Simple autoloader for Laravel
spl_autoload_register(function ($class) {
    $prefix = "App\\";
    $base_dir = __DIR__ . "/../../app/";
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace("\\", "/", $relative_class) . ".php";
    
    if (file_exists($file)) {
        require $file;
    }
});',
        
        'bootstrap/app.php' => '<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();',
        
        'bootstrap/cache/app.php' => '<?php
return [
    "name" => "Business Management System",
    "env" => "local",
    "debug" => true,
    "url" => "http://localhost",
    "timezone" => "UTC",
    "locale" => "en",
    "fallback_locale" => "en",
    "key" => "base64:" . base64_encode(random_bytes(32)),
    "cipher" => "AES-256-CBC",
];'
    ];
    
    foreach ($files as $file => $content) {
        if (!file_put_contents($file, $content)) {
            $errors[] = "Failed to create file: $file";
        }
    }
    
    return 'Laravel structure created';
}

function createEnvironmentFile(&$errors) {
    $envContent = 'APP_NAME="Business Management System"
APP_ENV=local
APP_KEY=base64:' . base64_encode(random_bytes(32)) . '
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erpsolution
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"';
    
    if (!file_put_contents('.env', $envContent)) {
        $errors[] = 'Failed to create .env file';
        return 'Environment file creation failed';
    }
    
    return 'Environment file created';
}

function createBasicConfig(&$errors) {
    $configDir = 'config';
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    $configFiles = [
        'app.php' => '<?php
return [
    "name" => env("APP_NAME", "Business Management System"),
    "env" => env("APP_ENV", "production"),
    "debug" => (bool) env("APP_DEBUG", false),
    "url" => env("APP_URL", "http://localhost"),
    "timezone" => "UTC",
    "locale" => "en",
    "fallback_locale" => "en",
    "key" => env("APP_KEY"),
    "cipher" => "AES-256-CBC",
];',
        
        'database.php' => '<?php
return [
    "default" => env("DB_CONNECTION", "mysql"),
    "connections" => [
        "mysql" => [
            "driver" => "mysql",
            "url" => env("DATABASE_URL"),
            "host" => env("DB_HOST", "127.0.0.1"),
            "port" => env("DB_PORT", "3306"),
            "database" => env("DB_DATABASE", "erpsolution"),
            "username" => env("DB_USERNAME", "root"),
            "password" => env("DB_PASSWORD", ""),
            "charset" => "utf8mb4",
            "collation" => "utf8mb4_unicode_ci",
            "prefix" => "",
            "prefix_indexes" => true,
            "strict" => true,
            "engine" => null,
        ],
    ],
];'
    ];
    
    foreach ($configFiles as $file => $content) {
        if (!file_put_contents("$configDir/$file", $content)) {
            $errors[] = "Failed to create config file: $file";
        }
    }
    
    return 'Basic configuration created';
}

function createSimpleAutoloader(&$errors) {
    $autoloaderContent = '<?php
// Simple autoloader for Laravel
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

// Load Composer autoloader if it exists
if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require __DIR__ . "/../vendor/autoload.php";
}';
    
    if (!file_put_contents('vendor/autoload.php', $autoloaderContent)) {
        $errors[] = 'Failed to create autoloader';
        return 'Autoloader creation failed';
    }
    
    return 'Simple autoloader created';
}

function markAsInstalled(&$errors) {
    if (!file_put_contents('.installed', date('Y-m-d H:i:s'))) {
        $errors[] = 'Failed to mark installation as complete';
        return 'Installation marking failed';
    }
    
    return 'Installation marked as complete';
}

// Display installation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management System - Simple Installation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .installer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #2d3748;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .warning {
            background: #fef5e7;
            border: 1px solid #f6ad55;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning h3 {
            color: #c05621;
            margin-bottom: 10px;
        }
        
        .warning p {
            color: #744210;
            font-size: 0.9rem;
        }
        
        .install-btn {
            background: #4299e1;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        .install-btn:hover {
            background: #3182ce;
        }
        
        .install-btn:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        .progress {
            display: none;
            margin-top: 20px;
        }
        
        .progress-bar {
            background: #e2e8f0;
            border-radius: 4px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            background: #4299e1;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .status {
            margin-top: 15px;
            font-size: 0.9rem;
            color: #718096;
        }
        
        .success {
            color: #48bb78;
        }
        
        .error {
            color: #f56565;
        }
        
        .steps {
            margin-top: 20px;
            display: none;
        }
        
        .step {
            padding: 10px;
            margin-bottom: 5px;
            background: #f7fafc;
            border-radius: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <h1>🏢 Business Management System</h1>
            <p>Simple Installation - No Composer Required</p>
        </div>
        
        <div class="warning">
            <h3>⚠️ Simple Installation Mode</h3>
            <p>This is a basic installation that creates the essential structure. For full functionality, you'll need to install Composer dependencies later.</p>
        </div>
        
        <button class="install-btn" id="install-btn" onclick="startInstallation()">
            🚀 Start Simple Installation
        </button>
        
        <div class="progress" id="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <div class="status" id="status">Preparing installation...</div>
        </div>
        
        <div class="steps" id="steps">
            <h4>Installation Steps:</h4>
            <div id="steps-list"></div>
        </div>
    </div>

    <script>
        function startInstallation() {
            const btn = document.getElementById('install-btn');
            const progress = document.getElementById('progress');
            const progressFill = document.getElementById('progress-fill');
            const status = document.getElementById('status');
            const steps = document.getElementById('steps');
            const stepsList = document.getElementById('steps-list');
            
            btn.disabled = true;
            btn.textContent = 'Installing...';
            progress.style.display = 'block';
            steps.style.display = 'block';
            
            // Start installation
            fetch('simple-install.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'install=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    status.innerHTML = '<span class="success">✅ ' + data.message + '</span>';
                    
                    // Show installation steps
                    if (data.steps) {
                        data.steps.forEach((step, index) => {
                            const stepDiv = document.createElement('div');
                            stepDiv.className = 'step';
                            stepDiv.textContent = `${index + 1}. ${step}`;
                            stepsList.appendChild(stepDiv);
                        });
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 3000);
                } else {
                    status.innerHTML = '<span class="error">❌ Installation failed: ' + data.errors.join(', ') + '</span>';
                    btn.disabled = false;
                    btn.textContent = '🔄 Retry Installation';
                }
            })
            .catch(error => {
                status.innerHTML = '<span class="error">❌ Installation failed: ' + error.message + '</span>';
                btn.disabled = false;
                btn.textContent = '🔄 Retry Installation';
            });
        }
    </script>
</body>
</html>

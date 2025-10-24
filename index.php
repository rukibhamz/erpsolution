<?php
/**
 * Business Management System - One-Click Installation
 * 
 * This file provides a one-click installation process similar to WordPress/Nextcloud
 * It will automatically set up the application when accessed for the first time
 */

// Check if application is already installed
if (file_exists(__DIR__ . '/.installed')) {
    // Application is installed, redirect to public directory
    header('Location: public/index.php');
    exit;
}

// Installation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    installApplication();
    exit;
}

function installApplication() {
    $errors = [];
    $success = [];
    
    try {
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $errors[] = 'PHP 8.2 or higher is required. Current version: ' . PHP_VERSION;
        }
        
        // Check if Composer is available
        if (!isComposerAvailable()) {
            $errors[] = 'Composer is not available. Please install Composer first.';
        }
        
        // Check write permissions
        if (!is_writable(__DIR__)) {
            $errors[] = 'Directory is not writable. Please check permissions.';
        }
        
        if (empty($errors)) {
            // Run installation steps
            $success[] = installDependencies();
            $success[] = setupEnvironment();
            $success[] = setupDatabase();
            $success[] = createStorageDirectories();
            $success[] = setPermissions();
            $success[] = markAsInstalled();
            
            // Installation complete
            echo json_encode([
                'success' => true,
                'message' => 'Installation completed successfully!',
                'redirect' => 'public/index.php'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'errors' => ['Installation failed: ' . $e->getMessage()]
        ]);
    }
}

function isComposerAvailable() {
    $output = [];
    $return_var = 0;
    exec('composer --version 2>&1', $output, $return_var);
    return $return_var === 0;
}

function installDependencies() {
    $output = [];
    $return_var = 0;
    exec('composer install --no-dev --optimize-autoloader 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to install dependencies: ' . implode("\n", $output));
    }
    
    return 'Dependencies installed successfully';
}

function setupEnvironment() {
    if (!file_exists('.env')) {
        copy('.env.example', '.env');
    }
    
    // Generate application key
    $output = [];
    $return_var = 0;
    exec('php artisan key:generate --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to generate application key');
    }
    
    return 'Environment configured successfully';
}

function setupDatabase() {
    // Run migrations
    $output = [];
    $return_var = 0;
    exec('php artisan migrate --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to run migrations: ' . implode("\n", $output));
    }
    
    // Run seeders
    exec('php artisan db:seed --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        throw new Exception('Failed to seed database: ' . implode("\n", $output));
    }
    
    return 'Database setup successfully';
}

function createStorageDirectories() {
    $directories = [
        'storage/app/public',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Create storage link
    $output = [];
    $return_var = 0;
    exec('php artisan storage:link 2>&1', $output, $return_var);
    
    return 'Storage directories created successfully';
}

function setPermissions() {
    $directories = ['storage', 'bootstrap/cache'];
    
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0755);
        }
    }
    
    return 'Permissions set successfully';
}

function markAsInstalled() {
    file_put_contents('.installed', date('Y-m-d H:i:s'));
    return 'Installation marked as complete';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Management System - Installation</title>
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
            max-width: 600px;
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
        
        .requirements {
            background: #f7fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .requirements h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        
        .requirement .icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .requirement .icon.check {
            background: #48bb78;
            color: white;
        }
        
        .requirement .icon.warning {
            background: #ed8936;
            color: white;
        }
        
        .requirement .icon.error {
            background: #f56565;
            color: white;
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
    </style>
</head>
<body>
    <div class="installer">
        <div class="logo">
            <h1>🏢 Business Management System</h1>
            <p>Comprehensive ERP Solution for Modern Businesses</p>
        </div>
        
        <div class="requirements">
            <h3>System Requirements</h3>
            <div id="requirements-list">
                <!-- Requirements will be populated by JavaScript -->
            </div>
        </div>
        
        <button class="install-btn" id="install-btn" onclick="startInstallation()">
            🚀 Install Business Management System
        </button>
        
        <div class="progress" id="progress">
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill"></div>
            </div>
            <div class="status" id="status">Preparing installation...</div>
        </div>
    </div>

    <script>
        // Check system requirements
        function checkRequirements() {
            const requirements = [
                {
                    name: 'PHP 8.2+',
                    check: () => {
                        const version = '<?php echo PHP_VERSION; ?>';
                        return version_compare(version, '8.2.0', '>=');
                    }
                },
                {
                    name: 'Composer',
                    check: () => {
                        // This will be checked server-side
                        return true;
                    }
                },
                {
                    name: 'Write Permissions',
                    check: () => {
                        return <?php echo is_writable(__DIR__) ? 'true' : 'false'; ?>;
                    }
                },
                {
                    name: 'MySQL/MariaDB',
                    check: () => {
                        return <?php echo extension_loaded('pdo_mysql') ? 'true' : 'false'; ?>;
                    }
                }
            ];
            
            const list = document.getElementById('requirements-list');
            let allGood = true;
            
            requirements.forEach(req => {
                const div = document.createElement('div');
                div.className = 'requirement';
                
                const icon = document.createElement('div');
                icon.className = 'icon';
                
                const name = document.createElement('span');
                name.textContent = req.name;
                
                if (req.check()) {
                    icon.className += ' check';
                    icon.textContent = '✓';
                } else {
                    icon.className += ' error';
                    icon.textContent = '✗';
                    allGood = false;
                }
                
                div.appendChild(icon);
                div.appendChild(name);
                list.appendChild(div);
            });
            
            if (!allGood) {
                document.getElementById('install-btn').disabled = true;
                document.getElementById('install-btn').textContent = '❌ Requirements not met';
            }
        }
        
        function startInstallation() {
            const btn = document.getElementById('install-btn');
            const progress = document.getElementById('progress');
            const progressFill = document.getElementById('progress-fill');
            const status = document.getElementById('status');
            
            btn.disabled = true;
            btn.textContent = 'Installing...';
            progress.style.display = 'block';
            
            // Simulate progress
            let progressValue = 0;
            const progressInterval = setInterval(() => {
                progressValue += Math.random() * 15;
                if (progressValue > 90) progressValue = 90;
                progressFill.style.width = progressValue + '%';
            }, 200);
            
            // Start installation
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'install=1'
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressFill.style.width = '100%';
                
                if (data.success) {
                    status.innerHTML = '<span class="success">✅ ' + data.message + '</span>';
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    status.innerHTML = '<span class="error">❌ Installation failed: ' + data.errors.join(', ') + '</span>';
                    btn.disabled = false;
                    btn.textContent = '🔄 Retry Installation';
                }
            })
            .catch(error => {
                clearInterval(progressInterval);
                status.innerHTML = '<span class="error">❌ Installation failed: ' + error.message + '</span>';
                btn.disabled = false;
                btn.textContent = '🔄 Retry Installation';
            });
        }
        
        // Initialize
        checkRequirements();
    </script>
</body>
</html>

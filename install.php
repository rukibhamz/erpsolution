<?php
/**
 * Business Management System - Installation Script
 * 
 * This script handles the complete installation process
 * including downloading and setting up Composer dependencies
 */

// Set execution time limit
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

// Check if already installed
if (file_exists('.installed')) {
    header('Location: public/index.php');
    exit;
}

// Handle installation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    header('Content-Type: application/json');
    echo json_encode(performInstallation());
    exit;
}

function performInstallation() {
    $results = [];
    $errors = [];
    
    try {
        // Step 1: Check requirements
        $results[] = checkRequirements($errors);
        
        // Step 2: Download Composer if not available
        $results[] = ensureComposerAvailable($errors);
        
        // Step 3: Install dependencies
        $results[] = installDependencies($errors);
        
        // Step 4: Setup environment
        $results[] = setupEnvironment($errors);
        
        // Step 5: Setup database
        $results[] = setupDatabase($errors);
        
        // Step 6: Create storage directories
        $results[] = createStorageDirectories($errors);
        
        // Step 7: Set permissions
        $results[] = setPermissions($errors);
        
        // Step 8: Mark as installed
        $results[] = markAsInstalled($errors);
        
        if (empty($errors)) {
            return [
                'success' => true,
                'message' => 'Installation completed successfully!',
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

function checkRequirements(&$errors) {
    $checks = [
        'PHP Version' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'OpenSSL' => extension_loaded('openssl'),
        'Mbstring' => extension_loaded('mbstring'),
        'Tokenizer' => extension_loaded('tokenizer'),
        'XML' => extension_loaded('xml'),
        'Ctype' => extension_loaded('ctype'),
        'JSON' => extension_loaded('json'),
        'Write Permissions' => is_writable(__DIR__)
    ];
    
    $failed = [];
    foreach ($checks as $name => $status) {
        if (!$status) {
            $failed[] = $name;
        }
    }
    
    if (!empty($failed)) {
        $errors[] = 'Missing requirements: ' . implode(', ', $failed);
    }
    
    return 'Requirements checked: ' . (empty($failed) ? 'All passed' : 'Some failed');
}

function ensureComposerAvailable(&$errors) {
    // Check if Composer is available
    $output = [];
    $return_var = 0;
    exec('composer --version 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        return 'Composer is available';
    }
    
    // Try to download Composer
    $composerPath = __DIR__ . '/composer.phar';
    
    if (!file_exists($composerPath)) {
        $composerUrl = 'https://getcomposer.org/composer-stable.phar';
        $composerContent = @file_get_contents($composerUrl);
        
        if ($composerContent === false) {
            $errors[] = 'Failed to download Composer. Please install Composer manually.';
            return 'Failed to download Composer';
        }
        
        file_put_contents($composerPath, $composerContent);
        chmod($composerPath, 0755);
    }
    
    return 'Composer downloaded successfully';
}

function installDependencies(&$errors) {
    $composerPath = file_exists(__DIR__ . '/composer.phar') ? 'php composer.phar' : 'composer';
    
    // Install dependencies
    $output = [];
    $return_var = 0;
    exec($composerPath . ' install --no-dev --optimize-autoloader --no-interaction 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        $errors[] = 'Failed to install dependencies: ' . implode("\n", $output);
        return 'Dependencies installation failed';
    }
    
    return 'Dependencies installed successfully';
}

function setupEnvironment(&$errors) {
    if (!file_exists('.env')) {
        if (!copy('.env.example', '.env')) {
            $errors[] = 'Failed to create .env file';
            return 'Environment setup failed';
        }
    }
    
    // Generate application key
    $output = [];
    $return_var = 0;
    exec('php artisan key:generate --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        $errors[] = 'Failed to generate application key: ' . implode("\n", $output);
        return 'Application key generation failed';
    }
    
    return 'Environment configured successfully';
}

function setupDatabase(&$errors) {
    // Run migrations
    $output = [];
    $return_var = 0;
    exec('php artisan migrate --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        $errors[] = 'Failed to run migrations: ' . implode("\n", $output);
        return 'Database migration failed';
    }
    
    // Run seeders
    exec('php artisan db:seed --force 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        $errors[] = 'Failed to seed database: ' . implode("\n", $output);
        return 'Database seeding failed';
    }
    
    return 'Database setup successfully';
}

function createStorageDirectories(&$errors) {
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
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Failed to create directory: $dir";
            }
        }
    }
    
    // Create storage link
    $output = [];
    $return_var = 0;
    exec('php artisan storage:link 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        // Not critical, just log it
        error_log('Storage link creation failed: ' . implode("\n", $output));
    }
    
    return 'Storage directories created successfully';
}

function setPermissions(&$errors) {
    $directories = ['storage', 'bootstrap/cache'];
    
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            chmod($dir, 0755);
        }
    }
    
    return 'Permissions set successfully';
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
        
        <div class="steps" id="steps">
            <h4>Installation Steps:</h4>
            <div id="steps-list"></div>
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
                    name: 'PDO Extension',
                    check: () => <?php echo extension_loaded('pdo') ? 'true' : 'false'; ?>
                },
                {
                    name: 'PDO MySQL',
                    check: () => <?php echo extension_loaded('pdo_mysql') ? 'true' : 'false'; ?>
                },
                {
                    name: 'OpenSSL',
                    check: () => <?php echo extension_loaded('openssl') ? 'true' : 'false'; ?>
                },
                {
                    name: 'Mbstring',
                    check: () => <?php echo extension_loaded('mbstring') ? 'true' : 'false'; ?>
                },
                {
                    name: 'Write Permissions',
                    check: () => <?php echo is_writable(__DIR__) ? 'true' : 'false'; ?>
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
            const steps = document.getElementById('steps');
            const stepsList = document.getElementById('steps-list');
            
            btn.disabled = true;
            btn.textContent = 'Installing...';
            progress.style.display = 'block';
            steps.style.display = 'block';
            
            // Start installation
            fetch('install.php', {
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
        
        // Initialize
        checkRequirements();
    </script>
</body>
</html>

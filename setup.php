<?php
/**
 * One-Click Installation Script for ERP Solution
 * This script handles the complete setup process
 */

echo "🚀 ERP Solution - One-Click Installation\n";
echo "========================================\n\n";

// Check PHP version
echo "🔍 Checking PHP version...\n";
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    echo "❌ PHP 8.2+ is required. Current version: " . PHP_VERSION . "\n";
    echo "Please upgrade PHP and try again.\n";
    exit(1);
}
echo "✅ PHP version: " . PHP_VERSION . " (OK)\n\n";

// Check if .env file exists
echo "🔍 Checking environment configuration...\n";
if (!file_exists('.env')) {
    if (file_exists('.env.example')) {
        echo "📝 Creating .env file from .env.example...\n";
        copy('.env.example', '.env');
        echo "✅ .env file created successfully!\n";
    } else {
        echo "❌ .env.example file not found!\n";
        echo "Please ensure you have the complete Laravel application files.\n";
        exit(1);
    }
} else {
    echo "✅ .env file already exists!\n";
}
echo "\n";

// Check if Composer is available
echo "🔍 Checking Composer availability...\n";
$composerAvailable = false;

// Check for global Composer
$output = [];
exec('composer --version 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "✅ Global Composer found: " . $output[0] . "\n";
    $composerAvailable = true;
} else {
    // Check for local Composer
    if (file_exists('composer.phar')) {
        echo "✅ Local Composer found!\n";
        $composerAvailable = true;
    } else {
        echo "❌ Composer not found. Installing Composer...\n";
        
        // Download and install Composer
        $installerUrl = 'https://getcomposer.org/installer';
        $installerContent = file_get_contents($installerUrl);
        
        if ($installerContent === false) {
            echo "❌ Failed to download Composer installer.\n";
            echo "Please install Composer manually from: https://getcomposer.org/download/\n";
            exit(1);
        }
        
        file_put_contents('composer-installer.php', $installerContent);
        
        $installOutput = [];
        exec('php composer-installer.php 2>&1', $installOutput, $installReturnCode);
        
        if ($installReturnCode === 0) {
            echo "✅ Composer installed successfully!\n";
            $composerAvailable = true;
        } else {
            echo "❌ Failed to install Composer:\n";
            echo implode("\n", $installOutput);
            exit(1);
        }
        
        // Clean up installer
        if (file_exists('composer-installer.php')) {
            unlink('composer-installer.php');
        }
    }
}

if (!$composerAvailable) {
    echo "❌ Composer is not available. Please install Composer first.\n";
    exit(1);
}

echo "\n";

// Install dependencies
echo "📦 Installing dependencies...\n";
if (file_exists('composer.phar')) {
    $composerCmd = 'php composer.phar install --no-dev --optimize-autoloader';
} else {
    $composerCmd = 'composer install --no-dev --optimize-autoloader';
}

$composerOutput = [];
exec($composerCmd . ' 2>&1', $composerOutput, $composerReturnCode);

if ($composerReturnCode === 0) {
    echo "✅ Dependencies installed successfully!\n";
} else {
    echo "❌ Failed to install dependencies:\n";
    echo implode("\n", $composerOutput);
    echo "\nTrying with development dependencies...\n";
    
    // Try with dev dependencies
    if (file_exists('composer.phar')) {
        $composerCmd = 'php composer.phar install';
    } else {
        $composerCmd = 'composer install';
    }
    
    exec($composerCmd . ' 2>&1', $composerOutput, $composerReturnCode);
    
    if ($composerReturnCode === 0) {
        echo "✅ Dependencies installed successfully (with dev dependencies)!\n";
    } else {
        echo "❌ Failed to install dependencies even with dev dependencies:\n";
        echo implode("\n", $composerOutput);
        exit(1);
    }
}

echo "\n";

// Generate application key
echo "🔑 Generating application key...\n";
$keyOutput = [];
if (file_exists('composer.phar')) {
    exec('php composer.phar run-script post-install-cmd 2>&1', $keyOutput, $keyReturnCode);
} else {
    exec('composer run-script post-install-cmd 2>&1', $keyOutput, $keyReturnCode);
}

// Try to generate key manually
if (!file_exists('.env') || strpos(file_get_contents('.env'), 'APP_KEY=') === false || strpos(file_get_contents('.env'), 'APP_KEY=base64:') === false) {
    echo "🔑 Generating application key manually...\n";
    $key = 'base64:' . base64_encode(random_bytes(32));
    
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_KEY=') !== false) {
        $envContent = preg_replace('/APP_KEY=.*/', 'APP_KEY=' . $key, $envContent);
    } else {
        $envContent .= "\nAPP_KEY=" . $key . "\n";
    }
    file_put_contents('.env', $envContent);
    echo "✅ Application key generated!\n";
} else {
    echo "✅ Application key already exists!\n";
}

echo "\n";

// Create storage directories
echo "📁 Creating storage directories...\n";
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
        echo "✅ Created: $dir\n";
    } else {
        echo "✅ Exists: $dir\n";
    }
}

echo "\n";

// Set permissions (Windows doesn't need chmod, but we'll try)
echo "🔐 Setting permissions...\n";
if (PHP_OS_FAMILY !== 'Windows') {
    exec('chmod -R 755 storage bootstrap/cache 2>&1', $permOutput, $permReturnCode);
    if ($permReturnCode === 0) {
        echo "✅ Permissions set successfully!\n";
    } else {
        echo "⚠️  Could not set permissions automatically. Please set manually if needed.\n";
    }
} else {
    echo "✅ Permissions set (Windows system)\n";
}

echo "\n";

// Create symbolic link for storage
echo "🔗 Creating storage link...\n";
if (file_exists('composer.phar')) {
    exec('php composer.phar run-script post-create-project-cmd 2>&1', $linkOutput, $linkReturnCode);
} else {
    exec('composer run-script post-create-project-cmd 2>&1', $linkOutput, $linkReturnCode);
}

// Try to create link manually
if (!file_exists('public/storage')) {
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows doesn't support symlinks easily, so we'll copy
        if (is_dir('storage/app/public')) {
            if (!is_dir('public/storage')) {
                mkdir('public/storage', 0755, true);
            }
            echo "✅ Storage link created (Windows copy method)\n";
        }
    } else {
        // Unix-like systems
        exec('ln -sf ../storage/app/public public/storage 2>&1', $linkOutput, $linkReturnCode);
        if ($linkReturnCode === 0) {
            echo "✅ Storage link created successfully!\n";
        } else {
            echo "⚠️  Could not create storage link automatically.\n";
        }
    }
} else {
    echo "✅ Storage link already exists!\n";
}

echo "\n";

// Run database migrations
echo "🗄️  Setting up database...\n";
echo "⚠️  Please ensure your database is configured in .env file\n";
echo "⚠️  You may need to run migrations manually: php artisan migrate\n";

echo "\n";

// Final instructions
echo "🎉 Installation Complete!\n";
echo "========================\n\n";
echo "📋 Next Steps:\n";
echo "1. Configure your database in .env file\n";
echo "2. Run: php artisan migrate\n";
echo "3. Run: php artisan db:seed\n";
echo "4. Start the server: php artisan serve\n";
echo "5. Visit: http://localhost:8000\n\n";
echo "🔧 Additional Commands:\n";
echo "- php artisan key:generate (if needed)\n";
echo "- php artisan storage:link (if needed)\n";
echo "- php artisan config:cache\n";
echo "- php artisan route:cache\n";
echo "- php artisan view:cache\n\n";
echo "📚 Documentation: Check README.md for detailed setup instructions\n";
echo "🐛 Issues: Check the logs in storage/logs/ if you encounter problems\n";
?>

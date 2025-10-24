<?php
/**
 * Composer Installation Helper
 * This script helps install Composer on Windows systems
 */

echo "🔧 Composer Installation Helper\n";
echo "==============================\n\n";

// Check if Composer is already installed
if (file_exists('composer.phar')) {
    echo "✅ Composer is already installed!\n";
    echo "You can run: php composer.phar install\n\n";
    exit(0);
}

echo "📥 Downloading Composer installer...\n";

// Download Composer installer
$installerUrl = 'https://getcomposer.org/installer';
$installerContent = file_get_contents($installerUrl);

if ($installerContent === false) {
    echo "❌ Failed to download Composer installer.\n";
    echo "Please check your internet connection and try again.\n";
    exit(1);
}

// Save installer
file_put_contents('composer-installer.php', $installerContent);

echo "✅ Composer installer downloaded successfully!\n";
echo "📦 Running Composer installation...\n";

// Run the installer
$output = [];
$returnCode = 0;
exec('php composer-installer.php 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Composer installed successfully!\n";
    echo "📦 Running composer install...\n";
    
    // Run composer install
    $composerOutput = [];
    $composerReturnCode = 0;
    exec('php composer.phar install 2>&1', $composerOutput, $composerReturnCode);
    
    if ($composerReturnCode === 0) {
        echo "✅ Dependencies installed successfully!\n";
        echo "🎉 Installation complete!\n";
    } else {
        echo "❌ Failed to install dependencies:\n";
        echo implode("\n", $composerOutput);
    }
} else {
    echo "❌ Failed to install Composer:\n";
    echo implode("\n", $output);
}

// Clean up
if (file_exists('composer-installer.php')) {
    unlink('composer-installer.php');
}

echo "\n📋 Manual Installation Instructions:\n";
echo "====================================\n";
echo "If the automatic installation failed, you can install Composer manually:\n\n";
echo "1. Download Composer from: https://getcomposer.org/download/\n";
echo "2. Save the file as 'composer.phar' in this directory\n";
echo "3. Run: php composer.phar install\n\n";
echo "Alternative: Install Composer globally on your system:\n";
echo "- Windows: Download and run Composer-Setup.exe\n";
echo "- Or use: php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"\n";
echo "- Then: php composer-setup.php\n";
echo "- Then: php -r \"unlink('composer-setup.php');\"\n";
?>

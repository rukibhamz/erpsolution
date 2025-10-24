# ERP Solution - One-Click Installation (PowerShell)
Write-Host "🚀 ERP Solution - One-Click Installation" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

# Check for PHP
Write-Host "🔍 Checking for PHP..." -ForegroundColor Yellow
try {
    $phpVersion = php --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ PHP found!" -ForegroundColor Green
        Write-Host $phpVersion[0] -ForegroundColor Cyan
    } else {
        throw "PHP not found"
    }
} catch {
    Write-Host "❌ PHP is not found in PATH" -ForegroundColor Red
    Write-Host "Please install PHP and add it to your system PATH" -ForegroundColor Yellow
    Write-Host "Download PHP from: https://windows.php.net/download/" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Alternative: If you have XAMPP, WAMP, or Laragon installed:" -ForegroundColor Yellow
    Write-Host "- Add PHP to your PATH environment variable" -ForegroundColor Yellow
    Write-Host "- Or run this script from the PHP installation directory" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host ""

# Check for Composer
Write-Host "🔍 Checking for Composer..." -ForegroundColor Yellow
try {
    $composerVersion = composer --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Composer found!" -ForegroundColor Green
        Write-Host $composerVersion[0] -ForegroundColor Cyan
        $composerCmd = "composer"
    } else {
        throw "Composer not found"
    }
} catch {
    Write-Host "❌ Composer not found. Installing Composer..." -ForegroundColor Red
    Write-Host ""
    
    Write-Host "📥 Downloading Composer installer..." -ForegroundColor Yellow
    try {
        Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile "composer-installer.php"
        Write-Host "✅ Composer installer downloaded!" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to download Composer installer" -ForegroundColor Red
        Write-Host "Please install Composer manually from: https://getcomposer.org/download/" -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
    
    Write-Host "📦 Installing Composer..." -ForegroundColor Yellow
    php composer-installer.php
    
    if (Test-Path "composer.phar") {
        Write-Host "✅ Composer installed successfully!" -ForegroundColor Green
        $composerCmd = "php composer.phar"
        Remove-Item "composer-installer.php" -Force
    } else {
        Write-Host "❌ Failed to install Composer" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}
Write-Host ""

# Check environment configuration
Write-Host "🔍 Checking environment configuration..." -ForegroundColor Yellow
if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Write-Host "📝 Creating .env file from .env.example..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "✅ .env file created!" -ForegroundColor Green
    } else {
        Write-Host "❌ .env.example file not found!" -ForegroundColor Red
        Write-Host "Please ensure you have the complete Laravel application files." -ForegroundColor Yellow
        Read-Host "Press Enter to exit"
        exit 1
    }
} else {
    Write-Host "✅ .env file already exists!" -ForegroundColor Green
}
Write-Host ""

# Install dependencies
Write-Host "📦 Installing dependencies..." -ForegroundColor Yellow
try {
    Invoke-Expression "$composerCmd install --no-dev --optimize-autoloader"
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to install dependencies"
    }
    Write-Host "✅ Dependencies installed successfully!" -ForegroundColor Green
} catch {
    Write-Host "❌ Failed to install dependencies. Trying with dev dependencies..." -ForegroundColor Red
    try {
        Invoke-Expression "$composerCmd install"
        if ($LASTEXITCODE -ne 0) {
            throw "Failed to install dependencies"
        }
        Write-Host "✅ Dependencies installed successfully (with dev dependencies)!" -ForegroundColor Green
    } catch {
        Write-Host "❌ Failed to install dependencies" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
}
Write-Host ""

# Generate application key
Write-Host "🔑 Generating application key..." -ForegroundColor Yellow
try {
    Invoke-Expression "$composerCmd run-script post-install-cmd"
    Write-Host "✅ Application key generated!" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Could not generate key automatically. You may need to run: php artisan key:generate" -ForegroundColor Yellow
}
Write-Host ""

# Create storage directories
Write-Host "📁 Creating storage directories..." -ForegroundColor Yellow
$directories = @(
    "storage\app\public",
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)

foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "✅ Created: $dir" -ForegroundColor Green
    } else {
        Write-Host "✅ Exists: $dir" -ForegroundColor Green
    }
}
Write-Host ""

# Create storage link
Write-Host "🔗 Creating storage link..." -ForegroundColor Yellow
if (-not (Test-Path "public\storage")) {
    try {
        Invoke-Expression "$composerCmd run-script post-create-project-cmd"
        Write-Host "✅ Storage link created!" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  Could not create storage link automatically. You may need to run: php artisan storage:link" -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ Storage link already exists!" -ForegroundColor Green
}
Write-Host ""

# Final instructions
Write-Host "🎉 Installation Complete!" -ForegroundColor Green
Write-Host "========================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Cyan
Write-Host "1. Configure your database in .env file" -ForegroundColor White
Write-Host "2. Run: php artisan migrate" -ForegroundColor White
Write-Host "3. Run: php artisan db:seed" -ForegroundColor White
Write-Host "4. Start the server: php artisan serve" -ForegroundColor White
Write-Host "5. Visit: http://localhost:8000" -ForegroundColor White
Write-Host ""
Write-Host "🔧 Additional Commands:" -ForegroundColor Cyan
Write-Host "- php artisan key:generate (if needed)" -ForegroundColor White
Write-Host "- php artisan storage:link (if needed)" -ForegroundColor White
Write-Host "- php artisan config:cache" -ForegroundColor White
Write-Host "- php artisan route:cache" -ForegroundColor White
Write-Host "- php artisan view:cache" -ForegroundColor White
Write-Host ""
Write-Host "📚 Documentation: Check README.md for detailed setup instructions" -ForegroundColor Cyan
Write-Host "🐛 Issues: Check the logs in storage/logs/ if you encounter problems" -ForegroundColor Cyan
Write-Host ""
Read-Host "Press Enter to exit"

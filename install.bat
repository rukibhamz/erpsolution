@echo off
echo 🚀 ERP Solution - One-Click Installation
echo ========================================
echo.

echo 🔍 Checking for PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not found in PATH
    echo Please install PHP and add it to your system PATH
    echo Download PHP from: https://windows.php.net/download/
    echo.
    echo Alternative: If you have XAMPP, WAMP, or Laragon installed:
    echo - Add PHP to your PATH environment variable
    echo - Or run this script from the PHP installation directory
    pause
    exit /b 1
)

echo ✅ PHP found!
php --version
echo.

echo 🔍 Checking for Composer...
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Composer not found. Installing Composer...
    echo.
    
    echo 📥 Downloading Composer installer...
    powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/installer' -OutFile 'composer-installer.php'"
    
    if not exist composer-installer.php (
        echo ❌ Failed to download Composer installer
        echo Please install Composer manually from: https://getcomposer.org/download/
        pause
        exit /b 1
    )
    
    echo ✅ Composer installer downloaded!
    echo 📦 Installing Composer...
    php composer-installer.php
    
    if not exist composer.phar (
        echo ❌ Failed to install Composer
        pause
        exit /b 1
    )
    
    echo ✅ Composer installed successfully!
    del composer-installer.php
) else (
    echo ✅ Composer found!
    composer --version
)
echo.

echo 🔍 Checking environment configuration...
if not exist .env (
    if exist .env.example (
        echo 📝 Creating .env file from .env.example...
        copy .env.example .env
        echo ✅ .env file created!
    ) else (
        echo ❌ .env.example file not found!
        echo Please ensure you have the complete Laravel application files.
        pause
        exit /b 1
    )
) else (
    echo ✅ .env file already exists!
)
echo.

echo 📦 Installing dependencies...
if exist composer.phar (
    php composer.phar install --no-dev --optimize-autoloader
) else (
    composer install --no-dev --optimize-autoloader
)

if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies. Trying with dev dependencies...
    if exist composer.phar (
        php composer.phar install
    ) else (
        composer install
    )
    
    if %errorlevel% neq 0 (
        echo ❌ Failed to install dependencies
        pause
        exit /b 1
    )
)

echo ✅ Dependencies installed successfully!
echo.

echo 🔑 Generating application key...
if exist composer.phar (
    php composer.phar run-script post-install-cmd
) else (
    composer run-script post-install-cmd
)

echo ✅ Application key generated!
echo.

echo 📁 Creating storage directories...
if not exist "storage\app\public" mkdir "storage\app\public"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"

echo ✅ Storage directories created!
echo.

echo 🔗 Creating storage link...
if not exist "public\storage" (
    if exist composer.phar (
        php composer.phar run-script post-create-project-cmd
    ) else (
        composer run-script post-create-project-cmd
    )
)

echo ✅ Storage link created!
echo.

echo 🎉 Installation Complete!
echo ========================
echo.
echo 📋 Next Steps:
echo 1. Configure your database in .env file
echo 2. Run: php artisan migrate
echo 3. Run: php artisan db:seed
echo 4. Start the server: php artisan serve
echo 5. Visit: http://localhost:8000
echo.
echo 🔧 Additional Commands:
echo - php artisan key:generate (if needed)
echo - php artisan storage:link (if needed)
echo - php artisan config:cache
echo - php artisan route:cache
echo - php artisan view:cache
echo.
echo 📚 Documentation: Check README.md for detailed setup instructions
echo 🐛 Issues: Check the logs in storage/logs/ if you encounter problems
echo.
pause

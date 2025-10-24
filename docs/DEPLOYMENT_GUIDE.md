# Deployment Guide

## Overview
This guide provides comprehensive instructions for deploying the Business Management System to various environments.

## Prerequisites

### System Requirements
- **PHP**: 8.2 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Memory**: Minimum 512MB RAM
- **Storage**: Minimum 1GB free space

### Required Extensions
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

## Environment Setup

### 1. Local Development

#### Using Laravel Sail (Docker)
```bash
# Clone repository
git clone <repository-url>
cd erpsolution

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Start Sail
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Seed database
./vendor/bin/sail artisan db:seed

# Install npm dependencies
./vendor/bin/sail npm install

# Build assets
./vendor/bin/sail npm run build
```

#### Manual Setup
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build assets
npm run build

# Set permissions
chmod -R 755 storage bootstrap/cache

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

### 2. Production Deployment

#### Server Configuration

##### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/erpsolution/public
    
    <Directory /var/www/erpsolution/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # Gzip compression
    LoadModule deflate_module modules/mod_deflate.so
    <Location />
        SetOutputFilter DEFLATE
        SetEnvIfNoCase Request_URI \
            \.(?:gif|jpe?g|png)$ no-gzip dont-vary
        SetEnvIfNoCase Request_URI \
            \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
    </Location>
</VirtualHost>
```

##### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/erpsolution/public;
    index index.php;

    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### Environment Configuration
```bash
# Production environment file
APP_NAME="Business Management System"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erpsolution
DB_USERNAME=erpsolution_user
DB_PASSWORD=secure_password

# Cache configuration
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Mail configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Business Management System"

# Redis configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. cPanel Deployment

#### File Upload
1. **Upload Files**: Upload all project files to `public_html` or subdirectory
2. **Set Permissions**: Set proper file permissions
3. **Configure Database**: Create MySQL database and user
4. **Environment Setup**: Create `.env` file with production settings

#### cPanel Configuration
```bash
# Set document root to public directory
# Document Root: /home/username/public_html/erpsolution/public

# Set permissions
chmod 755 storage bootstrap/cache
chmod 644 .env

# Configure PHP version (8.2+)
# Select PHP version in cPanel
```

#### Database Setup
```sql
-- Create database
CREATE DATABASE erpsolution_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'erpsolution_user'@'localhost' IDENTIFIED BY 'secure_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON erpsolution_db.* TO 'erpsolution_user'@'localhost';
FLUSH PRIVILEGES;
```

## Deployment Steps

### 1. Pre-Deployment Checklist
- [ ] Code review completed
- [ ] Tests passing
- [ ] Environment variables configured
- [ ] Database backup created
- [ ] SSL certificate installed
- [ ] Domain DNS configured

### 2. Deployment Process

#### Automated Deployment (Git)
```bash
# Pull latest changes
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
```

#### Manual Deployment
```bash
# 1. Backup current version
cp -r /var/www/erpsolution /var/www/erpsolution-backup-$(date +%Y%m%d)

# 2. Upload new files
rsync -avz --exclude='.git' ./ user@server:/var/www/erpsolution/

# 3. Install dependencies
cd /var/www/erpsolution
composer install --no-dev --optimize-autoloader

# 4. Run deployment commands
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### 3. Post-Deployment Verification

#### Health Checks
```bash
# Check application status
curl -I https://yourdomain.com

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue workers
php artisan queue:work --once

# Check scheduled tasks
php artisan schedule:run
```

#### Performance Optimization
```bash
# Optimize database
php artisan db:optimize

# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate optimized autoloader
composer dump-autoload --optimize
```

## Security Configuration

### 1. File Permissions
```bash
# Set proper permissions
find /var/www/erpsolution -type f -exec chmod 644 {} \;
find /var/www/erpsolution -type d -exec chmod 755 {} \;
chmod -R 755 storage bootstrap/cache
chmod 600 .env
```

### 2. Security Headers
```php
// In App\Http\Middleware\SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    
    return $response;
}
```

### 3. SSL Configuration
```nginx
# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
}
```

## Monitoring and Maintenance

### 1. Log Monitoring
```bash
# Monitor application logs
tail -f storage/logs/laravel.log

# Monitor error logs
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log
```

### 2. Performance Monitoring
```bash
# Check database performance
php artisan db:monitor

# Check application performance
php artisan performance:check

# Monitor queue workers
php artisan queue:monitor
```

### 3. Backup Strategy
```bash
# Database backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# File backup
tar -czf erpsolution_backup_$(date +%Y%m%d).tar.gz /var/www/erpsolution

# Automated backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p database_name > /backups/db_$DATE.sql
tar -czf /backups/files_$DATE.tar.gz /var/www/erpsolution
find /backups -name "*.sql" -mtime +7 -delete
find /backups -name "*.tar.gz" -mtime +7 -delete
```

## Troubleshooting

### Common Issues

#### 1. Permission Errors
```bash
# Fix storage permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 2. Database Connection Issues
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test database credentials
mysql -u username -p -h hostname database_name
```

#### 3. Queue Issues
```bash
# Restart queue workers
php artisan queue:restart

# Check queue status
php artisan queue:work --once
```

#### 4. Cache Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Rollback Procedure

### 1. Database Rollback
```bash
# Rollback migrations
php artisan migrate:rollback --step=5

# Restore database from backup
mysql -u username -p database_name < backup_file.sql
```

### 2. Code Rollback
```bash
# Revert to previous commit
git reset --hard HEAD~1

# Or restore from backup
rm -rf /var/www/erpsolution
cp -r /var/www/erpsolution-backup-YYYYMMDD /var/www/erpsolution
```

## Conclusion

This deployment guide ensures:
- **Reliability**: Stable and consistent deployments
- **Security**: Proper security configurations
- **Performance**: Optimized for production use
- **Maintainability**: Easy to monitor and maintain
- **Scalability**: Ready for growth and expansion

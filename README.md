# Business Management System

A comprehensive business management system built with Laravel 11, featuring property management, accounting, event booking, and user management capabilities.

## 🚀 Features

### Core Modules
- **Property Management**: Complete property lifecycle management with lease tracking
- **Accounting System**: Double-entry bookkeeping with transaction management
- **Event Booking**: Event creation, booking management, and payment processing
- **User Management**: Role-based access control with permissions
- **Inventory Management**: Stock tracking and maintenance logs
- **Utilities Tracking**: Electricity, water, and waste management
- **Tax & Revenue Collection**: VAT, AMAC, and other tax calculations
- **Online Booking**: Public-facing portal with payment integration

### Technical Features
- **Modern UI**: Responsive design with Tailwind CSS and Alpine.js
- **API Ready**: RESTful API with comprehensive documentation
- **Security**: CSRF protection, XSS prevention, SQL injection protection
- **Performance**: Optimized queries, caching, and database indexing
- **Monitoring**: Error tracking, performance monitoring, and logging
- **Validation**: Comprehensive server-side validation with business rules

## 🛠️ Technology Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Database**: MySQL 8.0
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Authentication**: Laravel Breeze with role-based permissions
- **Payments**: Paystack integration for Nigerian market
- **PDF Generation**: DomPDF for reports
- **Excel Reports**: Laravel Excel for data export

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Composer
- Node.js and NPM
- Web server (Apache/Nginx)

## 🚀 Installation

### 1. Clone Repository
```bash
git clone <repository-url>
cd erpsolution
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database settings in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erpsolution
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 4. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
```

### 5. Build Assets
```bash
# Build frontend assets
npm run build

# Or for development
npm run dev
```

### 6. Start Development Server
```bash
# Start Laravel development server
php artisan serve

# Or using Laravel Sail (Docker)
./vendor/bin/sail up -d
```

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="Business Management System"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erpsolution
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Business Management System"

# Payment Gateway (Paystack)
PAYSTACK_PUBLIC_KEY=your-public-key
PAYSTACK_SECRET_KEY=your-secret-key
PAYSTACK_PAYMENT_URL=https://api.paystack.co
```

### File Permissions
```bash
# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 📚 Usage

### Default Login Credentials
- **Admin**: admin@example.com / password
- **Manager**: manager@example.com / password
- **Staff**: staff@example.com / password

### Key Features Usage

#### Property Management
1. Navigate to Properties section
2. Create new properties with detailed information
3. Upload property images
4. Manage property status and availability
5. Track property leases and tenants

#### Accounting System
1. Access Accounting section
2. Create accounts and chart of accounts
3. Record transactions (income, expenses, transfers)
4. Generate financial reports
5. Export data to PDF/Excel

#### Event Booking
1. Create events with venue and pricing information
2. Set event capacity and dates
3. Manage bookings and payments
4. Track event attendance
5. Generate event reports

#### User Management
1. Access User Management section
2. Create users with specific roles
3. Assign permissions to users
4. Manage user access and status
5. Track user activities

## 🔒 Security

### Security Features
- **CSRF Protection**: All forms protected against CSRF attacks
- **XSS Prevention**: Input sanitization and output escaping
- **SQL Injection Protection**: Eloquent ORM with parameterized queries
- **Password Hashing**: bcrypt encryption for all passwords
- **Role-Based Access**: Granular permissions system
- **File Upload Security**: Validated file types and sizes
- **Rate Limiting**: API and form submission rate limiting

### Security Best Practices
- Regular security updates
- Secure file uploads
- Input validation and sanitization
- Secure session management
- HTTPS enforcement
- Security headers implementation

## 📊 Performance

### Performance Features
- **Query Optimization**: Eager loading and optimized queries
- **Database Indexing**: Strategic indexes for common queries
- **Caching**: Redis caching for improved performance
- **Asset Optimization**: Minified CSS and JavaScript
- **Image Optimization**: Compressed and optimized images
- **CDN Ready**: Prepared for content delivery networks

### Performance Monitoring
- Database query monitoring
- Application performance tracking
- Error logging and monitoring
- Resource usage monitoring
- Automated performance checks

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- Unit tests for models and services
- Feature tests for controllers
- Integration tests for API endpoints
- Browser tests for user interactions

## 📖 API Documentation

### API Endpoints
- **Properties**: `/api/properties`
- **Transactions**: `/api/transactions`
- **Leases**: `/api/leases`
- **Events**: `/api/events`
- **Bookings**: `/api/bookings`
- **Users**: `/api/users`

### Authentication
All API endpoints require authentication using Laravel Sanctum tokens.

### Rate Limiting
- General endpoints: 60 requests per minute
- Authentication endpoints: 5 requests per minute
- File upload endpoints: 10 requests per minute

## 🚀 Deployment

### Production Deployment
1. Configure production environment
2. Set up web server (Apache/Nginx)
3. Configure SSL certificate
4. Set up database and caching
5. Run deployment commands
6. Set up monitoring and backups

### Docker Deployment
```bash
# Using Laravel Sail
./vendor/bin/sail up -d

# Build production image
docker build -t erpsolution .

# Run production container
docker run -d -p 80:80 erpsolution
```

## 📝 Documentation

### Available Documentation
- [API Documentation](docs/API_DOCUMENTATION.md)
- [Code Standards](docs/CODE_STANDARDS.md)
- [Deployment Guide](docs/DEPLOYMENT_GUIDE.md)
- [User Manual](docs/USER_MANUAL.md)

### Code Quality
- PSR-12 coding standards
- Comprehensive documentation
- Automated code quality checks
- Security best practices
- Performance optimization

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and quality checks
5. Submit a pull request

### Code Standards
- Follow PSR-12 coding standards
- Write comprehensive tests
- Document all public methods
- Use meaningful variable names
- Follow Laravel best practices

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Getting Help
- Check the documentation
- Review the issue tracker
- Contact the development team
- Join the community forum

### Common Issues
- Database connection issues
- Permission problems
- Cache issues
- File upload problems

## 🔄 Updates

### Version History
- **v1.0.0**: Initial release with core features
- **v1.1.0**: Added API endpoints and documentation
- **v1.2.0**: Enhanced security and performance
- **v1.3.0**: Added advanced reporting features

### Upcoming Features
- Mobile application
- Advanced analytics
- Third-party integrations
- Multi-language support
- Advanced workflow automation

## 📞 Contact

- **Email**: support@yourdomain.com
- **Website**: https://yourdomain.com
- **Documentation**: https://docs.yourdomain.com
- **Issues**: https://github.com/yourusername/erpsolution/issues

---

**Built with ❤️ using Laravel 11**
# Business Management System

A comprehensive multi-module business management system built with Laravel 11, featuring property management, event booking, accounting, inventory management, utilities tracking, and more.

## Features

### Core Modules
- **User & Admin Management** - Role-based access control with permissions
- **Property Management** - Lease tracking, tenant management, rent expiry notifications
- **Event Booking System** - Full and partial payments, calendar view
- **Accounting System** - Income, expenses, journal entries, financial reports
- **Inventory Management** - Stock tracking, repairs, maintenance logs
- **Utilities Tracking** - Electricity, water, waste meter readings and billing
- **Tax & Revenue Collection** - VAT, AMAC, other tax calculations
- **Online Booking System** - Public-facing portal with payment integration

### Technical Features
- **Authentication** - Laravel Breeze with role-based permissions
- **Database** - MySQL 8.0 with proper relationships and indexes
- **Frontend** - Blade templates with Tailwind CSS and Alpine.js
- **Payments** - Paystack integration for Nigerian market
- **Reports** - PDF and Excel export capabilities
- **Notifications** - Email and in-app notifications
- **Activity Logging** - Comprehensive audit trail
- **Responsive Design** - Mobile-friendly interface

## Technology Stack

- **Backend**: Laravel 11, PHP 8.2+
- **Database**: MySQL 8.0
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js
- **Authentication**: Laravel Breeze with Spatie Permission
- **Payments**: Paystack
- **PDF Generation**: DomPDF
- **Excel Reports**: Laravel Excel
- **Activity Logging**: Spatie Activity Log

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL 8.0
- Node.js and NPM
- Git

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd erpsolution
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database Configuration**
   Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=erpsolution
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run Database Migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed the Database**
   ```bash
   php artisan db:seed
   ```

8. **Build Frontend Assets**
   ```bash
   npm run build
   ```

9. **Start the Development Server**
   ```bash
   php artisan serve
   ```

## Default Login Credentials

After seeding the database, you can login with:

- **Admin**: admin@example.com / password
- **Manager**: manager@example.com / password  
- **Staff**: staff@example.com / password

## Project Structure

```
erpsolution/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/           # Admin controllers
│   │   ├── Accounting/      # Accounting module
│   │   ├── Booking/         # Event booking module
│   │   ├── Property/        # Property management
│   │   ├── Inventory/       # Inventory management
│   │   ├── Utilities/       # Utilities tracking
│   │   ├── Tax/            # Tax management
│   │   └── Public/         # Public-facing controllers
│   ├── Models/              # Eloquent models
│   ├── Services/            # Business logic services
│   ├── Mail/               # Email templates
│   └── Notifications/      # Notification classes
├── database/
│   ├── migrations/         # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/         # Model factories
├── resources/
│   ├── views/             # Blade templates
│   ├── js/               # JavaScript files
│   └── css/              # CSS files
└── routes/               # Route definitions
```

## Database Schema

### Core Tables
- `users` - System users with roles
- `roles` - User roles (admin, manager, staff)
- `permissions` - System permissions
- `role_permissions` - Role-permission relationships
- `user_roles` - User-role assignments
- `settings` - System configuration
- `activity_logs` - Audit trail
- `notifications` - User notifications

### Property Management
- `property_types` - Property categories
- `properties` - Property listings
- `tenants` - Tenant information
- `leases` - Lease agreements
- `lease_payments` - Rent payments

### Event Booking
- `event_categories` - Event types
- `events` - Event listings
- `event_bookings` - Booking records
- `booking_payments` - Payment records

### Accounting
- `accounts` - Chart of accounts
- `transactions` - Financial transactions
- `journal_entries` - Journal entries
- `journal_entry_items` - Journal entry line items

## Key Features

### User Management
- Role-based access control
- User creation and management
- Permission assignment
- Activity logging

### Property Management
- Property listings with details
- Tenant management
- Lease tracking with expiry notifications
- Payment processing
- Maintenance scheduling

### Event Booking
- Event creation and management
- Online booking system
- Payment processing (Paystack)
- Calendar integration
- Partial payment support

### Accounting
- Double-entry bookkeeping
- Chart of accounts
- Transaction recording
- Financial reporting
- Tax calculations

### Reporting
- PDF report generation
- Excel export functionality
- Custom date ranges
- Multiple report formats

## Security Features

- CSRF protection
- XSS prevention
- SQL injection protection (Eloquent ORM)
- Password hashing (bcrypt)
- Role-based permissions
- Secure file uploads
- Activity logging

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

### Building Assets
```bash
npm run dev    # Development build
npm run build  # Production build
```

## Configuration

### Payment Gateway (Paystack)
Update your `.env` file:
```env
PAYSTACK_PUBLIC_KEY=your_public_key
PAYSTACK_SECRET_KEY=your_secret_key
PAYSTACK_PAYMENT_URL=https://api.paystack.co
```

### Email Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### Company Settings
Update company information in the settings table or through the admin panel.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For support and questions, please contact the development team.

## Changelog

### Version 1.0.0
- Initial release
- Core modules implemented
- User management system
- Property management
- Event booking system
- Accounting system
- Basic reporting functionality
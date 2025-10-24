# Business Management System API Documentation

## Overview
This document provides comprehensive API documentation for the Business Management System built with Laravel 11.

## Base URL
```
http://localhost:8000/api
```

## Authentication
All API endpoints require authentication using Laravel Sanctum tokens.

### Headers
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## Endpoints

### Properties

#### Get All Properties
```http
GET /api/properties
```

**Query Parameters:**
- `search` (string): Search by name, code, or address
- `status` (string): Filter by status (available, occupied, maintenance, unavailable)
- `property_type_id` (integer): Filter by property type
- `min_rent` (decimal): Minimum rent amount
- `max_rent` (decimal): Maximum rent amount
- `page` (integer): Page number for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "property_code": "PROP-000001",
      "name": "Luxury Apartment",
      "description": "Beautiful 3-bedroom apartment",
      "property_type": {
        "id": 1,
        "name": "Apartment"
      },
      "address": "123 Main Street",
      "city": "Lagos",
      "state": "Lagos",
      "rent_amount": 150000.00,
      "status": "available",
      "is_active": true,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/properties?page=1",
    "last": "http://localhost:8000/api/properties?page=10",
    "prev": null,
    "next": "http://localhost:8000/api/properties?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

#### Create Property
```http
POST /api/properties
```

**Request Body:**
```json
{
  "name": "Luxury Apartment",
  "description": "Beautiful 3-bedroom apartment",
  "property_type_id": 1,
  "address": "123 Main Street",
  "city": "Lagos",
  "state": "Lagos",
  "zip_code": "100001",
  "country": "Nigeria",
  "purchase_price": 50000000.00,
  "current_value": 60000000.00,
  "year_built": 2020,
  "number_of_units": 1,
  "status": "available",
  "notes": "Prime location"
}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "property_code": "PROP-000001",
    "name": "Luxury Apartment",
    "description": "Beautiful 3-bedroom apartment",
    "property_type_id": 1,
    "address": "123 Main Street",
    "city": "Lagos",
    "state": "Lagos",
    "zip_code": "100001",
    "country": "Nigeria",
    "purchase_price": 50000000.00,
    "current_value": 60000000.00,
    "year_built": 2020,
    "number_of_units": 1,
    "status": "available",
    "is_active": true,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
  },
  "message": "Property created successfully"
}
```

### Transactions

#### Get All Transactions
```http
GET /api/transactions
```

**Query Parameters:**
- `search` (string): Search by reference, description, or reference number
- `transaction_type` (string): Filter by type (income, expense, transfer)
- `status` (string): Filter by status (pending, approved, rejected, cancelled)
- `account_id` (integer): Filter by account
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date
- `page` (integer): Page number for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "transaction_reference": "TXN-000001",
      "account": {
        "id": 1,
        "account_name": "Main Account",
        "account_type": "asset"
      },
      "transaction_type": "income",
      "amount": 150000.00,
      "description": "Rent payment",
      "transaction_date": "2024-01-01",
      "status": "approved",
      "created_by": {
        "id": 1,
        "name": "John Doe"
      },
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/transactions?page=1",
    "last": "http://localhost:8000/api/transactions?page=10",
    "prev": null,
    "next": "http://localhost:8000/api/transactions?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

#### Create Transaction
```http
POST /api/transactions
```

**Request Body:**
```json
{
  "account_id": 1,
  "transaction_type": "income",
  "amount": 150000.00,
  "description": "Rent payment",
  "transaction_date": "2024-01-01",
  "category": "rent",
  "subcategory": "monthly_rent",
  "payment_method": "bank_transfer",
  "reference_number": "REF123456",
  "notes": "Monthly rent payment"
}
```

### Leases

#### Get All Leases
```http
GET /api/leases
```

**Query Parameters:**
- `search` (string): Search by reference, tenant name, or email
- `status` (string): Filter by status (active, terminated, cancelled)
- `property_id` (integer): Filter by property
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date
- `page` (integer): Page number for pagination

#### Create Lease
```http
POST /api/leases
```

**Request Body:**
```json
{
  "property_id": 1,
  "tenant_name": "John Doe",
  "tenant_email": "john@example.com",
  "tenant_phone": "+2348012345678",
  "tenant_address": "123 Tenant Street",
  "start_date": "2024-01-01",
  "end_date": "2024-12-31",
  "monthly_rent": 150000.00,
  "security_deposit": 300000.00,
  "late_fee": 5000.00,
  "grace_period_days": 5,
  "terms_conditions": "Standard lease terms",
  "notes": "First-time tenant"
}
```

### Events

#### Get All Events
```http
GET /api/events
```

**Query Parameters:**
- `search` (string): Search by title, reference, or venue
- `status` (string): Filter by status (draft, published, cancelled, completed)
- `city` (string): Filter by city
- `min_price` (decimal): Minimum price
- `max_price` (decimal): Maximum price
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date
- `page` (integer): Page number for pagination

#### Create Event
```http
POST /api/events
```

**Request Body:**
```json
{
  "title": "Business Conference 2024",
  "description": "Annual business conference",
  "start_date": "2024-06-01",
  "end_date": "2024-06-03",
  "venue": "Lagos Convention Centre",
  "city": "Lagos",
  "state": "Lagos",
  "capacity": 500,
  "price": 50000.00,
  "status": "published",
  "terms_conditions": "Standard event terms",
  "notes": "Early bird discount available"
}
```

### Bookings

#### Get All Bookings
```http
GET /api/bookings
```

**Query Parameters:**
- `search` (string): Search by reference, customer name, or email
- `booking_status` (string): Filter by booking status
- `payment_status` (string): Filter by payment status
- `event_id` (integer): Filter by event
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date
- `page` (integer): Page number for pagination

#### Create Booking
```http
POST /api/bookings
```

**Request Body:**
```json
{
  "event_id": 1,
  "customer_name": "Jane Doe",
  "customer_email": "jane@example.com",
  "customer_phone": "+2348012345679",
  "ticket_quantity": 2,
  "payment_method": "bank_transfer"
}
```

## Error Responses

### Validation Error (422)
```json
{
  "error": true,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be a valid email address."]
  },
  "error_code": "VALIDATION_ERROR"
}
```

### Business Logic Error (400)
```json
{
  "error": true,
  "message": "Property already has an active lease",
  "error_code": "BUSINESS_LOGIC_ERROR",
  "context": {
    "property_id": 1,
    "existing_lease_id": 5
  }
}
```

### Authorization Error (403)
```json
{
  "error": true,
  "message": "You do not have permission to perform this action",
  "error_code": "AUTHORIZATION_ERROR",
  "required_permission": "create_properties",
  "required_role": "admin"
}
```

### Not Found Error (404)
```json
{
  "error": true,
  "message": "The requested resource was not found",
  "error_code": "NOT_FOUND"
}
```

### Server Error (500)
```json
{
  "error": true,
  "message": "An unexpected error occurred. Please try again later.",
  "error_code": "SERVER_ERROR",
  "error_id": "err_1234567890"
}
```

## Rate Limiting

API endpoints are rate limited to prevent abuse:
- **General endpoints**: 60 requests per minute
- **Authentication endpoints**: 5 requests per minute
- **File upload endpoints**: 10 requests per minute

## Pagination

All list endpoints support pagination with the following parameters:
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15, max: 100)

## Filtering and Sorting

Most list endpoints support:
- **Search**: Text search across relevant fields
- **Filtering**: Filter by specific field values
- **Sorting**: Sort by any field (default: created_at desc)

## File Uploads

File uploads are supported for:
- Property images
- Event images
- User avatars
- Document attachments

**File restrictions:**
- Maximum file size: 2MB
- Allowed types: JPEG, PNG, JPG, GIF
- Maximum files per upload: 5

## Webhooks

The system supports webhooks for:
- Transaction status changes
- Lease status changes
- Event status changes
- User registration

**Webhook URL format:**
```
POST /api/webhooks/{event_type}
```

**Webhook payload:**
```json
{
  "event": "transaction.approved",
  "data": {
    "id": 1,
    "transaction_reference": "TXN-000001",
    "amount": 150000.00,
    "status": "approved"
  },
  "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

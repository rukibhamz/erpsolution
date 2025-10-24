# Code Standards and Best Practices

## Overview
This document outlines the coding standards and best practices for the Business Management System.

## PHP/Laravel Standards

### 1. Code Style

#### PSR-12 Compliance
- Follow PSR-12 coding standards
- Use 4 spaces for indentation (no tabs)
- Maximum line length: 120 characters
- Use meaningful variable and method names

#### Naming Conventions
```php
// Classes: PascalCase
class PropertyController extends Controller

// Methods: camelCase
public function createProperty()

// Variables: camelCase
$propertyData = [];

// Constants: UPPER_SNAKE_CASE
const MAX_FILE_SIZE = 2048;

// Database columns: snake_case
$table->string('property_code');
```

### 2. File Organization

#### Directory Structure
```
app/
├── Console/
│   └── Commands/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Property/
│   │   ├── Accounting/
│   │   └── Booking/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Rules/
├── Services/
└── Notifications/
```

#### File Naming
- Controllers: `{Entity}Controller.php`
- Models: `{Entity}.php`
- Services: `{Entity}Service.php`
- Requests: `{Action}{Entity}Request.php`
- Policies: `{Entity}Policy.php`

### 3. Code Documentation

#### Class Documentation
```php
/**
 * Property Management Controller
 * 
 * Handles all property-related operations including CRUD operations,
 * image management, and status updates.
 * 
 * @package App\Http\Controllers\Property
 * @author Business Management System
 * @version 1.0.0
 */
class PropertyController extends Controller
{
    // Implementation
}
```

#### Method Documentation
```php
/**
 * Create a new property with validation and image handling
 * 
 * @param StorePropertyRequest $request Validated property data
 * @return RedirectResponse Redirect to properties index with success message
 * @throws BusinessLogicException When property creation fails
 * @throws ValidationException When validation fails
 * 
 * @example
 * POST /admin/properties
 * {
 *   "name": "Luxury Apartment",
 *   "property_type_id": 1,
 *   "address": "123 Main Street"
 * }
 */
public function store(StorePropertyRequest $request): RedirectResponse
{
    // Implementation
}
```

#### Inline Comments
```php
// Generate unique property code with race condition protection
$propertyCode = $this->generatePropertyCode();

// Handle image uploads with error handling
$images = [];
if ($request->hasFile('images')) {
    try {
        foreach ($request->file('images') as $image) {
            $path = $image->store('properties', 'public');
            $images[] = $path;
        }
    } catch (Exception $e) {
        // Log error and throw business logic exception
        throw new BusinessLogicException(
            'Failed to upload images. Please try again.',
            'IMAGE_UPLOAD_ERROR',
            ['error' => $e->getMessage()]
        );
    }
}
```

### 4. Database Standards

#### Migration Standards
```php
/**
 * Create properties table with proper indexes and constraints
 */
public function up(): void
{
    Schema::create('properties', function (Blueprint $table) {
        // Primary key
        $table->id();
        
        // Business fields
        $table->string('property_code')->unique();
        $table->string('name');
        $table->text('description')->nullable();
        
        // Foreign keys
        $table->foreignId('property_type_id')->constrained()->onDelete('cascade');
        
        // Location fields
        $table->string('address');
        $table->string('city');
        $table->string('state');
        $table->string('zip_code')->nullable();
        $table->string('country')->default('Nigeria');
        
        // Financial fields
        $table->decimal('purchase_price', 15, 2);
        $table->decimal('current_value', 15, 2);
        
        // Status fields
        $table->enum('status', ['available', 'occupied', 'maintenance', 'unavailable'])
              ->default('available');
        $table->boolean('is_active')->default(true);
        
        // JSON fields
        $table->json('images')->nullable();
        
        // Timestamps
        $table->timestamps();
        
        // Indexes for performance
        $table->index(['status', 'is_active']);
        $table->index(['city', 'state']);
        $table->index(['property_type_id', 'status']);
    });
}
```

#### Model Standards
```php
/**
 * Property Model
 * 
 * Represents a property in the system with relationships
 * to property types, leases, and other entities.
 */
class Property extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'property_code',
        'name',
        'description',
        'property_type_id',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'purchase_price',
        'current_value',
        'year_built',
        'number_of_units',
        'status',
        'images',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'images' => 'array',
        'purchase_price' => 'decimal:2',
        'current_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the property type that owns the property
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /**
     * Get the leases for the property
     */
    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }
}
```

### 5. Service Layer Standards

#### Service Class Structure
```php
/**
 * Property Management Service
 * 
 * Encapsulates business logic for property operations
 * including status management and validation.
 */
class PropertyStatusService
{
    /**
     * Validate property status change
     * 
     * @param Property $property The property to validate
     * @param string $newStatus The new status to set
     * @return array Array of validation errors
     */
    public function validateStatusChange(Property $property, string $newStatus): array
    {
        $errors = [];
        
        // Business rule: Cannot change to available if property has active lease
        if ($newStatus === 'available' && $property->hasActiveLease()) {
            $errors[] = 'Cannot set property to available while it has an active lease.';
        }
        
        // Business rule: Cannot change to occupied without lease
        if ($newStatus === 'occupied' && !$property->hasActiveLease()) {
            $errors[] = 'Cannot set property to occupied without an active lease.';
        }
        
        return $errors;
    }
}
```

### 6. Error Handling Standards

#### Exception Handling
```php
/**
 * Handle property creation with comprehensive error handling
 */
public function store(StorePropertyRequest $request): RedirectResponse
{
    try {
        // Validate authorization
        $this->authorize('create', Property::class);
        
        // Process the request
        $property = $this->createProperty($request->validated());
        
        // Log successful creation
        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property created');
        
        return redirect()->route('admin.properties.index')
            ->with('success', 'Property created successfully.');
            
    } catch (BusinessLogicException $e) {
        // Handle business logic errors
        return redirect()->back()
            ->with('error', $e->getMessage())
            ->with('error_code', $e->getErrorCode())
            ->withInput();
            
    } catch (ValidationException $e) {
        // Handle validation errors
        return redirect()->back()
            ->withErrors($e->getErrors())
            ->withInput();
            
    } catch (Exception $e) {
        // Handle unexpected errors
        $errorService = new ErrorHandlingService();
        $errorService->handleError($e, 'Property Creation');
        
        return redirect()->back()
            ->with('error', 'An error occurred while creating the property. Please try again.')
            ->withInput();
    }
}
```

### 7. Testing Standards

#### Test Structure
```php
/**
 * Property Controller Test
 * 
 * Tests all property-related functionality including
 * CRUD operations, validation, and authorization.
 */
class PropertyControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test property creation with valid data
     */
    public function test_can_create_property_with_valid_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        $propertyData = [
            'name' => 'Test Property',
            'description' => 'Test Description',
            'property_type_id' => $propertyType->id,
            'address' => '123 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'purchase_price' => 1000000,
            'current_value' => 1200000,
        ];
        
        // Act
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), $propertyData);
        
        // Assert
        $response->assertRedirect(route('admin.properties.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('properties', [
            'name' => 'Test Property',
            'property_type_id' => $propertyType->id,
        ]);
    }
}
```

### 8. Security Standards

#### Input Validation
```php
/**
 * Validate property creation request
 */
class StorePropertyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'property_type_id' => 'required|exists:property_types,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'current_value' => 'required|numeric|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
```

#### Authorization
```php
/**
 * Property Policy
 */
class PropertyPolicy
{
    /**
     * Determine if the user can view any properties
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_properties');
    }
    
    /**
     * Determine if the user can create properties
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_properties');
    }
}
```

### 9. Performance Standards

#### Query Optimization
```php
/**
 * Get optimized properties with eager loading
 */
public function getOptimizedProperties($filters = [])
{
    $query = Property::with([
        'propertyType:id,name,description',
        'leases' => function ($q) {
            $q->select('id', 'property_id', 'status', 'start_date', 'end_date', 'tenant_name')
              ->where('status', 'active')
              ->where('start_date', '<=', now())
              ->where('end_date', '>=', now());
        }
    ]);
    
    // Apply filters with proper indexing
    if (isset($filters['status']) && $filters['status']) {
        $query->where('status', $filters['status']);
    }
    
    return $query->select([
        'id', 'name', 'property_code', 'property_type_id', 'address', 'city', 'state',
        'rent_amount', 'deposit_amount', 'bedrooms', 'bathrooms', 'status', 'is_active',
        'created_at', 'updated_at'
    ]);
}
```

### 10. Documentation Standards

#### README Structure
```markdown
# Business Management System

## Overview
Comprehensive business management system built with Laravel 11.

## Features
- Property Management
- Accounting System
- Event Booking
- User Management

## Installation
1. Clone the repository
2. Install dependencies
3. Configure environment
4. Run migrations
5. Seed database

## Usage
- Admin Dashboard
- Property Management
- Transaction Processing

## API Documentation
See [API_DOCUMENTATION.md](docs/API_DOCUMENTATION.md)

## Code Standards
See [CODE_STANDARDS.md](docs/CODE_STANDARDS.md)
```

## Conclusion

Following these standards ensures:
- **Consistency**: All code follows the same patterns
- **Maintainability**: Easy to understand and modify
- **Quality**: High-quality, production-ready code
- **Collaboration**: Team members can work together effectively
- **Documentation**: Comprehensive documentation for all components

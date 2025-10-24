<?php

namespace Tests\Functional;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Property;
use App\Models\Lease;
use App\Models\PropertyType;

class SecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test CSRF protection on AJAX routes
     */
    public function test_csrf_protection_on_ajax_routes()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Test without CSRF token (should fail)
        $response = $this->actingAs($user)
            ->patch(route('admin.properties.toggle-status', $property));
        
        $this->assertEquals(419, $response->status()); // CSRF token mismatch
        
        // Test with CSRF token (should succeed)
        $response = $this->actingAs($user)
            ->withSession(['_token' => csrf_token()])
            ->patch(route('admin.properties.toggle-status', $property));
        
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test file upload security validation
     */
    public function test_file_upload_security_validation()
    {
        $user = User::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        // Test with malicious file (should fail)
        $maliciousFile = UploadedFile::fake()->create('malicious.php', 1000, 'text/php');
        
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), [
                'name' => 'Test Property',
                'property_type_id' => $propertyType->id,
                'address' => '123 Test St',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => 1000,
                'images' => [$maliciousFile]
            ]);
        
        $response->assertSessionHasErrors('images.0.mimes');
        
        // Test with oversized file (should fail)
        $oversizedFile = UploadedFile::fake()->image('test.jpg')->size(3000); // 3MB
        
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), [
                'name' => 'Test Property 2',
                'property_type_id' => $propertyType->id,
                'address' => '123 Test St',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => 1000,
                'images' => [$oversizedFile]
            ]);
        
        $response->assertSessionHasErrors('images.0.max');
        
        // Test with valid file (should succeed)
        $validFile = UploadedFile::fake()->image('test.jpg')->size(1000); // 1MB
        
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), [
                'name' => 'Test Property 3',
                'property_type_id' => $propertyType->id,
                'address' => '123 Test St',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => 1000,
                'images' => [$validFile]
            ]);
        
        $response->assertRedirect(route('admin.properties.index'));
        $this->assertDatabaseHas('properties', ['name' => 'Test Property 3']);
    }

    /**
     * Test authorization checks
     */
    public function test_authorization_checks()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Test unauthorized access to edit property
        $response = $this->actingAs($user)
            ->get(route('admin.properties.edit', $property));
        
        // Should be redirected or get 403 if user doesn't have permission
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 403
        );
    }

    /**
     * Test password strength validation
     */
    public function test_password_strength_validation()
    {
        // Test weak password (should fail)
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => '123' // Too short
        ]);
        
        $response->assertSessionHasErrors('password');
        
        // Test strong password (should pass validation)
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'StrongPass123!' // Meets all requirements
        ]);
        
        // Should not have password validation errors
        $this->assertFalse($response->session()->hasErrors('password'));
    }

    /**
     * Test lease overlap validation
     */
    public function test_lease_overlap_validation()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        // Create existing lease
        $existingLease = Lease::factory()->create([
            'property_id' => $property->id,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);
        
        // Try to create overlapping lease (should fail)
        $response = $this->actingAs($user)
            ->post(route('admin.leases.store'), [
                'property_id' => $property->id,
                'tenant_name' => 'Test Tenant',
                'tenant_email' => 'tenant@example.com',
                'tenant_phone' => '+2348012345678',
                'tenant_address' => '123 Test St',
                'start_date' => '2024-06-01', // Overlaps with existing lease
                'end_date' => '2025-06-01',
                'monthly_rent' => 1000,
                'security_deposit' => 2000
            ]);
        
        $response->assertSessionHasErrors('end_date');
        
        // Try to create non-overlapping lease (should succeed)
        $response = $this->actingAs($user)
            ->post(route('admin.leases.store'), [
                'property_id' => $property->id,
                'tenant_name' => 'Test Tenant 2',
                'tenant_email' => 'tenant2@example.com',
                'tenant_phone' => '+2348012345679',
                'tenant_address' => '123 Test St',
                'start_date' => '2025-01-01', // No overlap
                'end_date' => '2025-12-31',
                'monthly_rent' => 1000,
                'security_deposit' => 2000
            ]);
        
        $response->assertRedirect(route('admin.leases.index'));
        $this->assertDatabaseHas('leases', ['tenant_email' => 'tenant2@example.com']);
    }
}

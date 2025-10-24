<?php

namespace Tests\Functional;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyType;
use App\Exceptions\BusinessLogicException;

class ErrorHandlingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test comprehensive error handling in property update
     */
    public function test_property_update_error_handling()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        // Test with invalid data (should handle gracefully)
        $response = $this->actingAs($user)
            ->put(route('admin.properties.update', $property), [
                'name' => '', // Invalid: empty name
                'property_type_id' => 999, // Invalid: non-existent type
                'address' => 'Test Address',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => -100, // Invalid: negative amount
                'status' => 'available'
            ]);
        
        // Should redirect back with errors
        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'property_type_id', 'rent_amount']);
    }

    /**
     * Test image upload error handling
     */
    public function test_image_upload_error_handling()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Mock storage to throw exception
        Storage::fake('public');
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturnSelf();
        Storage::shouldReceive('store')
            ->andThrow(new \Exception('Storage error'));
        
        $response = $this->actingAs($user)
            ->put(route('admin.properties.update', $property), [
                'name' => 'Updated Property',
                'property_type_id' => $property->property_type_id,
                'address' => 'Updated Address',
                'city' => 'Updated City',
                'state' => 'Updated State',
                'rent_amount' => 1500,
                'status' => 'available',
                'images' => [UploadedFile::fake()->image('test.jpg')]
            ]);
        
        // Should handle error gracefully
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test authorization error handling
     */
    public function test_authorization_error_handling()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Remove user permissions
        $user->revokePermissionTo('update-properties');
        
        $response = $this->actingAs($user)
            ->get(route('admin.properties.edit', $property));
        
        // Should be redirected or get 403
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 403
        );
    }

    /**
     * Test business logic exception handling
     */
    public function test_business_logic_exception_handling()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Mock a business logic exception
        $this->mock(\App\Services\PropertyStatusService::class, function ($mock) {
            $mock->shouldReceive('validateStatusChange')
                ->andThrow(new BusinessLogicException('Invalid status change', 'INVALID_STATUS'));
        });
        
        $response = $this->actingAs($user)
            ->patch(route('admin.properties.toggle-status', $property));
        
        // Should handle business logic exception gracefully
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test database error handling
     */
    public function test_database_error_handling()
    {
        $user = User::factory()->create();
        
        // Test with invalid property type (database constraint)
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), [
                'name' => 'Test Property',
                'property_type_id' => 99999, // Non-existent ID
                'address' => 'Test Address',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => 1000
            ]);
        
        // Should handle database constraint error
        $response->assertSessionHasErrors('property_type_id');
    }
}

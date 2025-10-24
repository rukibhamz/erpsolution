<?php

namespace Tests\Unit;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Lease;
use App\Services\PropertyStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_property()
    {
        $propertyType = PropertyType::factory()->create();
        
        $property = Property::create([
            'property_code' => 'PROP-000001',
            'name' => 'Test Property',
            'description' => 'Test Description',
            'property_type_id' => $propertyType->id,
            'address' => '123 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'zip_code' => '100001',
            'country' => 'Nigeria',
            'purchase_price' => 1000000,
            'current_value' => 1200000,
            'year_built' => 2020,
            'number_of_units' => 1,
            'status' => 'available',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('Test Property', $property->name);
        $this->assertEquals('available', $property->status);
        $this->assertTrue($property->is_active);
    }

    /** @test */
    public function it_belongs_to_a_property_type()
    {
        $propertyType = PropertyType::factory()->create();
        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id
        ]);

        $this->assertInstanceOf(PropertyType::class, $property->propertyType);
        $this->assertEquals($propertyType->id, $property->propertyType->id);
    }

    /** @test */
    public function it_has_many_leases()
    {
        $property = Property::factory()->create();
        $lease1 = Lease::factory()->create(['property_id' => $property->id]);
        $lease2 = Lease::factory()->create(['property_id' => $property->id]);

        $this->assertCount(2, $property->leases);
        $this->assertTrue($property->leases->contains($lease1));
        $this->assertTrue($property->leases->contains($lease2));
    }

    /** @test */
    public function it_can_have_images()
    {
        $images = ['properties/image1.jpg', 'properties/image2.jpg'];
        $property = Property::factory()->create(['images' => $images]);

        $this->assertIsArray($property->images);
        $this->assertCount(2, $property->images);
        $this->assertEquals($images, $property->images);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $property = Property::factory()->create();
        $propertyId = $property->id;

        $property->delete();

        $this->assertSoftDeleted('properties', ['id' => $propertyId]);
    }

    /** @test */
    public function it_can_scope_available_properties()
    {
        Property::factory()->available()->create();
        Property::factory()->occupied()->create();
        Property::factory()->maintenance()->create();

        $availableProperties = Property::available()->get();

        $this->assertCount(1, $availableProperties);
        $this->assertEquals('available', $availableProperties->first()->status);
    }

    /** @test */
    public function it_can_scope_occupied_properties()
    {
        Property::factory()->available()->create();
        Property::factory()->occupied()->create();
        Property::factory()->maintenance()->create();

        $occupiedProperties = Property::occupied()->get();

        $this->assertCount(1, $occupiedProperties);
        $this->assertEquals('occupied', $occupiedProperties->first()->status);
    }

    /** @test */
    public function it_can_scope_active_properties()
    {
        Property::factory()->create(['is_active' => true]);
        Property::factory()->create(['is_active' => false]);

        $activeProperties = Property::active()->get();

        $this->assertCount(1, $activeProperties);
        $this->assertTrue($activeProperties->first()->is_active);
    }

    /** @test */
    public function it_can_check_if_property_has_active_lease()
    {
        $property = Property::factory()->create();
        
        // No lease
        $this->assertFalse($property->hasActiveLease());
        
        // Active lease
        Lease::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30)
        ]);
        
        $property->refresh();
        $this->assertTrue($property->hasActiveLease());
    }

    /** @test */
    public function it_can_get_active_lease()
    {
        $property = Property::factory()->create();
        $activeLease = Lease::factory()->create([
            'property_id' => $property->id,
            'status' => 'active'
        ]);
        $inactiveLease = Lease::factory()->create([
            'property_id' => $property->id,
            'status' => 'terminated'
        ]);

        $this->assertEquals($activeLease->id, $property->activeLease->id);
    }

    /** @test */
    public function it_can_calculate_property_value_appreciation()
    {
        $property = Property::factory()->create([
            'purchase_price' => 1000000,
            'current_value' => 1200000
        ]);

        $appreciation = $property->getValueAppreciation();

        $this->assertEquals(200000, $appreciation);
        $this->assertEquals(20, $property->getValueAppreciationPercentage());
    }

    /** @test */
    public function it_can_get_property_location_string()
    {
        $property = Property::factory()->create([
            'address' => '123 Main Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'zip_code' => '100001'
        ]);

        $location = $property->getLocationString();

        $this->assertEquals('123 Main Street, Lagos, Lagos 100001', $location);
    }

    /** @test */
    public function it_can_get_property_full_name()
    {
        $property = Property::factory()->create([
            'name' => 'Luxury Apartment',
            'property_code' => 'PROP-000001'
        ]);

        $fullName = $property->getFullName();

        $this->assertEquals('Luxury Apartment (PROP-000001)', $fullName);
    }

    /** @test */
    public function it_can_validate_property_status_change()
    {
        $property = Property::factory()->available()->create();
        $service = new PropertyStatusService();

        // Test changing to occupied without lease
        $errors = $service->validateStatusChange($property, 'occupied');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('active lease', $errors[0]);

        // Test changing to available with active lease
        Lease::factory()->create([
            'property_id' => $property->id,
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'end_date' => now()->addDays(30)
        ]);
        
        $property->refresh();
        $errors = $service->validateStatusChange($property, 'available');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('active lease', $errors[0]);
    }

    /** @test */
    public function it_can_get_property_statistics()
    {
        $property = Property::factory()->create([
            'purchase_price' => 1000000,
            'current_value' => 1200000,
            'year_built' => 2020
        ]);

        $stats = $property->getStatistics();

        $this->assertArrayHasKey('age', $stats);
        $this->assertArrayHasKey('appreciation', $stats);
        $this->assertArrayHasKey('appreciation_percentage', $stats);
        $this->assertEquals(200000, $stats['appreciation']);
        $this->assertEquals(20, $stats['appreciation_percentage']);
    }

    /** @test */
    public function it_can_handle_property_images_correctly()
    {
        $property = Property::factory()->create(['images' => null]);
        
        // Test adding images
        $property->addImage('properties/new-image.jpg');
        $this->assertCount(1, $property->images);
        
        // Test removing image
        $property->removeImage(0);
        $this->assertCount(0, $property->images);
    }

    /** @test */
    public function it_can_validate_property_data()
    {
        $propertyData = [
            'name' => 'Test Property',
            'property_type_id' => 1,
            'address' => '123 Test Street',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'purchase_price' => 1000000,
            'current_value' => 1200000,
        ];

        $property = Property::create($propertyData);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals('Test Property', $property->name);
    }

    /** @test */
    public function it_can_handle_property_relationships()
    {
        $propertyType = PropertyType::factory()->create();
        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id
        ]);

        $this->assertInstanceOf(PropertyType::class, $property->propertyType);
        $this->assertEquals($propertyType->id, $property->propertyType->id);
    }
}

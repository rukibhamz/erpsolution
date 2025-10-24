<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Models\Lease;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with admin role
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
    }

    /** @test */
    public function it_can_display_properties_index()
    {
        // Create some test properties
        Property::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.properties.index');
        $response->assertViewHas('properties');
    }

    /** @test */
    public function it_can_create_a_property()
    {
        $propertyType = PropertyType::factory()->create();
        
        $propertyData = [
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
            'notes' => 'Test notes'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.properties.store'), $propertyData);

        $response->assertRedirect(route('admin.properties.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('properties', [
            'name' => 'Test Property',
            'property_type_id' => $propertyType->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_property()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.properties.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'property_type_id',
            'address',
            'city',
            'state',
            'purchase_price',
            'current_value'
        ]);
    }

    /** @test */
    public function it_can_update_a_property()
    {
        $property = Property::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        $updateData = [
            'name' => 'Updated Property Name',
            'description' => 'Updated Description',
            'property_type_id' => $propertyType->id,
            'address' => '456 Updated Street',
            'city' => 'Abuja',
            'state' => 'FCT',
            'zip_code' => '900001',
            'country' => 'Nigeria',
            'purchase_price' => 2000000,
            'current_value' => 2400000,
            'year_built' => 2021,
            'number_of_units' => 2,
            'notes' => 'Updated notes'
        ];

        $response = $this->actingAs($this->user)
            ->put(route('admin.properties.update', $property), $updateData);

        $response->assertRedirect(route('admin.properties.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'Updated Property Name',
            'city' => 'Abuja',
        ]);
    }

    /** @test */
    public function it_can_delete_a_property()
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('admin.properties.destroy', $property));

        $response->assertRedirect(route('admin.properties.index'));
        $response->assertSessionHas('success');
        
        $this->assertSoftDeleted('properties', [
            'id' => $property->id,
        ]);
    }

    /** @test */
    public function it_can_toggle_property_status()
    {
        $property = Property::factory()->available()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.properties.toggle-status', $property));

        $response->assertRedirect();
        
        $property->refresh();
        $this->assertEquals('unavailable', $property->status);
    }

    /** @test */
    public function it_can_search_properties()
    {
        Property::factory()->create(['name' => 'Lagos Apartment']);
        Property::factory()->create(['name' => 'Abuja House']);

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.index', ['search' => 'Lagos']));

        $response->assertStatus(200);
        $response->assertSee('Lagos Apartment');
        $response->assertDontSee('Abuja House');
    }

    /** @test */
    public function it_can_filter_properties_by_status()
    {
        Property::factory()->available()->create();
        Property::factory()->occupied()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.index', ['status' => 'available']));

        $response->assertStatus(200);
        $response->assertSee('available');
    }

    /** @test */
    public function it_can_filter_properties_by_city()
    {
        Property::factory()->lagos()->create();
        Property::factory()->abuja()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.index', ['city' => 'Lagos']));

        $response->assertStatus(200);
        $response->assertSee('Lagos');
    }

    /** @test */
    public function it_can_upload_property_images()
    {
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
            'year_built' => 2020,
            'number_of_units' => 1,
        ];

        // Create fake image files
        $image1 = \Illuminate\Http\UploadedFile::fake()->image('property1.jpg');
        $image2 = \Illuminate\Http\UploadedFile::fake()->image('property2.jpg');

        $propertyData['images'] = [$image1, $image2];

        $response = $this->actingAs($this->user)
            ->post(route('admin.properties.store'), $propertyData);

        $response->assertRedirect(route('admin.properties.index'));
        
        $property = Property::where('name', 'Test Property')->first();
        $this->assertNotNull($property->images);
        $this->assertCount(2, $property->images);
    }

    /** @test */
    public function it_can_remove_property_images()
    {
        $property = Property::factory()->create([
            'images' => ['properties/image1.jpg', 'properties/image2.jpg']
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('admin.properties.remove-image', $property), [
                'image_index' => 0
            ]);

        $response->assertRedirect();
        
        $property->refresh();
        $this->assertCount(1, $property->images);
        $this->assertEquals('properties/image2.jpg', $property->images[0]);
    }

    /** @test */
    public function it_can_display_property_details()
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.show', $property));

        $response->assertStatus(200);
        $response->assertViewIs('admin.properties.show');
        $response->assertViewHas('property', $property);
    }

    /** @test */
    public function it_can_display_property_edit_form()
    {
        $property = Property::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.edit', $property));

        $response->assertStatus(200);
        $response->assertViewIs('admin.properties.edit');
        $response->assertViewHas('property', $property);
    }

    /** @test */
    public function it_can_display_property_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.properties.create');
        $response->assertViewHas('propertyTypes');
    }

    /** @test */
    public function it_prevents_unauthorized_access()
    {
        $user = User::factory()->create();
        $user->assignRole('staff'); // Limited access

        $response = $this->actingAs($user)
            ->get(route('admin.properties.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_export_properties_to_pdf()
    {
        Property::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.export.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_export_properties_to_excel()
    {
        Property::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.properties.export.excel'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

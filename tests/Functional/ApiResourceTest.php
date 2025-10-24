<?php

namespace Tests\Functional;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Property;
use App\Models\Lease;
use App\Models\Transaction;
use App\Models\PropertyType;
use App\Models\Account;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\LeaseResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;

class ApiResourceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test PropertyResource transformation
     */
    public function test_property_resource_transformation()
    {
        $propertyType = PropertyType::factory()->create();
        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id,
            'rent_amount' => 1500.50,
            'deposit_amount' => 3000.00
        ]);
        
        $resource = new PropertyResource($property);
        $array = $resource->toArray(request());
        
        // Test basic structure
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('property_code', $array);
        $this->assertArrayHasKey('financial', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('address', $array);
        
        // Test financial data
        $this->assertEquals(1500.50, $array['financial']['rent_amount']);
        $this->assertEquals('₦1,500.50', $array['financial']['formatted_rent_amount']);
        
        // Test status data
        $this->assertArrayHasKey('current', $array['status']);
        $this->assertArrayHasKey('is_available', $array['status']);
        $this->assertArrayHasKey('is_occupied', $array['status']);
    }

    /**
     * Test LeaseResource transformation
     */
    public function test_lease_resource_transformation()
    {
        $property = Property::factory()->create();
        $lease = Lease::factory()->create([
            'property_id' => $property->id,
            'monthly_rent' => 2000.00,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31'
        ]);
        
        $resource = new LeaseResource($lease);
        $array = $resource->toArray(request());
        
        // Test basic structure
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('lease_reference', $array);
        $this->assertArrayHasKey('property', $array);
        $this->assertArrayHasKey('tenant', $array);
        $this->assertArrayHasKey('lease_period', $array);
        $this->assertArrayHasKey('financial', $array);
        
        // Test financial data
        $this->assertEquals(2000.00, $array['financial']['monthly_rent']);
        
        // Test lease period
        $this->assertEquals('2024-01-01', $array['lease_period']['start_date']);
        $this->assertEquals('2024-12-31', $array['lease_period']['end_date']);
        $this->assertIsInt($array['lease_period']['duration_days']);
    }

    /**
     * Test TransactionResource transformation
     */
    public function test_transaction_resource_transformation()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        $transaction = Transaction::factory()->create([
            'account_id' => $account->id,
            'created_by' => $user->id,
            'amount' => 5000.00,
            'transaction_type' => 'income'
        ]);
        
        $resource = new TransactionResource($transaction);
        $array = $resource->toArray(request());
        
        // Test basic structure
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('transaction_reference', $array);
        $this->assertArrayHasKey('account', $array);
        $this->assertArrayHasKey('transaction_details', $array);
        $this->assertArrayHasKey('status', $array);
        
        // Test transaction details
        $this->assertEquals(5000.00, $array['transaction_details']['amount']);
        $this->assertEquals('income', $array['transaction_details']['type']);
        
        // Test status
        $this->assertArrayHasKey('current', $array['status']);
        $this->assertArrayHasKey('is_approved', $array['status']);
    }

    /**
     * Test UserResource transformation
     */
    public function test_user_resource_transformation()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        
        $resource = new UserResource($user);
        $array = $resource->toArray(request());
        
        // Test basic structure
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('is_active', $array);
        $this->assertArrayHasKey('timestamps', $array);
        
        // Test data
        $this->assertEquals('Test User', $array['name']);
        $this->assertEquals('test@example.com', $array['email']);
    }

    /**
     * Test API resource collection
     */
    public function test_api_resource_collection()
    {
        $properties = Property::factory()->count(3)->create();
        
        $collection = PropertyResource::collection($properties);
        $array = $collection->toArray(request());
        
        $this->assertCount(3, $array);
        $this->assertArrayHasKey('id', $array[0]);
        $this->assertArrayHasKey('name', $array[0]);
    }

    /**
     * Test API resource with relationships
     */
    public function test_api_resource_with_relationships()
    {
        $propertyType = PropertyType::factory()->create(['name' => 'Apartment']);
        $property = Property::factory()->create([
            'property_type_id' => $propertyType->id
        ]);
        
        // Load relationship
        $property->load('propertyType');
        
        $resource = new PropertyResource($property);
        $array = $resource->toArray(request());
        
        // Test relationship data
        $this->assertArrayHasKey('property_type', $array);
        $this->assertEquals('Apartment', $array['property_type']['name']);
    }
}

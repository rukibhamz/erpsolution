<?php

namespace Tests\Functional;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\PropertyType;
use App\Models\Account;

class RouteFunctionalityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test all property routes functionality
     */
    public function test_property_routes_functionality()
    {
        $user = User::factory()->create();
        $propertyType = PropertyType::factory()->create();
        
        // Test property index route
        $response = $this->actingAs($user)
            ->get(route('admin.properties.index'));
        $response->assertStatus(200);
        
        // Test property create route
        $response = $this->actingAs($user)
            ->get(route('admin.properties.create'));
        $response->assertStatus(200);
        
        // Test property store route
        $response = $this->actingAs($user)
            ->post(route('admin.properties.store'), [
                'name' => 'Test Property',
                'property_type_id' => $propertyType->id,
                'address' => 'Test Address',
                'city' => 'Test City',
                'state' => 'Test State',
                'rent_amount' => 1000
            ]);
        $response->assertRedirect(route('admin.properties.index'));
        
        $property = Property::where('name', 'Test Property')->first();
        $this->assertNotNull($property);
        
        // Test property show route
        $response = $this->actingAs($user)
            ->get(route('admin.properties.show', $property));
        $response->assertStatus(200);
        
        // Test property edit route
        $response = $this->actingAs($user)
            ->get(route('admin.properties.edit', $property));
        $response->assertStatus(200);
        
        // Test property update route
        $response = $this->actingAs($user)
            ->put(route('admin.properties.update', $property), [
                'name' => 'Updated Property',
                'property_type_id' => $propertyType->id,
                'address' => 'Updated Address',
                'city' => 'Updated City',
                'state' => 'Updated State',
                'rent_amount' => 1500,
                'status' => 'available'
            ]);
        $response->assertRedirect(route('admin.properties.index'));
        
        // Test property toggle status route
        $response = $this->actingAs($user)
            ->withHeaders(['X-CSRF-TOKEN' => csrf_token()])
            ->patch(route('admin.properties.toggle-status', $property));
        $response->assertRedirect();
        
        // Test property destroy route
        $response = $this->actingAs($user)
            ->delete(route('admin.properties.destroy', $property));
        $response->assertRedirect(route('admin.properties.index'));
    }

    /**
     * Test all transaction routes functionality
     */
    public function test_transaction_routes_functionality()
    {
        $user = User::factory()->create();
        $account = Account::factory()->create();
        
        // Test transaction index route
        $response = $this->actingAs($user)
            ->get(route('admin.transactions.index'));
        $response->assertStatus(200);
        
        // Test transaction create route
        $response = $this->actingAs($user)
            ->get(route('admin.transactions.create'));
        $response->assertStatus(200);
        
        // Test transaction store route
        $response = $this->actingAs($user)
            ->post(route('admin.transactions.store'), [
                'account_id' => $account->id,
                'transaction_type' => 'income',
                'amount' => 1000,
                'description' => 'Test Transaction',
                'transaction_date' => now()->format('Y-m-d')
            ]);
        $response->assertRedirect(route('admin.transactions.index'));
        
        $transaction = Transaction::where('description', 'Test Transaction')->first();
        $this->assertNotNull($transaction);
        
        // Test transaction show route
        $response = $this->actingAs($user)
            ->get(route('admin.transactions.show', $transaction));
        $response->assertStatus(200);
        
        // Test transaction edit route
        $response = $this->actingAs($user)
            ->get(route('admin.transactions.edit', $transaction));
        $response->assertStatus(200);
        
        // Test transaction update route
        $response = $this->actingAs($user)
            ->put(route('admin.transactions.update', $transaction), [
                'account_id' => $account->id,
                'transaction_type' => 'income',
                'amount' => 1500,
                'description' => 'Updated Transaction',
                'transaction_date' => now()->format('Y-m-d')
            ]);
        $response->assertRedirect(route('admin.transactions.index'));
        
        // Test transaction approve route
        $response = $this->actingAs($user)
            ->withHeaders(['X-CSRF-TOKEN' => csrf_token()])
            ->patch(route('admin.transactions.approve', $transaction));
        $response->assertRedirect();
        
        // Test transaction destroy route
        $response = $this->actingAs($user)
            ->delete(route('admin.transactions.destroy', $transaction));
        $response->assertRedirect(route('admin.transactions.index'));
    }

    /**
     * Test authentication routes functionality
     */
    public function test_authentication_routes_functionality()
    {
        // Test login route (guest)
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        
        // Test login store route
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);
        $response->assertRedirect(route('dashboard'));
        
        // Test logout route (authenticated)
        $response = $this->actingAs($user)
            ->post(route('logout'));
        $response->assertRedirect();
    }

    /**
     * Test dashboard route functionality
     */
    public function test_dashboard_route_functionality()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('dashboard'));
        $response->assertStatus(200);
    }

    /**
     * Test route naming consistency
     */
    public function test_route_naming_consistency()
    {
        $routes = [
            'admin.properties.index',
            'admin.properties.create',
            'admin.properties.store',
            'admin.properties.show',
            'admin.properties.edit',
            'admin.properties.update',
            'admin.properties.destroy',
            'admin.properties.toggle-status',
            'admin.properties.remove-image',
            'admin.transactions.index',
            'admin.transactions.create',
            'admin.transactions.store',
            'admin.transactions.show',
            'admin.transactions.edit',
            'admin.transactions.update',
            'admin.transactions.destroy',
            'admin.transactions.approve',
            'admin.transactions.reject',
            'admin.transactions.cancel',
            'login',
            'login.store',
            'logout',
            'dashboard'
        ];
        
        foreach ($routes as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Route '{$routeName}' does not exist"
            );
        }
    }

    /**
     * Test middleware protection on routes
     */
    public function test_middleware_protection_on_routes()
    {
        // Test protected routes without authentication
        $protectedRoutes = [
            'admin.properties.index',
            'admin.transactions.index',
            'dashboard'
        ];
        
        foreach ($protectedRoutes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertRedirect(route('login'));
        }
    }
}

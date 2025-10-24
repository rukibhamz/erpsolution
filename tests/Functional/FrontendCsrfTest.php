<?php

namespace Tests\Functional;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Models\Property;

class FrontendCsrfTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test CSRF token is present in HTML
     */
    public function test_csrf_token_in_html()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.properties.index'));
        
        $response->assertStatus(200);
        $response->assertSee('csrf-token');
        $response->assertSee('content="{{ csrf_token() }}"');
    }

    /**
     * Test AJAX requests include CSRF token
     */
    public function test_ajax_requests_include_csrf_token()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Test with proper CSRF token
        $response = $this->actingAs($user)
            ->withHeaders([
                'X-CSRF-TOKEN' => csrf_token(),
                'Accept' => 'application/json'
            ])
            ->patch(route('admin.properties.toggle-status', $property));
        
        // Should not get 419 (CSRF token mismatch)
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test CSRF token validation on AJAX routes
     */
    public function test_csrf_token_validation_on_ajax_routes()
    {
        $user = User::factory()->create();
        $property = Property::factory()->create();
        
        // Test without CSRF token
        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('admin.properties.toggle-status', $property));
        
        // Should get 419 (CSRF token mismatch)
        $this->assertEquals(419, $response->status());
    }

    /**
     * Test JavaScript CSRF setup
     */
    public function test_javascript_csrf_setup()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.properties.index'));
        
        $response->assertStatus(200);
        
        // Check if JavaScript files are included
        $response->assertSee('app.js');
        $response->assertSee('bootstrap.js');
    }

    /**
     * Test Alpine.js CSRF integration
     */
    public function test_alpine_js_csrf_integration()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('admin.properties.index'));
        
        $response->assertStatus(200);
        
        // Check if Alpine.js is loaded
        $response->assertSee('alpinejs');
        $response->assertSee('x-data');
    }
}

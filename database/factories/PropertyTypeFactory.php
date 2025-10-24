<?php

namespace Database\Factories;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PropertyType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $propertyTypes = [
            'Apartment' => 'Multi-unit residential buildings with multiple apartments',
            'House' => 'Single-family residential properties',
            'Commercial' => 'Business and office spaces for commercial use',
            'Land' => 'Vacant land for development or investment',
            'Warehouse' => 'Industrial storage and distribution facilities',
            'Retail' => 'Commercial spaces for retail businesses',
            'Office' => 'Professional office spaces and buildings',
            'Industrial' => 'Manufacturing and industrial facilities'
        ];

        $type = $this->faker->randomElement(array_keys($propertyTypes));

        return [
            'name' => $type,
            'description' => $propertyTypes[$type],
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }

    /**
     * Indicate that the property type is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the property type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create an apartment property type.
     */
    public function apartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Apartment',
            'description' => 'Multi-unit residential buildings with multiple apartments',
            'status' => 'active',
        ]);
    }

    /**
     * Create a house property type.
     */
    public function house(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'House',
            'description' => 'Single-family residential properties',
            'status' => 'active',
        ]);
    }

    /**
     * Create a commercial property type.
     */
    public function commercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Commercial',
            'description' => 'Business and office spaces for commercial use',
            'status' => 'active',
        ]);
    }

    /**
     * Create a land property type.
     */
    public function land(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Land',
            'description' => 'Vacant land for development or investment',
            'status' => 'active',
        ]);
    }
}

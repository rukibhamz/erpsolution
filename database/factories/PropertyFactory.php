<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Property::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nigerianCities = [
            'Lagos', 'Abuja', 'Kano', 'Ibadan', 'Port Harcourt', 'Benin City',
            'Kaduna', 'Maiduguri', 'Zaria', 'Aba', 'Jos', 'Ilorin', 'Oyo',
            'Enugu', 'Abeokuta', 'Sokoto', 'Onitsha', 'Warri', 'Calabar',
            'Uyo', 'Akure', 'Osogbo', 'Bauchi', 'Katsina', 'Minna'
        ];

        $nigerianStates = [
            'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa',
            'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo',
            'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna',
            'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa',
            'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers',
            'Sokoto', 'Taraba', 'Yobe', 'Zamfara'
        ];

        $propertyTypes = ['Apartment', 'House', 'Commercial', 'Land'];
        $statuses = ['available', 'occupied', 'maintenance', 'unavailable'];

        $city = $this->faker->randomElement($nigerianCities);
        $state = $this->faker->randomElement($nigerianStates);
        $purchasePrice = $this->faker->numberBetween(5000000, 50000000); // ₦5M - ₦50M
        $currentValue = $purchasePrice * $this->faker->randomFloat(2, 0.8, 1.5);

        return [
            'property_code' => 'PROP-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => $this->faker->words(3, true) . ' ' . $this->faker->randomElement(['Apartment', 'House', 'Office', 'Building']),
            'description' => $this->faker->paragraph(3),
            'property_type_id' => PropertyType::factory(),
            'address' => $this->faker->streetAddress,
            'city' => $city,
            'state' => $state,
            'zip_code' => $this->faker->numerify('######'),
            'country' => 'Nigeria',
            'purchase_price' => $purchasePrice,
            'current_value' => $currentValue,
            'year_built' => $this->faker->numberBetween(1990, 2024),
            'number_of_units' => $this->faker->numberBetween(1, 20),
            'status' => $this->faker->randomElement($statuses),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'images' => $this->faker->optional(0.7)->randomElements([
                'properties/property1.jpg',
                'properties/property2.jpg',
                'properties/property3.jpg',
                'properties/property4.jpg'
            ], $this->faker->numberBetween(1, 4)),
            'notes' => $this->faker->optional(0.3)->paragraph(2),
        ];
    }

    /**
     * Indicate that the property is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the property is occupied.
     */
    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'occupied',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the property is under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the property is unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unavailable',
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the property is in Lagos.
     */
    public function lagos(): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => 'Lagos',
            'state' => 'Lagos',
        ]);
    }

    /**
     * Indicate that the property is in Abuja.
     */
    public function abuja(): static
    {
        return $this->state(fn (array $attributes) => [
            'city' => 'Abuja',
            'state' => 'FCT',
        ]);
    }

    /**
     * Indicate that the property is an apartment.
     */
    public function apartment(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->words(2, true) . ' Apartment',
            'number_of_units' => $this->faker->numberBetween(1, 10),
        ]);
    }

    /**
     * Indicate that the property is a house.
     */
    public function house(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->words(2, true) . ' House',
            'number_of_units' => 1,
        ]);
    }

    /**
     * Indicate that the property is commercial.
     */
    public function commercial(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->words(2, true) . ' Office',
            'number_of_units' => $this->faker->numberBetween(1, 5),
        ]);
    }
}

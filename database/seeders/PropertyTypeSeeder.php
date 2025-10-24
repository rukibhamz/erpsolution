<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyTypes = [
            [
                'name' => 'Apartment',
                'description' => 'Multi-unit residential building',
                'is_active' => true,
            ],
            [
                'name' => 'House',
                'description' => 'Single-family residential property',
                'is_active' => true,
            ],
            [
                'name' => 'Office Space',
                'description' => 'Commercial office building',
                'is_active' => true,
            ],
            [
                'name' => 'Retail Space',
                'description' => 'Commercial retail property',
                'is_active' => true,
            ],
            [
                'name' => 'Warehouse',
                'description' => 'Industrial storage facility',
                'is_active' => true,
            ],
        ];

        foreach ($propertyTypes as $type) {
            PropertyType::create($type);
        }
    }
}
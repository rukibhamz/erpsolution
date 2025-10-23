<?php

namespace Database\Seeders;

use App\Models\PropertyType;
use Illuminate\Database\Seeder;

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
            ],
            [
                'name' => 'House',
                'description' => 'Single-family residential property',
            ],
            [
                'name' => 'Office Space',
                'description' => 'Commercial office building or space',
            ],
            [
                'name' => 'Shop',
                'description' => 'Retail commercial space',
            ],
            [
                'name' => 'Warehouse',
                'description' => 'Industrial storage facility',
            ],
            [
                'name' => 'Land',
                'description' => 'Vacant land for development',
            ],
        ];

        foreach ($propertyTypes as $type) {
            PropertyType::create($type);
        }
    }
}

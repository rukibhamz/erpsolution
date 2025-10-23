<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Wedding',
                'description' => 'Wedding ceremonies and receptions',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'Corporate Event',
                'description' => 'Business meetings, conferences, and corporate functions',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Birthday Party',
                'description' => 'Birthday celebrations and parties',
                'color' => '#EF4444',
            ],
            [
                'name' => 'Conference',
                'description' => 'Professional conferences and seminars',
                'color' => '#10B981',
            ],
            [
                'name' => 'Exhibition',
                'description' => 'Trade shows and exhibitions',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Other',
                'description' => 'Other types of events',
                'color' => '#6B7280',
            ],
        ];

        foreach ($categories as $category) {
            EventCategory::create($category);
        }
    }
}

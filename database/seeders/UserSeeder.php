<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@erpsolution.com',
            'password' => Hash::make('password123'),
            'phone' => '+234-800-000-0000',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create manager user
        $manager = User::create([
            'name' => 'Business Manager',
            'email' => 'manager@erpsolution.com',
            'password' => Hash::make('password123'),
            'phone' => '+234-800-000-0001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // Create staff user
        $staff = User::create([
            'name' => 'Staff Member',
            'email' => 'staff@erpsolution.com',
            'password' => Hash::make('password123'),
            'phone' => '+234-800-000-0002',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('staff');

        // Create additional test users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@erpsolution.com',
            'password' => Hash::make('password123'),
            'phone' => '+234-800-000-0003',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->assignRole('staff');

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@erpsolution.com',
            'password' => Hash::make('password123'),
            'phone' => '+234-800-000-0004',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->assignRole('manager');
    }
}
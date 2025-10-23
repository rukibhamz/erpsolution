<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'phone' => '+234-xxx-xxx-xxxx',
            'is_active' => true,
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->attach($adminRole);

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'phone' => '+234-xxx-xxx-xxxx',
            'is_active' => true,
        ]);

        $managerRole = Role::where('name', 'manager')->first();
        $manager->roles()->attach($managerRole);

        // Create staff user
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'phone' => '+234-xxx-xxx-xxxx',
            'is_active' => true,
        ]);

        $staffRole = Role::where('name', 'staff')->first();
        $staff->roles()->attach($staffRole);
    }
}

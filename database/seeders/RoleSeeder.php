<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions',
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Management level access to most modules',
            ],
            [
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Limited access to assigned modules',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

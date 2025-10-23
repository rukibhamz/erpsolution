<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['name' => 'view-users', 'display_name' => 'View Users', 'module' => 'users'],
            ['name' => 'create-users', 'display_name' => 'Create Users', 'module' => 'users'],
            ['name' => 'edit-users', 'display_name' => 'Edit Users', 'module' => 'users'],
            ['name' => 'delete-users', 'display_name' => 'Delete Users', 'module' => 'users'],
            
            // Property Management
            ['name' => 'view-properties', 'display_name' => 'View Properties', 'module' => 'properties'],
            ['name' => 'create-properties', 'display_name' => 'Create Properties', 'module' => 'properties'],
            ['name' => 'edit-properties', 'display_name' => 'Edit Properties', 'module' => 'properties'],
            ['name' => 'delete-properties', 'display_name' => 'Delete Properties', 'module' => 'properties'],
            
            // Tenant Management
            ['name' => 'view-tenants', 'display_name' => 'View Tenants', 'module' => 'tenants'],
            ['name' => 'create-tenants', 'display_name' => 'Create Tenants', 'module' => 'tenants'],
            ['name' => 'edit-tenants', 'display_name' => 'Edit Tenants', 'module' => 'tenants'],
            ['name' => 'delete-tenants', 'display_name' => 'Delete Tenants', 'module' => 'tenants'],
            
            // Lease Management
            ['name' => 'view-leases', 'display_name' => 'View Leases', 'module' => 'leases'],
            ['name' => 'create-leases', 'display_name' => 'Create Leases', 'module' => 'leases'],
            ['name' => 'edit-leases', 'display_name' => 'Edit Leases', 'module' => 'leases'],
            ['name' => 'delete-leases', 'display_name' => 'Delete Leases', 'module' => 'leases'],
            
            // Booking Management
            ['name' => 'view-bookings', 'display_name' => 'View Bookings', 'module' => 'bookings'],
            ['name' => 'create-bookings', 'display_name' => 'Create Bookings', 'module' => 'bookings'],
            ['name' => 'edit-bookings', 'display_name' => 'Edit Bookings', 'module' => 'bookings'],
            ['name' => 'delete-bookings', 'display_name' => 'Delete Bookings', 'module' => 'bookings'],
            
            // Accounting
            ['name' => 'view-accounting', 'display_name' => 'View Accounting', 'module' => 'accounting'],
            ['name' => 'create-transactions', 'display_name' => 'Create Transactions', 'module' => 'accounting'],
            ['name' => 'edit-transactions', 'display_name' => 'Edit Transactions', 'module' => 'accounting'],
            ['name' => 'delete-transactions', 'display_name' => 'Delete Transactions', 'module' => 'accounting'],
            
            // Reports
            ['name' => 'view-reports', 'display_name' => 'View Reports', 'module' => 'reports'],
            ['name' => 'export-reports', 'display_name' => 'Export Reports', 'module' => 'reports'],
            
            // Settings
            ['name' => 'view-settings', 'display_name' => 'View Settings', 'module' => 'settings'],
            ['name' => 'edit-settings', 'display_name' => 'Edit Settings', 'module' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $staffRole = Role::where('name', 'staff')->first();

        // Admin gets all permissions
        $adminRole->permissions()->sync(Permission::pluck('id'));

        // Manager gets most permissions except user management
        $managerPermissions = Permission::whereNotIn('module', ['users'])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        // Staff gets limited permissions
        $staffPermissions = Permission::whereIn('name', [
            'view-properties', 'view-tenants', 'view-leases', 'view-bookings',
            'create-bookings', 'edit-bookings', 'view-accounting', 'create-transactions'
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPermissions);
    }
}

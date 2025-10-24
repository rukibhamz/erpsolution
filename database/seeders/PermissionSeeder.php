<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $admin = Role::create(['name' => 'admin', 'display_name' => 'Administrator']);
        $manager = Role::create(['name' => 'manager', 'display_name' => 'Manager']);
        $staff = Role::create(['name' => 'staff', 'display_name' => 'Staff']);

        // Create permissions
        $permissions = [
            // Dashboard permissions
            'view-dashboard' => 'View Dashboard',
            
            // User management permissions
            'view-users' => 'View Users',
            'create-users' => 'Create Users',
            'edit-users' => 'Edit Users',
            'delete-users' => 'Delete Users',
            
            // Property management permissions
            'view-properties' => 'View Properties',
            'create-properties' => 'Create Properties',
            'edit-properties' => 'Edit Properties',
            'delete-properties' => 'Delete Properties',
            'manage-property-images' => 'Manage Property Images',
            
            // Transaction permissions
            'view-transactions' => 'View Transactions',
            'create-transactions' => 'Create Transactions',
            'edit-transactions' => 'Edit Transactions',
            'delete-transactions' => 'Delete Transactions',
            'approve-transactions' => 'Approve Transactions',
            'reject-transactions' => 'Reject Transactions',
            
            // Event management permissions
            'view-events' => 'View Events',
            'create-events' => 'Create Events',
            'edit-events' => 'Edit Events',
            'delete-events' => 'Delete Events',
            'manage-event-images' => 'Manage Event Images',
            
            // Booking permissions
            'view-bookings' => 'View Bookings',
            'create-bookings' => 'Create Bookings',
            'edit-bookings' => 'Edit Bookings',
            'delete-bookings' => 'Delete Bookings',
            'process-payments' => 'Process Payments',
            
            // Lease management permissions
            'view-leases' => 'View Leases',
            'create-leases' => 'Create Leases',
            'edit-leases' => 'Edit Leases',
            'delete-leases' => 'Delete Leases',
            'terminate-leases' => 'Terminate Leases',
            
            // Account management permissions
            'view-accounts' => 'View Accounts',
            'create-accounts' => 'Create Accounts',
            'edit-accounts' => 'Edit Accounts',
            'delete-accounts' => 'Delete Accounts',
            
            // Journal entry permissions
            'view-journal-entries' => 'View Journal Entries',
            'create-journal-entries' => 'Create Journal Entries',
            'edit-journal-entries' => 'Edit Journal Entries',
            'delete-journal-entries' => 'Delete Journal Entries',
            'post-journal-entries' => 'Post Journal Entries',
            
            // Tax management permissions
            'view-tax-types' => 'View Tax Types',
            'create-tax-types' => 'Create Tax Types',
            'edit-tax-types' => 'Edit Tax Types',
            'delete-tax-types' => 'Delete Tax Types',
            
            'view-tax-calculations' => 'View Tax Calculations',
            'create-tax-calculations' => 'Create Tax Calculations',
            'edit-tax-calculations' => 'Edit Tax Calculations',
            'delete-tax-calculations' => 'Delete Tax Calculations',
            
            'view-revenue-collections' => 'View Revenue Collections',
            'create-revenue-collections' => 'Create Revenue Collections',
            'edit-revenue-collections' => 'Edit Revenue Collections',
            'delete-revenue-collections' => 'Delete Revenue Collections',
            'verify-revenue-collections' => 'Verify Revenue Collections',
            
            // Inventory management permissions
            'view-inventory' => 'View Inventory',
            'create-inventory' => 'Create Inventory',
            'edit-inventory' => 'Edit Inventory',
            'delete-inventory' => 'Delete Inventory',
            'manage-stock' => 'Manage Stock',
            
            // Utilities tracking permissions
            'view-utilities' => 'View Utilities',
            'create-utilities' => 'Create Utilities',
            'edit-utilities' => 'Edit Utilities',
            'delete-utilities' => 'Delete Utilities',
            
            // Reports permissions
            'view-reports' => 'View Reports',
            'export-reports' => 'Export Reports',
            
            // Settings permissions
            'view-settings' => 'View Settings',
            'edit-settings' => 'Edit Settings',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'guard_name' => 'web'
            ]);
        }

        // Assign permissions to roles
        $admin->givePermissionTo(Permission::all());
        
        $manager->givePermissionTo([
            'view-dashboard',
            'view-users', 'create-users', 'edit-users',
            'view-properties', 'create-properties', 'edit-properties',
            'view-transactions', 'create-transactions', 'edit-transactions', 'approve-transactions',
            'view-events', 'create-events', 'edit-events',
            'view-bookings', 'create-bookings', 'edit-bookings', 'process-payments',
            'view-leases', 'create-leases', 'edit-leases', 'terminate-leases',
            'view-accounts', 'create-accounts', 'edit-accounts',
            'view-journal-entries', 'create-journal-entries', 'edit-journal-entries', 'post-journal-entries',
            'view-tax-types', 'create-tax-types', 'edit-tax-types',
            'view-tax-calculations', 'create-tax-calculations', 'edit-tax-calculations',
            'view-revenue-collections', 'create-revenue-collections', 'edit-revenue-collections',
            'view-inventory', 'create-inventory', 'edit-inventory', 'manage-stock',
            'view-utilities', 'create-utilities', 'edit-utilities',
            'view-reports', 'export-reports',
            'view-settings', 'edit-settings',
        ]);
        
        $staff->givePermissionTo([
            'view-dashboard',
            'view-users',
            'view-properties', 'create-properties', 'edit-properties',
            'view-transactions', 'create-transactions', 'edit-transactions',
            'view-events', 'create-events', 'edit-events',
            'view-bookings', 'create-bookings', 'edit-bookings',
            'view-leases', 'create-leases', 'edit-leases',
            'view-accounts',
            'view-journal-entries', 'create-journal-entries', 'edit-journal-entries',
            'view-tax-types', 'view-tax-calculations', 'view-revenue-collections',
            'view-inventory', 'create-inventory', 'edit-inventory',
            'view-utilities', 'create-utilities', 'edit-utilities',
            'view-reports',
        ]);
    }
}
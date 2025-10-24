<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\Lease;
use App\Models\Event;
use App\Models\Booking;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createRolesAndPermissions();
        $this->createUsers();
        $this->createPropertyTypes();
        $this->createProperties();
        $this->createAccounts();
        $this->createTransactions();
        $this->createLeases();
        $this->createEvents();
        $this->createBookings();
        $this->createInventoryData();
    }

    /**
     * Create roles and permissions
     */
    private function createRolesAndPermissions(): void
    {
        // Create permissions
        $permissions = [
            'view_properties', 'create_properties', 'edit_properties', 'delete_properties',
            'view_transactions', 'create_transactions', 'edit_transactions', 'delete_transactions',
            'view_leases', 'create_leases', 'edit_leases', 'delete_leases',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_bookings', 'create_bookings', 'edit_bookings', 'delete_bookings',
            'view_users', 'create_users', 'edit_users', 'delete_users',
            'view_reports', 'export_data', 'manage_settings'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $staffRole = Role::firstOrCreate(['name' => 'staff']);

        // Assign all permissions to admin
        $adminRole->givePermissionTo(Permission::all());

        // Assign limited permissions to manager
        $managerRole->givePermissionTo([
            'view_properties', 'create_properties', 'edit_properties',
            'view_transactions', 'create_transactions', 'edit_transactions',
            'view_leases', 'create_leases', 'edit_leases',
            'view_events', 'create_events', 'edit_events',
            'view_bookings', 'create_bookings', 'edit_bookings',
            'view_reports', 'export_data'
        ]);

        // Assign basic permissions to staff
        $staffRole->givePermissionTo([
            'view_properties', 'view_transactions', 'view_leases',
            'view_events', 'view_bookings', 'view_reports'
        ]);
    }

    /**
     * Create test users
     */
    private function createUsers(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        // Create staff user
        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('staff');

        // Create additional users
        User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('staff');
        });
    }

    /**
     * Create property types
     */
    private function createPropertyTypes(): void
    {
        $propertyTypes = [
            ['name' => 'Apartment', 'description' => 'Multi-unit residential buildings'],
            ['name' => 'House', 'description' => 'Single-family residential properties'],
            ['name' => 'Commercial', 'description' => 'Business and office spaces'],
            ['name' => 'Land', 'description' => 'Vacant land for development'],
            ['name' => 'Warehouse', 'description' => 'Industrial storage facilities'],
        ];

        foreach ($propertyTypes as $type) {
            PropertyType::create($type);
        }
    }

    /**
     * Create properties
     */
    private function createProperties(): void
    {
        // Create properties in Lagos
        Property::factory()->count(15)->lagos()->create();
        
        // Create properties in Abuja
        Property::factory()->count(10)->abuja()->create();
        
        // Create properties in other cities
        Property::factory()->count(20)->create();
        
        // Create specific property types
        Property::factory()->count(5)->apartment()->create();
        Property::factory()->count(5)->house()->create();
        Property::factory()->count(5)->commercial()->create();
    }

    /**
     * Create accounts
     */
    private function createAccounts(): void
    {
        $accounts = [
            ['account_name' => 'Cash Account', 'account_type' => 'asset', 'balance' => 5000000],
            ['account_name' => 'Bank Account', 'account_type' => 'asset', 'balance' => 15000000],
            ['account_name' => 'Rent Income', 'account_type' => 'income', 'balance' => 0],
            ['account_name' => 'Service Fee Income', 'account_type' => 'income', 'balance' => 0],
            ['account_name' => 'Maintenance Expense', 'account_type' => 'expense', 'balance' => 0],
            ['account_name' => 'Utilities Expense', 'account_type' => 'expense', 'balance' => 0],
            ['account_name' => 'Accounts Payable', 'account_type' => 'liability', 'balance' => 0],
            ['account_name' => 'Security Deposits', 'account_type' => 'liability', 'balance' => 0],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }

    /**
     * Create transactions
     */
    private function createTransactions(): void
    {
        $cashAccount = Account::where('account_name', 'Cash Account')->first();
        $bankAccount = Account::where('account_name', 'Bank Account')->first();
        $rentIncome = Account::where('account_name', 'Rent Income')->first();
        $maintenanceExpense = Account::where('account_name', 'Maintenance Expense')->first();

        // Create rent income transactions
        Transaction::factory()->count(20)->rentIncome()->create([
            'account_id' => $rentIncome->id,
            'status' => 'approved',
            'approved_by' => User::first()->id,
            'approved_at' => now(),
        ]);

        // Create maintenance expense transactions
        Transaction::factory()->count(15)->maintenanceExpense()->create([
            'account_id' => $maintenanceExpense->id,
            'status' => 'approved',
            'approved_by' => User::first()->id,
            'approved_at' => now(),
        ]);

        // Create mixed transactions
        Transaction::factory()->count(30)->create();
    }

    /**
     * Create leases
     */
    private function createLeases(): void
    {
        $properties = Property::available()->take(10)->get();
        
        foreach ($properties as $property) {
            Lease::factory()->create([
                'property_id' => $property->id,
                'status' => 'active',
                'start_date' => now()->subMonths(rand(1, 12)),
                'end_date' => now()->addMonths(rand(6, 24)),
            ]);
        }

        // Create some terminated leases
        Lease::factory()->count(5)->create([
            'status' => 'terminated',
            'end_date' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Create events
     */
    private function createEvents(): void
    {
        // Create upcoming events
        Event::factory()->count(10)->create([
            'status' => 'published',
            'start_date' => now()->addDays(rand(1, 90)),
            'end_date' => now()->addDays(rand(91, 95)),
        ]);

        // Create past events
        Event::factory()->count(5)->create([
            'status' => 'completed',
            'start_date' => now()->subDays(rand(30, 90)),
            'end_date' => now()->subDays(rand(25, 85)),
        ]);

        // Create draft events
        Event::factory()->count(3)->create([
            'status' => 'draft',
            'start_date' => now()->addDays(rand(1, 30)),
            'end_date' => now()->addDays(rand(31, 35)),
        ]);
    }

    /**
     * Create bookings
     */
    private function createBookings(): void
    {
        $events = Event::published()->take(5)->get();
        
        foreach ($events as $event) {
            Booking::factory()->count(rand(5, 20))->create([
                'event_id' => $event->id,
                'booking_status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        }

        // Create some pending bookings
        Booking::factory()->count(10)->create([
            'booking_status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Create inventory data
     */
    private function createInventoryData(): void
    {
        $categories = [
            ['name' => 'Furniture', 'description' => 'Office and residential furniture'],
            ['name' => 'Electronics', 'description' => 'Electronic equipment and devices'],
            ['name' => 'Appliances', 'description' => 'Home and office appliances'],
            ['name' => 'Maintenance', 'description' => 'Maintenance tools and supplies'],
        ];

        foreach ($categories as $category) {
            InventoryCategory::create($category);
        }

        // Create inventory items
        InventoryItem::factory()->count(50)->create();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\Lease;
use App\Models\Event;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GenerateTestData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:generate-data {--count=100 : Number of records to generate} {--type=all : Type of data to generate}';

    /**
     * The console command description.
     */
    protected $description = 'Generate test data for development and testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $type = $this->option('type');
        
        $this->info("Generating {$count} {$type} records...");
        
        switch ($type) {
            case 'users':
                $this->generateUsers($count);
                break;
            case 'properties':
                $this->generateProperties($count);
                break;
            case 'transactions':
                $this->generateTransactions($count);
                break;
            case 'leases':
                $this->generateLeases($count);
                break;
            case 'events':
                $this->generateEvents($count);
                break;
            case 'bookings':
                $this->generateBookings($count);
                break;
            case 'all':
            default:
                $this->generateAllData($count);
                break;
        }
        
        $this->info('✅ Test data generated successfully!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate users
     */
    protected function generateUsers(int $count): void
    {
        $this->line("Generating {$count} users...");
        
        User::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} users");
    }
    
    /**
     * Generate properties
     */
    protected function generateProperties(int $count): void
    {
        $this->line("Generating {$count} properties...");
        
        Property::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} properties");
    }
    
    /**
     * Generate transactions
     */
    protected function generateTransactions(int $count): void
    {
        $this->line("Generating {$count} transactions...");
        
        Transaction::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} transactions");
    }
    
    /**
     * Generate leases
     */
    protected function generateLeases(int $count): void
    {
        $this->line("Generating {$count} leases...");
        
        Lease::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} leases");
    }
    
    /**
     * Generate events
     */
    protected function generateEvents(int $count): void
    {
        $this->line("Generating {$count} events...");
        
        Event::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} events");
    }
    
    /**
     * Generate bookings
     */
    protected function generateBookings(int $count): void
    {
        $this->line("Generating {$count} bookings...");
        
        Booking::factory()->count($count)->create();
        
        $this->info("✅ Generated {$count} bookings");
    }
    
    /**
     * Generate all data
     */
    protected function generateAllData(int $count): void
    {
        $this->line("Generating comprehensive test data...");
        
        // Generate users
        $this->generateUsers($count / 10);
        
        // Generate properties
        $this->generateProperties($count);
        
        // Generate transactions
        $this->generateTransactions($count * 2);
        
        // Generate leases
        $this->generateLeases($count / 2);
        
        // Generate events
        $this->generateEvents($count / 3);
        
        // Generate bookings
        $this->generateBookings($count);
        
        $this->info("✅ Generated comprehensive test data");
    }
}

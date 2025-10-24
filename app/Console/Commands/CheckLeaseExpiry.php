<?php

namespace App\Console\Commands;

use App\Services\LeaseManagementService;
use Illuminate\Console\Command;

class CheckLeaseExpiry extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'leases:check-expiry';

    /**
     * The console command description.
     */
    protected $description = 'Check for expired leases and update their status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expired leases...');
        
        $leaseService = new LeaseManagementService();
        $expiredLeases = $leaseService->checkLeaseExpiry();
        
        if (empty($expiredLeases)) {
            $this->info('No expired leases found.');
        } else {
            $this->info('Updated ' . count($expiredLeases) . ' expired leases.');
            $this->table(['Lease ID'], array_map(fn($id) => [$id], $expiredLeases));
        }
        
        return Command::SUCCESS;
    }
}

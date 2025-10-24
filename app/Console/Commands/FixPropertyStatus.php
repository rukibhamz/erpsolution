<?php

namespace App\Console\Commands;

use App\Services\PropertyStatusService;
use Illuminate\Console\Command;

class FixPropertyStatus extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'properties:fix-status';

    /**
     * The console command description.
     */
    protected $description = 'Fix property status inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for property status inconsistencies...');
        
        $propertyService = new PropertyStatusService();
        $fixedProperties = $propertyService->fixStatusInconsistencies();
        
        if (empty($fixedProperties)) {
            $this->info('No property status inconsistencies found.');
        } else {
            $this->info('Fixed status for ' . count($fixedProperties) . ' properties.');
            $this->table(['Property ID'], array_map(fn($id) => [$id], $fixedProperties));
        }
        
        return Command::SUCCESS;
    }
}

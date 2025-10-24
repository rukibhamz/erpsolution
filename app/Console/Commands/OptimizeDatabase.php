<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitoringService;
use Illuminate\Console\Command;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:optimize {--analyze : Also analyze tables}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize database tables for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Optimizing database tables...');
        
        $performanceService = new PerformanceMonitoringService();
        
        // Optimize tables
        $optimizeResults = $performanceService->optimizeTables();
        foreach ($optimizeResults as $result) {
            $this->line($result);
        }
        
        // Analyze tables if requested
        if ($this->option('analyze')) {
            $this->info('Analyzing tables...');
            $analyzeResults = $performanceService->analyzeTables();
            foreach ($analyzeResults as $result) {
                $this->line($result);
            }
        }
        
        $this->info('Database optimization completed successfully.');
        
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitoringService;
use Illuminate\Console\Command;

class CheckPerformance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'performance:check';

    /**
     * The console command description.
     */
    protected $description = 'Check database and application performance metrics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking performance metrics...');
        
        $performanceService = new PerformanceMonitoringService();
        
        // Get database metrics
        $dbMetrics = $performanceService->getDatabaseMetrics();
        
        $this->displayDatabaseMetrics($dbMetrics);
        
        // Get cache metrics
        $cacheMetrics = $performanceService->getCacheMetrics();
        
        $this->displayCacheMetrics($cacheMetrics);
        
        return Command::SUCCESS;
    }

    /**
     * Display database metrics
     */
    protected function displayDatabaseMetrics(array $metrics): void
    {
        $this->info('=== DATABASE METRICS ===');
        $this->line("Total Tables: {$metrics['total_tables']}");
        
        $this->line('');
        $this->info('Largest Tables:');
        $this->table(['Table', 'Rows'], $metrics['largest_tables']->toArray());
        
        if (!empty($metrics['index_usage'])) {
            $this->line('');
            $this->info('Index Usage:');
            $this->table(
                ['Table', 'Index', 'Cardinality', 'Type'],
                collect($metrics['index_usage'])->map(function ($index) {
                    return [
                        $index->TABLE_NAME,
                        $index->INDEX_NAME,
                        $index->CARDINALITY,
                        $index->INDEX_TYPE,
                    ];
                })->toArray()
            );
        }
        
        if (!empty($metrics['slow_queries'])) {
            $this->line('');
            $this->warn('Slow Queries Detected:');
            foreach ($metrics['slow_queries'] as $query) {
                $this->line("  - Avg Time: {$query->avg_time_seconds}s, Max Time: {$query->max_time_seconds}s");
                $this->line("    SQL: " . substr($query->sql_text, 0, 100) . '...');
            }
        }
    }

    /**
     * Display cache metrics
     */
    protected function displayCacheMetrics(array $metrics): void
    {
        $this->line('');
        $this->info('=== CACHE METRICS ===');
        $this->line("Cache Hits: {$metrics['cache_hits']}");
        $this->line("Cache Misses: {$metrics['cache_misses']}");
        $this->line("Cache Size: {$metrics['cache_size']}");
        
        $hitRate = $metrics['cache_hits'] + $metrics['cache_misses'] > 0 
            ? round(($metrics['cache_hits'] / ($metrics['cache_hits'] + $metrics['cache_misses'])) * 100, 2)
            : 0;
        $this->line("Hit Rate: {$hitRate}%");
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitoringService
{
    /**
     * Monitor query performance
     */
    public function monitorQueryPerformance($query, $description = 'Query')
    {
        $startTime = microtime(true);
        $result = $query;
        $endTime = microtime(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        if ($executionTime > 1000) { // Log slow queries (> 1 second)
            Log::warning("Slow query detected: {$description}", [
                'execution_time' => $executionTime,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);
        }
        
        return $result;
    }

    /**
     * Get database performance metrics
     */
    public function getDatabaseMetrics()
    {
        $metrics = Cache::remember('database_metrics', 300, function () {
            return [
                'total_tables' => $this->getTableCount(),
                'largest_tables' => $this->getLargestTables(),
                'index_usage' => $this->getIndexUsage(),
                'slow_queries' => $this->getSlowQueries(),
            ];
        });

        return $metrics;
    }

    /**
     * Get table count
     */
    private function getTableCount()
    {
        return DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()")[0]->count;
    }

    /**
     * Get largest tables by row count
     */
    private function getLargestTables()
    {
        $tables = [
            'users', 'properties', 'transactions', 'leases', 'events', 'bookings',
            'accounts', 'inventory_items', 'activity_log', 'journal_entries'
        ];

        $results = [];
        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $results[] = ['table' => $table, 'rows' => $count];
            } catch (\Exception $e) {
                // Table doesn't exist yet
            }
        }

        return collect($results)->sortByDesc('rows')->take(10)->values();
    }

    /**
     * Get index usage statistics
     */
    private function getIndexUsage()
    {
        try {
            return DB::select("
                SELECT 
                    TABLE_NAME,
                    INDEX_NAME,
                    CARDINALITY,
                    SUB_PART,
                    PACKED,
                    NULLABLE,
                    INDEX_TYPE
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY CARDINALITY DESC
                LIMIT 20
            ");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get slow queries from MySQL slow query log
     */
    private function getSlowQueries()
    {
        try {
            return DB::select("
                SELECT 
                    sql_text,
                    exec_count,
                    avg_timer_wait/1000000000 as avg_time_seconds,
                    max_timer_wait/1000000000 as max_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait > 1000000000
                ORDER BY avg_timer_wait DESC
                LIMIT 10
            ");
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Optimize database tables
     */
    public function optimizeTables()
    {
        $tables = [
            'users', 'properties', 'transactions', 'leases', 'events', 'bookings',
            'accounts', 'inventory_items', 'activity_log', 'journal_entries'
        ];

        $results = [];
        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $results[] = "Table {$table} optimized successfully";
            } catch (\Exception $e) {
                $results[] = "Failed to optimize table {$table}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Analyze table performance
     */
    public function analyzeTables()
    {
        $tables = [
            'users', 'properties', 'transactions', 'leases', 'events', 'bookings',
            'accounts', 'inventory_items', 'activity_log', 'journal_entries'
        ];

        $results = [];
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $results[] = "Table {$table} analyzed successfully";
            } catch (\Exception $e) {
                $results[] = "Failed to analyze table {$table}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get cache performance metrics
     */
    public function getCacheMetrics()
    {
        return [
            'cache_hits' => Cache::get('cache_hits', 0),
            'cache_misses' => Cache::get('cache_misses', 0),
            'cache_size' => $this->getCacheSize(),
        ];
    }

    /**
     * Get cache size
     */
    private function getCacheSize()
    {
        try {
            $cachePath = storage_path('framework/cache');
            if (is_dir($cachePath)) {
                $size = 0;
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cachePath));
                foreach ($files as $file) {
                    $size += $file->getSize();
                }
                return $this->formatBytes($size);
            }
        } catch (\Exception $e) {
            // Cache directory doesn't exist or can't be accessed
        }
        
        return '0 B';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clear performance cache
     */
    public function clearPerformanceCache()
    {
        Cache::forget('database_metrics');
        Cache::forget('cache_hits');
        Cache::forget('cache_misses');
        
        return 'Performance cache cleared successfully';
    }
}

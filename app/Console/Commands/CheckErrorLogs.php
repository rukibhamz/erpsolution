<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class CheckErrorLogs extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:check {--hours=24 : Check logs from the last N hours}';

    /**
     * The console command description.
     */
    protected $description = 'Check application error logs and report issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = $this->option('hours');
        $this->info("Checking error logs from the last {$hours} hours...");
        
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            $this->warn('No log file found at: ' . $logPath);
            return Command::SUCCESS;
        }
        
        $logContent = File::get($logPath);
        $lines = explode("\n", $logContent);
        
        $cutoffTime = now()->subHours($hours);
        $errorCount = 0;
        $warningCount = 0;
        $criticalErrors = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Parse log line to extract timestamp
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $logTime = \Carbon\Carbon::parse($matches[1]);
                
                if ($logTime->lt($cutoffTime)) continue;
                
                // Check for error levels
                if (strpos($line, '.ERROR:') !== false) {
                    $errorCount++;
                    
                    // Check for critical errors
                    if (strpos($line, 'CRITICAL') !== false || 
                        strpos($line, 'Fatal') !== false ||
                        strpos($line, 'Exception') !== false) {
                        $criticalErrors[] = $line;
                    }
                } elseif (strpos($line, '.WARNING:') !== false) {
                    $warningCount++;
                }
            }
        }
        
        $this->displayLogSummary($errorCount, $warningCount, $criticalErrors);
        
        return Command::SUCCESS;
    }
    
    /**
     * Display log summary
     */
    protected function displayLogSummary(int $errorCount, int $warningCount, array $criticalErrors): void
    {
        $this->line('');
        $this->info('=== ERROR LOG SUMMARY ===');
        $this->line("Total Errors: {$errorCount}");
        $this->line("Total Warnings: {$warningCount}");
        
        if ($errorCount === 0 && $warningCount === 0) {
            $this->info('✅ No errors or warnings found in the specified time period.');
        } else {
            $this->warn('⚠️  Errors and warnings detected.');
        }
        
        if (!empty($criticalErrors)) {
            $this->line('');
            $this->error('🚨 CRITICAL ERRORS DETECTED:');
            foreach (array_slice($criticalErrors, 0, 5) as $error) {
                $this->line("  - " . substr($error, 0, 100) . '...');
            }
            
            if (count($criticalErrors) > 5) {
                $this->line("  ... and " . (count($criticalErrors) - 5) . " more critical errors");
            }
        }
        
        $this->line('');
        $this->info('Recommendations:');
        
        if ($errorCount > 10) {
            $this->warn('  - High error count detected. Consider investigating common error patterns.');
        }
        
        if (!empty($criticalErrors)) {
            $this->error('  - Critical errors require immediate attention.');
        }
        
        if ($warningCount > 20) {
            $this->warn('  - High warning count. Consider reviewing application configuration.');
        }
    }
}

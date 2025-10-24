<?php

namespace App\Console\Commands;

use App\Services\DataIntegrityService;
use Illuminate\Console\Command;

class CheckDataIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:check-integrity {--fix : Automatically fix issues when possible}';

    /**
     * The console command description.
     */
    protected $description = 'Check and optionally fix data integrity issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking data integrity...');
        
        $integrityService = new DataIntegrityService();
        $report = $integrityService->generateReport();
        
        $this->displaySummary($report['summary']);
        $this->displayDetails($report['details']);
        
        if ($this->option('fix') && $report['summary']['total_issues'] > 0) {
            $this->info('Data integrity issues have been automatically fixed where possible.');
        }
        
        return Command::SUCCESS;
    }

    /**
     * Display summary information.
     */
    protected function displaySummary(array $summary): void
    {
        $this->info('=== DATA INTEGRITY SUMMARY ===');
        $this->line("Total Issues Found: {$summary['total_issues']}");
        $this->line("Total Issues Fixed: {$summary['total_fixed']}");
        $this->line("Check Time: {$summary['timestamp']}");
        $this->line('');
    }

    /**
     * Display detailed information.
     */
    protected function displayDetails(array $details): void
    {
        foreach ($details as $module => $result) {
            if (empty($result['issues']) && empty($result['fixed'])) {
                continue;
            }

            $this->info("=== " . strtoupper($module) . " ===");
            
            if (!empty($result['issues'])) {
                $this->warn('Issues Found:');
                foreach ($result['issues'] as $issue) {
                    $this->line("  - {$issue}");
                }
            }
            
            if (!empty($result['fixed'])) {
                $this->info('Issues Fixed:');
                foreach ($result['fixed'] as $fix) {
                    $this->line("  ✓ {$fix}");
                }
            }
            
            $this->line('');
        }
    }
}

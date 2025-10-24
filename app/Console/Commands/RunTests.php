<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunTests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:run {--coverage : Generate test coverage report} {--filter= : Filter tests by name}';

    /**
     * The console command description.
     */
    protected $description = 'Run all tests with optional coverage and filtering';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running tests...');
        
        $command = 'test';
        $options = [];
        
        if ($this->option('coverage')) {
            $options[] = '--coverage';
        }
        
        if ($this->option('filter')) {
            $options[] = '--filter=' . $this->option('filter');
        }
        
        $command .= ' ' . implode(' ', $options);
        
        $exitCode = Artisan::call($command);
        
        $this->line(Artisan::output());
        
        if ($exitCode === 0) {
            $this->info('✅ All tests passed!');
        } else {
            $this->error('❌ Some tests failed!');
        }
        
        return $exitCode;
    }
}

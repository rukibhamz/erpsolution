<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class CodeQualityCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'code:quality {--fix : Fix code quality issues automatically}';

    /**
     * The console command description.
     */
    protected $description = 'Check code quality and enforce coding standards';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting code quality check...');
        
        $issues = [];
        
        // Check PHP syntax
        $this->checkPhpSyntax($issues);
        
        // Check coding standards
        $this->checkCodingStandards($issues);
        
        // Check for security issues
        $this->checkSecurityIssues($issues);
        
        // Check for performance issues
        $this->checkPerformanceIssues($issues);
        
        // Check documentation
        $this->checkDocumentation($issues);
        
        // Display results
        $this->displayResults($issues);
        
        // Fix issues if requested
        if ($this->option('fix') && !empty($issues)) {
            $this->fixIssues($issues);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Check PHP syntax
     */
    protected function checkPhpSyntax(array &$issues): void
    {
        $this->line('Checking PHP syntax...');
        
        $phpFiles = $this->getPhpFiles();
        $syntaxIssues = 0;
        
        foreach ($phpFiles as $file) {
            $output = [];
            $returnCode = 0;
            
            exec("php -l \"{$file}\" 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $issues[] = "Syntax error in {$file}: " . implode(' ', $output);
                $syntaxIssues++;
            }
        }
        
        $this->line("PHP syntax check completed. Issues found: {$syntaxIssues}");
    }
    
    /**
     * Check coding standards
     */
    protected function checkCodingStandards(array &$issues): void
    {
        $this->line('Checking coding standards...');
        
        $phpFiles = $this->getPhpFiles();
        $standardIssues = 0;
        
        foreach ($phpFiles as $file) {
            $issues += $this->checkFileStandards($file);
            $standardIssues += count($this->checkFileStandards($file));
        }
        
        $this->line("Coding standards check completed. Issues found: {$standardIssues}");
    }
    
    /**
     * Check file coding standards
     */
    protected function checkFileStandards(string $file): array
    {
        $issues = [];
        $content = File::get($file);
        
        // Check for proper class documentation
        if (strpos($content, '/**') === false && strpos($content, 'class ') !== false) {
            $issues[] = "Missing class documentation in {$file}";
        }
        
        // Check for proper method documentation
        if (preg_match_all('/public function \w+\([^)]*\)/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $methodName = preg_replace('/public function (\w+)\([^)]*\).*/', '$1', $match);
                if (!preg_match('/\/\*\*[\s\S]*?\*\/[\s\S]*?' . preg_quote($match, '/') . '/', $content)) {
                    $issues[] = "Missing documentation for method {$methodName} in {$file}";
                }
            }
        }
        
        // Check for proper variable naming
        if (preg_match_all('/\$[A-Z][a-zA-Z]*/', $content, $matches)) {
            foreach ($matches[0] as $match) {
                $issues[] = "Invalid variable naming convention {$match} in {$file}";
            }
        }
        
        // Check for proper indentation
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^[ \t]+$/', $line) && strlen($line) > 0) {
                $issues[] = "Mixed indentation in {$file} at line " . ($lineNumber + 1);
            }
        }
        
        return $issues;
    }
    
    /**
     * Check for security issues
     */
    protected function checkSecurityIssues(array &$issues): void
    {
        $this->line('Checking for security issues...');
        
        $phpFiles = $this->getPhpFiles();
        $securityIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = File::get($file);
            
            // Check for SQL injection vulnerabilities
            if (preg_match('/DB::raw\([^)]*\$/', $content)) {
                $issues[] = "Potential SQL injection vulnerability in {$file}";
                $securityIssues++;
            }
            
            // Check for XSS vulnerabilities
            if (preg_match('/echo\s+\$/', $content) || preg_match('/print\s+\$/', $content)) {
                $issues[] = "Potential XSS vulnerability in {$file}";
                $securityIssues++;
            }
            
            // Check for mass assignment vulnerabilities
            if (preg_match('/Model::create\([^)]*\$request->all\(\)/', $content)) {
                $issues[] = "Potential mass assignment vulnerability in {$file}";
                $securityIssues++;
            }
            
            // Check for file upload vulnerabilities
            if (preg_match('/move_uploaded_file\([^)]*\$/', $content)) {
                $issues[] = "Potential file upload vulnerability in {$file}";
                $securityIssues++;
            }
        }
        
        $this->line("Security check completed. Issues found: {$securityIssues}");
    }
    
    /**
     * Check for performance issues
     */
    protected function checkPerformanceIssues(array &$issues): void
    {
        $this->line('Checking for performance issues...');
        
        $phpFiles = $this->getPhpFiles();
        $performanceIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = File::get($file);
            
            // Check for N+1 query problems
            if (preg_match('/foreach\s*\([^)]*\)\s*\{[\s\S]*?\$[a-zA-Z]+->[a-zA-Z]+\(\)/', $content)) {
                $issues[] = "Potential N+1 query problem in {$file}";
                $performanceIssues++;
            }
            
            // Check for inefficient loops
            if (preg_match('/for\s*\([^)]*\)\s*\{[\s\S]*?DB::/', $content)) {
                $issues[] = "Potential inefficient database query in loop in {$file}";
                $performanceIssues++;
            }
            
            // Check for missing indexes
            if (preg_match('/where\s*\([^)]*\)/', $content) && !preg_match('/index/', $content)) {
                $issues[] = "Potential missing database index in {$file}";
                $performanceIssues++;
            }
        }
        
        $this->line("Performance check completed. Issues found: {$performanceIssues}");
    }
    
    /**
     * Check documentation
     */
    protected function checkDocumentation(array &$issues): void
    {
        $this->line('Checking documentation...');
        
        $phpFiles = $this->getPhpFiles();
        $documentationIssues = 0;
        
        foreach ($phpFiles as $file) {
            $content = File::get($file);
            
            // Check for missing class documentation
            if (strpos($content, 'class ') !== false && strpos($content, '/**') === false) {
                $issues[] = "Missing class documentation in {$file}";
                $documentationIssues++;
            }
            
            // Check for missing method documentation
            if (preg_match_all('/public function \w+\([^)]*\)/', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    $methodName = preg_replace('/public function (\w+)\([^)]*\).*/', '$1', $match);
                    if (!preg_match('/\/\*\*[\s\S]*?\*\/[\s\S]*?' . preg_quote($match, '/') . '/', $content)) {
                        $issues[] = "Missing documentation for method {$methodName} in {$file}";
                        $documentationIssues++;
                    }
                }
            }
            
            // Check for missing parameter documentation
            if (preg_match_all('/@param\s+\w+\s+\$/', $content, $matches)) {
                foreach ($matches[0] as $match) {
                    if (!preg_match('/@param\s+\w+\s+\$\w+\s+[^@]/', $match)) {
                        $issues[] = "Missing parameter description in {$file}";
                        $documentationIssues++;
                    }
                }
            }
        }
        
        $this->line("Documentation check completed. Issues found: {$documentationIssues}");
    }
    
    /**
     * Get all PHP files
     */
    protected function getPhpFiles(): array
    {
        $files = [];
        $directories = [
            app_path(),
            database_path('migrations'),
            database_path('seeders'),
        ];
        
        foreach ($directories as $directory) {
            if (File::exists($directory)) {
                $files = array_merge($files, File::allFiles($directory));
            }
        }
        
        return array_filter($files, function ($file) {
            return $file->getExtension() === 'php';
        });
    }
    
    /**
     * Display results
     */
    protected function displayResults(array $issues): void
    {
        $this->line('');
        $this->info('=== CODE QUALITY RESULTS ===');
        
        if (empty($issues)) {
            $this->info('✅ No code quality issues found!');
        } else {
            $this->warn("⚠️  Found " . count($issues) . " code quality issues:");
            
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
        }
        
        $this->line('');
        $this->info('Recommendations:');
        $this->line('  - Use PHP CS Fixer to automatically fix coding standards');
        $this->line('  - Use PHPStan for static analysis');
        $this->line('  - Use PHPUnit for testing');
        $this->line('  - Use Laravel Pint for code formatting');
    }
    
    /**
     * Fix issues
     */
    protected function fixIssues(array $issues): void
    {
        $this->line('');
        $this->info('Fixing code quality issues...');
        
        // Run Laravel Pint
        $this->line('Running Laravel Pint...');
        Artisan::call('pint');
        
        // Run PHP CS Fixer
        $this->line('Running PHP CS Fixer...');
        exec('vendor/bin/php-cs-fixer fix app/ --rules=@PSR12');
        
        $this->info('Code quality issues fixed successfully.');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ValidationService;
use App\Services\DataIntegrityService;
use Illuminate\Console\Command;

class ValidateData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:validate {--fix : Fix validation issues automatically}';

    /**
     * The console command description.
     */
    protected $description = 'Validate all data in the system for integrity and business rules';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting data validation...');
        
        $validationService = new ValidationService();
        $integrityService = new DataIntegrityService();
        
        $issues = [];
        
        // Validate properties
        $this->validateProperties($issues);
        
        // Validate transactions
        $this->validateTransactions($issues);
        
        // Validate leases
        $this->validateLeases($issues);
        
        // Validate events
        $this->validateEvents($issues);
        
        // Validate users
        $this->validateUsers($issues);
        
        // Display results
        $this->displayValidationResults($issues);
        
        // Fix issues if requested
        if ($this->option('fix') && !empty($issues)) {
            $this->fixIssues($issues);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Validate properties
     */
    protected function validateProperties(array &$issues): void
    {
        $this->line('Validating properties...');
        
        $properties = \App\Models\Property::all();
        $propertyIssues = 0;
        
        foreach ($properties as $property) {
            $propertyIssues += $this->validateProperty($property, $issues);
        }
        
        $this->line("Properties validated: {$properties->count()}, Issues found: {$propertyIssues}");
    }
    
    /**
     * Validate individual property
     */
    protected function validateProperty($property, array &$issues): int
    {
        $propertyIssues = 0;
        
        // Check for missing required fields
        if (empty($property->name)) {
            $issues[] = "Property ID {$property->id}: Missing name";
            $propertyIssues++;
        }
        
        if (empty($property->address)) {
            $issues[] = "Property ID {$property->id}: Missing address";
            $propertyIssues++;
        }
        
        // Check for invalid status
        if (!in_array($property->status, ['available', 'occupied', 'maintenance', 'unavailable'])) {
            $issues[] = "Property ID {$property->id}: Invalid status '{$property->status}'";
            $propertyIssues++;
        }
        
        // Check for invalid property type
        if (!$property->propertyType) {
            $issues[] = "Property ID {$property->id}: Invalid property type";
            $propertyIssues++;
        }
        
        // Check for negative values
        if ($property->purchase_price < 0) {
            $issues[] = "Property ID {$property->id}: Negative purchase price";
            $propertyIssues++;
        }
        
        if ($property->current_value < 0) {
            $issues[] = "Property ID {$property->id}: Negative current value";
            $propertyIssues++;
        }
        
        return $propertyIssues;
    }
    
    /**
     * Validate transactions
     */
    protected function validateTransactions(array &$issues): void
    {
        $this->line('Validating transactions...');
        
        $transactions = \App\Models\Transaction::all();
        $transactionIssues = 0;
        
        foreach ($transactions as $transaction) {
            $transactionIssues += $this->validateTransaction($transaction, $issues);
        }
        
        $this->line("Transactions validated: {$transactions->count()}, Issues found: {$transactionIssues}");
    }
    
    /**
     * Validate individual transaction
     */
    protected function validateTransaction($transaction, array &$issues): int
    {
        $transactionIssues = 0;
        
        // Check for missing required fields
        if (empty($transaction->description)) {
            $issues[] = "Transaction ID {$transaction->id}: Missing description";
            $transactionIssues++;
        }
        
        if (empty($transaction->amount)) {
            $issues[] = "Transaction ID {$transaction->id}: Missing amount";
            $transactionIssues++;
        }
        
        // Check for invalid status
        if (!in_array($transaction->status, ['pending', 'approved', 'rejected', 'cancelled'])) {
            $issues[] = "Transaction ID {$transaction->id}: Invalid status '{$transaction->status}'";
            $transactionIssues++;
        }
        
        // Check for invalid transaction type
        if (!in_array($transaction->transaction_type, ['income', 'expense', 'transfer'])) {
            $issues[] = "Transaction ID {$transaction->id}: Invalid transaction type '{$transaction->transaction_type}'";
            $transactionIssues++;
        }
        
        // Check for negative amounts
        if ($transaction->amount < 0) {
            $issues[] = "Transaction ID {$transaction->id}: Negative amount";
            $transactionIssues++;
        }
        
        // Check for invalid account
        if (!$transaction->account) {
            $issues[] = "Transaction ID {$transaction->id}: Invalid account";
            $transactionIssues++;
        }
        
        return $transactionIssues;
    }
    
    /**
     * Validate leases
     */
    protected function validateLeases(array &$issues): void
    {
        $this->line('Validating leases...');
        
        $leases = \App\Models\Lease::all();
        $leaseIssues = 0;
        
        foreach ($leases as $lease) {
            $leaseIssues += $this->validateLease($lease, $issues);
        }
        
        $this->line("Leases validated: {$leases->count()}, Issues found: {$leaseIssues}");
    }
    
    /**
     * Validate individual lease
     */
    protected function validateLease($lease, array &$issues): int
    {
        $leaseIssues = 0;
        
        // Check for missing required fields
        if (empty($lease->tenant_name)) {
            $issues[] = "Lease ID {$lease->id}: Missing tenant name";
            $leaseIssues++;
        }
        
        if (empty($lease->tenant_email)) {
            $issues[] = "Lease ID {$lease->id}: Missing tenant email";
            $leaseIssues++;
        }
        
        // Check for invalid status
        if (!in_array($lease->status, ['active', 'terminated', 'cancelled'])) {
            $issues[] = "Lease ID {$lease->id}: Invalid status '{$lease->status}'";
            $leaseIssues++;
        }
        
        // Check for invalid dates
        if ($lease->start_date >= $lease->end_date) {
            $issues[] = "Lease ID {$lease->id}: Start date must be before end date";
            $leaseIssues++;
        }
        
        // Check for negative amounts
        if ($lease->monthly_rent < 0) {
            $issues[] = "Lease ID {$lease->id}: Negative monthly rent";
            $leaseIssues++;
        }
        
        if ($lease->security_deposit < 0) {
            $issues[] = "Lease ID {$lease->id}: Negative security deposit";
            $leaseIssues++;
        }
        
        return $leaseIssues;
    }
    
    /**
     * Validate events
     */
    protected function validateEvents(array &$issues): void
    {
        $this->line('Validating events...');
        
        $events = \App\Models\Event::all();
        $eventIssues = 0;
        
        foreach ($events as $event) {
            $eventIssues += $this->validateEvent($event, $issues);
        }
        
        $this->line("Events validated: {$events->count()}, Issues found: {$eventIssues}");
    }
    
    /**
     * Validate individual event
     */
    protected function validateEvent($event, array &$issues): int
    {
        $eventIssues = 0;
        
        // Check for missing required fields
        if (empty($event->title)) {
            $issues[] = "Event ID {$event->id}: Missing title";
            $eventIssues++;
        }
        
        if (empty($event->venue)) {
            $issues[] = "Event ID {$event->id}: Missing venue";
            $eventIssues++;
        }
        
        // Check for invalid status
        if (!in_array($event->status, ['draft', 'published', 'cancelled', 'completed'])) {
            $issues[] = "Event ID {$event->id}: Invalid status '{$event->status}'";
            $eventIssues++;
        }
        
        // Check for invalid dates
        if ($event->start_date >= $event->end_date) {
            $issues[] = "Event ID {$event->id}: Start date must be before end date";
            $eventIssues++;
        }
        
        // Check for negative values
        if ($event->price < 0) {
            $issues[] = "Event ID {$event->id}: Negative price";
            $eventIssues++;
        }
        
        if ($event->capacity < 0) {
            $issues[] = "Event ID {$event->id}: Negative capacity";
            $eventIssues++;
        }
        
        return $eventIssues;
    }
    
    /**
     * Validate users
     */
    protected function validateUsers(array &$issues): void
    {
        $this->line('Validating users...');
        
        $users = \App\Models\User::all();
        $userIssues = 0;
        
        foreach ($users as $user) {
            $userIssues += $this->validateUser($user, $issues);
        }
        
        $this->line("Users validated: {$users->count()}, Issues found: {$userIssues}");
    }
    
    /**
     * Validate individual user
     */
    protected function validateUser($user, array &$issues): int
    {
        $userIssues = 0;
        
        // Check for missing required fields
        if (empty($user->name)) {
            $issues[] = "User ID {$user->id}: Missing name";
            $userIssues++;
        }
        
        if (empty($user->email)) {
            $issues[] = "User ID {$user->id}: Missing email";
            $userIssues++;
        }
        
        // Check for invalid email format
        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $issues[] = "User ID {$user->id}: Invalid email format";
            $userIssues++;
        }
        
        // Check for invalid status
        if (!in_array($user->status, ['active', 'inactive', 'suspended'])) {
            $issues[] = "User ID {$user->id}: Invalid status '{$user->status}'";
            $userIssues++;
        }
        
        return $userIssues;
    }
    
    /**
     * Display validation results
     */
    protected function displayValidationResults(array $issues): void
    {
        $this->line('');
        $this->info('=== VALIDATION RESULTS ===');
        
        if (empty($issues)) {
            $this->info('✅ No validation issues found!');
        } else {
            $this->warn("⚠️  Found " . count($issues) . " validation issues:");
            
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
        }
    }
    
    /**
     * Fix validation issues
     */
    protected function fixIssues(array $issues): void
    {
        $this->line('');
        $this->info('Fixing validation issues...');
        
        $integrityService = new DataIntegrityService();
        $results = $integrityService->fixAllIssues();
        
        foreach ($results as $result) {
            $this->line("  - {$result}");
        }
        
        $this->info('Validation issues fixed successfully.');
    }
}

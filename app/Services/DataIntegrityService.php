<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Lease;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Event;
use App\Models\Booking;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataIntegrityService
{
    /**
     * Check and fix all data integrity issues
     */
    public function checkAndFixAll(): array
    {
        $results = [
            'properties' => $this->checkPropertyIntegrity(),
            'leases' => $this->checkLeaseIntegrity(),
            'transactions' => $this->checkTransactionIntegrity(),
            'accounts' => $this->checkAccountIntegrity(),
            'events' => $this->checkEventIntegrity(),
            'bookings' => $this->checkBookingIntegrity(),
            'inventory' => $this->checkInventoryIntegrity(),
        ];

        return $results;
    }

    /**
     * Check property data integrity
     */
    public function checkPropertyIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for properties with inconsistent status
        $properties = Property::all();
        foreach ($properties as $property) {
            $hasActiveLease = $property->leases()
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->exists();

            if ($property->status === 'occupied' && !$hasActiveLease) {
                $issues[] = "Property {$property->id} marked as occupied but has no active lease";
                $property->update(['status' => 'available']);
                $fixed[] = "Property {$property->id} status corrected to available";
            }

            if ($property->status === 'available' && $hasActiveLease) {
                $issues[] = "Property {$property->id} marked as available but has active lease";
                $property->update(['status' => 'occupied']);
                $fixed[] = "Property {$property->id} status corrected to occupied";
            }
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check lease data integrity
     */
    public function checkLeaseIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for leases with invalid dates
        $invalidLeases = Lease::where('end_date', '<=', 'start_date')->get();
        foreach ($invalidLeases as $lease) {
            $issues[] = "Lease {$lease->id} has invalid date range";
        }

        // Check for expired leases that are still active
        $expiredLeases = Lease::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        foreach ($expiredLeases as $lease) {
            $issues[] = "Lease {$lease->id} is expired but still marked as active";
            $lease->update(['status' => 'expired']);
            $lease->property->update(['status' => 'available']);
            $fixed[] = "Lease {$lease->id} status corrected to expired";
        }

        // Check for overlapping leases
        $leases = Lease::where('status', 'active')->get();
        foreach ($leases as $lease) {
            $overlapping = Lease::where('id', '!=', $lease->id)
                ->where('property_id', $lease->property_id)
                ->where('status', 'active')
                ->where(function ($query) use ($lease) {
                    $query->whereBetween('start_date', [$lease->start_date, $lease->end_date])
                          ->orWhereBetween('end_date', [$lease->start_date, $lease->end_date])
                          ->orWhere(function ($q) use ($lease) {
                              $q->where('start_date', '<=', $lease->start_date)
                                ->where('end_date', '>=', $lease->end_date);
                          });
                })
                ->first();

            if ($overlapping) {
                $issues[] = "Lease {$lease->id} overlaps with lease {$overlapping->id}";
            }
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check transaction data integrity
     */
    public function checkTransactionIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for transactions with invalid amounts
        $invalidAmounts = Transaction::where('amount', '<=', 0)->get();
        foreach ($invalidAmounts as $transaction) {
            $issues[] = "Transaction {$transaction->id} has invalid amount: {$transaction->amount}";
        }

        // Check for transactions with future dates
        $futureTransactions = Transaction::where('transaction_date', '>', now())->get();
        foreach ($futureTransactions as $transaction) {
            $issues[] = "Transaction {$transaction->id} has future date: {$transaction->transaction_date}";
        }

        // Check for duplicate transaction references
        $duplicates = Transaction::select('transaction_reference')
            ->groupBy('transaction_reference')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $issues[] = "Duplicate transaction reference: {$duplicate->transaction_reference}";
        }

        // Check for transactions with invalid account references
        $invalidAccounts = Transaction::whereDoesntHave('account')->get();
        foreach ($invalidAccounts as $transaction) {
            $issues[] = "Transaction {$transaction->id} has invalid account reference";
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check account data integrity
     */
    public function checkAccountIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for accounts with invalid balances
        $invalidBalances = Account::where('balance', '<', 0)->get();
        foreach ($invalidBalances as $account) {
            $issues[] = "Account {$account->id} has negative balance: {$account->balance}";
        }

        // Check for duplicate account codes
        $duplicates = Account::select('account_code')
            ->groupBy('account_code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $issues[] = "Duplicate account code: {$duplicate->account_code}";
        }

        // Recalculate account balances
        $accounts = Account::all();
        foreach ($accounts as $account) {
            $calculatedBalance = Transaction::where('account_id', $account->id)
                ->where('status', 'approved')
                ->sum(DB::raw('CASE 
                    WHEN transaction_type = "income" THEN amount 
                    WHEN transaction_type = "expense" THEN -amount 
                    WHEN transaction_type = "transfer" THEN 
                        CASE WHEN account_id = ' . $account->id . ' THEN amount ELSE -amount END
                    ELSE 0 
                END'));

            if (abs($account->balance - $calculatedBalance) > 0.01) {
                $issues[] = "Account {$account->id} balance mismatch. Stored: {$account->balance}, Calculated: {$calculatedBalance}";
                $account->update(['balance' => $calculatedBalance]);
                $fixed[] = "Account {$account->id} balance corrected to {$calculatedBalance}";
            }
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check event data integrity
     */
    public function checkEventIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for events with invalid dates
        $invalidDates = Event::where('end_date', '<', 'start_date')->get();
        foreach ($invalidDates as $event) {
            $issues[] = "Event {$event->id} has invalid date range";
        }

        // Check for events with invalid times
        $invalidTimes = Event::where('end_time', '<=', 'start_time')->get();
        foreach ($invalidTimes as $event) {
            $issues[] = "Event {$event->id} has invalid time range";
        }

        // Check for events with negative prices
        $negativePrices = Event::where('price', '<', 0)->get();
        foreach ($negativePrices as $event) {
            $issues[] = "Event {$event->id} has negative price: {$event->price}";
        }

        // Check for events with invalid capacity
        $invalidCapacity = Event::where('capacity', '<=', 0)->get();
        foreach ($invalidCapacity as $event) {
            $issues[] = "Event {$event->id} has invalid capacity: {$event->capacity}";
        }

        // Check for events with booked count exceeding capacity
        $overbooked = Event::whereRaw('booked_count > capacity')->get();
        foreach ($overbooked as $event) {
            $issues[] = "Event {$event->id} is overbooked. Capacity: {$event->capacity}, Booked: {$event->booked_count}";
            $event->update(['booked_count' => $event->capacity]);
            $fixed[] = "Event {$event->id} booked count corrected to capacity";
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check booking data integrity
     */
    public function checkBookingIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for bookings with invalid quantities
        $invalidQuantities = Booking::where('ticket_quantity', '<=', 0)->get();
        foreach ($invalidQuantities as $booking) {
            $issues[] = "Booking {$booking->id} has invalid quantity: {$booking->ticket_quantity}";
        }

        // Check for bookings with invalid amounts
        $invalidAmounts = Booking::where('total_amount', '<=', 0)->get();
        foreach ($invalidAmounts as $booking) {
            $issues[] = "Booking {$booking->id} has invalid total amount: {$booking->total_amount}";
        }

        // Check for bookings with invalid payment status
        $invalidPaymentStatus = Booking::where('paid_amount', '>', 'total_amount')->get();
        foreach ($invalidPaymentStatus as $booking) {
            $issues[] = "Booking {$booking->id} has paid amount exceeding total amount";
        }

        // Check for bookings with invalid balance
        $invalidBalance = Booking::whereRaw('balance_amount != (total_amount - paid_amount)')->get();
        foreach ($invalidBalance as $booking) {
            $issues[] = "Booking {$booking->id} has incorrect balance calculation";
            $correctBalance = $booking->total_amount - $booking->paid_amount;
            $booking->update(['balance_amount' => $correctBalance]);
            $fixed[] = "Booking {$booking->id} balance corrected to {$correctBalance}";
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Check inventory data integrity
     */
    public function checkInventoryIntegrity(): array
    {
        $issues = [];
        $fixed = [];

        // Check for inventory items with negative stock
        $negativeStock = InventoryItem::where('current_stock', '<', 0)->get();
        foreach ($negativeStock as $item) {
            $issues[] = "Inventory item {$item->id} has negative stock: {$item->current_stock}";
            $item->update(['current_stock' => 0]);
            $fixed[] = "Inventory item {$item->id} stock corrected to 0";
        }

        // Check for inventory items with negative prices
        $negativePrices = InventoryItem::where('unit_price', '<', 0)->get();
        foreach ($negativePrices as $item) {
            $issues[] = "Inventory item {$item->id} has negative unit price: {$item->unit_price}";
        }

        // Check for inventory items with invalid reorder levels
        $invalidReorderLevels = InventoryItem::where('reorder_level', '<', 0)->get();
        foreach ($invalidReorderLevels as $item) {
            $issues[] = "Inventory item {$item->id} has negative reorder level: {$item->reorder_level}";
        }

        return ['issues' => $issues, 'fixed' => $fixed];
    }

    /**
     * Generate data integrity report
     */
    public function generateReport(): array
    {
        $results = $this->checkAndFixAll();
        
        $totalIssues = 0;
        $totalFixed = 0;
        
        foreach ($results as $result) {
            $totalIssues += count($result['issues']);
            $totalFixed += count($result['fixed']);
        }

        return [
            'summary' => [
                'total_issues' => $totalIssues,
                'total_fixed' => $totalFixed,
                'timestamp' => now(),
            ],
            'details' => $results,
        ];
    }
}

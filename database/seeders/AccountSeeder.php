<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets
            ['account_code' => '1000', 'account_name' => 'Cash', 'account_type' => 'asset', 'parent_account_id' => null, 'description' => 'Cash on hand and in bank', 'balance' => 0, 'is_active' => true],
            ['account_code' => '1100', 'account_name' => 'Bank Account', 'account_type' => 'asset', 'parent_account_id' => null, 'description' => 'Main business bank account', 'balance' => 0, 'is_active' => true],
            ['account_code' => '1200', 'account_name' => 'Accounts Receivable', 'account_type' => 'asset', 'parent_account_id' => null, 'description' => 'Money owed by customers', 'balance' => 0, 'is_active' => true],
            ['account_code' => '1300', 'account_name' => 'Property Assets', 'account_type' => 'asset', 'parent_account_id' => null, 'description' => 'Real estate properties', 'balance' => 0, 'is_active' => true],
            
            // Liabilities
            ['account_code' => '2000', 'account_name' => 'Accounts Payable', 'account_type' => 'liability', 'parent_account_id' => null, 'description' => 'Money owed to suppliers', 'balance' => 0, 'is_active' => true],
            ['account_code' => '2100', 'account_name' => 'Accrued Expenses', 'account_type' => 'liability', 'parent_account_id' => null, 'description' => 'Expenses incurred but not yet paid', 'balance' => 0, 'is_active' => true],
            ['account_code' => '2200', 'account_name' => 'Security Deposits', 'account_type' => 'liability', 'parent_account_id' => null, 'description' => 'Security deposits from tenants', 'balance' => 0, 'is_active' => true],
            
            // Equity
            ['account_code' => '3000', 'account_name' => 'Owner Equity', 'account_type' => 'equity', 'parent_account_id' => null, 'description' => 'Owner investment in business', 'balance' => 0, 'is_active' => true],
            ['account_code' => '3100', 'account_name' => 'Retained Earnings', 'account_type' => 'equity', 'parent_account_id' => null, 'description' => 'Accumulated business profits', 'balance' => 0, 'is_active' => true],
            
            // Income
            ['account_code' => '4000', 'account_name' => 'Rental Income', 'account_type' => 'income', 'parent_account_id' => null, 'description' => 'Income from property rentals', 'balance' => 0, 'is_active' => true],
            ['account_code' => '4100', 'account_name' => 'Event Income', 'account_type' => 'income', 'parent_account_id' => null, 'description' => 'Income from event bookings', 'balance' => 0, 'is_active' => true],
            ['account_code' => '4200', 'account_name' => 'Other Income', 'account_type' => 'income', 'parent_account_id' => null, 'description' => 'Miscellaneous income', 'balance' => 0, 'is_active' => true],
            
            // Expenses
            ['account_code' => '5000', 'account_name' => 'Property Maintenance', 'account_type' => 'expense', 'parent_account_id' => null, 'description' => 'Costs for property upkeep', 'balance' => 0, 'is_active' => true],
            ['account_code' => '5100', 'account_name' => 'Utilities', 'account_type' => 'expense', 'parent_account_id' => null, 'description' => 'Electricity, water, and other utilities', 'balance' => 0, 'is_active' => true],
            ['account_code' => '5200', 'account_name' => 'Administrative Expenses', 'account_type' => 'expense', 'parent_account_id' => null, 'description' => 'General business administration costs', 'balance' => 0, 'is_active' => true],
            ['account_code' => '5300', 'account_name' => 'Marketing Expenses', 'account_type' => 'expense', 'parent_account_id' => null, 'description' => 'Advertising and marketing costs', 'balance' => 0, 'is_active' => true],
        ];

        foreach ($accounts as $account) {
            Account::create($account);
        }
    }
}

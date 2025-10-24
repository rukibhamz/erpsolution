<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $transactionTypes = ['income', 'expense', 'transfer'];
        $statuses = ['pending', 'approved', 'rejected', 'cancelled'];
        $paymentMethods = ['cash', 'bank_transfer', 'check', 'card', 'other'];
        
        $categories = [
            'income' => ['rent', 'service_fee', 'late_fee', 'deposit', 'other_income'],
            'expense' => ['maintenance', 'utilities', 'insurance', 'taxes', 'management_fee', 'repairs'],
            'transfer' => ['account_transfer', 'loan_payment', 'investment']
        ];

        $transactionType = $this->faker->randomElement($transactionTypes);
        $category = $this->faker->randomElement($categories[$transactionType]);
        
        $amount = $this->faker->numberBetween(1000, 1000000); // ₦1,000 - ₦1,000,000

        return [
            'transaction_reference' => 'TXN-' . str_pad($this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'account_id' => Account::factory(),
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'description' => $this->generateDescription($transactionType, $category),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'category' => $category,
            'subcategory' => $this->faker->optional(0.5)->randomElement([
                'monthly_rent', 'quarterly_rent', 'annual_rent',
                'electricity', 'water', 'internet', 'security',
                'cleaning', 'gardening', 'plumbing', 'electrical'
            ]),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'reference_number' => $this->faker->optional(0.7)->bothify('REF-####-####'),
            'notes' => $this->faker->optional(0.3)->paragraph(1),
            'created_by' => User::factory(),
            'approved_by' => $this->faker->optional(0.8)->randomElement([User::factory()]),
            'approved_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'status' => $this->faker->randomElement($statuses),
        ];
    }

    /**
     * Generate description based on transaction type and category
     */
    private function generateDescription(string $type, string $category): string
    {
        $descriptions = [
            'income' => [
                'rent' => 'Monthly rent payment',
                'service_fee' => 'Service fee collection',
                'late_fee' => 'Late payment fee',
                'deposit' => 'Security deposit',
                'other_income' => 'Miscellaneous income'
            ],
            'expense' => [
                'maintenance' => 'Property maintenance',
                'utilities' => 'Utility bills payment',
                'insurance' => 'Insurance premium',
                'taxes' => 'Tax payment',
                'management_fee' => 'Property management fee',
                'repairs' => 'Property repairs'
            ],
            'transfer' => [
                'account_transfer' => 'Account transfer',
                'loan_payment' => 'Loan payment',
                'investment' => 'Investment transfer'
            ]
        ];

        return $descriptions[$type][$category] ?? 'Transaction description';
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'income',
            'category' => $this->faker->randomElement(['rent', 'service_fee', 'late_fee', 'deposit', 'other_income']),
            'amount' => $this->faker->numberBetween(10000, 500000),
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'expense',
            'category' => $this->faker->randomElement(['maintenance', 'utilities', 'insurance', 'taxes', 'management_fee', 'repairs']),
            'amount' => $this->faker->numberBetween(1000, 100000),
        ]);
    }

    /**
     * Indicate that the transaction is a transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'transfer',
            'category' => $this->faker->randomElement(['account_transfer', 'loan_payment', 'investment']),
            'amount' => $this->faker->numberBetween(5000, 200000),
        ]);
    }

    /**
     * Indicate that the transaction is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the transaction is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the transaction is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Create a rent income transaction.
     */
    public function rentIncome(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'income',
            'category' => 'rent',
            'subcategory' => 'monthly_rent',
            'description' => 'Monthly rent payment',
            'amount' => $this->faker->numberBetween(50000, 500000),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash']),
        ]);
    }

    /**
     * Create a maintenance expense transaction.
     */
    public function maintenanceExpense(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'expense',
            'category' => 'maintenance',
            'description' => 'Property maintenance',
            'amount' => $this->faker->numberBetween(5000, 50000),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'check']),
        ]);
    }
}

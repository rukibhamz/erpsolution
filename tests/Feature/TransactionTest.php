<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with admin role
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        
        // Create test account
        $this->account = Account::factory()->create();
    }

    /** @test */
    public function it_can_display_transactions_index()
    {
        // Create some test transactions
        Transaction::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.index');
        $response->assertViewHas('transactions');
    }

    /** @test */
    public function it_can_create_an_income_transaction()
    {
        $transactionData = [
            'account_id' => $this->account->id,
            'transaction_type' => 'income',
            'amount' => 150000,
            'description' => 'Monthly rent payment',
            'transaction_date' => now()->format('Y-m-d'),
            'category' => 'rent',
            'subcategory' => 'monthly_rent',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF123456',
            'notes' => 'Rent payment for January'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.store'), $transactionData);

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'transaction_type' => 'income',
            'amount' => 150000,
            'description' => 'Monthly rent payment',
        ]);
    }

    /** @test */
    public function it_can_create_an_expense_transaction()
    {
        $transactionData = [
            'account_id' => $this->account->id,
            'transaction_type' => 'expense',
            'amount' => 25000,
            'description' => 'Property maintenance',
            'transaction_date' => now()->format('Y-m-d'),
            'category' => 'maintenance',
            'subcategory' => 'repairs',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF789012',
            'notes' => 'Plumbing repairs'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.store'), $transactionData);

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'transaction_type' => 'expense',
            'amount' => 25000,
            'description' => 'Property maintenance',
        ]);
    }

    /** @test */
    public function it_can_create_a_transfer_transaction()
    {
        $toAccount = Account::factory()->create();
        
        $transactionData = [
            'account_id' => $this->account->id,
            'transaction_type' => 'transfer',
            'amount' => 100000,
            'description' => 'Transfer to savings account',
            'transaction_date' => now()->format('Y-m-d'),
            'category' => 'account_transfer',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'TRF345678',
            'notes' => 'Monthly savings transfer'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.store'), $transactionData);

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('transactions', [
            'account_id' => $this->account->id,
            'transaction_type' => 'transfer',
            'amount' => 100000,
            'description' => 'Transfer to savings account',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_transaction()
    {
        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.store'), []);

        $response->assertSessionHasErrors([
            'account_id',
            'transaction_type',
            'amount',
            'description',
            'transaction_date',
            'category',
            'payment_method'
        ]);
    }

    /** @test */
    public function it_can_approve_a_transaction()
    {
        $transaction = Transaction::factory()->pending()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.approve', $transaction));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $transaction->refresh();
        $this->assertEquals('approved', $transaction->status);
        $this->assertNotNull($transaction->approved_by);
        $this->assertNotNull($transaction->approved_at);
    }

    /** @test */
    public function it_can_reject_a_transaction()
    {
        $transaction = Transaction::factory()->pending()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.reject', $transaction));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $transaction->refresh();
        $this->assertEquals('rejected', $transaction->status);
    }

    /** @test */
    public function it_can_cancel_a_transaction()
    {
        $transaction = Transaction::factory()->approved()->create();

        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.cancel', $transaction));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $transaction->refresh();
        $this->assertEquals('cancelled', $transaction->status);
    }

    /** @test */
    public function it_can_update_a_transaction()
    {
        $transaction = Transaction::factory()->create();
        
        $updateData = [
            'account_id' => $this->account->id,
            'transaction_type' => 'income',
            'amount' => 200000,
            'description' => 'Updated transaction description',
            'transaction_date' => now()->format('Y-m-d'),
            'category' => 'rent',
            'subcategory' => 'monthly_rent',
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF999999',
            'notes' => 'Updated notes',
            'status' => 'approved'
        ];

        $response = $this->actingAs($this->user)
            ->put(route('admin.transactions.update', $transaction), $updateData);

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'amount' => 200000,
            'description' => 'Updated transaction description',
        ]);
    }

    /** @test */
    public function it_can_delete_a_transaction()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('admin.transactions.destroy', $transaction));

        $response->assertRedirect(route('admin.transactions.index'));
        $response->assertSessionHas('success');
        
        $this->assertSoftDeleted('transactions', [
            'id' => $transaction->id,
        ]);
    }

    /** @test */
    public function it_can_search_transactions()
    {
        Transaction::factory()->create(['description' => 'Rent payment']);
        Transaction::factory()->create(['description' => 'Maintenance fee']);

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.index', ['search' => 'Rent']));

        $response->assertStatus(200);
        $response->assertSee('Rent payment');
        $response->assertDontSee('Maintenance fee');
    }

    /** @test */
    public function it_can_filter_transactions_by_type()
    {
        Transaction::factory()->income()->create();
        Transaction::factory()->expense()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.index', ['transaction_type' => 'income']));

        $response->assertStatus(200);
        $response->assertSee('income');
    }

    /** @test */
    public function it_can_filter_transactions_by_status()
    {
        Transaction::factory()->approved()->create();
        Transaction::factory()->pending()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('approved');
    }

    /** @test */
    public function it_can_filter_transactions_by_date_range()
    {
        Transaction::factory()->create([
            'transaction_date' => now()->subDays(10)
        ]);
        Transaction::factory()->create([
            'transaction_date' => now()->subDays(5)
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.index', [
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_display_transaction_details()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.show', $transaction));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.show');
        $response->assertViewHas('transaction', $transaction);
    }

    /** @test */
    public function it_can_display_transaction_edit_form()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.edit', $transaction));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.edit');
        $response->assertViewHas('transaction', $transaction);
    }

    /** @test */
    public function it_can_display_transaction_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.transactions.create');
        $response->assertViewHas('accounts');
    }

    /** @test */
    public function it_can_export_transactions_to_pdf()
    {
        Transaction::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.export.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_export_transactions_to_excel()
    {
        Transaction::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.transactions.export.excel'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function it_generates_unique_transaction_references()
    {
        $transaction1 = Transaction::factory()->create();
        $transaction2 = Transaction::factory()->create();

        $this->assertNotEquals(
            $transaction1->transaction_reference,
            $transaction2->transaction_reference
        );
    }

    /** @test */
    public function it_can_handle_transaction_approval_workflow()
    {
        $transaction = Transaction::factory()->pending()->create();

        // Approve transaction
        $response = $this->actingAs($this->user)
            ->post(route('admin.transactions.approve', $transaction));

        $response->assertRedirect();
        
        $transaction->refresh();
        $this->assertEquals('approved', $transaction->status);
        $this->assertEquals($this->user->id, $transaction->approved_by);
    }
}

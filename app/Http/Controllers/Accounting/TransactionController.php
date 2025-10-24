<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Services\TransactionApprovalService;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * PERFORMANCE FIX: Display a listing of the resource with optimized queries
     */
    public function index(Request $request): View
    {
        $optimizationService = new QueryOptimizationService();
        $query = $optimizationService->getOptimizedTransactions($request->all());
        $transactions = $query->latest()->paginate(15);
        $accounts = Account::active()->select('id', 'account_name', 'account_type')->get();

        return view('admin.transactions.index', compact('transactions', 'accounts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $accounts = Account::active()->get();
        return view('admin.transactions.create', compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => ['required', Rule::in(['income', 'expense', 'transfer', 'adjustment'])],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // RACE CONDITION FIX: Generate unique transaction reference using database transaction
        $transaction = DB::transaction(function () use ($validated) {
            // Get the next sequence number atomically
            $nextNumber = DB::table('transactions')
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(transaction_reference, 5) AS UNSIGNED)')) + 1;

            $transactionReference = 'TXN-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            return Transaction::create([
                ...$validated,
                'transaction_reference' => $transactionReference,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction created');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): View
    {
        $transaction->load(['account', 'createdBy', 'approvedBy']);
        return view('admin.transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction): View
    {
        // Only allow editing of pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Only pending transactions can be edited.');
        }

        $accounts = Account::active()->get();
        return view('admin.transactions.edit', compact('transaction', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        // Only allow editing of pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Only pending transactions can be edited.');
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => ['required', Rule::in(['income', 'expense', 'transfer', 'adjustment'])],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $transaction->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction updated');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        // Only allow deletion of pending transactions
        if ($transaction->status !== 'pending') {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Only pending transactions can be deleted.');
        }

        $transaction->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction deleted');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }

    /**
     * BUSINESS LOGIC FIX: Approve transaction with comprehensive validation
     */
    public function approve(Transaction $transaction): RedirectResponse
    {
        $approvalService = new TransactionApprovalService();
        $result = $approvalService->approveTransaction($transaction, auth()->user());
        
        if ($result['success']) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($transaction)
                ->log('Transaction approved');

            $message = 'Transaction approved successfully.';
            if (!empty($result['warnings'])) {
                $message .= ' Warnings: ' . implode(', ', $result['warnings']);
            }
            
            return redirect()->back()
                ->with('success', $message);
        } else {
            return redirect()->back()
                ->with('error', implode(', ', $result['errors']));
        }
    }

    /**
     * BUSINESS LOGIC FIX: Reject transaction with proper validation
     */
    public function reject(Transaction $transaction): RedirectResponse
    {
        $approvalService = new TransactionApprovalService();
        $result = $approvalService->rejectTransaction($transaction, auth()->user());
        
        if ($result['success']) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($transaction)
                ->log('Transaction rejected');

            return redirect()->back()
                ->with('success', 'Transaction rejected successfully.');
        } else {
            return redirect()->back()
                ->with('error', implode(', ', $result['errors']));
        }
    }

    /**
     * BUSINESS LOGIC FIX: Cancel transaction with proper validation
     */
    public function cancel(Transaction $transaction): RedirectResponse
    {
        $approvalService = new TransactionApprovalService();
        $result = $approvalService->cancelTransaction($transaction, auth()->user());
        
        if ($result['success']) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($transaction)
                ->log('Transaction cancelled');

            return redirect()->back()
                ->with('success', 'Transaction cancelled successfully.');
        } else {
            return redirect()->back()
                ->with('error', implode(', ', $result['errors']));
        }
    }
}
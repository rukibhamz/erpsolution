<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Transaction::with(['account', 'createdBy', 'approvedBy']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('transaction_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('reference_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(15);
        $accounts = Account::active()->get();

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
     * Approve transaction
     * BUSINESS LOGIC FIX: Added proper validation and error handling
     */
    public function approve(Transaction $transaction): RedirectResponse
    {
        try {
            $transaction->approve(auth()->user());
            
            activity()
                ->causedBy(auth()->user())
                ->performedOn($transaction)
                ->log('Transaction approved');

            return redirect()->back()
                ->with('success', 'Transaction approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject transaction
     */
    public function reject(Transaction $transaction): RedirectResponse
    {
        $transaction->reject();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction rejected');

        return redirect()->back()
            ->with('success', 'Transaction rejected successfully.');
    }

    /**
     * Cancel transaction
     */
    public function cancel(Transaction $transaction): RedirectResponse
    {
        $transaction->cancel();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction cancelled');

        return redirect()->back()
            ->with('success', 'Transaction cancelled successfully.');
    }
}
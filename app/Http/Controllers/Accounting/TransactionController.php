<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): View
    {
        $query = Transaction::with(['account', 'createdBy', 'approvedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('transaction_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('transaction_date', '<=', $request->end_date);
        }

        $transactions = $query->latest('transaction_date')->paginate(15);
        $accounts = Account::active()->get();

        return view('accounting.transactions.index', compact('transactions', 'accounts'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): View
    {
        $accounts = Account::active()->get();
        $selectedAccount = $request->account_id ? Account::find($request->account_id) : null;

        return view('accounting.transactions.create', compact('accounts', 'selectedAccount'));
    }

    /**
     * Store a newly created transaction.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|in:income,expense,transfer,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate a more robust transaction reference
        $transactionReference = 'TXN-' . now()->format('YmdHis') . '-' . Str::random(6);

        $transaction = Transaction::create([
            'transaction_reference' => $transactionReference,
            'account_id' => $request->account_id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'category' => $request->category,
            'subcategory' => $request->subcategory,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction created');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): View
    {
        $transaction->load(['account', 'createdBy', 'approvedBy']);
        return view('accounting.transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the transaction.
     */
    public function edit(Transaction $transaction): View
    {
        $accounts = Account::active()->get();
        return view('accounting.transactions.edit', compact('transaction', 'accounts'));
    }

    /**
     * Update the specified transaction.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        // Check if transaction is already approved
        if ($transaction->isApproved()) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Cannot edit approved transactions.');
        }

        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'transaction_type' => 'required|in:income,expense,transfer,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'transaction_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'subcategory' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $transaction->update([
            'account_id' => $request->account_id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date,
            'category' => $request->category,
            'subcategory' => $request->subcategory,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction updated');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction updated successfully.');
    }

    /**
     * Approve the specified transaction.
     */
    public function approve(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isApproved()) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Transaction is already approved.');
        }

        $transaction->approve(auth()->user());

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction approved');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction approved successfully.');
    }

    /**
     * Reject the specified transaction.
     */
    public function reject(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isApproved()) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Cannot reject approved transactions.');
        }

        $transaction->reject();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction rejected');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction rejected successfully.');
    }

    /**
     * Cancel the specified transaction.
     */
    public function cancel(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isApproved()) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Cannot cancel approved transactions.');
        }

        $transaction->cancel();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction cancelled');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction cancelled successfully.');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        if ($transaction->isApproved()) {
            return redirect()->route('admin.transactions.index')
                ->with('error', 'Cannot delete approved transactions.');
        }

        $transaction->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($transaction)
            ->log('Transaction deleted');

        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaction deleted successfully.');
    }
}

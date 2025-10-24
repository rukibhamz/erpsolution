<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): View
    {
        $query = Account::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by account type
        if ($request->filled('account_type')) {
            $query->where('account_type', $request->input('account_type'));
        }

        // Filter by account category
        if ($request->filled('account_category')) {
            $query->where('account_category', $request->input('account_category'));
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $accounts = $query->orderBy('account_code')->paginate(15);

        return view('accounting.accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): View
    {
        return view('accounting.accounts.create');
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'account_code' => 'required|string|max:20|unique:accounts,account_code',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_category' => 'required|string|max:100',
            'opening_balance' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $account = Account::create([
            'account_code' => $request->input('account_code'),
            'account_name' => $request->input('account_name'),
            'description' => $request->input('description'),
            'account_type' => $request->input('account_type'),
            'account_category' => $request->input('account_category'),
            'opening_balance' => $request->input('opening_balance'),
            'current_balance' => $request->input('opening_balance'),
            'is_active' => $request->boolean('is_active', true),
            'is_system_account' => false,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log('Account created');

        return redirect()->route('admin.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): View
    {
        $account->load(['journalEntryItems.journalEntry']);
        return view('accounting.accounts.show', compact('account'));
    }

    /**
     * Show the form for editing the account.
     */
    public function edit(Account $account): View
    {
        return view('accounting.accounts.edit', compact('account'));
    }

    /**
     * Update the specified account.
     */
    public function update(Request $request, Account $account): RedirectResponse
    {
        $request->validate([
            'account_code' => 'required|string|max:20|unique:accounts,account_code,' . $account->id,
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'account_type' => 'required|in:asset,liability,equity,revenue,expense',
            'account_category' => 'required|string|max:100',
            'opening_balance' => 'required|numeric',
            'is_active' => 'boolean',
        ]);

        $account->update([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'description' => $request->description,
            'account_type' => $request->account_type,
            'account_category' => $request->account_category,
            'opening_balance' => $request->opening_balance,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Recalculate current balance
        $account->updateBalance();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log('Account updated');

        return redirect()->route('admin.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Account $account): RedirectResponse
    {
        // Check if account has transactions
        if ($account->journalEntryItems()->exists()) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'Cannot delete account with existing transactions.');
        }

        // Check if account is a system account
        if ($account->is_system_account) {
            return redirect()->route('admin.accounts.index')
                ->with('error', 'Cannot delete system accounts.');
        }

        $account->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log('Account deleted');

        return redirect()->route('admin.accounts.index')
            ->with('success', 'Account deleted successfully.');
    }

    /**
     * Toggle account status.
     */
    public function toggleStatus(Account $account): RedirectResponse
    {
        $account->update(['is_active' => !$account->is_active]);

        $status = $account->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log("Account {$status}");

        return redirect()->route('admin.accounts.index')
            ->with('success', "Account {$status} successfully.");
    }

    /**
     * Update account balance.
     */
    public function updateBalance(Account $account): RedirectResponse
    {
        $account->updateBalance();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($account)
            ->log('Account balance updated');

        return redirect()->route('admin.accounts.show', $account)
            ->with('success', 'Account balance updated successfully.');
    }
}

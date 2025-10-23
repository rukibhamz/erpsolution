<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class JournalEntryController extends Controller
{
    /**
     * Display a listing of journal entries.
     */
    public function index(Request $request): View
    {
        $query = JournalEntry::with(['createdBy', 'approvedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }

        $journalEntries = $query->latest('entry_date')->paginate(15);

        return view('accounting.journal-entries.index', compact('journalEntries'));
    }

    /**
     * Show the form for creating a new journal entry.
     */
    public function create(): View
    {
        $accounts = Account::active()->get();
        return view('accounting.journal-entries.create', compact('accounts'));
    }

    /**
     * Store a newly created journal entry.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit_amount' => 'required_without:items.*.credit_amount|numeric|min:0',
            'items.*.credit_amount' => 'required_without:items.*.debit_amount|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.reference' => 'nullable|string|max:100',
        ]);

        // Validate that debit and credit amounts are balanced
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($request->items as $item) {
            $debit = $item['debit_amount'] ?? 0;
            $credit = $item['credit_amount'] ?? 0;
            
            if ($debit > 0 && $credit > 0) {
                return redirect()->back()
                    ->withErrors(['items' => 'Each item must be either debit or credit, not both.'])
                    ->withInput();
            }
            
            if ($debit == 0 && $credit == 0) {
                return redirect()->back()
                    ->withErrors(['items' => 'Each item must have either debit or credit amount.'])
                    ->withInput();
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return redirect()->back()
                ->withErrors(['items' => 'Total debits must equal total credits.'])
                ->withInput();
        }

        // Generate entry reference
        $entryReference = 'JE-' . str_pad(JournalEntry::count() + 1, 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $entryReference, $totalDebit, $totalCredit) {
            $journalEntry = JournalEntry::create([
                'entry_reference' => $entryReference,
                'entry_date' => $request->entry_date,
                'description' => $request->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $item['account_id'],
                    'debit_amount' => $item['debit_amount'] ?? 0,
                    'credit_amount' => $item['credit_amount'] ?? 0,
                    'description' => $item['description'],
                    'reference' => $item['reference'],
                ]);
            }
        });

        activity()
            ->causedBy(auth()->user())
            ->log('Journal entry created');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry created successfully.');
    }

    /**
     * Display the specified journal entry.
     */
    public function show(JournalEntry $journalEntry): View
    {
        $journalEntry->load(['journalEntryItems.account', 'createdBy', 'approvedBy']);
        return view('accounting.journal-entries.show', compact('journalEntry'));
    }

    /**
     * Show the form for editing the journal entry.
     */
    public function edit(JournalEntry $journalEntry): View
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot edit approved journal entries.');
        }

        $accounts = Account::active()->get();
        $journalEntry->load('journalEntryItems');
        return view('accounting.journal-entries.edit', compact('journalEntry', 'accounts'));
    }

    /**
     * Update the specified journal entry.
     */
    public function update(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot edit approved journal entries.');
        }

        $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit_amount' => 'required_without:items.*.credit_amount|numeric|min:0',
            'items.*.credit_amount' => 'required_without:items.*.credit_amount|numeric|min:0',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.reference' => 'nullable|string|max:100',
        ]);

        // Validate that debit and credit amounts are balanced
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($request->items as $item) {
            $debit = $item['debit_amount'] ?? 0;
            $credit = $item['credit_amount'] ?? 0;
            
            if ($debit > 0 && $credit > 0) {
                return redirect()->back()
                    ->withErrors(['items' => 'Each item must be either debit or credit, not both.'])
                    ->withInput();
            }
            
            if ($debit == 0 && $credit == 0) {
                return redirect()->back()
                    ->withErrors(['items' => 'Each item must have either debit or credit amount.'])
                    ->withInput();
            }
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return redirect()->back()
                ->withErrors(['items' => 'Total debits must equal total credits.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $journalEntry, $totalDebit, $totalCredit) {
            $journalEntry->update([
                'entry_date' => $request->entry_date,
                'description' => $request->description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'notes' => $request->notes,
            ]);

            // Delete existing items
            $journalEntry->journalEntryItems()->delete();

            // Create new items
            foreach ($request->items as $item) {
                JournalEntryItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $item['account_id'],
                    'debit_amount' => $item['debit_amount'] ?? 0,
                    'credit_amount' => $item['credit_amount'] ?? 0,
                    'description' => $item['description'],
                    'reference' => $item['reference'],
                ]);
            }
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($journalEntry)
            ->log('Journal entry updated');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry updated successfully.');
    }

    /**
     * Approve the specified journal entry.
     */
    public function approve(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Journal entry is already approved.');
        }

        if (!$journalEntry->isBalanced()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot approve unbalanced journal entry.');
        }

        $journalEntry->approve(auth()->user());

        activity()
            ->causedBy(auth()->user())
            ->performedOn($journalEntry)
            ->log('Journal entry approved');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry approved successfully.');
    }

    /**
     * Reject the specified journal entry.
     */
    public function reject(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot reject approved journal entries.');
        }

        $journalEntry->reject();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($journalEntry)
            ->log('Journal entry rejected');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry rejected successfully.');
    }

    /**
     * Cancel the specified journal entry.
     */
    public function cancel(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot cancel approved journal entries.');
        }

        $journalEntry->cancel();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($journalEntry)
            ->log('Journal entry cancelled');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry cancelled successfully.');
    }

    /**
     * Remove the specified journal entry.
     */
    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        if ($journalEntry->isApproved()) {
            return redirect()->route('admin.journal-entries.index')
                ->with('error', 'Cannot delete approved journal entries.');
        }

        $journalEntry->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($journalEntry)
            ->log('Journal entry deleted');

        return redirect()->route('admin.journal-entries.index')
            ->with('success', 'Journal entry deleted successfully.');
    }
}

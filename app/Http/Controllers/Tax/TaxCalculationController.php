<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxCalculation;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaxCalculationController extends Controller
{
    /**
     * Display a listing of tax calculations.
     */
    public function index(Request $request): View
    {
        $query = TaxCalculation::with(['taxType', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('base_amount', 'like', "%{$search}%")
                  ->orWhere('tax_amount', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filter by tax type
        if ($request->filled('tax_type_id')) {
            $query->where('tax_type_id', $request->tax_type_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('calculation_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('calculation_date', '<=', $request->end_date);
        }

        $taxCalculations = $query->latest('calculation_date')->paginate(15);
        $taxTypes = TaxType::active()->get();

        return view('tax.calculations.index', compact('taxCalculations', 'taxTypes'));
    }

    /**
     * Show the form for creating a new tax calculation.
     */
    public function create(): View
    {
        $taxTypes = TaxType::active()->get();
        return view('tax.calculations.create', compact('taxTypes'));
    }

    /**
     * Store a newly created tax calculation.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'tax_type_id' => 'required|exists:tax_types,id',
            'base_amount' => 'required|numeric|min:0',
            'calculation_date' => 'required|date',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
        ]);

        $taxType = TaxType::findOrFail($request->tax_type_id);
        
        $taxCalculation = TaxCalculation::create([
            'tax_type_id' => $request->tax_type_id,
            'base_amount' => $request->base_amount,
            'tax_rate' => $taxType->rate,
            'tax_amount' => $taxType->calculateTax($request->base_amount),
            'calculation_date' => $request->calculation_date,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'status' => 'calculated',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation created');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation created successfully.');
    }

    /**
     * Display the specified tax calculation.
     */
    public function show(TaxCalculation $taxCalculation): View
    {
        $taxCalculation->load(['taxType', 'createdBy', 'taxPayments']);
        return view('tax.calculations.show', compact('taxCalculation'));
    }

    /**
     * Show the form for editing the tax calculation.
     */
    public function edit(TaxCalculation $taxCalculation): View
    {
        $taxTypes = TaxType::active()->get();
        return view('tax.calculations.edit', compact('taxCalculation', 'taxTypes'));
    }

    /**
     * Update the specified tax calculation.
     */
    public function update(Request $request, TaxCalculation $taxCalculation): RedirectResponse
    {
        $request->validate([
            'tax_type_id' => 'required|exists:tax_types,id',
            'base_amount' => 'required|numeric|min:0',
            'calculation_date' => 'required|date',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'notes' => 'nullable|string|max:1000',
        ]);

        $taxType = TaxType::findOrFail($request->tax_type_id);

        $taxCalculation->update([
            'tax_type_id' => $request->tax_type_id,
            'base_amount' => $request->base_amount,
            'tax_rate' => $taxType->rate,
            'tax_amount' => $taxType->calculateTax($request->base_amount),
            'calculation_date' => $request->calculation_date,
            'period_start' => $request->period_start,
            'period_end' => $request->period_end,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation updated');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation updated successfully.');
    }

    /**
     * Mark calculation as paid.
     */
    public function markAsPaid(TaxCalculation $taxCalculation): RedirectResponse
    {
        $taxCalculation->markAsPaid();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation marked as paid');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation marked as paid.');
    }

    /**
     * Mark calculation as overdue.
     */
    public function markAsOverdue(TaxCalculation $taxCalculation): RedirectResponse
    {
        $taxCalculation->markAsOverdue();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation marked as overdue');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation marked as overdue.');
    }

    /**
     * Mark calculation as cancelled.
     */
    public function markAsCancelled(TaxCalculation $taxCalculation): RedirectResponse
    {
        $taxCalculation->markAsCancelled();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation marked as cancelled');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation marked as cancelled.');
    }

    /**
     * Remove the specified tax calculation.
     */
    public function destroy(TaxCalculation $taxCalculation): RedirectResponse
    {
        $taxCalculation->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxCalculation)
            ->log('Tax calculation deleted');

        return redirect()->route('admin.tax-calculations.index')
            ->with('success', 'Tax calculation deleted successfully.');
    }
}

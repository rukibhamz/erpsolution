<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TaxTypeController extends Controller
{
    /**
     * Display a listing of tax types.
     */
    public function index(Request $request): View
    {
        $query = TaxType::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by rate type
        if ($request->filled('rate_type')) {
            $query->where('rate_type', $request->rate_type);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $taxTypes = $query->orderBy('name')->paginate(15);

        return view('tax.tax-types.index', compact('taxTypes'));
    }

    /**
     * Show the form for creating a new tax type.
     */
    public function create(): View
    {
        return view('tax.tax-types.create');
    }

    /**
     * Store a newly created tax type.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tax_types,code',
            'description' => 'nullable|string|max:1000',
            'rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:percentage,fixed',
            'is_percentage' => 'boolean',
            'is_active' => 'boolean',
            'applies_to' => 'nullable|string|max:255',
            'calculation_method' => 'nullable|string|max:255',
        ]);

        $taxType = TaxType::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'rate' => $request->rate,
            'rate_type' => $request->rate_type,
            'is_percentage' => $request->boolean('is_percentage', $request->rate_type === 'percentage'),
            'is_active' => $request->boolean('is_active', true),
            'applies_to' => $request->applies_to,
            'calculation_method' => $request->calculation_method,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxType)
            ->log('Tax type created');

        return redirect()->route('admin.tax-types.index')
            ->with('success', 'Tax type created successfully.');
    }

    /**
     * Display the specified tax type.
     */
    public function show(TaxType $taxType): View
    {
        $taxType->load(['taxCalculations', 'taxPayments']);
        return view('tax.tax-types.show', compact('taxType'));
    }

    /**
     * Show the form for editing the tax type.
     */
    public function edit(TaxType $taxType): View
    {
        return view('tax.tax-types.edit', compact('taxType'));
    }

    /**
     * Update the specified tax type.
     */
    public function update(Request $request, TaxType $taxType): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:tax_types,code,' . $taxType->id,
            'description' => 'nullable|string|max:1000',
            'rate' => 'required|numeric|min:0',
            'rate_type' => 'required|in:percentage,fixed',
            'is_percentage' => 'boolean',
            'is_active' => 'boolean',
            'applies_to' => 'nullable|string|max:255',
            'calculation_method' => 'nullable|string|max:255',
        ]);

        $taxType->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'rate' => $request->rate,
            'rate_type' => $request->rate_type,
            'is_percentage' => $request->boolean('is_percentage', $request->rate_type === 'percentage'),
            'is_active' => $request->boolean('is_active', true),
            'applies_to' => $request->applies_to,
            'calculation_method' => $request->calculation_method,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxType)
            ->log('Tax type updated');

        return redirect()->route('admin.tax-types.index')
            ->with('success', 'Tax type updated successfully.');
    }

    /**
     * Remove the specified tax type.
     */
    public function destroy(TaxType $taxType): RedirectResponse
    {
        // Check if tax type has calculations
        if ($taxType->taxCalculations()->exists()) {
            return redirect()->route('admin.tax-types.index')
                ->with('error', 'Cannot delete tax type with existing calculations.');
        }

        $taxType->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxType)
            ->log('Tax type deleted');

        return redirect()->route('admin.tax-types.index')
            ->with('success', 'Tax type deleted successfully.');
    }

    /**
     * Toggle tax type status.
     */
    public function toggleStatus(TaxType $taxType): RedirectResponse
    {
        $taxType->update(['is_active' => !$taxType->is_active]);

        $status = $taxType->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($taxType)
            ->log("Tax type {$status}");

        return redirect()->route('admin.tax-types.index')
            ->with('success', "Tax type {$status} successfully.");
    }
}

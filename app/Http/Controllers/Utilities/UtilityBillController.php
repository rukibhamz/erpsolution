<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\UtilityBill;
use App\Models\Property;
use App\Models\UtilityType;
use App\Models\UtilityMeter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UtilityBillController extends Controller
{
    /**
     * Display a listing of utility bills.
     */
    public function index(Request $request): View
    {
        $query = UtilityBill::with(['property', 'utilityType', 'meter', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('property', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by utility type
        if ($request->filled('utility_type_id')) {
            $query->where('utility_type_id', $request->input('utility_type_id'));
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('billing_period_start', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('billing_period_end', '<=', $request->input('end_date'));
        }

        $utilityBills = $query->latest('billing_period_start')->paginate(15);
        $properties = Property::active()->get();
        $utilityTypes = UtilityType::active()->get();

        return view('utilities.bills.index', compact('utilityBills', 'properties', 'utilityTypes'));
    }

    /**
     * Show the form for creating a new utility bill.
     */
    public function create(Request $request): View
    {
        $properties = Property::active()->get();
        $utilityTypes = UtilityType::active()->get();
        $utilityMeters = UtilityMeter::active()->get();
        $selectedProperty = $request->input('property_id') ? Property::find($request->input('property_id')) : null;
        
        return view('utilities.bills.create', compact('properties', 'utilityTypes', 'utilityMeters', 'selectedProperty'));
    }

    /**
     * Store a newly created utility bill.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'utility_type_id' => 'required|exists:utility_types,id',
            'meter_id' => 'required|exists:utility_meters,id',
            'billing_period_start' => 'required|date',
            'billing_period_end' => 'required|date|after:billing_period_start',
            'previous_reading' => 'required|numeric|min:0',
            'current_reading' => 'required|numeric|min:0|gte:previous_reading',
            'rate_per_unit' => 'required|numeric|min:0',
            'due_date' => 'required|date|after:billing_period_end',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate bill number
        $billNumber = 'UB-' . str_pad(UtilityBill::count() + 1, 6, '0', STR_PAD_LEFT);

        $utilityBill = UtilityBill::create([
            'bill_number' => $billNumber,
            'property_id' => $request->input('property_id'),
            'utility_type_id' => $request->input('utility_type_id'),
            'meter_id' => $request->input('meter_id'),
            'billing_period_start' => $request->input('billing_period_start'),
            'billing_period_end' => $request->input('billing_period_end'),
            'previous_reading' => $request->input('previous_reading'),
            'current_reading' => $request->input('current_reading'),
            'consumption' => $request->input('current_reading') - $request->input('previous_reading'),
            'rate_per_unit' => $request->input('rate_per_unit'),
            'base_amount' => ($request->input('current_reading') - $request->input('previous_reading')) * $request->input('rate_per_unit'),
            'tax_amount' => (($request->input('current_reading') - $request->input('previous_reading')) * $request->input('rate_per_unit')) * 0.05,
            'total_amount' => (($request->input('current_reading') - $request->input('previous_reading')) * $request->input('rate_per_unit')) * 1.05,
            'due_date' => $request->input('due_date'),
            'payment_status' => 'pending',
            'notes' => $request->input('notes'),
            'created_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill created');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill created successfully.');
    }

    /**
     * Display the specified utility bill.
     */
    public function show(UtilityBill $utilityBill): View
    {
        $utilityBill->load(['property', 'utilityType', 'meter', 'createdBy', 'billPayments']);
        return view('utilities.bills.show', compact('utilityBill'));
    }

    /**
     * Show the form for editing the utility bill.
     */
    public function edit(UtilityBill $utilityBill): View
    {
        $properties = Property::active()->get();
        $utilityTypes = UtilityType::active()->get();
        $utilityMeters = UtilityMeter::active()->get();
        return view('utilities.bills.edit', compact('utilityBill', 'properties', 'utilityTypes', 'utilityMeters'));
    }

    /**
     * Update the specified utility bill.
     */
    public function update(Request $request, UtilityBill $utilityBill): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'utility_type_id' => 'required|exists:utility_types,id',
            'meter_id' => 'required|exists:utility_meters,id',
            'billing_period_start' => 'required|date',
            'billing_period_end' => 'required|date|after:billing_period_start',
            'previous_reading' => 'required|numeric|min:0',
            'current_reading' => 'required|numeric|min:0|gte:previous_reading',
            'rate_per_unit' => 'required|numeric|min:0',
            'due_date' => 'required|date|after:billing_period_end',
            'notes' => 'nullable|string|max:1000',
        ]);

        $utilityBill->update([
            'property_id' => $request->property_id,
            'utility_type_id' => $request->utility_type_id,
            'meter_id' => $request->meter_id,
            'billing_period_start' => $request->billing_period_start,
            'billing_period_end' => $request->billing_period_end,
            'previous_reading' => $request->previous_reading,
            'current_reading' => $request->current_reading,
            'consumption' => $request->current_reading - $request->previous_reading,
            'rate_per_unit' => $request->rate_per_unit,
            'base_amount' => ($request->current_reading - $request->previous_reading) * $request->rate_per_unit,
            'tax_amount' => (($request->current_reading - $request->previous_reading) * $request->rate_per_unit) * 0.05,
            'total_amount' => (($request->current_reading - $request->previous_reading) * $request->rate_per_unit) * 1.05,
            'due_date' => $request->due_date,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill updated');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill updated successfully.');
    }

    /**
     * Mark bill as paid.
     */
    public function markAsPaid(UtilityBill $utilityBill): RedirectResponse
    {
        $utilityBill->markAsPaid();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill marked as paid');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill marked as paid.');
    }

    /**
     * Mark bill as overdue.
     */
    public function markAsOverdue(UtilityBill $utilityBill): RedirectResponse
    {
        $utilityBill->markAsOverdue();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill marked as overdue');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill marked as overdue.');
    }

    /**
     * Mark bill as cancelled.
     */
    public function markAsCancelled(UtilityBill $utilityBill): RedirectResponse
    {
        $utilityBill->markAsCancelled();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill marked as cancelled');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill marked as cancelled.');
    }

    /**
     * Remove the specified utility bill.
     */
    public function destroy(UtilityBill $utilityBill): RedirectResponse
    {
        $utilityBill->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityBill)
            ->log('Utility bill deleted');

        return redirect()->route('admin.utility-bills.index')
            ->with('success', 'Utility bill deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Services\LeaseManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lease::class);
        
        $query = Lease::with(['property']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('lease_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('tenant_name', 'like', '%' . $request->search . '%')
                  ->orWhere('tenant_email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $leases = $query->latest()->paginate(15);
        $properties = Property::available()->get();

        return view('admin.leases.index', compact('leases', 'properties'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Lease::class);
        $properties = Property::available()->get();
        return view('admin.leases.create', compact('properties'));
    }

    /**
     * BUSINESS LOGIC FIX: Store lease with comprehensive validation
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lease::class);
        
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'tenant_name' => 'required|string|max:255',
            'tenant_email' => 'required|email|max:255',
            'tenant_phone' => 'required|string|max:20',
            'tenant_address' => 'required|string|max:500',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $leaseService = new LeaseManagementService();
        $result = $leaseService->createLease($validated);
        
        if ($result['success']) {
            $lease = Lease::where('property_id', $validated['property_id'])
                ->where('tenant_email', $validated['tenant_email'])
                ->latest()
                ->first();

            activity()
                ->causedBy(auth()->user())
                ->performedOn($lease)
                ->log('Lease created');

            $message = 'Lease created successfully.';
            if (!empty($result['warnings'])) {
                $message .= ' Warnings: ' . implode(', ', $result['warnings']);
            }

            return redirect()->route('admin.leases.index')
                ->with('success', $message);
        } else {
            return redirect()->back()
                ->with('error', implode(', ', $result['errors']))
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Lease $lease): View
    {
        $this->authorize('view', $lease);
        $lease->load(['property', 'payments']);
        return view('admin.leases.show', compact('lease'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lease $lease): View
    {
        $this->authorize('update', $lease);
        $properties = Property::available()->get();
        return view('admin.leases.edit', compact('lease', 'properties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lease $lease): RedirectResponse
    {
        $this->authorize('update', $lease);
        
        $validated = $request->validate([
            'tenant_name' => 'required|string|max:255',
            'tenant_email' => 'required|email|max:255',
            'tenant_phone' => 'required|string|max:20',
            'tenant_address' => 'required|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'security_deposit' => 'required|numeric|min:0',
            'late_fee' => 'nullable|numeric|min:0',
            'grace_period_days' => 'nullable|integer|min:0|max:30',
            'status' => ['required', Rule::in(['draft', 'active', 'expired', 'terminated', 'cancelled'])],
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $lease->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease updated');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lease $lease): RedirectResponse
    {
        $this->authorize('delete', $lease);
        
        // Check if lease has payments
        if ($lease->payments()->exists()) {
            return redirect()->route('admin.leases.index')
                ->with('error', 'Cannot delete lease with payment records.');
        }

        // BUSINESS LOGIC FIX: Store property reference BEFORE deleting the lease
        $property = $lease->property;
        $propertyId = $property->id;

        $lease->delete();

        // Update property status to available using the stored property reference
        $property->update(['status' => 'available']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease deleted');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease deleted successfully.');
    }

    /**
     * Activate lease
     */
    public function activate(Lease $lease): RedirectResponse
    {
        $this->authorize('update', $lease);
        
        $lease->update(['status' => 'active']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease activated');

        return redirect()->back()
            ->with('success', 'Lease activated successfully.');
    }

    /**
     * BUSINESS LOGIC FIX: Terminate lease with proper validation
     */
    public function terminate(Lease $lease): RedirectResponse
    {
        $this->authorize('terminate', $lease);
        
        $leaseService = new LeaseManagementService();
        $result = $leaseService->terminateLease($lease);
        
        if ($result['success']) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($lease)
                ->log('Lease terminated');

            return redirect()->back()
                ->with('success', 'Lease terminated successfully.');
        } else {
            return redirect()->back()
                ->with('error', implode(', ', $result['errors']));
        }
    }

    /**
     * Cancel lease
     */
    public function cancel(Lease $lease): RedirectResponse
    {
        $this->authorize('update', $lease);
        
        $lease->update(['status' => 'cancelled']);

        // Update property status to available
        $lease->property->update(['status' => 'available']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease cancelled');

        return redirect()->back()
            ->with('success', 'Lease cancelled successfully.');
    }
}
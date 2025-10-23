<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LeaseController extends Controller
{
    /**
     * Display a listing of leases.
     */
    public function index(Request $request): View
    {
        $query = Lease::with(['property', 'tenant']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lease_number', 'like', "%{$search}%")
                  ->orWhereHas('property', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tenant', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leases = $query->latest()->paginate(15);
        $properties = Property::active()->get();
        $tenants = Tenant::active()->get();

        return view('property.leases.index', compact('leases', 'properties', 'tenants'));
    }

    /**
     * Show the form for creating a new lease.
     */
    public function create(Request $request): View
    {
        $properties = Property::available()->get();
        $tenants = Tenant::active()->get();
        
        // Pre-select property or tenant if provided
        $selectedProperty = $request->property_id ? Property::find($request->property_id) : null;
        $selectedTenant = $request->tenant_id ? Tenant::find($request->tenant_id) : null;

        return view('property.leases.create', compact('properties', 'tenants', 'selectedProperty', 'selectedTenant'));
    }

    /**
     * Store a newly created lease.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'late_fee_days' => 'nullable|integer|min:1',
            'rent_due_date' => 'nullable|integer|min:1|max:31',
            'terms_and_conditions' => 'nullable|string',
            'additional_charges' => 'nullable|array',
            'auto_renewal' => 'boolean',
            'renewal_notice_days' => 'nullable|integer|min:1',
        ]);

        // Check if property is available
        $property = Property::find($request->property_id);
        if ($property->status !== 'available') {
            return redirect()->back()
                ->with('error', 'Selected property is not available for lease.');
        }

        // Check if tenant already has an active lease
        $existingLease = Lease::where('tenant_id', $request->tenant_id)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        if ($existingLease) {
            return redirect()->back()
                ->with('error', 'Tenant already has an active lease.');
        }

        // Generate lease number
        $leaseNumber = 'LEASE-' . str_pad(Lease::count() + 1, 6, '0', STR_PAD_LEFT);

        $leaseData = $request->except(['additional_charges']);
        $leaseData['lease_number'] = $leaseNumber;
        $leaseData['additional_charges'] = $request->additional_charges ?? [];
        $leaseData['auto_renewal'] = $request->boolean('auto_renewal', false);

        $lease = Lease::create($leaseData);

        // Update property status to occupied
        $property->update(['status' => 'occupied']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease created');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease created successfully.');
    }

    /**
     * Display the specified lease.
     */
    public function show(Lease $lease): View
    {
        $lease->load(['property', 'tenant', 'payments']);
        return view('property.leases.show', compact('lease'));
    }

    /**
     * Show the form for editing the lease.
     */
    public function edit(Lease $lease): View
    {
        $properties = Property::active()->get();
        $tenants = Tenant::active()->get();
        return view('property.leases.edit', compact('lease', 'properties', 'tenants'));
    }

    /**
     * Update the specified lease.
     */
    public function update(Request $request, Lease $lease): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'monthly_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'late_fee_amount' => 'nullable|numeric|min:0',
            'late_fee_days' => 'nullable|integer|min:1',
            'rent_due_date' => 'nullable|integer|min:1|max:31',
            'terms_and_conditions' => 'nullable|string',
            'additional_charges' => 'nullable|array',
            'auto_renewal' => 'boolean',
            'renewal_notice_days' => 'nullable|integer|min:1',
        ]);

        $leaseData = $request->except(['additional_charges']);
        $leaseData['additional_charges'] = $request->additional_charges ?? [];
        $leaseData['auto_renewal'] = $request->boolean('auto_renewal', false);

        $lease->update($leaseData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease updated');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease updated successfully.');
    }

    /**
     * Terminate the specified lease.
     */
    public function terminate(Request $request, Lease $lease): RedirectResponse
    {
        $request->validate([
            'termination_date' => 'required|date|after_or_equal:today',
            'termination_reason' => 'required|string|max:500',
        ]);

        $lease->update([
            'status' => 'terminated',
            'termination_date' => $request->termination_date,
            'termination_reason' => $request->termination_reason,
        ]);

        // Update property status to available
        $lease->property->update(['status' => 'available']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($lease)
            ->log('Lease terminated');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease terminated successfully.');
    }

    /**
     * Renew the specified lease.
     */
    public function renew(Request $request, Lease $lease): RedirectResponse
    {
        $request->validate([
            'new_end_date' => 'required|date|after:today',
            'new_monthly_rent' => 'nullable|numeric|min:0',
        ]);

        // Create new lease
        $newLeaseNumber = 'LEASE-' . str_pad(Lease::count() + 1, 6, '0', STR_PAD_LEFT);
        
        $newLease = Lease::create([
            'lease_number' => $newLeaseNumber,
            'property_id' => $lease->property_id,
            'tenant_id' => $lease->tenant_id,
            'start_date' => $lease->end_date->addDay(),
            'end_date' => $request->new_end_date,
            'monthly_rent' => $request->new_monthly_rent ?? $lease->monthly_rent,
            'deposit_amount' => $lease->deposit_amount,
            'late_fee_amount' => $lease->late_fee_amount,
            'late_fee_days' => $lease->late_fee_days,
            'rent_due_date' => $lease->rent_due_date,
            'terms_and_conditions' => $lease->terms_and_conditions,
            'additional_charges' => $lease->additional_charges,
            'auto_renewal' => $lease->auto_renewal,
            'renewal_notice_days' => $lease->renewal_notice_days,
        ]);

        // Mark old lease as renewed
        $lease->update(['status' => 'renewed']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($newLease)
            ->log('Lease renewed');

        return redirect()->route('admin.leases.index')
            ->with('success', 'Lease renewed successfully.');
    }

    /**
     * Remove the specified lease.
     */
    public function destroy(Lease $lease): RedirectResponse
    {
        // Check if lease has payments
        if ($lease->payments()->exists()) {
            return redirect()->route('admin.leases.index')
                ->with('error', 'Cannot delete lease with payment records.');
        }

        // Store property reference BEFORE deleting the lease
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
}

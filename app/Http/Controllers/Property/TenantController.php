<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): View
    {
        $query = Tenant::with('currentLease.property');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->withActiveLeases();
            } elseif ($request->status === 'inactive') {
                $query->whereDoesntHave('leases', function ($q) {
                    $q->where('status', 'active')
                      ->where('start_date', '<=', now())
                      ->where('end_date', '>=', now());
                });
            }
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $tenants = $query->latest()->paginate(15);

        return view('property.tenants.index', compact('tenants'));
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create(): View
    {
        return view('property.tenants.create');
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants',
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
        ]);

        $tenantData = $request->except(['documents']);
        $tenantData['is_active'] = $request->boolean('is_active', true);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            $documentPaths = [];
            foreach ($request->file('documents') as $document) {
                $path = $document->store('tenant-documents', 'public');
                $documentPaths[] = [
                    'name' => $document->getClientOriginalName(),
                    'path' => $path,
                    'uploaded_at' => now()->toISOString(),
                ];
            }
            $tenantData['documents'] = $documentPaths;
        }

        $tenant = Tenant::create($tenantData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log('Tenant created');

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): View
    {
        $tenant->load(['leases.property', 'leases.payments']);
        return view('property.tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing the tenant.
     */
    public function edit(Tenant $tenant): View
    {
        return view('property.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tenants,email,' . $tenant->id,
            'phone' => 'required|string|max:20',
            'alternate_phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
        ]);

        $tenantData = $request->except(['documents']);
        $tenantData['is_active'] = $request->boolean('is_active', true);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            $existingDocuments = $tenant->documents ?? [];
            $newDocuments = [];
            foreach ($request->file('documents') as $document) {
                $path = $document->store('tenant-documents', 'public');
                $newDocuments[] = [
                    'name' => $document->getClientOriginalName(),
                    'path' => $path,
                    'uploaded_at' => now()->toISOString(),
                ];
            }
            $tenantData['documents'] = array_merge($existingDocuments, $newDocuments);
        }

        $tenant->update($tenantData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log('Tenant updated');

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Check if tenant has active leases
        if ($tenant->hasActiveLease()) {
            return redirect()->route('admin.tenants.index')
                ->with('error', 'Cannot delete tenant with active leases.');
        }

        $tenant->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log('Tenant deleted');

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    /**
     * Toggle tenant active status.
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => !$tenant->is_active]);

        $status = $tenant->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($tenant)
            ->log("Tenant {$status}");

        return redirect()->route('admin.tenants.index')
            ->with('success', "Tenant {$status} successfully.");
    }
}

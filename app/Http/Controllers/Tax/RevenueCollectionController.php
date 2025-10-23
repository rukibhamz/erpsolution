<?php

namespace App\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use App\Models\RevenueCollection;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RevenueCollectionController extends Controller
{
    /**
     * Display a listing of revenue collections.
     */
    public function index(Request $request): View
    {
        $query = RevenueCollection::with(['property', 'collectedBy', 'verifiedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('collection_reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('property', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by collection type
        if ($request->filled('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by verification status
        if ($request->filled('verified')) {
            if ($request->boolean('verified')) {
                $query->verified();
            } else {
                $query->unverified();
            }
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('collection_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('collection_date', '<=', $request->end_date);
        }

        $revenueCollections = $query->latest('collection_date')->paginate(15);
        $properties = Property::active()->get();

        return view('tax.revenue-collections.index', compact('revenueCollections', 'properties'));
    }

    /**
     * Show the form for creating a new revenue collection.
     */
    public function create(Request $request): View
    {
        $properties = Property::active()->get();
        $selectedProperty = $request->property_id ? Property::find($request->property_id) : null;
        
        return view('tax.revenue-collections.create', compact('properties', 'selectedProperty'));
    }

    /**
     * Store a newly created revenue collection.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'collection_type' => 'required|in:rent,service_charge,maintenance_fee,penalty,other',
            'amount' => 'required|numeric|min:0',
            'collection_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:collection_date',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate collection reference
        $collectionReference = 'RC-' . str_pad(RevenueCollection::count() + 1, 6, '0', STR_PAD_LEFT);

        $revenueCollection = RevenueCollection::create([
            'collection_reference' => $collectionReference,
            'property_id' => $request->property_id,
            'collection_type' => $request->collection_type,
            'amount' => $request->amount,
            'collection_date' => $request->collection_date,
            'due_date' => $request->due_date,
            'payment_status' => 'pending',
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'description' => $request->description,
            'notes' => $request->notes,
            'collected_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection created');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection created successfully.');
    }

    /**
     * Display the specified revenue collection.
     */
    public function show(RevenueCollection $revenueCollection): View
    {
        $revenueCollection->load(['property', 'collectedBy', 'verifiedBy', 'revenueCollectionItems']);
        return view('tax.revenue-collections.show', compact('revenueCollection'));
    }

    /**
     * Show the form for editing the revenue collection.
     */
    public function edit(RevenueCollection $revenueCollection): View
    {
        $properties = Property::active()->get();
        return view('tax.revenue-collections.edit', compact('revenueCollection', 'properties'));
    }

    /**
     * Update the specified revenue collection.
     */
    public function update(Request $request, RevenueCollection $revenueCollection): RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'collection_type' => 'required|in:rent,service_charge,maintenance_fee,penalty,other',
            'amount' => 'required|numeric|min:0',
            'collection_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:collection_date',
            'payment_method' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $revenueCollection->update([
            'property_id' => $request->property_id,
            'collection_type' => $request->collection_type,
            'amount' => $request->amount,
            'collection_date' => $request->collection_date,
            'due_date' => $request->due_date,
            'payment_method' => $request->payment_method,
            'reference_number' => $request->reference_number,
            'description' => $request->description,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection updated');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection updated successfully.');
    }

    /**
     * Mark collection as paid.
     */
    public function markAsPaid(RevenueCollection $revenueCollection): RedirectResponse
    {
        $revenueCollection->markAsPaid();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection marked as paid');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection marked as paid.');
    }

    /**
     * Mark collection as overdue.
     */
    public function markAsOverdue(RevenueCollection $revenueCollection): RedirectResponse
    {
        $revenueCollection->markAsOverdue();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection marked as overdue');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection marked as overdue.');
    }

    /**
     * Mark collection as cancelled.
     */
    public function markAsCancelled(RevenueCollection $revenueCollection): RedirectResponse
    {
        $revenueCollection->markAsCancelled();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection marked as cancelled');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection marked as cancelled.');
    }

    /**
     * Verify collection.
     */
    public function verify(RevenueCollection $revenueCollection): RedirectResponse
    {
        $revenueCollection->verify(auth()->user());

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection verified');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection verified successfully.');
    }

    /**
     * Remove the specified revenue collection.
     */
    public function destroy(RevenueCollection $revenueCollection): RedirectResponse
    {
        $revenueCollection->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($revenueCollection)
            ->log('Revenue collection deleted');

        return redirect()->route('admin.revenue-collections.index')
            ->with('success', 'Revenue collection deleted successfully.');
    }
}

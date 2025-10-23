<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Repair;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RepairController extends Controller
{
    /**
     * Display a listing of repairs.
     */
    public function index(Request $request): View
    {
        $query = Repair::with(['item', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('repair_reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('technician', 'like', "%{$search}%");
            });
        }

        // Filter by repair status
        if ($request->filled('repair_status')) {
            $query->where('repair_status', $request->repair_status);
        }

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('repair_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('repair_date', '<=', $request->end_date);
        }

        $repairs = $query->latest('repair_date')->paginate(15);
        $inventoryItems = InventoryItem::active()->get();

        return view('inventory.repairs.index', compact('repairs', 'inventoryItems'));
    }

    /**
     * Show the form for creating a new repair.
     */
    public function create(Request $request): View
    {
        $inventoryItems = InventoryItem::active()->get();
        $selectedItem = $request->item_id ? InventoryItem::find($request->item_id) : null;
        
        return view('inventory.repairs.create', compact('inventoryItems', 'selectedItem'));
    }

    /**
     * Store a newly created repair.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'description' => 'required|string|max:1000',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'repair_status' => 'required|in:pending,in_progress,completed,cancelled',
            'technician' => 'nullable|string|max:255',
            'technician_contact' => 'nullable|string|max:255',
            'warranty_period' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Generate repair reference
        $repairReference = 'REP-' . str_pad(Repair::count() + 1, 6, '0', STR_PAD_LEFT);

        $repair = Repair::create([
            'item_id' => $request->item_id,
            'repair_reference' => $repairReference,
            'description' => $request->description,
            'repair_date' => $request->repair_date,
            'cost' => $request->cost,
            'repair_status' => $request->repair_status,
            'technician' => $request->technician,
            'technician_contact' => $request->technician_contact,
            'warranty_period' => $request->warranty_period,
            'warranty_expiry' => $request->warranty_period ? now()->addDays($request->warranty_period) : null,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair created');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair created successfully.');
    }

    /**
     * Display the specified repair.
     */
    public function show(Repair $repair): View
    {
        $repair->load(['item', 'createdBy']);
        return view('inventory.repairs.show', compact('repair'));
    }

    /**
     * Show the form for editing the repair.
     */
    public function edit(Repair $repair): View
    {
        $inventoryItems = InventoryItem::active()->get();
        return view('inventory.repairs.edit', compact('repair', 'inventoryItems'));
    }

    /**
     * Update the specified repair.
     */
    public function update(Request $request, Repair $repair): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'description' => 'required|string|max:1000',
            'repair_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'repair_status' => 'required|in:pending,in_progress,completed,cancelled',
            'technician' => 'nullable|string|max:255',
            'technician_contact' => 'nullable|string|max:255',
            'completion_date' => 'nullable|date|after_or_equal:repair_date',
            'warranty_period' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $repair->update([
            'item_id' => $request->item_id,
            'description' => $request->description,
            'repair_date' => $request->repair_date,
            'cost' => $request->cost,
            'repair_status' => $request->repair_status,
            'technician' => $request->technician,
            'technician_contact' => $request->technician_contact,
            'completion_date' => $request->completion_date,
            'warranty_period' => $request->warranty_period,
            'warranty_expiry' => $request->warranty_period ? $request->completion_date?->addDays($request->warranty_period) : null,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair updated');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair updated successfully.');
    }

    /**
     * Mark repair as in progress.
     */
    public function markInProgress(Repair $repair): RedirectResponse
    {
        $repair->markInProgress();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair marked as in progress');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair marked as in progress.');
    }

    /**
     * Mark repair as completed.
     */
    public function markCompleted(Repair $repair): RedirectResponse
    {
        $repair->markCompleted();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair marked as completed');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair marked as completed.');
    }

    /**
     * Mark repair as cancelled.
     */
    public function markCancelled(Repair $repair): RedirectResponse
    {
        $repair->markCancelled();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair marked as cancelled');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair marked as cancelled.');
    }

    /**
     * Remove the specified repair.
     */
    public function destroy(Repair $repair): RedirectResponse
    {
        $repair->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($repair)
            ->log('Repair deleted');

        return redirect()->route('admin.repairs.index')
            ->with('success', 'Repair deleted successfully.');
    }
}

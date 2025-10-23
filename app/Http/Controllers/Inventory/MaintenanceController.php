<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of maintenance logs.
     */
    public function index(Request $request): View
    {
        $query = MaintenanceLog::with(['item', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('technician', 'like', "%{$search}%");
            });
        }

        // Filter by maintenance type
        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        // Filter by item
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('maintenance_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('maintenance_date', '<=', $request->end_date);
        }

        // Filter by maintenance status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'overdue':
                    $query->overdue();
                    break;
                case 'due_soon':
                    $query->dueSoon();
                    break;
            }
        }

        $maintenanceLogs = $query->latest('maintenance_date')->paginate(15);
        $inventoryItems = InventoryItem::active()->get();

        return view('inventory.maintenance.index', compact('maintenanceLogs', 'inventoryItems'));
    }

    /**
     * Show the form for creating a new maintenance log.
     */
    public function create(Request $request): View
    {
        $inventoryItems = InventoryItem::active()->get();
        $selectedItem = $request->item_id ? InventoryItem::find($request->item_id) : null;
        
        return view('inventory.maintenance.create', compact('inventoryItems', 'selectedItem'));
    }

    /**
     * Store a newly created maintenance log.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'maintenance_type' => 'required|in:preventive,corrective,predictive,emergency',
            'description' => 'required|string|max:1000',
            'maintenance_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'technician' => 'nullable|string|max:255',
            'technician_contact' => 'nullable|string|max:255',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $maintenanceLog = MaintenanceLog::create([
            'item_id' => $request->item_id,
            'maintenance_type' => $request->maintenance_type,
            'description' => $request->description,
            'maintenance_date' => $request->maintenance_date,
            'cost' => $request->cost,
            'technician' => $request->technician,
            'technician_contact' => $request->technician_contact,
            'next_maintenance_date' => $request->next_maintenance_date,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($maintenanceLog)
            ->log('Maintenance log created');

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance log created successfully.');
    }

    /**
     * Display the specified maintenance log.
     */
    public function show(MaintenanceLog $maintenanceLog): View
    {
        $maintenanceLog->load(['item', 'createdBy']);
        return view('inventory.maintenance.show', compact('maintenanceLog'));
    }

    /**
     * Show the form for editing the maintenance log.
     */
    public function edit(MaintenanceLog $maintenanceLog): View
    {
        $inventoryItems = InventoryItem::active()->get();
        return view('inventory.maintenance.edit', compact('maintenanceLog', 'inventoryItems'));
    }

    /**
     * Update the specified maintenance log.
     */
    public function update(Request $request, MaintenanceLog $maintenanceLog): RedirectResponse
    {
        $request->validate([
            'item_id' => 'required|exists:inventory_items,id',
            'maintenance_type' => 'required|in:preventive,corrective,predictive,emergency',
            'description' => 'required|string|max:1000',
            'maintenance_date' => 'required|date',
            'cost' => 'required|numeric|min:0',
            'technician' => 'nullable|string|max:255',
            'technician_contact' => 'nullable|string|max:255',
            'next_maintenance_date' => 'nullable|date|after:maintenance_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $maintenanceLog->update([
            'item_id' => $request->item_id,
            'maintenance_type' => $request->maintenance_type,
            'description' => $request->description,
            'maintenance_date' => $request->maintenance_date,
            'cost' => $request->cost,
            'technician' => $request->technician,
            'technician_contact' => $request->technician_contact,
            'next_maintenance_date' => $request->next_maintenance_date,
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($maintenanceLog)
            ->log('Maintenance log updated');

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance log updated successfully.');
    }

    /**
     * Remove the specified maintenance log.
     */
    public function destroy(MaintenanceLog $maintenanceLog): RedirectResponse
    {
        $maintenanceLog->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($maintenanceLog)
            ->log('Maintenance log deleted');

        return redirect()->route('admin.maintenance.index')
            ->with('success', 'Maintenance log deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request): View
    {
        $query = InventoryItem::with('category');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by stock level
        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'low_stock':
                    $query->where('current_stock', '<=', DB::raw('minimum_stock'));
                    break;
                case 'out_of_stock':
                    $query->where('current_stock', 0);
                    break;
                case 'needs_reorder':
                    $query->where('current_stock', '<=', DB::raw('reorder_level'));
                    break;
            }
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $inventoryItems = $query->latest()->paginate(15);
        $categories = InventoryCategory::active()->get();

        return view('inventory.items.index', compact('inventoryItems', 'categories'));
    }

    /**
     * Show the form for creating a new inventory item.
     */
    public function create(): View
    {
        $categories = InventoryCategory::active()->get();
        return view('inventory.items.create', compact('categories'));
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventory_items',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,discontinued',
            'is_active' => 'boolean',
        ]);

        $inventoryData = $request->except(['is_active']);
        $inventoryData['is_active'] = $request->boolean('is_active', true);

        $inventoryItem = InventoryItem::create($inventoryData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Inventory item created');

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display the specified inventory item.
     */
    public function show(InventoryItem $inventoryItem): View
    {
        $inventoryItem->load(['category', 'stockMovements']);
        return view('inventory.items.show', compact('inventoryItem'));
    }

    /**
     * Show the form for editing the inventory item.
     */
    public function edit(InventoryItem $inventoryItem): View
    {
        $categories = InventoryCategory::active()->get();
        return view('inventory.items.edit', compact('inventoryItem', 'categories'));
    }

    /**
     * Update the specified inventory item.
     */
    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventory_items,sku,' . $inventoryItem->id,
            'description' => 'nullable|string',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,discontinued',
            'is_active' => 'boolean',
        ]);

        $inventoryData = $request->except(['is_active']);
        $inventoryData['is_active'] = $request->boolean('is_active', true);

        $inventoryItem->update($inventoryData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Inventory item updated');

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        // Check if item has stock movements
        if ($inventoryItem->stockMovements()->exists()) {
            return redirect()->route('admin.inventory.index')
                ->with('error', 'Cannot delete inventory item with stock movement records.');
        }

        $inventoryItem->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Inventory item deleted');

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item deleted successfully.');
    }

    /**
     * Toggle inventory item active status.
     */
    public function toggleStatus(InventoryItem $inventoryItem): RedirectResponse
    {
        $inventoryItem->update(['is_active' => !$inventoryItem->is_active]);

        $status = $inventoryItem->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log("Inventory item {$status}");

        return redirect()->route('admin.inventory.index')
            ->with('success', "Inventory item {$status} successfully.");
    }

    /**
     * Add stock to inventory item.
     */
    public function addStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $inventoryItem->addStock($request->quantity, $request->reason, $request->notes);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log("Added {$request->quantity} units to inventory");

        return redirect()->route('admin.inventory.show', $inventoryItem)
            ->with('success', 'Stock added successfully.');
    }

    /**
     * Remove stock from inventory item.
     */
    public function removeStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $inventoryItem->current_stock,
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $inventoryItem->removeStock($request->quantity, $request->reason, $request->notes);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log("Removed {$request->quantity} units from inventory");

        return redirect()->route('admin.inventory.show', $inventoryItem)
            ->with('success', 'Stock removed successfully.');
    }
}
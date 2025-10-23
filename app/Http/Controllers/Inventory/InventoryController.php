<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
                $q->where('item_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
                    $query->where('current_stock', '<=', DB::raw('reorder_point'));
                    break;
            }
        }

        $inventoryItems = $query->orderBy('name')->paginate(15);
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
            'item_code' => 'required|string|max:50|unique:inventory_items,item_code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_of_measure' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'current_stock' => 'required|integer|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $inventoryItem = InventoryItem::create([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'unit_of_measure' => $request->unit_of_measure,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'current_stock' => $request->current_stock,
            'minimum_stock' => $request->minimum_stock,
            'maximum_stock' => $request->maximum_stock,
            'reorder_point' => $request->reorder_point,
            'supplier' => $request->supplier,
            'location' => $request->location,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Update status based on stock level
        $inventoryItem->updateStatus();

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
        $inventoryItem->load(['category', 'stockMovements', 'repairs', 'maintenanceLogs']);
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
            'item_code' => 'required|string|max:50|unique:inventory_items,item_code,' . $inventoryItem->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_of_measure' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'minimum_stock' => 'required|integer|min:0',
            'maximum_stock' => 'required|integer|min:0',
            'reorder_point' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $inventoryItem->update([
            'item_code' => $request->item_code,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'unit_of_measure' => $request->unit_of_measure,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'minimum_stock' => $request->minimum_stock,
            'maximum_stock' => $request->maximum_stock,
            'reorder_point' => $request->reorder_point,
            'supplier' => $request->supplier,
            'location' => $request->location,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Update status based on stock level
        $inventoryItem->updateStatus();

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
                ->with('error', 'Cannot delete item with existing stock movements.');
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
     * Toggle item status.
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
     * Add stock to the item.
     */
    public function addStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $inventoryItem->addStock($request->quantity, $request->reason);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Stock added to inventory item');

        return redirect()->route('admin.inventory.show', $inventoryItem)
            ->with('success', 'Stock added successfully.');
    }

    /**
     * Remove stock from the item.
     */
    public function removeStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $inventoryItem->removeStock($request->quantity, $request->reason);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($inventoryItem)
                ->log('Stock removed from inventory item');

            return redirect()->route('admin.inventory.show', $inventoryItem)
                ->with('success', 'Stock removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.inventory.show', $inventoryItem)
                ->with('error', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);
        
        $query = InventoryItem::with(['category']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('item_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('item_code', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('filter')) {
            switch ($request->input('filter')) {
                case 'low_stock':
                    $query->whereRaw('current_stock <= reorder_level');
                    break;
                case 'out_of_stock':
                    $query->where('current_stock', 0);
                    break;
                case 'needs_reorder':
                    $query->whereRaw('current_stock <= reorder_level AND current_stock > 0');
                    break;
            }
        }

        $inventoryItems = $query->latest()->paginate(15);
        $categories = InventoryCategory::active()->get();

        return view('admin.inventory.index', compact('inventoryItems', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', InventoryItem::class);
        $categories = InventoryCategory::active()->get();
        return view('admin.inventory.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', InventoryItem::class);
        
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'initial_stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // RACE CONDITION FIX: Generate unique item code using database transaction
        $inventoryItem = DB::transaction(function () use ($validated) {
            // Get the next sequence number atomically
            $nextNumber = DB::table('inventory_items')
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(item_code, 4) AS UNSIGNED)')) + 1;

            $itemCode = 'INV' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            return InventoryItem::create([
                ...$validated,
                'item_code' => $itemCode,
                'current_stock' => $validated['initial_stock'],
                'status' => 'active',
            ]);
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Inventory item created');

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);
        $inventoryItem->load(['category']);
        return view('admin.inventory.show', compact('inventoryItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);
        $categories = InventoryCategory::active()->get();
        return view('admin.inventory.edit', compact('inventoryItem', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:inventory_categories,id',
            'unit_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'supplier_contact' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => ['required', Rule::in(['active', 'inactive', 'discontinued'])],
            'notes' => 'nullable|string|max:1000',
        ]);

        $inventoryItem->update($validated);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log('Inventory item updated');

        return redirect()->route('admin.inventory.index')
            ->with('success', 'Inventory item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('delete', $inventoryItem);
        
        // Check if item has stock
        if ($inventoryItem->getAttribute('current_stock') > 0) {
            return redirect()->route('admin.inventory.index')
                ->with('error', 'Cannot delete inventory item with existing stock.');
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
     * Adjust stock
     */
    public function adjustStock(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('manage-stock', $inventoryItem);
        
        $validated = $request->validate([
            'adjustment_type' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $quantity = $validated['quantity'];
        if ($validated['adjustment_type'] === 'remove') {
            $quantity = -$quantity;
        }

        // Check if removal would result in negative stock
        if ($validated['adjustment_type'] === 'remove' && 
            ($inventoryItem->getAttribute('current_stock') - $quantity) < 0) {
            return redirect()->back()
                ->with('error', 'Insufficient stock for this adjustment.');
        }

        $inventoryItem->increment('current_stock', $quantity);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log("Stock {$validated['adjustment_type']}ed by {$validated['quantity']} units. Reason: {$validated['reason']}");

        return redirect()->back()
            ->with('success', 'Stock adjusted successfully.');
    }

    /**
     * Toggle status
     */
    public function toggleStatus(InventoryItem $inventoryItem): RedirectResponse
    {
        $this->authorize('update', $inventoryItem);
        
        $newStatus = $inventoryItem->getAttribute('status') === 'active' ? 'inactive' : 'active';
        $inventoryItem->update(['status' => $newStatus]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($inventoryItem)
            ->log("Inventory item status changed to {$newStatus}");

        return redirect()->back()
            ->with('success', "Inventory item {$newStatus} successfully.");
    }
}
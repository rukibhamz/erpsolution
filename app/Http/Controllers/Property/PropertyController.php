<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyType;
use App\Services\PropertyStatusService;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Property::class);
        $query = Property::with(['propertyType', 'leases']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('property_code', 'like', '%' . $request->search . '%')
                  ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_type_id')) {
            $query->where('property_type_id', $request->property_type_id);
        }

        if ($request->filled('min_rent')) {
            $query->where('rent_amount', '>=', $request->min_rent);
        }

        if ($request->filled('max_rent')) {
            $query->where('rent_amount', '<=', $request->max_rent);
        }

        $properties = $query->paginate(15);
        $propertyTypes = PropertyType::active()->get();

        return view('admin.properties.index', compact('properties', 'propertyTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Property::class);
        $propertyTypes = PropertyType::active()->get();
        return view('admin.properties.create', compact('propertyTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate unique property code
        $propertyCode = $this->generatePropertyCode();

        // Handle image uploads
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $images[] = $path;
            }
        }

        $property = Property::create([
            ...$validated,
            'property_code' => $propertyCode,
            'images' => $images,
            'status' => 'available',
            'is_active' => true,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property created');

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Property $property): View
    {
        $property->load(['propertyType', 'leases.tenant']);
        return view('admin.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Property $property): View
    {
        $propertyTypes = PropertyType::active()->get();
        return view('admin.properties.edit', compact('property', 'propertyTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $validated = $request->validated();

        // Handle new image uploads
        $currentImages = $property->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $currentImages[] = $path;
            }
        }

        $property->update([
            ...$validated,
            'images' => $currentImages,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property updated');

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property): RedirectResponse
    {
        // Check if property has active leases
        if ($property->leases()->where('status', 'active')->exists()) {
            return redirect()->route('admin.properties.index')
                ->with('error', 'Cannot delete property with active leases.');
        }

        // Delete images from storage
        if ($property->images) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $property->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property deleted');

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property deleted successfully.');
    }

    /**
     * SECURITY FIX: Remove image with proper validation
     */
    public function removeImage(Property $property, Request $request): RedirectResponse
    {
        $request->validate([
            'image_index' => 'required|integer|min:0'
        ]);

        $imageIndex = $request->image_index;
        $images = $property->images ?? [];

        // SECURITY FIX: Validate image index exists
        if (!isset($images[$imageIndex])) {
            return redirect()->back()
                ->with('error', 'Image not found. It may have already been removed.');
        }

        // Delete file from storage
        Storage::disk('public')->delete($images[$imageIndex]);

        // Remove from array
        unset($images[$imageIndex]);
        $images = array_values($images); // Re-index array

        $property->update(['images' => $images]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property image removed');

        return redirect()->back()
            ->with('success', 'Image removed successfully.');
    }

    /**
     * BUSINESS LOGIC FIX: Toggle property status with proper validation
     */
    public function toggleStatus(Property $property): RedirectResponse
    {
        $propertyStatusService = new PropertyStatusService();
        
        $newStatus = $property->status === 'available' ? 'unavailable' : 'available';
        
        // Validate status change
        $errors = $propertyStatusService->validateStatusChange($property, $newStatus);
        
        if (!empty($errors)) {
            return redirect()->back()
                ->with('error', implode(' ', $errors));
        }
        
        $property->update(['status' => $newStatus]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log("Property status changed to {$newStatus}");

        return redirect()->back()
            ->with('success', "Property status updated to {$newStatus}.");
    }

    /**
     * BUSINESS LOGIC FIX: Fix property status inconsistencies
     */
    public function fixStatusInconsistencies(): RedirectResponse
    {
        $propertyStatusService = new PropertyStatusService();
        $fixed = $propertyStatusService->fixStatusInconsistencies();

        return redirect()->back()
            ->with('success', "Fixed status inconsistencies for " . count($fixed) . " properties.");
    }

    /**
     * Generate unique property code
     * RACE CONDITION FIX: Use database transaction
     */
    private function generatePropertyCode(): string
    {
        return DB::transaction(function () {
            $lastProperty = Property::orderBy('id', 'desc')->first();
            $nextNumber = $lastProperty ? (int) substr($lastProperty->property_code, 3) + 1 : 1;
            
            $propertyCode = 'PRP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            
            // Ensure uniqueness
            while (Property::where('property_code', $propertyCode)->exists()) {
                $nextNumber++;
                $propertyCode = 'PRP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
            
            return $propertyCode;
        });
    }
}
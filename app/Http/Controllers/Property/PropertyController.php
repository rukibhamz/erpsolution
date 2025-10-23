<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index(Request $request): View
    {
        $query = Property::with('propertyType');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('property_code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by property type
        if ($request->filled('property_type_id')) {
            $query->where('property_type_id', $request->property_type_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by city
        if ($request->filled('city')) {
            $query->where('city', 'like', "%{$request->city}%");
        }

        $properties = $query->latest()->paginate(15);
        $propertyTypes = PropertyType::active()->get();

        return view('property.index', compact('properties', 'propertyTypes'));
    }

    /**
     * Show the form for creating a new property.
     */
    public function create(): View
    {
        $propertyTypes = PropertyType::active()->get();
        return view('property.create', compact('propertyTypes'));
    }

    /**
     * Store a newly created property.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'property_code' => 'required|string|max:50|unique:properties',
            'description' => 'nullable|string',
            'property_type_id' => 'required|exists:property_types,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'rent_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area_sqft' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,occupied,maintenance,unavailable',
            'is_active' => 'boolean',
        ]);

        $propertyData = $request->except(['images']);
        $propertyData['amenities'] = $request->amenities ?? [];
        $propertyData['is_active'] = $request->boolean('is_active', true);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $imagePaths[] = $path;
            }
            $propertyData['images'] = $imagePaths;
        }

        $property = Property::create($propertyData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property created');

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property created successfully.');
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property): View
    {
        $property->load(['propertyType', 'leases.tenant']);
        return view('property.show', compact('property'));
    }

    /**
     * Show the form for editing the property.
     */
    public function edit(Property $property): View
    {
        $propertyTypes = PropertyType::active()->get();
        return view('property.edit', compact('property', 'propertyTypes'));
    }

    /**
     * Update the specified property.
     */
    public function update(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'property_code' => 'required|string|max:50|unique:properties,property_code,' . $property->id,
            'description' => 'nullable|string',
            'property_type_id' => 'required|exists:property_types,id',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'rent_amount' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area_sqft' => 'nullable|numeric|min:0',
            'amenities' => 'nullable|array',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,occupied,maintenance,unavailable',
            'is_active' => 'boolean',
        ]);

        $propertyData = $request->except(['images']);
        $propertyData['amenities'] = $request->amenities ?? [];
        $propertyData['is_active'] = $request->boolean('is_active', true);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = $property->images ?? [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $imagePaths[] = $path;
            }
            $propertyData['images'] = $imagePaths;
        }

        $property->update($propertyData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log('Property updated');

        return redirect()->route('admin.properties.index')
            ->with('success', 'Property updated successfully.');
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property): RedirectResponse
    {
        // Check if property has active leases
        if ($property->leases()->where('status', 'active')->exists()) {
            return redirect()->route('admin.properties.index')
                ->with('error', 'Cannot delete property with active leases.');
        }

        // Delete associated images
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
     * Toggle property active status.
     */
    public function toggleStatus(Property $property): RedirectResponse
    {
        $property->update(['is_active' => !$property->is_active]);

        $status = $property->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($property)
            ->log("Property {$status}");

        return redirect()->route('admin.properties.index')
            ->with('success', "Property {$status} successfully.");
    }

    /**
     * Remove image from property.
     */
    public function removeImage(Property $property, Request $request): RedirectResponse
    {
        $imageIndex = $request->image_index;
        $images = $property->images ?? [];

        if (isset($images[$imageIndex])) {
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
        }

        return redirect()->back()
            ->with('success', 'Image removed successfully.');
    }
}

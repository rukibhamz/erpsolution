<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\UtilityMeter;
use App\Models\UtilityType;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UtilityMeterController extends Controller
{
    /**
     * Display a listing of utility meters.
     */
    public function index(Request $request): View
    {
        $query = UtilityMeter::with(['utilityType', 'property']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('meter_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('property', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by utility type
        if ($request->filled('utility_type_id')) {
            $query->where('utility_type_id', $request->utility_type_id);
        }

        // Filter by property
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by needs reading
        if ($request->filled('needs_reading')) {
            $query->needsReading();
        }

        $utilityMeters = $query->orderBy('meter_number')->paginate(15);
        $utilityTypes = UtilityType::active()->get();
        $properties = Property::active()->get();

        return view('utilities.meters.index', compact('utilityMeters', 'utilityTypes', 'properties'));
    }

    /**
     * Show the form for creating a new utility meter.
     */
    public function create(): View
    {
        $utilityTypes = UtilityType::active()->get();
        $properties = Property::active()->get();
        return view('utilities.meters.create', compact('utilityTypes', 'properties'));
    }

    /**
     * Store a newly created utility meter.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'meter_number' => 'required|string|max:100|unique:utility_meters,meter_number',
            'utility_type_id' => 'required|exists:utility_types,id',
            'property_id' => 'required|exists:properties,id',
            'location' => 'required|string|max:255',
            'installation_date' => 'required|date',
            'last_reading' => 'nullable|numeric|min:0',
            'last_reading_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,faulty',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $utilityMeter = UtilityMeter::create([
            'meter_number' => $request->meter_number,
            'utility_type_id' => $request->utility_type_id,
            'property_id' => $request->property_id,
            'location' => $request->location,
            'installation_date' => $request->installation_date,
            'last_reading' => $request->last_reading,
            'last_reading_date' => $request->last_reading_date,
            'status' => $request->status,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityMeter)
            ->log('Utility meter created');

        return redirect()->route('admin.utility-meters.index')
            ->with('success', 'Utility meter created successfully.');
    }

    /**
     * Display the specified utility meter.
     */
    public function show(UtilityMeter $utilityMeter): View
    {
        $utilityMeter->load(['utilityType', 'property', 'utilityReadings']);
        return view('utilities.meters.show', compact('utilityMeter'));
    }

    /**
     * Show the form for editing the utility meter.
     */
    public function edit(UtilityMeter $utilityMeter): View
    {
        $utilityTypes = UtilityType::active()->get();
        $properties = Property::active()->get();
        return view('utilities.meters.edit', compact('utilityMeter', 'utilityTypes', 'properties'));
    }

    /**
     * Update the specified utility meter.
     */
    public function update(Request $request, UtilityMeter $utilityMeter): RedirectResponse
    {
        $request->validate([
            'meter_number' => 'required|string|max:100|unique:utility_meters,meter_number,' . $utilityMeter->id,
            'utility_type_id' => 'required|exists:utility_types,id',
            'property_id' => 'required|exists:properties,id',
            'location' => 'required|string|max:255',
            'installation_date' => 'required|date',
            'last_reading' => 'nullable|numeric|min:0',
            'last_reading_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,maintenance,faulty',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $utilityMeter->update([
            'meter_number' => $request->meter_number,
            'utility_type_id' => $request->utility_type_id,
            'property_id' => $request->property_id,
            'location' => $request->location,
            'installation_date' => $request->installation_date,
            'last_reading' => $request->last_reading,
            'last_reading_date' => $request->last_reading_date,
            'status' => $request->status,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->notes,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityMeter)
            ->log('Utility meter updated');

        return redirect()->route('admin.utility-meters.index')
            ->with('success', 'Utility meter updated successfully.');
    }

    /**
     * Remove the specified utility meter.
     */
    public function destroy(UtilityMeter $utilityMeter): RedirectResponse
    {
        // Check if meter has readings
        if ($utilityMeter->utilityReadings()->exists()) {
            return redirect()->route('admin.utility-meters.index')
                ->with('error', 'Cannot delete meter with existing readings.');
        }

        $utilityMeter->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityMeter)
            ->log('Utility meter deleted');

        return redirect()->route('admin.utility-meters.index')
            ->with('success', 'Utility meter deleted successfully.');
    }

    /**
     * Toggle meter status.
     */
    public function toggleStatus(UtilityMeter $utilityMeter): RedirectResponse
    {
        $utilityMeter->update(['is_active' => !$utilityMeter->is_active]);

        $status = $utilityMeter->is_active ? 'activated' : 'deactivated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityMeter)
            ->log("Utility meter {$status}");

        return redirect()->route('admin.utility-meters.index')
            ->with('success', "Utility meter {$status} successfully.");
    }
}

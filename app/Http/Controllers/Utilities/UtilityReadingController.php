<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\UtilityReading;
use App\Models\UtilityMeter;
use App\Models\UtilityType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UtilityReadingController extends Controller
{
    /**
     * Display a listing of utility readings.
     */
    public function index(Request $request): View
    {
        $query = UtilityReading::with(['meter.property', 'utilityType', 'readBy', 'verifiedBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reading', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('meter', function ($q) use ($search) {
                      $q->where('meter_number', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by utility type
        if ($request->filled('utility_type_id')) {
            $query->where('utility_type_id', $request->utility_type_id);
        }

        // Filter by meter
        if ($request->filled('meter_id')) {
            $query->where('meter_id', $request->meter_id);
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
            $query->where('reading_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('reading_date', '<=', $request->end_date);
        }

        $utilityReadings = $query->latest('reading_date')->paginate(15);
        $utilityMeters = UtilityMeter::active()->get();
        $utilityTypes = UtilityType::active()->get();

        return view('utilities.readings.index', compact('utilityReadings', 'utilityMeters', 'utilityTypes'));
    }

    /**
     * Show the form for creating a new utility reading.
     */
    public function create(Request $request): View
    {
        $utilityMeters = UtilityMeter::active()->get();
        $utilityTypes = UtilityType::active()->get();
        $selectedMeter = $request->meter_id ? UtilityMeter::find($request->meter_id) : null;
        
        return view('utilities.readings.create', compact('utilityMeters', 'utilityTypes', 'selectedMeter'));
    }

    /**
     * Store a newly created utility reading.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'meter_id' => 'required|exists:utility_meters,id',
            'utility_type_id' => 'required|exists:utility_types,id',
            'reading' => 'required|numeric|min:0',
            'reading_date' => 'required|date',
            'rate_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $meter = UtilityMeter::findOrFail($request->meter_id);
        
        // Get previous reading
        $previousReading = $meter->utilityReadings()
            ->orderBy('reading_date', 'desc')
            ->first();

        $previousReadingValue = $previousReading ? $previousReading->reading : 0;

        $utilityReading = UtilityReading::create([
            'meter_id' => $request->meter_id,
            'utility_type_id' => $request->utility_type_id,
            'reading' => $request->reading,
            'reading_date' => $request->reading_date,
            'previous_reading' => $previousReadingValue,
            'consumption' => $request->reading - $previousReadingValue,
            'rate_per_unit' => $request->rate_per_unit,
            'total_amount' => ($request->reading - $previousReadingValue) * $request->rate_per_unit,
            'notes' => $request->notes,
            'read_by' => auth()->id(),
        ]);

        // Update meter with new reading
        $meter->update([
            'last_reading' => $request->reading,
            'last_reading_date' => $request->reading_date,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityReading)
            ->log('Utility reading created');

        return redirect()->route('admin.utility-readings.index')
            ->with('success', 'Utility reading created successfully.');
    }

    /**
     * Display the specified utility reading.
     */
    public function show(UtilityReading $utilityReading): View
    {
        $utilityReading->load(['meter.property', 'utilityType', 'readBy', 'verifiedBy']);
        return view('utilities.readings.show', compact('utilityReading'));
    }

    /**
     * Show the form for editing the utility reading.
     */
    public function edit(UtilityReading $utilityReading): View
    {
        $utilityMeters = UtilityMeter::active()->get();
        $utilityTypes = UtilityType::active()->get();
        return view('utilities.readings.edit', compact('utilityReading', 'utilityMeters', 'utilityTypes'));
    }

    /**
     * Update the specified utility reading.
     */
    public function update(Request $request, UtilityReading $utilityReading): RedirectResponse
    {
        $request->validate([
            'meter_id' => 'required|exists:utility_meters,id',
            'utility_type_id' => 'required|exists:utility_types,id',
            'reading' => 'required|numeric|min:0',
            'reading_date' => 'required|date',
            'rate_per_unit' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $utilityReading->update([
            'meter_id' => $request->meter_id,
            'utility_type_id' => $request->utility_type_id,
            'reading' => $request->reading,
            'reading_date' => $request->reading_date,
            'rate_per_unit' => $request->rate_per_unit,
            'notes' => $request->notes,
        ]);

        // Recalculate consumption and total amount
        $utilityReading->calculateConsumption();
        $utilityReading->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityReading)
            ->log('Utility reading updated');

        return redirect()->route('admin.utility-readings.index')
            ->with('success', 'Utility reading updated successfully.');
    }

    /**
     * Verify the specified utility reading.
     */
    public function verify(UtilityReading $utilityReading): RedirectResponse
    {
        $utilityReading->verify(auth()->user());

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityReading)
            ->log('Utility reading verified');

        return redirect()->route('admin.utility-readings.index')
            ->with('success', 'Utility reading verified successfully.');
    }

    /**
     * Remove the specified utility reading.
     */
    public function destroy(UtilityReading $utilityReading): RedirectResponse
    {
        $utilityReading->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($utilityReading)
            ->log('Utility reading deleted');

        return redirect()->route('admin.utility-readings.index')
            ->with('success', 'Utility reading deleted successfully.');
    }
}

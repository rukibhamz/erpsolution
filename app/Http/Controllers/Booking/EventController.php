<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);
        
        $query = Event::query();

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('event_reference', 'like', '%' . $request->search . '%')
                  ->orWhere('venue', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->date_to);
        }

        $events = $query->latest()->paginate(15);

        return view('admin.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Event::class);
        return view('admin.events.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Event::class);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'amenities' => 'nullable|array',
            'allow_partial_payment' => 'boolean',
            'partial_payment_amount' => 'nullable|numeric|min:0',
            'terms_conditions' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // RACE CONDITION FIX: Generate unique event reference using database transaction
        $event = DB::transaction(function () use ($validated) {
            // Get the next sequence number atomically
            $nextNumber = DB::table('events')
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(event_reference, 4) AS UNSIGNED)')) + 1;

            $eventReference = 'EVT' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Handle image uploads
            $images = [];
            if (request()->hasFile('images')) {
                foreach (request()->file('images') as $image) {
                    $path = $image->store('events', 'public');
                    $images[] = $path;
                }
            }

            return Event::create([
                ...$validated,
                'event_reference' => $eventReference,
                'images' => $images,
                'booked_count' => 0,
                'status' => 'draft',
                'is_active' => true,
            ]);
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event created');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): View
    {
        $this->authorize('view', $event);
        $event->load(['bookings']);
        return view('admin.events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): View
    {
        $this->authorize('update', $event);
        return view('admin.events.edit', compact('event'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'amenities' => 'nullable|array',
            'status' => ['required', Rule::in(['draft', 'published', 'cancelled', 'completed'])],
            'is_active' => 'boolean',
            'allow_partial_payment' => 'boolean',
            'partial_payment_amount' => 'nullable|numeric|min:0',
            'terms_conditions' => 'nullable|string',
            'cancellation_policy' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle new image uploads
        $currentImages = $event->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('events', 'public');
                $currentImages[] = $path;
            }
        }

        $event->update([
            ...$validated,
            'images' => $currentImages,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event updated');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);
        
        // Check if event has bookings
        if ($event->bookings()->exists()) {
            return redirect()->route('admin.events.index')
                ->with('error', 'Cannot delete event with existing bookings.');
        }

        // Delete images from storage
        if ($event->images) {
            foreach ($event->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $event->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event deleted');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * SECURITY FIX: Remove image with proper validation
     */
    public function removeImage(Event $event, Request $request): RedirectResponse
    {
        $this->authorize('update', $event);
        
        $request->validate([
            'image_index' => 'required|integer|min:0'
        ]);

        $imageIndex = $request->image_index;
        $images = $event->images ?? [];

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

        $event->update(['images' => $images]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event image removed');

        return redirect()->back()
            ->with('success', 'Image removed successfully.');
    }

    /**
     * BUSINESS LOGIC FIX: Toggle event status with proper state transitions
     */
    public function toggleStatus(Event $event): RedirectResponse
    {
        $this->authorize('update', $event);
        
        // Define status transitions
        $statusTransitions = [
            'draft' => 'published',
            'published' => 'draft',
            'cancelled' => 'draft',
            'completed' => 'draft'
        ];

        $newStatus = $statusTransitions[$event->status] ?? 'draft';
        $event->update(['status' => $newStatus]);

        $statusMessages = [
            'draft' => 'moved to draft',
            'published' => 'published',
            'cancelled' => 'cancelled',
            'completed' => 'completed'
        ];

        $message = $statusMessages[$newStatus] ?? 'status updated';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log("Event {$message}");

        return redirect()->route('admin.events.index')
            ->with('success', "Event {$message} successfully.");
    }
}
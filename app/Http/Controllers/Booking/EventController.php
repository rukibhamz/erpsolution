<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request): View
    {
        $query = Event::with('category');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        // Filter by visibility
        if ($request->filled('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $events = $query->latest()->paginate(15);
        $categories = EventCategory::active()->get();

        return view('booking.events.index', compact('events', 'categories'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create(): View
    {
        $categories = EventCategory::active()->get();
        return view('booking.events.create', compact('categories'));
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'max_attendees' => 'required|integer|min:1',
            'price_per_person' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_percentage' => 'nullable|integer|min:1|max:100',
            'terms_and_conditions' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'amenities' => 'nullable|array',
            'status' => 'required|in:draft,published,cancelled,completed',
            'is_public' => 'boolean',
            'allow_partial_payment' => 'boolean',
        ]);

        $eventData = $request->except(['images']);
        $eventData['amenities'] = $request->amenities ?? [];
        $eventData['is_public'] = $request->boolean('is_public', true);
        $eventData['allow_partial_payment'] = $request->boolean('allow_partial_payment', true);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('events', 'public');
                $imagePaths[] = $path;
            }
            $eventData['images'] = $imagePaths;
        }

        $event = Event::create($eventData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event created');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): View
    {
        $event->load(['category', 'bookings']);
        return view('booking.events.show', compact('event'));
    }

    /**
     * Show the form for editing the event.
     */
    public function edit(Event $event): View
    {
        $categories = EventCategory::active()->get();
        return view('booking.events.edit', compact('event', 'categories'));
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:event_categories,id',
            'venue' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'max_attendees' => 'required|integer|min:1',
            'price_per_person' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'deposit_percentage' => 'nullable|integer|min:1|max:100',
            'terms_and_conditions' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'amenities' => 'nullable|array',
            'status' => 'required|in:draft,published,cancelled,completed',
            'is_public' => 'boolean',
            'allow_partial_payment' => 'boolean',
        ]);

        $eventData = $request->except(['images']);
        $eventData['amenities'] = $request->amenities ?? [];
        $eventData['is_public'] = $request->boolean('is_public', true);
        $eventData['allow_partial_payment'] = $request->boolean('allow_partial_payment', true);

        // Handle image uploads
        if ($request->hasFile('images')) {
            $imagePaths = $event->images ?? [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('events', 'public');
                $imagePaths[] = $path;
            }
            $eventData['images'] = $imagePaths;
        }

        $event->update($eventData);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log('Event updated');

        return redirect()->route('admin.events.index')
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        // Check if event has bookings
        if ($event->bookings()->exists()) {
            return redirect()->route('admin.events.index')
                ->with('error', 'Cannot delete event with existing bookings.');
        }

        // Delete associated images
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
     * Toggle event status.
     */
    public function toggleStatus(Event $event): RedirectResponse
    {
        $newStatus = $event->status === 'published' ? 'draft' : 'published';
        $event->update(['status' => $newStatus]);

        $status = $newStatus === 'published' ? 'published' : 'unpublished';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($event)
            ->log("Event {$status}");

        return redirect()->route('admin.events.index')
            ->with('success', "Event {$status} successfully.");
    }

    /**
     * Remove image from event.
     */
    public function removeImage(Event $event, Request $request): RedirectResponse
    {
        $imageIndex = $request->image_index;
        $images = $event->images ?? [];

        if (isset($images[$imageIndex])) {
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
        }

        return redirect()->back()
            ->with('success', 'Image removed successfully.');
    }

    /**
     * Get calendar view of events.
     */
    public function calendar(): View
    {
        $events = Event::with('category')
            ->where('status', 'published')
            ->where('start_date', '>=', now()->subDays(30))
            ->where('start_date', '<=', now()->addDays(90))
            ->get();

        return view('booking.events.calendar', compact('events'));
    }
}

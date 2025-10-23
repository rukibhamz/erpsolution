<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicEventController extends Controller
{
    /**
     * Display a listing of public events.
     */
    public function index(Request $request): View
    {
        $query = Event::with(['category'])
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('start_date', '>=', now());

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('start_date', '<=', $request->end_date);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $events = $query->orderBy('start_date')->paginate(12);
        $categories = EventCategory::where('is_active', true)->get();

        return view('public.events.index', compact('events', 'categories'));
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): View
    {
        // Only show public events
        if (!$event->is_public || $event->status !== 'active') {
            abort(404);
        }

        $event->load(['category', 'bookings']);
        
        // Get related events
        $relatedEvents = Event::with(['category'])
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('id', '!=', $event->id)
            ->where('category_id', $event->category_id)
            ->where('start_date', '>=', now())
            ->limit(4)
            ->get();

        return view('public.events.show', compact('event', 'relatedEvents'));
    }

    /**
     * Get events for calendar view.
     */
    public function calendar(): View
    {
        $events = Event::with(['category'])
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('start_date', '>=', now())
            ->get();

        return view('public.events.calendar', compact('events'));
    }

    /**
     * Get events data for calendar API.
     */
    public function calendarData()
    {
        $events = Event::with(['category'])
            ->where('is_public', true)
            ->where('status', 'active')
            ->where('start_date', '>=', now())
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->name,
                    'start' => $event->start_date->format('Y-m-d H:i:s'),
                    'end' => $event->end_date->format('Y-m-d H:i:s'),
                    'url' => route('public.events.show', $event),
                    'color' => $event->category->color ?? '#3B82F6',
                    'description' => $event->description,
                    'location' => $event->location,
                    'price' => $event->formatted_price,
                ];
            });

        return response()->json($events);
    }
}

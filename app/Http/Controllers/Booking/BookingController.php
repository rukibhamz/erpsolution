<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);
        
        $query = Booking::with(['event']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('booking_reference', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('customer_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('customer_email', 'like', '%' . $request->input('search') . '%');
            });
        }

        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->input('booking_status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('booking_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('booking_date', '<=', $request->input('date_to'));
        }

        $bookings = $query->latest()->paginate(15);
        $events = Event::published()->get();

        return view('admin.bookings.index', compact('bookings', 'events'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Booking::class);
        $events = Event::published()->get();
        return view('admin.bookings.create', compact('events'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);
        
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'ticket_quantity' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get the event
        $event = Event::findOrFail($validated['event_id']);

        // Check if event has available spots
        if (!$event->hasAvailableSpots()) {
            return redirect()->back()
                ->with('error', 'Event is fully booked.');
        }

        // Check if requested quantity is available
        if ($validated['ticket_quantity'] > $event->available_spots) {
            return redirect()->back()
                ->with('error', 'Requested quantity exceeds available spots.');
        }

        // RACE CONDITION FIX: Generate unique booking reference using database transaction
        $booking = DB::transaction(function () use ($validated, $event) {
            // Get the next sequence number atomically
            $nextNumber = DB::table('bookings')
                ->lockForUpdate()
                ->max(DB::raw('CAST(SUBSTRING(booking_reference, 4) AS UNSIGNED)')) + 1;

            $bookingReference = 'BKG' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $totalAmount = $event->price * $validated['ticket_quantity'];
            $balanceAmount = $totalAmount;

            $booking = Booking::create([
                ...$validated,
                'booking_reference' => $bookingReference,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_amount' => $balanceAmount,
                'payment_status' => 'pending',
                'booking_status' => 'pending',
                'booking_date' => now(),
            ]);

            // Update event booked count
            $event->increment('booked_count', $validated['ticket_quantity']);

            return $booking;
        });

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Booking created');

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);
        $booking->load(['event']);
        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking): View
    {
        $this->authorize('update', $booking);
        $events = Event::published()->get();
        return view('admin.bookings.edit', compact('booking', 'events'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);
        
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'ticket_quantity' => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if new quantity is available
        $quantityDifference = $validated['ticket_quantity'] - $booking->getAttribute('ticket_quantity');
        if ($quantityDifference > 0 && $booking->event->available_spots < $quantityDifference) {
            return redirect()->back()
                ->with('error', 'Insufficient available spots for the requested quantity.');
        }

        $booking->update($validated);

        // Update event booked count if quantity changed
        if ($quantityDifference != 0) {
            $booking->event->increment('booked_count', $quantityDifference);
            
            // Recalculate amounts
            $totalAmount = $booking->event->price * $validated['ticket_quantity'];
            $balanceAmount = $totalAmount - $booking->paid_amount;
            
            $booking->update([
                'total_amount' => $totalAmount,
                'balance_amount' => $balanceAmount,
            ]);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Booking updated');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);
        
        // Check if booking can be cancelled
        if ($booking->getAttribute('booking_status') === 'confirmed' && $booking->getAttribute('payment_status') === 'paid') {
            return redirect()->route('admin.bookings.index')
                ->with('error', 'Cannot delete confirmed and paid booking.');
        }

        // Update event booked count
        $booking->event->decrement('booked_count', $booking->getAttribute('ticket_quantity'));

        $booking->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Booking deleted');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }

    /**
     * Confirm booking
     */
    public function confirm(Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);
        
        $booking->update(['booking_status' => 'confirmed']);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Booking confirmed');

        return redirect()->back()
            ->with('success', 'Booking confirmed successfully.');
    }

    /**
     * Cancel booking
     */
    public function cancel(Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);
        
        $booking->update(['booking_status' => 'cancelled']);

        // Update event booked count
        $booking->event->decrement('booked_count', $booking->getAttribute('ticket_quantity'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Booking cancelled');

        return redirect()->back()
            ->with('success', 'Booking cancelled successfully.');
    }
}
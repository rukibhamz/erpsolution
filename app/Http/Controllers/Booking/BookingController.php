<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\EventBooking;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): View
    {
        $query = EventBooking::with(['event', 'createdBy']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_reference', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by booking status
        if ($request->filled('booking_status')) {
            $query->where('booking_status', $request->booking_status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by event
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('start_date', '>=', $request->start_date);
            });
        }
        if ($request->filled('end_date')) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('start_date', '<=', $request->end_date);
            });
        }

        $bookings = $query->latest()->paginate(15);
        $events = Event::published()->get();

        return view('booking.bookings.index', compact('bookings', 'events'));
    }

    /**
     * Show the form for creating a new booking.
     */
    public function create(Request $request): View
    {
        $events = Event::published()->get();
        $selectedEvent = $request->event_id ? Event::find($request->event_id) : null;
        
        return view('booking.bookings.create', compact('events', 'selectedEvent'));
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|string|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'number_of_attendees' => 'required|integer|min:1',
            'special_requirements' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $event = Event::findOrFail($request->event_id);

        // Check if event is available
        if ($event->status !== 'published') {
            return redirect()->back()
                ->with('error', 'Selected event is not available for booking.');
        }

        // Check if event is fully booked
        if ($event->isFullyBooked()) {
            return redirect()->back()
                ->with('error', 'Selected event is fully booked.');
        }

        // Check if requested attendees exceed remaining capacity
        if ($event->remaining_capacity < $request->number_of_attendees) {
            return redirect()->back()
                ->with('error', 'Not enough capacity for the requested number of attendees.');
        }

        // Calculate amounts
        $totalAmount = $event->price_per_person * $request->number_of_attendees;
        $depositAmount = $event->deposit_amount;
        if ($event->deposit_percentage) {
            $depositAmount = ($totalAmount * $event->deposit_percentage) / 100;
        }
        $balanceAmount = $totalAmount - $depositAmount;

        // Generate booking reference
        $bookingReference = 'BK-' . str_pad(EventBooking::count() + 1, 6, '0', STR_PAD_LEFT);

        $booking = EventBooking::create([
            'booking_reference' => $bookingReference,
            'event_id' => $request->event_id,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'number_of_attendees' => $request->number_of_attendees,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'balance_amount' => $balanceAmount,
            'amount_paid' => 0,
            'payment_status' => 'pending',
            'booking_status' => 'pending',
            'special_requirements' => $request->special_requirements,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking created');

        return redirect()->route('admin.bookings.show', $booking)
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified booking.
     */
    public function show(EventBooking $booking): View
    {
        $booking->load(['event', 'payments', 'createdBy']);
        return view('booking.bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the booking.
     */
    public function edit(EventBooking $booking): View
    {
        $events = Event::published()->get();
        return view('booking.bookings.edit', compact('booking', 'events'));
    }

    /**
     * Update the specified booking.
     */
    public function update(Request $request, EventBooking $booking): RedirectResponse
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|string|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'number_of_attendees' => 'required|integer|min:1',
            'special_requirements' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Check if new attendee count exceeds event capacity
        $event = $booking->event;
        $currentAttendees = $event->total_attendees - $booking->number_of_attendees;
        $newTotalAttendees = $currentAttendees + $request->number_of_attendees;
        
        if ($newTotalAttendees > $event->max_attendees) {
            return redirect()->back()
                ->with('error', 'Not enough capacity for the requested number of attendees.');
        }

        $booking->update([
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'number_of_attendees' => $request->number_of_attendees,
            'special_requirements' => $request->special_requirements,
            'notes' => $request->notes,
        ]);

        // Recalculate amounts if attendee count changed
        if ($booking->number_of_attendees != $request->number_of_attendees) {
            $totalAmount = $event->price_per_person * $request->number_of_attendees;
            $depositAmount = $event->deposit_amount;
            if ($event->deposit_percentage) {
                $depositAmount = ($totalAmount * $event->deposit_percentage) / 100;
            }
            $balanceAmount = $totalAmount - $depositAmount;

            $booking->update([
                'total_amount' => $totalAmount,
                'deposit_amount' => $depositAmount,
                'balance_amount' => $balanceAmount,
            ]);

            // Update payment status
            $booking->updatePaymentStatus();
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking updated');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking updated successfully.');
    }

    /**
     * Confirm the specified booking.
     */
    public function confirm(EventBooking $booking): RedirectResponse
    {
        $booking->markAsConfirmed();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking confirmed');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking confirmed successfully.');
    }

    /**
     * Cancel the specified booking.
     */
    public function cancel(EventBooking $booking): RedirectResponse
    {
        $booking->markAsCancelled();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking cancelled');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking cancelled successfully.');
    }

    /**
     * Complete the specified booking.
     */
    public function complete(EventBooking $booking): RedirectResponse
    {
        $booking->markAsCompleted();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking completed');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking completed successfully.');
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(EventBooking $booking): RedirectResponse
    {
        // Check if booking has payments
        if ($booking->payments()->exists()) {
            return redirect()->route('admin.bookings.index')
                ->with('error', 'Cannot delete booking with payment records.');
        }

        $booking->delete();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($booking)
            ->log('Event booking deleted');

        return redirect()->route('admin.bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}

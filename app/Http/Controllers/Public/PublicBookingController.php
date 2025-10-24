<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\BookingPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PublicBookingController extends Controller
{
    /**
     * Show the form for creating a new booking.
     */
    public function create(Event $event): View
    {
        // Only allow booking for public events
        if (!$event->getAttribute('is_public') || $event->getAttribute('status') !== 'active') {
            abort(404);
        }

        // Check if event is still available
        if ($event->start_date < now()) {
            return redirect()->route('public.events.show', $event)
                ->with('error', 'This event has already started and cannot be booked.');
        }

        // Check capacity
        $bookedSeats = $event->bookings()->where('status', '!=', 'cancelled')->sum('number_of_guests');
        $availableSeats = $event->getAttribute('capacity') - $bookedSeats;

        if ($availableSeats <= 0) {
            return redirect()->route('public.events.show', $event)
                ->with('error', 'This event is fully booked.');
        }

        return view('public.bookings.create', compact('event', 'availableSeats'));
    }

    /**
     * Store a newly created booking.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        // Only allow booking for public events
        if (!$event->getAttribute('is_public') || $event->getAttribute('status') !== 'active') {
            abort(404);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'number_of_guests' => 'required|integer|min:1|max:20',
            'special_requirements' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:full,partial',
            'partial_payment_amount' => 'required_if:payment_method,partial|nullable|numeric|min:0',
        ]);

        // Check capacity
        $bookedSeats = $event->bookings()->where('status', '!=', 'cancelled')->sum('number_of_guests');
        $availableSeats = $event->getAttribute('capacity') - $bookedSeats;

        if ($request->input('number_of_guests') > $availableSeats) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Not enough seats available. Only ' . $availableSeats . ' seats remaining.');
        }

        // Calculate amounts
        $totalAmount = $event->getAttribute('price') * $request->input('number_of_guests');
        $partialAmount = $request->input('payment_method') === 'partial' ? $request->input('partial_payment_amount') : $totalAmount;
        $remainingAmount = $totalAmount - $partialAmount;

        DB::beginTransaction();
        try {
            // Create booking
            $booking = EventBooking::create([
                'event_id' => $event->getKey(),
                'customer_name' => $request->input('customer_name'),
                'customer_email' => $request->input('customer_email'),
                'customer_phone' => $request->input('customer_phone'),
                'number_of_guests' => $request->input('number_of_guests'),
                'total_amount' => $totalAmount,
                'amount_paid' => $partialAmount,
                'remaining_amount' => $remainingAmount,
                'booking_date' => now(),
                'status' => 'pending',
                'special_requirements' => $request->input('special_requirements'),
                'payment_status' => $request->input('payment_method') === 'full' ? 'paid' : 'partial',
                'booking_reference' => 'BK-' . str_pad(EventBooking::count() + 1, 6, '0', STR_PAD_LEFT),
            ]);

            // Create payment record
            if ($partialAmount > 0) {
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'amount' => $partialAmount,
                    'payment_method' => 'online',
                    'payment_status' => 'pending',
                    'payment_reference' => 'PAY-' . str_pad(BookingPayment::count() + 1, 6, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('public.bookings.show', $booking)
                ->with('success', 'Booking created successfully. Please complete your payment.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while creating your booking. Please try again.');
        }
    }

    /**
     * Display the specified booking.
     */
    public function show(EventBooking $booking): View
    {
        $booking->load(['event', 'payments']);
        return view('public.bookings.show', compact('booking'));
    }

    /**
     * Show payment form for booking.
     */
    public function payment(EventBooking $booking): View
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('public.bookings.show', $booking)
                ->with('info', 'This booking is already fully paid.');
        }

        return view('public.bookings.payment', compact('booking'));
    }

    /**
     * Process payment for booking.
     */
    public function processPayment(Request $request, EventBooking $booking): RedirectResponse
    {
        if ($booking->payment_status === 'paid') {
            return redirect()->route('public.bookings.show', $booking)
                ->with('info', 'This booking is already fully paid.');
        }

        $request->validate([
            'payment_method' => 'required|in:card,bank_transfer,cash',
            'amount' => 'required|numeric|min:0|max:' . $booking->remaining_amount,
        ]);

        // Create payment record
        $payment = BookingPayment::create([
            'booking_id' => $booking->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'payment_reference' => 'PAY-' . str_pad(BookingPayment::count() + 1, 6, '0', STR_PAD_LEFT),
            'payment_date' => now(),
        ]);

        // Update booking amounts
        $newAmountPaid = $booking->amount_paid + $request->amount;
        $newRemainingAmount = $booking->total_amount - $newAmountPaid;
        $newPaymentStatus = $newRemainingAmount <= 0 ? 'paid' : 'partial';

        $booking->update([
            'amount_paid' => $newAmountPaid,
            'remaining_amount' => $newRemainingAmount,
            'payment_status' => $newPaymentStatus,
            'status' => $newPaymentStatus === 'paid' ? 'confirmed' : 'pending',
        ]);

        return redirect()->route('public.bookings.show', $booking)
            ->with('success', 'Payment processed successfully.');
    }

    /**
     * Cancel booking.
     */
    public function cancel(EventBooking $booking): RedirectResponse
    {
        if ($booking->status === 'cancelled') {
            return redirect()->route('public.bookings.show', $booking)
                ->with('info', 'This booking is already cancelled.');
        }

        if ($booking->status === 'completed') {
            return redirect()->route('public.bookings.show', $booking)
                ->with('error', 'Cannot cancel a completed booking.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'Cancelled by customer',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('public.bookings.show', $booking)
            ->with('success', 'Booking cancelled successfully.');
    }

    /**
     * Show booking confirmation.
     */
    public function confirm(EventBooking $booking): View
    {
        if ($booking->status !== 'confirmed') {
            return redirect()->route('public.bookings.show', $booking);
        }

        return view('public.bookings.confirm', compact('booking'));
    }
}

<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\BookingPayment;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): View
    {
        $query = BookingPayment::with(['booking.event']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                  ->orWhere('gateway_reference', 'like', "%{$search}%")
                  ->orWhereHas('booking', function ($q) use ($search) {
                      $q->where('booking_reference', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $payments = $query->latest()->paginate(15);

        return view('booking.payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new payment.
     */
    public function create(Request $request): View
    {
        $booking = $request->booking_id ? EventBooking::findOrFail($request->booking_id) : null;
        $bookings = EventBooking::where('payment_status', '!=', 'paid')->get();
        
        return view('booking.payments.create', compact('bookings', 'booking'));
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:event_bookings,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,card,cheque,online',
            'gateway_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $booking = EventBooking::findOrFail($request->booking_id);

        // Check if payment amount exceeds outstanding balance
        $outstandingBalance = $booking->outstanding_balance;
        if ($request->amount > $outstandingBalance) {
            return redirect()->back()
                ->with('error', 'Payment amount cannot exceed outstanding balance.');
        }

        // Generate payment reference
        $paymentReference = 'PAY-' . str_pad(BookingPayment::count() + 1, 6, '0', STR_PAD_LEFT);

        $payment = BookingPayment::create([
            'booking_id' => $request->booking_id,
            'payment_reference' => $paymentReference,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'gateway_reference' => $request->gateway_reference,
            'status' => 'completed', // Auto-complete for manual payments
            'notes' => $request->notes,
            'processed_at' => now(),
        ]);

        // Update booking payment status
        $booking->updatePaymentStatus();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($payment)
            ->log('Payment recorded');

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Display the specified payment.
     */
    public function show(BookingPayment $payment): View
    {
        $payment->load(['booking.event']);
        return view('booking.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the payment.
     */
    public function edit(BookingPayment $payment): View
    {
        $bookings = EventBooking::all();
        return view('booking.payments.edit', compact('payment', 'bookings'));
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, BookingPayment $payment): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,card,cheque,online',
            'gateway_reference' => 'nullable|string|max:255',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $payment->update([
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'gateway_reference' => $request->gateway_reference,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        // Update booking payment status if payment is completed
        if ($request->status === 'completed') {
            $payment->booking->updatePaymentStatus();
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($payment)
            ->log('Payment updated');

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Mark payment as completed.
     */
    public function markCompleted(BookingPayment $payment): RedirectResponse
    {
        $payment->markAsCompleted();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($payment)
            ->log('Payment marked as completed');

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment marked as completed.');
    }

    /**
     * Mark payment as failed.
     */
    public function markFailed(BookingPayment $payment): RedirectResponse
    {
        $payment->markAsFailed();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($payment)
            ->log('Payment marked as failed');

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment marked as failed.');
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(BookingPayment $payment): RedirectResponse
    {
        $booking = $payment->booking;
        $payment->delete();

        // Update booking payment status
        $booking->updatePaymentStatus();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($payment)
            ->log('Payment deleted');

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
}

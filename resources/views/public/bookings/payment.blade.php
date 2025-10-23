@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('public.events.index') }}" class="hover:text-indigo-600">Events</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('public.bookings.show', $booking) }}" class="hover:text-indigo-600">Booking #{{ $booking->booking_reference }}</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900">Payment</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Payment Form -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900">Complete Your Payment</h1>
                        <p class="text-gray-600">Choose your preferred payment method and complete your booking.</p>
                    </div>
                    
                    <form method="POST" action="{{ route('public.bookings.process-payment', $booking) }}" class="p-6">
                        @csrf
                        
                        <!-- Payment Method -->
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h2>
                            
                            <div class="space-y-4">
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 cursor-pointer">
                                    <input type="radio" name="payment_method" id="card_payment" value="card" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="card_payment" class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">Credit/Debit Card</span>
                                                <span class="text-sm text-gray-500 block">Pay with Visa, Mastercard, or other cards</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 cursor-pointer">
                                    <input type="radio" name="payment_method" id="bank_transfer" value="bank_transfer" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="bank_transfer" class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">Bank Transfer</span>
                                                <span class="text-sm text-gray-500 block">Transfer directly to our bank account</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-indigo-500 cursor-pointer">
                                    <input type="radio" name="payment_method" id="cash_payment" value="cash" 
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="cash_payment" class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">Cash Payment</span>
                                                <span class="text-sm text-gray-500 block">Pay in cash at our office</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            @error('payment_method')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Amount -->
                        <div class="mb-6">
                            <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Payment Amount (₦) *</label>
                            <input type="number" name="amount" id="amount" required
                                   min="0" max="{{ $booking->remaining_amount }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('amount') border-red-300 @enderror"
                                   value="{{ old('amount', $booking->remaining_amount) }}">
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Maximum: ₦{{ number_format($booking->remaining_amount, 2) }} | 
                                Remaining: ₦{{ number_format($booking->remaining_amount, 2) }}
                            </p>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="mb-6">
                            <div class="flex items-start">
                                <input type="checkbox" name="terms_accepted" id="terms_accepted" required
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1">
                                <label for="terms_accepted" class="ml-3 text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms and Conditions</a> 
                                    and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                                </label>
                            </div>
                            @error('terms_accepted')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                            <a href="{{ route('public.bookings.show', $booking) }}" 
                               class="text-gray-600 hover:text-gray-800">← Back to Booking</a>
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Process Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Summary</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            @if($booking->event->image)
                                <img src="{{ Storage::url($booking->event->image) }}" alt="{{ $booking->event->name }}" class="w-16 h-16 object-cover rounded-lg">
                            @else
                                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($booking->event->name, 0, 2) }}</span>
                                </div>
                            @endif
                            <div class="ml-4">
                                <h3 class="font-medium text-gray-900">{{ $booking->event->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $booking->event->start_date->format('M j, Y g:i A') }}</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Total Amount</span>
                                <span class="text-sm font-medium">{{ $booking->formatted_total_amount }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Amount Paid</span>
                                <span class="text-sm font-medium text-green-600">{{ $booking->formatted_amount_paid }}</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-semibold">
                                <span>Remaining Amount</span>
                                <span class="text-red-600">{{ $booking->formatted_remaining_amount }}</span>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-2">Payment Information</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Secure payment processing</li>
                                <li>• Instant confirmation</li>
                                <li>• Email receipt</li>
                                <li>• 24/7 customer support</li>
                            </ul>
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            <p><strong>Booking Reference:</strong> {{ $booking->booking_reference }}</p>
                            <p><strong>Number of Guests:</strong> {{ $booking->number_of_guests }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const maxAmount = {{ $booking->remaining_amount }};
    
    // Set default amount to remaining amount
    amountInput.value = maxAmount;
    
    // Validate amount input
    amountInput.addEventListener('input', function() {
        const value = parseFloat(this.value);
        if (value > maxAmount) {
            this.value = maxAmount;
        }
        if (value < 0) {
            this.value = 0;
        }
    });
});
</script>
@endsection

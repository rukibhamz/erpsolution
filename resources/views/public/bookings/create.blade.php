@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('public.events.index') }}" class="hover:text-indigo-600">Events</a></li>
                <li><span class="mx-2">/</span></li>
                <li><a href="{{ route('public.events.show', $event) }}" class="hover:text-indigo-600">{{ $event->name }}</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900">Book Event</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Booking Form -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-gray-900">Book Your Spot</h1>
                        <p class="text-gray-600">Complete the form below to book your spot at this event.</p>
                    </div>
                    
                    <form method="POST" action="{{ route('public.bookings.store', $event) }}" class="p-6">
                        @csrf
                        
                        <!-- Customer Information -->
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input type="text" name="customer_name" id="customer_name" required
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_name') border-red-300 @enderror"
                                           value="{{ old('customer_name') }}">
                                    @error('customer_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                    <input type="email" name="customer_email" id="customer_email" required
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_email') border-red-300 @enderror"
                                           value="{{ old('customer_email') }}">
                                    @error('customer_email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                                    <input type="tel" name="customer_phone" id="customer_phone" required
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_phone') border-red-300 @enderror"
                                           value="{{ old('customer_phone') }}">
                                    @error('customer_phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="number_of_guests" class="block text-sm font-medium text-gray-700 mb-1">Number of Guests *</label>
                                    <select name="number_of_guests" id="number_of_guests" required
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('number_of_guests') border-red-300 @enderror">
                                        <option value="">Select number of guests</option>
                                        @for($i = 1; $i <= min(20, $availableSeats); $i++)
                                            <option value="{{ $i }}" {{ old('number_of_guests') == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'guest' : 'guests' }}</option>
                                        @endfor
                                    </select>
                                    @error('number_of_guests')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label for="special_requirements" class="block text-sm font-medium text-gray-700 mb-1">Special Requirements</label>
                                <textarea name="special_requirements" id="special_requirements" rows="3"
                                          class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('special_requirements') border-red-300 @enderror"
                                          placeholder="Any special dietary requirements, accessibility needs, or other requests...">{{ old('special_requirements') }}</textarea>
                                @error('special_requirements')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Options -->
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Options</h2>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="radio" name="payment_method" id="full_payment" value="full" 
                                           {{ old('payment_method', 'full') === 'full' ? 'checked' : '' }}
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="full_payment" class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Pay Full Amount</span>
                                        <span class="text-sm text-gray-500 block">Pay the complete amount now</span>
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="radio" name="payment_method" id="partial_payment" value="partial"
                                           {{ old('payment_method') === 'partial' ? 'checked' : '' }}
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="partial_payment" class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Partial Payment</span>
                                        <span class="text-sm text-gray-500 block">Pay a portion now and the rest later</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Partial Payment Amount -->
                            <div id="partial_amount_section" class="mt-4 hidden">
                                <label for="partial_payment_amount" class="block text-sm font-medium text-gray-700 mb-1">Partial Payment Amount (₦)</label>
                                <input type="number" name="partial_payment_amount" id="partial_payment_amount" 
                                       min="0" max="{{ $event->price * 20 }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('partial_payment_amount') border-red-300 @enderror"
                                       value="{{ old('partial_payment_amount') }}">
                                @error('partial_payment_amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Minimum: ₦1,000 | Maximum: ₦{{ number_format($event->price * 20, 2) }}</p>
                            </div>
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
                            <a href="{{ route('public.events.show', $event) }}" 
                               class="text-gray-600 hover:text-gray-800">← Back to Event</a>
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Create Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Booking Summary</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            @if($event->image)
                                <img src="{{ Storage::url($event->image) }}" alt="{{ $event->name }}" class="w-16 h-16 object-cover rounded-lg">
                            @else
                                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($event->name, 0, 2) }}</span>
                                </div>
                            @endif
                            <div class="ml-4">
                                <h3 class="font-medium text-gray-900">{{ $event->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $event->start_date->format('M j, Y g:i A') }}</p>
                                <p class="text-sm text-gray-500">{{ $event->location }}</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Price per person</span>
                                <span class="text-sm font-medium">{{ $event->formatted_price }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Number of guests</span>
                                <span class="text-sm font-medium" id="guest_count">1</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-semibold">
                                <span>Total Amount</span>
                                <span id="total_amount">{{ $event->formatted_price }}</span>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-2">What's Included</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Event access</li>
                                <li>• Professional facilities</li>
                                <li>• Expert guidance</li>
                                <li>• Networking opportunities</li>
                            </ul>
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            <p><strong>Available Seats:</strong> {{ $availableSeats }} / {{ $event->capacity }}</p>
                            <p><strong>Booking Reference:</strong> Will be generated after booking</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const partialAmountSection = document.getElementById('partial_amount_section');
    const partialAmountInput = document.getElementById('partial_payment_amount');
    const guestCountSelect = document.getElementById('number_of_guests');
    const guestCountDisplay = document.getElementById('guest_count');
    const totalAmountDisplay = document.getElementById('total_amount');
    
    const eventPrice = {{ $event->price }};
    
    // Handle payment method change
    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'partial') {
                partialAmountSection.classList.remove('hidden');
            } else {
                partialAmountSection.classList.add('hidden');
            }
        });
    });
    
    // Handle guest count change
    guestCountSelect.addEventListener('change', function() {
        const guestCount = parseInt(this.value) || 1;
        const totalAmount = eventPrice * guestCount;
        
        guestCountDisplay.textContent = guestCount;
        totalAmountDisplay.textContent = '₦' + totalAmount.toLocaleString('en-NG', { minimumFractionDigits: 2 });
        
        // Update partial payment max value
        if (partialAmountInput) {
            partialAmountInput.max = totalAmount;
        }
    });
    
    // Initialize display
    if (guestCountSelect.value) {
        guestCountSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection

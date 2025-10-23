@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create New Booking</h1>
                    <p class="mt-2 text-gray-600">Book an event for a customer</p>
                </div>
                <div>
                    <a href="{{ route('admin.bookings.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Back to Bookings
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('admin.bookings.store') }}" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Event Selection -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Event Selection</h3>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="event_id" class="block text-sm font-medium text-gray-700">Select Event *</label>
                        <select name="event_id" id="event_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('event_id') border-red-300 @enderror">
                            <option value="">Choose an event...</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ (old('event_id') == $event->id || ($selectedEvent && $selectedEvent->id == $event->id)) ? 'selected' : '' }}
                                        data-price="{{ $event->price_per_person }}"
                                        data-capacity="{{ $event->max_attendees }}"
                                        data-attendees="{{ $event->total_attendees }}"
                                        data-venue="{{ $event->venue }}"
                                        data-start="{{ $event->start_date->format('M d, Y g:i A') }}">
                                    {{ $event->title }} - {{ $event->venue }} ({{ $event->start_date->format('M d, Y') }})
                                </option>
                            @endforeach
                        </select>
                        @error('event_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Event Details Display -->
                    <div id="event-details" class="md:col-span-2 hidden">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900">Selected Event Details</h4>
                            <div id="event-info" class="mt-2 text-sm text-gray-600"></div>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                    </div>
                    
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700">Customer Name *</label>
                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_name') border-red-300 @enderror">
                        @error('customer_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                        <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_email') border-red-300 @enderror">
                        @error('customer_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700">Phone Number *</label>
                        <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('customer_phone') border-red-300 @enderror">
                        @error('customer_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="number_of_attendees" class="block text-sm font-medium text-gray-700">Number of Attendees *</label>
                        <input type="number" name="number_of_attendees" id="number_of_attendees" value="{{ old('number_of_attendees', 1) }}" min="1" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('number_of_attendees') border-red-300 @enderror">
                        @error('number_of_attendees')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p id="capacity-warning" class="mt-1 text-sm text-red-600 hidden">Not enough capacity for the requested number of attendees.</p>
                    </div>

                    <!-- Booking Details -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Booking Details</h3>
                    </div>

                    <div class="md:col-span-2">
                        <label for="special_requirements" class="block text-sm font-medium text-gray-700">Special Requirements</label>
                        <textarea name="special_requirements" id="special_requirements" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('special_requirements') border-red-300 @enderror">{{ old('special_requirements') }}</textarea>
                        @error('special_requirements')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Internal Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('notes') border-red-300 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pricing Summary -->
                    <div id="pricing-summary" class="md:col-span-2 hidden">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing Summary</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Price per person:</span>
                                    <span id="price-per-person">₦0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Number of attendees:</span>
                                    <span id="attendee-count">0</span>
                                </div>
                                <div class="flex justify-between font-medium text-lg">
                                    <span>Total Amount:</span>
                                    <span id="total-amount">₦0.00</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Deposit Amount:</span>
                                    <span id="deposit-amount">₦0.00</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Balance Amount:</span>
                                    <span id="balance-amount">₦0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.bookings.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventSelect = document.getElementById('event_id');
    const eventDetails = document.getElementById('event-details');
    const eventInfo = document.getElementById('event-info');
    const pricingSummary = document.getElementById('pricing-summary');
    const attendeesInput = document.getElementById('number_of_attendees');
    const capacityWarning = document.getElementById('capacity-warning');

    eventSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const price = parseFloat(selectedOption.dataset.price);
            const capacity = parseInt(selectedOption.dataset.capacity);
            const attendees = parseInt(selectedOption.dataset.attendees);
            const venue = selectedOption.dataset.venue;
            const start = selectedOption.dataset.start;
            
            // Show event details
            eventDetails.classList.remove('hidden');
            eventInfo.innerHTML = `
                <div><strong>Venue:</strong> ${venue}</div>
                <div><strong>Date:</strong> ${start}</div>
                <div><strong>Price:</strong> ₦${price.toLocaleString()}/person</div>
                <div><strong>Available Capacity:</strong> ${capacity - attendees} seats</div>
            `;
            
            // Show pricing summary
            pricingSummary.classList.remove('hidden');
            updatePricing();
        } else {
            eventDetails.classList.add('hidden');
            pricingSummary.classList.add('hidden');
        }
    });

    attendeesInput.addEventListener('input', function() {
        updatePricing();
        checkCapacity();
    });

    function updatePricing() {
        const selectedOption = eventSelect.options[eventSelect.selectedIndex];
        if (!selectedOption.value) return;

        const price = parseFloat(selectedOption.dataset.price);
        const attendees = parseInt(attendeesInput.value) || 0;
        const totalAmount = price * attendees;
        
        document.getElementById('price-per-person').textContent = `₦${price.toLocaleString()}`;
        document.getElementById('attendee-count').textContent = attendees;
        document.getElementById('total-amount').textContent = `₦${totalAmount.toLocaleString()}`;
        document.getElementById('deposit-amount').textContent = `₦${(totalAmount * 0.3).toLocaleString()}`; // 30% deposit
        document.getElementById('balance-amount').textContent = `₦${(totalAmount * 0.7).toLocaleString()}`;
    }

    function checkCapacity() {
        const selectedOption = eventSelect.options[eventSelect.selectedIndex];
        if (!selectedOption.value) return;

        const capacity = parseInt(selectedOption.dataset.capacity);
        const currentAttendees = parseInt(selectedOption.dataset.attendees);
        const requestedAttendees = parseInt(attendeesInput.value) || 0;
        const availableCapacity = capacity - currentAttendees;

        if (requestedAttendees > availableCapacity) {
            capacityWarning.classList.remove('hidden');
        } else {
            capacityWarning.classList.add('hidden');
        }
    }
});
</script>
@endsection

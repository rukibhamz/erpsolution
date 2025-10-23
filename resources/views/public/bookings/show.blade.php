@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Booking Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Booking Details</h1>
                        <p class="text-gray-600">Reference: {{ $booking->booking_reference }}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($booking->status === 'confirmed') bg-green-100 text-green-800
                            @elseif($booking->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($booking->status === 'cancelled') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($booking->status) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($booking->payment_status === 'paid') bg-green-100 text-green-800
                            @elseif($booking->payment_status === 'partial') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($booking->payment_status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Booking Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Event Details -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Event Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start">
                            @if($booking->event->image)
                                <img src="{{ Storage::url($booking->event->image) }}" alt="{{ $booking->event->name }}" class="w-20 h-20 object-cover rounded-lg">
                            @else
                                <div class="w-20 h-20 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                    <span class="text-white font-bold">{{ substr($booking->event->name, 0, 2) }}</span>
                                </div>
                            @endif
                            <div class="ml-4 flex-1">
                                <h3 class="text-xl font-semibold text-gray-900">{{ $booking->event->name }}</h3>
                                <p class="text-gray-600">{{ $booking->event->description }}</p>
                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $booking->event->start_date->format('l, F j, Y \a\t g:i A') }}
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    {{ $booking->event->location }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $booking->customer_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $booking->customer_email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $booking->customer_phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Number of Guests</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $booking->number_of_guests }} {{ $booking->number_of_guests == 1 ? 'guest' : 'guests' }}</p>
                            </div>
                        </div>
                        @if($booking->special_requirements)
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700">Special Requirements</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $booking->special_requirements }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Payment History -->
                @if($booking->payments->count() > 0)
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Payment History</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($booking->payments as $payment)
                                <div class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">Payment #{{ $payment->payment_reference }}</p>
                                            <p class="text-sm text-gray-500">{{ $payment->payment_date->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900">{{ $payment->formatted_amount }}</p>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($payment->payment_status === 'completed') bg-green-100 text-green-800
                                                @elseif($payment->payment_status === 'pending') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($payment->payment_status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Booking Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6 sticky top-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Booking Summary</h2>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Amount</span>
                            <span class="text-sm font-medium">{{ $booking->formatted_total_amount }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Amount Paid</span>
                            <span class="text-sm font-medium text-green-600">{{ $booking->formatted_amount_paid }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Remaining Amount</span>
                            <span class="text-sm font-medium text-red-600">{{ $booking->formatted_remaining_amount }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-900">Booking Date</span>
                                <span class="text-sm text-gray-600">{{ $booking->booking_date->format('M j, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 space-y-3">
                        @if($booking->payment_status !== 'paid')
                            <a href="{{ route('public.bookings.payment', $booking) }}" 
                               class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Make Payment
                            </a>
                        @endif
                        
                        @if($booking->status !== 'cancelled' && $booking->status !== 'completed')
                            <form method="POST" action="{{ route('public.bookings.cancel', $booking) }}" class="w-full">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Are you sure you want to cancel this booking?')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel Booking
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('public.events.index') }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Browse More Events
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

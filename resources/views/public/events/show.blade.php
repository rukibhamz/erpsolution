@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="{{ route('public.events.index') }}" class="hover:text-indigo-600">Events</a></li>
                <li><span class="mx-2">/</span></li>
                <li class="text-gray-900">{{ $event->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Event Details -->
            <div class="lg:col-span-2">
                <!-- Event Image -->
                @if($event->image)
                    <img src="{{ Storage::url($event->image) }}" alt="{{ $event->name }}" class="w-full h-64 object-cover rounded-lg mb-6">
                @else
                    <div class="w-full h-64 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg mb-6 flex items-center justify-center">
                        <span class="text-white text-4xl font-bold">{{ substr($event->name, 0, 2) }}</span>
                    </div>
                @endif

                <!-- Event Information -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-3xl font-bold text-gray-900">{{ $event->name }}</h1>
                        <span class="text-3xl font-bold text-indigo-600">{{ $event->formatted_price }}</span>
                    </div>
                    
                    <div class="flex items-center mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                            {{ $event->category->name }}
                        </span>
                    </div>
                    
                    <div class="prose max-w-none">
                        <p class="text-gray-600 text-lg">{{ $event->description }}</p>
                    </div>
                </div>

                <!-- Event Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Event Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900">Start Date & Time</h3>
                                <p class="text-gray-600">{{ $event->start_date->format('l, F j, Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900">End Date & Time</h3>
                                <p class="text-gray-600">{{ $event->end_date->format('l, F j, Y \a\t g:i A') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900">Location</h3>
                                <p class="text-gray-600">{{ $event->location }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-gray-400 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-medium text-gray-900">Capacity</h3>
                                <p class="text-gray-600">{{ $event->capacity }} people</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Book Your Spot</h2>
                    
                    @php
                        $bookedSeats = $event->bookings()->where('status', '!=', 'cancelled')->sum('number_of_guests');
                        $availableSeats = $event->capacity - $bookedSeats;
                    @endphp
                    
                    <div class="mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Available Seats</span>
                            <span class="text-sm font-bold text-indigo-600">{{ $availableSeats }} / {{ $event->capacity }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ ($availableSeats / $event->capacity) * 100 }}%"></div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <div class="text-3xl font-bold text-indigo-600">{{ $event->formatted_price }}</div>
                        <div class="text-sm text-gray-500">per person</div>
                    </div>
                    
                    @if($availableSeats > 0)
                        <a href="{{ route('public.bookings.create', $event) }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Book Now
                        </a>
                    @else
                        <div class="text-center py-4">
                            <div class="text-red-600 font-medium mb-2">Fully Booked</div>
                            <p class="text-sm text-gray-500">This event is currently fully booked.</p>
                        </div>
                    @endif
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-2">Event Highlights</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• Professional event management</li>
                            <li>• High-quality facilities</li>
                            <li>• Expert guidance</li>
                            <li>• Networking opportunities</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Events -->
        @if($relatedEvents->count() > 0)
            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Related Events</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedEvents as $relatedEvent)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            @if($relatedEvent->image)
                                <img src="{{ Storage::url($relatedEvent->image) }}" alt="{{ $relatedEvent->name }}" class="w-full h-32 object-cover">
                            @else
                                <div class="w-full h-32 bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center">
                                    <span class="text-white text-lg font-bold">{{ substr($relatedEvent->name, 0, 2) }}</span>
                                </div>
                            @endif
                            
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 mb-2">{{ $relatedEvent->name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">{{ $relatedEvent->start_date->format('M j, Y') }}</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-indigo-600">{{ $relatedEvent->formatted_price }}</span>
                                    <a href="{{ route('public.events.show', $relatedEvent) }}" 
                                       class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

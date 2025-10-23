@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Discover Amazing Events</h1>
            <p class="text-lg text-gray-600">Book your spot at our upcoming events and create unforgettable memories.</p>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Events</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Event name, location, or description">
                    </div>
                    
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" id="category_id" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div>
                        <label for="min_price" class="block text-sm font-medium text-gray-700 mb-1">Min Price (₦)</label>
                        <input type="number" name="min_price" id="min_price" value="{{ request('min_price') }}"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="0" min="0">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filter Events
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Events Grid -->
        @if($events->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($events as $event)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        @if($event->image)
                            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->name }}" class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gradient-to-r from-indigo-500 to-purple-600 flex items-center justify-center">
                                <span class="text-white text-2xl font-bold">{{ substr($event->name, 0, 2) }}</span>
                            </div>
                        @endif
                        
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $event->category->name }}
                                </span>
                                <span class="text-2xl font-bold text-indigo-600">{{ $event->formatted_price }}</span>
                            </div>
                            
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $event->name }}</h3>
                            
                            <p class="text-gray-600 text-sm mb-4">{{ Str::limit($event->description, 100) }}</p>
                            
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $event->start_date->format('M j, Y g:i A') }}
                            </div>
                            
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $event->location }}
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-500">
                                    <span class="font-medium">{{ $event->capacity - $event->bookings()->where('status', '!=', 'cancelled')->sum('number_of_guests') }}</span> seats left
                                </div>
                                <a href="{{ route('public.events.show', $event) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="mt-8">
                {{ $events->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No events found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria or check back later for new events.</p>
            </div>
        @endif
    </div>
</div>
@endsection

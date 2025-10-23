@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Properties</h1>
                    <p class="mt-2 text-gray-600">Manage your property portfolio</p>
                </div>
                <div>
                    <a href="{{ route('admin.properties.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Add New Property
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Name, code, or address">
                    </div>
                    
                    <div>
                        <label for="property_type_id" class="block text-sm font-medium text-gray-700">Property Type</label>
                        <select name="property_type_id" id="property_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Types</option>
                            @foreach($propertyTypes as $type)
                                <option value="{{ $type->id }}" {{ request('property_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="occupied" {{ request('status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                            <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="unavailable" {{ request('status') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" name="city" id="city" value="{{ request('city') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="City name">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Properties Grid -->
        @if($properties->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($properties as $property)
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <!-- Property Image -->
                    <div class="h-48 bg-gray-200 relative">
                        @if($property->images && count($property->images) > 0)
                            <img src="{{ asset('storage/' . $property->images[0]) }}" 
                                 alt="{{ $property->name }}" 
                                 class="h-full w-full object-cover">
                        @else
                            <div class="h-full w-full flex items-center justify-center">
                                <svg class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                            </div>
                        @endif
                        
                        <!-- Status Badge -->
                        <div class="absolute top-2 right-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $property->status === 'available' ? 'bg-green-100 text-green-800' : 
                                   ($property->status === 'occupied' ? 'bg-blue-100 text-blue-800' : 
                                   ($property->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($property->status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Property Details -->
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">{{ $property->name }}</h3>
                            <span class="text-sm text-gray-500">{{ $property->property_code }}</span>
                        </div>
                        
                        <p class="mt-1 text-sm text-gray-600">{{ $property->propertyType->name }}</p>
                        <p class="mt-1 text-sm text-gray-600">{{ $property->full_address }}</p>
                        
                        @if($property->bedrooms || $property->bathrooms)
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            @if($property->bedrooms)
                                <span>{{ $property->bedrooms }} bed{{ $property->bedrooms > 1 ? 's' : '' }}</span>
                            @endif
                            @if($property->bedrooms && $property->bathrooms)
                                <span class="mx-1">•</span>
                            @endif
                            @if($property->bathrooms)
                                <span>{{ $property->bathrooms }} bath{{ $property->bathrooms > 1 ? 's' : '' }}</span>
                            @endif
                            @if($property->area_sqft)
                                <span class="mx-1">•</span>
                                <span>{{ number_format($property->area_sqft) }} sqft</span>
                            @endif
                        </div>
                        @endif

                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                <span class="text-lg font-semibold text-gray-900">₦{{ number_format($property->rent_amount, 2) }}</span>
                                <span class="text-sm text-gray-500">/month</span>
                            </div>
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.properties.show', $property) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View</a>
                                <a href="{{ route('admin.properties.edit', $property) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="mt-6">
                {{ $properties->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No properties found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding your first property.</p>
                <div class="mt-6">
                    <a href="{{ route('admin.properties.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Add New Property
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

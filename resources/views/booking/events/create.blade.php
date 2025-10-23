@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create New Event</h1>
                    <p class="mt-2 text-gray-600">Set up a new event for booking</p>
                </div>
                <div>
                    <a href="{{ route('admin.events.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Back to Events
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white shadow rounded-lg">
            <form method="POST" action="{{ route('admin.events.store') }}" enctype="multipart/form-data" class="p-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Event Information</h3>
                    </div>
                    
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Event Title *</label>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('title') border-red-300 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                        <select name="category_id" id="category_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('category_id') border-red-300 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea name="description" id="description" rows="4" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Venue Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Venue Information</h3>
                    </div>

                    <div>
                        <label for="venue" class="block text-sm font-medium text-gray-700">Venue Name *</label>
                        <input type="text" name="venue" id="venue" value="{{ old('venue') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('venue') border-red-300 @enderror">
                        @error('venue')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="venue_address" class="block text-sm font-medium text-gray-700">Venue Address *</label>
                        <textarea name="venue_address" id="venue_address" rows="2" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('venue_address') border-red-300 @enderror">{{ old('venue_address') }}</textarea>
                        @error('venue_address')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date and Time -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Date and Time</h3>
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date & Time *</label>
                        <input type="datetime-local" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('start_date') border-red-300 @enderror">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date & Time *</label>
                        <input type="datetime-local" name="end_date" id="end_date" value="{{ old('end_date') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('end_date') border-red-300 @enderror">
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Capacity and Pricing -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Capacity and Pricing</h3>
                    </div>

                    <div>
                        <label for="max_attendees" class="block text-sm font-medium text-gray-700">Maximum Attendees *</label>
                        <input type="number" name="max_attendees" id="max_attendees" value="{{ old('max_attendees') }}" min="1" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('max_attendees') border-red-300 @enderror">
                        @error('max_attendees')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="price_per_person" class="block text-sm font-medium text-gray-700">Price per Person (₦) *</label>
                        <input type="number" name="price_per_person" id="price_per_person" value="{{ old('price_per_person') }}" step="0.01" min="0" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('price_per_person') border-red-300 @enderror">
                        @error('price_per_person')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="deposit_amount" class="block text-sm font-medium text-gray-700">Deposit Amount (₦)</label>
                        <input type="number" name="deposit_amount" id="deposit_amount" value="{{ old('deposit_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('deposit_amount') border-red-300 @enderror">
                        @error('deposit_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="deposit_percentage" class="block text-sm font-medium text-gray-700">Deposit Percentage (%)</label>
                        <input type="number" name="deposit_percentage" id="deposit_percentage" value="{{ old('deposit_percentage') }}" min="1" max="100"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('deposit_percentage') border-red-300 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Leave empty if using fixed deposit amount</p>
                        @error('deposit_percentage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Amenities -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Amenities</label>
                        <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-2">
                            @php
                                $amenities = [
                                    'WiFi', 'Parking', 'Catering', 'Audio/Visual', 'Air Conditioning', 'Security',
                                    'Photography', 'Videography', 'Decoration', 'Entertainment', 'Transportation', 'Accommodation'
                                ];
                            @endphp
                            @foreach($amenities as $amenity)
                                <label class="flex items-center">
                                    <input type="checkbox" name="amenities[]" value="{{ $amenity }}" 
                                           {{ in_array($amenity, old('amenities', [])) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700">{{ $amenity }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Images -->
                    <div class="md:col-span-2">
                        <label for="images" class="block text-sm font-medium text-gray-700">Event Images</label>
                        <input type="file" name="images[]" id="images" multiple accept="image/*"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('images') border-red-300 @enderror">
                        <p class="mt-1 text-sm text-gray-500">Upload up to 10 images (max 2MB each)</p>
                        @error('images')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="md:col-span-2">
                        <label for="terms_and_conditions" class="block text-sm font-medium text-gray-700">Terms and Conditions</label>
                        <textarea name="terms_and_conditions" id="terms_and_conditions" rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('terms_and_conditions') border-red-300 @enderror">{{ old('terms_and_conditions') }}</textarea>
                        @error('terms_and_conditions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status and Settings -->
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Status and Settings</h3>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                        <select name="status" id="status" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('status') border-red-300 @enderror">
                            <option value="">Select Status</option>
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_public" id="is_public" value="1" 
                                   {{ old('is_public', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <label for="is_public" class="ml-2 text-sm text-gray-700">Event is public (visible to customers)</label>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="allow_partial_payment" id="allow_partial_payment" value="1" 
                                   {{ old('allow_partial_payment', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <label for="allow_partial_payment" class="ml-2 text-sm text-gray-700">Allow partial payments</label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="{{ route('admin.events.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill end date when start date changes
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    if (startDate) {
        // Default to 4 hours from start date
        const endDate = new Date(startDate);
        endDate.setHours(endDate.getHours() + 4);
        document.getElementById('end_date').value = endDate.toISOString().slice(0, 16);
    }
});

// Calculate deposit amount when percentage changes
document.getElementById('deposit_percentage').addEventListener('input', function() {
    const percentage = parseFloat(this.value);
    const pricePerPerson = parseFloat(document.getElementById('price_per_person').value) || 0;
    const maxAttendees = parseInt(document.getElementById('max_attendees').value) || 1;
    
    if (percentage > 0 && pricePerPerson > 0) {
        const totalAmount = pricePerPerson * maxAttendees;
        const depositAmount = (totalAmount * percentage) / 100;
        document.getElementById('deposit_amount').value = depositAmount.toFixed(2);
    }
});
</script>
@endsection

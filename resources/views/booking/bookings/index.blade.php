@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Event Bookings</h1>
                    <p class="mt-2 text-gray-600">Manage event bookings and payments</p>
                </div>
                <div>
                    <a href="{{ route('admin.bookings.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        New Booking
                    </a>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               placeholder="Booking ref, customer name, or email">
                    </div>
                    
                    <div>
                        <label for="booking_status" class="block text-sm font-medium text-gray-700">Booking Status</label>
                        <select name="booking_status" id="booking_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('booking_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('booking_status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="cancelled" {{ request('booking_status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="completed" {{ request('booking_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment Status</label>
                        <select name="payment_status" id="payment_status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Payments</option>
                            <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="event_id" class="block text-sm font-medium text-gray-700">Event</label>
                        <select name="event_id" id="event_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">All Events</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                    {{ $event->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:p-6">
                @if($bookings->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($bookings as $booking)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $booking->booking_reference }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->number_of_attendees }} attendee{{ $booking->number_of_attendees > 1 ? 's' : '' }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->created_at->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $booking->customer_name }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->customer_email }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->customer_phone }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $booking->event->title }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->event->venue }}</div>
                                        <div class="text-sm text-gray-500">{{ $booking->event->start_date->format('M d, Y g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">₦{{ number_format($booking->total_amount, 2) }}</div>
                                        <div class="text-sm text-gray-500">Paid: ₦{{ number_format($booking->amount_paid, 2) }}</div>
                                        @if($booking->outstanding_balance > 0)
                                            <div class="text-sm text-red-600">Balance: ₦{{ number_format($booking->outstanding_balance, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col space-y-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $booking->status_color === 'green' ? 'bg-green-100 text-green-800' : ($booking->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : ($booking->status_color === 'red' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                                                {{ ucfirst($booking->booking_status) }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $booking->payment_status_color === 'green' ? 'bg-green-100 text-green-800' : ($booking->payment_status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : ($booking->payment_status_color === 'orange' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                                {{ ucfirst($booking->payment_status) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('admin.bookings.show', $booking) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">View</a>
                                            <a href="{{ route('admin.bookings.edit', $booking) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            
                                            @if($booking->booking_status === 'pending')
                                                <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-green-600 hover:text-green-900">Confirm</button>
                                                </form>
                                            @endif
                                            
                                            @if($booking->booking_status === 'confirmed')
                                                <form method="POST" action="{{ route('admin.bookings.complete', $booking) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-blue-600 hover:text-blue-900">Complete</button>
                                                </form>
                                            @endif
                                            
                                            @if(in_array($booking->booking_status, ['pending', 'confirmed']))
                                                <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Cancel</button>
                                                </form>
                                            @endif
                                            
                                            <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this booking?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $bookings->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No bookings found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first booking.</p>
                        <div class="mt-6">
                            <a href="{{ route('admin.bookings.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                New Booking
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

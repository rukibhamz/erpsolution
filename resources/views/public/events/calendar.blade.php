@extends('layouts.public')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Event Calendar</h1>
            <p class="text-lg text-gray-600">View all upcoming events in our interactive calendar.</p>
        </div>

        <!-- Calendar Navigation -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="prev-month" class="p-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <h2 id="current-month" class="text-xl font-semibold text-gray-900"></h2>
                        <button id="next-month" class="p-2 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="today-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            Today
                        </button>
                        <button id="list-view-btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            List View
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <div id="calendar"></div>
            </div>
        </div>

        <!-- Event List (Hidden by default) -->
        <div id="event-list" class="hidden mt-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Upcoming Events</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($events as $event)
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($event->image)
                                            <img src="{{ Storage::url($event->image) }}" alt="{{ $event->name }}" class="w-16 h-16 object-cover rounded-lg">
                                        @else
                                            <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                <span class="text-white font-bold">{{ substr($event->name, 0, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $event->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $event->start_date->format('l, F j, Y \a\t g:i A') }}</p>
                                        <p class="text-sm text-gray-500">{{ $event->location }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <span class="text-2xl font-bold text-indigo-600">{{ $event->formatted_price }}</span>
                                    <a href="{{ route('public.events.show', $event) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const currentMonthEl = document.getElementById('current-month');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const todayBtn = document.getElementById('today-btn');
    const listViewBtn = document.getElementById('list-view-btn');
    const eventListEl = document.getElementById('event-list');
    
    let calendar;
    let isListView = false;

    // Initialize FullCalendar
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        events: {
            url: '{{ route("public.events.calendar-data") }}',
            method: 'GET'
        },
        eventClick: function(info) {
            window.location.href = info.event.url;
        },
        eventDidMount: function(info) {
            // Add custom styling
            info.el.style.borderRadius = '6px';
            info.el.style.border = 'none';
            info.el.style.padding = '4px 8px';
            info.el.style.fontSize = '12px';
            info.el.style.fontWeight = '500';
        },
        dateClick: function(info) {
            // Optional: Handle date clicks
        },
        viewDidMount: function(info) {
            updateMonthDisplay();
        }
    });

    // Render calendar
    calendar.render();

    // Update month display
    function updateMonthDisplay() {
        const view = calendar.view;
        const start = view.activeStart;
        const month = start.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        currentMonthEl.textContent = month;
    }

    // Navigation buttons
    prevMonthBtn.addEventListener('click', function() {
        calendar.prev();
        updateMonthDisplay();
    });

    nextMonthBtn.addEventListener('click', function() {
        calendar.next();
        updateMonthDisplay();
    });

    todayBtn.addEventListener('click', function() {
        calendar.today();
        updateMonthDisplay();
    });

    // Toggle list view
    listViewBtn.addEventListener('click', function() {
        isListView = !isListView;
        
        if (isListView) {
            calendarEl.style.display = 'none';
            eventListEl.classList.remove('hidden');
            listViewBtn.textContent = 'Calendar View';
        } else {
            calendarEl.style.display = 'block';
            eventListEl.classList.add('hidden');
            listViewBtn.textContent = 'List View';
        }
    });
});
</script>
@endsection

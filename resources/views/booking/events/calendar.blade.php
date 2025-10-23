@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Event Calendar</h1>
                    <p class="mt-2 text-gray-600">View all events in calendar format</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.events.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        List View
                    </a>
                    <a href="{{ route('admin.events.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Event
                    </a>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="bg-white shadow rounded-lg">
            <div class="p-6">
                <div id="calendar" class="w-full"></div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div id="eventModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" x-data="{ show: false }" x-show="show">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="modalEventTitle" class="text-lg font-medium text-gray-900 mb-4"></h3>
            <div id="modalEventDetails" class="space-y-2 text-sm text-gray-600"></div>
            <div class="mt-6 flex justify-end space-x-3">
                <button onclick="closeEventModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                    Close
                </button>
                <a id="modalEventLink" href="#" 
                   class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    View Details
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const events = @json($events);
    
    // Format events for FullCalendar
    const calendarEvents = events.map(event => ({
        id: event.id,
        title: event.title,
        start: event.start_date,
        end: event.end_date,
        backgroundColor: event.category.color,
        borderColor: event.category.color,
        extendedProps: {
            venue: event.venue,
            venue_address: event.venue_address,
            price_per_person: event.price_per_person,
            max_attendees: event.max_attendees,
            total_attendees: event.total_attendees,
            status: event.status,
            description: event.description
        }
    }));

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: calendarEvents,
        eventClick: function(info) {
            showEventModal(info.event);
        },
        eventMouseEnter: function(info) {
            info.el.style.cursor = 'pointer';
        }
    });

    calendar.render();
});

function showEventModal(event) {
    const modal = document.getElementById('eventModal');
    const title = document.getElementById('modalEventTitle');
    const details = document.getElementById('modalEventDetails');
    const link = document.getElementById('modalEventLink');
    
    title.textContent = event.title;
    
    const props = event.extendedProps;
    details.innerHTML = `
        <div><strong>Venue:</strong> ${props.venue}</div>
        <div><strong>Address:</strong> ${props.venue_address}</div>
        <div><strong>Price:</strong> ₦${props.price_per_person.toLocaleString()}/person</div>
        <div><strong>Attendees:</strong> ${props.total_attendees}/${props.max_attendees}</div>
        <div><strong>Status:</strong> ${props.status}</div>
        <div><strong>Start:</strong> ${event.start.toLocaleString()}</div>
        <div><strong>End:</strong> ${event.end.toLocaleString()}</div>
        ${props.description ? `<div><strong>Description:</strong> ${props.description}</div>` : ''}
    `;
    
    link.href = `/admin/events/${event.id}`;
    
    modal.classList.remove('hidden');
}

function closeEventModal() {
    document.getElementById('eventModal').classList.add('hidden');
}
</script>
@endsection

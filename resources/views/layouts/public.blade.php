<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Event Booking Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div id="app">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('public.events.index') }}" class="text-2xl font-bold text-indigo-600">
                                {{ config('app.name', 'EventHub') }}
                            </a>
                        </div>
                        
                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('public.events.index') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition duration-150 ease-in-out">
                                Events
                            </a>
                            <a href="{{ route('public.events.calendar') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition duration-150 ease-in-out">
                                Calendar
                            </a>
                        </div>
                    </div>

                    <!-- Right side -->
                    <div class="flex items-center">
                        <!-- Search -->
                        <div class="hidden md:block">
                            <form method="GET" action="{{ route('public.events.index') }}" class="flex items-center">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Search events..." 
                                       class="w-64 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <button type="submit" class="ml-2 p-2 text-gray-400 hover:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        
                        <!-- Admin Login -->
                        <div class="ml-4">
                            <a href="{{ route('login') }}" 
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Admin Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile menu -->
        <div class="sm:hidden" x-data="{ open: false }">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('public.events.index') }}" 
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                    Events
                </a>
                <a href="{{ route('public.events.calendar') }}" 
                   class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300">
                    Calendar
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <main>
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mx-4 mt-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mx-4 mt-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if(session('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mx-4 mt-4" role="alert">
                    <span class="block sm:inline">{{ session('info') }}</span>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ config('app.name', 'EventHub') }}</h3>
                        <p class="text-gray-300">Your premier destination for event booking and management.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('public.events.index') }}" class="text-gray-300 hover:text-white">Browse Events</a></li>
                            <li><a href="{{ route('public.events.calendar') }}" class="text-gray-300 hover:text-white">Event Calendar</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contact</h3>
                        <p class="text-gray-300">Email: info@eventhub.com</p>
                        <p class="text-gray-300">Phone: +234 123 456 7890</p>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-700">
                    <p class="text-center text-gray-300">&copy; {{ date('Y') }} {{ config('app.name', 'EventHub') }}. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
</body>
</html>

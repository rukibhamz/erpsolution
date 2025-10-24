<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    <!-- Toast Notifications -->
    <div x-data="{ show: false, message: '', type: 'success' }" 
         x-show="show" 
         x-transition
         class="fixed top-4 right-4 z-50">
        <div x-show="show" 
             x-text="message"
             class="px-6 py-3 rounded-lg shadow-lg"
             :class="{
                 'bg-green-500 text-white': type === 'success',
                 'bg-red-500 text-white': type === 'error',
                 'bg-yellow-500 text-white': type === 'warning',
                 'bg-blue-500 text-white': type === 'info'
             }">
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             class="fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('success') }}
            <button @click="show = false" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             class="fixed top-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('error') }}
            <button @click="show = false" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    @endif

    @if (session('warning'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             class="fixed top-4 right-4 z-50 bg-yellow-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('warning') }}
            <button @click="show = false" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    @endif

    @if (session('info'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition
             class="fixed top-4 right-4 z-50 bg-blue-500 text-white px-6 py-3 rounded-lg shadow-lg">
            {{ session('info') }}
            <button @click="show = false" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    @endif
</body>
</html>
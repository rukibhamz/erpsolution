@props([
    'size' => 'md',
    'color' => 'indigo',
    'text' => null,
    'overlay' => false
])

@php
$sizeClasses = [
    'xs' => 'h-3 w-3',
    'sm' => 'h-4 w-4',
    'md' => 'h-6 w-6',
    'lg' => 'h-8 w-8',
    'xl' => 'h-12 w-12'
];

$colorClasses = [
    'indigo' => 'text-indigo-600',
    'white' => 'text-white',
    'gray' => 'text-gray-600',
    'red' => 'text-red-600',
    'green' => 'text-green-600',
    'blue' => 'text-blue-600'
];

$classes = $sizeClasses[$size] . ' ' . $colorClasses[$color];
@endphp

@if($overlay)
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" 
     role="dialog" 
     aria-modal="true" 
     aria-label="Loading">
    <div class="bg-white rounded-lg p-6 flex flex-col items-center space-y-4">
        <svg class="animate-spin {{ $classes }}" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        @if($text)
        <p class="text-sm text-gray-600">{{ $text }}</p>
        @endif
    </div>
</div>
@else
<div class="flex items-center justify-center {{ $text ? 'space-x-2' : '' }}">
    <svg class="animate-spin {{ $classes }}" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    @if($text)
    <span class="text-sm text-gray-600">{{ $text }}</span>
    @endif
</div>
@endif

@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'positive',
    'icon' => null,
    'color' => 'indigo',
    'loading' => false,
    'href' => null
])

@php
$colorClasses = [
    'indigo' => 'bg-indigo-500',
    'green' => 'bg-green-500',
    'red' => 'bg-red-500',
    'yellow' => 'bg-yellow-500',
    'blue' => 'bg-blue-500',
    'purple' => 'bg-purple-500',
    'pink' => 'bg-pink-500',
    'gray' => 'bg-gray-500'
];

$iconColorClasses = [
    'indigo' => 'text-indigo-600',
    'green' => 'text-green-600',
    'red' => 'text-red-600',
    'yellow' => 'text-yellow-600',
    'blue' => 'text-blue-600',
    'purple' => 'text-purple-600',
    'pink' => 'text-pink-600',
    'gray' => 'text-gray-600'
];

$changeColorClasses = [
    'positive' => 'text-green-600',
    'negative' => 'text-red-600',
    'neutral' => 'text-gray-600'
];
@endphp

<div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-md transition-shadow duration-200">
    @if($href)
    <a href="{{ $href }}" class="block">
    @endif
    
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if($icon)
                <div class="w-8 h-8 {{ $colorClasses[$color] }} rounded-md flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icon !!}
                    </svg>
                </div>
                @else
                <div class="w-8 h-8 {{ $colorClasses[$color] }} rounded-md"></div>
                @endif
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ $title }}
                    </dt>
                    <dd class="flex items-baseline">
                        <div class="text-2xl font-semibold text-gray-900">
                            @if($loading)
                                <div class="animate-pulse bg-gray-200 h-8 w-20 rounded"></div>
                            @else
                                {{ $value }}
                            @endif
                        </div>
                        @if($change && !$loading)
                        <div class="ml-2 flex items-baseline text-sm font-semibold {{ $changeColorClasses[$changeType] }}">
                            @if($changeType === 'positive')
                            <svg class="self-center flex-shrink-0 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            @elseif($changeType === 'negative')
                            <svg class="self-center flex-shrink-0 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            @endif
                            <span class="sr-only">
                                {{ $changeType === 'positive' ? 'Increased' : ($changeType === 'negative' ? 'Decreased' : 'No change') }} by
                            </span>
                            {{ $change }}
                        </div>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    
    @if($href)
    </a>
    @endif
</div>

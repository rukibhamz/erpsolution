@props([
    'type' => 'text',
    'label' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'help' => null,
    'placeholder' => '',
    'autocomplete' => 'off'
])

@php
$inputId = $attributes->get('id', Str::random(8));
$errorId = $inputId . '_error';
$helpId = $inputId . '_help';
@endphp

<div class="space-y-1">
    @if($label)
    <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">
        {{ $label }}
        @if($required)
            <span class="text-red-500" aria-label="required">*</span>
        @endif
    </label>
    @endif
    
    <div class="relative">
        <input 
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $attributes->get('name') }}"
            value="{{ $attributes->get('value') }}"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($error) aria-invalid="true" aria-describedby="{{ $errorId }}" @endif
            @if($help && !$error) aria-describedby="{{ $helpId }}" @endif
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error($attributes->get('name')) border-red-300 focus:ring-red-500 focus:border-red-500 @enderror @if($disabled) bg-gray-100 cursor-not-allowed @endif @if($readonly) bg-gray-50 @endif"
            {{ $attributes->except(['id', 'name', 'value', 'class']) }}
        >
        
        @if($type === 'password')
        <button type="button" 
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
                aria-label="Toggle password visibility">
            <svg x-show="!showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            <svg x-show="showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
            </svg>
        </button>
        @endif
    </div>
    
    @if($help && !$error)
    <p id="{{ $helpId }}" class="text-sm text-gray-500">
        {{ $help }}
    </p>
    @endif
    
    @if($error)
    <p id="{{ $errorId }}" class="text-sm text-red-600" role="alert">
        {{ $error }}
    </p>
    @endif
    
    @error($attributes->get('name'))
    <p class="text-sm text-red-600" role="alert">
        {{ $message }}
    </p>
    @enderror
</div>

@if($type === 'password')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('passwordInput', () => ({
        showPassword: false
    }))
})
</script>
@endif

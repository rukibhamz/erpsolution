@props([
    'method' => 'POST',
    'action' => '',
    'enctype' => 'application/x-www-form-urlencoded',
    'novalidate' => false
])

<form 
    method="{{ $method === 'GET' ? 'GET' : 'POST' }}"
    action="{{ $action }}"
    enctype="{{ $enctype }}"
    @if(!$novalidate) novalidate @endif
    role="form"
    aria-label="{{ $attributes->get('aria-label', 'Form') }}"
    {{ $attributes->except(['aria-label']) }}
>
    @if($method !== 'GET')
        @csrf
    @endif
    
    @if($method === 'PUT' || $method === 'PATCH' || $method === 'DELETE')
        @method($method)
    @endif
    
    {{ $slot }}
</form>

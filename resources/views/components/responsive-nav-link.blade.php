@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block px-4 py-3 rounded-xl text-start text-base font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 focus:outline-none transition-all duration-200 ease-in-out shadow-sm'
            : 'block px-4 py-3 rounded-xl text-start text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 hover:shadow-sm focus:outline-none transition-all duration-200 ease-in-out border border-transparent';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>

@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-4 py-1.5 bg-indigo-50 text-indigo-700 rounded-full text-sm font-semibold transition duration-150 ease-in-out shadow-sm border border-indigo-100'
            : 'inline-flex items-center px-4 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-full transition duration-150 ease-in-out border border-transparent';
@endphp

<a {{ $attributes->merge(['class' => $classes . ' max-w-[140px] truncate justify-center']) }}>
    {{ $slot }}
</a>

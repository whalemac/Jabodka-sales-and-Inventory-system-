@props([
    'variant' => 'primary',   // primary | accent | secondary | ghost | danger
    'size'    => 'md',        // sm | md | lg
    'href'    => null,
    'type'    => 'button',
    'icon'    => null,
    'block'   => false,
])

@php
    $variants = [
        'primary'   => 'bg-navy hover:bg-navy-dark text-white shadow-sm',
        'accent'    => 'bg-accent hover:bg-accent-dark text-white shadow-sm',
        'secondary' => 'bg-white border border-gray-200 hover:border-navy text-ink',
        'ghost'     => 'bg-transparent hover:bg-sand text-gray-600 hover:text-ink',
        'danger'    => 'bg-white border border-red-200 hover:border-red-400 text-red-600',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-2',
        'md' => 'text-sm px-4 py-2.5',
        'lg' => 'text-sm px-5 py-3',
    ];

    $classes = trim(
        'inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition select-none whitespace-nowrap '
        .'disabled:opacity-50 disabled:pointer-events-none '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']).' '
        .($block ? 'w-full' : '')
    );
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon){!! $icon !!}@endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon){!! $icon !!}@endif{{ $slot }}
    </button>
@endif

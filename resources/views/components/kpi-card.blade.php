@props([
    'label'   => '',
    'value'   => '',
    'sub'     => null,
    'href'    => null,
    'color'   => 'navy',   // navy | accent | green | red
    'icon'    => null,
])

@php
    $colorMap = [
        'navy'   => ['bg' => 'bg-navy',        'icon' => 'bg-navy-dark/40  text-white',    'text' => 'text-white',    'sub' => 'text-white/70'],
        'accent' => ['bg' => 'bg-accent',       'icon' => 'bg-accent-dark/30 text-white',   'text' => 'text-white',    'sub' => 'text-white/70'],
        'green'  => ['bg' => 'bg-green-600',    'icon' => 'bg-green-800/30  text-white',    'text' => 'text-white',    'sub' => 'text-white/70'],
        'red'    => ['bg' => 'bg-red-500',      'icon' => 'bg-red-700/30    text-white',    'text' => 'text-white',    'sub' => 'text-white/70'],
        'white'  => ['bg' => 'bg-white border border-gray-100', 'icon' => 'bg-sand text-navy', 'text' => 'text-ink', 'sub' => 'text-gray-500'],
    ];
    $c = $colorMap[$color] ?? $colorMap['white'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    class="{{ $c['bg'] }} rounded-2xl shadow-sm p-5 flex items-start gap-4 {{ $href ? 'hover:opacity-90 transition cursor-pointer' : '' }}">

    @if($icon)
    <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center {{ $c['icon'] }}">
        {!! $icon !!}
    </div>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-sm font-medium {{ $c['sub'] }} leading-tight truncate">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $c['text'] }} leading-tight mt-0.5">{{ $value }}</p>
        @if($sub)
            <p class="text-xs {{ $c['sub'] }} mt-1">{{ $sub }}</p>
        @endif
    </div>
</{{ $tag }}>

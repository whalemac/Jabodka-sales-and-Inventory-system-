@props([
    'name',
    'title' => '',
    'subtitle' => null,
    'size' => 'md',
    'closeable' => true,
])

@php
    $sizes = [
        'sm'   => 'max-w-sm',
        'md'   => 'max-w-lg',
        'lg'   => 'max-w-2xl',
        'xl'   => 'max-w-4xl',
        '2xl'  => 'max-w-6xl',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

{{-- Reusable modal (A1).
     Open:  window.Jabodka.modalOpen('name')  or  $dispatch('modal-open', 'name')
     Close: window.Jabodka.modalClose('name') or  $dispatch('modal-close', 'name')
     Load a fragment into the body: window.Jabodka.modalUrl('name', url) --}}
<div data-modal="{{ $name }}"
     x-data="{ open: false }"
     @modal-open.window="if ($event.detail === @js($name)) open = true"
     @modal-close.window="if ($event.detail === @js($name)) open = false"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-[90] overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-label="{{ $title ?: $name }}">

    <div class="fixed inset-0 bg-navy/60 backdrop-blur-[2px]" x-transition.opacity @click="{{ $closeable ? 'open = false' : 'void 0' }}"></div>

    <div class="relative min-h-full flex items-start sm:items-center justify-center p-4 sm:p-6">
        <div class="relative w-full {{ $sizeClass }} bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-100 shrink-0">
                <div class="min-w-0">
                    @if($title)
                        <h2 class="text-base font-bold text-ink leading-tight">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($closeable)
                <button type="button"
                        @click="open = false"
                        aria-label="Close dialog"
                        class="shrink-0 w-8 h-8 -mt-1 -mr-1 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-ink hover:bg-sand transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                @endif
            </div>

            {{-- Body --}}
            <div class="px-5 py-4 overflow-y-auto grow" data-modal-content data-print-area>
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @isset($footer)
            <div class="px-5 py-4 border-t border-gray-100 bg-sand/40 shrink-0">
                <div class="action-row justify-end">{{ $footer }}</div>
            </div>
            @endisset
        </div>
    </div>
</div>

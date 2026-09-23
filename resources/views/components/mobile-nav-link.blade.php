@props(['href', 'active' => false])

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'block px-3 py-2.5 rounded-lg text-sm font-medium transition '
           . ($active ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white hover:bg-white/10')
   ]) }}>
    {{ $slot }}
</a>

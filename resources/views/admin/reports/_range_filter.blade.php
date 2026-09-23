{{--
    Reusable range filter bar.
    Usage: @include('admin.reports._range_filter', ['action' => route('admin.reports.sales')])
--}}
<form method="GET" action="{{ $action }}"
      class="flex flex-wrap items-center gap-2 mb-5 print:hidden">

    {{-- Preset buttons --}}
    @foreach(['today' => 'Today', 'week' => 'This Week', 'month' => 'This Month'] as $val => $label)
    <button type="submit" name="range" value="{{ $val }}"
            class="h-9 px-4 rounded-xl text-sm font-medium transition
                   {{ $range === $val
                      ? 'bg-navy text-white shadow-sm'
                      : 'bg-white border border-gray-200 text-gray-600 hover:border-navy' }}">
        {{ $label }}
    </button>
    @endforeach

    {{-- Custom date range --}}
    <div class="flex items-center gap-2">
        <input type="date" name="from"
               value="{{ $range === 'custom' ? \Carbon\Carbon::parse($from)->format('Y-m-d') : '' }}"
               class="h-9 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
        <span class="text-gray-400 text-sm">–</span>
        <input type="date" name="to"
               value="{{ $range === 'custom' ? \Carbon\Carbon::parse($to)->format('Y-m-d') : '' }}"
               class="h-9 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
        <button type="submit" name="range" value="custom"
                class="h-9 px-4 bg-navy hover:bg-navy-dark text-white text-sm font-medium rounded-xl transition">
            Apply
        </button>
    </div>

    {{-- Period label --}}
    <span class="text-xs text-gray-400 ml-auto">
        {{ \Carbon\Carbon::parse($from)->format('M j, Y') }}
        – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}
    </span>
</form>

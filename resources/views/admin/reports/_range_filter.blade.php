{{--
    Reusable range filter bar (auto-submits).
    Usage: @include('admin.reports._range_filter', ['action' => route('admin.reports.sales')])
--}}
<form method="GET" action="{{ $action }}"
      class="flex flex-wrap items-center gap-2 mb-5 print:hidden">

    {{-- Custom mode is picked as soon as a date input is touched --}}
    <input type="hidden" name="range" value="custom">

    {{-- Preset buttons (their own range value overrides the hidden one) --}}
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
               onchange="if (!this.value) return; if (!this.form.to.value) this.form.to.value = this.value; this.form.submit();"
               placeholder="From"
               class="h-9 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
        <span class="text-gray-400 text-sm">–</span>
        <input type="date" name="to"
               value="{{ $range === 'custom' ? \Carbon\Carbon::parse($to)->format('Y-m-d') : '' }}"
               onchange="if (!this.value) return; if (!this.form.from.value) this.form.from.value = this.value; this.form.submit();"
               placeholder="To"
               class="h-9 px-3 rounded-xl border-2 border-gray-200 bg-white text-sm focus:outline-none focus:border-navy">
    </div>

    {{-- Period label --}}
    <span class="text-xs text-gray-400 ml-auto">
        {{ \Carbon\Carbon::parse($from)->format('M j, Y') }}
        – {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}
    </span>
</form>

@php
    // شريط التقدّم كما في التطبيق: أربع خطوات فوق حالات الوزارة.
    $steps = ['بدأت الرحلة', 'بانتظار العدّاد', 'جارية العد', 'اكتمل العد وجاهزة للبيع'];
    $current = $trip->progress_step;
@endphp
<ol class="trip-steps" @if ($trip->isCancelled()) data-cancelled @endif>
    @foreach ($steps as $i => $label)
        @php $n = $i + 1; @endphp
        <li @class(['is-done' => $n < $current, 'is-current' => $n === $current])>
            <span class="dot">@if ($n < $current)@include('partials.icon', ['name' => 'check-check'])@else{{ $n }}@endif</span>
            <span class="lbl">{{ $label }}</span>
        </li>
    @endforeach
</ol>

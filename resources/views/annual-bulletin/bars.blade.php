@php
    /** أقصى قيمة هي مقام العرض؛ لا تكون صفرًا حتى لا يختفي الشريط كله. */
    $max = max(0.0001, (float) collect($items)->max('value'));
@endphp
<div class="bars">
    @forelse ($items as $item)
        <div class="bar-row">
            <span class="name">{{ $item['label'] }}</span>
            <span class="bar-track"><span class="bar-fill" style="width:{{ max(2, $item['value'] / $max * 100) }}%"></span></span>
            <span class="val">{{ $item['display'] }}</span>
        </div>
    @empty
        <p style="padding:24px;text-align:center;font-size:10px;color:#94a3b8">لا توجد بيانات لهذه السنة</p>
    @endforelse
</div>

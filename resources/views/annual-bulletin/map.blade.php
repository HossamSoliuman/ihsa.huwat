@php
    /*
     * الخريطة إسقاط بسيط لنطاق إحداثيات المملكة على صندوق: خط الطول 34–56 شرقًا
     * وخط العرض 16–32 شمالًا. حجم النقطة يعكس كمية الإنتاج نسبةً إلى أعلاها.
     */
    $lngMin = 34; $lngMax = 56; $latMin = 16; $latMax = 32;
    $maxTons = max(0.0001, (float) collect($points)->max('catch_tons'));
@endphp

<div class="map-box" style="height:{{ $height }}">
    <span class="edge" style="right:12px">الخليج العربي</span>
    <span class="edge" style="left:12px">البحر الأحمر</span>
    @foreach ($points as $point)
        @php
            $size = 10 + ($point['catch_tons'] / $maxTons) * 26;
            // الشرق إلى اليمين كما في أي خريطة: كلما زاد خط الطول قلّت المسافة عن الحافة اليمنى.
            $right = ($lngMax - $point['lng']) / ($lngMax - $lngMin) * 100;
            $bottom = ($point['lat'] - $latMin) / ($latMax - $latMin) * 100;
        @endphp
        <span class="pt"
              title="{{ $point['port'] }} — {{ number_format($point['catch_tons'], 1) }} طن"
              style="right:{{ max(0, min(100, $right)) }}%;bottom:{{ max(0, min(100, $bottom)) }}%;width:{{ $size }}px;height:{{ $size }}px"></span>
    @endforeach
</div>
<p class="map-note">
    @if ($points->isEmpty())
        لا توجد موانئ لها إحداثيات وإنتاج مسجّل في هذه السنة.
    @else
        تمثيل إحداثي لمواقع الموانئ ذات البيانات الجغرافية، وحجم النقطة يعكس كمية الإنتاج.
    @endif
</p>

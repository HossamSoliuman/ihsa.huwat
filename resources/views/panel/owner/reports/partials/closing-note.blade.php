{{--
    تنبيه الأشهر المفتوحة في الفترة: أرقامها معاينة إغلاقها وتتغير حتى يُقفل
    الشهر، ورابط الإقفال الشهري. `months` أشهر الفترة التي بدأت، و`closed` المقفل منها.
--}}
@if ($months > $closed)
    <div class="report-prompt" style="margin-bottom:1.25rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem">
        <span>الأشهر المقفلة في الفترة: {{ $closed }} من {{ $months }} — أرقام الأشهر المفتوحة معاينة لإغلاقها وتتغير حتى يُقفل الشهر.</span>
        <a href="{{ route('panel.owner.month-closings') }}" class="btn btn-outline" style="padding:.3rem .6rem;font-size:.74rem">@include('partials.icon', ['name' => 'lock']) الإقفال الشهري</a>
    </div>
@endif

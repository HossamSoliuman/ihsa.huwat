{{--
    بطاقة الرحلة كما في التطبيق: اسم الرحلة، القارب، موقع الرحلة (الميناء)،
    الترخيص (نوع التصريح)، مدة الرحلة بالأيام، عدد الطاقم — ثم أزرار الفعل
    المتاح الآن: ابدأ/إلغاء لما بانتظاره، وإنهاء وإرسال المخرجات لما في البحر.
--}}
<div class="trip-card">
    <div class="trip-card-head">
        <div>
            <a href="{{ route('panel.captain.trips.show', $trip) }}" class="num" style="font-weight:700;font-size:.95rem">{{ $trip->trip_number }}</a>
            <p class="card-sub">{{ $trip->boat?->name ?? '—' }}</p>
        </div>
        @include('panel.owner.partials.trip-badges', ['trip' => $trip])
    </div>
    <dl class="trip-card-meta">
        <div><dt>موقع الرحلة</dt><dd>{{ $trip->departurePort?->name ?? '—' }}</dd></div>
        <div><dt>الترخيص</dt><dd>{{ $trip->tripType?->name ?? $trip->license_number ?? '—' }}</dd></div>
        <div><dt>مدة الرحلة</dt><dd class="num">{{ $trip->planned_days ? $trip->planned_days.' يوم' : '—' }}</dd></div>
        <div><dt>عدد الطاقم</dt><dd class="num">{{ $trip->crew_count }}</dd></div>
        <div><dt>الانطلاق</dt><dd class="num">{{ ($trip->started_at ?? $trip->departure_time)?->format('Y-m-d H:i') ?? '—' }}</dd></div>
    </dl>
    <div class="trip-card-actions">
        @if ($trip->canStart())
            <form method="POST" action="{{ route('panel.captain.trips.start', $trip) }}" onsubmit="return confirm('بدء الرحلة {{ $trip->trip_number }} الآن؟')">
                @csrf
                <button class="btn btn-primary" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'zap']) ابدأ الرحلة</button>
            </form>
            <a href="{{ route('panel.captain.trips.show', ['trip' => $trip->id, 'cancel' => 1]) }}" class="btn btn-outline" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'x-circle']) إلغاء الرحلة</a>
        @elseif ($trip->canSubmitCatch())
            <a href="{{ route('panel.captain.trips.show', $trip) }}" class="btn btn-primary" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'check-check']) إنهاء الرحلة وإرسال المخرجات</a>
        @else
            <a href="{{ route('panel.captain.trips.show', $trip) }}" class="btn btn-outline" style="padding:.35rem .7rem;font-size:.74rem">التفاصيل</a>
        @endif
    </div>
</div>

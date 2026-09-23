{{--
    بطاقة الرحلة في طابور العدّاد: رقم الرحلة والقارب، ثم ما يهمّه قبل أن
    يفتحها — المالك والكابتن، وميناء العودة، ووقت العودة، والمصيد المعلن —
    ثم الفعل المتاح الآن: استلام لما عاد، وعدّ لما استُلم.
--}}
<div class="trip-card">
    <div class="trip-card-head">
        <div>
            <a href="{{ route('panel.counter.trips.show', $trip) }}" class="num" style="font-weight:700;font-size:.95rem">{{ $trip->trip_number }}</a>
            <p class="card-sub">{{ $trip->boat?->name ?? '—' }} — {{ $trip->owner?->name ?? '—' }}</p>
        </div>
        @include('panel.owner.partials.trip-badges', ['trip' => $trip])
    </div>
    <dl class="trip-card-meta">
        <div><dt>ميناء العودة</dt><dd>{{ $trip->returnPort?->name ?? $trip->departurePort?->name ?? '—' }}</dd></div>
        <div><dt>القبطان</dt><dd>{{ $trip->captain?->name ?? $trip->captain_name ?? '—' }}</dd></div>
        <div><dt>الترخيص</dt><dd>{{ $trip->tripType?->name ?? $trip->license_number ?? '—' }}</dd></div>
        <div><dt>العودة</dt><dd class="num">{{ $trip->return_time?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        <div><dt>المصيد المعلن</dt><dd class="num">{{ $trip->captain_input_kg !== null ? number_format($trip->captain_input_kg, 1).' كجم' : '—' }}</dd></div>
    </dl>
    <div class="trip-card-actions">
        @if ($trip->canReceive())
            <form method="POST" action="{{ route('panel.counter.trips.receive', $trip) }}" onsubmit="return confirm('استلام الرحلة {{ $trip->trip_number }} وبدء عملية العد؟')">
                @csrf
                <button class="btn btn-primary" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'inbox']) استلام الرحلة</button>
            </form>
        @elseif ($trip->status === \App\Models\Trip::COUNTING)
            <a href="{{ route('panel.counter.trips.show', $trip) }}" class="btn btn-primary" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'scale']) عدّ المصيد</a>
        @endif
        <a href="{{ route('panel.counter.trips.report', $trip) }}" class="btn btn-outline" style="padding:.35rem .7rem;font-size:.74rem">@include('partials.icon', ['name' => 'file-text']) التقرير المفصّل</a>
    </div>
</div>

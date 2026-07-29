@extends('layouts.dashboard')

@section('title', 'عمليات الإحصاء')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/employee-operations.css') }}">
@endpush

@section('content')
<div class="operations-deck">
    @if(!$employee || !$assignment)
        <section class="operations-unassigned"><span>NO ACTIVE STATION</span><h1>لا يوجد تكليف تشغيلي اليوم</h1><p>تواصل مع مشرف الميناء لإضافتك إلى مناوبة اليوم.</p></section>
    @else
        <header class="operations-command"><div><span>LIVE COUNTING STATION</span><h1>{{ $assignment->port->name }}</h1><p>{{ config("attendance.shifts.{$assignment->shift->name}") }} · <span dir="ltr">{{ $assignment->shift->start_time }}—{{ $assignment->shift->end_time }}</span></p></div><dl><div><dt>الموظف</dt><dd>{{ auth()->user()->full_name }}</dd></div><div><dt>تاريخ التشغيل</dt><dd dir="ltr">{{ today()->format('Y/m/d') }}</dd></div></dl></header>
        <section class="operations-vitals"><article><span>متوقع</span><strong>{{ $kpi['expected'] }}</strong></article><article><span>وصل</span><strong>{{ $kpi['arrived'] }}</strong></article><article class="is-live"><span>تحت الإحصاء</span><strong>{{ $kpi['counting'] }}</strong></article><article><span>معتمد اليوم</span><strong>{{ $kpi['approved_today'] }}</strong></article><article class="is-warning"><span>بفروقات</span><strong>{{ $kpi['difference_trips'] }}</strong></article><article><span>وزن اليوم</span><strong>{{ $kpi['total_weight'] }}<small>كجم</small></strong></article><article><span>الصناديق</span><strong>{{ $kpi['total_boxes'] }}</strong></article><article><span>متوسط الفرق</span><strong>{{ $kpi['average_difference'] }}<small>%</small></strong></article></section>

        <div class="operations-grid">
            <section class="operations-panel"><header><div><span>INBOUND</span><h2>القوارب المتوقعة</h2></div><b>{{ $expectedTrips->count() }}</b></header><div class="operations-list">@forelse($expectedTrips as $trip)<article><div><strong dir="ltr">{{ $trip->trip_code }}</strong><span>{{ $trip->boat->name }} · {{ $trip->captain->full_name }}</span></div><time dir="ltr">{{ $trip->expected_arrival?->format('H:i') ?? '—' }}</time></article>@empty<p>لا توجد قوارب متوقعة.</p>@endforelse</div></section>
            <section class="operations-panel"><header><div><span>ARRIVED / READY</span><h2>بانتظار بدء الإحصاء</h2></div><b>{{ $arrivedTrips->count() }}</b></header><div class="operations-list">@forelse($arrivedTrips as $trip)<article><div><strong dir="ltr">{{ $trip->trip_code }}</strong><span>{{ $trip->boat->name }} · وصول {{ $trip->actual_arrival?->format('H:i') }}</span></div><form method="post" action="{{ route('dashboard.employee-operations.trips.start', $trip) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">بدء الإحصاء</button></form></article>@empty<p>لا توجد رحلات جاهزة.</p>@endforelse</div></section>
        </div>

        <section class="counting-workbench"><header><div><span>ACTIVE WEIGHING</span><h2>محطة تسجيل المصيد</h2></div><b>{{ $countingTrips->count() }} نشط</b></header>
        @forelse($countingTrips as $trip)<article class="catch-sheet"><div class="catch-sheet-head"><div><span>TRIP</span><h3 dir="ltr">{{ $trip->trip_code }}</h3><p>{{ $trip->boat->name }} · {{ $trip->captain->full_name }}</p></div><time dir="ltr">بدأ {{ $trip->counting_started_at?->format('H:i') }}</time></div><form method="post" action="{{ route('dashboard.employee-operations.trips.catch', $trip) }}">@csrf @method('PUT')<div class="catch-table-wrap"><table><thead><tr><th>الصنف</th><th>بلاغ الكابتن (كجم)</th><th>الوزن الفعلي (كجم)</th><th>الصناديق</th></tr></thead><tbody>@foreach($fishSpecies as $index => $species)<tr><td><strong>{{ $species->name_ar }}</strong><input type="hidden" name="catches[{{ $index }}][species_id]" value="{{ $species->id }}"></td><td><input type="number" step="0.01" min="0" name="catches[{{ $index }}][reported_kg]" aria-label="بلاغ الكابتن لصنف {{ $species->name_ar }}"></td><td><input type="number" step="0.01" min="0" name="catches[{{ $index }}][verified_kg]" aria-label="الوزن الفعلي لصنف {{ $species->name_ar }}"></td><td><input type="number" min="0" name="catches[{{ $index }}][boxes_count]" aria-label="صناديق صنف {{ $species->name_ar }}"></td></tr>@endforeach</tbody></table></div><footer><p>الوزن الفعلي دون بلاغ كابتن يُسجل تلقائيًا كصنف غير مُبلغ.</p><button class="btn btn-primary" type="submit">حفظ وإنهاء الإحصاء</button></footer></form></article>@empty<div class="operations-empty">لا توجد رحلة تحت الإحصاء لديك الآن.</div>@endforelse
        </section>
    @endif
</div>
@endsection

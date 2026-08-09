@extends('layouts.dashboard')

@section('title', 'لوحة المحافظة')
@section('body-class', 'jurisdiction-page')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/jurisdiction.css') }}">
@endpush

@section('content')
<div class="jurisdiction-shell governorate-shell">
    <header class="jurisdiction-hero"><div><span>GOVERNORATE WATCH</span><h1>مركز إشراف المحافظة</h1><p>صورة لحظية للمناوبات والقوارب والفروقات والتنبيهات عبر موانئ المحافظة.</p></div>@if(auth()->user()->role->code !== 'gov_supervisor' && $governorates->count() > 1)<form method="get" class="scope-filter"><label>المحافظة<select name="governorate_id">@foreach($governorates as $item)<option value="{{ $item->id }}" @selected($governorate?->id === $item->id)>{{ $item->name }} — {{ $item->region->name }}</option>@endforeach</select></label><button class="btn btn-primary" type="submit">عرض</button></form>@endif</header>
    @if(!$governorate)<section class="jurisdiction-empty">لا توجد محافظات ضمن نطاقك.</section>@else
        <section class="jurisdiction-context"><span>{{ $governorate->region->name }}</span><strong>{{ $governorate->name }}</strong><small>لقطة تشغيلية لليوم</small></section>
        <section class="jurisdiction-kpis">@foreach([['الموانئ','ports'],['الموظفون','employees'],['الحاضرون','present'],['متوقعة','expected'],['وصلت','arrived'],['قيد الإحصاء','counting'],['معتمدة اليوم','approved'],['بها فروقات','diff_trips']] as [$label,$key])<article><span>{{ $label }}</span><strong>{{ $kpi[$key] ?? 0 }}</strong></article>@endforeach</section>
        <section class="jurisdiction-grid two-one"><article class="command-panel wide"><header><span>PORT THROUGHPUT</span><h2>توزيع رحلات اليوم</h2></header><div class="table-scroll"><table><thead><tr><th>الميناء</th><th>رحلات اليوم</th><th>الوزن المعتمد كجم</th></tr></thead><tbody>@forelse($portRows as $row)<tr><td>{{ $row['port']->name }}</td><td>{{ $row['trips'] }}</td><td>{{ number_format($row['weight'], 1) }}</td></tr>@empty<tr><td colspan="3">لا توجد موانئ.</td></tr>@endforelse</tbody></table></div></article><article class="command-panel alert-panel"><header><span>ACTIVE SIGNALS</span><h2>تنبيهات الغياب والازدحام</h2></header><ul class="signal-list">@forelse($alerts as $alert)<li class="signal-{{ $alert['severity'] }}"><strong>{{ $alert['port']->name }}</strong><span>{{ $alert['message'] }}</span></li>@empty<li class="all-clear">لا توجد تنبيهات تشغيلية حالياً.</li>@endforelse</ul></article></section>
        <section class="command-panel"><header><span>SHIFT FLOOR</span><h2>المناوبات الحالية</h2><p>متاحون: {{ $shiftRows->filter(fn($row) => $row['attendance']?->status === 'present' && $row['active_trips'] === 0)->count() }} — مشغولون: {{ $shiftRows->filter(fn($row) => $row['active_trips'] > 0)->count() }}</p></header><div class="table-scroll"><table><thead><tr><th>الموظف</th><th>الميناء</th><th>المناوبة</th><th>الحضور</th><th>رحلات نشطة</th></tr></thead><tbody>@forelse($shiftRows as $row)<tr><td>{{ $row['assignment']->employee->user->full_name }}</td><td>{{ $row['port']->name }}</td><td>{{ $row['assignment']->shift->name }}</td><td>{{ $row['attendance'] ? config("attendance.statuses.{$row['attendance']->status}") : 'لم يبدأ' }}</td><td>{{ $row['active_trips'] }}</td></tr>@empty<tr><td colspan="5">لا توجد مناوبات اليوم.</td></tr>@endforelse</tbody></table></div></section>
        <section class="command-panel delayed-panel"><header><span>WAIT THRESHOLD</span><h2>رحلات تنتظر أكثر من 30 دقيقة</h2></header><div class="table-scroll"><table><thead><tr><th>الرحلة</th><th>الميناء</th><th>وقت الوصول</th><th>مدة الانتظار</th></tr></thead><tbody>@forelse($delayedTrips as $trip)<tr><td dir="ltr">{{ $trip->trip_code }}</td><td>{{ $trip->port->name }}</td><td dir="ltr">{{ $trip->actual_arrival->format('Y/m/d H:i') }}</td><td>{{ (int) $trip->actual_arrival->diffInMinutes(now()) }} دقيقة</td></tr>@empty<tr><td colspan="4">لا توجد رحلات متأخرة.</td></tr>@endforelse</tbody></table></div></section>
    @endif
</div>
@endsection

@extends('layouts.dashboard')

@section('title', 'أداء موظفي الإحصاء')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/performance.css') }}">
@endpush

@section('content')
<div class="performance-console">
    <header class="performance-head"><div><span>QUALITY / THROUGHPUT</span><h1>أداء موظفي الإحصاء</h1><p>قياس الإنجاز، سرعة الإحصاء، جودة البيانات واكتمال توثيق الرحلات المعتمدة.</p></div><form method="get"><label>من<input type="date" name="date_from" value="{{ $filters['date_from'] }}"></label><label>إلى<input type="date" name="date_to" value="{{ $filters['date_to'] }}"></label><button class="btn btn-primary" type="submit">تحليل الفترة</button></form></header>
    <section class="performance-kpis"><article><span>الرحلات المعتمدة</span><strong>{{ $kpi['trips'] }}</strong></article><article><span>المصيد المحصى</span><strong>{{ $kpi['weight'] }}<small>كجم</small></strong></article><article><span>متوسط الإحصاء</span><strong>{{ $kpi['average_minutes'] }}<small>د</small></strong></article><article class="is-warning"><span>رحلات بفروقات</span><strong>{{ $kpi['difference_trips'] }}</strong></article><article><span>اكتمال المرفقات</span><strong>{{ $kpi['attachment_completion'] }}<small>%</small></strong></article><article class="{{ $kpi['edits'] > 0 ? 'is-danger' : '' }}"><span>تعديلات بعد الاعتماد</span><strong>{{ $kpi['edits'] }}</strong></article><article><span>الأعلى إنجازًا</span><b>{{ $kpi['top_performer'] ?? '—' }}</b></article><article><span>الأعلى جودة</span><b>{{ $kpi['top_quality'] ?? '—' }}</b></article></section>
    <section class="performance-table-panel"><header><div><span>EMPLOYEE SCORECARD</span><h2>بطاقات الأداء المقارنة</h2></div><b>{{ $performanceRows->count() }} موظف</b></header><div class="performance-table-wrap"><table><thead><tr><th>الموظف</th><th>آخر ميناء</th><th>الرحلات</th><th>الكمية</th><th>متوسط الوقت</th><th>اكتمال التوثيق</th><th>رحلات بفروقات</th><th>التقييم</th></tr></thead><tbody>
        @forelse($performanceRows as $row)<tr><td><strong>{{ $row['employee']->user->full_name }}</strong></td><td>{{ $row['last_port'] ?? '—' }}</td><td>{{ $row['trips_count'] }}</td><td>{{ $row['total_weight'] }} كجم</td><td>{{ $row['average_minutes'] === null ? '—' : $row['average_minutes'].' د' }}</td><td><progress class="performance-meter" value="{{ $row['attachment_completion'] }}" max="100"></progress><small>{{ $row['attachment_completion'] }}%</small></td><td>{{ $row['difference_trips'] }}<small>{{ $row['average_difference'] === null ? '' : ' · '.$row['average_difference'].'%' }}</small></td><td><span class="rating rating-{{ $row['rating_tone'] }}">{{ $row['rating'] }}</span></td></tr>
        @empty<tr><td colspan="8" class="performance-empty">لا توجد رحلات معتمدة كافية ضمن الفترة المحددة.</td></tr>@endforelse
    </tbody></table></div></section>
</div>
@endsection

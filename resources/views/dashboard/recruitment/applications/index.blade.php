@extends('layouts.dashboard')

@section('title', 'طلبات التوظيف')

@section('content')
<div class="employment-admin-shell">
    <section class="employment-admin-hero applications-hero"><div><span class="employment-eyebrow">غرفة فرز المرشحين</span><h2>كل قرار موثّق، وكل تعيين يبدأ من طلب مقبول</h2><p>راجع بيانات المتقدم ومرفقاته، ثم انقل الطلب عبر مراحل الاعتماد.</p></div><a class="btn btn-outline" href="{{ route('dashboard.jobs.index') }}">إدارة الفرص</a></section>
    <nav class="employment-workflow-nav" aria-label="التوظيف"><a href="{{ route('dashboard.jobs.index') }}">الفرص الوظيفية</a><a class="active" href="{{ route('dashboard.applications.index') }}">طلبات المتقدمين</a></nav>

    <section class="employment-kpis" aria-label="مؤشرات الطلبات">
        <article><span>إجمالي الطلبات</span><strong>{{ $stats->total }}</strong></article>
        <article class="tone-live"><span>طلبات جديدة</span><strong>{{ $stats->submitted_count }}</strong></article>
        <article><span>في مسار المراجعة</span><strong>{{ $stats->active_review_count }}</strong></article>
        <article><span>بانتظار إنشاء حساب</span><strong>{{ $stats->accepted_count }}</strong></article>
        <article><span>حسابات منشأة</span><strong>{{ $stats->accounts_count }}</strong></article>
    </section>

    <section class="panel employment-list-panel">
        <header class="employment-section-heading"><div><span>صندوق الطلبات</span><h3>فرز وبحث المتقدمين</h3></div><small>{{ $applications->total() }} نتيجة</small></header>
        <form method="get" class="employment-filter-bar">
            <label class="employment-search-field">بحث<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="الاسم، المرجع، البريد أو الجوال"></label>
            <label>الحالة<select name="status"><option value="">كل الحالات</option>@foreach(config('employment.application_statuses') as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>الفرصة<select name="job_id"><option value="">كل الفرص</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @selected((string) ($filters['job_id'] ?? '') === (string) $job->id)>{{ $job->reference_no }} — {{ $job->title_ar }}</option>@endforeach</select></label>
            <button class="btn btn-outline" type="submit">تطبيق</button><a class="btn btn-outline" href="{{ route('dashboard.applications.index') }}">مسح</a>
        </form>

        @if($applications->isEmpty())
            <div class="employment-empty-state"><strong>لا توجد طلبات مطابقة</strong><p>جرّب تغيير المرشحات أو اختيار فرصة أخرى.</p></div>
        @else
            <div class="employment-table-wrap"><table class="employment-applications-table"><thead><tr><th>المتقدم</th><th>الفرصة</th><th>الخبرة</th><th>تاريخ التقديم</th><th>الحالة</th><th></th></tr></thead><tbody>
            @foreach($applications as $application)
                <tr><td><strong>{{ $application->full_name }}</strong><small dir="ltr">{{ $application->reference_no }} · {{ $application->mobile }}</small></td><td><strong>{{ $application->job->title_ar }}</strong><small>{{ $application->preferredPort?->name ?? $application->job->reference_no }}</small></td><td>{{ $application->experience_years }} سنة</td><td>{{ $application->submitted_at->format('Y-m-d') }}</td><td><span class="employment-status status-{{ $application->status }}">{{ config("employment.application_statuses.{$application->status}") }}</span></td><td><a class="btn btn-outline btn-sm" href="{{ route('dashboard.applications.show', $application) }}">فتح الملف</a></td></tr>
            @endforeach
            </tbody></table></div>
            {{ $applications->links() }}
        @endif
    </section>
</div>
@endsection

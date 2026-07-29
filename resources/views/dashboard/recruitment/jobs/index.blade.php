@extends('layouts.dashboard')

@section('title', 'إدارة فرص التوظيف')

@section('content')
<div class="employment-admin-shell">
    <section class="employment-admin-hero">
        <div><span class="employment-eyebrow">مركز الاستقطاب</span><h2>من المسودة إلى التعيين، في مسار واضح</h2><p>أنشئ الفرص وتحكم في فترة استقبال الطلبات من لوحة واحدة.</p></div>
        <div class="employment-hero-actions"><a class="btn btn-primary" href="{{ route('dashboard.jobs.create') }}">إضافة فرصة جديدة</a><a class="btn btn-outline" href="{{ route('dashboard.applications.index') }}">طلبات التوظيف</a><a class="btn btn-outline" href="{{ route('jobs.index') }}" target="_blank" rel="noopener">الصفحة العامة</a></div>
    </section>

    <nav class="employment-workflow-nav" aria-label="التوظيف"><a class="active" href="{{ route('dashboard.jobs.index') }}">الفرص الوظيفية</a><a href="{{ route('dashboard.applications.index') }}">طلبات المتقدمين</a></nav>

    <section class="employment-kpis" aria-label="مؤشرات الفرص">
        <article><span>جميع الفرص</span><strong>{{ $stats->total }}</strong></article>
        <article class="tone-live"><span>متاحة الآن</span><strong>{{ $stats->open_count }}</strong></article>
        <article><span>مسودات</span><strong>{{ $stats->draft_count }}</strong></article>
        <article><span>مغلقة</span><strong>{{ $stats->closed_count }}</strong></article>
        <article><span>الأرشيف</span><strong>{{ $stats->archived_count }}</strong></article>
    </section>

    <section class="panel employment-list-panel">
        <header class="employment-section-heading"><div><span>سجل الفرص</span><h3>إدارة الإعلانات ودورة النشر</h3></div><small>{{ $jobs->total() }} نتيجة</small></header>
        <form method="get" class="employment-filter-bar">
            <label class="employment-search-field">بحث<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="المرجع، المسمى، الإدارة أو المدينة"></label>
            <label>الحالة<select name="status"><option value="">كل الحالات</option>@foreach(config('employment.job_statuses') as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select></label>
            <button class="btn btn-outline" type="submit">تطبيق</button><a class="btn btn-outline" href="{{ route('dashboard.jobs.index') }}">مسح</a>
        </form>

        @if($jobs->isEmpty())
            <div class="employment-empty-state"><strong>لا توجد فرص مطابقة</strong><p>أنشئ فرصة جديدة أو غيّر مرشحات البحث.</p></div>
        @else
            <div class="employment-table-wrap"><table class="employment-applications-table"><thead><tr><th>الفرصة</th><th>الموقع</th><th>الطلبات</th><th>الحالة</th><th>الإجراءات</th></tr></thead><tbody>
            @foreach($jobs as $job)
                <tr>
                    <td><strong>{{ $job->title_ar }}</strong><small dir="ltr">{{ $job->reference_no }}</small></td>
                    <td>{{ $job->port?->name ?? $job->city ?? 'غير محدد' }}</td>
                    <td>{{ $job->applications_count }} <small>({{ $job->accepted_applications_count }} مقبول)</small></td>
                    <td><span class="employment-status status-{{ $job->status }}">{{ config("employment.job_statuses.{$job->status}") }}</span></td>
                    <td><div class="employment-row-actions">
                        <a class="btn btn-outline btn-sm" href="{{ route('dashboard.jobs.edit', $job) }}">تعديل</a>
                        @if($job->status === 'open')<a class="btn btn-outline btn-sm" href="{{ route('jobs.show', $job) }}" target="_blank" rel="noopener">عرض</a>@endif
                        @php
                            $transitions = match($job->status) {
                                'draft' => ['publish' => 'نشر'],
                                'open' => ['close' => 'إغلاق', 'archive' => 'أرشفة'],
                                'closed' => ['publish' => 'إعادة النشر', 'archive' => 'أرشفة'],
                                'archived' => ['restore' => 'استعادة'],
                                default => [],
                            };
                        @endphp
                        @foreach($transitions as $value => $label)<form method="post" action="{{ route('dashboard.jobs.transition', $job) }}">@csrf @method('PATCH')<input type="hidden" name="transition" value="{{ $value }}"><button class="btn btn-outline btn-sm" type="submit">{{ $label }}</button></form>@endforeach
                    </div></td>
                </tr>
            @endforeach
            </tbody></table></div>
            {{ $jobs->links() }}
        @endif
    </section>
</div>
@endsection

@extends('layouts.employment')

@section('title', 'الوظائف المتاحة')
@section('body-class', 'employment-home-page employment-simple-home')

@section('content')
<section class="employment-simple-hero" aria-labelledby="careers-title">
    <div class="employment-container employment-simple-hero-grid">
        <div class="employment-simple-hero-copy">
            <p class="employment-eyebrow">IHSA / CAREERS</p>
            <h1 id="careers-title">الوظائف المتاحة</h1>
            <p>اختر الوظيفة المناسبة وقدّم طلبك إلكترونياً بخطوات واضحة وآمنة.</p>
            <div class="employment-simple-hero-actions"><a class="employment-button employment-button-primary" href="#available-jobs">عرض الوظائف</a><span><i></i>{{ number_format($jobs->count()) }} وظيفة مفتوحة</span></div>
        </div>
        <aside class="employment-hero-telemetry" aria-label="ملخص الوظائف"><span class="employment-telemetry-label">OPEN POSITIONS</span><strong>{{ number_format($jobs->count()) }}</strong><p>فرصة وظيفية متاحة للتقديم حالياً</p><div class="employment-signal-lines" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i></div></aside>
    </div>
</section>

<section class="employment-container employment-simple-jobs" id="available-jobs" aria-labelledby="available-jobs-title">
    <header class="employment-simple-section-heading"><div><p class="employment-eyebrow">الفرص الحالية</p><h2 id="available-jobs-title">اختر الوظيفة</h2></div><span>{{ $jobs->count() }} نتيجة</span></header>
    @if($jobs->isEmpty())
        <div class="employment-empty-state"><h3>لا توجد وظائف مفتوحة حالياً</h3><p>ستظهر الوظائف الجديدة هنا عند نشرها.</p></div>
    @else
        <div class="employment-simple-job-grid">
            @foreach($jobs as $job)
                <article class="employment-simple-job-card">
                    <div class="employment-simple-job-topline"><span>{{ config('employment.types.'.$job->employment_type, $job->employment_type) }}</span><time>{{ $job->application_deadline ? 'حتى '.$job->application_deadline->format('Y/m/d') : 'التقديم مفتوح' }}</time></div>
                    <h3>{{ $job->title_ar }}</h3>
                    <p class="employment-simple-job-meta">{{ $job->department ?: 'غير محدد' }} <span>•</span> {{ $job->port?->name ?: ($job->city ?: 'مواقع متعددة') }}</p>
                    <p class="employment-simple-job-summary">{{ str($job->summary)->limit(150) }}</p>
                    <div class="employment-simple-job-actions"><a class="employment-button employment-button-primary" href="{{ route('applications.create', $job) }}">قدّم الآن</a><a class="employment-text-link" href="{{ route('jobs.show', $job) }}">التفاصيل</a></div>
                </article>
            @endforeach
        </div>
    @endif
</section>

<section class="employment-simple-process" id="application-process"><div class="employment-container"><header class="employment-simple-section-heading"><div><p class="employment-eyebrow">التقديم</p><h2>أربع خطوات فقط</h2></div></header><ol><li><span>01</span><strong>اختر الوظيفة</strong></li><li><span>02</span><strong>أدخل بياناتك</strong></li><li><span>03</span><strong>ارفع المرفقات</strong></li><li><span>04</span><strong>راجع وأرسل</strong></li></ol></div></section>
@endsection

@extends('layouts.employment')

@section('title', $job->title_ar)
@section('description', $job->summary)
@section('body-class', 'employment-job-page employment-hud-public')

@section('content')
<section class="employment-page-hero"><div class="employment-container"><a class="employment-back-link" href="{{ route('jobs.index') }}">العودة إلى الوظائف</a><p class="employment-eyebrow">{{ $job->reference_no }}</p><h1>{{ $job->title_ar }}</h1><p>{{ $job->summary }}</p><div class="employment-job-tags"><span>{{ config('employment.types.'.$job->employment_type) }}</span><span>{{ $job->port?->name ?: ($job->city ?: 'مواقع متعددة') }}</span><span>{{ $job->vacancies }} شاغر</span></div></div></section>
<section class="employment-container employment-job-detail-layout">
    <article class="employment-job-content">
        <section><h2>عن الوظيفة</h2><p class="preserve-lines">{{ $job->description }}</p></section>
        @if($job->responsibilities)<section><h2>المسؤوليات</h2><ul>@foreach(preg_split('/\R/u', trim($job->responsibilities)) as $line)<li>{{ ltrim($line, " •-\t") }}</li>@endforeach</ul></section>@endif
        <section><h2>المتطلبات</h2><ul>@foreach(preg_split('/\R/u', trim($job->requirements)) as $line)<li>{{ ltrim($line, " •-\t") }}</li>@endforeach</ul></section>
    </article>
    <aside class="employment-job-sidebar"><div class="employment-application-summary"><small>الفرصة الوظيفية</small><h2>{{ $job->title_ar }}</h2><dl><div><dt>الإدارة</dt><dd>{{ $job->department ?: 'غير محدد' }}</dd></div><div><dt>آخر موعد</dt><dd>{{ $job->application_deadline?->format('Y/m/d') ?: 'مفتوح' }}</dd></div><div><dt>الراتب</dt><dd>@if($job->salary_min && $job->salary_max){{ number_format($job->salary_min) }}–{{ number_format($job->salary_max) }} ريال@else حسب سلم الرواتب @endif</dd></div></dl><a class="employment-button employment-button-primary" href="{{ route('applications.create', $job) }}">التقديم على الوظيفة</a></div></aside>
</section>
@endsection

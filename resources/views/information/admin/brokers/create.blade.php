@extends('layouts.information-admin')

@section('title', 'الدلالين · دلال جديد')

@section('content')
<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.brokers.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى الدلالين</span>
        </a>
        <h1>دلال جديد</h1>
        <p class="info-admin-header-meta">
            <span>اختر السوق أولاً</span>
            <span aria-hidden="true">·</span>
            <span>نوع الدلال يحدد الحقول المطلوبة</span>
        </p>
    </div>
</header>

@if ($markets->isEmpty())
    <section class="info-form-card info-admin-panel">
        <div class="info-admin-panel-head">
            <h2>لا توجد أسواق مسجلة</h2>
        </div>
        <p class="info-admin-empty">
            الدلال يُسجَّل على سوق، فأضف سوق سمك أولاً.
            <a class="info-lookup-action" href="{{ route('information.admin.markets.create') }}">إضافة سوق</a>
        </p>
    </section>
@else
    <section class="info-form-card info-admin-panel">
        <div class="info-admin-panel-head">
            <h2>بيانات الدلال</h2>
        </div>

        <x-information.broker-form :markets="$markets" :stalls="$stalls" :nationalities="$nationalities" :job-titles="$jobTitles" />
    </section>
@endif
@endsection

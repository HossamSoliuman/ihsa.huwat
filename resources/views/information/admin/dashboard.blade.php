@extends('layouts.information-admin')

@section('title', 'لوحة مؤشرات مركز المعلومات')

@section('content')
<header class="info-dashboard-header">
    <div>
        <p class="info-dashboard-kicker">مرصد السجلات والتشغيل</p>
        <h1>لوحة مؤشرات مركز المعلومات</h1>
    </div>
    <time datetime="{{ $generatedAt->toIso8601String() }}">آخر تحديث {{ $generatedAt->diffForHumans() }}</time>
</header>

@include('information.admin.dashboard.filters')
@include('information.admin.dashboard.census')
@include('information.admin.dashboard.review-health')
@include('information.admin.dashboard.geography')
@include('information.admin.dashboard.fleet')
@include('information.admin.dashboard.market')
@include('information.admin.dashboard.licence-expiry')
@include('information.admin.dashboard.data-completeness')
@include('information.admin.dashboard.attention')

<div class="info-chart-tooltip" role="tooltip" hidden data-chart-tooltip></div>
@endsection

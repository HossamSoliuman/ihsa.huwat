@extends('layouts.admin')

@section('title', config('info.title'))

@section('content')
    <div class="page-header">
        <div class="icon">@include('admin.partials.icon', ['name' => 'shield'])</div>
        <div>
            <h1>{{ config('info.title') }}</h1>
            <p>{{ config('info.subtitle') }}</p>
        </div>
    </div>

    <div class="notice">
        @include('admin.partials.icon', ['name' => 'layers'])
        <p>هذه اللوحة لإدارة <strong>البيانات الأساسية (Master Data)</strong> فقط. لا تُعدّل بيانات الرحلات أو المصيد المعتمد مباشرةً — تمرّ عبر مسارها التشغيلي مع سجل العمليات.</p>
    </div>

    <div class="tabbar">
        @foreach ($tabs as $key => $tab)
            <a class="tabbar-item @if ($key === $activeTab) is-active @endif" href="{{ route('admin.tab', $key) }}">
                @include('admin.partials.icon', ['name' => $tab['icon']])
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <div class="panel">
        @include($panel['view'], $panel)
    </div>
@endsection
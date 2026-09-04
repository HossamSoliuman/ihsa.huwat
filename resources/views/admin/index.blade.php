@extends('layouts.admin')

@section('title', config('info.title'))

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('admin.partials.icon', ['name' => 'shield'])</div>
            <div>
                <h1>{{ config('info.title') }}</h1>
                <p>{{ config('info.subtitle') }}</p>
            </div>
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

    {{-- اللوحة بطاقةُ اللوحة نفسها: خطّ شعري وأقواس زوايا، و.panel يزيدها فاصلها. --}}
    <div class="card panel">
        @include($panel['view'], $panel)
    </div>
@endsection

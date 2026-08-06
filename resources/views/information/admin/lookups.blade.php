@extends('layouts.information-admin')

@section('title', 'الإعدادات')

@section('content')
<header class="info-admin-header">
    <h1>الإعدادات</h1>
</header>

<nav class="info-admin-tablist info-lookup-tablist" aria-label="أقسام الإعدادات">
    @foreach ($tabs as $tabKey => $tabLabel)
        <a class="info-admin-tab @if ($tab === $tabKey) is-active @endif"
           href="{{ route('information.admin.lookups.index', ['tab' => $tabKey]) }}"
           @if ($tab === $tabKey) aria-current="page" @endif>
            {{ $tabLabel }}
        </a>
    @endforeach
</nav>

@if ($optionLists !== [])
    <div class="info-lookup-stack">
        @foreach ($optionLists as $optionList)
            <x-information.option-list :list="$optionList['key']" :title="$optionList['title']" :options="$optionList['options']" />
        @endforeach
    </div>
@else
    @include('information.admin.lookups.'.$tab)
@endif
@endsection

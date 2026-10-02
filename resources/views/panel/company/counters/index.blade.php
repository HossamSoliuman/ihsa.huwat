@extends('layouts.app')

@section('title', 'العدّادون')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'users'])</div>
            <div>
                <h1>العدّادون</h1>
                <p>عدّادو {{ $company->name }} ونشاطهم — أوقف أحدهم أو انقله بين موانئ الشركة</p>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $p)<option value="{{ $p->id }}" @selected(request('port') === (string) $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">الكل</option>
                <option value="active" @selected(request('status') === 'active')>عامل</option>
                <option value="suspended" @selected(request('status') === 'suspended')>موقوف</option>
            </select>
        </label>
        <a href="{{ route('panel.company.counters') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    @include('panel.company.partials.activity-table', ['counters' => $counters, 'actions' => true])
@endsection

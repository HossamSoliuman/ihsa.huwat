@extends('layouts.app')

@section('title', 'النشرة السنوية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'book-open'])</div>
            <div>
                <h1>النشرة السنوية للمصايد البحرية</h1>
                <p>تقرير سنوي رسمي من ست عشرة صفحة — يتولّد آليًا من بيانات النظام وقابل للطباعة وحفظ PDF</p>
            </div>
        </div>
        <form method="GET" class="actions">
            <select class="select" name="year" onchange="this.form.submit()" style="width:auto">
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>
                @endforeach
            </select>
            <a href="{{ route('stats.annual-bulletin', ['year' => $year]) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'refresh-cw']) تحديث</a>
            <a href="{{ route('stats.annual-bulletin.print', ['year' => $year]) }}" target="_blank" rel="noopener" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) طباعة / حفظ PDF</a>
        </form>
    </div>

    @if ($report['totals']['trips_records'] === 0)
        <div class="pending-card" style="margin-bottom:1.25rem">
            @include('partials.icon', ['name' => 'book-open'])
            <h3>لا توجد سجلات مصيد لسنة {{ $year }}</h3>
            <p>تُنشأ النشرة من سجلات المصيد المسجّلة على الرحلات. تظهر الصفحات أدناه بقيم صفرية حتى تُسجَّل بيانات هذه السنة.</p>
        </div>
    @endif

    @include('annual-bulletin.styles')
    @include('annual-bulletin.pages')
@endsection

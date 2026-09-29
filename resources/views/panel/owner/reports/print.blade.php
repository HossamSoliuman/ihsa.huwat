@extends('layouts.sheet')

@section('title', 'hawat_'.$key.'_'.($file_suffix ?? now()->format('Y-m-d')))
@section('orientation', $orientation ?? 'portrait')
@section('width', ($orientation ?? 'portrait') === 'landscape' ? '1100px' : '800px')

@section('content')
    @include('panel.owner.reports.partials.sheet-head')
    @include('panel.owner.reports.partials.sheet-kpis', ['kpis' => $kpis ?? []])

    @foreach ($tables as $table)
        @include('panel.owner.reports.partials.sheet-table', ['table' => $table])
    @endforeach

    @if (! empty($notes))
        <ul class="notes">
            @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
        </ul>
    @endif

    <div class="signs">
        <div><span></span>المحاسب</div>
        <div><span></span>المراجع</div>
        <div><span></span>المالك</div>
    </div>
@endsection

@extends('layouts.app')

@section('title', $report['title'])

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => $report['icon']])</div>
            <div>
                <h1>{{ $report['title'] }}</h1>
                <p>
                    {{ $profile->display_name }}@if ($profile->dakka_name) — {{ $profile->dakka_name }}@endif
                    — {{ isset($report['filters']['from']) || isset($report['filters']['to']) ? 'الفترة '.($report['filters']['from'] ?? '…').' — '.($report['filters']['to'] ?? '…') : 'كل الفترات' }}
                    — أُنشئ {{ now()->format('Y-m-d H:i') }}
                </p>
            </div>
        </div>
        <div class="actions">
            <button type="button" class="btn btn-primary" onclick="window.print()">@include('partials.icon', ['name' => 'printer']) طباعة</button>
            <a href="{{ route('panel.dalal.reports') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'arrow-left-right']) التقارير</a>
        </div>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>@foreach ($report['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        @foreach ($report['columns'] as $column)
                            @php
                                $value = $row[$column['key']] ?? null;
                            @endphp
                            <td class="{{ $column['format'] === 'text' ? '' : 'num' }}">
                                @switch($column['format'])
                                    @case('money') {{ number_format((float) $value, 2) }} @break
                                    @case('kg') {{ number_format((float) $value, 2) }} @break
                                    @case('int') {{ number_format((int) $value) }} @break
                                    @default {{ $value ?? '—' }}
                                @endswitch
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['columns']) }}" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا بيانات في هذه الفترة</td></tr>
                @endforelse
            </tbody>
            @if ($report['rows'] !== [] && $report['totals'] !== [])
                <tfoot>
                    <tr style="font-weight:700">
                        @foreach ($report['columns'] as $column)
                            <td class="num">
                                @if ($loop->first)
                                    المجموع
                                @elseif (array_key_exists($column['key'], $report['totals']))
                                    {{ $column['format'] === 'int' ? number_format($report['totals'][$column['key']]) : number_format($report['totals'][$column['key']], 2) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection

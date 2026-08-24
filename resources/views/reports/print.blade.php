@extends('layouts.print')

@section('title', $report['title'])

@section('content')
    <div class="doc-head">
        <h1>{{ $report['title'] }}</h1>
        <div class="sub">{{ $report['desc'] }}</div>
        <div class="meta">
            {{ config('hawat.ministry') }} — {{ config('hawat.sector') }} ·
            تاريخ الإنشاء: {{ now()->format('Y-m-d H:i') }} ·
            عدد السجلات: <span class="count">{{ number_format($total) }}</span>
        </div>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">لا توجد بيانات في هذا التقرير</div>
    @else
        <table>
            <thead>
                <tr>@foreach (array_keys($rows->first()) as $header)<th>{{ $header }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>@foreach ($row as $cell)<td>{{ is_float($cell) ? number_format($cell, 1) : ($cell ?? '—') }}</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
        @if ($total > $rows->count())
            <p style="margin-top:8px;font-size:10px;color:#94a3b8">تعرض هذه النسخة أول {{ number_format($rows->count()) }} صفًا من أصل {{ number_format($total) }}. التصدير بصيغة CSV يحتوي السجلات كاملة.</p>
        @endif
    @endif

    <div class="doc-foot">نظام {{ config('hawat.name') }} — {{ config('hawat.tagline') }}</div>
@endsection

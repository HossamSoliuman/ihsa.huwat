@extends('layouts.print')

@section('title', 'تقرير الإنتاج '.$report['period_label'])

@php $totals = $report['totals']; @endphp

@section('content')
    <div class="doc-head">
        <h1>{{ config('hawat.ministry') }} — {{ config('hawat.sector') }}</h1>
        <div class="sub">تقرير الإنتاج السمكي {{ $report['period_label'] }} — {{ $region }}</div>
        <div class="meta">تم توليده تلقائيًا من نظام {{ config('hawat.name') }} — {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="totals">
        <div class="card"><div class="v">{{ number_format($totals['catch_kg'], 1) }}</div><div class="l">إجمالي المصيد (كجم)</div></div>
        <div class="card"><div class="v">{{ $totals['approved_kg'] ? number_format($totals['approved_kg'], 1) : '—' }}</div><div class="l">المعتمد (كجم)</div></div>
        <div class="card"><div class="v">{{ number_format($totals['trips']) }}</div><div class="l">عدد الرحلات</div></div>
        <div class="card"><div class="v">{{ number_format($totals['boats']) }}</div><div class="l">القوارب</div></div>
        <div class="card"><div class="v">{{ number_format($totals['species']) }}</div><div class="l">عدد الأنواع</div></div>
    </div>

    <h2>الإنتاج حسب المنطقة</h2>
    <table>
        <thead><tr><th>المنطقة</th><th>المصيد (كجم)</th><th>الرحلات</th><th>الموانئ</th><th>القوارب</th></tr></thead>
        <tbody>
            @forelse ($report['by_region'] as $row)
                <tr><td>{{ $row['region'] }}</td><td>{{ number_format($row['catch_kg'], 1) }}</td><td>{{ $row['trips'] }}</td><td>{{ $row['ports'] }}</td><td>{{ $row['boats'] }}</td></tr>
            @empty
                <tr><td colspan="5" class="empty">لا توجد بيانات لهذه الفترة</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>الإنتاج حسب الميناء</h2>
    <table>
        <thead><tr><th>الميناء</th><th>المحافظة</th><th>المنطقة</th><th>المصيد (كجم)</th><th>الرحلات</th><th>القوارب</th><th>الأنواع</th></tr></thead>
        <tbody>
            @forelse ($report['by_port'] as $row)
                <tr><td>{{ $row['port'] }}</td><td>{{ $row['governorate'] }}</td><td>{{ $row['region'] }}</td><td>{{ number_format($row['catch_kg'], 1) }}</td><td>{{ $row['trips'] }}</td><td>{{ $row['boats'] }}</td><td>{{ $row['species_count'] }}</td></tr>
            @empty
                <tr><td colspan="7" class="empty">لا توجد بيانات لهذه الفترة</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>أبرز الأنواع السمكية</h2>
    <table>
        <thead><tr><th>النوع</th><th>المصيد (كجم)</th><th>عدد السجلات</th><th>الرحلات</th></tr></thead>
        <tbody>
            @forelse ($report['by_species'] as $row)
                <tr><td>{{ $row['species'] }}</td><td>{{ number_format($row['catch_kg'], 1) }}</td><td>{{ $row['records'] }}</td><td>{{ $row['trips'] }}</td></tr>
            @empty
                <tr><td colspan="4" class="empty">لا توجد بيانات لهذه الفترة</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-foot">نظام {{ config('hawat.name') }} — {{ config('hawat.tagline') }} · تقرير {{ $report['period_label'] }}</div>
@endsection

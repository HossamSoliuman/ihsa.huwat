@extends('layouts.app')

@section('title', 'التقارير')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-text'])</div>
            <div>
                <h1>التقارير</h1>
            </div>
        </div>
        <div class="actions">
            <span class="badge badge-info">{{ $total }} تقرير</span>
        </div>
    </div>

    @foreach ($groups as $group)
        <section>
            <div class="section-head">
                <span class="ico">@include('partials.icon', ['name' => $group['icon']])</span>
                <h2>{{ $group['title'] }}</h2>
                <span class="line"></span>
                <span class="count-pill">{{ count($group['items']) }} تقارير · {{ number_format($group['records']) }} سجل</span>
            </div>
            <div class="portal-grid">
                @foreach ($group['items'] as $report)
                    <div class="report-card">
                        <span class="accent {{ $group['tone'] }}"></span>
                        <div class="body">
                            <div class="lead">
                                <div class="kpi-icon {{ $group['tone'] }}">@include('partials.icon', ['name' => $report['icon']])</div>
                                <div style="min-width:0;flex:1">
                                    <h3>{{ $report['title'] }}</h3>
                                    <p class="desc">{{ $report['desc'] }}</p>
                                </div>
                            </div>
                            <div style="margin-top:.75rem">
                                <span class="badge {{ $report['count'] > 0 ? 'badge-ok' : 'badge-info' }}">{{ number_format($report['count']) }} سجل</span>
                            </div>
                            <div class="actions">
                                @if ($report['id'] === 'bulletin')
                                    <a href="{{ route('stats.annual-bulletin') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'book-open']) فتح النشرة</a>
                                @elseif ($report['count'] === 0)
                                    <span class="btn btn-outline" style="opacity:.45;cursor:not-allowed">لا توجد بيانات</span>
                                @else
                                    <a href="{{ route('stats.reports.export', $report['id']) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-spreadsheet']) CSV</a>
                                    <a href="{{ route('stats.reports.print', $report['id']) }}" target="_blank" rel="noopener" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) طباعة</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="card">
        <p style="font-size:.75rem;line-height:1.9;color:hsl(var(--muted-foreground))">
            لكل تقرير أعمدته المسمّاة بالعربية ومصدره الصريح، فيخرج ملف CSV وصفحة الطباعة بالشكل نفسه.
            نسخة الطباعة تعرض أول 500 صف وتفتح نافذة الطباعة تلقائيًا؛ اختر «حفظ كـ PDF» من وجهة الطباعة للحصول على ملف PDF.
            ملف CSV يحتوي السجلات كاملة بلا حدّ.
        </p>
    </div>
@endsection

{{--
    رأس صفحة التقرير كما في hispa: العنوان ووصفه، وزر الطباعة أعلى الصفحة
    في التقريرين اللذين يضعانه هناك (قائمة الأرباح وملخص الشهر).
--}}
<div class="page-header">
    <div class="lead">
        <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
        <div>
            <h1>{{ $meta['title'] }}</h1>
            @if ($description ?? true)<p>{{ $meta['description'] }}</p>@endif
        </div>
    </div>
    @if ($print ?? false)
        <div class="actions">
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-outline">@include('partials.icon', ['name' => 'printer']) طباعة</a>
        </div>
    @endif
</div>

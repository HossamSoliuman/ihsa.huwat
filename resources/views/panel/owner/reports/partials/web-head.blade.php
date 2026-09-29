{{-- رأس صفحة التقرير: العنوان، والعودة للمركز، والطباعة وExcel بالتصفية نفسها. --}}
<div class="page-header">
    <div class="lead">
        <div class="icon-wrap">@include('partials.icon', ['name' => $meta['icon']])</div>
        <div>
            <h1>{{ $meta['title'] }}</h1>
            <p>{{ $meta['description'] }}@if (! empty($period)) — {{ $period }}@endif</p>
        </div>
    </div>
    <div class="actions">
        <a href="{{ route('panel.owner.reports') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-chart']) كل التقارير</a>
        @if ($ready ?? true)
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'export'] + $query) }}" class="btn btn-outline">@include('partials.icon', ['name' => 'file-spreadsheet']) Excel</a>
            <a href="{{ route('panel.owner.reports.show', ['report' => $key, 'mode' => 'print'] + $query) }}" target="_blank" class="btn btn-primary">@include('partials.icon', ['name' => 'printer']) طباعة / PDF</a>
        @endif
    </div>
</div>

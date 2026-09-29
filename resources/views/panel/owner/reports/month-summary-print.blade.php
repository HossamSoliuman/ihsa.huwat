@extends('layouts.sheet')

@section('title', 'hawat_month_summary_'.$file_suffix)
@section('orientation', 'portrait')

@section('content')
    @php $f = $figures; @endphp
    @include('panel.owner.reports.partials.sheet-head', ['title' => 'الملخص الشهري — '.$label])

    @include('panel.owner.reports.partials.sheet-kpis', ['kpis' => [
        ['label' => 'صافي الإيراد', 'value' => $f['revenue'], 'format' => 'money'],
        ['label' => 'المصروفات', 'value' => $f['total_expenses'], 'format' => 'money'],
        ['label' => 'نصيب الطاقم', 'value' => $f['crew_pool'], 'format' => 'money'],
        ['label' => 'صافي المالك', 'value' => $f['owner_net'], 'format' => 'money', 'strong' => true],
    ]])

    <div class="section">قائمة الشهر</div>
    @include('panel.owner.reports.partials.sheet-statement')

    @include('panel.owner.reports.partials.sheet-table', ['table' => $boats, 'empty' => 'لا نشاط للقوارب في هذا الشهر'])
    @include('panel.owner.reports.partials.sheet-table', ['table' => $species, 'empty' => 'لا مبيعات في هذا الشهر'])

    <ul class="notes">
        @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
    </ul>

    <div class="signs">
        <div><span></span>المحاسب</div>
        <div><span></span>المراجع</div>
        <div><span></span>المالك</div>
    </div>
@endsection

@extends('layouts.sheet')

@section('title', 'hawat_profit_loss_'.$file_suffix)
@section('orientation', 'portrait')

@section('content')
    @php $f = $figures; @endphp
    @include('panel.owner.reports.partials.sheet-head')
    @include('panel.owner.reports.partials.sheet-kpis', ['kpis' => [
        ['label' => 'صافي الإيراد', 'value' => $f['revenue'], 'format' => 'money'],
        ['label' => 'المصروفات', 'value' => $f['total_expenses'], 'format' => 'money'],
        ['label' => 'الربح التشغيلي', 'value' => $f['operating'], 'format' => 'money'],
        ['label' => $boat ? 'نصيبك من القارب' : 'صافي المالك', 'value' => $f['owner_net'], 'format' => 'money', 'strong' => true],
    ]])

    @include('panel.owner.reports.partials.sheet-statement')

    @if (count($months['rows']) > 1)
        @include('panel.owner.reports.partials.sheet-table', ['table' => $months])
    @endif

    <ul class="notes">
        @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
    </ul>

    <div class="signs">
        <div><span></span>المحاسب</div>
        <div><span></span>المراجع</div>
        <div><span></span>المالك</div>
    </div>
@endsection

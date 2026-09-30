@extends('layouts.app')

@section('title', 'التقارير')

@section('content')
    @php
        $tones = ['primary' => 'primary', 'info' => 'info', 'success' => 'success', 'warning' => 'warning'];
        $crewUrl = fn ($id) => route('panel.owner.crew-pay.statement', $id);
    @endphp

    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'file-chart'])</div>
            <div>
                <h1>التقارير</h1>
            </div>
        </div>
    </div>

    @foreach ($groups as $group => $groupTitle)
        <h2 style="font-size:.95rem;font-weight:700;margin:1.25rem 0 .75rem">{{ $groupTitle }}</h2>
        <div style="display:grid;gap:var(--gap);grid-template-columns:repeat(auto-fit,minmax(15rem,1fr))">
            @foreach ($reports as $key => $report)
                @continue($report['group'] !== $group)
                @if ($group !== 'accounts')
                    <a href="{{ route('panel.owner.reports.show', $key) }}" class="report-card" style="text-decoration:none;color:inherit">
                        <span class="accent {{ $tones[$report['tone']] }}"></span>
                        <div class="body">
                            <div class="lead">
                                <div class="kpi-icon {{ $tones[$report['tone']] }}">@include('partials.icon', ['name' => $report['icon']])</div>
                                <div style="min-width:0;flex:1">
                                    <h3>{{ $report['title'] }}</h3>
                                </div>
                            </div>
                        </div>
                    </a>
                @else
                    @php
                        $isCustomer = $key === 'customer-statement';
                        $parties = $isCustomer ? $customers : $vendors;
                        $param = $isCustomer ? 'customer_id' : 'vendor_id';
                    @endphp
                    <form method="GET" action="{{ route('panel.owner.reports.show', $key) }}" class="report-card">
                        <span class="accent {{ $tones[$report['tone']] }}"></span>
                        <div class="body" style="display:grid;gap:.6rem">
                            <div class="lead">
                                <div class="kpi-icon {{ $tones[$report['tone']] }}">@include('partials.icon', ['name' => $report['icon']])</div>
                                <div style="min-width:0;flex:1">
                                    <h3>{{ $report['title'] }}</h3>
                                </div>
                            </div>
                            <div style="display:flex;gap:.5rem">
                                <select class="select" name="{{ $param }}" required aria-label="{{ $report['title'] }}" style="flex:1">
                                    <option value="">{{ $isCustomer ? 'اختر العميل' : 'اختر المورد' }}</option>
                                    @foreach ($parties as $party)<option value="{{ $party->id }}">{{ $party->name }}</option>@endforeach
                                </select>
                                <button class="btn btn-outline" @disabled($parties->isEmpty())>عرض</button>
                            </div>
                        </div>
                    </form>
                @endif
            @endforeach

            @if ($group === 'accounts')
                <div class="report-card">
                    <span class="accent info"></span>
                    <div class="body" style="display:grid;gap:.6rem">
                        <div class="lead">
                            <div class="kpi-icon info">@include('partials.icon', ['name' => 'users'])</div>
                            <div style="min-width:0;flex:1">
                                <h3>كشف حساب فرد من الطاقم</h3>
                            </div>
                        </div>
                        <div style="display:flex;gap:.5rem">
                            <select class="select" id="crewPick" aria-label="الفرد" style="flex:1">
                                <option value="">اختر الفرد</option>
                                @foreach ($fishers as $fisher)<option value="{{ $crewUrl($fisher->id) }}">{{ $fisher->name }}@if ($fisher->is_captain) (كابتن)@endif</option>@endforeach
                            </select>
                            <button type="button" class="btn btn-outline" onclick="const v = document.getElementById('crewPick').value; if (v) window.open(v, '_blank')" @disabled($fishers->isEmpty())>عرض</button>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <span class="accent success"></span>
                    <div class="body" style="display:grid;gap:.6rem">
                        <div class="lead">
                            <div class="kpi-icon success">@include('partials.icon', ['name' => 'calculator'])</div>
                            <div style="min-width:0;flex:1">
                                <h3>كشف حساب دلال</h3>
                            </div>
                        </div>
                        <div style="display:flex;gap:.5rem">
                            <select class="select" id="dalalPick" aria-label="الدلال" style="flex:1">
                                <option value="">اختر الدلال</option>
                                @foreach ($dalals as $dalal)<option value="{{ route('panel.owner.dalal-accounts.show', $dalal->id) }}">{{ $dalal->name }}</option>@endforeach
                            </select>
                            <button type="button" class="btn btn-outline" onclick="const v = document.getElementById('dalalPick').value; if (v) window.location = v" @disabled($dalals->isEmpty())>عرض</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endsection

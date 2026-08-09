@extends('layouts.information-admin')

@section('title', 'الموانئ · '.$port->name)

@section('content')
@php
    $berthStates = [
        'available' => ['label' => 'متاح', 'tone' => 'sea'],
        'full' => ['label' => 'ممتلئ', 'tone' => 'danger'],
        'stopped' => ['label' => 'متوقف', 'tone' => 'gold'],
    ];

    $tiles = [
        ['label' => 'الطاقة الاستيعابية', 'value' => number_format($capacity), 'tone' => 'blue'],
        ['label' => 'القوارب الشاغلة', 'value' => number_format($occupied), 'tone' => 'sea'],
        ['label' => 'نسبة التشغيل', 'value' => $occupancy.'%', 'tone' => 'blue'],
        ['label' => 'العاملون النشطون', 'value' => number_format($activeWorkers), 'tone' => 'neutral'],
        ['label' => 'الرخص السارية', 'value' => number_format($validLicenses), 'tone' => 'gold'],
        ['label' => 'المخالفات المفتوحة', 'value' => number_format($openViolations), 'tone' => 'danger'],
    ];
@endphp

<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.ports.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى الموانئ</span>
        </a>
        <h1>{{ $port->name }}</h1>
        <p class="info-admin-header-meta">
            <span>{{ $port->governorate->region->name }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $port->governorate->name }}</span>
            @if ($port->location_name)
                <span aria-hidden="true">·</span>
                <span>{{ $port->location_name }}</span>
            @endif
        </p>
    </div>

    <span class="info-status-chip info-status-chip-lg" data-tone="sea">
        <i aria-hidden="true"></i>نشط
    </span>
</header>

<div class="info-admin-tiles">
    @foreach ($tiles as $tile)
        <div class="info-admin-tile" data-tone="{{ $tile['tone'] }}">
            <strong>{{ $tile['value'] }}</strong>
            <span>{{ $tile['label'] }}</span>
        </div>
    @endforeach
</div>

<div class="info-admin-stack">
    <section class="info-form-card info-admin-panel" aria-labelledby="port-facts">
        <div class="info-admin-panel-head">
            <h2 id="port-facts">بيانات الميناء</h2>
        </div>

        <dl class="info-admin-datagrid">
            <div><dt>المنطقة</dt><dd>{{ $port->governorate->region->name }}</dd></div>
            <div><dt>المحافظة</dt><dd>{{ $port->governorate->name }}</dd></div>
            <div><dt>الموقع</dt><dd>{{ $port->location_name ?? '—' }}</dd></div>
            <div><dt>خط العرض</dt><dd dir="ltr">{{ $port->latitude ?? '—' }}</dd></div>
            <div><dt>خط الطول</dt><dd dir="ltr">{{ $port->longitude ?? '—' }}</dd></div>
            <div>
                <dt>رابط الموقع</dt>
                <dd>
                    @if ($port->location_url)
                        <a href="{{ $port->location_url }}" target="_blank" rel="noopener">فتح الخريطة</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <section class="info-form-card info-admin-panel" aria-labelledby="port-berths">
        <div class="info-admin-panel-head">
            <h2 id="port-berths">القوارب حسب النوع</h2>
        </div>

        <div class="info-port-berths">
            @foreach ($boatTypes as $boatType)
                <article class="info-port-berth">
                    <header>
                        <h3>{{ $boatType['label'] }}</h3>
                        <span class="info-status-chip" data-tone="{{ $berthStates[$boatType['status']]['tone'] }}">
                            <i aria-hidden="true"></i>{{ $berthStates[$boatType['status']]['label'] }}
                        </span>
                    </header>

                    <dl class="info-port-figures">
                        <div>
                            <dt>الطاقة</dt>
                            <dd>{{ number_format($boatType['capacity']) }}</dd>
                        </div>
                        <div>
                            <dt>شاغلة</dt>
                            <dd>{{ number_format($boatType['occupied']) }}</dd>
                        </div>
                        <div>
                            <dt>متاحة</dt>
                            <dd>{{ number_format($boatType['available']) }}</dd>
                        </div>
                        <div>
                            <dt>معطلة</dt>
                            <dd>{{ number_format($boatType['disabled']) }}</dd>
                        </div>
                    </dl>

                    <div class="info-port-occupancy">
                        <p><span>نسبة التشغيل</span><b>{{ $boatType['percent'] }}%</b></p>
                        <span class="info-port-meter" aria-hidden="true"><span style="width: {{ $boatType['percent'] }}%"></span></span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="info-form-card info-admin-panel" aria-labelledby="port-workforce">
        <div class="info-admin-panel-head">
            <h2 id="port-workforce">القوى البشرية</h2>
        </div>

        <div class="info-admin-tiles">
            @foreach ($workers as $worker)
                <div class="info-admin-tile">
                    <strong>{{ number_format($worker['count']) }}</strong>
                    <span>{{ $worker['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="info-form-card info-admin-panel" aria-labelledby="port-submissions">
        <div class="info-admin-panel-head">
            <h2 id="port-submissions">طلبات الميناء</h2>
        </div>

        <div class="info-admin-tiles">
            @foreach ($submissions as $submission)
                <a class="info-admin-tile"
                   href="{{ route('information.admin.index', ['port_id' => $port->id, 'status' => $submission['status']]) }}">
                    <strong>{{ number_format($submission['count']) }}</strong>
                    <span>{{ $submission['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
</div>
@endsection

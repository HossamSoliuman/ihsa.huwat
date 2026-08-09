@extends('layouts.information-admin')

@section('title', 'الموانئ')

@section('content')
<header class="info-admin-header">
    <div>
        <p class="info-eyebrow"><span></span>لوحة مركز المعلومات<span></span></p>
        <h1>الموانئ</h1>
    </div>
</header>

<section class="info-form-card info-admin-panel" aria-labelledby="ports-title">
    <div class="info-admin-panel-head">
        <h2 id="ports-title">الموانئ النشطة</h2>

        <form class="info-admin-filters" method="get" action="{{ route('information.admin.ports.index') }}">
            <div class="info-field">
                <label class="sr-only" for="filter-q">بحث</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="ابحث باسم الميناء أو الموقع">
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-region">المنطقة</label>
                <select id="filter-region" name="region_id">
                    <option value="">كل المناطق</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected((int) ($filters['region_id'] ?? 0) === $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-governorate">المحافظة</label>
                <select id="filter-governorate" name="governorate_id">
                    <option value="">كل المحافظات</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate->id }}" @selected((int) ($filters['governorate_id'] ?? 0) === $governorate->id)>{{ $governorate->name }}</option>
                    @endforeach
                </select>
            </div>

            <button class="info-button info-button-primary" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm5.5-1.5L21 21"></path></svg>
                <span>تصفية</span>
            </button>
        </form>
    </div>

    @if ($ports->isEmpty())
        <p class="info-admin-empty">لا توجد موانئ مطابقة لعوامل التصفية الحالية.</p>
    @else
        <div class="info-port-grid">
            @foreach ($ports as $port)
                @php
                    $capacity = (int) $port->berth_capacity;
                    $percent = $capacity > 0 ? min(100, round($port->occupied_boats_count / $capacity * 100, 1)) : 0;
                @endphp

                <a class="info-port-card" href="{{ route('information.admin.ports.show', $port) }}">
                    <header>
                        <h3>{{ $port->name }}</h3>
                        <p>{{ $port->governorate->region->name }} · {{ $port->governorate->name }}</p>
                    </header>

                    <dl class="info-port-figures">
                        <div>
                            <dt>الطاقة الاستيعابية</dt>
                            <dd>{{ number_format($capacity) }}</dd>
                        </div>
                        <div>
                            <dt>القوارب الشاغلة</dt>
                            <dd>{{ number_format($port->occupied_boats_count) }}</dd>
                        </div>
                        <div>
                            <dt>الطلبات</dt>
                            <dd>{{ number_format($port->submissions_count) }}</dd>
                        </div>
                    </dl>

                    <div class="info-port-occupancy">
                        <p><span>نسبة التشغيل</span><b>{{ $percent }}%</b></p>
                        <span class="info-port-meter" aria-hidden="true"><span style="width: {{ $percent }}%"></span></span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection

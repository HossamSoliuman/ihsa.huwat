@extends('layouts.information-admin')

@section('title', 'أسواق السمك · '.$market->name)

@section('content')
@php
    $shops = $market->units->where('unit_type', \App\Models\FishMarketUnit::TYPE_SHOP);
    $auctionStalls = $market->units->where('unit_type', \App\Models\FishMarketUnit::TYPE_AUCTION_STALL);
    $workerCount = $market->units->sum(fn (\App\Models\FishMarketUnit $unit): int => $unit->workers->count());

    $tiles = [
        ['label' => 'محلات بيع السمك', 'value' => $shops->count(), 'tone' => 'sea'],
        ['label' => 'دكات الحراجات', 'value' => $auctionStalls->count(), 'tone' => 'blue'],
        ['label' => 'العمالة', 'value' => $workerCount, 'tone' => 'neutral'],
        ['label' => 'الدلالون', 'value' => $market->brokers->count(), 'tone' => 'gold'],
    ];
@endphp

<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.markets.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى أسواق السمك</span>
        </a>
        <h1>{{ $market->name }}</h1>
        <p class="info-admin-header-meta">
            <span>{{ $market->governorate->region->name }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $market->governorate->name }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $market->investor_name ?? 'المستثمر غير مسجل' }}</span>
        </p>
    </div>

    <span class="info-status-chip info-status-chip-lg" data-tone="{{ $market->is_active ? 'sea' : 'gold' }}">
        <i aria-hidden="true"></i>{{ $market->is_active ? 'نشط' : 'متوقف' }}
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
    <section class="info-form-card info-admin-panel" aria-labelledby="market-details">
        <div class="info-admin-panel-head">
            <h2 id="market-details">بيانات السوق والمستثمر</h2>

            <form method="post" action="{{ route('information.admin.markets.destroy', $market) }}"
                  data-confirm="سيُحذف السوق نهائياً إذا لم تكن عليه محلات أو دكات أو دلالون. هل تريد المتابعة؟">
                @csrf
                @method('DELETE')
                <button class="info-lookup-action is-danger" type="submit">حذف السوق</button>
            </form>
        </div>

        <x-information.market-form
            :market="$market"
            :regions="$regions"
            :governorates="$governorates"
            :active="old('form_key') === 'market-'.$market->id"
        />
    </section>

    <x-information.market-unit-panel
        :market="$market"
        :type="\App\Models\FishMarketUnit::TYPE_SHOP"
        :units="$shops"
        :nationalities="$nationalities"
        :job-titles="$jobTitles"
        :nationality-labels="$nationalityLabels"
        :job-title-labels="$jobTitleLabels"
    />

    <x-information.market-unit-panel
        :market="$market"
        :type="\App\Models\FishMarketUnit::TYPE_AUCTION_STALL"
        :units="$auctionStalls"
        :nationalities="$nationalities"
        :job-titles="$jobTitles"
        :nationality-labels="$nationalityLabels"
        :job-title-labels="$jobTitleLabels"
    />

    <section class="info-form-card info-admin-panel" aria-labelledby="market-brokers">
        <div class="info-admin-panel-head">
            <h2 id="market-brokers">الدلالون المرتبطون بالسوق</h2>

            <a class="info-lookup-action" href="{{ route('information.admin.brokers.index', ['fish_market_id' => $market->id]) }}">
                إدارة الدلالين
            </a>
        </div>

        <div class="info-admin-table-scroll">
            <table class="info-admin-table">
                <caption class="sr-only">الدلالون المسجلون على {{ $market->name }}</caption>
                <thead>
                    <tr>
                        <th scope="col">الدلال</th>
                        <th scope="col">النوع</th>
                        <th scope="col">رقم الجوال</th>
                        <th scope="col">الحالة</th>
                        <th scope="col"><span class="sr-only">الإجراءات</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($market->brokers as $broker)
                        <tr @class(['is-retired' => ! $broker->is_active])>
                            <td><strong>{{ $broker->displayName() }}</strong></td>
                            <td>{{ $broker->entityTypeLabel() }}</td>
                            <td><span dir="ltr">{{ $broker->phone ?? '—' }}</span></td>
                            <td>
                                <span class="info-status-chip" data-tone="{{ $broker->is_active ? 'sea' : 'gold' }}">
                                    <i aria-hidden="true"></i>{{ $broker->is_active ? 'نشط' : 'متوقف' }}
                                </span>
                            </td>
                            <td>
                                <a class="info-lookup-action" href="{{ route('information.admin.brokers.show', $broker) }}">تعديل</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="info-admin-empty">لا يوجد دلالون مسجلون على هذا السوق.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
